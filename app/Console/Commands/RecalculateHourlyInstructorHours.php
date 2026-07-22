<?php

namespace App\Console\Commands;

use App\Models\InstructorPayment;
use App\Models\MonthlyPeriod;
use App\Observers\StudentEnrollmentObserver;
use Illuminate\Console\Command;

class RecalculateHourlyInstructorHours extends Command
{
    protected $signature = 'instructor-payments:recalculate-hourly-hours';

    protected $description = 'Recalcula total_hours de los pagos por horas del mes actual usando el conteo real de WorkshopClass (corrige registros calculados antes del fix del hardcode de 4 clases/mes)';

    public function handle(): int
    {
        $period = MonthlyPeriod::where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        if (! $period) {
            $this->error('No existe MonthlyPeriod para el mes actual.');

            return self::FAILURE;
        }

        $payments = InstructorPayment::where('monthly_period_id', $period->id)
            ->where('payment_type', 'hourly')
            ->get();

        if ($payments->isEmpty()) {
            $this->info("No hay pagos por horas para recalcular en el período {$period->year}-{$period->month}.");

            return self::SUCCESS;
        }

        $observer = new StudentEnrollmentObserver;
        $updated = 0;
        $removed = 0;

        foreach ($payments as $payment) {
            $before = $payment->total_hours;
            $instructorWorkshopId = $payment->instructor_workshop_id;

            $observer->recalculateForInstructorWorkshop($instructorWorkshopId, $period->id);

            $after = InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $period->id)
                ->first();

            if (! $after) {
                $this->warn("Pago #{$payment->id} (instructor_workshop_id={$instructorWorkshopId}): eliminado, ya no tiene inscripciones completadas.");
                $removed++;

                continue;
            }

            $this->line("Pago #{$payment->id} (instructor_workshop_id={$instructorWorkshopId}): {$before}hrs -> {$after->total_hours}hrs");
            $updated++;
        }

        $this->info("Recalculados {$updated} pagos, {$removed} eliminados por falta de inscripciones. Período {$period->year}-{$period->month}.");

        return self::SUCCESS;
    }
}
