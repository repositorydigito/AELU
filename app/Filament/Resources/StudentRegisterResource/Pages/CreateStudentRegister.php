<?php

namespace App\Filament\Resources\StudentRegisterResource\Pages;

use App\Filament\Resources\StudentRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentRegister extends CreateRecord
{
    protected static string $resource = StudentRegisterResource::class;

    protected function getRedirectUrl(): string
    {
        return StudentRegisterResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(StudentRegisterResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
