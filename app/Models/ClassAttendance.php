<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAttendance extends Model
{
    protected $fillable = [
        'workshop_class_id',
        'student_enrollment_id',
        'is_present',
        'comments',
        'recorded_by',
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    /**
     * Relación con la clase del taller
     */
    public function workshopClass(): BelongsTo
    {
        return $this->belongsTo(WorkshopClass::class);
    }

    /**
     * Relación con la matrícula del estudiante
     */
    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /**
     * Relación con el usuario que registró la asistencia
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Relación indirecta con el estudiante a través de student_enrollment
     */
    public function student()
    {
        return $this->studentEnrollment->student();
    }
}
