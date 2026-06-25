<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopTemplate extends Model
{
    protected $fillable = [
        'name',
        'instructor_id',
        'delegate_user_id',
        'description',
        'standard_monthly_fee',
        'pricing_surcharge_percentage',
        'day_of_week',
        'start_time',
        'duration',
        'capacity',
        'number_of_classes',
        'place',
        'modality',
        'additional_comments',
        'is_active',
    ];

    protected $casts = [
        'standard_monthly_fee' => 'decimal:2',
        'pricing_surcharge_percentage' => 'decimal:2',
        'duration' => 'integer',
        'capacity' => 'integer',
        'number_of_classes' => 'integer',
        'day_of_week' => 'array',
        'is_active' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'delegate_user_id');
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }
}
