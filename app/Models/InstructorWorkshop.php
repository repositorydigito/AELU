<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorWorkshop extends Model
{
    protected $fillable = [
        'instructor_id',
        'workshop_id',
        'initial_monthly_period_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_capacity',
        'place',
        'payment_type',
        'hourly_rate',
        'duration_hours',
        'custom_volunteer_percentage',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_volunteer' => 'boolean',
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }    
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function initialMonthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class, 'initial_monthly_period_id');
    }
    public function payments()
    {
        return $this->hasMany(InstructorPayment::class);
    }

    // Métodos helper
    public function isVolunteer(): bool
    {
        return $this->payment_type === 'volunteer';
    }
    public function isHourly(): bool
    {
        return $this->payment_type === 'hourly';
    }
    public function getEstimatedPayPerClass(): ?float
    {
        if ($this->isHourly() && $this->hourly_rate && $this->duration_hours) {
            return $this->hourly_rate * $this->duration_hours;
        }
        return null;
    }
    public function getEffectiveVolunteerPercentage(?MonthlyInstructorRate $monthlyRate = null): ?float
    {
        if ($this->isVolunteer()) {
            return $this->custom_volunteer_percentage ?? $monthlyRate?->volunteer_percentage;
        }
        return null;
    }
}
