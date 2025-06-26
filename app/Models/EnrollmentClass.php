<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentClass extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'workshop_class_id',
        'class_fee',
        'attendance_status',
    ];

    protected $casts = [
        'class_fee' => 'decimal:2',
    ];

    // Relaciones
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function workshopClass()
    {
        return $this->belongsTo(WorkshopClass::class);
    }

    // Relaciones indirectas
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            StudentEnrollment::class,
            'id',
            'id',
            'student_enrollment_id',
            'student_id'
        );
    }
}
