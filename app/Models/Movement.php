<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Movement extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'concept',
        'notes',
        'movement_category_id',
        'movable_id',
        'movable_type',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(MovementCategory::class, 'movement_category_id');
    }    
    public function movable(): MorphTo
    {
        return $this->morphTo();
    }
}
