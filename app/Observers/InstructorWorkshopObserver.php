<?php

namespace App\Observers;

use App\Models\InstructorWorkshop;

class InstructorWorkshopObserver
{
    /**
     * Handle the InstructorWorkshop "creating" event.
     */
    public function creating(InstructorWorkshop $instructorWorkshop): void
    {
        // Solo establecer initial_monthly_period_id si no está ya definido
        if (empty($instructorWorkshop->initial_monthly_period_id) && !empty($instructorWorkshop->workshop_id)) {
            // Obtener el monthly_period_id del workshop asociado
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }
        }
    }

    /**
     * Handle the InstructorWorkshop "updating" event.
     */
    public function updating(InstructorWorkshop $instructorWorkshop): void
    {
        // Si cambia el workshop_id, actualizar también el initial_monthly_period_id
        if ($instructorWorkshop->isDirty('workshop_id') && !empty($instructorWorkshop->workshop_id)) {
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }
        }
    }
}
