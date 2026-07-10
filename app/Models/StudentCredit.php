<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StudentCredit extends Model
{
    protected $fillable = [
        'student_id',
        'origin_student_enrollment_id',
        'origin_monthly_period_id',
        'valid_through_period_id',
        'classes_count',
        'amount',
        'origin',
        'status',
        'consumed_at',
        'consumed_student_enrollment_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'classes_count' => 'integer',
        'amount' => 'decimal:2',
        'consumed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (StudentCredit $credit) {
            if (Auth::check() && empty($credit->created_by)) {
                $credit->created_by = Auth::id();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function originEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'origin_student_enrollment_id');
    }

    public function consumedEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'consumed_student_enrollment_id');
    }

    public function originPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class, 'origin_monthly_period_id');
    }

    public function validThroughPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class, 'valid_through_period_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollmentClasses()
    {
        return $this->belongsToMany(EnrollmentClass::class, 'student_credit_classes');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Valida si este crédito puede aplicarse a la inscripción destino.
     *
     * Decisión de negocio (2026-07): el crédito es un **saldo del alumno**, aplicable a
     * CUALQUIER taller/horario al momento de pagar — NO se exige que coincida el taller
     * de origen (se removió la restricción `matchesWorkshop`). Solo se valida:
     *  - crédito disponible,
     *  - mismo alumno,
     *  - dentro del mes de vigencia (RN-D17: solo el mes inmediato siguiente).
     */
    public function isApplicableTo(StudentEnrollment $enrollment): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        if ($this->student_id !== $enrollment->student_id) {
            return false;
        }

        return $this->valid_through_period_id === $enrollment->monthly_period_id;
    }
}
