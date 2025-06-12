<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorWorkshop extends ViewRecord
{
    protected static string $resource = InstructorWorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
