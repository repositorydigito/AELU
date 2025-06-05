<?php

namespace App\Filament\Resources\StudentRegisterResource\Pages;

use App\Filament\Resources\StudentRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StudentResource;

class CreateStudentRegister extends CreateRecord
{
    protected static string $resource = StudentRegisterResource::class;

    protected function getRedirectUrl(): string
    {        
        return StudentResource::getUrl('index');     
    }
}
