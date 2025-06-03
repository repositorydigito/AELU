<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'student_id',
        'weight',
        'height',
        'gender',
        'smokes',
        'cigarettes_per_day',
        'health_insurance',
        'medical_conditions',
        'allergies',
        'allergy_details',
        'surgical_operations',
        'surgical_operation_details',
    ];

    protected $casts = [
        'medical_conditions' => 'array',
        'allergies' => 'array',
        'surgical_operations' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function medications()
    {
        return $this->hasMany(StudentMedication::class);
    }
}
