# Resolución del Porcentaje Voluntario — Pagos de Instructor

> Cómo el sistema determina el `%` a aplicar cuando calcula el pago mensual de un instructor voluntario.

---

## Lógica de Resolución

Cuando `StudentEnrollmentObserver::calculateAndSaveInstructorPayment()` se ejecuta (bloque `payment_type == 'volunteer'`), resuelve el porcentaje en este orden:

```
InstructorWorkshop.custom_volunteer_percentage
        │
        ├── NOT NULL  →  usar custom / 100  (ej: 60.00 → 0.60)
        │
        └── NULL      →  usar MonthlyInstructorRate.volunteer_percentage
                                    │
                                    ├── existe  →  usar valor decimal  (ej: 0.5000 → 50%)
                                    │
                                    └── NULL    →  $appliedPercentage = null → monto = 0
```

El método que encapsula esta lógica:

```php
// app/Models/InstructorWorkshop.php
public function getEffectiveVolunteerPercentage(?MonthlyInstructorRate $monthlyRate = null): ?float
{
    if ($this->isVolunteer()) {
        if ($this->custom_volunteer_percentage !== null) {
            return $this->custom_volunteer_percentage / 100; // 60.00 → 0.60
        }
        return $monthlyRate?->volunteer_percentage; // ya en decimal: 0.5000
    }
    return null;
}
```

---

## Escalas de Almacenamiento (importante)

| Campo | Tabla | Tipo | Ejemplo almacenado | Significado |
|-------|-------|------|--------------------|-------------|
| `custom_volunteer_percentage` | `instructor_workshops` | `decimal(5,2)` | `60.00` | 60% |
| `volunteer_percentage` | `monthly_instructor_rates` | `decimal(5,4)` | `0.5000` | 50% |
| `applied_volunteer_percentage` | `instructor_payments` | decimal | `0.6000` | 60% (normalizado al guardar) |

**Regla:** `getEffectiveVolunteerPercentage()` siempre retorna decimal (0.0–1.0). El service guarda ese decimal directamente en `InstructorPayment.applied_volunteer_percentage`.

El reporte multiplica por 100 para mostrar: `applied_volunteer_percentage * 100 = %`.

---

## Dónde se Configura Cada Valor

### `custom_volunteer_percentage` — por instructor/taller
- **UI:** Wizard de instructor → Paso 3 "Talleres y Modalidad de Pago" → campo "Porcentaje de Pago (%)"
- **Scope:** específico para ese `InstructorWorkshop` (instructor + taller)
- **Precedencia:** ALTA — si está seteado, ignora la tasa mensual por defecto

### `MonthlyInstructorRate.volunteer_percentage` — tasa mensual por defecto
- **UI:** ⚠️ **No existe UI en el panel.** Solo configurable via DB o Tinker
- **Scope:** aplica a TODOS los instructores voluntarios del período que no tengan `custom_volunteer_percentage`
- **Precedencia:** BAJA — fallback cuando no hay porcentaje personalizado

---

## Bug histórico (ya no aplica) — `getActiveInstructorRate()` no definido

Este bug existía en `app/Services/InstructorPaymentService.php` (llamaba a un método `MonthlyPeriod::getActiveInstructorRate()` que nunca existió). Ese archivo era **código muerto** (0 llamadores, confirmado 2026-07) y fue **eliminado**. La ruta real (`StudentEnrollmentObserver::calculateAndSaveInstructorPayment()`) nunca tuvo este bug — resuelve la tasa mensual con una query directa:

```php
$monthlyRate = MonthlyInstructorRate::where('monthly_period_id', $monthlyPeriodId)
    ->where('is_active', true)
    ->first();
```

---

## Caso: Pago Muestra % Incorrecto

Si el reporte muestra un porcentaje distinto al configurado en el wizard:

1. El `InstructorPayment` fue generado **antes** de asignar `custom_volunteer_percentage`
2. En ese momento cayó al fallback de `MonthlyInstructorRate`
3. El pago quedó con `applied_volunteer_percentage` del fallback
4. Cambiar el % en el wizard **no recalcula** pagos existentes

**Solución:** regenerar el `InstructorPayment` para ese instructor/período desde el panel de pagos.

---

## Archivos Clave

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Models/InstructorWorkshop.php` | `getEffectiveVolunteerPercentage()` — resolución con normalización de escala |
| `app/Models/MonthlyInstructorRate.php` | Tasa mensual por defecto (`volunteer_percentage` en decimal) |
| `app/Observers/StudentEnrollmentObserver.php` | `calculateAndSaveInstructorPayment()` — usa el método anterior, guarda `applied_volunteer_percentage` |
| `app/Models/InstructorPayment.php` | Almacena `applied_volunteer_percentage` como decimal histórico |
