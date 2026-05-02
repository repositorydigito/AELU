<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;

class ReplicateRecurringHolidays extends Command
{
    protected $signature = 'holidays:replicate-recurring
                            {--from-year= : Año fuente (por defecto año actual)}
                            {--to-year=   : Año destino (por defecto año siguiente)}';

    protected $description = 'Replica los feriados recurrentes al año siguiente';

    public function handle(): int
    {
        $fromYear = (int) ($this->option('from-year') ?? now()->year);
        $toYear   = (int) ($this->option('to-year')   ?? $fromYear + 1);

        $source = Holiday::query()
            ->where('is_recurring', true)
            ->whereYear('date', $fromYear)
            ->get();

        if ($source->isEmpty()) {
            $this->warn("No hay feriados recurrentes en {$fromYear}.");
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($source as $holiday) {
            $newDate = $holiday->date->setYear($toYear)->toDateString();

            $exists = Holiday::query()
                ->whereYear('date', $toYear)
                ->whereMonth('date', $holiday->date->month)
                ->whereDay('date', $holiday->date->day)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Holiday::create([
                'name'            => $holiday->name,
                'date'            => $newDate,
                'description'     => $holiday->description,
                'is_recurring'    => true,
                'affects_classes' => $holiday->affects_classes,
            ]);

            $created++;
        }

        $this->info("Feriados replicados de {$fromYear} → {$toYear}: {$created} creados, {$skipped} ya existían.");

        return self::SUCCESS;
    }
}
