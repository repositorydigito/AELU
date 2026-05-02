<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\MonthlyPeriod;
use App\Models\Workshop;
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

        // Feriados exactos del período (no recurrentes)
        $exactHolidays = Holiday::query()
            ->where('affects_classes', true)
            ->where('is_recurring', false)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [$d->toDateString() => true])
            ->all();

        // Feriados recurrentes (mismo mes/día todos los años)
        $recurringHolidays = Holiday::query()
            ->where('affects_classes', true)
            ->where('is_recurring', true)
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [$d->format('m-d') => true])
            ->all();

        $createdCount = 0;

        // Encontrar todas las fechas del período que coinciden con los días configurados
        $dates = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            if (in_array($cursor->dayOfWeek, $targetWeekdays, true)) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        $startTime = Carbon::parse($workshop->start_time);
        $endTime = $startTime->copy()->addMinutes((int) ($workshop->duration ?? 60));

        foreach ($dates as $date) {
            $isHoliday = isset($exactHolidays[$date->toDateString()])
                || isset($recurringHolidays[$date->format('m-d')]);

            if ($isHoliday) {
                continue;
            }

            WorkshopClass::create([
                'workshop_id'       => $workshop->id,
                'monthly_period_id' => $period->id,
                'class_date'        => $date->toDateString(),
                'start_time'        => $startTime->format('H:i:s'),
                'end_time'          => $endTime->format('H:i:s'),
                'max_capacity'      => (int) ($workshop->capacity ?? 0),
                'status'            => 'scheduled',
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    private function mapNumericDayToSpanish(int $n): string
    {
        return ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$n] ?? 'Lunes';
    }
}
