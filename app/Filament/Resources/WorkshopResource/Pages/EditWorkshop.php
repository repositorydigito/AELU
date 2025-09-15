<?php

namespace App\Filament\Resources\WorkshopResource\Pages;

use App\Filament\Resources\WorkshopResource;
use App\Models\WorkshopClass;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshop extends EditRecord
{
    protected static string $resource = WorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return WorkshopResource::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $workshop = $this->record;

        // Cargar las clases existentes
        $workshopClasses = $workshop->workshopClasses()
            ->orderBy('class_date', 'asc')
            ->get();

        if ($workshopClasses->isNotEmpty()) {
            // Establecer la fecha de inicio como la primera clase
            $data['temp_start_date'] = $workshopClasses->first()->class_date->format('Y-m-d');

            // Generar schedule_data desde las clases existentes
            $scheduleData = [];
            foreach ($workshopClasses as $index => $class) {
                $scheduleData[] = [
                    'class_number' => $index + 1,
                    'date' => $class->class_date->format('d/m/Y'),
                    'raw_date' => $class->class_date->format('Y-m-d'),
                    'day' => $workshop->day_of_week,
                    'is_holiday' => false,
                ];
            }
            $data['schedule_data'] = $scheduleData;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $workshop = $this->record;
        $scheduleData = $this->data['schedule_data'] ?? [];

        if (!empty($scheduleData) && is_array($scheduleData)) {
            // ELIMINAR todas las clases existentes primero
            $workshop->workshopClasses()->delete();

            // CREAR las clases nuevamente con los datos actualizados
            foreach ($scheduleData as $index => $classData) {
                WorkshopClass::create([
                    'workshop_id' => $workshop->id,
                    'monthly_period_id' => $workshop->monthly_period_id,
                    'class_date' => $classData['raw_date'],
                    'start_time' => $workshop->start_time,
                    'end_time' => $workshop->end_time, // Asegúrate de que end_time esté calculado
                    'status' => 'scheduled',
                    'max_capacity' => $workshop->capacity,
                ]);
            }
        }
    }
}
