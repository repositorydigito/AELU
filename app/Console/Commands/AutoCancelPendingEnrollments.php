<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EnrollmentBatch;
use App\Models\StudentEnrollment;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCancelPendingEnrollments extends Command
{
    protected $signature = 'enrollments:auto-cancel';
    
    protected $description = 'Automatically cancel pending enrollments on the configured day of each month';

    public function handle()
    {
        $this->info('Starting auto-cancel process...');
        
        // Verificar si la funcionalidad está habilitada
        $isEnabled = SystemSetting::get('auto_cancel_enabled', false);
        if (!$isEnabled) {
            $this->info('Auto-cancel is disabled. Skipping...');
            return;
        }

        // Obtener configuraciones
        $cancelDay = (int) SystemSetting::get('auto_cancel_day', 28);
        $cancelTime = SystemSetting::get('auto_cancel_time', '23:59:59');
        
        $today = Carbon::now();
        $targetTime = Carbon::today()->setTimeFromTimeString($cancelTime);
        
        // Verificar si es el día correcto del mes
        if ($today->day !== $cancelDay) {
            $this->info("Today is day {$today->day}, waiting for day {$cancelDay}");
            return;
        }
        
        // Verificar si ya pasó la hora configurada
        if ($today->lt($targetTime)) {
            $this->info("Current time {$today->format('H:i:s')} is before target time {$cancelTime}");
            return;
        }

        $this->info("Starting auto-cancellation process for day {$cancelDay} at {$today->format('H:i:s')}");

        // Obtener el período mensual actual
        $currentPeriod = \App\Models\MonthlyPeriod::where('year', $today->year)
            ->where('month', $today->month)
            ->first();

        if (!$currentPeriod) {
            $this->error('No monthly period found for current month');
            return;
        }

        // Buscar lotes de inscripciones en proceso (pending)
        $pendingBatches = EnrollmentBatch::where('payment_status', 'pending')
            ->whereHas('enrollments', function ($query) use ($currentPeriod) {
                $query->where('monthly_period_id', $currentPeriod->id);
            })
            ->with(['enrollments', 'student', 'tickets'])
            ->get();

        if ($pendingBatches->isEmpty()) {
            $this->info('No pending enrollment batches found for auto-cancellation');
            return;
        }

        $cancelledBatches = 0;
        $cancelledEnrollments = 0;
        $errors = [];

        DB::beginTransaction();
        
        try {
            foreach ($pendingBatches as $batch) {
                try {
                    // Actualizar el lote (marcar como anulado por el sistema)
                    $batch->update([
                        'payment_status' => 'refunded',
                        'cancelled_at' => now(),
                        'cancelled_by_user_id' => null,
                        'cancellation_reason' => 'Anulación automática - No se completó el pago antes del día límite',
                        'notes' => ($batch->notes ? $batch->notes . "\n\n" : '') .
                                  'Anulación automática el ' . now()->format('d/m/Y H:i:s') .
                                  ': No se completó el pago antes del día límite'
                    ]);

                    // Actualizar cada inscripción individualmente para disparar observers
                    foreach ($batch->enrollments as $enrollment) {
                        $enrollment->update([
                            'payment_status' => 'refunded',
                            'cancelled_at' => now(),
                            'cancelled_by_user_id' => null,
                            'cancellation_reason' => 'Anulación automática - No se completó el pago antes del día límite',
                        ]);
                        $cancelledEnrollments++;
                    }

                    // Anular TODOS los tickets asociados al lote (incluidos los ya pagados)
                    $batch->tickets()->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_by_user_id' => null,
                        'cancellation_reason' => 'Anulación automática de inscripción',
                    ]);

                    $cancelledBatches++;
                    
                    $this->info("Cancelled batch ID {$batch->id} for student {$batch->student->first_names} {$batch->student->last_names}");
                    
                } catch (\Exception $e) {
                    $errors[] = "Error cancelling batch ID {$batch->id}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Resumen de la operación
            $this->info("Auto-cancellation completed successfully");
            $this->info("- Cancelled batches: {$cancelledBatches}");
            $this->info("- Cancelled enrollments: {$cancelledEnrollments}");
            
            if (!empty($errors)) {
                $this->warn("- Errors encountered: " . count($errors));
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Critical error during auto-cancellation: ' . $e->getMessage());
        }
    }
}
