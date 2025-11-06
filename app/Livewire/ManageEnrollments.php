<?php

namespace App\Livewire;

use App\Models\EnrollmentBatch;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ManageEnrollments extends Component
{
    public $batchId; // Cambiar a ID en lugar del modelo completo
    public $cancellationReason = [];
    public $refreshKey = 0; // Agregar esta propiedad

    public function mount($batch)
    {
        // Si recibe un modelo, extraer el ID
        $this->batchId = is_object($batch) ? $batch->id : $batch;
    }

    public function cancelEnrollment($enrollmentId)
    {
        try {
            DB::transaction(function () use ($enrollmentId) {
                $enrollment = StudentEnrollment::findOrFail($enrollmentId);
                $batch = EnrollmentBatch::findOrFail($this->batchId);

                // Validar que pertenece al batch
                if ($enrollment->enrollment_batch_id !== $batch->id) {
                    throw new \Exception('La inscripción no pertenece a este lote.');
                }

                // Validar que está pendiente
                if ($enrollment->payment_status !== 'pending') {
                    throw new \Exception('Solo se pueden anular inscripciones pendientes de pago.');
                }

                // Anular la inscripción
                $enrollment->update([
                    'cancelled_at' => now(),
                    'cancelled_by_user_id' => auth()->id(),
                    'cancellation_reason' => $this->cancellationReason[$enrollmentId] ?? null,
                    'payment_status' => 'refunded',
                ]);

                // Recalcular el total_amount del batch
                $this->updateBatchTotalAmount($batch);

                // Verificar si se debe anular el batch completo
                $this->checkBatchCancellation($batch);
            });

            // Limpiar el motivo de cancelación
            unset($this->cancellationReason[$enrollmentId]);

            // Incrementar refreshKey para forzar re-render
            $this->refreshKey++;

            Notification::make()
                ->title('Inscripción Anulada')
                ->body('La inscripción ha sido anulada exitosamente.')
                ->success()
                ->send();

            // Cerrar el modal
            $this->dispatch('close-modal', id: 'cancel-enrollment-' . $enrollmentId);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Anular')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function updateBatchTotalAmount($batch)
    {
        // Recalcular el total solo con inscripciones NO reembolsadas
        $newTotal = $batch->enrollments()
            ->where('payment_status', '!=', 'refunded')
            ->sum('total_amount');

        $batch->update([
            'total_amount' => $newTotal,
        ]);
    }

    private function checkBatchCancellation($batch)
    {
        // Verificar si todas las inscripciones están reembolsadas
        $totalEnrollments = $batch->enrollments()->count();
        $refundedEnrollments = $batch->enrollments()->where('payment_status', 'refunded')->count();

        if ($totalEnrollments === $refundedEnrollments && $totalEnrollments > 0) {
            // Anular el batch completo
            $batch->update([
                'cancelled_at' => now(),
                'cancelled_by_user_id' => auth()->id(),
                'cancellation_reason' => 'Todas las inscripciones fueron anuladas individualmente.',
                'payment_status' => 'refunded',
            ]);
        }
    }

    public function render()
    {
        $batch = EnrollmentBatch::findOrFail($this->batchId);

        $enrollments = $batch->enrollments()
            ->with(['instructorWorkshop.workshop', 'instructorWorkshop.instructor', 'tickets'])
            ->orderByRaw("CASE
                WHEN payment_status = 'completed' THEN 1
                WHEN payment_status = 'pending' THEN 2
                WHEN payment_status = 'refunded' THEN 3
                ELSE 4
            END")
            ->get();

        return view('livewire.manage-enrollments', [
            'batch' => $batch,
            'enrollments' => $enrollments,
        ]);
    }
}
