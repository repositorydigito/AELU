<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_names',
        'first_names',
        'document_type',
        'document_number',
        'birth_date',
        'nationality',
        // 'instructor_type',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function affidavit()
    {
        return $this->hasOne(Affidavit::class);
    }

    public function instructorWorkshops()
    {
        return $this->hasMany(InstructorWorkshop::class);
    }

    public function workshops()
    {
        return $this->hasManyThrough(Workshop::class, InstructorWorkshop::class);
    }

    public function instructorPayments()
    {
        return $this->hasMany(InstructorPayment::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_names.' '.$this->last_names;
    }
}
