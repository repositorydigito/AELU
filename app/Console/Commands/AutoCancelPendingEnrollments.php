<?php

namespace App\Console\Commands;

use App\Models\EnrollmentBatch;
use App\Models\MonthlyPeriod;
use App\Models\SystemSetting;
use App\Services\EnrollmentPaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCancelPendingEnrollments extends Command
{
    protected $signature = 'enrollments:auto-cancel';

    protected $description = 'Automatically cancel pending enrollments on the configured day of each month';

    public function handle()
    {
        $isEnabled = SystemSetting::get('auto_cancel_enabled', false);
        if (! $isEnabled) {
            $this->info('Auto-cancel is disabled. Skipping...');
            return;
        }

        $cancelDay = (int) SystemSetting::get('auto_cancel_day', 28);
        $cancelTime = SystemSetting::get('auto_cancel_time', '23:59:59');
        $today = Carbon::now();
        $targetTime = Carbon::today()->setTimeFromTimeString($cancelTime);

        if ($today->day !== $cancelDay) {
            $this->info("Today is day {$today->day}, waiting for day {$cancelDay}");
            return;
        }

        if ($today->lt($targetTime)) {
            $this->info("Current time {$today->format('H:i:s')} is before target time {$cancelTime}");
            return;
        }

        $this->info("Running auto-cancellation for day {$cancelDay} at {$today->format('H:i:s')}");

        // Solo cancelar batches de períodos actuales o pasados (no futuros)
        $currentPeriod = MonthlyPeriod::where('year', $today->year)
            ->where('month', $today->month)
            ->first();

        if (! $currentPeriod) {
            $this->error('No monthly period found for current date. Aborting.');
            return;
        }

        // Anular lotes sin ningún pago
        $this->cancelPendingBatches($currentPeriod->id);

        // Anular solo las inscripciones sin pago dentro de lotes con pago parcial
        $this->cancelUnpaidEnrollmentsInPartialBatches($currentPeriod->id);
    }

    /**
     * Anula completamente los lotes en estado 'pending' (sin ningún pago registrado).
     */
    private function cancelPendingBatches(int $currentPeriodId): void
    {
        $batches = EnrollmentBatch::where('payment_status', 'pending')
            ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', '<=', $currentPeriodId))
            ->with(['enrollments', 'student', 'tickets'])
            ->get();

        if ($batches->isEmpty()) {
            $this->info('No pending batches found.');
            return;
        }

        $cancelledBatches = 0;
        $cancelledEnrollments = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($batches as $batch) {
                try {
                    $batch->update([
                        'payment_status'      => 'refunded',
                        'cancelled_at'        => now(),
                        'cancelled_by_user_id' => null,
                        'cancellation_reason' => 'Anulación automática - Sin pago al día límite',
                        'notes'               => ($batch->notes ? $batch->notes . "\n\n" : '') .
                                                 'Anulación automática el ' . now()->format('d/m/Y H:i:s'),
                    ]);

                    foreach ($batch->enrollments as $enrollment) {
                        $enrollment->update([
                            'payment_status'      => 'refunded',
                            'cancelled_at'        => now(),
                            'cancelled_by_user_id' => null,
                            'cancellation_reason' => 'Anulación automática - Sin pago al día límite',
                        ]);
                        $cancelledEnrollments++;
                    }

                    $batch->tickets()->update([
                        'status'              => 'cancelled',
                        'cancelled_at'        => now(),
                        'cancelled_by_user_id' => null,
                        'cancellation_reason' => 'Anulación automática de inscripción',
                    ]);

                    $cancelledBatches++;

                } catch (\Exception $e) {
                    $errors[] = "Batch ID {$batch->id}: " . $e->getMessage();
                }
            }

            DB::commit();

            $this->info("Pending batches cancelled: {$cancelledBatches} batches, {$cancelledEnrollments} enrollments.");

            foreach ($errors as $error) {
                $this->error($error);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Critical error cancelling pending batches: ' . $e->getMessage());
        }
    }

    /**
     * Para lotes con pago parcial ('to_pay'), anula solo las inscripciones
     * que siguen sin pagar, dejando intactas las ya pagadas.
     */
    private function cancelUnpaidEnrollmentsInPartialBatches(int $currentPeriodId): void
    {
        $batches = EnrollmentBatch::where('payment_status', 'to_pay')
            ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', '<=', $currentPeriodId))
            ->with(['enrollments.instructorWorkshop.workshop', 'student'])
            ->get();

        if ($batches->isEmpty()) {
            $this->info('No partial batches found.');
            return;
        }

        $processedBatches = 0;
        $cancelledEnrollments = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($batches as $batch) {
                try {
                    // Inscripciones pendientes de pago dentro de este lote parcial
                    $unpaidEnrollments = $batch->enrollments
                        ->whereNull('cancelled_at')
                        ->where('payment_status', 'pending');

                    if ($unpaidEnrollments->isEmpty()) {
                        continue;
                    }

                    $workshopLines = [];

                    foreach ($unpaidEnrollments as $enrollment) {
                        $iw       = $enrollment->instructorWorkshop;
                        $name     = $iw->workshop->name ?? 'Sin nombre';
                        $days     = is_array($iw->day_of_week)
                                        ? implode('/', $iw->day_of_week)
                                        : ($iw->day_of_week ?? 'N/A');
                        $start    = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i') : 'N/A';
                        $end      = $iw->end_time   ? \Carbon\Carbon::parse($iw->end_time)->format('H:i')   : 'N/A';
                        $modality = $iw->workshop->modality ?? 'N/A';

                        $workshopLines[] = "- {$name} | {$days} {$start}-{$end} | {$modality}";

                        $enrollment->update([
                            'payment_status'       => 'refunded',
                            'cancelled_at'         => now(),
                            'cancelled_by_user_id' => null,
                            'cancellation_reason'  => 'Anulación automática - Sin pago al día límite',
                        ]);
                        $cancelledEnrollments++;
                    }

                    // Ajustar total del lote a la suma de inscripciones no canceladas
                    $newTotal = $batch->enrollments()->whereNull('cancelled_at')->sum('total_amount');

                    // Nota de auditoría: detalle de talleres anulados y nuevo total
                    $note = 'Anulación automática el ' . now()->format('d/m/Y H:i:s') . ': '
                        . 'Se anularon ' . count($workshopLines) . ' inscripción(es) sin pago.'
                        . "\n" . implode("\n", $workshopLines)
                        . "\nNuevo total: S/ " . number_format($newTotal, 2) . '.';

                    $batch->update([
                        'total_amount'        => $newTotal,
                        'cancellation_reason' => $note,
                    ]);

                    // Recalcular estado del lote (pasa a 'completed' si todas las restantes están pagadas)
                    app(EnrollmentPaymentService::class)->updateBatchStatus($batch);

                    $processedBatches++;

                } catch (\Exception $e) {
                    $errors[] = "Batch ID {$batch->id}: " . $e->getMessage();
                }
            }

            DB::commit();

            $this->info("Partial batches processed: {$processedBatches} batches, {$cancelledEnrollments} enrollments cancelled.");

            foreach ($errors as $error) {
                $this->error($error);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Critical error processing partial batches: ' . $e->getMessage());
        }
    }
}
