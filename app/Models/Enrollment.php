<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'instructor_workshop_id',        
        'enrollment_date',
        'status',
        'payment_status',
        'total_amount',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instructorWorkshop(): BelongsTo 
    {
        return $this->belongsTo(InstructorWorkshop::class);
    }

    /* public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    } */

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->paid_amount >= $this->total_amount;
    }
}
