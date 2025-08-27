<?php

namespace App\Filament\Resources\ClassAttendanceResource\Pages;

use App\Filament\Resources\ClassAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassAttendance extends EditRecord
{
    protected static string $resource = ClassAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
