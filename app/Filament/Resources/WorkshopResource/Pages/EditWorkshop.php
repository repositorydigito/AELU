<?php

namespace App\Filament\Resources\WorkshopResource\Pages;

use App\Filament\Resources\WorkshopResource;
use App\Models\InstructorWorkshop;
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
        // Cargar los datos del horario desde workshop_classes
        $workshop = $this->record;
        
        // Obtener las clases existentes del taller
        $workshopClasses = \App\Models\WorkshopClass::whereHas('instructorWorkshop', function ($query) use ($workshop) {
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

    protected function afterSave(): void
    {
        $this->syncWorkshopClasses();
    }

    protected function syncWorkshopClasses(): void
    {
        $workshop = $this->record;
        $scheduleData = $this->data['schedule_data'] ?? [];

        if (empty($scheduleData)) {
            return;
        }

        // Mapear nombres de días a números
        $dayMapping = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 0,
        ];

        $dayOfWeekNumber = $dayMapping[$workshop->day_of_week] ?? 1;

        // Crear o encontrar el InstructorWorkshop principal
        $instructorWorkshop = InstructorWorkshop::firstOrCreate([
            'workshop_id' => $workshop->id,
            'instructor_id' => $workshop->instructor_id,
        ], [
            'day_of_week' => $dayOfWeekNumber,
            'start_time' => $workshop->start_time,
            'end_time' => \Carbon\Carbon::parse($workshop->start_time)->addMinutes((int) $workshop->duration)->format('H:i:s'),
            'duration_hours' => (int) $workshop->duration / 60, // Convertir minutos a horas
            'max_capacity' => $workshop->capacity,
            'is_active' => true,
        ]);

        // Eliminar clases existentes para este instructor_workshop
        WorkshopClass::where('instructor_workshop_id', $instructorWorkshop->id)->delete();

        // Crear las nuevas clases basándose en schedule_data
        foreach ($scheduleData as $classData) {
            $classDate = \Carbon\Carbon::parse($classData['raw_date']);
            
            // Encontrar el período mensual correcto para esta fecha
            $monthlyPeriod = \App\Models\MonthlyPeriod::where('start_date', '<=', $classDate)
                ->where('end_date', '>=', $classDate)
                ->first();
            
            // Si no existe un período mensual, crear uno para ese mes
            if (!$monthlyPeriod) {
                $monthlyPeriod = \App\Models\MonthlyPeriod::create([
                    'year' => $classDate->year,
                    'month' => $classDate->month,
                    'start_date' => $classDate->startOfMonth()->format('Y-m-d'),
                    'end_date' => $classDate->endOfMonth()->format('Y-m-d'),
                    'is_active' => true,
                    'auto_generate_classes' => false,
                ]);
            }

            WorkshopClass::create([
                'instructor_workshop_id' => $instructorWorkshop->id,
                'monthly_period_id' => $monthlyPeriod->id,
                'class_date' => $classData['raw_date'],
                'start_time' => $workshop->start_time,
                'end_time' => \Carbon\Carbon::parse($workshop->start_time)->addMinutes((int) $workshop->duration)->format('H:i:s'),
                'status' => 'scheduled',
                'max_capacity' => $workshop->capacity,
            ]);
        }
    }
}
