<?php

namespace App\Services;

use App\Models\Workshop;
use App\Models\MonthlyPeriod;
use App\Models\WorkshopClass;
use Carbon\Carbon;

class WorkshopReplicationService
{
    /**
     * Replicar talleres de un período al siguiente y generar sus clases.
     *
     * @return array{workshops:int, classes:int}
     */
    public function replicateFromPeriodToNext(MonthlyPeriod $current, MonthlyPeriod $next): array
    {
        $createdWorkshops = 0;
        $createdClasses = 0;

        $workshops = Workshop::where('monthly_period_id', $current->id)->get();

        foreach ($workshops as $workshop) {
            $exists = Workshop::where('monthly_period_id', $next->id)
                ->where('name', $workshop->name)
                ->where('instructor_id', $workshop->instructor_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $newWorkshop = $workshop->replicate();
            $newWorkshop->monthly_period_id = $next->id;
            $newWorkshop->save();

            $createdWorkshops++;

            if ($next->auto_generate_classes && ! $newWorkshop->workshopClasses()->exists()) {
                $createdClasses += $this->generateClassesForWorkshopAndPeriod($newWorkshop, $next);
            }
        }

        return [
            'workshops' => $createdWorkshops,
            'classes' => $createdClasses,
        ];
    }

    /**
     * Generar clases del taller dentro del período usando day_of_week y number_of_classes.
     */
    public function generateClassesForWorkshopAndPeriod(Workshop $workshop, MonthlyPeriod $period): int
    {
        $days = $workshop->day_of_week;
        if (is_string($days)) {
            $days = [$days];
        }
        if (is_numeric($days)) {
            $days = [$this->mapNumericDayToSpanish((int) $days)];
        }
        if (! is_array($days) || empty($days)) {
            $days = ['Lunes'];
        }

        $dayMap = [
            'Domingo' => 0,
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
        ];

        $targetWeekdays = [];
        foreach ($days as $d) {
            $key = is_numeric($d) ? (int) $d : $d;
            if (is_int($key)) {
                $targetWeekdays[] = $key;
            } elseif (isset($dayMap[$key])) {
                $targetWeekdays[] = $dayMap[$key];
            }
        }
        $targetWeekdays = array_unique($targetWeekdays);
        if (empty($targetWeekdays)) {
            $targetWeekdays = [1];
        }

        $startDate = Carbon::parse($period->start_date)->startOfDay();
        $endDate = Carbon::parse($period->end_date)->endOfDay();

        $classesToCreate = (int) ($workshop->number_of_classes ?? 4);
        $createdCount = 0;

        $dates = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            if (in_array($cursor->dayOfWeek, $targetWeekdays, true)) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        $dates = array_slice($dates, 0, $classesToCreate);

        $startTime = Carbon::parse($workshop->start_time);
        $endTime = $startTime->copy()->addMinutes((int) ($workshop->duration ?? 60));

        foreach ($dates as $date) {
            WorkshopClass::create([
                'workshop_id' => $workshop->id,
                'monthly_period_id' => $period->id,
                'class_date' => $date->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'max_capacity' => (int) ($workshop->capacity ?? 0),
                'status' => 'scheduled',
                'notes' => null,
            ]);
            $createdCount++;
        }

        return $createdCount;
    }

    private function mapNumericDayToSpanish(int $n): string
    {
        return ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'][$n] ?? 'Lunes';
    }
}