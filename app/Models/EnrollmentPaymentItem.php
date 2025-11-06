<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function enrollmentPayment()
    {
        return $this->belongsTo(EnrollmentPayment::class);
    }
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}
