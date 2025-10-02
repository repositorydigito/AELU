<?php

namespace App\Filament\Resources\InstructorPaymentResource\Pages;

use App\Exports\InstructorPaymentExport;
use App\Filament\Resources\InstructorPaymentResource;
use App\Filament\Resources\InstructorPaymentResource\Widgets\LiquidatedInstructorsCount;
use App\Filament\Resources\InstructorPaymentResource\Widgets\PaymentStatusChart;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructorPayments extends ListRecords
{
    protected static string $resource = InstructorPaymentResource::class;

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

                    // Extraer filtro de período mensual
                    if (isset($tableFilters['monthly_period_id']['value'])) {
                        $filters['monthly_period_id'] = $tableFilters['monthly_period_id']['value'];
                    }

                    // Extraer filtro de tipo de pago
                    if (isset($tableFilters['payment_type']['value'])) {
                        $filters['payment_type'] = $tableFilters['payment_type']['value'];
                    }

                    // Extraer filtro de estado de pago
                    if (isset($tableFilters['payment_status']['value'])) {
                        $filters['payment_status'] = $tableFilters['payment_status']['value'];
                    }

                    // Generar nombre del archivo con fecha actual
                    $fileName = 'pagos_profesores_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                    // Exportar con filtros aplicados
                    return (new InstructorPaymentExport($filters))->download($fileName);
                }),
            Actions\CreateAction::make(),
        ];
    }

    /* protected function getHeaderWidgets(): array
    {
        return [
            LiquidatedInstructorsCount::class,
            PaymentStatusChart::class,

        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    } */
}
