<?php

namespace App\Services;

use App\Models\InstructorWorkshop;
use App\Models\Workshop;

class WorkshopAutoCreationService
{
    /**
     * Crear automáticamente un taller para un período futuro basado en un taller previo
     */
    public function createWorkshopForFuturePeriod(int $previousWorkshopId, int $targetMonthlyPeriodId): ?InstructorWorkshop
    {
        $previousInstructorWorkshop = InstructorWorkshop::with('workshop')->find($previousWorkshopId);

        if (! $previousInstructorWorkshop || ! $previousInstructorWorkshop->workshop) {
            return null;
        }

        $previousWorkshop = $previousInstructorWorkshop->workshop;

        // Crear Workshop EXACTAMENTE igual al anterior, solo cambiando monthly_period_id
        $newWorkshop = Workshop::updateOrCreate(
            [
                'name' => $previousWorkshop->name,
                'monthly_period_id' => $targetMonthlyPeriodId,
            ],
            [
                // Copiar TODOS los campos del workshop original
                'description' => $previousWorkshop->description,
                'standard_monthly_fee' => $previousWorkshop->standard_monthly_fee,
                'pricing_surcharge_percentage' => $previousWorkshop->pricing_surcharge_percentage,
                'instructor_id' => $previousWorkshop->instructor_id,
                'delegate_user_id' => $previousWorkshop->delegate_user_id,
                'day_of_week' => $previousWorkshop->day_of_week,
                'start_time' => $previousWorkshop->start_time,
                'duration' => $previousWorkshop->duration,
                'capacity' => $previousWorkshop->capacity,
                'number_of_classes' => $previousWorkshop->number_of_classes,
                'place' => $previousWorkshop->place,
                'additional_comments' => $previousWorkshop->additional_comments,
            ]
        );

        // Crear InstructorWorkshop EXACTAMENTE igual al anterior, solo cambiando initial_monthly_period_id
        $newInstructorWorkshop = InstructorWorkshop::updateOrCreate(
            [
                'instructor_id' => $previousInstructorWorkshop->instructor_id,
                'workshop_id' => $newWorkshop->id,
                'day_of_week' => $previousInstructorWorkshop->day_of_week,
                'start_time' => $previousInstructorWorkshop->start_time,
                'initial_monthly_period_id' => $targetMonthlyPeriodId,
            ],
            [
                // Copiar TODOS los campos del instructor_workshop original
                'end_time' => $previousInstructorWorkshop->end_time,
                'max_capacity' => $previousInstructorWorkshop->max_capacity,
                'place' => $previousInstructorWorkshop->place,
                'is_active' => $previousInstructorWorkshop->is_active,
                'payment_type' => $previousInstructorWorkshop->payment_type,
                'hourly_rate' => $previousInstructorWorkshop->hourly_rate,
                'duration_hours' => $previousInstructorWorkshop->duration_hours,
                'custom_volunteer_percentage' => $previousInstructorWorkshop->custom_volunteer_percentage,
            ]
        );

        // Copiar también los workshop_pricings si existen
        $originalPricings = $previousWorkshop->workshopPricings;
        foreach ($originalPricings as $pricing) {
            \App\Models\WorkshopPricing::updateOrCreate(
                [
                    'workshop_id' => $newWorkshop->id,
                    'number_of_classes' => $pricing->number_of_classes,
                    'for_volunteer_workshop' => $pricing->for_volunteer_workshop,
                ],
                [
                    'price' => $pricing->price,
                    'base_amount' => $pricing->base_amount,
                    'surcharge_amount' => $pricing->surcharge_amount,
                ]
            );
        }

        return $newInstructorWorkshop;
    }

    /**
     * Buscar o crear InstructorWorkshop para un período específico
     */
    public function findOrCreateInstructorWorkshopForPeriod(int $previousInstructorWorkshopId, int $targetMonthlyPeriodId): ?InstructorWorkshop
    {
        $previousInstructorWorkshop = InstructorWorkshop::find($previousInstructorWorkshopId);

        if (! $previousInstructorWorkshop) {
            return null;
        }

        // Primero intentar buscar uno existente
        $existing = InstructorWorkshop::whereHas('workshop', function ($query) use ($targetMonthlyPeriodId) {
            $query->where('monthly_period_id', $targetMonthlyPeriodId);
        })
            ->where('instructor_id', $previousInstructorWorkshop->instructor_id)
            ->where('day_of_week', $previousInstructorWorkshop->day_of_week)
            ->where('start_time', $previousInstructorWorkshop->start_time)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Si no existe, crear uno nuevo
        return $this->createWorkshopForFuturePeriod($previousInstructorWorkshopId, $targetMonthlyPeriodId);
    }
}
