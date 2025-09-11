<?php

namespace App\Filament\Resources\InstructorPaymentResource\Pages;

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
