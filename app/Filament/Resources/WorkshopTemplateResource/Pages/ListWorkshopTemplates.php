<?php

namespace App\Filament\Resources\WorkshopTemplateResource\Pages;

use App\Filament\Resources\WorkshopTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopTemplates extends ListRecords
{
    protected static string $resource = WorkshopTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
