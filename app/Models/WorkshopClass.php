<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopClass extends Model
{
    protected $fillable = [
        'workshop_id',
        'class_date',
        'start_time',
        'end_time',
        'is_holiday',
        'notes',
    ];

    protected $casts = [
        'class_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_holiday' => 'boolean',
    ];
    
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
