<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EnrollmentBatch;
use App\Models\StudentEnrollment;

class SyncEnrollmentPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollment:sync-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el estado de pago de los lotes con las inscripciones individuales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de estados de pago...');
        
        // Obtener todos los lotes de inscripciones
        $batches = EnrollmentBatch::all();
        $totalUpdated = 0;
        
        foreach ($batches as $batch) {
            // Actualizar todas las inscripciones individuales del lote
            $updated = StudentEnrollment::where('enrollment_batch_id', $batch->id)
                ->update([
                    'payment_status' => $batch->payment_status,
                    'payment_method' => $batch->payment_method,
                    'payment_due_date' => $batch->payment_due_date,
                    'payment_date' => $batch->payment_date,
                    'payment_document' => $batch->payment_document,
                ]);
            
            if ($updated > 0) {
                $this->line("Lote {$batch->id}: {$updated} inscripciones actualizadas");
                $totalUpdated += $updated;
            }
        }
        
        $this->info("Sincronización completada. Total de inscripciones actualizadas: {$totalUpdated}");
        
        // Mostrar resumen de estados
        $this->info("\nResumen de estados después de la sincronización:");
        $statuses = StudentEnrollment::selectRaw('payment_status, count(*) as count')
            ->groupBy('payment_status')
            ->get();
            
        foreach ($statuses as $status) {
            $this->line("- {$status->payment_status}: {$status->count}");
        }
        
        return 0;
    }
}