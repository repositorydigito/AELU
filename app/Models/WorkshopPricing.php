<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopPricing extends Model
{
    protected $fillable = [
        'workshop_id',
        'number_of_classes',
        'price',
        'is_default',
    ];

    protected $casts = [
        'number_of_classes' => 'integer',
        'price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }
}
