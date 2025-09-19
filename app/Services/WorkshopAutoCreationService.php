<?php

namespace App\Services;

use App\Models\InstructorWorkshop;
use App\Models\Workshop;

class WorkshopAutoCreationService
{
    /**
     * Generar automáticamente WorkshopClasses para un workshop y período específico
     */
    private function generateWorkshopClasses(Workshop $workshop, InstructorWorkshop $instructorWorkshop, int $monthlyPeriodId): void
    {
        // Obtener el período mensual para saber el rango de fechas
        $monthlyPeriod = \App\Models\MonthlyPeriod::find($monthlyPeriodId);
        if (!$monthlyPeriod) {
            return;
        }

        // Crear fecha del primer día del mes
        $startDate = \Carbon\Carbon::create($monthlyPeriod->year, $monthlyPeriod->month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Buscar la primera fecha que coincida con el día de la semana del taller
        $currentDate = $startDate->copy();
        // USAR InstructorWorkshop.day_of_week (numérico) en lugar de Workshop.day_of_week (string)
        $dayOfWeek = $instructorWorkshop->day_of_week;
        
        // Ajustar el día de la semana (Laravel usa 0=Domingo, 1=Lunes, etc.)
        // Si day_of_week es 7 (Domingo), convertir a 0
        $targetDayOfWeek = $dayOfWeek === 7 ? 0 : $dayOfWeek;
        
        // Encontrar el primer día que coincida
        while ($currentDate->dayOfWeek !== $targetDayOfWeek && $currentDate->lte($endDate)) {
            $currentDate->addDay();
        }

        $classDates = [];
        $classCount = 0;
        
        // Generar exactamente 4 clases (siempre habrá al menos 4 ocurrencias en cualquier mes)
        for ($i = 0; $i < 4; $i++) {
            $classDates[] = $currentDate->copy();
            $currentDate->addWeek(); // Avanzar una semana para la siguiente clase
        }

        // Crear los WorkshopClass records
        foreach ($classDates as $index => $classDate) {
            \App\Models\WorkshopClass::updateOrCreate(
                [
                    'workshop_id' => $workshop->id,
                    'monthly_period_id' => $monthlyPeriod->id,
                    'class_date' => $classDate->format('Y-m-d'),
                ],
                [
                    'class_number' => $index + 1,
                    'start_time' => $workshop->start_time,
                    'end_time' => $workshop->end_time ?? \Carbon\Carbon::parse($workshop->start_time)->addHours(2)->format('H:i:s'),
                    'status' => 'scheduled',
                    'max_capacity' => $workshop->capacity,
                ]
            );
        }
    }

    /**
     * Crear automáticamente un taller para un período futuro basado en un taller previo
     * (Método modificado para incluir la generación de clases)
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
                'end_time' => $previousWorkshop->end_time,
                'duration' => $previousWorkshop->duration,
                'capacity' => $previousWorkshop->capacity,
                'number_of_classes' => 4, // Forzar siempre a 4 clases por defecto
                'place' => $previousWorkshop->place,
                'additional_comments' => $previousWorkshop->additional_comments,
            ]
        );

        // Crear InstructorWorkshop EXACTAMENTE igual al anterior
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

        // Copiar workshop_pricings
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

        // Generar automáticamente las WorkshopClasses
        $this->generateWorkshopClasses($newWorkshop, $newInstructorWorkshop, $targetMonthlyPeriodId);

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
