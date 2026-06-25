# User Stories — Reporte General de Pago de Profesores

---

## HU-RPP-01: Registrar recibo único por instructor al fin de mes

**Título:** Registrar un solo recibo para todos los talleres de un instructor

**Descripción:**
Como administradora de tesorería, quiero registrar un único número de recibo y fecha de pago para cubrir todos los talleres de un instructor en el período mensual, para que el registro refleje la realidad operativa (se paga una sola vez al mes por todos los talleres) sin tener que ir fila por fila.

**Design:** N/A

---

### Reglas de Negocio

**RN-01 — Un solo pago por instructor por período:**
Un instructor recibe un único pago al final del mes que cubre todos sus talleres y horarios del período. No se emiten recibos separados por taller.

**RN-02 — El recibo es una entidad independiente:**
El recibo (`InstructorPaymentReceipt`) es un documento que agrupa N registros de `InstructorPayment` del mismo instructor + período + tipo de pago (`volunteer` o `hourly`). Un instructor puede tener dos recibos en el mismo período si dicta talleres de ambos tipos (uno voluntario y uno por horas).

**RN-03 — Separación de tipos de pago:**
Los talleres tipo `volunteer` y tipo `hourly` del mismo instructor se pagan con recibos separados porque tienen formas de cálculo distintas y pueden procesarse en momentos diferentes.

**RN-04 — El recibo determina el estado de pago:**
Un `InstructorPayment` se considera pagado cuando tiene un `instructor_payment_receipt_id` asignado. El campo `payment_status` de cada fila se sincroniza a `'paid'` al crear el recibo y a `'pending'` si el recibo es eliminado.

**RN-05 — No se puede modificar un recibo registrado:**
Una vez creado, el número de recibo y fecha de pago no se pueden cambiar desde el reporte general. Si hay un error, se debe eliminar el recibo (acción de admin) y registrar uno nuevo. Eliminar un recibo revierte `payment_status` a `'pending'` en todos sus `InstructorPayment`.

**RN-06 — El PDF del recibo individual reemplaza al listado por taller:**
El PDF generado para un instructor (vista `resources/views/reports/instructor-payments.blade.php`) mostrará el `document_number` y `payment_date` provenientes del recibo asociado (JOIN a través de `instructor_payment_receipt_id`), no de campos individuales por fila.

---

### Diseño de Base de Datos

**Problema actual:** `document_number`, `payment_date` se almacenan repetidos en cada fila de `instructor_payments` para el mismo instructor. Si un instructor tiene 3 talleres, el mismo número de recibo se duplica 3 veces — violación de 2NF.

**Solución:** Nueva tabla `instructor_payment_receipts` + FK en `instructor_payments`.

#### Nueva tabla: `instructor_payment_receipts`

| Columna              | Tipo             | Notas                                              |
|----------------------|------------------|----------------------------------------------------|
| `id`                 | bigint PK        |                                                    |
| `instructor_id`      | FK → instructors |                                                    |
| `monthly_period_id`  | FK → monthly_periods |                                                |
| `payment_type`       | enum('volunteer','hourly') | Separado por tipo                       |
| `document_number`    | string           | Número de recibo/comprobante                       |
| `payment_date`       | date             |                                                    |
| `total_amount`       | decimal(10,2)    | Suma de `calculated_amount` de las filas asociadas |
| `registered_by`      | FK → users nullable | Usuario que registró el pago                    |
| `notes`              | text nullable    |                                                    |
| `timestamps`         |                  |                                                    |

**Constraint único:** `UNIQUE(instructor_id, monthly_period_id, payment_type)` — un solo recibo por instructor+período+tipo.

#### Modificación tabla: `instructor_payments`

- **Agregar:** `instructor_payment_receipt_id` (FK nullable → `instructor_payment_receipts`, onDelete SET NULL)
- **Eliminar:** `document_number` (se mueve al recibo)
- **Eliminar:** `payment_date` (se mueve al recibo)
- **Mantener:** `payment_status` (se sincroniza cuando se crea/elimina el recibo)

---

### Contexto Técnico

- `AllInstructorsPaymentReport` agrupa `InstructorPayment` por instructor y muestra subtotales.
- La vista Blade `instructor-payments.blade.php` accede a `$payment['document_number']` y `$payment['payment_date']` — deberán venir del recibo vía eager load.
- `InstructorPaymentResource` edita registros individuales — el formulario de edición debe mostrar el recibo asociado como campo readonly si existe.
- El reporte ya implementa `HasActions` → la acción de registrar recibo encaja nativamente.

---

### Criterios de Aceptación

#### [DB] Base de datos

1. **[DB]** Existe la migración que crea `instructor_payment_receipts` con las columnas descritas en "Diseño de BD" y el constraint único `(instructor_id, monthly_period_id, payment_type)`.

2. **[DB]** Existe la migración que agrega `instructor_payment_receipt_id` a `instructor_payments` y elimina `document_number` y `payment_date` de esa tabla.

#### [UI] Rediseño de columnas del reporte — Tab Voluntarios

3. **[UI]** La columna **"Estado"** se elimina del reporte (tab Voluntarios y tab Por Horas). El estado de pago se infiere de si el instructor tiene recibo registrado o no — visible en el header.

4. **[UI — Bug fix]** El porcentaje `%` se elimina del header del instructor y pasa a ser **columna en cada fila** del tab Voluntarios. La columna se ubica entre "Inscritos" y "Tarifa". Cada fila muestra el `applied_volunteer_percentage` de ese `InstructorPayment`, no el del primer taller.
   - Estructura final de columnas (Voluntarios): `Taller | Horario | Inscritos | % | Tarifa | Ingresos | Por Pagar | Saldo a Favor`
   - Estructura final de columnas (Por Horas): `Taller | Horario | Inscritos | Tarifa | Honorarios/horas | Ingresos | Por Pagar | Saldo a Favor` *(sin cambios en este tab)*

5. **[UI]** El header del instructor (fila verde) muestra solo el nombre. A la derecha del nombre, en el mismo header:
   - Si **no tiene recibo**: botón **"Registrar Recibo"** (color primario/verde)
   - Si **ya tiene recibo**: texto `N° REC-001  •  30/06/2026` (sin botón)

6. **[UI]** Un instructor con talleres de % distintos (ej: Yoga 60%, Baile 40%) se agrupa en **un solo bloque** bajo su nombre. No se crean bloques separados por porcentaje. La columna `%` refleja el valor correcto por fila.

   Orden de filas dentro del bloque del instructor:
   - Primero por `applied_volunteer_percentage` **DESC** (mayor % primero)
   - Dentro del mismo %, orden alfabético por nombre de taller
   - Ejemplo: instructor con Yoga 60% (Lunes), Yoga 60% (Martes), Baile 40% → se muestran los dos Yoga primero, luego Baile

   Esto significa que las filas del mismo % quedan visualmente agrupadas (las del 60% juntas, las del 40% juntas) sin necesidad de separadores adicionales.

#### [UI] Acción — Registrar Recibo

7. **[UI]** El botón "Registrar Recibo" **NO existe** en el header de la página (acciones globales). La acción es siempre **por instructor**: el botón aparece únicamente en la fila verde del instructor, alineado a la derecha de su nombre. Esto garantiza que cada registro de recibo está explícitamente asociado a un instructor específico.

8. **[UI]** Al hacer clic en "Registrar Recibo" (en la fila del instructor), se abre un modal con:
   - Campo **Número de Recibo** (texto, requerido, max 50 chars)
   - Campo **Fecha de Pago** (fecha, requerido, default = hoy)
   - **Total a Pagar** del instructor (suma de `calculated_amount`, readonly, no editable)
   - Botón "Confirmar" y botón "Cancelar"

9. **[Lógica]** Al confirmar, el sistema:
   a. Crea un registro en `instructor_payment_receipts` con `instructor_id`, `monthly_period_id`, `payment_type`, `document_number`, `payment_date`, `total_amount` y `registered_by = Auth::id()`.
   b. Actualiza todos los `InstructorPayment` del instructor+período+tipo: asigna `instructor_payment_receipt_id` y cambia `payment_status = 'paid'`.
   c. Llama `loadAllInstructorPayments()` para refrescar la vista sin recargar página.

10. **[UI]** Tras registrar, el header del instructor reemplaza el botón por `N° {document_number}  •  {payment_date}`.

11. **[PDF/Excel]** El PDF y el Excel exportados reflejan las mismas columnas que la UI: Voluntarios sin columna Recibo/Estado, con columna `%` por fila; Por Horas sin columna Recibo/Estado. El Excel genera dos hojas separadas (Voluntarios / Por Horas).

#### [Borde]

12. **[Borde]** Si el constraint único falla al crear el recibo (race condition), notificación de error: "Ya existe un recibo registrado para este instructor en este período."

13. **[Borde]** Si un instructor tiene talleres `volunteer` y `hourly` en el mismo período, aparece en ambos tabs con headers independientes y botones de recibo independientes (RN-03).

---

### Permisos y Roles

| Rol | Puede ver reporte | Puede registrar recibo |
|---|---|---|
| `super_admin` | ✅ | ✅ |
| `Administrador` | ✅ | ✅ |
| `Cobranzas` | ✅ | ✅ |
| `Cajero` | ✅ | ❓ confirmar con usuario |
| `Secretaria` | ❌ | ❌ |
| `Delegado` | ❌ | ❌ |

> La página `AllInstructorsPaymentReport` tiene `$shouldRegisterNavigation = false` — acceso via link directo, no menú lateral. Los permisos del botón "Registrar Recibo" deben verificarse con `Auth::user()->hasAnyRole(['super_admin', 'Administrador', 'Cobranzas'])` o via policy.

---

### Dependencias

- **Datos existentes:** No hay registros de pago de profesores registrados en producción aún → no requiere script de migración de datos. Las columnas `document_number` y `payment_date` están vacías en todas las filas.
- **Otras HUs bloqueantes:** Ninguna. Esta HU es independiente.
- **Debe completarse antes de:** cualquier HU que agregue exportaciones o reportes PDF basados en recibos de instructor.

---

### Rollback

Si la migración falla en producción:

1. Ejecutar `php artisan migrate:rollback` — elimina `instructor_payment_receipts` y revierte `instructor_payments` a su estado anterior.
2. No hay pérdida de datos: `document_number` y `payment_date` estaban vacíos en todas las filas antes de esta HU.
3. El código de la app debe desplegarse junto con la migración en el mismo release — no desplegar lógica sin migración ni migración sin lógica.

---

### Definición de Listo (DoD)

- [ ] Migraciones corren sin error en local y en staging: `php artisan migrate`
- [ ] Rollback funciona: `php artisan migrate:rollback`
- [ ] `InstructorPaymentService::recalculatePaymentsForPeriod()` no resetea `payment_status` de filas con recibo ya registrado
- [ ] `InstructorPaymentResource` no lanza error al editar un registro (campos eliminados reemplazados)
- [ ] `InstructorPaymentResource` acción `mark_as_paid` crea `InstructorPaymentReceipt` correctamente
- [ ] Columna "Estado" eliminada de ambos tabs
- [ ] Columna "%" aparece por fila en tab Voluntarios mostrando el % correcto de cada `InstructorPayment` (no del primero)
- [ ] Instructor con % distintos por taller aparece en un solo bloque (no se divide en dos grupos)
- [ ] Header del instructor muestra solo nombre + botón recibo (o N° recibo si ya pagado)
- [ ] Botón "Registrar Recibo" aparece en header de instructor sin recibo
- [ ] Botón no aparece (muestra N° y fecha) si ya fue registrado
- [ ] Modal registra recibo y refresca vista sin recargar página
- [ ] PDF generado muestra `document_number` y `payment_date` del recibo (no vacíos)
- [ ] Constraint único `(instructor_id, monthly_period_id, payment_type)` rechaza duplicados
- [ ] Roles `Administrador` y `Cobranzas` pueden registrar recibo; `Secretaria` y `Delegado` no ven el botón

---

### Plan de Implementación (orden obligatorio)

> ⚠️ Las fases 2 y 3 dependen de que la fase 1 esté completa. Implementar en orden estricto.

---

#### FASE 1 — Migración de base de datos

**No tocar lógica aún. Solo DB + modelos.**

1. Crear migración: tabla `instructor_payment_receipts`
   - Columnas: `id`, `instructor_id` (FK), `monthly_period_id` (FK), `payment_type` (enum), `document_number` (string), `payment_date` (date), `total_amount` (decimal 10,2), `registered_by` (FK users nullable), `notes` (text nullable), `timestamps`
   - Constraint único: `UNIQUE(instructor_id, monthly_period_id, payment_type)`

2. Crear migración: modificar `instructor_payments`
   - Agregar: `instructor_payment_receipt_id` (FK nullable → `instructor_payment_receipts`, onDelete SET NULL)
   - Eliminar: `document_number`
   - Eliminar: `payment_date`
   - Mantener: `payment_status` (se sigue usando, se sincroniza al crear/eliminar recibo)

3. Crear modelo `app/Models/InstructorPaymentReceipt.php`
   - `$fillable`: todos los campos de la tabla
   - `hasMany(InstructorPayment::class)`
   - `belongsTo(Instructor::class)`, `belongsTo(MonthlyPeriod::class)`, `belongsTo(User::class, 'registered_by')`

4. Actualizar modelo `app/Models/InstructorPayment.php`
   - Agregar `belongsTo(InstructorPaymentReceipt::class)`
   - Remover `document_number` y `payment_date` de `$fillable` y `$casts`

---

#### FASE 2 — Lógica rota por la migración (arreglar antes de tocar UI)

**Estos archivos fallarán con columnas inexistentes si no se corrigen:**

5. **`app/Services/InstructorPaymentService.php`** — `calculateVolunteerPayment()` y `calculateHourlyPayment()`
   - Problema: `updateOrCreate` siempre setea `payment_status = 'pending'` → borra estado si hay recibo
   - Fix: quitar `payment_status` del array de `updateOrCreate`; solo setearlo en `creating` (via model boot o condición `wasRecentlyCreated`)

6. **`app/Filament/Resources/InstructorPaymentResource.php`** — acción `mark_as_paid`
   - Problema: escribe `document_number` y `payment_date` directamente en la fila → columnas eliminadas
   - Fix: reemplazar acción → crea `InstructorPaymentReceipt` para el instructor+período+tipo completo (igual que el botón del reporte general) y sincroniza `payment_status = 'paid'` en todas las filas del grupo

7. **`app/Filament/Resources/InstructorPaymentResource.php`** — formulario Edit
   - Problema: campos `payment_date` y `document_number` referencian columnas eliminadas
   - Fix: reemplazar por campos readonly que leen de `receipt.document_number` y `receipt.payment_date` vía relación; visibles solo si `$record->instructorPaymentReceipt !== null`

8. **`app/Filament/Resources/InstructorPaymentResource.php`** — columna tabla `payment_date`
   - Problema: `Tables\Columns\TextColumn::make('payment_date')` → columna eliminada
   - Fix: `Tables\Columns\TextColumn::make('instructorPaymentReceipt.payment_date')`

---

#### FASE 3 — Feature nueva: botón "Registrar Recibo" en el reporte

**Solo después de que FASE 1 y 2 funcionan sin errores.**

9. **`app/Filament/Pages/AllInstructorsPaymentReport.php`**
   - Agregar `instructorPaymentReceipt` al `with([...])` en `loadAllInstructorPayments()`
   - Cambiar mapeo: `'document_number' => $payment->instructorPaymentReceipt?->document_number`
   - Cambiar mapeo: `'payment_date' => $payment->instructorPaymentReceipt?->payment_date`
   - Agregar al array del instructor: `'has_receipt' => bool`, `'receipt_document' => string|null`
   - Nueva `Action::make('registerReceipt')` con `form()` inline (campos: `document_number`, `payment_date`), recibe `instructor_id` + `payment_type` como argumentos
   - En el `action()`: `DB::transaction()` → crear `InstructorPaymentReceipt` → `InstructorPayment::where(...)->update(['payment_status' => 'paid', 'instructor_payment_receipt_id' => $receipt->id])` → `$this->loadAllInstructorPayments()`
   - **Orden de talleres dentro del bloque del instructor (tab Voluntarios):** sort por `applied_volunteer_percentage` DESC primero, luego por `workshop_name` ASC. Reemplaza el sort actual (que era solo por nombre). Implementar en el `usort()` de `loadAllInstructorPayments()`:
     ```php
     usort($instructorData['workshops'], function ($a, $b) {
         $pctCmp = $b['volunteer_percentage'] <=> $a['volunteer_percentage']; // DESC
         if ($pctCmp !== 0) return $pctCmp;
         $n = strcmp($a['workshop_name'], $b['workshop_name']);
         if ($n !== 0) return $n;
         return strcmp($a['schedule'], $b['schedule']);
     });
     ```

10. **`resources/views/filament/pages/all-instructors-payment-report.blade.php`**
    - La fila header del instructor mantiene `colspan` completo pero el `<td>` usa `flex justify-between items-center` para nombre izquierda y recibo derecha:
      ```html
      <tr class="ipr-instructor-header">
          <td colspan="8" class="px-3 py-2 flex items-center justify-between">
              <span>{{ $instructor['instructor_name'] }}</span>
              @if ($instructor['has_receipt'])
                  <span class="text-sm font-normal text-green-800">
                      N° {{ $instructor['receipt_document'] }}  •  {{ $instructor['receipt_date'] }}
                  </span>
              @else
                  {{-- Botón Livewire action --}}
                  <x-filament::button
                      size="sm"
                      wire:click="mountAction('registerReceipt', { instructor_id: {{ $instructor['instructor_id'] }}, payment_type: '{{ $type }}' })"
                  >
                      Registrar Recibo
                  </x-filament::button>
              @endif
          </td>
      </tr>
      ```
    - Eliminar el `<span>` del % del header (ya no va aquí)
    - Eliminar columna "Estado" del `<thead>` y de cada fila `<td>` en ambos tabs
    - Agregar columna "%" en `<thead>` de tab Voluntarios (entre "Inscritos" y "Tarifa") y su `<td>` correspondiente con `$workshop['volunteer_percentage']`
    - Ajustar `colspan` del subtotal y footer al nuevo número de columnas
    - Skill `filament-blade-styling` para cualquier clase de color nueva en Blade

11. **PDF mapping** (`AllInstructorsPaymentReport::generatePDF()` y `resources/views/reports/instructor-payments.blade.php`)
    - `$payment['document_number']` y `$payment['payment_date']` ya vienen del mapeo corregido en paso 9
    - No requiere cambio en la vista Blade si el array llega con los mismos keys
