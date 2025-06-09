<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use App\Models\Instructor;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructor extends CreateRecord
{
    protected static string $resource = InstructorResource::class;

    public function getRedirectUrl(): string
    {        
        return InstructorResource::getUrl('index');
    }
    protected function getFormActions(): array
    {
        return [            
            Actions\Action::make('save') 
                ->label('Guardar') 
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(InstructorResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
