<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorWorkshop extends Model
{
    protected $fillable = [
        'instructor_id',
        'workshop_id',
        'day_of_week',
        'start_time',
        'end_time',
        'class_count',
        'class_rate',
    ];

    protected $casts = [
        'start_time' => 'datetime', 
        'end_time' => 'datetime',   
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
