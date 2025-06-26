<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_workshop_id',
        'monthly_period_id',
        'previous_enrollment_id',
        'enrollment_type',
        'number_of_classes',
        'price_per_quantity',
        'total_amount',
        'payment_status',
        'enrollment_date',
        'renewal_status',
        'renewal_deadline',
        'is_renewal',
    ];

    protected $casts = [
        'number_of_classes' => 'integer',
        'price_per_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'enrollment_date' => 'date',
        'renewal_deadline' => 'date',
        'is_renewal' => 'boolean',
        'previous_enrollment_id' => 'integer',
    ];

    // Relaciones
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
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
    public function previousEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'previous_enrollment_id');
    }
    public function nextEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'previous_enrollment_id');
    }


    // Relaciones indirectas
    public function instructor()
    {
        return $this->hasOneThrough(
            Instructor::class,
            InstructorWorkshop::class,
            'id',
            'id', 
            'instructor_workshop_id',
            'instructor_id'
        );
    }
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
