<?php

namespace App\Console\Commands;

use App\Models\MonthlyPeriod;
use App\Models\StudentCredit;
use Illuminate\Console\Command;

class ExpireStudentCredits extends Command
{
    protected $signature = 'credits:expire';

    protected $description = 'Marca como vencidos (RN-D17) los créditos de recuperación disponibles cuya vigencia ya pasó';

    public function handle(): int
    {
        $currentPeriod = MonthlyPeriod::where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        if (! $currentPeriod) {
            $this->info('No se encontró período mensual actual.');

            return Command::SUCCESS;
        }

        $expired = StudentCredit::where('status', 'available')
            ->whereHas('validThroughPeriod', function ($query) use ($currentPeriod) {
                $query->where(function ($q) use ($currentPeriod) {
                    $q->where('year', '<', $currentPeriod->year)
                        ->orWhere(function ($q2) use ($currentPeriod) {
                            $q2->where('year', $currentPeriod->year)
                                ->where('month', '<', $currentPeriod->month);
                        });
                });
            })
            ->update(['status' => 'expired']);

        $this->info("Créditos marcados como vencidos: {$expired}");

        return Command::SUCCESS;
    }
}
