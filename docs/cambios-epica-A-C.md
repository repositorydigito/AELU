# Cambios implementados — Épica A y Épica C

> **Fecha:** 2026-07-02 · **Alcance:** Épica A (motor de "dinero real") completa y Épica C.1 (tickets correlativos). **C.2 excluido** (plazo de retención pendiente de definición por Susana).
>
> Referencia de requerimientos: `docs/new-requirements.md`.

---

## Épica A — Motor de "dinero real"

### Archivos modificados

| Archivo | Cambio | Regla |
|---------|--------|-------|
| `app/Services/InstructorPaymentService.php` | ⚠️ **Corrección posterior (ver sección "Revisión revenue del profesor" abajo):** este Service resultó ser **código muerto** (0 llamadores). El fix de `getWorkshopRevenueForPeriod()` que se hizo aquí **no tenía efecto en runtime**; el cálculo real vive en `StudentEnrollmentObserver`. El Service quedó **comentado**. | RN-A3, RN-A4 |
| `app/Filament/Pages/ScheduleEnrollmentReport.php` | Recaudación por horario: la consulta ahora exige lote (`EnrollmentBatch`) con `payment_status = 'completed'`. Antes solo excluía `refunded` → pendientes y parciales sumaban al total. | RN-A1, RN-A2 |
| `app/Filament/Pages/AllUsersEnrollmentReport.php` | Reporte general: los tickets listados ahora exigen lote `completed`. Tickets de lotes parciales (`to_pay`) ya no aparecen ni suman. | RN-A1, RN-A2 |
| `app/Filament/Pages/CashiersEnrollmentReport.php` | Reporte por cajero (caja): mismo filtro de lote `completed`. | RN-A1, RN-A2 |
| `app/Filament/Pages/MonthlyInstructorReport.php` | Revenue por taller: filtro cambiado de `['completed', 'pending']` a solo `completed` a nivel inscripción (en la consulta principal y en `processWorkshops()`). | RN-A3 |
| `app/Filament/Resources/InstructorPaymentResource.php` | Nueva columna "Conciliación" en la tabla de Pago de Profesores: ícono verde si cuadra, triángulo rojo con tooltip de montos (esperado vs cobrado vs diferencia) si hay descuadre. **Solo alerta visual, no bloquea.** | RN-A6 |

### Archivos nuevos

| Archivo | Descripción | Regla |
|---------|-------------|-------|
| `app/Services/InstructorPaymentReconciliationService.php` | Servicio de conciliación: compara el revenue esperado (Σ inscripciones `completed` del taller/período) contra los pagos realmente registrados (Σ `enrollment_payment_items.amount` de pagos `completed`). Con caché por taller/período para no repetir consultas por fila de la tabla. | RN-A6 |

### Archivos eliminados

| Archivo | Motivo |
|---------|--------|
| `app/Policies/TreasuryPolicy.php` | Policy huérfana autogenerada por Filament Shield; referenciaba `App\Models\Treasury`, modelo que no existe (sin migración, tabla ni uso). Limpieza acordada en Q6. |

### Notas de comportamiento (criterios de aceptación cubiertos)

- **CA-A1/A2/A3** — Los tres reportes de recaudación (general, horario, cajero) muestran y suman **solo lotes `completed`**; `pending`/`to_pay`/`refunded` desaparecen de la vista y del total. Los exports Excel/PDF heredan el filtro porque consumen el mismo dataset en memoria.
- **CA-A4/A5** — El pago al profesor suma cada inscripción `completed` de su horario aunque el lote esté `to_pay`; las pendientes no suman.
- **CA-A6** — Sin cambios: `getTotalHoursForPeriod()` ya excluía clases `cancelled` (feriados).
- **CA-A7** — Alerta visual de descuadre operativa en Tesorería → Pago de Profesores.

### Revisión revenue del profesor (post-Épica A) + fix crédito de recuperación

Al validar la interacción con recuperaciones (Épica D) se verificó el flujo real y se corrigieron dos cosas:

1. **`InstructorPaymentService` es código muerto.** No lo llama ningún comando, scheduler, página, resource ni test (confirmado por grep + exploración). El pago al instructor lo calcula **`app/Observers/StudentEnrollmentObserver.php`** (`calculateAndSaveInstructorPayment`, `updateOrCreate` en cada cambio a `completed`), que **ya** filtraba `payment_status='completed'` (RN-A3). Por eso el fix de Épica A al Service no cambiaba nada en runtime. **Acción:** el Service quedó **comentado** con un banner que explica que está muerto y en qué difiere del observer (hourly: cuenta clases reales vs 4 fijas; % voluntario: `getEffectiveVolunteerPercentage` vs `custom ?? 50`; crédito). No se borró, por decisión.

2. 🔴 **Bug de sobre-resta de crédito (RN-D5 / RN-D23).** En 3 lugares se restaba el `StudentCredit.amount` **completo** (suma de clases del mes origen), cuando lo realmente aplicado a la inscripción destino es `min(credit.amount, total_amount)` (`EnrollmentPaymentService::processPaymentWithCredit`). Si el crédito excede el total del destino → se restaba de más: **subpagaba al profesor** y **descuadraba la recaudación** contra caja. **Fix:** nuevo helper `EnrollmentPaymentItem::creditAppliedForEnrollments()` que suma el crédito **realmente aplicado** (items de pago con `payment_method='credito'`, ya capado). Reemplaza el patrón erróneo en:
   - `app/Observers/StudentEnrollmentObserver.php` (revenue voluntario, RN-D5)
   - `app/Filament/Pages/AllUsersEnrollmentReport.php` (dinero nuevo, RN-D23)
   - `app/Filament/Pages/CashiersEnrollmentReport.php` (dinero nuevo, RN-D23)

**Archivos nuevos/tocados en esta revisión:** `app/Models/EnrollmentPaymentItem.php` (helper), observer, los 2 reportes, `app/Services/InstructorPaymentService.php` (comentado).

3. **Regla de aplicabilidad del crédito ampliada (decisión 2026-07).** `StudentCredit::isApplicableTo` exigía que el taller destino coincidiera exactamente con el de origen (`matchesWorkshop`: nombre+instructor+día+hora+modalidad). Se descubrió que por eso un crédito de "YOGA Miércoles Presencial" no aplicaba a "YOGA Lunes Virtual" del mismo alumno. **Decisión del cliente:** el crédito es saldo aplicable a **cualquier taller/horario** al pagar. Se removió la restricción `matchesWorkshop`; ahora solo valida disponible + mismo alumno + mes de vigencia (RN-D17). Archivo: `app/Models/StudentCredit.php`. Limitación conocida: 1 crédito por inscripción (no apila varios en un pago).

---

## Épica C — Tickets y recibos

### C.1 — Tickets correlativos (cash + link unificado, secuencia GLOBAL) — ✅ corregido en esta sesión

**Corrección de alcance (2026-07-02):** el cliente aclaró que el correlativo debe ser **global** (un único contador compartido por todos los cajeros), no uno independiente por cajero como se había implementado antes (commit `1da9c68`). Se revirtió el diseño "por cajero" y se reemplazó por un contador compartido.

| Archivo | Cambio |
|---------|--------|
| `app/Services/EnrollmentPaymentService.php` | `getNextSequential()` ya **no** recibe `User` ni bloquea `users.id`: ahora incrementa bajo `lockForUpdate` la fila `system_settings.key = 'global_ticket_seq'`, serializando la emisión de tickets de **todo el sistema** (no solo del mismo cajero). El prefijo `enrollment_code` del cajero se sigue anteponiendo al código, pero ya no abre una secuencia propia. `createTicketWithUniqueCode()` sin cambios (reintento ante colisión SQLSTATE 23000 sigue vigente). |
| `app/Models/User.php` | Revertido: se quitó `last_ticket_seq` de `$fillable` (la columna ya no existe). |
| `database/migrations/2026_07_02_120000_add_last_ticket_seq_to_users_table.php` | **Eliminada** (estaba sin commitear; nunca llegó a correr en ningún entorno). Reemplazada por la migración de abajo. |
| `database/migrations/2026_07_03_000000_seed_global_ticket_sequence.php` | **Nueva.** Siembra `system_settings` con `key = 'global_ticket_seq'`, valor = correlativo máximo real ya emitido en **toda** la tabla `tickets` (no por usuario). |

> **Por qué `system_settings` y no una tabla nueva:** ya existe como tabla clave/valor de configuración global en el proyecto; una fila con `lockForUpdate()` sirve igual de bien que una tabla dedicada para un contador único, sin agregar una tabla nueva solo para esto.

### 🔴 Hotfix post-deploy (2026-07-06): corrupción del contador global

Al correr en el entorno real, la siembra calculó mal el máximo: un ticket link **legacy de 2 partes** (`{enrollment_code}-{voucher}`, ej. `006-01061705`) tiene un voucher puramente numérico de 8 dígitos que el parser original confundió con un correlativo real de 6 dígitos, sembrando el contador en **~1,061,705** en vez de **~513**. Esto causó `SQLSTATE 23000` (duplicado `002-000001`) en un pago y, en el reintento, un ticket válido pero con número absurdo (`002-1061706`).

**Corrección:**
- `database/migrations/2026_07_03_000000_seed_global_ticket_sequence.php` — el parser ahora exige que la parte tras el primer guion tenga **exactamente 6 dígitos numéricos** (formato real del generador) antes de tratarla como correlativo; cualquier otro caso (vouchers legacy, alfanuméricos, longitudes distintas) se ignora.
- `database/migrations/2026_07_06_120000_fix_global_ticket_seq_corruption.php` — **nueva**, recalcula y corrige el valor ya sembrado en la base real usando el mismo criterio estricto. Ejecutada: `global_ticket_seq` corregido de `1061705` → `513`.
- El ticket `002-1061706` (id 2728, pago real de S/45, lote 4672) **no se tocó** — queda con número fuera de secuencia pero válido y único. Renumerar un ticket ya emitido viola RN-C4; si se quiere corregir visualmente requiere decisión de negocio (anular + reemitir), no un fix de datos.

### C.2 — Gastos extra: DELETE con retención — ⛔ NO implementado (a pedido)

Excluido de este alcance: el plazo de retención (3/6 meses) está **por definirse** (bloqueante — Susana). Sin cambios en `Expense`, `ExpenseDetail` ni `ExpenseResource`.

---

## Pendientes / seguimiento

- **Tests:** el proyecto solo tiene `ExampleTest` y solo existe `UserFactory` — no hay factories para Student/StudentEnrollment/EnrollmentPayment/etc. Queda pendiente (bloqueado por falta de factories) cubrir: RN-A3/A4 (revenue solo `completed`), RN-A1/A2 (recaudación por lote), RN-C3 (correlativo global, caso concurrente entre 2 cajeros) y **RN-D5 caso borde**: crédito aplicado > total destino → verificar que `creditAppliedForEnrollments` resta solo lo aplicado (capado), no el `credit.amount`. Sin datos de crédito consumido en la DB actual no se pudo smoke-test con datos reales; el helper se validó con entrada vacía (→ 0).
- **Migración pendiente de correr:** `php artisan migrate` para `2026_07_03_000000_seed_global_ticket_sequence` (aún no se aplicó en ningún entorno).
- **Concurrencia (nota):** al pasar de contador por cajero a global, el `lockForUpdate` ahora serializa **todas** las emisiones de ticket del sistema (más contención que antes), lo cual es la contraparte esperada de tener una secuencia realmente global.
- **Opcional (eficiencia):** denormalizar `monthly_period_id` en `enrollment_batches`/`enrollment_payments` — no implementado por ser opcional.
- **Datos históricos:** lotes pagados antes del sistema de `EnrollmentPayment` (nov 2025) pueden no tener items de pago → la columna "Conciliación" los marcará con descuadre. Es esperado: la alerta es informativa y sirve justamente para detectar esos casos.
