<?php

namespace App\Filament\Resources\InstructorPaymentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\InstructorPayment;

class LiquidatedInstructorsCount extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 3;

    protected function getStats(): array
    {
        $liquidatedPaymentsCount = InstructorPayment::where('payment_status', 'paid')->count();

        return [
            Stat::make('Número de Profesores Liquidados', $liquidatedPaymentsCount)
                ->description('Total de pagos a instructores marcados como "Pagados"')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Número de Profesores Liquidados', $liquidatedPaymentsCount)
                ->description('Total de pagos a instructores marcados como "Pagados"')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Número de Profesores Liquidados', $liquidatedPaymentsCount)
                ->description('Total de pagos a instructores marcados como "Pagados"')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

        ];
    }
}
