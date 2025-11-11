<?php

namespace App\Observers;

use App\Models\InstructorWorkshop;

class InstructorWorkshopObserver
{
    public function creating(InstructorWorkshop $instructorWorkshop): void
    {
        // LÓGICA ORIGINAL: Solo establecer initial_monthly_period_id si no está ya definido
        if (empty($instructorWorkshop->initial_monthly_period_id) && !empty($instructorWorkshop->workshop_id)) {
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }
        }

        // Sincronizar campos del Workshop solo si están vacíos
        $this->syncEmptyFieldsFromWorkshop($instructorWorkshop);
    }

    public function updating(InstructorWorkshop $instructorWorkshop): void
    {
        // LÓGICA ORIGINAL: Si cambia el workshop_id, actualizar también el initial_monthly_period_id
        if ($instructorWorkshop->isDirty('workshop_id') && !empty($instructorWorkshop->workshop_id)) {
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }

            // NUEVA FUNCIONALIDAD: Al cambiar workshop, sincronizar todos los campos
            $this->syncAllFieldsFromWorkshop($instructorWorkshop, $workshop);
        }
    }

    /**
     * Sincronizar solo campos vacíos desde el Workshop (para crear)
     */
    private function syncEmptyFieldsFromWorkshop(InstructorWorkshop $instructorWorkshop): void
    {
        if (empty($instructorWorkshop->workshop_id)) {
            return;
        }

        $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);
        if (!$workshop) {
            return;
        }

        if (is_null($instructorWorkshop->day_of_week)) {
            $instructorWorkshop->day_of_week = $workshop->day_of_week ?? ['Lunes'];
        }

        if (is_null($instructorWorkshop->start_time)) {
            $instructorWorkshop->start_time = $workshop->start_time;
        }

        if (is_null($instructorWorkshop->end_time) && $workshop->start_time && $workshop->duration) {
            try {
                $startTime = \Carbon\Carbon::parse($workshop->start_time);
                $instructorWorkshop->end_time = $startTime->addMinutes($workshop->duration)->format('H:i:s');
            } catch (\Exception $e) {
                $instructorWorkshop->end_time = $workshop->start_time;
            }
        }

        if (is_null($instructorWorkshop->max_capacity)) {
            $instructorWorkshop->max_capacity = $workshop->capacity;
        }

        if (is_null($instructorWorkshop->place)) {
            $instructorWorkshop->place = $workshop->place;
        }
    }

    /**
     * Sincronizar todos los campos desde el Workshop (cuando cambia workshop_id)
     */
    private function syncAllFieldsFromWorkshop(InstructorWorkshop $instructorWorkshop, $workshop): void
    {
        if (!$workshop) {
            return;
        }

        // Forzar actualización de todos los campos
        $instructorWorkshop->day_of_week = $workshop->day_of_week ?? ['Lunes'];
        $instructorWorkshop->start_time = $workshop->start_time;
        $instructorWorkshop->max_capacity = $workshop->capacity;
        $instructorWorkshop->place = $workshop->place;

        if ($workshop->start_time && $workshop->duration) {
            try {
                $startTime = \Carbon\Carbon::parse($workshop->start_time);
                $instructorWorkshop->end_time = $startTime->addMinutes($workshop->duration)->format('H:i:s');
            } catch (\Exception $e) {
                $instructorWorkshop->end_time = $workshop->start_time;
            }
        }
    }
}
