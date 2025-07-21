<?php

namespace App\Filament\Resources\WorkshopResource\Pages;

use App\Filament\Resources\WorkshopResource;
use App\Models\WorkshopClass;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkshop extends ViewRecord
{
    protected static string $resource = WorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los datos del horario desde workshop_classes
        $workshop = $this->record;
        
        // Obtener las clases existentes del taller
        $workshopClasses = WorkshopClass::whereHas('instructorWorkshop', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
        ->orderBy('class_date')
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
}
