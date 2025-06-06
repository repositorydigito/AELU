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
        // return $this->getResource()::getUrl('index');
        return InstructorResource::getUrl('index');

    }
}
