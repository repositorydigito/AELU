# Historias de Usuario — Índice General

> Las HU están organizadas por módulo en `docs/modules/`. Este archivo es el índice de navegación.
> Referencia rápida: menciona el ID (ej. `HU-I02`) en el chat para cargar el contexto completo.

---

## Módulo de Inscripciones

Doc: `docs/modules/inscriptions/user-stories.md`

| ID | Historia | Categoría |
|----|----------|-----------|
| HU-I01 | Listar inscripciones | CRUD |
| HU-I02 | Iniciar inscripción (seleccionar período + estudiante) | Wizard — Paso 1 |
| HU-I03 | Seleccionar talleres disponibles | Wizard — Paso 2 |
| HU-I04 | Configurar detalles + precios | Wizard — Paso 3a |
| HU-I05 | Pago y finalización → genera Ticket | Wizard — Paso 3b |
| HU-I06 | Exportar inscripciones a Excel | CRUD |
| HU-I07 | Replicación automática al siguiente mes | Automatización |
| HU-I08 | Inscripción por recuperación (pago cero) | Caso especial |

## Módulo de Pagos

Doc: `docs/modules/payments/full-payment-flow.md` y `partial-payment-flow.md`

| Flujo | Descripción |
|-------|-------------|
| Pago total | Cobro de todas las inscripciones en una transacción (cash o link) |
| Pago parcial | Cobro de subset de inscripciones; lote en `to_pay`; protegido de auto-cancel |

## Módulo de Instructores

Doc: `docs/modules/instructors/user-stories.md`

## Módulo de Reportes

Doc: `docs/modules/reports/user-stories.md`
