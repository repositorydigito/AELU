<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Pages;

use App\Filament\Resources\EnrollmentBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrollmentBatches extends ListRecords
{
    protected static string $resource = EnrollmentBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
