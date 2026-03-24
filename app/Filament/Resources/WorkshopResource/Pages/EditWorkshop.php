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
                    'status' => $class->status,
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

        if (empty($scheduleData) || !is_array($scheduleData)) {
            return;
        }

        // Verificar si hay clases existentes
        $existingClasses = $workshop->workshopClasses()
            ->orderBy('class_date', 'asc')
            ->get();

        if ($existingClasses->isEmpty()) {
            // No hay clases → Crearlas desde cero
            foreach ($scheduleData as $classData) {
                WorkshopClass::create([
                    'workshop_id' => $workshop->id,
                    'monthly_period_id' => $workshop->monthly_period_id,
                    'class_date' => $classData['raw_date'],
                    'start_time' => $workshop->start_time,
                    'end_time' => \Carbon\Carbon::parse($workshop->start_time)->addMinutes($workshop->duration ?? 60)->format('H:i:s'),
                    'status' => $classData['status'] ?? 'scheduled',
                    'max_capacity' => $workshop->capacity,
                ]);
            }
        } else {
            // Ya hay clases → Actualizarlas preservando IDs y enrollment_classes
            \DB::transaction(function () use ($workshop, $scheduleData, $existingClasses) {
                $endTime = \Carbon\Carbon::parse($workshop->start_time)->addMinutes($workshop->duration ?? 60)->format('H:i:s');

                // PASO 1: Cambiar todas las fechas a temporales (año 3000)
                // Esto "libera" las fechas reales y evita violaciones de unique_class
                foreach ($existingClasses as $index => $class) {
                    $tempDate = '3000-01-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                    \DB::table('workshop_classes')
                        ->where('id', $class->id)
                        ->update(['class_date' => $tempDate]);
                }

                // PASO 2: Actualizar cada clase a su nueva fecha real
                foreach ($existingClasses as $index => $class) {
                    if (isset($scheduleData[$index])) {
                        \DB::table('workshop_classes')
                            ->where('id', $class->id)
                            ->update([
                                'class_date' => $scheduleData[$index]['raw_date'],
                                'start_time' => $workshop->start_time,
                                'end_time' => $endTime,
                                'status' => $scheduleData[$index]['status'] ?? $class->status,
                                'max_capacity' => $workshop->capacity,
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Menos clases en el nuevo schedule → Eliminar sobrantes
                        $class->delete();
                    }
                }

                // PASO 3: Crear clases adicionales si hay más en el nuevo schedule
                if (count($scheduleData) > $existingClasses->count()) {
                    for ($i = $existingClasses->count(); $i < count($scheduleData); $i++) {
                        WorkshopClass::create([
                            'workshop_id' => $workshop->id,
                            'monthly_period_id' => $workshop->monthly_period_id,
                            'class_date' => $scheduleData[$i]['raw_date'],
                            'start_time' => $workshop->start_time,
                            'end_time' => $endTime,
                            'status' => $scheduleData[$i]['status'] ?? 'scheduled',
                            'max_capacity' => $workshop->capacity,
                        ]);
                    }
                }
            });
        }

        // Sincronizar duration_hours en InstructorWorkshops de tipo hourly.
        // Esto dispara el Observer que recalcula los InstructorPayments pendientes.
        if ($workshop->duration) {
            $durationHours = round($workshop->duration / 60, 2);
            $workshop->instructorWorkshops()
                ->where('payment_type', 'hourly')
                ->each(fn ($iw) => $iw->update(['duration_hours' => $durationHours]));
        }
    }
}
