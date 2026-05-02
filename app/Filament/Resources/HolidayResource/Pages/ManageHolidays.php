<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use App\Models\Holiday;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageHolidays extends ManageRecords
{
    protected static string $resource = HolidayResource::class;

    public int $filterYear;

    public function mount(): void
    {
        parent::mount();
        $this->filterYear = now()->year;
    }

    public function updatedFilterYear(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return Holiday::query()->whereYear('date', $this->filterYear);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('lg')
                ->label('Agregar Feriado'),
        ];
    }
}
