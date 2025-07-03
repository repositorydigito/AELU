<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMovement extends CreateRecord
{
    protected static string $resource = MovementResource::class;

    public function getRedirectUrl(): string
    {
        return MovementResource::getUrl('index');
    }
}
