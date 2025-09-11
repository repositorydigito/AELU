<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MaintenancePeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'name',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public static function getCurrentPeriod()
    {
        $now = Carbon::now();

        return self::where('year', $now->year)
            ->where('month', $now->month)
            ->first();
    }

    public function isEqualOrAfter(MaintenancePeriod $otherPeriod): bool
    {
        if ($this->year > $otherPeriod->year) {
            return true;
        }

        if ($this->year === $otherPeriod->year && $this->month >= $otherPeriod->month) {
            return true;
        }

        return false;
    }

    public function isBefore(MaintenancePeriod $otherPeriod): bool
    {
        return ! $this->isEqualOrAfter($otherPeriod);
    }
}
