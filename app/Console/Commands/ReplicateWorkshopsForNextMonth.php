<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SystemSetting;
use App\Models\MonthlyPeriod;
use App\Models\Workshop;
use App\Services\WorkshopReplicationService;

class ReplicateWorkshopsForNextMonth extends Command
{
    protected $signature = 'workshops:auto-replicate {--force : Ejecuta aunque ya se haya replicado este período}';

    protected $description = 'Replica los talleres del período actual al siguiente y genera sus clases según configuración';

    public function handle()
    {
        $this->info('Iniciando proceso de replicación de talleres...');

        // Usar configuraciones existentes de auto_generate (día/hora)
        $isEnabled = SystemSetting::get('auto_generate_enabled', false);
        if (! $isEnabled) {
            $this->info('Auto-generación deshabilitada. Saliendo...');
            return Command::SUCCESS;
        }

        $generateDay = (int) SystemSetting::get('auto_generate_day', 25);
        $generateTime = SystemSetting::get('auto_generate_time', '23:59:59');

        $now = Carbon::now();
        $targetTime = Carbon::today()->setTimeFromTimeString($generateTime);
        $targetMinute = $targetTime->format('H:i');
        $nowMinute = $now->format('H:i');

        // Validar día/hora exactos cuando no es forzado
        if (! $this->option('force')) {
            if ($now->day !== $generateDay) {
                $this->info("Hoy es día {$now->day}, esperando día {$generateDay}");
                return Command::SUCCESS;
            }

            if ($nowMinute !== $targetMinute) {
                $this->info("Hora actual {$nowMinute} distinta de hora objetivo {$targetMinute}. Saliendo...");
                return Command::SUCCESS;
            }
        } else {
            $this->info('Ejecución forzada: omitiendo validación de día/hora.');
        }

        $currentPeriod = MonthlyPeriod::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if (! $currentPeriod) {
            $this->error('No se encontró período mensual actual.');
            return Command::FAILURE;
        }

        // Calcular próximo mes
        $nextMonth = (clone $now)->addMonthNoOverflow();
        $nextPeriod = MonthlyPeriod::firstOrCreate(
            ['year' => $nextMonth->year, 'month' => $nextMonth->month],
            [
                'start_date' => Carbon::create($nextMonth->year, $nextMonth->month, 1),
                'end_date' => Carbon::create($nextMonth->year, $nextMonth->month, 1)->endOfMonth(),
                'is_active' => true,
                'auto_generate_classes' => true,
            ]
        );

        $service = app(WorkshopReplicationService::class);

        DB::beginTransaction();
        try {
            $replicated = $service->replicateFromPeriodToNext($currentPeriod, $nextPeriod);

            DB::commit();

            $this->info('Replicación completa');
            $this->info("- Talleres replicados: {$replicated['workshops']}");
            $this->info("- Clases generadas: {$replicated['classes']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error replicando talleres', ['error' => $e->getMessage()]);
            $this->error('Error durante replicación: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}