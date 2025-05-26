<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_names',
        'first_names',
        'document_type',
        'document_number',
        'birth_date',
        'nationality',
        'instructor_code',
        'instructor_type',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
   
    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'instructor_workshop')
                    ->withPivot('day', 'time', 'class_count', 'rate')
                    ->withTimestamps();
    }
}
