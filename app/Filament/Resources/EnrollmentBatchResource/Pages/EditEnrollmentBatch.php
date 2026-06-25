<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Pages;

use App\Filament\Resources\EnrollmentBatchResource;
use App\Models\StudentEnrollment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEnrollmentBatch extends EditRecord
{
    protected static string $resource = EnrollmentBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Actualizar el lote de inscripciones
        $record->update($data);

        // Sincronizar el estado de pago con todas las inscripciones individuales del lote
        if (isset($data['payment_status'])) {
            $updatedCount = StudentEnrollment::where('enrollment_batch_id', $record->id)
                ->update([
                    'payment_status' => $data['payment_status'],
                    'payment_method' => $data['payment_method'] ?? $record->payment_method,
                    'payment_due_date' => $data['payment_due_date'] ?? null,
                    'payment_date' => $data['payment_date'] ?? null,
                    'payment_document' => $data['payment_document'] ?? null,
                ]);

            // Mostrar notificación de sincronización
            if ($updatedCount > 0) {
                Notification::make()
                    ->title('Estado sincronizado')
                    ->body("Se actualizó el estado de {$updatedCount} inscripción".($updatedCount > 1 ? 'es' : '').' individual'.($updatedCount > 1 ? 'es' : '').' asociada'.($updatedCount > 1 ? 's' : '').' a este lote.')
                    ->success()
                    ->send();
            }
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return EnrollmentBatchResource::getUrl('index');
    }

    // Fuerza HTTP redirect completo en lugar de wire:navigate,
    // evitando que la tabla del listado quede vacía tras el guardado.
    protected function afterSave(): void
    {
        redirect()->to($this->getRedirectUrl());
    }
}
