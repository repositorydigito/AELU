<?php

namespace App\Observers;

use App\Models\Workshop;
use App\Models\WorkshopPricing;
use Illuminate\Support\Facades\DB;

class WorkshopObserver
{
    // Bandera para evitar ejecuciones recursivas
    private static $syncing = false;

    public function created(Workshop $workshop): void
    {
        $this->syncPricing($workshop);
        $this->createInstructorWorkshop($workshop);
    }

    public function updated(Workshop $workshop): void
    {
        // Evitar ejecuciones recursivas
        if (self::$syncing) {
            return;
        }

        // Verificar que el workshop EXISTE en BD antes de continuar
        if (!$workshop->exists) {
            // \Log::warning('Intentando actualizar un workshop que no existe en BD', ['id' => $workshop->id]);
            return;
        }

        // Regenerar tarifas si cambia la tarifa mensual, porcentaje de recargo o número de clases
        if ($workshop->isDirty(['standard_monthly_fee', 'pricing_surcharge_percentage', 'number_of_classes'])) {
            $this->syncPricing($workshop);
        }

        // Actualizar InstructorWorkshops si cambian campos relevantes
        if ($workshop->isDirty(['day_of_week', 'start_time', 'duration', 'capacity', 'place'])) {
            $this->updateInstructorWorkshops($workshop);
        }

        // Asegurar creación de InstructorWorkshop si no existía al crear el Workshop
        // Caso típico: se creó el taller sin instructor y luego se asignó en una edición.
        if ($workshop->instructor_id && $workshop->instructorWorkshops()->count() === 0) {
            $this->createInstructorWorkshop($workshop);
        }
    }

    protected function syncPricing(Workshop $workshop): void
    {
        DB::transaction(function () use ($workshop) {
            // Eliminar todas las tarifas existentes para este taller
            WorkshopPricing::where('workshop_id', $workshop->id)->delete();

            $standardMonthlyFee = $workshop->standard_monthly_fee;
            $surchargePercentage = $workshop->pricing_surcharge_percentage ?? 20.00;
            $surchargeMultiplier = 1 + ($surchargePercentage / 100);
            $numberOfClasses = $workshop->number_of_classes ?? 4;
            $basePerClass = $standardMonthlyFee / $numberOfClasses;

            // Tarifas voluntarios
            $volunteerPricings = [];
            for ($i = 1; $i < $numberOfClasses; $i++) {
                $priceWithSurcharge = round($basePerClass * $surchargeMultiplier * $i, 2);
                $volunteerPricings[$i] = $priceWithSurcharge;
            }
            $volunteerPricings[$numberOfClasses] = $standardMonthlyFee;

            foreach ($volunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === $numberOfClasses),
                    'for_volunteer_workshop' => true,
                ]);
            }

            // Tarifas no voluntarios
            $nonVolunteerPricings = [];
            for ($i = 1; $i < $numberOfClasses; $i++) {
                $priceWithSurcharge = round($basePerClass * $surchargeMultiplier * $i, 2);
                $nonVolunteerPricings[$i] = $priceWithSurcharge;
            }
            $nonVolunteerPricings[$numberOfClasses] = $standardMonthlyFee;

            foreach ($nonVolunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === $numberOfClasses),
                    'for_volunteer_workshop' => false,
                ]);
            }
        });
    }

    protected function createInstructorWorkshop(Workshop $workshop): void
    {
        // Solo crear si no existe ya y tiene instructor asignado
        if ($workshop->instructorWorkshops()->count() === 0 && $workshop->instructor_id) {
            $daysOfWeek = $workshop->day_of_week;
            $endTime = null;

            if ($workshop->start_time && $workshop->duration) {
                try {
                    $startTime = \Carbon\Carbon::parse($workshop->start_time);
                    $endTime = $startTime->addMinutes($workshop->duration)->format('H:i:s');
                } catch (\Exception $e) {
                    $endTime = $workshop->start_time;
                }
            }

            \App\Models\InstructorWorkshop::create([
                'workshop_id' => $workshop->id,
                'instructor_id' => $workshop->instructor_id,
                'day_of_week' => $daysOfWeek,
                'start_time' => $workshop->start_time,
                'end_time' => $endTime,
                'max_capacity' => $workshop->capacity,
                'is_active' => true,
                'payment_type' => 'volunteer',
                'place' => $workshop->place ?? null,
            ]);
        }
    }

    protected function updateInstructorWorkshops(Workshop $workshop): void
    {
        // Activar bandera para evitar recursión
        self::$syncing = true;

        try {
            $daysOfWeek = $workshop->day_of_week ?? ['Lunes'];
            $endTime = null;

            if ($workshop->start_time && $workshop->duration) {
                try {
                    $startTime = \Carbon\Carbon::parse($workshop->start_time);
                    $endTime = $startTime->addMinutes($workshop->duration)->format('H:i:s');
                } catch (\Exception $e) {
                    $endTime = $workshop->start_time;
                }
            }

            // Actualizar todos los InstructorWorkshops asociados
            // Usar withoutEvents para evitar disparar observers en cascade
            $workshop->instructorWorkshops()->each(function($instructorWorkshop) use ($daysOfWeek, $workshop, $endTime) {
                $instructorWorkshop->withoutEvents(function() use ($instructorWorkshop, $daysOfWeek, $workshop, $endTime) {
                    $instructorWorkshop->update([
                        'day_of_week' => $daysOfWeek,
                        'start_time' => $workshop->start_time,
                        'end_time' => $endTime,
                        'max_capacity' => $workshop->capacity,
                        'place' => $workshop->place,
                    ]);
                });
            });
        } finally {
            // Desactivar bandera
            self::$syncing = false;
        }
    }
}
