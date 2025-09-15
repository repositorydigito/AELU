<?php

namespace App\Observers;

use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyInstructorRate;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentObserver
{
    public function created(StudentEnrollment $studentEnrollment): void
    {
        // Solo calcular cuando el estado sea 'completed'
        if ($studentEnrollment->payment_status === 'completed') {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }

    public function updated(StudentEnrollment $studentEnrollment): void
    {
        // Verificar si cambió el estado de pago
        if ($studentEnrollment->isDirty('payment_status')) {
            $this->handlePaymentStatusChange($studentEnrollment);
        }

        // Si cambió otros campos relevantes Y el estado es 'completed', recalcular
        if ($studentEnrollment->payment_status === 'completed' &&
            $studentEnrollment->isDirty(['total_amount', 'instructor_workshop_id', 'monthly_period_id'])) {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }

    public function deleted(StudentEnrollment $studentEnrollment): void
    {
        // Solo recalcular si la inscripción eliminada estaba en estado 'completed'
        if ($studentEnrollment->payment_status === 'completed') {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }

    /**
     * Manejar cambios en el estado de pago
     */
    protected function handlePaymentStatusChange(StudentEnrollment $studentEnrollment): void
    {
        $oldStatus = $studentEnrollment->getOriginal('payment_status');
        $newStatus = $studentEnrollment->payment_status;

        // Caso 1: De cualquier estado a 'completed' - CREAR/ACTUALIZAR pago
        if ($newStatus === 'completed') {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }

        // Caso 2: De 'completed' a cualquier otro estado - RECALCULAR sin esta inscripción
        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }

        // Caso 3: Anulación específica - ELIMINAR completamente si no hay más inscripciones válidas
        if ($newStatus === 'refunded') {
            $this->handleCancellationOrRefund($studentEnrollment);
        }
    }

    /**
     * Manejar anulaciones y devoluciones
     */
    protected function handleCancellationOrRefund(StudentEnrollment $studentEnrollment): void
    {
        $instructorWorkshopId = $studentEnrollment->instructor_workshop_id;
        $monthlyPeriodId = $studentEnrollment->monthly_period_id;

        if (!$instructorWorkshopId || !$monthlyPeriodId) {
            return;
        }

        // Verificar si quedan inscripciones completadas para este instructor/período
        $remainingCompletedEnrollments = StudentEnrollment::where('instructor_workshop_id', $instructorWorkshopId)
            ->where('monthly_period_id', $monthlyPeriodId)
            ->where('payment_status', 'completed')
            ->where('id', '!=', $studentEnrollment->id) // Excluir la actual
            ->count();

        if ($remainingCompletedEnrollments === 0) {
            // No quedan inscripciones válidas, eliminar el pago del instructor
            InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->delete();
        } else {
            // Quedan inscripciones válidas, recalcular el pago
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }

    protected function calculateAndSaveInstructorPayment(StudentEnrollment $studentEnrollment): void
    {
        $instructorWorkshopId = $studentEnrollment->instructor_workshop_id;
        $monthlyPeriodId = $studentEnrollment->monthly_period_id;

        if (! $instructorWorkshopId || ! $monthlyPeriodId) {
            return;
        }

        $instructorWorkshop = InstructorWorkshop::find($instructorWorkshopId);
        if (! $instructorWorkshop) {
            return;
        }

        // ✅ VERIFICAR SI HAY INSCRIPCIONES COMPLETADAS ANTES DE CALCULAR
        $completedEnrollments = StudentEnrollment::where('instructor_workshop_id', $instructorWorkshopId)
            ->where('monthly_period_id', $monthlyPeriodId)
            ->where('payment_status', 'completed')
            ->count();

        // Si no hay inscripciones completadas, eliminar el pago del instructor y salir
        if ($completedEnrollments === 0) {
            InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->delete();
            return;
        }

        // Obtener la tarifa mensual para voluntarios
        $monthlyRate = MonthlyInstructorRate::where('monthly_period_id', $monthlyPeriodId)
            ->where('is_active', true)
            ->first();

        $calculatedAmount = 0;
        $paymentType = $instructorWorkshop->payment_type;
        $totalStudents = null;
        $monthlyRevenue = null;
        $totalHours = null;
        $hourlyRate = null;
        $volunteerPercentage = null;
        $appliedHourlyRate = null;
        $appliedVolunteerPercentage = null;

        if ($instructorWorkshop->payment_type == 'volunteer') {
            // 🎯 LÓGICA PARA INSTRUCTORES VOLUNTARIOS
            $appliedVolunteerPercentage = $instructorWorkshop->custom_volunteer_percentage ?? 50.0000;

            $monthlyRevenue = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->where('payment_status', 'completed')
                ->sum('total_amount');

            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->where('payment_status', 'completed')
                ->distinct('student_id')
                ->count('student_id');

            $calculatedAmount = $monthlyRevenue * ($appliedVolunteerPercentage / 100);
            $volunteerPercentage = $monthlyRate?->volunteer_percentage;
        }

        if ($instructorWorkshop->payment_type == 'hourly') {
            // 🎯 LÓGICA PARA INSTRUCTORES NO VOLUNTARIOS (POR HORAS)
            $appliedHourlyRate = $instructorWorkshop->hourly_rate ?? 0;
            $classesPerMonth = 4;

            $durationHours = $instructorWorkshop->duration_hours;
            if (! $durationHours && $instructorWorkshop->workshop) {
                $durationMinutes = $instructorWorkshop->workshop->duration ?? 60;
                $durationHours = $durationMinutes / 60;
            }
            $durationHours = $durationHours ?? 1;

            $totalHours = $classesPerMonth * $durationHours;
            $calculatedAmount = $totalHours * $appliedHourlyRate;
            $hourlyRate = $instructorWorkshop->hourly_rate;

            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->where('payment_status', 'completed')
                ->distinct('student_id')
                ->count('student_id');
        }

        // Preparar notas informativas
        $notes = '';
        if ($instructorWorkshop->isVolunteer()) {
            $notes = "Pago voluntario: {$totalStudents} estudiantes. Ingresos totales: S/ ".number_format($monthlyRevenue, 2)." × ".number_format($appliedVolunteerPercentage, 2)."% = S/ ".number_format($calculatedAmount, 2);
        } elseif ($instructorWorkshop->isHourly()) {
            $notes = "Pago por horas: {$totalHours} horas totales (4 clases × {$durationHours} hrs/clase) × S/ {$appliedHourlyRate}/hora = S/ ".number_format($calculatedAmount, 2);
        }

        // Crear o actualizar el registro de pago del instructor
        InstructorPayment::updateOrCreate(
            [
                'instructor_workshop_id' => $instructorWorkshopId,
                'monthly_period_id' => $monthlyPeriodId,
            ],
            [
                'instructor_id' => $instructorWorkshop->instructor_id,
                'monthly_instructor_rate_id' => $monthlyRate?->id,
                'payment_type' => $paymentType,
                'total_students' => $totalStudents,
                'monthly_revenue' => $monthlyRevenue,
                'volunteer_percentage' => $volunteerPercentage,
                'total_hours' => $totalHours,
                'hourly_rate' => $hourlyRate,
                'applied_hourly_rate' => $appliedHourlyRate,
                'applied_volunteer_percentage' => $appliedVolunteerPercentage/100,
                'calculated_amount' => round($calculatedAmount, 2),
                'notes' => $notes,
                'payment_status' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                    ->where('monthly_period_id', $monthlyPeriodId)
                    ->value('payment_status') ?? 'pending',
                'payment_date' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                    ->where('monthly_period_id', $monthlyPeriodId)
                    ->value('payment_date'),
            ]
        );
    }
}
