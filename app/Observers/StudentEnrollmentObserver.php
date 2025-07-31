<?php

namespace App\Observers;

use App\Models\StudentEnrollment;
use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use App\Models\MonthlyInstructorRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Este observer se utiliza para crear InstructorPayments cuando se crean,actualizan o eliminan StudentEnrollments.
class StudentEnrollmentObserver
{
    public function created(StudentEnrollment $studentEnrollment): void
    {
        $this->calculateAndSaveInstructorPayment($studentEnrollment);
    }

    public function updated(StudentEnrollment $studentEnrollment): void
    {
        if ($studentEnrollment->isDirty(['total_amount', 'instructor_workshop_id', 'monthly_period_id'])) {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }

    public function deleted(StudentEnrollment $studentEnrollment): void
    {
        $this->calculateAndSaveInstructorPayment($studentEnrollment);
    }

    protected function calculateAndSaveInstructorPayment(StudentEnrollment $studentEnrollment): void
    {
        $instructorWorkshopId = $studentEnrollment->instructor_workshop_id;
        $monthlyPeriodId = $studentEnrollment->monthly_period_id;

        if (!$instructorWorkshopId || !$monthlyPeriodId) {
            return;
        }

        $instructorWorkshop = InstructorWorkshop::find($instructorWorkshopId);
        if (!$instructorWorkshop) {
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
            // 🎯 LÓGICA PARA INSTRUCTORES VOLUNTARIOS CON ESTUDIANTES EXENTOS

            // Determinar el porcentaje a aplicar
            $appliedVolunteerPercentage = $instructorWorkshop->custom_volunteer_percentage ?? 0.5000;

            // 🔥 NUEVA LÓGICA CORREGIDA:
            // TODOS los estudiantes pagan por talleres, solo aplicar descuento del 50% a 'Individual PRE-PAMA'
            $enrollments = DB::table('student_enrollments')
                ->join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.instructor_workshop_id', $instructorWorkshopId)
                ->where('student_enrollments.monthly_period_id', $monthlyPeriodId)
                ->select(
                    'student_enrollments.total_amount',
                    'students.category_partner',
                    'students.first_names',
                    'students.last_names'
                )
                ->get();

            $monthlyRevenue = 0;
            $regularStudents = 0;
            $prepmaStudents = 0;

            foreach ($enrollments as $enrollment) {
                if ($enrollment->category_partner === 'Individual PRE-PAMA') {
                    // Estudiantes PRE-PAMA pagan el 50% del precio normal
                    $monthlyRevenue += $enrollment->total_amount * 1.5;
                    $prepmaStudents++;
                } else {
                    // Todos los demás estudiantes pagan el precio completo
                    $monthlyRevenue += $enrollment->total_amount;
                    $regularStudents++;
                }
            }

            $totalStudents = $regularStudents + $prepmaStudents;
            $calculatedAmount = $monthlyRevenue * $appliedVolunteerPercentage;

            // Campo original para auditoría
            $volunteerPercentage = $monthlyRate?->volunteer_percentage;

        }

        if ($instructorWorkshop->payment_type == 'hourly') {
            // 🎯 LÓGICA PARA INSTRUCTORES NO VOLUNTARIOS (POR HORAS)
            // Siempre son 4 clases por mes según especificación del usuario

            $appliedHourlyRate = $instructorWorkshop->hourly_rate ?? 0;

            // CAMBIO: Siempre usar 4 clases por mes (no las clases programadas)
            $classesPerMonth = 4;

            // Obtener la duración en horas desde InstructorWorkshop
            // Si no está definida, calcular desde el Workshop (convertir minutos a horas)
            $durationHours = $instructorWorkshop->duration_hours;

            if (!$durationHours && $instructorWorkshop->workshop) {
                // Convertir minutos del workshop a horas
                $durationMinutes = $instructorWorkshop->workshop->duration ?? 60;
                $durationHours = $durationMinutes / 60;
            }

            $durationHours = $durationHours ?? 1; // Default 1 hora si no se puede determinar

            // Calcular total de horas: 4 clases * duration_hours
            $totalHours = $classesPerMonth * $durationHours;

            $calculatedAmount = $totalHours * $appliedHourlyRate;

            // Campos originales para auditoría
            $hourlyRate = $instructorWorkshop->hourly_rate;

            // Contar todos los estudiantes para estadísticas
            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->distinct('student_id')
                ->count('student_id');
        }

        // Preparar notas informativas
        $notes = '';
        if ($instructorWorkshop->isVolunteer() && isset($prepmaStudents, $regularStudents)) {
            if ($prepmaStudents > 0) {
                $notes = "Ingresos calculados: {$regularStudents} estudiantes regulares + {$prepmaStudents} estudiantes PRE-PAMA (50% descuento). Total: {$totalStudents} estudiantes.";
            } else {
                $notes = "Todos los {$totalStudents} estudiantes pagan precio completo. Ingresos totales: S/ " . number_format($monthlyRevenue, 2);
            }
        } elseif ($instructorWorkshop->isHourly()) {
            $notes = "Pago por horas: {$totalHours} horas totales (4 clases × {$durationHours} hrs/clase) × S/ {$appliedHourlyRate}/hora = S/ " . number_format($calculatedAmount, 2);
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

                // Para voluntarios
                'total_students' => $totalStudents,
                'monthly_revenue' => $monthlyRevenue,
                'volunteer_percentage' => $volunteerPercentage,

                // Para por horas
                'total_hours' => $totalHours,
                'hourly_rate' => $hourlyRate,

                // Campos aplicados (los que se usaron realmente)
                'applied_hourly_rate' => $appliedHourlyRate,
                'applied_volunteer_percentage' => $appliedVolunteerPercentage,

                // Resultado final
                'calculated_amount' => round($calculatedAmount, 2),

                // Notas informativas
                'notes' => $notes,

                // Preservar estado de pago existente
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
