<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstructorWorkshop extends EditRecord
{
    protected static string $resource = InstructorWorkshopResource::class;

    public function getRedirectUrl(): string
    {        
        return InstructorWorkshopResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
