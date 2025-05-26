<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
    
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'instructor_workshop')
                    ->withPivot('day', 'time', 'class_count', 'rate')
                    ->withTimestamps();
    }
}
