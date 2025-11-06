<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EnrollmentPayment extends Model
{
    protected $fillable = [
        'enrollment_batch_id',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'registered_by_user_id',
        'registered_at',
        'payment_document',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'registered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (Auth::check() && empty($payment->registered_by_user_id)) {
                $payment->registered_by_user_id = Auth::id();
            }

            if (empty($payment->registered_at)) {
                $payment->registered_at = now();
            }
        });
    }

    public function enrollmentBatch()
    {
        return $this->belongsTo(EnrollmentBatch::class);
    }
    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
    public function paymentItems()
    {
        return $this->hasMany(EnrollmentPaymentItem::class);
    }
    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }

    public function studentEnrollments()
    {
        return $this->belongsToMany(
            StudentEnrollment::class,
            'enrollment_payment_items',
            'enrollment_payment_id',
            'student_enrollment_id'
        )->withPivot('amount')->withTimestamps();
    }
}
