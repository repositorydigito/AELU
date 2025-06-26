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
    }    
    public function updated(Workshop $workshop): void
    {
        if ($workshop->isDirty('standard_monthly_fee')) {
            $this->syncPricing($workshop);
        }
    }

    protected function syncPricing(Workshop $workshop): void
    {
        // Usar una transacción para asegurar que la operación sea atómica.
        // Si algo falla, se revierte todo.
        DB::transaction(function () use ($workshop) {
            // 1. Eliminar todas las tarifas existentes para este taller
            WorkshopPricing::where('workshop_id', $workshop->id)->delete();

            $standardMonthlyFee = $workshop->standard_monthly_fee;

            // Precios para talleres voluntarios (for_volunteer_workshop = true)
            // Lado izquierdo del tarifario
            $basePerClassVolunteer = $standardMonthlyFee / 4;

            $volunteerPricings = [
                1 => round($basePerClassVolunteer * 1.20, 2),
                2 => round($basePerClassVolunteer * 1.20 * 2, 2),
                3 => round($basePerClassVolunteer * 1.20 * 3, 2),
                4 => $standardMonthlyFee, // Tarifa estándar
                5 => round($standardMonthlyFee * 1.25, 2), // 5ta clase tiene un costo adicional
            ];

            foreach ($volunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4), // Solo 4 clases es la tarifa por defecto
                    'for_volunteer_workshop' => true,
                ]);
            }

            // Precios para talleres NO voluntarios (for_volunteer_workshop = false)
            // Lado derecho del tarifario
            $basePerClassNonVolunteer = $standardMonthlyFee / 4;

            $nonVolunteerPricings = [
                1 => round($basePerClassNonVolunteer * 1.20, 2),
                2 => round($basePerClassNonVolunteer * 1.20 * 2, 2),
                3 => round($basePerClassNonVolunteer * 1.20 * 3, 2),
                4 => $standardMonthlyFee, // Tarifa estándar
                5 => $standardMonthlyFee, // 5ta clase no tiene costo adicional (mismo precio que 4)
            ];

            foreach ($nonVolunteerPricings as $numClasses => $price) {
                WorkshopPricing::create([
                    'workshop_id' => $workshop->id,
                    'number_of_classes' => $numClasses,
                    'price' => $price,
                    'is_default' => ($numClasses === 4), // Solo 4 clases es la tarifa por defecto
                    'for_volunteer_workshop' => false,
                ]);
            }
        });
    }
    
}
