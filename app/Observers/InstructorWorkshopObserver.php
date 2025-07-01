<?php

namespace App\Observers;

use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use App\Models\MonthlyPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InstructorWorkshopObserver
{  
    public function created(InstructorWorkshop $instructorWorkshop): void
    {
        if ($instructorWorkshop->initial_monthly_period_id) {
            $this->generateClassesForPeriod($instructorWorkshop, $instructorWorkshop->initialMonthlyPeriod);
        }
    }
    public function updated(InstructorWorkshop $instructorWorkshop): void
    {
        // Si el initial_monthly_period_id ha cambiado y tiene un valor, regeneramos/generamos desde ahí
        if ($instructorWorkshop->isDirty('initial_monthly_period_id') && $instructorWorkshop->initial_monthly_period_id) {
            // Eliminar clases generadas previamente *sólo si no tienen inscripciones*
            // Esta parte requiere lógica de negocio: ¿se pueden borrar clases ya creadas?
            // Por simplicidad, no borramos automáticamente. Asumimos que el admin sabe lo que hace.
            // Considera añadir un modal de confirmación en Filament si esto es crítico.

            $this->generateClassesForPeriod($instructorWorkshop, $instructorWorkshop->initialMonthlyPeriod);
        }
        // Puedes añadir más lógica aquí si cambian start_time, end_time, day_of_week etc.
        // Pero eso es más complejo porque implica modificar clases ya existentes.
        // Para empezar, nos centramos en la generación inicial o en el cambio del periodo inicial.
    }

    protected function generateClassesForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): void
    {
        // Asegurarse de que el InstructorWorkshop esté activo para generar clases
        if (!$instructorWorkshop->is_active) {
            Log::info('No se generaron clases para InstructorWorkshop ID ' . $instructorWorkshop->id . ' porque no está activo.');
            return;
        }

        // Asegurarse de que el MonthlyPeriod permita la auto-generación de clases
        if (!$monthlyPeriod->auto_generate_classes) {
            Log::info('No se generaron clases para InstructorWorkshop ID ' . $instructorWorkshop->id . ' en el periodo ' . $monthlyPeriod->id . ' porque auto_generate_classes está deshabilitado.');
            return;
        }


        $startDate = Carbon::parse($monthlyPeriod->start_date);
        $endDate = Carbon::parse($monthlyPeriod->end_date);
        $dayOfWeek = $instructorWorkshop->day_of_week;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek === $dayOfWeek) {
                // Previene duplicados
                $classExists = WorkshopClass::where('instructor_workshop_id', $instructorWorkshop->id)
                                            ->where('monthly_period_id', $monthlyPeriod->id)
                                            ->where('class_date', $currentDate->toDateString())
                                            ->exists();

                if (!$classExists) {
                    WorkshopClass::create([
                        'instructor_workshop_id' => $instructorWorkshop->id,
                        'monthly_period_id' => $monthlyPeriod->id,
                        'class_date' => $currentDate->toDateString(),
                        'start_time' => $instructorWorkshop->start_time,
                        'end_time' => $instructorWorkshop->end_time,
                        'status' => 'scheduled',
                        'max_capacity' => $instructorWorkshop->max_capacity,
                    ]);
                }
            }
            $currentDate->addDay();
        }
    }
    
}
