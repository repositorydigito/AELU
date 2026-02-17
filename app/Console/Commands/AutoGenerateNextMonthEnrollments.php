<?php

namespace App\Console\Commands;

use App\Models\MonthlyPeriod;
use App\Models\SystemSetting;
use App\Services\EnrollmentReplicationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateNextMonthEnrollments extends Command
{
    protected $signature = 'enrollments:auto-generate {--force : Ejecuta aunque no sea el día/hora configurado}';

    protected $description = 'Automatically replicate enrollments for the next month based on current month completed enrollments';

    public function handle()
    {
        $this->info('Iniciando proceso de replicación de inscripciones...');

        // Verificar si la funcionalidad está habilitada
        $isEnabled = SystemSetting::get('auto_replicate_enrollments_enabled', false);
        if (! $isEnabled && ! $this->option('force')) {
            $this->info('Replicación automática de inscripciones deshabilitada. Saliendo...');

            return Command::SUCCESS;
        }

        // Obtener configuraciones
        $generateDay = (int) SystemSetting::get('auto_replicate_enrollments_day', 25);
        $generateTime = SystemSetting::get('auto_replicate_enrollments_time', '23:59:59');

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

        // Obtener el período mensual actual
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

        // Verificar si ya se replicaron las inscripciones para este período
        if ($nextPeriod->enrollments_replicated_at && ! $this->option('force')) {
            $this->info("Las inscripciones ya fueron replicadas para {$nextPeriod->year}/{$nextPeriod->month} el {$nextPeriod->enrollments_replicated_at->format('Y-m-d H:i:s')}");
            $this->info('Usa --force para forzar la replicación nuevamente.');

            return Command::SUCCESS;
        }

        if ($this->option('force') && $nextPeriod->enrollments_replicated_at) {
            $this->warn("ADVERTENCIA: Forzando replicación aunque ya se ejecutó el {$nextPeriod->enrollments_replicated_at->format('Y-m-d H:i:s')}");
            $this->warn('Esto puede crear inscripciones duplicadas.');
        }

        $this->info("Replicando inscripciones de {$currentPeriod->year}/{$currentPeriod->month} a {$nextPeriod->year}/{$nextPeriod->month}");

        $service = app(EnrollmentReplicationService::class);

        DB::beginTransaction();
        try {
            $result = $service->replicateEnrollmentsToNextMonth($currentPeriod, $nextPeriod);

            // Marcar como replicado
            $nextPeriod->update(['enrollments_replicated_at' => now()]);

            DB::commit();

            // Resumen de la operación
            $this->info('Replicación completada exitosamente');
            $this->info("- Lotes creados: {$result['batches']}");
            $this->info("- Inscripciones creadas: {$result['enrollments']}");
            $this->info("- Clases asignadas: {$result['classes']}");
            $this->info("- Lotes omitidos: {$result['skipped']}");

            // Mostrar advertencias
            if (! empty($result['warnings'])) {
                $this->warn('- Advertencias: '.count($result['warnings']));
                foreach ($result['warnings'] as $warning) {
                    $this->warn("  • {$warning}");
                }
            }

            // Mostrar errores
            if (! empty($result['errors'])) {
                $this->error('- Errores: '.count($result['errors']));
                foreach ($result['errors'] as $error) {
                    $this->error("  • {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error crítico replicando inscripciones', ['error' => $e->getMessage()]);
            $this->error('Error crítico durante replicación: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
