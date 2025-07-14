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
        // Regenerar tarifas si cambia la tarifa mensual o el porcentaje de recargo
        if ($workshop->isDirty(['standard_monthly_fee', 'pricing_surcharge_percentage'])) {
            $this->syncPricing($workshop);
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

            // Log para debugging (opcional)
            \Log::info("Tarifas generadas para taller: {$workshop->name}", [
                'standard_monthly_fee' => $standardMonthlyFee,
                'surcharge_percentage' => $surchargePercentage,
                'surcharge_multiplier' => $surchargeMultiplier,
                'base_per_class' => $basePerClass,
                'volunteer_pricings' => $volunteerPricings,
                'non_volunteer_pricings' => $nonVolunteerPricings,
            ]);
        });
    }
}