<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/* Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote'); */

// Paso 1 — Día 14 a las 23:59: cancela batches pending del período actual (solo períodos actuales o pasados)
// Paso 2 — Día 15 a las 23:59: clona talleres y genera workshop_classes del siguiente período
// Paso 3 — Día 20 a las 23:59: replica inscripciones a las clases scheduled del siguiente período
//           (el admin tiene los días 20-21 para marcar feriados como cancelled antes de que corra este job)
//
// Nota: los tres corren everyMinute() porque el día/hora exacto se configura dinámicamente
// desde el panel en Configuración del Sistema (SystemSettings). Cada comando verifica
// internamente si es el día y hora correctos y sale inmediatamente si no corresponde.

Schedule::command('enrollments:auto-cancel')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('workshops:auto-replicate')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('enrollments:auto-generate')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Replica feriados recurrentes al año siguiente cada 1 de diciembre
Schedule::command('holidays:replicate-recurring')
    ->yearlyOn(12, 1, '00:00')
    ->withoutOverlapping()
    ->runInBackground();
