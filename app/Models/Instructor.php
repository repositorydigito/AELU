<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'instructor_code',
        'instructor_type',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
    
    public function instructorWorkshops(): HasMany
    {
        return $this->hasMany(InstructorWorkshop::class);
    }    

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(Treasury::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function affidavit()
    {
        return $this->hasOne(Affidavit::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_names . ' ' . $this->last_names;
    }
}
