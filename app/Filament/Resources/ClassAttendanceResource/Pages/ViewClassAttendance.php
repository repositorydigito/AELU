<?php

namespace App\Filament\Resources\ClassAttendanceResource\Pages;

use App\Filament\Resources\ClassAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClassAttendance extends ViewRecord
{
    protected static string $resource = ClassAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
