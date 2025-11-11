<?php

namespace App\Observers;

use App\Models\Workshop;
use App\Models\WorkshopPricing;
use Illuminate\Support\Facades\DB;

class WorkshopObserver
{
    public function created(Workshop $workshop): void
    {
        $this->syncPricing($workshop);
        $this->createInstructorWorkshop($workshop);
    }

    public function updated(Workshop $workshop): void
    {
        // Regenerar tarifas si cambia la tarifa mensual o el porcentaje de recargo
        if ($workshop->isDirty(['standard_monthly_fee', 'pricing_surcharge_percentage'])) {
            $this->syncPricing($workshop);
        }

        // Actualizar InstructorWorkshops si cambian campos relevantes
        if ($workshop->isDirty(['day_of_week', 'start_time', 'duration', 'capacity', 'place'])) {
            $this->updateInstructorWorkshops($workshop);
        }
    }

    protected function syncPricing(Workshop $workshop): void
    {
        // Tu código existente...
        DB::transaction(function () use ($workshop) {
            WorkshopPricing::where('workshop_id', $workshop->id)->delete();

            $standardMonthlyFee = $workshop->standard_monthly_fee;
            $surchargePercentage = $workshop->pricing_surcharge_percentage ?? 20.00;
            $surchargeMultiplier = 1 + ($surchargePercentage / 100);
            $numberOfClasses = $workshop->number_of_classes ?? 4;
            $basePerClass = $standardMonthlyFee / $numberOfClasses;

            // Tarifas voluntarios
            $volunteerPricings = [];
            for ($i = 1; $i < $numberOfClasses; $i++) {
                $volunteerPricings[$i] = round($basePerClass * $surchargeMultiplier * $i, 2);
            }
            $volunteerPricings[$numberOfClasses] = $standardMonthlyFee; // Tarifa completa sin recargo

            // Solo agregar opción de 5 clases si el taller tiene 4 clases base
            if ($numberOfClasses == 4) {
                $volunteerPricings[5] = round($standardMonthlyFee * 1.25, 2);
            }

            foreach ($volunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4),
                    'for_volunteer_workshop' => true,
                ]);
            }

            // Tarifas no voluntarios
            $nonVolunteerPricings = [];
            for ($i = 1; $i < $numberOfClasses; $i++) {
                $nonVolunteerPricings[$i] = round($basePerClass * $surchargeMultiplier * $i, 2);
            }
            $nonVolunteerPricings[$numberOfClasses] = $standardMonthlyFee; // Tarifa completa

            // Solo agregar opción de 5 clases si el taller tiene 4 clases base
            if ($numberOfClasses == 4) {
                $nonVolunteerPricings[5] = $standardMonthlyFee;
            }

            foreach ($nonVolunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4),
                    'for_volunteer_workshop' => false,
                ]);
            }
        });
    }

    protected function createInstructorWorkshop(Workshop $workshop): void
    {
        // Tu código existente...
        if ($workshop->instructorWorkshops()->count() === 0 && $workshop->instructor_id) {
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
                'day_of_week' => $dayOfWeekNumber,
                'start_time' => $workshop->start_time,
                'end_time' => $endTime,
                'max_capacity' => $workshop->capacity,
                'is_active' => true,
                'payment_type' => 'volunteer',
                'place' => $workshop->place ?? null,
            ]);
        }
    }

    /**
     * Actualizar InstructorWorkshops existentes cuando cambie el Workshop
     */
    protected function updateInstructorWorkshops(Workshop $workshop): void
    {
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
        $workshop->instructorWorkshops()->update([
            'day_of_week' => $dayOfWeekNumber,
            'start_time' => $workshop->start_time,
            'end_time' => $endTime,
            'max_capacity' => $workshop->capacity,
            'place' => $workshop->place,
        ]);
    }
}
