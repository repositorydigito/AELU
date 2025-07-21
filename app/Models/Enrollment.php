<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'instructor_workshop_id',
        'enrollment_date',
        'notes',
        'class_type',
        'number_of_classes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function instructorWorkshop()
    {
        return $this->belongsTo(InstructorWorkshop::class);
    }
}
