# Historias de Usuario — Módulo de Inscripciones

> Historias específicas del módulo de inscripciones. Para el índice general ver `docs/user-stories.md`.

---

## HU-I08: Inscripción por Recuperación o Motivo (Pago Cero)

**Rol:** Administrador
**Acción:** Crear una inscripción de tipo "recuperación" con monto total $0 seleccionando un motivo del catálogo
**Beneficio:** Permitir que un estudiante asista a un taller sin generar cobro, con trazabilidad del motivo y control sobre comportamiento en automatizaciones

### Descripción

Como administrador, quiero una tercera opción de tipo de inscripción llamada **"Por Recuperación / Motivo"** (`recovery`) para que el estudiante pueda ser inscrito en un taller con pago cero, generando un ticket de $0 como comprobante y registrando el motivo desde un catálogo administrable.

Casos de uso típicos:
- El estudiante faltó una clase y se le permite recuperarla en otro horario sin costo
- Cortesía institucional o acuerdo especial
- Corrección de inscripción previa
- Invitado puntual a una sesión

---

### Criterios de aceptación

#### Flujo de inscripción
- [ ] En el wizard de inscripción existe un toggle/checkbox **"¿Es recuperación?"** independiente del tipo de clase (`enrollment_type`)
- [ ] Al activar recuperación, el campo `total_amount` se fija en `0.00` y no es editable
- [ ] Al activar recuperación, se muestra selector obligatorio **"Motivo"** que lista solo `Tag` con `context = 'recovery_reason'` activos
- [ ] El `EnrollmentBatch` resultante tiene `total_amount = 0` y `payment_status = completed` directamente al crearse (no queda en `pending`)
- [ ] Se genera un `Ticket` con `amount = 0` marcado visualmente como tipo recuperación (no es un ticket de cobro)
- [ ] El estudiante inscrito con tipo `recovery` aparece en la lista de asistencia del taller igual que cualquier inscripción normal
- [ ] En la vista de lista de `EnrollmentBatchResource` se muestra badge visual diferenciado para tipo `recovery`

#### Comportamiento en automatizaciones (controlado por flags del Tag seleccionado)
- [ ] Si el tag tiene `excludes_from_replication = true` → la inscripción **no se replica** al mes siguiente
- [ ] Si el tag tiene `excludes_from_instructor_revenue = true` → la inscripción **no cuenta** en el cálculo de revenue del instructor
- [ ] Las inscripciones `recovery` **no se auto-cancelan** el día configurado (ya están en `completed`)

#### Gestión del catálogo de Tags
- [ ] Existe un recurso Filament (`TagResource`) para administrar el catálogo de tags
- [ ] Al crear/editar un tag se configura: nombre, contexto (`context`), y las flags de comportamiento
- [ ] Solo tags con `context = 'recovery_reason'` aparecen como opciones al crear inscripción `recovery`

---

### Diseño de BD — Tabla `tags` (catálogo maestro)

```sql
tags
  id
  name                              varchar   -- ej: "Recuperación de clase", "Cortesía staff"
  context                           varchar   -- 'recovery_reason' | 'cancellation_reason' | etc.
  excludes_from_instructor_revenue  boolean   default false
  excludes_from_replication         boolean   default false
  is_active                         boolean   default true
  timestamps
```

```sql
-- FK directa en student_enrollments (un solo motivo por inscripción recovery)
student_enrollments
  ...
  recovery_tag_id   FK → tags.id  nullable
```

> **¿Por qué FK directa y no pivot polimórfico?**
> La inscripción `recovery` requiere exactamente **un** motivo. FK directa es más simple, performante, y evita ambigüedad. La tabla `tags` sigue siendo reutilizable para otros contextos futuros mediante FKs en otros modelos o un pivot `taggables` si se necesita multi-tag.

---

### Campos nuevos requeridos

| Campo | Tabla | Tipo | Descripción |
|-------|-------|------|-------------|
| `recovery_tag_id` | `student_enrollments` | `FK nullable` | Tag/motivo seleccionado; su presencia (`IS NOT NULL`) es el discriminador de inscripción recovery |
| `tags` | nueva tabla | — | Catálogo maestro de tags con flags de comportamiento |

> **`enrollment_type` no cambia.** Sigue controlando selección de clases (`full_month` / `specific_classes`). El hecho de que sea recuperación lo determina `recovery_tag_id IS NOT NULL`, no un tercer valor en el enum.

---

### Reglas de negocio

- `recovery_tag_id IS NOT NULL` bypasea cálculo de precio: `total_amount = 0`, ignora multiplicadores de categoría del estudiante
- Batch con inscripciones `recovery` nace en `completed` (no pasa por flujo de pago)
- Si batch mezcla inscripciones normales + `recovery`: total del batch excluye las `recovery`; las normales siguen flujo habitual
- Validación de mantenimiento al día **sí aplica** (igual que cualquier inscripción)
- Validación de cupo **sí aplica** (la inscripción ocupa un lugar en el taller)
- Ticket generado tiene `amount = 0`; se registra igual en `tickets` para trazabilidad
- El comportamiento en automatizaciones lo determinan las **flags del tag**, no el `enrollment_type` directamente:
  - `excludes_from_replication` → `EnrollmentReplicationService` omite el enrollment
  - `excludes_from_instructor_revenue` → `InstructorPaymentService` omite el enrollment del revenue base

---

### Flujo esperado

```
Wizard de inscripción
  ├── Paso: Tipo de clase (enrollment_type — sin cambio)
  │     ├── full_month       → todas las clases del mes
  │     └── specific_classes → clases específicas seleccionadas
  │
  └── Paso: ¿Es recuperación?
        ├── No  → precio calculado normal según enrollment_type
        └── Sí  → selector "Motivo" (tags context=recovery_reason, obligatorio)
                  recovery_tag_id se asigna
                  total_amount = 0 (fijo, ignora enrollment_type)
                  batch nace en completed
                  genera Ticket con amount = $0
                  automatizaciones leen flags del tag seleccionado
```

---

### Impacto en otros módulos

| Módulo | Impacto |
|--------|---------|
| Reporte de ingresos | No contar inscripciones `recovery` como ingreso (total_amount = 0) |
| Pago a instructores (voluntario) | Leer flag `excludes_from_instructor_revenue` del tag para excluir del revenue |
| Auto-replicación mensual | `EnrollmentReplicationService` leer flag `excludes_from_replication` del tag |
| Auto-cancelación día 28 | No aplica — batch nace `completed` |
| Lista de asistencia | Sin cambio — aparece normalmente |
| Tickets | Genera ticket con `amount = 0`, mismo flujo pero monto cero |

---

### Conexión con el código

| Archivo | Cambio |
|---------|--------|
| Nueva migración | Crear tabla `tags` + columna `recovery_tag_id` en `student_enrollments` (enum `enrollment_type` no cambia) |
| `app/Models/StudentEnrollment.php` | Relación `recoveryTag()` → `Tag` |
| `app/Models/Tag.php` | Nuevo modelo con scopes por context |
| `app/Services/EnrollmentBatchService.php` | Lógica de precio cero para `recovery` |
| `app/Services/EnrollmentReplicationService.php` | Filtrar por `tag.excludes_from_replication` |
| `app/Services/InstructorPaymentService.php` | Filtrar por `tag.excludes_from_instructor_revenue` |
| `app/Filament/Resources/EnrollmentBatchResource.php` | Toggle "¿Es recuperación?" + selector de tag en wizard |
| `app/Filament/Resources/TagResource.php` | Nuevo recurso para gestión del catálogo |

---
