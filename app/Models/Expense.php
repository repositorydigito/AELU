<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'concept',
        'vale_code',
        'is_income',
        'voucher_path',
    ];

    protected $casts = [
        'is_income' => 'boolean',
    ];

    public function expenseDetails()
    {
        return $this->hasMany(ExpenseDetail::class);
    }

}
