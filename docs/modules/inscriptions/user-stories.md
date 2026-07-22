# Historias de Usuario — Módulo de Inscripciones

> Organización: CRUD → Wizard de creación → Casos especiales → Automatizaciones
> Referencia rápida: menciona el ID (ej. `HU-I02`) para que Claude cargue el contexto completo.

---

## Mapa del módulo

```
EnrollmentBatch (lote)
  ├── CRUD
  │   ├── HU-I01  Listar inscripciones
  │   ├── HU-I06  Exportar a Excel
  │   └── (cancelación — acción en tabla, ver HU-I05)
  │
  ├── Flujo de creación (Wizard 3 pasos)
  │   ├── HU-I02  Iniciar: seleccionar período + estudiante
  │   ├── HU-I03  Seleccionar talleres disponibles
  │   ├── HU-I04  Configurar detalles + precios
  │   └── HU-I05  Pago y finalización → genera Ticket
  │
  ├── Casos especiales
  │   └── HU-I08  Inscripción por recuperación (pago cero)
  │
  └── Automatizaciones
      └── HU-I07  Replicación automática al siguiente mes
```

---

## CRUD

### HU-I01: Listar Inscripciones  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Ver registro de todas las inscripciones realizadas
**Beneficio:** Control de estudiantes, estados de pago y métodos

#### Criterios de aceptación
- [ ] Muestra: estudiante, fecha, estado de pago, método de pago
- [ ] Filtra por periodo mensual, estado de pago, estudiante
- [ ] Busca por nombre de estudiante o código de batch

#### Reglas de negocio
- Columna `payment_status` usa enum: `pending | to_pay | completed | credit_favor | refunded`
- Acciones visibles según estado del batch (ver HU-I05)

#### Conexión con el código
- Recurso: `EnrollmentBatchResource` → `ListEnrollmentBatches`
- Modelo: `EnrollmentBatch`
- Eager load: `student`, `studentEnrollments.instructorWorkshop.workshop`

---

### HU-I06: Exportar Inscripciones  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Exportar lista de inscripciones a Excel con los filtros activos
**Beneficio:** Generar reportes externos

#### Criterios de aceptación
- [ ] Exporta respetando los filtros activos en la tabla
- [ ] Incluye: estudiante, período, talleres, monto, estado de pago, método de pago
- [ ] Formato compatible con Excel

#### Conexión con el código
- Export: `app/Exports/EnrollmentBatchExport.php`
- Librería: Maatwebsite Excel

---

## Flujo de creación (Wizard)

El wizard tiene 3 pasos en secuencia. Se ejecuta en `EnrollmentBatchResource → CreateEnrollmentBatch`.

```
Paso 1: HU-I02 → Período + Estudiante
Paso 2: HU-I03 → Talleres disponibles
Paso 3: HU-I04 → Detalles + precios  →  HU-I05 → Pago → Ticket
```

---

### HU-I02: Iniciar Proceso de Inscripción (Paso 1)  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Seleccionar período mensual y estudiante antes de avanzar
**Beneficio:** Establecer el contexto correcto para el resto del wizard

#### Criterios de aceptación
- [ ] Se selecciona `MonthlyPeriod` antes de buscar al estudiante
- [ ] Buscador de estudiantes filtra por nombre o código
- [ ] Si el estudiante no tiene mantenimiento al día: advertencia o bloqueo
- [ ] Wizard avanza: Período → Estudiante → Talleres → Pago

#### Campos del formulario
| Campo | Tipo | Requerido |
|-------|------|-----------|
| Período mensual | Select | Sí |
| Estudiante | Search/Select | Sí |

#### Reglas de negocio
- `Student::maintenance_period_id` debe estar dentro de los últimos 2 meses del período seleccionado
- Categorías exentas de la validación de mantenimiento: Vitalicios, Hijo de Fundador, Transitorio Mayor de 75

#### Conexión con el código
- Recurso: `EnrollmentBatchResource` → `CreateEnrollmentBatch`
- Servicio: `EnrollmentBatchService`
- Validación: `Student::isMaintenanceCurrent()`

---

### HU-I03: Seleccionar Talleres (Paso 2)  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Ver y seleccionar talleres disponibles para el estudiante en el período dado
**Beneficio:** Inscribir al estudiante en los talleres correctos con precio calculado automáticamente

#### Criterios de aceptación
- [ ] Lista solo talleres del período con cupo disponible
- [ ] Muestra cupo restante (`isFullForPeriod()`)
- [ ] Muestra costo calculado según categoría del estudiante (multiplicador)
- [ ] Permite tipo de inscripción: `full_month` (mes completo) o `specific_classes` (recuperación)
- [ ] Indica si el taller está relacionado con cuota de mantenimiento

#### Combinaciones posibles por taller

| Tipo | Descripción | Clases asignadas |
|------|-------------|-----------------|
| `full_month` | Todas las clases del mes | `number_of_classes` del taller |
| `specific_classes` | Solo las fechas que el estudiante selecciona | Las que elija el cajero |

#### Reglas de negocio — Pricing

| Categoría del estudiante | Multiplicador | Ejemplo base S/10 |
|--------------------------|:-------------:|:-----------------:|
| PRE PAMA 50+ | 2.0x | S/ 20.00 |
| PRE PAMA 55+ | 1.5x | S/ 15.00 |
| Resto (PAMA regular, Vitalicios, etc.) | 1.0x | S/ 10.00 |

- No se puede inscribir en taller sin cupo (`isFullForPeriod()` retorna true)
- El precio base viene de `Workshop.standard_monthly_fee` del período actual

#### Conexión con el código
- Modelos: `Workshop`, `InstructorWorkshop`, `StudentEnrollment`
- Capacity: `Workshop::isFullForPeriod($periodId)`
- Pricing: `Student.php` → métodos de cálculo por categoría

---

### HU-I04: Configurar Detalles de Inscripción (Paso 3a)  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Ajustar cantidad de clases, ver subtotales y total del batch
**Beneficio:** Registrar con precisión los términos de cada inscripción

#### Criterios de aceptación
- [ ] Permite ajustar `number_of_classes` si tipo es `specific_classes`
- [ ] Muestra subtotal por taller y total del batch
- [ ] El precio queda guardado en `StudentEnrollment.total_amount` al crear (precio congelado)

#### Campos relevantes
| Campo | Modelo | Descripción |
|-------|--------|-------------|
| `number_of_classes` | `StudentEnrollment` | Cantidad de clases a tomar |
| `price_per_quantity` | `StudentEnrollment` | Precio unitario por clase |
| `total_amount` | `StudentEnrollment` | Total calculado al momento de inscripción |

#### Reglas de negocio
- El precio se **congela** al crear la inscripción — cambios futuros al taller no afectan enrollments existentes
- `total_amount = price_per_quantity × number_of_classes`

#### Conexión con el código
- Modelo: `StudentEnrollment`
- Servicio: `EnrollmentBatchService`

---

### HU-I05: Pago y Finalización (Paso 3b)  ·  ✅ Hecho

**Rol:** Administrador
**Acción:** Registrar método de pago y finalizar la inscripción
**Beneficio:** Estudiante queda oficialmente inscrito con comprobante generado

#### Criterios de aceptación
- [ ] Permite pago total o parcial (selección de subset de talleres)
- [ ] Pago parcial → batch pasa a `to_pay` (protegido de auto-cancelación global)
- [ ] Pago total → batch pasa a `completed`
- [ ] Se genera `Ticket` con código único
- [ ] Se registra `payment_registered_by_user_id`

#### Estados del EnrollmentBatch

| Estado | Descripción | ¿Se auto-cancela el día 28? |
|--------|-------------|----------------------------|
| `pending` | Sin ningún pago registrado | **Sí** → pasa a `refunded` |
| `to_pay` | Pago parcial: al menos un taller pagado, no todos | **No** → requiere gestión manual |
| `completed` | Todos los talleres del batch pagados | No aplica |
| `credit_favor` | Saldo a favor del estudiante | No aplica |
| `refunded` | Cancelado/devuelto (manual o automático) | No aplica |

#### Detalle del estado `to_pay`

Ocurre cuando el estudiante paga solo algunos talleres del lote.

**Ejemplo:** 3 talleres inscritos — se pagan 2, el tercero queda pendiente.

```
EnrollmentBatch  → to_pay
  ├── StudentEnrollment (Yoga)      → completed  ✅ pagado
  ├── StudentEnrollment (Pintura)   → completed  ✅ pagado
  └── StudentEnrollment (Danzas)   → pending    ⏳ sin pagar
```

**Qué pasa el día 28 con un batch `to_pay`:**
- El batch completo **NO se cancela** (está protegido)
- Solo los `StudentEnrollment` con `payment_status = pending` dentro del batch se cancelan
- `total_amount` del batch se recalcula con los talleres que quedan activos
- Si los talleres restantes ya estaban pagados, el batch pasa automáticamente a `completed`

**Qué NO hace el sistema con `to_pay`:**
- No notifica al estudiante que tiene saldo pendiente
- No bloquea replicación al mes siguiente (solo verifica mantenimiento)
- Requiere seguimiento manual

#### Métodos de pago

| Método | Flujo | Código de ticket |
|--------|-------|-----------------|
| Efectivo (cash) | Cajero ingresa monto; sistema calcula vuelto | `{enrollment_code}-{seq-6-dígitos}` |
| Transferencia (link) | Cajero confirma con código de voucher | `{enrollment_code}-{codigo_voucher}` |

#### Flujo de estados

```
Creación
  └── pending (sin pagos)
        ├── pago parcial → to_pay
        │     ├── pago del resto → completed
        │     └── día 28 → inscripciones pending del batch se cancelan
        │                  batch recalcula total → puede pasar a completed
        └── día 28 sin ningún pago → refunded (batch completo cancelado)
```

#### Acciones disponibles en tabla según estado

| Estado batch | "Cobrar" | "Anular" | "Anular pendientes" |
|-------------|:--------:|:--------:|:-------------------:|
| `pending` | ✅ | ✅ (usuarios autorizados) | ✗ |
| `to_pay` | ✅ | ✗ | ✅ (usuarios autorizados) |
| `completed` | ✗ | ✅ (usuarios autorizados) | ✗ |
| `refunded` | ✗ | ✗ | ✗ |

#### Reglas de negocio
- `to_pay` NO se auto-cancela como batch → las inscripciones individuales sin pagar sí se cancelan el día 28
- `pending` SÍ se auto-cancela completo en el día configurado (default: día 19)
- Un batch `to_pay` **sí se replica** al mes siguiente si el estudiante está al día con mantenimiento
- La replicación crea el nuevo batch en `pending` independientemente del estado parcial anterior
- Autorización para anular: whitelist hardcoded en `EnrollmentBatchResource` (no usa Policies)

#### Conexión con el código
- Acción de pago: `RegisterPaymentAction` — `EnrollmentBatchResource/Actions/`
- Servicio: `EnrollmentPaymentService::processPayment()`, `::updateBatchStatus()`
- Auto-cancelación: `AutoCancelPendingEnrollments` → `cancelPendingBatches()` y `cancelUnpaidEnrollmentsInPartialBatches()`
- Modelos: `EnrollmentPayment`, `EnrollmentPaymentItem`, `Ticket`
- Docs detallados de pagos: `docs/modules/payments/full-payment-flow.md` y `partial-payment-flow.md`

---

## Casos especiales

### HU-I08: Inscripción por Recuperación (Pago Cero)  ·  ❌ Descartado

> ❌ **Descartado (2026-07).** El approach "pago cero" (`total_amount = 0` + `recovery_tag_id`) fue reemplazado por **recuperaciones con crédito** (`StudentCredit`), otra lógica que se desarrolla en la branch `feat/recuperaciones`. Se conserva esta HU como rastro de la decisión. Para el diseño vigente ver esa branch y su documentación de crédito.

**Rol:** Administrador
**Acción:** Crear inscripción tipo "recuperación" con `total_amount = 0` y motivo del catálogo
**Beneficio:** Estudiante asiste sin generar cobro, con trazabilidad del motivo y control en automatizaciones

#### Descripción

Tercera variante de inscripción: **`recovery`** (determinada por `recovery_tag_id IS NOT NULL`).

Casos de uso típicos:
- Estudiante faltó una clase y la recupera en otro horario sin costo
- Cortesía institucional o acuerdo especial
- Corrección de inscripción previa
- Invitado puntual a una sesión

#### Criterios de aceptación

**Flujo de inscripción:**
- [ ] Toggle/checkbox **"¿Es recuperación?"** en el wizard (independiente de `enrollment_type`)
- [ ] Al activar: `total_amount` se fija en `0.00` y no es editable
- [ ] Al activar: selector obligatorio **"Motivo"** lista solo `Tag` con `context = 'recovery_reason'` activos
- [ ] `EnrollmentBatch` resultante: `total_amount = 0` y `payment_status = completed` al crearse
- [ ] Se genera `Ticket` con `amount = 0` (comprobante de recuperación)
- [ ] El estudiante aparece en lista de asistencia igual que inscripción normal
- [ ] Badge visual diferenciado en `EnrollmentBatchResource` para tipo `recovery`

**Comportamiento en automatizaciones (controlado por flags del Tag):**
- [ ] `tag.excludes_from_replication = true` → la inscripción **no se replica** al mes siguiente
- [ ] `tag.excludes_from_instructor_revenue = true` → la inscripción **no cuenta** en revenue del instructor
- [ ] Las inscripciones `recovery` **no se auto-cancelan** el día 28 (ya nacen en `completed`)

**Gestión del catálogo de Tags:**
- [ ] Recurso Filament `TagResource` para administrar el catálogo
- [ ] Al crear/editar tag: nombre, contexto, flags de comportamiento
- [ ] Solo tags con `context = 'recovery_reason'` aparecen en el selector

#### Combinaciones posibles

| Tipo inscripción | ¿Recovery? | `total_amount` | `payment_status` batch | Replica mes siguiente |
|-----------------|:----------:|:-------------:|:---------------------:|:--------------------:|
| `full_month` | No | Calculado normal | `pending` → flujo normal | Sí |
| `specific_classes` | No | Calculado normal | `pending` → flujo normal | Sí |
| `full_month` | Sí | `0.00` | `completed` directo | Según flag del tag |
| `specific_classes` | Sí | `0.00` | `completed` directo | Según flag del tag |
| Mix: normales + recovery en mismo batch | — | Solo suma normales | Normal (recovery excluida del total) | Según flags de cada inscripción |

#### Reglas de negocio

| # | Regla |
|---|-------|
| 1 | `recovery_tag_id IS NOT NULL` → bypasea cálculo de precio: `total_amount = 0`, ignora multiplicadores |
| 2 | Batch con inscripciones `recovery` nace en `completed` (no pasa por flujo de pago) |
| 3 | Si batch mezcla normales + `recovery`: total del batch excluye las `recovery`; las normales siguen flujo habitual |
| 4 | Validación de mantenimiento al día **sí aplica** (igual que cualquier inscripción) |
| 5 | Validación de cupo **sí aplica** (ocupa lugar en el taller) |
| 6 | Ticket generado tiene `amount = 0`; se registra en `tickets` para trazabilidad |
| 7 | El comportamiento en automatizaciones lo determinan las **flags del tag**, no `enrollment_type` |
| 8 | `enrollment_type` no cambia — sigue siendo `full_month` o `specific_classes` |

#### Diseño de BD

```sql
-- Tabla nueva
tags
  id
  name                              varchar   -- ej: "Recuperación de clase", "Cortesía staff"
  context                           varchar   -- 'recovery_reason' | 'cancellation_reason' | etc.
  excludes_from_instructor_revenue  boolean   default false
  excludes_from_replication         boolean   default false
  is_active                         boolean   default true
  timestamps

-- FK directa en student_enrollments (un motivo por inscripción recovery)
student_enrollments
  ...
  recovery_tag_id   FK → tags.id  nullable
```

> **¿Por qué FK directa y no pivot polimórfico?** La inscripción `recovery` requiere exactamente **un** motivo. FK directa es más simple y evita ambigüedad. La tabla `tags` sigue siendo reutilizable para otros contextos.

#### Impacto en otros módulos

| Módulo | Impacto |
|--------|---------|
| Reporte de ingresos | No contar inscripciones `recovery` (total_amount = 0) |
| Pago a instructores (voluntario) | Leer `excludes_from_instructor_revenue` para excluir del revenue |
| Auto-replicación mensual | `EnrollmentReplicationService` lee `excludes_from_replication` |
| Auto-cancelación día 28 | No aplica — batch nace `completed` |
| Lista de asistencia | Sin cambio — aparece normalmente |
| Tickets | Genera ticket con `amount = 0`, mismo flujo |

#### Flujo esperado

```
Wizard de inscripción
  ├── Paso: enrollment_type (sin cambio)
  │     ├── full_month       → todas las clases del mes
  │     └── specific_classes → clases específicas seleccionadas
  │
  └── Paso: ¿Es recuperación?
        ├── No  → precio calculado normal según enrollment_type
        └── Sí  → selector "Motivo" (tags context=recovery_reason, obligatorio)
                  recovery_tag_id se asigna al StudentEnrollment
                  total_amount = 0 (fijo)
                  batch nace en completed
                  genera Ticket amount = $0
                  automatizaciones leen flags del tag seleccionado
```

#### Conexión con el código

| Archivo | Cambio requerido |
|---------|-----------------|
| Nueva migración | Crear tabla `tags` + columna `recovery_tag_id` en `student_enrollments` |
| `app/Models/StudentEnrollment.php` | Relación `recoveryTag()` → `Tag` |
| `app/Models/Tag.php` | Nuevo modelo con scopes por context |
| `app/Services/EnrollmentBatchService.php` | Lógica precio cero para `recovery` |
| `app/Services/EnrollmentReplicationService.php` | Filtrar por `tag.excludes_from_replication` |
| `app/Observers/StudentEnrollmentObserver.php` | Filtrar por `tag.excludes_from_instructor_revenue` (nota: `InstructorPaymentService.php` era código muerto y se eliminó 2026-07 — el cálculo real vive acá) |
| `app/Filament/Resources/EnrollmentBatchResource.php` | Toggle recovery + selector tag en wizard |
| `app/Filament/Resources/TagResource.php` | Nuevo recurso para gestión del catálogo |

---

## Automatizaciones

### HU-I07: Replicación Automática al Siguiente Mes  ·  ✅ Hecho

**Rol:** Sistema (job manual/programado)
**Acción:** Copiar inscripciones del mes actual al siguiente para estudiantes con batch `completed`
**Beneficio:** Evitar re-selección manual de talleres cada mes; batch del siguiente mes queda en `pending` listo para cobrar

#### Qué se replica

| Qué | Estado resultante | Nota |
|-----|------------------|------|
| `EnrollmentBatch` | `pending` | Precio NO se copia |
| `StudentEnrollment` × N | `pending` | Precio **se recalcula** con tarifas del nuevo período |
| `EnrollmentClass` × M | — | Se asignan a `WorkshopClass` del nuevo período |

No se replica: pagos, tickets, ni estado de pago original.

#### Criterios de aceptación
- [ ] Solo replica batches con `payment_status = completed` del período actual
- [ ] Salta al estudiante si ya tiene batch manual en el siguiente período (no sobreescribe)
- [ ] Salta al estudiante si no está al día con mantenimiento (gracia 2 meses)
- [ ] Si el taller no existe en el siguiente período → warning, no error fatal
- [ ] Si el taller está lleno → warning, no error fatal
- [ ] **No asigna clases con `status = cancelled`** (feriados, suspensiones)
- [ ] Si ningún taller pudo replicarse → elimina el batch vacío

#### Orden de ejecución obligatorio

| Paso | Día | Quién | Qué |
|------|-----|-------|-----|
| 1 | Día 19 — 23:59 | Sistema | `enrollments:auto-cancel` → cancela batches `pending` del mes actual |
| 2 | Día 20 — 00:00 | Sistema | `workshops:auto-replicate` → crea talleres y `workshop_classes` del mes siguiente (todas `scheduled`) |
| 3 | Días 20-21 | Admin | Revisa calendario y marca feriados/suspensiones como `cancelled` |
| 4 | Día 22 — 00:00 | Sistema | `enrollments:auto-generate` → crea batches `pending` para el mes siguiente asignando solo clases `scheduled` |

> **Crítico:** `enrollments:auto-cancel` debe correr **antes** de `enrollments:auto-generate`.
> Fix aplicado: `AutoCancelPendingEnrollments` filtra `monthly_period_id <= currentPeriod` para no cancelar batches recién creados del siguiente mes.

#### Configuración en SystemSettings

| Setting | Valor recomendado | Descripción |
|---------|------------------|-------------|
| `auto_cancel_day` | `19` | Día que corre `enrollments:auto-cancel` |
| `auto_cancel_time` | `23:59:00` | Hora exacta |
| `auto_generate_day` | `20` | Día que corre `workshops:auto-replicate` |
| `auto_generate_time` | `00:00:00` | Hora exacta |
| `auto_replicate_enrollments_day` | `22` | Día que corre `enrollments:auto-generate` |
| `auto_replicate_enrollments_time` | `00:00:00` | Hora exacta |

#### Cronograma mensual

```mermaid
timeline
    title Ciclo de replicación mensual (ejemplo: Mayo → Junio)
    Día 19 - 23:59 : enrollments:auto-cancel
                   : Cancela batches pending de Mayo
                   : Solo períodos actuales o anteriores
    Día 20 - 00:00 : workshops:auto-replicate
                   : Clona talleres de Mayo → Junio
                   : Genera WorkshopClasses para Junio
                   : Todas inician en status = scheduled
    Días 20 y 21   : Admin revisa calendario de Junio
                   : Marca feriados como cancelled
    Día 22 - 00:00 : enrollments:auto-generate
                   : Lee batches completed de Mayo
                   : Crea batches pending para Junio
                   : Solo asigna clases scheduled
```

#### Bug documentado: Semana Santa

**Síntoma:** Inscripciones (`EnrollmentClass`) generadas apuntando a Viernes y Sábado Santo
**Causa:** `createDefaultEnrollmentClasses()` y `findEquivalentWorkshopClass()` no filtraban `workshop_classes` con `status = 'cancelled'`
**Fix:** `->where('status', '!=', 'cancelled')` en ambas consultas de `EnrollmentReplicationService`

#### Reglas de negocio — Pricing en replicación

```
finalPrice = workshop.standard_monthly_fee × student.inscription_multiplier
```

El admin ajusta `standard_monthly_fee` antes de correr el replicador (para meses con feriados). El replicador usa el fee **ya ajustado**. Aplicar además una fórmula de recargo causaría doble descuento.

**Bug corregido (2026-06-23):** Existía una rama "holiday" que aplicaba `fee / templateClasses × surcharge × actualClasses` cuando `workshop.number_of_classes < workshopTemplate.number_of_classes`. Causaba precio menor al correcto.

#### Conexión con el código
- Servicio: `app/Services/EnrollmentReplicationService.php`
- Depende de: `app/Services/WorkshopReplicationService.php` (debe correr primero)
- Comando: `app/Console/Commands/AutoGenerateNextMonthEnrollments.php` (deshabilitado en scheduler — ejecución manual)
- Modelos: `EnrollmentBatch`, `StudentEnrollment`, `EnrollmentClass`, `WorkshopClass`
- Doc detallado: `docs/modules/inscriptions/enrollment-replication-flow.md`
