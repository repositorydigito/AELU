<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getRedirectUrl(): string
    {
        return ExpenseResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(ExpenseResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
