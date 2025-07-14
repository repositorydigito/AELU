<?php

namespace App\Filament\Resources\InstructorPaymentResource\Pages;

use App\Filament\Resources\InstructorPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstructorPayment extends EditRecord
{
    protected static string $resource = InstructorPaymentResource::class;

    public function getRedirectUrl(): string
    {
        return InstructorPaymentResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
