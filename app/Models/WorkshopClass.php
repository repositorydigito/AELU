<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopClass extends Model
{
    protected $fillable = [
        'instructor_workshop_id',
        'monthly_period_id',
        'class_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'max_capacity',
    ];

    protected $casts = [
        'class_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    public function instructorWorkshop()
    {
        return $this->belongsTo(InstructorWorkshop::class);
    }
    public function monthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }
    public function enrollmentClasses()
    {
        return $this->hasMany(EnrollmentClass::class);
    }
    // Relación indirecta al instructor y workshop
    public function instructor()
    {
        return $this->hasOneThrough(
            Instructor::class, 
            InstructorWorkshop::class,
            'id', // Foreign key on instructor_workshops table
            'id', // Foreign key on instructors table
            'instructor_workshop_id', // Local key on workshop_classes table
            'instructor_id' // Local key on instructor_workshops table
        );
    }
    public function workshop()
    {
        return $this->hasOneThrough(
            Workshop::class, 
            InstructorWorkshop::class,
            'id', // Foreign key on instructor_workshops table
            'id', // Foreign key on workshops table
            'instructor_workshop_id', // Local key on workshop_classes table
            'workshop_id' // Local key on instructor_workshops table
        );
    }
}
