<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Pages;

use App\Filament\Resources\EnrollmentBatchResource;
use Filament\Actions;
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
}
