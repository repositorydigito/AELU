<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorPayment extends Model
{
    protected $fillable = [
        'instructor_id',
        'instructor_workshop_id',
        'monthly_period_id',
        'monthly_instructor_rate_id',
        'payment_type',
        'total_students',
        'monthly_revenue',
        'volunteer_percentage',
        'total_hours',
        'hourly_rate',
        'applied_hourly_rate',
        'applied_volunteer_percentage',
        'calculated_amount',
        'payment_status',
        'payment_date',
        'notes',
        'document_number',
    ];

    protected $casts = [
        'monthly_revenue' => 'decimal:2',
        'volunteer_percentage' => 'decimal:4',
        'total_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'applied_hourly_rate' => 'decimal:2',
        'applied_volunteer_percentage' => 'decimal:4',
        'calculated_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relaciones
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function instructorWorkshop()
    {
        return $this->belongsTo(InstructorWorkshop::class);
    }

    public function monthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    public function monthlyInstructorRate(): BelongsTo
    {
        return $this->belongsTo(MonthlyInstructorRate::class);
    }

    // Relación indirecta al workshop
    public function workshop()
    {
        return $this->hasOneThrough(
            Workshop::class,
            InstructorWorkshop::class,
            'id',
            'id',
            'instructor_workshop_id',
            'workshop_id'
        );
    }
}
