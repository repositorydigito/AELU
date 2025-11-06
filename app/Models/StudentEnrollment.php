<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'created_by',
        'instructor_workshop_id',
        'monthly_period_id',
        'previous_enrollment_id',
        'enrollment_batch_id',
        'enrollment_type',
        'number_of_classes',
        'price_per_quantity',
        'total_amount',
        'pricing_notes',
        'payment_status',
        'payment_method',
        'payment_due_date',
        'payment_date',
        'payment_document',
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
        'payment_due_date' => 'date',
        'payment_date' => 'date',
        'renewal_deadline' => 'date',
        'is_renewal' => 'boolean',
        'previous_enrollment_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($enrollment) {
            // Guardar el usuario que está creando la inscripción individual
            if (Auth::check() && empty($enrollment->created_by)) {
                $enrollment->created_by = Auth::id();
            }
        });
    }

    // Relaciones
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class);
    }
    public function previousEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'previous_enrollment_id');
    }
    public function nextEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'previous_enrollment_id');
    }
    public function enrollmentBatch()
    {
        return $this->belongsTo(EnrollmentBatch::class);
    }
    public function paymentItems()
    {
        return $this->hasMany(EnrollmentPaymentItem::class);
    }
    public function payments()
    {
        return $this->belongsToMany(
            EnrollmentPayment::class,
            'enrollment_payment_items',
            'student_enrollment_id',
            'enrollment_payment_id'
        )->withPivot('amount')->withTimestamps();
    }
    public function tickets()
    {
        return $this->belongsToMany(
            Ticket::class,
            'ticket_student_enrollment',
            'student_enrollment_id',
            'ticket_id'
        )->withTimestamps();
    }

    public function isPaid()
    {
        return $this->payment_status === 'completed';
    }
    public function getCreatedByNameAttribute()
    {
        if ($this->creator) {
            return $this->creator->name;
        }

        return 'Sistema';
    }
    // Accessor para obtener el código de pago a través de la relación
    public function getBatchCodeAttribute(): ?string
    {
        return $this->enrollmentBatch ? $this->enrollmentBatch->batch_code : null;
    }
    // Accessor para obtener quién registró el pago a través de la relación
    public function getPaymentRegisteredByNameAttribute(): ?string
    {
        return $this->enrollmentBatch && $this->enrollmentBatch->paymentRegisteredByUser
            ? $this->enrollmentBatch->paymentRegisteredByUser->name
            : null;
    }
    // Accessor para obtener cuándo se registró el pago a través de la relación
    public function getPaymentRegisteredAtAttribute(): ?string
    {
        return $this->enrollmentBatch && $this->enrollmentBatch->payment_registered_at
            ? $this->enrollmentBatch->payment_registered_at
            : null;
    }
    // Accessor para mostrar información completa del registro de pago
    public function getPaymentRegisteredByDisplayAttribute(): ?string
    {
        if (! $this->enrollmentBatch || ! $this->enrollmentBatch->payment_registered_by_user_id || ! $this->enrollmentBatch->payment_registered_at) {
            return null;
        }

        $userName = $this->enrollmentBatch->paymentRegisteredByUser ? $this->enrollmentBatch->paymentRegisteredByUser->name : 'Usuario eliminado';

        return $userName.' - '.$this->enrollmentBatch->payment_registered_at->format('d/m/Y H:i');
    }
}
