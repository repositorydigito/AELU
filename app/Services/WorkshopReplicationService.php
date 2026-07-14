<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\MonthlyPeriod;
use App\Models\Workshop;
use App\Models\WorkshopClass;
use App\Models\WorkshopTemplate;
use Carbon\Carbon;

class WorkshopReplicationService
{
    /**
     * Replicar talleres desde plantillas activas al siguiente período.
     *
     * @return array{workshops:int, classes:int, skipped:int}
     */
    public function replicateFromTemplates(MonthlyPeriod $next): array
    {
        $createdWorkshops = 0;
        $createdClasses   = 0;
        $skipped          = 0;
        $warnings         = [];

        $templates = WorkshopTemplate::where('is_active', true)->get();

        $existingNames = Workshop::where('monthly_period_id', $next->id)
            ->pluck('name')
            ->map(fn ($n) => strtolower($n))
            ->toArray();

        foreach ($templates as $template) {
            if (in_array(strtolower($template->name), $existingNames)) {
                $skipped++;
                continue;
            }

            $workshop = Workshop::create([
                'name'                         => $template->name,
                'description'                  => $template->description,
                'instructor_id'                => $template->instructor_id,
                'delegate_user_id'             => $template->delegate_user_id,
                'standard_monthly_fee'         => $template->standard_monthly_fee,
                'pricing_surcharge_percentage' => $template->pricing_surcharge_percentage,
                'day_of_week'                  => $template->day_of_week,
                'start_time'                   => $template->start_time,
                'duration'                     => $template->duration,
                'capacity'                     => $template->capacity,
                'number_of_classes'            => $template->number_of_classes,
                'place'                        => $template->place,
                'modality'                     => $template->modality,
                'additional_comments'          => $template->additional_comments,
                'monthly_period_id'            => $next->id,
                'workshop_template_id'         => $template->id,
            ]);

            $createdWorkshops++;

            if ($next->auto_generate_classes) {
                // D.3: si el taller queda mal configurado (sin día u sin cupo), no se debe
                // adivinar en silencio. Se aísla el error por taller (no aborta a los demás)
                // y se reporta como advertencia — el taller queda creado pero sin clases,
                // pendiente de que el admin corrija y genere las clases a mano.
                try {
                    $createdClasses += $this->generateClassesForWorkshopAndPeriod($workshop, $next);

                    // Actualizar number_of_classes con clases reales generadas (feriados excluidos)
                    $actualClasses = $workshop->workshopClasses()->where('status', 'scheduled')->count();
                    if ($actualClasses > 0 && $actualClasses !== $workshop->number_of_classes) {
                        $workshop->update(['number_of_classes' => $actualClasses]);
                    }
                } catch (\Exception $e) {
                    $warnings[] = $e->getMessage();
                }
            }
        }

        return [
            'workshops' => $createdWorkshops,
            'classes'   => $createdClasses,
            'skipped'   => $skipped,
            'warnings'  => $warnings,
        ];
    }

    /**
     * Generar clases del taller dentro del período usando day_of_week y number_of_classes.
     */
    public function generateClassesForWorkshopAndPeriod(Workshop $workshop, MonthlyPeriod $period): int
    {
        // D.3: no defaultear en silencio si falta configuración. Antes esto generaba
        // clases el "Lunes" (day_of_week vacío) o con cupo 0 (capacity vacío), corrompiendo
        // datos sin ningún aviso — mejor fallar explícito para que el admin lo corrija.
        if (empty($workshop->day_of_week)) {
            throw new \Exception("Taller '{$workshop->name}' (id {$workshop->id}): no tiene día(s) de clase configurado (day_of_week vacío), no se generaron clases.");
        }

        if (empty($workshop->capacity) || $workshop->capacity <= 0) {
            throw new \Exception("Taller '{$workshop->name}' (id {$workshop->id}): no tiene capacidad configurada (capacity vacío o 0), no se generaron clases.");
        }

        $days = $workshop->day_of_week;
        if (is_string($days)) {
            $days = [$days];
        }
        if (is_numeric($days)) {
            $days = [$this->mapNumericDayToSpanish((int) $days)];
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
            throw new \Exception("Taller '{$workshop->name}' (id {$workshop->id}): day_of_week tiene valores no reconocidos (".json_encode($workshop->day_of_week).'), no se generaron clases.');
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
                'max_capacity'      => (int) $workshop->capacity,
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
