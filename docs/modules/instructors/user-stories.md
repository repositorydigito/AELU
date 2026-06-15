# Historias de Usuario — Módulo de Profesores

> Historias específicas del módulo de profesores. Para el índice general ver `docs/user-stories.md`.

---

## HU-P01: Asignar Talleres a un Instructor

**Rol:** Administrador
**Acción:** Asignar uno o más talleres a un instructor durante su creación o edición
**Beneficio:** Registrar qué talleres dicta cada instructor y bajo qué modalidad de pago, para que el sistema pueda calcular pagos mensuales correctamente

### Descripción

Como administrador, quiero configurar los talleres que dicta un instructor desde el paso 3 del wizard ("Talleres y Modalidad de Pago"), pudiendo agregar múltiples asignaciones, para que el sistema tenga la información necesaria para generar los pagos mensuales automáticamente.

### Flujo

```
Wizard Instructor
  └─ Paso 1: Datos Personales
  └─ Paso 2: Ficha Médica
  └─ Paso 3: Talleres y Modalidad de Pago  ← esta historia
       └─ Repeater "Talleres"
            ├─ [Fila 1] workshop_id + payment_type + (% o tarifa/hora)
            ├─ [Fila 2] ...
            └─ Botón "Agregar Taller"
  └─ Paso 4: Declaración Jurada y Resumen
```

### Criterios de aceptación

- [ ] El paso 3 muestra un repeater vacío por defecto; se puede agregar 1 o más filas
- [ ] Cada fila permite seleccionar un taller (`Workshop`) con búsqueda; el label muestra: `{nombre} - {días} {hora_inicio}-{hora_fin} - {mes} {año} - {modalidad}`
- [ ] Al seleccionar un taller, el campo de lectura "Horario" se actualiza reactivamente mostrando días y horas
- [ ] Cada fila tiene su propia configuración de modalidad de pago (independiente de las otras filas)
- [ ] Se puede eliminar cualquier fila del repeater con el ícono de papelera
- [ ] Guardar el paso persiste los registros en la tabla `instructor_workshops`, uno por fila
- [ ] Si se edita un instructor existente, las filas del repeater se pre-cargan con sus `InstructorWorkshop` actuales

### Conexión con el código

- Recurso: `InstructorResource.php` → Wizard Step 3 (líneas ~322–458)
- Modelo junction: `InstructorWorkshop` (`instructor_id`, `workshop_id`, `payment_type`, ...)
- Relación: `Instructor::instructorWorkshops()` → `hasMany(InstructorWorkshop::class)`
- Relación: `InstructorWorkshop::workshop()` → `belongsTo(Workshop::class)`

### Campos del formulario

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| `workshop_id` | Select (searchable, reactive) | Sí | Muestra nombre + horario + período + modalidad |
| `schedule_display` | Placeholder (solo lectura) | — | Se actualiza al cambiar `workshop_id` |
| `payment_type` | Radio | Sí | `volunteer` = Voluntario, `hourly` = Por Horas |
| `custom_volunteer_percentage` | TextInput numérico | Si `volunteer` | 0–100, sufijo `%` |
| `hourly_rate` | TextInput numérico | Si `hourly` | Prefijo `S/` |

---

## HU-P02: Configurar Modalidad Voluntario

**Rol:** Administrador
**Acción:** Asignar porcentaje de pago voluntario personalizado a un taller de instructor
**Beneficio:** Permitir que el sistema calcule el pago mensual como `ingresos_taller × porcentaje`

### Descripción

Como administrador, quiero poder configurar el porcentaje de pago voluntario para cada taller asignado a un instructor, pudiendo sobreescribir el porcentaje mensual por defecto (`MonthlyInstructorRate`), para que los casos especiales queden registrados sin afectar al resto.

### Reglas de negocio

- `custom_volunteer_percentage` almacenado en `instructor_workshops` como decimal (ej. `30.00` = 30%)
- Si es `null`, `InstructorWorkshop::getEffectiveVolunteerPercentage()` usa el `MonthlyInstructorRate` vigente del período
- El campo se muestra **solo** cuando `payment_type = 'volunteer'`
- Rango válido: 0–100

### Criterios de aceptación

- [ ] Al seleccionar "Voluntario", aparece el campo "Porcentaje de Pago (%)"
- [ ] Al seleccionar "Por Horas", el campo de porcentaje se oculta (y su valor no se persiste)
- [ ] Ingresar un valor fuera de 0–100 muestra error de validación
- [ ] El valor se guarda en `instructor_workshops.custom_volunteer_percentage`
- [ ] Al generar el pago mensual, si `custom_volunteer_percentage` es distinto de null, ese valor tiene precedencia sobre `MonthlyInstructorRate.volunteer_percentage`

### Conexión con el código

- Campo: `custom_volunteer_percentage` en `InstructorWorkshop`
- Lógica de resolución: `InstructorWorkshop::getEffectiveVolunteerPercentage(?MonthlyInstructorRate)`
- Consumido por: `InstructorPaymentService`

---

## HU-P03: Configurar Modalidad Por Horas

**Rol:** Administrador
**Acción:** Asignar tarifa por hora a un taller de instructor con pago horario
**Beneficio:** Permitir que el sistema calcule el pago mensual como `horas_trabajadas × tarifa_hora`

### Descripción

Como administrador, quiero configurar la tarifa por hora para instructores que cobran de forma horaria, para que el cálculo de pago mensual sea correcto y no dependa de los ingresos del taller.

### Reglas de negocio

- `hourly_rate` almacenado en `instructor_workshops` como decimal en Soles (S/)
- El campo se muestra **solo** cuando `payment_type = 'hourly'`
- El cálculo final en `InstructorPaymentService`: `total_hours × applied_hourly_rate`
- `total_hours` se registra en `InstructorPayment` al generar el pago mensual

### Criterios de aceptación

- [ ] Al seleccionar "Por Horas", aparece el campo "Honorario por Hora" con prefijo `S/`
- [ ] Al seleccionar "Voluntario", el campo de tarifa se oculta (y su valor no se persiste)
- [ ] Ingresar valor negativo o no numérico muestra error de validación
- [ ] El valor se guarda en `instructor_workshops.hourly_rate`
- [ ] Al generar el pago mensual, `InstructorPayment.applied_hourly_rate` refleja el valor configurado aquí

### Conexión con el código

- Campo: `hourly_rate` en `InstructorWorkshop`
- Método: `InstructorWorkshop::getEstimatedPayPerClass()` → `hourly_rate × duration_hours`
- Consumido por: `InstructorPaymentService`

---

## Modelo de Datos — Resumen

```
instructors
  └─ id, last_names, first_names, document_type, ...

instructor_workshops                         ← registro por asignación
  └─ id
  └─ instructor_id       → instructors.id
  └─ workshop_id         → workshops.id
  └─ payment_type        ENUM(volunteer, hourly)
  └─ custom_volunteer_percentage  DECIMAL nullable   (% override)
  └─ hourly_rate                  DECIMAL nullable   (S/hora)
  └─ duration_hours               DECIMAL nullable   (en schema, no expuesto en form)
  └─ is_active           BOOLEAN

workshops
  └─ id, name, standard_monthly_fee, day_of_week, start_time, end_time
  └─ monthly_period_id   → monthly_periods.id

monthly_instructor_rates                     ← porcentaje mensual por defecto
  └─ monthly_period_id, volunteer_percentage

instructor_payments                          ← generado mensualmente
  └─ instructor_id, instructor_workshop_id, monthly_period_id
  └─ payment_type, calculated_amount
  └─ applied_volunteer_percentage / applied_hourly_rate / total_hours
```

---

## Notas Técnicas

- `duration_hours` existe en DB y en el modelo pero el campo del form está comentado (`InstructorResource.php` líneas ~433–440). No se usa actualmente.
- Campos del schema no expuestos en el form: `initial_monthly_period_id`, `day_of_week`, `start_time`, `end_time`, `max_capacity`, `place`, `is_active`
- El select de talleres incluye el período en el label para distinguir talleres con el mismo nombre en distintos meses
