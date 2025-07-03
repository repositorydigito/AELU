<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class, 'movement_category_id');
    }
}
