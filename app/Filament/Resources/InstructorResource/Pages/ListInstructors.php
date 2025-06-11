<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructors extends ListRecords
{
    protected static string $resource = InstructorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Profesor'),
            Actions\Action::make('import')
                ->label('Importar Profesores')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn () => static::$resource::getUrl('import'))
                ->color('primary'),            
        ];
    }
}
