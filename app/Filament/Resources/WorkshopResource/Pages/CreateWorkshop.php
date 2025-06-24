<?php

namespace App\Filament\Resources\WorkshopResource\Pages;

use App\Filament\Resources\WorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkshop extends CreateRecord
{
    protected static string $resource = WorkshopResource::class;

    public function getRedirectUrl(): string
    {
        return WorkshopResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(WorkshopResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
