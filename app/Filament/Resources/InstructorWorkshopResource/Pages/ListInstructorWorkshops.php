<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructorWorkshops extends ListRecords
{
    protected static string $resource = InstructorWorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
