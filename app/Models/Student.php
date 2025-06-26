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
        'photo',
        'document_type',
        'document_number',
        'birth_date',
        'nationality',
        'student_code',
        'category_partner',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function medications()
    {
        return $this->hasManyThrough(StudentMedication::class, MedicalRecord::class);
    }

    public function affidavit()
    {
        return $this->hasOne(Affidavit::class);
    }    

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function workshops()
    {
        return $this->belongsToMany(Workshop::class, 'student_enrollments')
                    ->withPivot('monthly_period_id', 'enrollment_type', 'number_of_classes', 'price_per_quantity', 'total_amount', 'payment_status', 'enrollment_date')
                    ->withTimestamps();
    }

    /* public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    } */

    public function getFullNameAttribute(): string
    {
        return $this->last_names . ' ' . $this->first_names;
    }
}
