<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorWorkshop extends Model
{
    protected $fillable = [
        'instructor_id',
        'workshop_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_capacity',
        'place',
        'is_volunteer',
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
    public function classes()
    {
        return $this->hasMany(WorkshopClass::class);
    }
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function payments()
    {
        return $this->hasMany(InstructorPayment::class);
    }
}
