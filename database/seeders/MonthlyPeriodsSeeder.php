<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MonthlyPeriod;
use Illuminate\Support\Carbon;

class MonthlyPeriodsSeeder extends Seeder
{    
    public function run(): void
    {
        // Define el año de inicio (puedes ajustar si quieres empezar antes)
        $startYear = Carbon::now()->year;
        // Define el año final
        $endYear = 2040;

        $periodsToInsert = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $endDate = $startDate->copy()->endOfMonth()->endOfDay();

                // Calcular fechas de renovación (ej: del 15 al 25 del mes anterior)
                // Para el primer mes del año, la renovación sería en diciembre del año anterior.
                // Ajustamos para que la renovación sea siempre en el mes anterior al inicio del período.
                $renewalStartDate = $startDate->copy()->subMonth()->day(15)->startOfDay();
                $renewalEndDate = $startDate->copy()->subMonth()->day(25)->endOfDay();

                // Asegurarse de que las fechas de renovación no sean inválidas (ej. para enero si empieza en diciembre)
                if ($renewalStartDate->month !== $startDate->copy()->subMonth()->month) {
                    // Si el 15 del mes anterior no existe (ej. Febrero 15), ajusta al último día del mes anterior
                    $renewalStartDate = $startDate->copy()->subMonth()->endOfMonth()->subDays(10)->startOfDay();
                    $renewalEndDate = $startDate->copy()->subMonth()->endOfMonth()->endOfDay();
                }

                $periodsToInsert[] = [
                    'year' => $year,
                    'month' => $month,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => true, // Por defecto activos. Puedes ajustar esto si prefieres que solo el mes actual esté activo.
                    'renewal_start_date' => $renewalStartDate,
                    'renewal_end_date' => $renewalEndDate,
                    'auto_generate_classes' => true, // Por defecto, se auto-generarán las clases
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        // Insertar en lotes para mayor eficiencia
        // Utiliza `upsert` para evitar errores si ejecutas el seeder varias veces
        // y ya existen periodos únicos (year, month)
        MonthlyPeriod::upsert(
            $periodsToInsert,
            ['year', 'month'], 
            [ 
                'start_date', 'end_date', 'is_active',
                'renewal_start_date', 'renewal_end_date', 'auto_generate_classes',
                'updated_at'
            ]
        );

        $this->command->info('Monthly periods generated successfully from ' . $startYear . ' to ' . $endYear . '!');
    }
}
