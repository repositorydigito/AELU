<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorPaymentReceipt extends Model
{
    protected $fillable = [
        'instructor_id',
        'monthly_period_id',
        'payment_type',
        'document_number',
        'payment_date',
        'total_amount',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function monthlyPeriod(): BelongsTo
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function instructorPayments(): HasMany
    {
        return $this->hasMany(InstructorPayment::class);
    }
}
