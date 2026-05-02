# Queries útiles — Laravel Tinker

Ejecutar tinker: `php artisan tinker`

---

## Períodos Mensuales

```php
// Ver todos los períodos
\App\Models\MonthlyPeriod::orderBy('year')->orderBy('month')->get(['id','year','month','is_active','workshops_replicated_at','enrollments_replicated_at']);

// Período actual
$current = \App\Models\MonthlyPeriod::where('year', now()->year)->where('month', now()->month)->first();

// Período siguiente
$next = \App\Models\MonthlyPeriod::where('year', now()->addMonth()->year)->where('month', now()->addMonth()->month)->first();

// ¿Ya se replicaron talleres para el siguiente período?
$next?->workshops_replicated_at;

// ¿Ya se replicaron inscripciones para el siguiente período?
$next?->enrollments_replicated_at;

// Resetear bandera de replicación de talleres (para volver a correr el job)
$next->update(['workshops_replicated_at' => null]);

// Resetear bandera de replicación de inscripciones
$next->update(['enrollments_replicated_at' => null]);
```

---

## Talleres y Clases (WorkshopClass)

```php
// Contar workshop_classes del período actual
\App\Models\WorkshopClass::where('monthly_period_id', $current->id)->count();

// Contar workshop_classes del período siguiente
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->count();

// Ver clases por status del período siguiente
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->get();

// Clases canceladas del período actual
\App\Models\WorkshopClass::where('monthly_period_id', $current->id)
    ->where('status', 'cancelled')
    ->with('workshop')
    ->get(['id','workshop_id','class_date','status']);
```

---

## Inscripciones (EnrollmentBatch / StudentEnrollment)

```php
// Batches completados del período actual (los que replica el job)
\App\Models\EnrollmentBatch::where('payment_status', 'completed')
    ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $current->id))
    ->count();

// Batches del período siguiente ya creados
\App\Models\EnrollmentBatch::whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $next->id))
    ->count();

// Ver inscripciones creadas para el período siguiente
\App\Models\StudentEnrollment::where('monthly_period_id', $next->id)
    ->with('student:id,first_names,last_names')
    ->get(['id','student_id','instructor_workshop_id','enrollment_type','number_of_classes','payment_status']);

// Contar inscripciones por status para el período siguiente
\App\Models\StudentEnrollment::where('monthly_period_id', $next->id)
    ->selectRaw('payment_status, COUNT(*) as total')
    ->groupBy('payment_status')
    ->get();
```

---

## Bug Semana Santa — EnrollmentClass apuntando a clases canceladas

```php
// Contar enrollment_classes que apuntan a una workshop_class cancelada
\App\Models\EnrollmentClass::whereHas('workshopClass', fn($q) => $q->where('status', 'cancelled'))->count();

// Ver el detalle: estudiante, fecha y período afectado
\App\Models\EnrollmentClass::whereHas('workshopClass', fn($q) => $q->where('status', 'cancelled'))
    ->with(['workshopClass', 'studentEnrollment.student'])
    ->get()
    ->map(fn($ec) => [
        'student'    => optional($ec->studentEnrollment->student)->last_names,
        'class_date' => $ec->workshopClass->class_date,
        'workshop_id'=> $ec->workshopClass->workshop_id,
        'period_id'  => optional($ec->studentEnrollment)->monthly_period_id,
    ]);

// Eliminar los enrollment_classes con clases canceladas (usar con cuidado)
\App\Models\EnrollmentClass::whereHas('workshopClass', fn($q) => $q->where('status', 'cancelled'))->delete();
```

---

## Configuración del Sistema (SystemSettings)

```php
// Ver todas las configuraciones
\App\Models\SystemSetting::all(['key','value']);

// Configuración de replicación de talleres
\App\Models\SystemSetting::whereIn('key', [
    'auto_generate_enabled',
    'auto_generate_day',
    'auto_generate_time',
])->pluck('value', 'key');

// Configuración de replicación de inscripciones
\App\Models\SystemSetting::whereIn('key', [
    'auto_replicate_enrollments_enabled',
    'auto_replicate_enrollments_day',
    'auto_replicate_enrollments_time',
])->pluck('value', 'key');

// Configuración de auto-cancelación de inscripciones pendientes
\App\Models\SystemSetting::whereIn('key', [
    'auto_cancel_enabled',
    'auto_cancel_day',
    'auto_cancel_time',
])->pluck('value', 'key');
```

---

## Replicación — Verificación previa al job

```php
// Prerequisito 1: ¿existen workshop_classes para el siguiente período?
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->count();
// Si es 0 → correr primero: php artisan workshops:auto-replicate --force

// Prerequisito 2: ¿hay batches completados en el período actual?
\App\Models\EnrollmentBatch::where('payment_status', 'completed')
    ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $current->id)->whereNull('cancelled_at'))
    ->count();

// Prerequisito 3: ¿el período siguiente ya fue replicado?
$next?->enrollments_replicated_at;
// Si tiene fecha → resetear: $next->update(['enrollments_replicated_at' => null])
```

---

## Replicación — Revertir una prueba

```php
// IDs de batches de prueba creados para el siguiente período (pending = creados por el job)
$batchIds = \App\Models\EnrollmentBatch::where('payment_status', 'pending')
    ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $next->id))
    ->pluck('id');

// Eliminar en orden: enrollment_classes → student_enrollments → enrollment_batches
\App\Models\EnrollmentClass::whereHas('studentEnrollment', fn($q) => $q->whereIn('enrollment_batch_id', $batchIds))->delete();
\App\Models\StudentEnrollment::whereIn('enrollment_batch_id', $batchIds)->delete();
\App\Models\EnrollmentBatch::whereIn('id', $batchIds)->delete();

// Resetear la bandera para poder correr de nuevo
$next->update(['enrollments_replicated_at' => null]);
```

---

## Estudiantes

```php
// Estudiantes con mantenimiento al día
\App\Models\Student::with('maintenancePeriod')
    ->get()
    ->filter(fn($s) => $s->isMaintenanceCurrent())
    ->count();

// Estudiantes exonerados de mantenimiento
\App\Models\Student::whereIn('category_partner', ['Vitalicios', 'Hijo de Fundador', 'Transitorio Mayor de 75'])->count();

// Estudiantes PRE PAMA (pagan tarifa diferencial)
\App\Models\Student::whereIn('category_partner', ['PRE PAMA 50+', 'PRE PAMA 55+'])
    ->get(['id','first_names','last_names','category_partner']);
```

---

## Caso de uso 1 — Probar replicación de talleres (workshops:auto-replicate)

```php
// PASO 1: Obtener períodos
$current = \App\Models\MonthlyPeriod::where('year', now()->year)->where('month', now()->month)->first();
$next    = \App\Models\MonthlyPeriod::where('year', now()->addMonth()->year)->where('month', now()->addMonth()->month)->first();

// PASO 2: Verificar estado actual del siguiente período
\App\Models\Workshop::where('monthly_period_id', $next->id)->count();       // talleres existentes
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->count();  // clases existentes
$next->workshops_replicated_at;                                              // ¿ya corrió el job?

// PASO 3: Limpiar para prueba limpia (si ya hay datos)
\App\Models\EnrollmentClass::whereHas('workshopClass', fn($q) =>
    $q->where('monthly_period_id', $next->id)
)->delete();
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->delete();
\App\Models\Workshop::where('monthly_period_id', $next->id)->delete();
$next->update(['workshops_replicated_at' => null]);
```

```bash
# PASO 4: Correr el job
php artisan workshops:auto-replicate --force
```

```php
// PASO 5: Verificar resultado
\App\Models\Workshop::where('monthly_period_id', $next->id)->count();      // debe ser > 0
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->count(); // debe ser > 0

// Ver clases por status (todas deben ser 'scheduled', ninguna 'cancelled')
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->get();

// Ver talleres creados con sus clases
\App\Models\Workshop::where('monthly_period_id', $next->id)
    ->withCount('workshopClasses')
    ->get(['id','name','day_of_week','number_of_classes'])
    ->map(fn($w) => ['taller' => $w->name, 'clases' => $w->workshop_classes_count]);
```

---

## Caso de uso 2 — Probar replicación de inscripciones (enrollments:auto-generate)

```php
// PASO 1: Verificar prerequisitos
$current = \App\Models\MonthlyPeriod::where('year', now()->year)->where('month', now()->month)->first();
$next    = \App\Models\MonthlyPeriod::where('year', now()->addMonth()->year)->where('month', now()->addMonth()->month)->first();

// ¿Existen workshop_classes scheduled para el siguiente período? (prerequisito del job)
\App\Models\WorkshopClass::where('monthly_period_id', $next->id)->where('status', 'scheduled')->count();
// Si es 0 → correr primero: php artisan workshops:auto-replicate --force

// ¿Cuántos batches completed hay en el período actual para replicar?
\App\Models\EnrollmentBatch::where('payment_status', 'completed')
    ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $current->id)->whereNull('cancelled_at'))
    ->count();

// ¿El job ya corrió para el siguiente período?
$next->enrollments_replicated_at;
// Si tiene fecha → resetear: $next->update(['enrollments_replicated_at' => null])

// PASO 2: Limpiar inscripciones del siguiente período si ya existen (prueba limpia)
$batchIds = \App\Models\EnrollmentBatch::where('payment_status', 'pending')
    ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $next->id))
    ->pluck('id');

\App\Models\EnrollmentClass::whereHas('studentEnrollment',
    fn($q) => $q->whereIn('enrollment_batch_id', $batchIds)
)->delete();
\App\Models\StudentEnrollment::whereIn('enrollment_batch_id', $batchIds)->delete();
\App\Models\EnrollmentBatch::whereIn('id', $batchIds)->delete();
$next->update(['enrollments_replicated_at' => null]);
```

```bash
# PASO 3: Correr el job
php artisan enrollments:auto-generate --force
```

```php
// PASO 4: Verificar resultado
// Batches creados para el siguiente período
\App\Models\EnrollmentBatch::whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $next->id))
    ->count();

// Inscripciones creadas
\App\Models\StudentEnrollment::where('monthly_period_id', $next->id)->count();

// EnrollmentClasses creadas
\App\Models\EnrollmentClass::whereHas('studentEnrollment',
    fn($q) => $q->where('monthly_period_id', $next->id)
)->count();

// BUG CHECK: ¿alguna clase apunta a una workshop_class cancelada? Debe ser 0
\App\Models\EnrollmentClass::whereHas('workshopClass', fn($q) => $q->where('status', 'cancelled'))
    ->whereHas('studentEnrollment', fn($q) => $q->where('monthly_period_id', $next->id))
    ->count();

// Ver resumen por estudiante
\App\Models\StudentEnrollment::where('monthly_period_id', $next->id)
    ->with('student:id,first_names,last_names')
    ->get()
    ->groupBy('student_id')
    ->map(fn($enrollments) => [
        'estudiante'   => $enrollments->first()->student->last_names.' '.$enrollments->first()->student->first_names,
        'talleres'     => $enrollments->count(),
        'clases_total' => $enrollments->sum('number_of_classes'),
    ]);
```

---

## Comandos Artisan relacionados

```bash
# Replicar talleres al siguiente mes (omite validación de día/hora)
php artisan workshops:auto-replicate --force

# Replicar inscripciones al siguiente mes
php artisan enrollments:auto-generate --force

# Auto-cancelar inscripciones pendientes
php artisan enrollments:auto-cancel --force

# Ver todas las tareas programadas
php artisan schedule:list

# Ver logs en tiempo real
php artisan pail
```


