# User Model — Campo `enrollment_code`

## Qué es

`enrollment_code` es un código de 3 dígitos (string, zero-padded) asignado automáticamente a cada usuario del sistema al momento de su creación. Sirve como **prefijo identificador** para todos los tickets de pago emitidos por ese usuario.

## Auto-generación

**Archivo:** `app/Models/User.php` → método `boot()`, evento `created`

- Se genera **solo para usuarios sin rol `Delegado`**
- Si el usuario ya tiene un `enrollment_code`, no se sobreescribe
- Algoritmo: consulta el último `enrollment_code` no nulo ordenado descendente, incrementa en 1 y zero-padea a 3 dígitos
- Si no existe ninguno previo, inicia en `'001'`
- Usa `saveQuietly()` para evitar re-disparar eventos

```
001 → 002 → 003 → ... → 999
```

## Base de datos

- **Migración:** `database/migrations/2025_10_10_105941_add_enrollment_code_to_users_table.php`
- **Tipo:** `string(3)`, nullable, unique
- **Índice:** tiene index para performance en queries de tickets

## Dependencias directas

### `app/Services/EnrollmentPaymentService.php`

| Método | Uso | Formato ticket resultante |
|--------|-----|--------------------------|
| `generateTicketCode(int $userId)` | Pago en efectivo (cash) | `{enrollment_code}-{6-digit-seq}` ej: `002-000019` |
| `generateTicketCodeForLink(int $userId, string $manualCode)` | Pago por link | `{enrollment_code}-{batch_code}` ej: `002-B001-9827` |

Ambos métodos lanzan `Exception` si el usuario no tiene `enrollment_code` configurado.

**Flujo en `processPayment()`:** según `payment_method` ('cash' o 'link'), enruta a uno de los dos métodos anteriores.

## Reglas de negocio

- Campo **inmutable** una vez asignado — no editar manualmente
- Usuarios con rol `Delegado` NO reciben código (no emiten tickets directamente)
- El código es único por usuario y sirve para identificar rápidamente qué cajero emitió un ticket
- Rango teórico: `001`–`999` (máximo 999 usuarios con código)

## UI

Visible en `/admin/users` como columna "Código" (badge) y en el formulario de edición como campo read-only "Código de inscripción". Registrado via `AppServiceProvider::boot()` usando la API de `tomatophp/filament-users`.
