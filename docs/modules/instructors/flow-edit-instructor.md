# Flujo: Creación / Edición de Instructor

> Wizard de 4 pasos para registrar o modificar un instructor y sus talleres asignados.

---

## Flujo General del Wizard

```mermaid
flowchart TD
    START([Administrador abre\nCrear / Editar Instructor])

    subgraph STEP1["Paso 1 — Datos Personales"]
        S1A[Ingresar apellidos, nombres,\ndocumento, teléfono, dirección, foto]
    end

    subgraph STEP2["Paso 2 — Ficha Médica"]
        S2A[Ingresar datos médicos\ndel instructor]
    end

    subgraph STEP3["Paso 3 — Talleres y Modalidad de Pago"]
        S3A[Repeater: lista de talleres asignados]
        S3B{¿Agregar taller?}
        S3C[Seleccionar Workshop\nNombre · Horario · Período · Modalidad]
        S3D[Horario se actualiza\nautomáticamente]
        S3E{Tipo de pago}
        S3F[Ingresar\nPorcentaje %\ncustom_volunteer_percentage]
        S3G[Ingresar\nTarifa por hora S/\nhourly_rate]
        S3H{¿Eliminar fila?}
        S3I[Fila eliminada\ndel repeater]
        S3J[¿Agregar otra?]
    end

    subgraph STEP4["Paso 4 — Declaración Jurada y Resumen"]
        S4A[Revisar resumen del instructor\ny talleres asignados]
        S4B[Confirmar / Guardar]
    end

    subgraph PERSIST["Persistencia"]
        DB1[(instructors)]
        DB2[(instructor_workshops\nuna fila por taller asignado)]
    end

    START --> STEP1
    S1A --> STEP2
    S2A --> STEP3
    S3A --> S3B
    S3B -->|Sí| S3C
    S3C --> S3D
    S3D --> S3E
    S3E -->|volunteer| S3F
    S3E -->|hourly| S3G
    S3F --> S3H
    S3G --> S3H
    S3H -->|Sí| S3I
    S3H -->|No| S3J
    S3J -->|Sí| S3C
    S3J -->|No| STEP4
    S3B -->|No| STEP4
    S4A --> S4B
    S4B --> DB1
    S4B --> DB2
```

---

## Lógica de Cálculo de Pago (posterior al wizard)

```mermaid
flowchart TD
    TRIGGER([Sistema genera pagos mensuales\nInstructorPaymentService])

    FETCH[Cargar InstructorWorkshop\nde período vigente]

    CHECK{payment_type}

    subgraph VOL["Modalidad Voluntario"]
        V1{custom_volunteer_percentage\nes null?}
        V2[Usar MonthlyInstructorRate\n.volunteer_percentage]
        V3[Usar custom_volunteer_percentage]
        V4[Calcular:\nmonthly_revenue × pct]
    end

    subgraph HOUR["Modalidad Por Horas"]
        H1[Usar hourly_rate\nde InstructorWorkshop]
        H2[Calcular:\ntotal_hours × hourly_rate]
    end

    SAVE[(InstructorPayment\ncalculated_amount\napplied_volunteer_percentage\napplied_hourly_rate\ntotal_hours)]

    TRIGGER --> FETCH
    FETCH --> CHECK
    CHECK -->|volunteer| V1
    V1 -->|Sí| V2
    V1 -->|No| V3
    V2 --> V4
    V3 --> V4
    V4 --> SAVE
    CHECK -->|hourly| H1
    H1 --> H2
    H2 --> SAVE
```

---

## Modelo de Datos Involucrado

```mermaid
erDiagram
    INSTRUCTOR {
        bigint id PK
        string last_names
        string first_names
        string document_number
    }

    INSTRUCTOR_WORKSHOP {
        bigint id PK
        bigint instructor_id FK
        bigint workshop_id FK
        enum payment_type "volunteer | hourly"
        decimal custom_volunteer_percentage "nullable"
        decimal hourly_rate "nullable"
        boolean is_active
    }

    WORKSHOP {
        bigint id PK
        bigint monthly_period_id FK
        string name
        json day_of_week
        time start_time
        time end_time
        decimal standard_monthly_fee
    }

    MONTHLY_PERIOD {
        bigint id PK
        int year
        int month
    }

    MONTHLY_INSTRUCTOR_RATE {
        bigint id PK
        bigint monthly_period_id FK
        decimal volunteer_percentage
    }

    INSTRUCTOR_PAYMENT {
        bigint id PK
        bigint instructor_id FK
        bigint instructor_workshop_id FK
        bigint monthly_period_id FK
        enum payment_type
        decimal calculated_amount
        decimal applied_volunteer_percentage
        decimal applied_hourly_rate
        decimal total_hours
        decimal monthly_revenue
    }

    INSTRUCTOR ||--o{ INSTRUCTOR_WORKSHOP : "dicta"
    WORKSHOP ||--o{ INSTRUCTOR_WORKSHOP : "asignado a"
    MONTHLY_PERIOD ||--o{ WORKSHOP : "pertenece a"
    MONTHLY_PERIOD ||--o{ MONTHLY_INSTRUCTOR_RATE : "tiene tasa"
    INSTRUCTOR ||--o{ INSTRUCTOR_PAYMENT : "recibe"
    INSTRUCTOR_WORKSHOP ||--o{ INSTRUCTOR_PAYMENT : "genera"
    MONTHLY_PERIOD ||--o{ INSTRUCTOR_PAYMENT : "del período"
```

---

## Archivos Clave

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Filament/Resources/InstructorResource.php` | Wizard y form schema (pasos 1–4) |
| `app/Models/Instructor.php` | Modelo principal, relaciones |
| `app/Models/InstructorWorkshop.php` | Junction; lógica `getEffectiveVolunteerPercentage()` |
| `app/Models/Workshop.php` | Taller base con horario y precio |
| `app/Models/InstructorPayment.php` | Pago mensual generado |
| `app/Services/InstructorPaymentService.php` | Cálculo de pagos volunteer/hourly |
| `app/Models/MonthlyInstructorRate.php` | Porcentaje mensual por defecto |
