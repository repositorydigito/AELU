<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_hours',
        'price',
        'max_students',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];    

    public function instructorWorkshops(): HasMany
    {
        return $this->hasMany(InstructorWorkshop::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
                    ->withPivot('enrollment_date', 'status', 'payment_status')
                    ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(Treasury::class);
    }
}
