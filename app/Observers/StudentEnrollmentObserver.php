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

        if ($instructorWorkshop->isVolunteer()) {
            // 🎯 LÓGICA PARA INSTRUCTORES VOLUNTARIOS CON ESTUDIANTES EXENTOS
            
            // Determinar el porcentaje a aplicar
            $appliedVolunteerPercentage = $instructorWorkshop->custom_volunteer_percentage 
                                        ?? $monthlyRate?->volunteer_percentage 
                                        ?? 0.5000; // 50% por defecto

            // 🔥 NUEVA LÓGICA: Solo sumar ingresos de estudiantes que PAGAN
            // Excluir estudiantes exentos del cálculo de ingresos
            $monthlyRevenue = DB::table('student_enrollments')
                ->join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.instructor_workshop_id', $instructorWorkshopId)
                ->where('student_enrollments.monthly_period_id', $monthlyPeriodId)
                ->where('student_enrollments.total_amount', '>', 0) // Solo inscripciones con pago > 0
                ->where('students.has_payment_exemption', false) // Excluir estudiantes exentos
                ->sum('student_enrollments.total_amount');

            // Contar TODOS los estudiantes (incluyendo exentos) para estadísticas generales
            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->distinct('student_id')
                ->count('student_id');

            // Contar solo estudiantes que pagan para referencia
            $payingStudents = DB::table('student_enrollments')
                ->join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->where('student_enrollments.instructor_workshop_id', $instructorWorkshopId)
                ->where('student_enrollments.monthly_period_id', $monthlyPeriodId)
                ->where('student_enrollments.total_amount', '>', 0)
                ->where('students.has_payment_exemption', false)
                ->distinct('student_enrollments.student_id')
                ->count('student_enrollments.student_id');

            $calculatedAmount = $monthlyRevenue * $appliedVolunteerPercentage;
            
            // Campos originales para auditoría
            $volunteerPercentage = $monthlyRate?->volunteer_percentage;

            // 📝 Log para debugging de voluntarios
            \Log::info("Cálculo de pago instructor voluntario", [
                'instructor_workshop_id' => $instructorWorkshopId,
                'monthly_period_id' => $monthlyPeriodId,
                'total_students' => $totalStudents,
                'paying_students' => $payingStudents,
                'exempt_students' => $totalStudents - $payingStudents,
                'monthly_revenue' => $monthlyRevenue,
                'applied_percentage' => $appliedVolunteerPercentage,
                'calculated_amount' => $calculatedAmount
            ]);

        } else {
            // LÓGICA PARA INSTRUCTORES NO VOLUNTARIOS (POR HORAS)
            // Esta lógica NO cambia porque se basa en horas dictadas, no en ingresos
            
            $appliedHourlyRate = $instructorWorkshop->hourly_rate;
            
            // Contar clases programadas/completadas en el mes
            $classesInMonth = WorkshopClass::where('instructor_workshop_id', $instructorWorkshopId)
                                          ->where('monthly_period_id', $monthlyPeriodId)
                                          ->whereIn('status', ['scheduled', 'completed'])
                                          ->count();

            // Calcular total de horas: clases_en_el_mes * duration_hours
            $totalHours = $classesInMonth * ($instructorWorkshop->duration_hours ?? 0);
            
            $calculatedAmount = $totalHours * $appliedHourlyRate;
            
            // Campos originales para auditoría
            $hourlyRate = $instructorWorkshop->hourly_rate;
            
            // Para instructores por horas, total_students incluye a todos
            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->distinct('student_id')
                ->count('student_id');

            // 📝 Log para debugging de por horas
            \Log::info("Cálculo de pago instructor por horas", [
                'instructor_workshop_id' => $instructorWorkshopId,
                'monthly_period_id' => $monthlyPeriodId,
                'total_students' => $totalStudents,
                'classes_in_month' => $classesInMonth,
                'total_hours' => $totalHours,
                'hourly_rate' => $appliedHourlyRate,
                'calculated_amount' => $calculatedAmount
            ]);
        }

        // Preparar notas informativas
        $notes = '';
        if ($instructorWorkshop->isVolunteer() && isset($payingStudents)) {
            $exemptCount = $totalStudents - $payingStudents;
            if ($exemptCount > 0) {
                $notes = "Ingresos calculados de {$payingStudents} estudiantes que pagan (de {$totalStudents} total). {$exemptCount} estudiantes exentos no contribuyen al cálculo.";
            } else {
                $notes = "Todos los {$totalStudents} estudiantes pagan. Ingresos totales: S/ " . number_format($monthlyRevenue, 2);
            }
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
                
                // Notas informativas sobre estudiantes exentos
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