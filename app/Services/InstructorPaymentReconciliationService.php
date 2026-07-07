<?php

namespace App\Services;

use App\Models\EnrollmentPaymentItem;
use App\Models\InstructorPayment;

class InstructorPaymentReconciliationService
{
    /**
     * Cache por (instructor_workshop_id, monthly_period_id) para evitar
     * consultas repetidas cuando la tabla pide estado y tooltip por fila.
     * Estático: el contenedor resuelve una instancia nueva por llamada.
     */
    private static array $cache = [];

    /**
     * Concilia el revenue esperado de un taller/período contra los pagos
     * realmente registrados (RN-A6).
     *
     * - esperado: Σ total_amount de inscripciones cobradas (payment_status = 'completed')
     * - cobrado:  Σ enrollment_payment_items.amount vinculados a esas inscripciones,
     *             de pagos con status 'completed'
     *
     * La alerta es solo informativa: no bloquea ni ajusta el pago al instructor.
     */
    public function reconcile(InstructorPayment $payment): array
    {
        $key = $payment->instructor_workshop_id.'-'.$payment->monthly_period_id;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $enrollmentFilter = function ($query) use ($payment) {
            $query->where('instructor_workshop_id', $payment->instructor_workshop_id)
                ->where('monthly_period_id', $payment->monthly_period_id)
                ->whereNull('cancelled_at')
                ->where('payment_status', 'completed');
        };

        $expected = (float) \App\Models\StudentEnrollment::where($enrollmentFilter)
            ->sum('total_amount');

        $collected = (float) EnrollmentPaymentItem::whereHas('studentEnrollment', $enrollmentFilter)
            ->whereHas('enrollmentPayment', function ($query) {
                $query->where('status', 'completed');
            })
            ->sum('amount');

        $difference = round($expected - $collected, 2);

        return self::$cache[$key] = [
            'expected'    => round($expected, 2),
            'collected'   => round($collected, 2),
            'difference'  => $difference,
            'is_balanced' => abs($difference) < 0.01,
        ];
    }

    public function isBalanced(InstructorPayment $payment): bool
    {
        return $this->reconcile($payment)['is_balanced'];
    }
}
