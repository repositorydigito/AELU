<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'is_active',
        'renewal_start_date',
        'renewal_end_date',
        'auto_generate_classes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'renewal_start_date' => 'date',
        'renewal_end_date' => 'date',
        'auto_generate_classes' => 'boolean',
    ];

    public function classes()
    {
        return $this->hasMany(WorkshopClass::class);
    }
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function instructorPayments()
    {
        return $this->hasMany(InstructorPayment::class);
    }
}
