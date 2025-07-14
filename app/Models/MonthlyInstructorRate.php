<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyInstructorRate extends Model
{
    protected $fillable = [
        'monthly_period_id',
        'volunteer_percentage',
        'is_active',
    ];

    protected $casts = [
        'volunteer_percentage' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function monthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }
    public function instructorPayments()
    {
        return $this->hasMany(InstructorPayment::class);
    }
    
}
