<?php

namespace App\Filament\Resources\StudentRegisterResource\Pages;

use App\Filament\Resources\StudentRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentRegister extends EditRecord
{
    protected static string $resource = StudentRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return StudentRegisterResource::getUrl('index');
    }
}
