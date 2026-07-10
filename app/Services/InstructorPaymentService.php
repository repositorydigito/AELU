<?php

namespace App\Services;

/**
 * @deprecated CÓDIGO MUERTO — NO se usa en runtime (verificado 2026-07: 0 llamadores
 * en toda la app; ni comandos, ni scheduler `routes/console.php`, ni páginas/resources/tests).
 *
 * El cálculo real del pago al instructor lo hace `App\Observers\StudentEnrollmentObserver`
 * (método `calculateAndSaveInstructorPayment`), que corre en cada cambio de una inscripción
 * a `payment_status='completed'` y escribe `instructor_payments` con `updateOrCreate`.
 * Ver también el comentario en `InstructorPaymentResource::canCreate()`.
 *
 * Se conserva SOLO como referencia (comentado abajo). ⚠️ Si algún día se revive, ojo con
 * que DIFIERE del observer y habría que reconciliarlo antes de usarlo:
 *  - Revenue voluntario: aquí NO se resta el crédito de recuperación aplicado (RN-D5);
 *    el observer sí lo resta vía `EnrollmentPaymentItem::creditAppliedForEnrollments()`.
 *  - Horas (hourly): aquí se cuentan los `WorkshopClass` reales scheduled/completed
 *    (`getTotalHoursForPeriod`); el observer hardcodea 4 clases/mes.
 *  - % voluntario: aquí `getEffectiveVolunteerPercentage($monthlyRate)`; el observer usa
 *    `custom_volunteer_percentage ?? 50`.
 */
class InstructorPaymentService
{
    // ————————————————————————————————————————————————————————————————————————
    // Implementación original conservada como referencia (deshabilitada):
    // ————————————————————————————————————————————————————————————————————————
    //
    // use App\Models\InstructorPayment;
    // use App\Models\InstructorWorkshop;
    // use App\Models\MonthlyInstructorRate;
    // use App\Models\MonthlyPeriod;
    //
    // /**
    //  * Calcula y crea/actualiza el pago para un instructor_workshop en un período específico
    //  */
    // public function calculatePaymentForInstructorWorkshop(
    //     InstructorWorkshop $instructorWorkshop,
    //     MonthlyPeriod $monthlyPeriod
    // ): InstructorPayment {
    //
    //     $monthlyRate = $monthlyPeriod->getActiveInstructorRate();
    //
    //     if ($instructorWorkshop->isVolunteer()) {
    //         return $this->calculateVolunteerPayment($instructorWorkshop, $monthlyPeriod, $monthlyRate);
    //     } else {
    //         return $this->calculateHourlyPayment($instructorWorkshop, $monthlyPeriod, $monthlyRate);
    //     }
    // }
    //
    // /**
    //  * Calcula pago para instructor voluntario (porcentaje de ingresos)
    //  */
    // private function calculateVolunteerPayment(
    //     InstructorWorkshop $instructorWorkshop,
    //     MonthlyPeriod $monthlyPeriod,
    //     ?MonthlyInstructorRate $monthlyRate
    // ): InstructorPayment {
    //
    //     $monthlyRevenue = $this->getWorkshopRevenueForPeriod($instructorWorkshop, $monthlyPeriod);
    //     $totalStudents = $this->getTotalStudentsForPeriod($instructorWorkshop, $monthlyPeriod);
    //
    //     $appliedPercentage = $instructorWorkshop->getEffectiveVolunteerPercentage($monthlyRate);
    //
    //     $calculatedAmount = $monthlyRevenue * $appliedPercentage;
    //
    //     $payment = InstructorPayment::firstOrNew([
    //         'instructor_workshop_id' => $instructorWorkshop->id,
    //         'monthly_period_id'      => $monthlyPeriod->id,
    //     ]);
    //
    //     $payment->fill([
    //         'instructor_id'                => $instructorWorkshop->instructor_id,
    //         'monthly_instructor_rate_id'   => $monthlyRate?->id,
    //         'payment_type'                 => 'volunteer',
    //         'total_students'               => $totalStudents,
    //         'monthly_revenue'              => $monthlyRevenue,
    //         'volunteer_percentage'         => $monthlyRate?->volunteer_percentage,
    //         'applied_volunteer_percentage' => $appliedPercentage,
    //         'calculated_amount'            => round($calculatedAmount, 2),
    //     ]);
    //
    //     if (! $payment->exists) {
    //         $payment->payment_status = 'pending';
    //     }
    //
    //     $payment->save();
    //
    //     return $payment;
    // }
    //
    // /**
    //  * Calcula pago para instructor por horas (tarifa fija * horas dictadas)
    //  */
    // private function calculateHourlyPayment(
    //     InstructorWorkshop $instructorWorkshop,
    //     MonthlyPeriod $monthlyPeriod,
    //     ?MonthlyInstructorRate $monthlyRate
    // ): InstructorPayment {
    //
    //     $totalHours = $this->getTotalHoursForPeriod($instructorWorkshop, $monthlyPeriod);
    //
    //     $appliedHourlyRate = $instructorWorkshop->hourly_rate;
    //
    //     $calculatedAmount = $totalHours * $appliedHourlyRate;
    //
    //     $payment = InstructorPayment::firstOrNew([
    //         'instructor_workshop_id' => $instructorWorkshop->id,
    //         'monthly_period_id'      => $monthlyPeriod->id,
    //     ]);
    //
    //     $payment->fill([
    //         'instructor_id'             => $instructorWorkshop->instructor_id,
    //         'monthly_instructor_rate_id' => $monthlyRate?->id,
    //         'payment_type'              => 'hourly',
    //         'total_hours'               => $totalHours,
    //         'hourly_rate'               => $instructorWorkshop->hourly_rate,
    //         'applied_hourly_rate'       => $appliedHourlyRate,
    //         'calculated_amount'         => round($calculatedAmount, 2),
    //     ]);
    //
    //     if (! $payment->exists) {
    //         $payment->payment_status = 'pending';
    //     }
    //
    //     $payment->save();
    //
    //     return $payment;
    // }
    //
    // /**
    //  * Obtiene los ingresos totales de un taller en un período.
    //  * Solo dinero real: inscripciones cobradas (payment_status = 'completed'),
    //  * independiente del estado del lote (RN-A3/RN-A4).
    //  */
    // private function getWorkshopRevenueForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): float
    // {
    //     return $instructorWorkshop->enrollments()
    //         ->where('monthly_period_id', $monthlyPeriod->id)
    //         ->where('payment_status', 'completed')
    //         ->sum('total_amount');
    // }
    //
    // /**
    //  * Obtiene el total de estudiantes únicos inscritos en un período
    //  */
    // private function getTotalStudentsForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): int
    // {
    //     return $instructorWorkshop->enrollments()
    //         ->where('monthly_period_id', $monthlyPeriod->id)
    //         ->distinct('student_id')
    //         ->count('student_id');
    // }
    //
    // /**
    //  * Calcula las horas totales dictadas en un período
    //  * (número_de_clases_en_el_mes * duration_hours)
    //  */
    // private function getTotalHoursForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): float
    // {
    //     $classesCount = \App\Models\WorkshopClass::where('workshop_id', $instructorWorkshop->workshop_id)
    //         ->where('monthly_period_id', $monthlyPeriod->id)
    //         ->whereIn('status', ['scheduled', 'completed'])
    //         ->count();
    //
    //     $durationHours = $instructorWorkshop->duration_hours;
    //     if (!$durationHours && $instructorWorkshop->workshop) {
    //         $durationHours = ($instructorWorkshop->workshop->duration ?? 60) / 60;
    //     }
    //
    //     return $classesCount * ($durationHours ?? 0);
    // }
    //
    // /**
    //  * Recalcula pagos para todos los instructores de un período específico
    //  */
    // public function recalculatePaymentsForPeriod(MonthlyPeriod $monthlyPeriod): int
    // {
    //     $count = 0;
    //
    //     $instructorWorkshops = InstructorWorkshop::whereHas('workshopClasses', function ($query) use ($monthlyPeriod) {
    //         $query->where('monthly_period_id', $monthlyPeriod->id);
    //     })->get();
    //
    //     foreach ($instructorWorkshops as $instructorWorkshop) {
    //         $this->calculatePaymentForInstructorWorkshop($instructorWorkshop, $monthlyPeriod);
    //         $count++;
    //     }
    //
    //     return $count;
    // }
}
