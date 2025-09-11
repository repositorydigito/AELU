<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Exports\IncomeExport;
use App\Filament\Resources\IncomeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

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

                    // Extraer filtros de fecha de pago
                    if (isset($tableFilters['payment_date']['payment_from'])) {
                        $filters['payment_from'] = $tableFilters['payment_date']['payment_from'];
                    }
                    if (isset($tableFilters['payment_date']['payment_until'])) {
                        $filters['payment_until'] = $tableFilters['payment_date']['payment_until'];
                    }

                    // Extraer filtros de fecha de inscripción
                    if (isset($tableFilters['enrollment_date_filter']['enrollment_date_from'])) {
                        $filters['enrollment_date_from'] = $tableFilters['enrollment_date_filter']['enrollment_date_from'];
                    }
                    if (isset($tableFilters['enrollment_date_filter']['enrollment_date_until'])) {
                        $filters['enrollment_date_until'] = $tableFilters['enrollment_date_filter']['enrollment_date_until'];
                    }

                    // Extraer filtro de método de pago
                    if (isset($tableFilters['payment_method']['value'])) {
                        $filters['payment_method'] = $tableFilters['payment_method']['value'];
                    }

                    // Extraer filtro de tipo de inscripción
                    if (isset($tableFilters['enrollment_type']['value'])) {
                        $filters['enrollment_type'] = $tableFilters['enrollment_type']['value'];
                    }

                    // Generar nombre del archivo con fecha actual
                    $fileName = 'ingresos_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                    // Exportar con filtros aplicados
                    return (new IncomeExport($filters))->download($fileName);
                }),
        ];
    }
}
