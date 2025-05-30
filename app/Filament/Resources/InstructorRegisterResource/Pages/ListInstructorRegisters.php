<?php

namespace App\Filament\Resources\InstructorRegisterResource\Pages;

use App\Filament\Resources\InstructorRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructorRegisters extends ListRecords
{
    protected static string $resource = InstructorRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
