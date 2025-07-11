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
            // LÓGICA PARA INSTRUCTORES VOLUNTARIOS
            
            // Determinar el porcentaje a aplicar
            $appliedVolunteerPercentage = $instructorWorkshop->custom_volunteer_percentage 
                                        ?? $monthlyRate?->volunteer_percentage 
                                        ?? 0.5000; // 50% por defecto

            // Sumar total_amount de todas las inscripciones de estudiantes
            $monthlyRevenue = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->sum('total_amount');

            // Contar estudiantes únicos
            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->distinct('student_id')
                ->count('student_id');

            $calculatedAmount = $monthlyRevenue * $appliedVolunteerPercentage;
            
            // Campos originales para auditoría
            $volunteerPercentage = $monthlyRate?->volunteer_percentage;

        } else {
            // LÓGICA PARA INSTRUCTORES NO VOLUNTARIOS (POR HORAS)
            
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
                
                // Preservar estado de pago existente
                'payment_status' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                                    ->where('monthly_period_id', $monthlyPeriodId)
                                                    ->value('payment_status') ?? 'pending',
                'payment_date' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                                   ->where('monthly_period_id', $monthlyPeriodId)
                                                   ->value('payment_date'),
                'notes' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                            ->where('monthly_period_id', $monthlyPeriodId)
                                            ->value('notes'),
            ]
        );
    }
}