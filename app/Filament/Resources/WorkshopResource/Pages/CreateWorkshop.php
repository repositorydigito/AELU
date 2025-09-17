<?php

namespace App\Filament\Resources\WorkshopResource\Pages;

use App\Filament\Resources\WorkshopResource;
use App\Models\WorkshopClass;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

class CreateWorkshop extends CreateRecord
{
    protected static string $resource = WorkshopResource::class;

    protected function getRedirectUrl(): string
    {
        return WorkshopResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(WorkshopResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function afterCreate(): void
    {
        $workshop = $this->record;
        $scheduleData = $this->data['schedule_data'] ?? [];

        if (! empty($scheduleData) && is_array($scheduleData)) {
            foreach ($scheduleData as $classData) {
                WorkshopClass::create([
                    'workshop_id' => $workshop->id,
                    'monthly_period_id' => $workshop->monthly_period_id,
                    'class_date' => $classData['raw_date'],
                    'start_time' => $workshop->start_time,
                    'end_time' => $workshop->end_time,
                    'status' => 'scheduled',
                    'max_capacity' => $workshop->capacity,
                ]);
            }
        }
    }
}
