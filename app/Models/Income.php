<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = [
        'concept',
        'date',
        'razon_social',
        'amount',
        'document_number',
        'notes',
        'vale_code',
        'is_income',
        'voucher_path',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
