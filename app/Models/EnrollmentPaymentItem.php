<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EnrollmentPaymentItem extends Model
{
    protected $fillable = [
        'enrollment_payment_id',
        'student_enrollment_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Monto REALMENTE aplicado con crédito de recuperación (payment_method='credito')
     * a un conjunto de inscripciones. Es el valor capado en processPaymentWithCredit
     * (min(credit.amount, total_amount)) — NO el StudentCredit.amount completo, que
     * puede exceder lo aplicado y provocaría sobre-resta (RN-D5 / RN-D23).
     *
     * @param  array<int>|Collection<int, int>  $enrollmentIds
     */
    public static function creditAppliedForEnrollments(array|Collection $enrollmentIds): float
    {
        $ids = collect($enrollmentIds)->all();

        if (empty($ids)) {
            return 0.0;
        }

        return (float) static::whereIn('student_enrollment_id', $ids)
            ->whereHas('enrollmentPayment', fn ($query) => $query->where('payment_method', 'credito'))
            ->sum('amount');
    }

    public function enrollmentPayment()
    {
        return $this->belongsTo(EnrollmentPayment::class);
    }
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}
