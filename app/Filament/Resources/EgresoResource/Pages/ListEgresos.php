<?php

namespace App\Filament\Resources\EgresoResource\Pages;

use App\Exports\EgresoExport;
use App\Filament\Resources\EgresoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEgresos extends ListRecords
{
    protected static string $resource = EgresoResource::class;

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

                    // Extraer filtros de fecha de gasto
                    if (isset($tableFilters['date']['date_from'])) {
                        $filters['date_from'] = $tableFilters['date']['date_from'];
                    }
                    if (isset($tableFilters['date']['date_until'])) {
                        $filters['date_until'] = $tableFilters['date']['date_until'];
                    }

                    // Extraer filtros de fecha de registro
                    if (isset($tableFilters['created_date']['created_from'])) {
                        $filters['created_from'] = $tableFilters['created_date']['created_from'];
                    }
                    if (isset($tableFilters['created_date']['created_until'])) {
                        $filters['created_until'] = $tableFilters['created_date']['created_until'];
                    }

                    // Extraer filtro de concepto
                    if (isset($tableFilters['expense.concept']['value'])) {
                        $filters['concept'] = $tableFilters['expense.concept']['value'];
                    }

                    // Extraer filtros de rango de monto
                    if (isset($tableFilters['amount_range']['amount_from'])) {
                        $filters['amount_from'] = $tableFilters['amount_range']['amount_from'];
                    }
                    if (isset($tableFilters['amount_range']['amount_until'])) {
                        $filters['amount_until'] = $tableFilters['amount_range']['amount_until'];
                    }

                    // Extraer filtro de voucher
                    if (isset($tableFilters['has_voucher']['value'])) {
                        $filters['has_voucher'] = $tableFilters['has_voucher']['value'];
                    }

                    // Extraer filtro de proveedor
                    if (isset($tableFilters['razon_social']['proveedor'])) {
                        $filters['proveedor'] = $tableFilters['razon_social']['proveedor'];
                    }

                    // Generar nombre del archivo con fecha actual
                    $fileName = 'egresos_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                    // Exportar con filtros aplicados
                    return (new EgresoExport($filters))->download($fileName);
                }),
        ];
    }
}
