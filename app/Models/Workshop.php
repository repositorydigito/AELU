<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Workshop extends Model
{
    protected $fillable = [
        'name',
        'description',
        'standard_monthly_fee',
        'hourly_rate',
        'duration_minutes',
    ];

    protected $casts = [
        'standard_monthly_fee' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function instructorWorkshops()
    {
        return $this->hasMany(InstructorWorkshop::class);
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class, 'instructor_workshops')
                    ->withPivot('day_of_week', 'start_time', 'end_time', 'is_volunteer', 'is_active')
                    ->withTimestamps();
    }

    public function pricing()
    {
        return $this->hasMany(WorkshopPricing::class);
    }

    public function enrollments()
    {
        return $this->hasManyThrough(StudentEnrollment::class, InstructorWorkshop::class);
    }
    public function movements(): MorphMany
    {
        return $this->morphMany(Movement::class, 'movable');
    }
}
