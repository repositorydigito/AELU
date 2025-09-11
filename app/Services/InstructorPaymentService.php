<?php

namespace App\Services;

use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyInstructorRate;
use App\Models\MonthlyPeriod;

class InstructorPaymentService
{
    /**
     * Calcula y crea/actualiza el pago para un instructor_workshop en un período específico
     */
    public function calculatePaymentForInstructorWorkshop(
        InstructorWorkshop $instructorWorkshop,
        MonthlyPeriod $monthlyPeriod
    ): InstructorPayment {

        $monthlyRate = $monthlyPeriod->getActiveInstructorRate();

        if ($instructorWorkshop->isVolunteer()) {
            return $this->calculateVolunteerPayment($instructorWorkshop, $monthlyPeriod, $monthlyRate);
        } else {
            return $this->calculateHourlyPayment($instructorWorkshop, $monthlyPeriod, $monthlyRate);
        }
    }

    /**
     * Calcula pago para instructor voluntario (porcentaje de ingresos)
     */
    private function calculateVolunteerPayment(
        InstructorWorkshop $instructorWorkshop,
        MonthlyPeriod $monthlyPeriod,
        ?MonthlyInstructorRate $monthlyRate
    ): InstructorPayment {

        // Obtener ingresos del taller en el período
        $monthlyRevenue = $this->getWorkshopRevenueForPeriod($instructorWorkshop, $monthlyPeriod);
        $totalStudents = $this->getTotalStudentsForPeriod($instructorWorkshop, $monthlyPeriod);

        // Determinar porcentaje a aplicar
        $appliedPercentage = $instructorWorkshop->getEffectiveVolunteerPercentage($monthlyRate);

        $calculatedAmount = $monthlyRevenue * $appliedPercentage;

        return InstructorPayment::updateOrCreate(
            [
                'instructor_workshop_id' => $instructorWorkshop->id,
                'monthly_period_id' => $monthlyPeriod->id,
            ],
            [
                'instructor_id' => $instructorWorkshop->instructor_id,
                'monthly_instructor_rate_id' => $monthlyRate?->id,
                'payment_type' => 'volunteer',
                'total_students' => $totalStudents,
                'monthly_revenue' => $monthlyRevenue,
                'volunteer_percentage' => $monthlyRate?->volunteer_percentage,
                'applied_volunteer_percentage' => $appliedPercentage,
                'calculated_amount' => round($calculatedAmount, 2),
                'payment_status' => 'pending',
            ]
        );
    }

    /**
     * Calcula pago para instructor por horas (tarifa fija * horas dictadas)
     */
    private function calculateHourlyPayment(
        InstructorWorkshop $instructorWorkshop,
        MonthlyPeriod $monthlyPeriod,
        ?MonthlyInstructorRate $monthlyRate
    ): InstructorPayment {

        // Obtener total de horas dictadas en el período
        $totalHours = $this->getTotalHoursForPeriod($instructorWorkshop, $monthlyPeriod);

        // Usar tarifa del instructor workshop
        $appliedHourlyRate = $instructorWorkshop->hourly_rate;

        $calculatedAmount = $totalHours * $appliedHourlyRate;

        return InstructorPayment::updateOrCreate(
            [
                'instructor_workshop_id' => $instructorWorkshop->id,
                'monthly_period_id' => $monthlyPeriod->id,
            ],
            [
                'instructor_id' => $instructorWorkshop->instructor_id,
                'monthly_instructor_rate_id' => $monthlyRate?->id,
                'payment_type' => 'hourly',
                'total_hours' => $totalHours,
                'hourly_rate' => $instructorWorkshop->hourly_rate,
                'applied_hourly_rate' => $appliedHourlyRate,
                'calculated_amount' => round($calculatedAmount, 2),
                'payment_status' => 'pending',
            ]
        );
    }

    /**
     * Obtiene los ingresos totales de un taller en un período
     */
    private function getWorkshopRevenueForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): float
    {
        return $instructorWorkshop->studentEnrollments()
            ->where('monthly_period_id', $monthlyPeriod->id)
            ->sum('total_amount');
    }

    /**
     * Obtiene el total de estudiantes únicos inscritos en un período
     */
    private function getTotalStudentsForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): int
    {
        return $instructorWorkshop->studentEnrollments()
            ->where('monthly_period_id', $monthlyPeriod->id)
            ->distinct('student_id')
            ->count('student_id');
    }

    /**
     * Calcula las horas totales dictadas en un período
     * (número_de_clases_en_el_mes * duration_hours)
     */
    private function getTotalHoursForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): float
    {
        // Contar clases programadas/completadas en el mes
        $classesCount = $instructorWorkshop->workshopClasses()
            ->where('monthly_period_id', $monthlyPeriod->id)
            ->whereIn('status', ['scheduled', 'completed'])
            ->count();

        // Multiplicar por la duración de cada clase
        return $classesCount * ($instructorWorkshop->duration_hours ?? 0);
    }

    /**
     * Recalcula pagos para todos los instructores de un período específico
     */
    public function recalculatePaymentsForPeriod(MonthlyPeriod $monthlyPeriod): int
    {
        $count = 0;

        // Obtener todos los instructor_workshops que tienen clases en este período
        $instructorWorkshops = InstructorWorkshop::whereHas('workshopClasses', function ($query) use ($monthlyPeriod) {
            $query->where('monthly_period_id', $monthlyPeriod->id);
        })->get();

        foreach ($instructorWorkshops as $instructorWorkshop) {
            $this->calculatePaymentForInstructorWorkshop($instructorWorkshop, $monthlyPeriod);
            $count++;
        }

        return $count;
    }
}
