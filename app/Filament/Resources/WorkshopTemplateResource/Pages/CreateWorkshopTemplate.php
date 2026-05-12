<?php

namespace App\Filament\Resources\WorkshopTemplateResource\Pages;

use App\Filament\Resources\WorkshopTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkshopTemplate extends CreateRecord
{
    protected static string $resource = WorkshopTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return WorkshopTemplateResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(WorkshopTemplateResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
