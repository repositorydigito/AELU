<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructorWorkshop extends CreateRecord
{
    protected static string $resource = InstructorWorkshopResource::class;

    public function getRedirectUrl(): string
    {        
        return InstructorWorkshopResource::getUrl('index');
    }

}
