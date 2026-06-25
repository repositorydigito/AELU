# Flujo: Replicación Automática de Inscripciones

> Proceso que replica las inscripciones del mes actual al siguiente período para estudiantes con lote `completed`.
> Comando: `enrollments:auto-generate` — actualmente deshabilitado en el scheduler; se ejecuta manualmente.

---

## Prerrequisito obligatorio

**La replicación de talleres debe correr ANTES que este proceso.**

```
WorkshopReplicationService (workshops:auto-replicate)
  └── genera workshop_classes para el período siguiente
      └── marca como cancelled las que caen en feriado (Holiday.affects_classes = true)

        ↓  El admin revisa y cancela manualmente suspensiones no registradas como feriado

EnrollmentReplicationService (enrollments:auto-generate)
  └── asigna estudiantes SOLO a clases con status = 'scheduled'
```

Si `EnrollmentReplicationService` corre antes, no encuentra `WorkshopClass` del período siguiente y falla al asignar clases.

---

## Flujo general

```mermaid
flowchart TD
    CMD([enrollments:auto-generate\n--force opcional])

    CHECK_ENABLED{auto_replicate_enrollments_enabled\n= true?}
    CHECK_DAY{¿Hoy es el día\nconfigurado?}
    CHECK_HOUR{¿Hora actual\n= hora configurada?}
    CHECK_PERIOD{¿Existe período\nactual en BD?}
    CREATE_NEXT[firstOrCreate\nperíodo siguiente]
    CHECK_REPLICATED{¿Ya se replicó\neste período?}

    QUERY[Cargar batches completed\ndel período actual\nen chunks de 50]

    subgraph POR_BATCH["Por cada EnrollmentBatch"]
        CHECK_EXISTS{¿Estudiante ya tiene\nbatch en período siguiente?}
        CHECK_MAINT{¿Estudiante al día\ncon mantenimiento?}
        CREATE_BATCH[Crear nuevo EnrollmentBatch\npayment_status = pending]

        subgraph POR_ENROLL["Por cada StudentEnrollment del batch"]
            FIND_IW[Buscar InstructorWorkshop\nequivalente en período siguiente]
            CHECK_CAP{¿Taller\ncon cupo?}
            CALC_PRICE[Calcular precio:\nworkshop.standard_monthly_fee\n× student.inscription_multiplier]
            CREATE_ENROLL[Crear StudentEnrollment]
            MAP_CLASSES[Mapear EnrollmentClasses\na clases scheduled del nuevo período]
        end

        CHECK_CREATED{¿Se creó al menos\n1 inscripción?}
        UPDATE_BATCH[Actualizar batch.total_amount]
        DELETE_BATCH[Eliminar batch vacío]
    end

    MARK[Marcar período siguiente\nenrollments_replicated_at = now]

    CMD --> CHECK_ENABLED
    CHECK_ENABLED -->|No y sin --force| END_OK([SUCCESS — deshabilitado])
    CHECK_ENABLED -->|Sí o --force| CHECK_DAY
    CHECK_DAY -->|Día distinto y sin --force| END_OK2([SUCCESS — esperar día])
    CHECK_DAY -->|OK o --force| CHECK_HOUR
    CHECK_HOUR -->|Hora distinta y sin --force| END_OK3([SUCCESS — esperar hora])
    CHECK_HOUR -->|OK o --force| CHECK_PERIOD
    CHECK_PERIOD -->|No existe| END_FAIL([FAILURE])
    CHECK_PERIOD -->|Existe| CREATE_NEXT
    CREATE_NEXT --> CHECK_REPLICATED
    CHECK_REPLICATED -->|Ya replicado y sin --force| END_OK4([SUCCESS — ya procesado])
    CHECK_REPLICATED -->|No o --force| QUERY

    QUERY --> POR_BATCH

    CHECK_EXISTS -->|Sí| SKIP1([Warning — omitir])
    CHECK_EXISTS -->|No| CHECK_MAINT
    CHECK_MAINT -->|No al día| SKIP2([Warning — omitir])
    CHECK_MAINT -->|Al día| CREATE_BATCH

    CREATE_BATCH --> POR_ENROLL

    FIND_IW -->|No encontrado| ERR1([Warning — omitir inscripción])
    FIND_IW -->|Encontrado| CHECK_CAP
    CHECK_CAP -->|Lleno| ERR2([Warning — omitir inscripción])
    CHECK_CAP -->|Con cupo| CALC_PRICE
    CALC_PRICE --> CREATE_ENROLL
    CREATE_ENROLL --> MAP_CLASSES

    CHECK_CREATED -->|Sí| UPDATE_BATCH
    CHECK_CREATED -->|No| DELETE_BATCH

    UPDATE_BATCH --> MARK
```

---

## Cálculo de precio

El precio de cada `StudentEnrollment` replicado se calcula con la tarifa del taller del **período siguiente** (no se copia la tarifa original):

```
finalPrice = workshop.standard_monthly_fee × student.inscription_multiplier
```

| Campo | Fuente |
|-------|--------|
| `workshop.standard_monthly_fee` | Taller del período siguiente (ya ajustado por el admin si hay feriados) |
| `student.inscription_multiplier` | Según `category_partner`: PRE PAMA 50+ → 2.0 · PRE PAMA 55+ → 1.5 · resto → 1.0 |

### Por qué tarifa flat (sin rama holiday)

Cuando `WorkshopReplicationService` replica un taller a un mes con feriados, actualiza `workshop.number_of_classes` al conteo real de clases `scheduled`. El admin luego ajusta `workshop.standard_monthly_fee` para reflejar el precio correcto del mes (ej: 3/4 del precio normal si hay un feriado que deja 3 clases de 4).

El servicio de replicación de inscripciones usa ese fee **ya ajustado** directamente. Aplicar además una fórmula de recargo causaría doble descuento.

**Bug corregido (2026-06-23, migración `fix_july_2026_enrollment_prices_double_discount`):**
Existía una rama "holiday" que aplicaba `standard_monthly_fee / templateClasses × surcharge × actualClasses` cuando `workshop.number_of_classes < workshopTemplate.number_of_classes`. Esto dividía un fee ya reducido por el admin entre las clases del template (4), resultando en un precio menor al correcto. Ejemplo: ACTIVIDAD FISICA → `18.75 / 4 × 1.2 × 3 = 16.88` en lugar de `18.75`.

---

## Búsqueda del InstructorWorkshop equivalente

El sistema no usa IDs directos para localizar el taller del período siguiente. Construye una clave compuesta:

```
{instructor_id}_{workshop.name}_{day_of_week}_{start_time}_{duration}_{modality}
```

Los `InstructorWorkshop` del período siguiente se pre-cargan en memoria al inicio (evita N+1). Si no existe match exacto, la inscripción se omite con warning en el log.

---

## Mapeo de clases (EnrollmentClass)

| Tipo de inscripción | Comportamiento |
|---------------------|---------------|
| `full_month` | Se asigna a **todas** las `WorkshopClass` con `status = 'scheduled'` del nuevo período, hasta `number_of_classes` |
| `specific_classes` | Se intenta mapear cada clase original a su equivalente: mismo día de semana + mismo `start_time` + mismo `end_time`, excluyendo clases ya usadas |

Solo se asignan clases con `status != 'cancelled'`. Los feriados ya fueron marcados como `cancelled` por `WorkshopReplicationService`.

---

## Reglas de omisión

| Condición | Resultado |
|-----------|-----------|
| Estudiante ya tiene batch activo en período siguiente (creado manualmente) | Batch omitido — warning |
| Estudiante no está al día con mantenimiento | Batch omitido — warning |
| Taller equivalente no existe en período siguiente | Inscripción omitida — warning |
| Taller lleno (`isFullForPeriod`) | Inscripción omitida — warning |
| Ninguna inscripción se creó para un batch | Batch vacío eliminado |

El método `validateStudentEligibility` delega a `Student::isMaintenanceCurrent()`, que contempla:
- Categorías exoneradas (Vitalicios, Hijo de Fundador, Transitorio Mayor de 75): siempre elegibles
- Período de gracia de 2 meses para las demás categorías

---

## Protección contra duplicados entre batches del mismo estudiante

Un estudiante puede tener **múltiples batches** replicados en el mismo período (si en el mes origen tenía múltiples batches). El servicio mantiene `$createdBatchIdsByStudent` en memoria para excluir los batches que él mismo creó en la ejecución actual, evitando falsos positivos en la validación de duplicados.

---

## Modelo de datos

```mermaid
erDiagram
    MONTHLY_PERIOD {
        bigint id PK
        int year
        int month
        timestamp enrollments_replicated_at "null si no se replicó"
    }

    WORKSHOP_TEMPLATE {
        bigint id PK
        string name
        decimal standard_monthly_fee
        int number_of_classes "clases canónicas del template"
    }

    WORKSHOP {
        bigint id PK
        bigint monthly_period_id FK
        bigint workshop_template_id FK "nullable"
        decimal standard_monthly_fee "precio correcto para este mes"
        int number_of_classes "clases scheduled reales (actualizado por WorkshopReplicationService)"
    }

    WORKSHOP_CLASS {
        bigint id PK
        bigint workshop_id FK
        bigint monthly_period_id FK
        date class_date
        enum status "scheduled | cancelled | completed"
    }

    INSTRUCTOR_WORKSHOP {
        bigint id PK
        bigint instructor_id FK
        bigint workshop_id FK
    }

    ENROLLMENT_BATCH {
        bigint id PK
        bigint student_id FK
        decimal total_amount
        enum payment_status "pending | to_pay | completed | refunded"
        string notes "incluye referencia al batch origen"
    }

    STUDENT_ENROLLMENT {
        bigint id PK
        bigint enrollment_batch_id FK
        bigint instructor_workshop_id FK
        bigint monthly_period_id FK
        bigint previous_enrollment_id FK "encadena renovaciones"
        int number_of_classes
        decimal price_per_quantity
        decimal total_amount
        string pricing_notes
    }

    ENROLLMENT_CLASS {
        bigint id PK
        bigint student_enrollment_id FK
        bigint workshop_class_id FK
        decimal class_fee
        enum attendance_status
    }

    MONTHLY_PERIOD ||--o{ WORKSHOP : "período"
    WORKSHOP_TEMPLATE ||--o{ WORKSHOP : "plantilla"
    WORKSHOP ||--o{ WORKSHOP_CLASS : "clases"
    WORKSHOP ||--o{ INSTRUCTOR_WORKSHOP : "asignado a"
    INSTRUCTOR_WORKSHOP ||--o{ STUDENT_ENROLLMENT : "en taller"
    ENROLLMENT_BATCH ||--o{ STUDENT_ENROLLMENT : "contiene"
    STUDENT_ENROLLMENT ||--o{ ENROLLMENT_CLASS : "clases asignadas"
    WORKSHOP_CLASS ||--o{ ENROLLMENT_CLASS : "fecha concreta"
    STUDENT_ENROLLMENT ||--o| STUDENT_ENROLLMENT : "previous_enrollment_id"
```

---

## Archivos clave

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Console/Commands/AutoGenerateNextMonthEnrollments.php` | Comando artisan; valida día/hora/feature flag antes de delegar al servicio |
| `app/Services/EnrollmentReplicationService.php` | Lógica principal: búsqueda de batches, cálculo de precio, creación de enrollments y enrollment classes |
| `app/Services/WorkshopReplicationService.php` | Debe correr primero; genera `workshop_classes` y actualiza `number_of_classes` |
| `app/Models/MonthlyPeriod.php` | Contiene `enrollments_replicated_at` (lock de idempotencia) |
| `app/Models/Workshop.php` | `standard_monthly_fee` y `number_of_classes` son la fuente de verdad del precio mensual |
| `app/Models/WorkshopTemplate.php` | `number_of_classes` canónico (referencia histórica, no determina el precio en replicación) |
| `app/Models/Student.php` | `inscription_multiplier`, `isMaintenanceCurrent()` |

---

## Ejecución manual

```bash
# Verificar sin ejecutar (dry-run no disponible — revisar logs post-ejecución)
php artisan enrollments:auto-generate --force

# Ver resultado en logs
php artisan pail
```

El flag `--force` omite validaciones de día/hora y el lock `enrollments_replicated_at`. Muestra advertencia si ya se ejecutó para el período.
