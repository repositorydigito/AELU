<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_names',
        'first_names',
        'document_type',
        'document_number',
        'birth_date',
        'nationality',
        'student_code',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'photo',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'enrollments')
                    ->withPivot('enrollment_date', 'status', 'payment_status')
                    ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_names . ' ' . $this->last_names;
    }
}
