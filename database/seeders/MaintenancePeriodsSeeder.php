<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MaintenancePeriod;
use Illuminate\Support\Carbon;

class MaintenancePeriodsSeeder extends Seeder
{
    public function run(): void
    {
        $startYear = 2025;
        $endYear = 2050;

        $periodsToInsert = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::createFromDate($year, $month, 1);
                $monthName = ucfirst($date->locale('es')->monthName);

                $periodsToInsert[] = [
                    'year' => $year,
                    'month' => $month,
                    'name' => "{$monthName} {$year}",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        MaintenancePeriod::upsert(
            $periodsToInsert,
            ['year', 'month'],
            ['name', 'updated_at']
        );

        $this->command->info('Maintenance periods generated successfully from ' . $startYear . ' to ' . $endYear . '!');
    }
}
