<?php

namespace App\Filament\Resources\ClassAttendanceResource\Pages;

use App\Filament\Resources\ClassAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassAttendances extends ListRecords
{
    protected static string $resource = ClassAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
