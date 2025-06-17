<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'place',
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

    public function enrollments(): HasMany 
    {
        return $this->hasMany(Enrollment::class);        
    }

    public function getTimeRangeAttribute(): string
    {
        $startTime = $this->start_time->format('h:i A');
        $endTime = $this->end_time->format('h:i A');

        return "{$startTime} - {$endTime}";
    }
}
