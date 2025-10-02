<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Pages;

use App\Exports\EnrollmentBatchExport;
use App\Filament\Resources\EnrollmentBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrollmentBatches extends ListRecords
{
    protected static string $resource = EnrollmentBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Obtener los filtros activos de la tabla
                    $tableFilters = $this->getTableFiltersForm()->getState();

                    $filters = [];

                    // Extraer filtro de usuario
                    if (isset($tableFilters['created_by']['value'])) {
                        $filters['created_by'] = $tableFilters['created_by']['value'];
                    }

                    // Extraer filtro de estado de pago
                    if (isset($tableFilters['payment_status']['value'])) {
                        $filters['payment_status'] = $tableFilters['payment_status']['value'];
                    }

                    // Extraer filtro de método de pago
                    if (isset($tableFilters['payment_method']['value'])) {
                        $filters['payment_method'] = $tableFilters['payment_method']['value'];
                    }

                    // Extraer filtros de fecha de inscripción
                    if (isset($tableFilters['enrollment_date']['enrollment_from'])) {
                        $filters['enrollment_from'] = $tableFilters['enrollment_date']['enrollment_from'];
                    }
                    if (isset($tableFilters['enrollment_date']['enrollment_until'])) {
                        $filters['enrollment_until'] = $tableFilters['enrollment_date']['enrollment_until'];
                    }

                    // Generar nombre del archivo con fecha actual
                    $fileName = 'inscripciones_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                    // Exportar con filtros aplicados
                    return (new EnrollmentBatchExport($filters))->download($fileName);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
