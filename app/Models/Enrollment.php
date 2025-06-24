<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'workshop_id',
        'enrollment_date',
        'status',
        'total_amount',
        'amount_paid', 
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'total_amount' => 'float',
        'amount_paid' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
