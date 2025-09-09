<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EnrollmentBatch extends Model
{
    protected $fillable = [
        'student_id',
        'created_by',
        'batch_code',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_due_date',
        'payment_date',
        'payment_document',
        'payment_registered_by_user_id',
        'payment_registered_at',
        'enrollment_date',
        'notes',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'enrollment_date' => 'date',
        'payment_due_date' => 'date',
        'payment_date' => 'date',
        'payment_registered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            // Generar batch_code según el método de pago
            if (empty($batch->batch_code)) {
                if ($batch->payment_method === 'link') {
                    $batch->batch_code = 'Sin código';
                } else {
                    $batch->batch_code = 'INS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                }
            }

            // Guardar el usuario que está creando la inscripción
            if (Auth::check() && empty($batch->created_by)) {
                $batch->created_by = Auth::id();
            }
        });
    }

    // Relaciones
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'enrollment_batch_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function paymentRegisteredByUser()
    {
        return $this->belongsTo(User::class, 'payment_registered_by_user_id');
    }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    // Métodos auxiliares
    public function getWorkshopsCountAttribute()
    {
        return $this->enrollments()->count();
    }
    public function getWorkshopsListAttribute()
    {
        return $this->enrollments()
            ->with(['instructorWorkshop.workshop'])
            ->get()
            ->pluck('instructorWorkshop.workshop.name')
            ->join(', ');
    }
    public function getTotalClassesAttribute()
    {
        return $this->enrollments()->sum('number_of_classes');
    }
    public function getFormattedPaymentStatusAttribute()
    {
        return match ($this->payment_status) {
            'pending' => 'En Proceso',
            'to_pay' => 'Por Pagar',
            'completed' => 'Inscrito',
            'credit_favor' => 'Crédito a Favor',
            'refunded' => 'Devuelto',
            default => $this->payment_status,
        };
    }
    public function getFormattedPaymentMethodAttribute()
    {
        return match ($this->payment_method) {
            'cash' => 'Efectivo',
            'link' => 'Link de Pago',
            default => $this->payment_method,
        };
    }
    public function getCreatedByNameAttribute()
    {
        if ($this->creator) {
            return $this->creator->name;
        }

        return 'Sistema';
    }
    public function getPaymentRegisteredByDisplayAttribute(): ?string
    {
        if (!$this->payment_registered_by_user_id || !$this->payment_registered_at) {
            return null;
        }

        $userName = $this->paymentRegisteredByUser ? $this->paymentRegisteredByUser->name : 'Usuario eliminado';
        return $userName . ' - ' . $this->payment_registered_at->format('d/m/Y H:i');
    }
    public function getPaymentRegisteredByNameAttribute(): ?string
    {
        return $this->paymentRegisteredByUser ? $this->paymentRegisteredByUser->name : null;
    }
}
