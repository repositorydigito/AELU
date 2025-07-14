<?php

namespace App\Observers;

use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use App\Models\MonthlyPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InstructorWorkshopObserver
{  
    public function creating(InstructorWorkshop $instructorWorkshop): void
    {
        // Calcular duration_hours antes de guardar
        $this->calculateDurationHours($instructorWorkshop);
    }

    public function updating(InstructorWorkshop $instructorWorkshop): void
    {
        // Recalcular duration_hours si cambian las horas
        if ($instructorWorkshop->isDirty(['start_time', 'end_time'])) {
            $this->calculateDurationHours($instructorWorkshop);
        }
    }

    public function created(InstructorWorkshop $instructorWorkshop): void
    {
        if ($instructorWorkshop->initial_monthly_period_id) {
            $this->generateClassesForPeriod($instructorWorkshop, $instructorWorkshop->initialMonthlyPeriod);
        }
    }

    public function updated(InstructorWorkshop $instructorWorkshop): void
    {
        if ($instructorWorkshop->isDirty('initial_monthly_period_id') && $instructorWorkshop->initial_monthly_period_id) {
            $this->generateClassesForPeriod($instructorWorkshop, $instructorWorkshop->initialMonthlyPeriod);
        }
    }

    protected function calculateDurationHours(InstructorWorkshop $instructorWorkshop): void
    {
        if ($instructorWorkshop->start_time && $instructorWorkshop->end_time) {
            try {
                $start = Carbon::parse($instructorWorkshop->start_time);
                $end = Carbon::parse($instructorWorkshop->end_time);
                
                if ($end->greaterThan($start)) {
                    $durationMinutes = $start->diffInMinutes($end);
                    $instructorWorkshop->duration_hours = round($durationMinutes / 60, 2);
                }
            } catch (\Exception $e) {
                Log::warning('Error calculando duration_hours: ' . $e->getMessage());
            }
        }
    }

    protected function generateClassesForPeriod(InstructorWorkshop $instructorWorkshop, MonthlyPeriod $monthlyPeriod): void
    {
        if (!$instructorWorkshop->is_active) {
            Log::info('No se generaron clases para InstructorWorkshop ID ' . $instructorWorkshop->id . ' porque no está activo.');
            return;
        }

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