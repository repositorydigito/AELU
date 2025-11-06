<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_code',
        'enrollment_batch_id',
        'enrollment_payment_id',
        'student_id',
        'total_amount',
        'ticket_type',
        'status',
        'issued_at',
        'issued_by_user_id',
        'notes',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (Auth::check() && empty($ticket->issued_by_user_id)) {
                $ticket->issued_by_user_id = Auth::id();
            }

            if (empty($ticket->issued_at)) {
                $ticket->issued_at = now();
            }
        });
    }

    // Relaciones
    public function enrollmentBatch()
    {
        return $this->belongsTo(EnrollmentBatch::class);
    }

    public function enrollmentPayment()
    {
        return $this->belongsTo(EnrollmentPayment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function issuedByUser()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function studentEnrollments()
    {
        return $this->belongsToMany(
            StudentEnrollment::class,
            'ticket_student_enrollment',
            'ticket_id',
            'student_enrollment_id'
        )->withTimestamps();
    }

    // Métodos auxiliares
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getFormattedStatusAttribute()
    {
        return match ($this->status) {
            'active' => 'Activo',
            'cancelled' => 'Anulado',
            'refunded' => 'Reembolsado',
            default => $this->status,
        };
    }

    public function getIssuedByNameAttribute()
    {
        return $this->issuedByUser ? $this->issuedByUser->name : 'Sistema';
    }
}
