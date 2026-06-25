# Bugs — Módulo de Inscripciones

---

## BUG-I01 — Doble descuento en replicación de inscripciones (meses con feriados)

**Estado:** Resuelto  
**Fecha:** 2026-06-23  
**Afectado:** `EnrollmentReplicationService::replicateEnrollment()`

### Síntoma

Inscripciones replicadas automáticamente para Julio 2026 mostraban un precio menor al del taller. Ejemplo: ACTIVIDAD FISICA (3 clases, tarifa S/ 18.75) → inscripción creada con S/ 16.88.

### Causa raíz

El servicio detectaba `workshop.number_of_classes (3) < workshopTemplate.number_of_classes (4)` e interpretaba el mes como "mes con feriado". Aplicaba entonces la fórmula:

```
finalPrice = standard_monthly_fee / templateClasses × surchargeMultiplier × actualClasses
           = 18.75 / 4 × 1.20 × 3
           = 16.88  ← INCORRECTO
```

El problema: el admin ya había ajustado `standard_monthly_fee` a 18.75 para reflejar el precio correcto de 3 clases. La fórmula lo dividió nuevamente por 4 (clases del template), causando un segundo descuento sobre un fee ya reducido.

### Fix

`EnrollmentReplicationService::replicateEnrollment()` — eliminada la rama holiday. Siempre usa tarifa flat:

```php
$finalPrice = round((float) $workshop->standard_monthly_fee * $student->inscription_multiplier, 2);
```

El `standard_monthly_fee` del taller mensual ES el precio correcto del mes. Si hubo feriados, el admin ya lo ajustó antes de que corra la replicación de inscripciones.

### Corrección de datos

225 inscripciones de Julio 2026 con precio incorrecto fueron corregidas mediante:

```
database/migrations/2026_06_23_111352_fix_july_2026_enrollment_prices_double_discount.php
```

La migración recalcula `total_amount` y `price_per_quantity` en `student_enrollments` y ajusta `total_amount` en los `enrollment_batches` correspondientes.

### Talleres afectados (ejemplos)

| Taller | num_classes | fee (correcto) | total almacenado | total correcto |
|--------|------------|----------------|-----------------|----------------|
| ACTIVIDAD FISICA | 3 | 18.75 | 16.88 | 18.75 |
| EJERCICIOS EN EL AGUA | 3 | 45.00 | 40.50 | 45.00 |
| TECLADO | 3 | 45.00 | 40.50 | 45.00 |
| BAILE EN SILLA | 3 | 18.75 | 16.88 | 18.75 |

---

## BUG-I02 — División por cero en asignación de clases por defecto

**Estado:** Resuelto  
**Fecha:** 2026-06-23  
**Afectado:** `EnrollmentReplicationService::createDefaultEnrollmentClasses()`

### Síntoma

Edge case potencial: si una inscripción llega a `createDefaultEnrollmentClasses()` con `number_of_classes = 0`, PHP lanza `DivisionByZeroError` y la replicación del batch completo falla.

### Causa raíz

```php
// Sin protección contra número de clases cero
$pricePerClass = $enrollment->total_amount / $enrollment->number_of_classes;
```

### Fix

```php
$pricePerClass = $enrollment->number_of_classes > 0
    ? $enrollment->total_amount / $enrollment->number_of_classes
    : 0;
```

---

## BUG-I03 — `replicateFromPeriodToNext()` no actualiza `number_of_classes` tras feriados

**Estado:** Resuelto  
**Fecha:** 2026-06-23  
**Afectado:** `WorkshopReplicationService::replicateFromPeriodToNext()`

### Síntoma

Cuando se usa `replicateFromPeriodToNext()` en un mes con feriados, el taller replicado conserva el `number_of_classes` del mes anterior aunque solo se hayan generado N-1 clases `scheduled`. Resultado: `student_enrollments.number_of_classes = 4` pero solo 3 `EnrollmentClass` asignadas.

### Causa raíz

`replicateFromTemplates()` ya incluía el bloque de actualización post-generación:

```php
$actualClasses = $workshop->workshopClasses()->where('status', 'scheduled')->count();
if ($actualClasses > 0 && $actualClasses !== $workshop->number_of_classes) {
    $workshop->update(['number_of_classes' => $actualClasses]);
}
```

`replicateFromPeriodToNext()` no lo tenía.

### Fix

Mismo bloque agregado en `replicateFromPeriodToNext()` después de `generateClassesForWorkshopAndPeriod()`.

> **Nota:** Al actualizar `number_of_classes`, el `WorkshopObserver` dispara `syncPricing()` que regenera los registros de `WorkshopPricing` con el conteo correcto. Comportamiento esperado — no hay loop porque `syncPricing()` solo escribe en `workshop_pricings`, no en `workshops`.
