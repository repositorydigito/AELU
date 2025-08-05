<?php

namespace App\Observers;

use App\Models\Workshop;
use App\Models\WorkshopPricing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkshopObserver
{
    public function created(Workshop $workshop): void
    {
        $this->syncPricing($workshop);
    }

    public function updated(Workshop $workshop): void
    {
        // Regenerar tarifas si cambia la tarifa mensual o el porcentaje de recargo
        if ($workshop->isDirty(['standard_monthly_fee', 'pricing_surcharge_percentage'])) {
            $this->syncPricing($workshop);
        }

        // Sincronizar horarios y capacidad con InstructorWorkshops si cambian campos relevantes
        if ($workshop->isDirty(['start_time', 'duration', 'day_of_week', 'capacity'])) {
            $this->syncInstructorWorkshopsSchedule($workshop);
        }
    }

    protected function syncPricing(Workshop $workshop): void
    {
        // Usar una transacción para asegurar que la operación sea atómica.
        DB::transaction(function () use ($workshop) {
            // 1. Eliminar todas las tarifas existentes para este taller
            WorkshopPricing::where('workshop_id', $workshop->id)->delete();

            $standardMonthlyFee = $workshop->standard_monthly_fee;
            $surchargePercentage = $workshop->pricing_surcharge_percentage ?? 20.00; // Fallback al 20% si es null

            // Calcular el multiplicador de recargo
            $surchargeMultiplier = 1 + ($surchargePercentage / 100);

            // Precio base por clase (tarifa mensual / 4)
            $basePerClass = $standardMonthlyFee / 4;

            // === TARIFAS PARA TALLERES VOLUNTARIOS (for_volunteer_workshop = true) ===
            $volunteerPricings = [
                1 => round($basePerClass * $surchargeMultiplier, 2),                    // 1 clase con recargo
                2 => round($basePerClass * $surchargeMultiplier * 2, 2),                // 2 clases con recargo
                3 => round($basePerClass * $surchargeMultiplier * 3, 2),                // 3 clases con recargo
                4 => $standardMonthlyFee,                                               // 4 clases = tarifa estándar (sin recargo)
                5 => round($standardMonthlyFee * 1.25, 2),                             // 5ta clase tiene 25% adicional sobre tarifa mensual
            ];

            foreach ($volunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4), // 4 clases es la tarifa por defecto
                    'for_volunteer_workshop' => true,
                ]);
            }

            // === TARIFAS PARA TALLERES NO VOLUNTARIOS (for_volunteer_workshop = false) ===
            $nonVolunteerPricings = [
                1 => round($basePerClass * $surchargeMultiplier, 2),                    // 1 clase con recargo
                2 => round($basePerClass * $surchargeMultiplier * 2, 2),                // 2 clases con recargo
                3 => round($basePerClass * $surchargeMultiplier * 3, 2),                // 3 clases con recargo
                4 => $standardMonthlyFee,                                               // 4 clases = tarifa estándar (sin recargo)
                5 => $standardMonthlyFee,                                               // 5ta clase = mismo precio que 4 (sin recargo adicional)
            ];

            foreach ($nonVolunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4), // 4 clases es la tarifa por defecto
                    'for_volunteer_workshop' => false,
                ]);
            }
        });
    }

    protected function syncInstructorWorkshopsSchedule(Workshop $workshop): void
    {
        // Solo proceder si hay InstructorWorkshops relacionados
        if ($workshop->instructorWorkshops()->count() === 0) {
            return;
        }

        // Calcular la nueva hora de fin
        $endTime = null;
        $durationHours = null;

        if ($workshop->start_time && $workshop->duration) {
            $startTime = Carbon::parse($workshop->start_time);
            $endTime = $startTime->copy()->addMinutes($workshop->duration)->format('H:i:s');
            $durationHours = round($workshop->duration / 60, 2); // Convertir minutos a horas con 2 decimales
        }

        // Mapear día de la semana a número
        $dayOfWeekNumber = $this->mapDayOfWeekToNumber($workshop->day_of_week);

        // Preparar datos para actualización
        $updateData = [];
        
        if ($workshop->isDirty('start_time')) {
            $updateData['start_time'] = $workshop->start_time;
        }
        
        if ($workshop->isDirty(['start_time', 'duration'])) {
            $updateData['end_time'] = $endTime;
        }
        
        if ($workshop->isDirty('day_of_week')) {
            $updateData['day_of_week'] = $dayOfWeekNumber;
        }
        
        if ($workshop->isDirty('duration')) {
            $updateData['duration_hours'] = $durationHours;
        }
        
        if ($workshop->isDirty('capacity')) {
            $updateData['max_capacity'] = $workshop->capacity;
        }

        // Solo actualizar si hay cambios
        if (!empty($updateData)) {
            $affectedRows = $workshop->instructorWorkshops()->update($updateData);

            // Log para debugging (opcional - puedes comentar en producción)
            \Log::info("Workshop horarios sincronizados", [
                'workshop_id' => $workshop->id,
                'workshop_name' => $workshop->name,
                'updated_fields' => array_keys($updateData),
                'update_data' => $updateData,
                'affected_instructor_workshops' => $affectedRows,
                'changes' => $workshop->getDirty()
            ]);
        }
    }


    /** * Mapear día de la semana en español a número * Coincide con el formato usado en InstructorWorkshop */
     
    private function mapDayOfWeekToNumber(?string $dayOfWeek): ?int
    {
        if (!$dayOfWeek) return null;

        $mapping = [
            'Lunes' => 1,
            'Martes' => 2,  
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 0, 
        ];

        return $mapping[$dayOfWeek] ?? null;
    }
}
