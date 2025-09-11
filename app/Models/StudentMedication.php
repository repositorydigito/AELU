<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMedication extends Model
{
    protected $fillable = [
        'medical_record_id',
        'medicine',
        'dose',
        'schedule',
    ];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
