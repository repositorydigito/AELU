# Estructura de Reportes

Mapeo completo entre cada reporte del panel, su clase de exportación Excel y su vista PDF.

## Convención de archivos

| Tipo | Ubicación |
|------|-----------|
| Page PHP (lógica + filtros) | `app/Filament/Pages/*Report.php` |
| Export Excel | `app/Exports/*Export.php` |
| Vista PDF (Dompdf) | `resources/views/reports/*.blade.php` |
| Vista panel Livewire | `resources/views/filament/pages/*.blade.php` ← **NO son PDF** |

## Tabla de relación

| Reporte (Page) | Título en UI | Excel (`app/Exports/`) | PDF (`resources/views/reports/`) |
|----------------|--------------|------------------------|----------------------------------|
| `AllInstructorsPaymentReport` | Pago Profesores - General | `AllInstructorsPaymentExport.php` | `all-instructors-payment.blade.php` |
| `AllUsersEnrollmentReport` | Inscripciones - Reporte General | `AllUsersEnrollmentExport.php` | `all-users-enrollment.blade.php` |
| `CashiersEnrollmentReport` | Inscripciones por Cajero | `CashiersEnrollmentExport.php` | `cashiers-enrollment.blade.php` |
| `EnrollmentsReport1` | Inscripciones por Alumno | `StudentEnrollmentsExport.php` | `student-enrollments.blade.php` |
| `EnrollmentsReport2` | Inscripciones por Mes | `MonthlyEnrollmentsExport.php` | `monthly-enrollments.blade.php` |
| `InstructorKardexReport` | Kardex por Profesor | `InstructorKardexExport.php` | `instructor-kardex.blade.php` |
| `InstructorPaymentsReport` | Reporte de Pagos por Profesor | `InstructorPaymentExport.php` | `instructor-payments.blade.php` |
| `MonthlyInstructorReport` | Reporte Mensual de Inscripciones | `MonthlyInstructorExport.php` | `monthly-instructors.blade.php` |
| `ScheduleEnrollmentReport` | Inscripciones por Horario | `ScheduleEnrollmentExport.php` | `schedule-enrollment.blade.php` |

## Módulo aparte (no está en Reports)

| Page | Excel | PDF |
|------|-------|-----|
| `AttendanceManagement` | `AttendanceExport.php` | `attendance.blade.php` |

## Al agregar un nuevo reporte

1. Crear `app/Filament/Pages/NombreReport.php`
2. Crear `app/Exports/NombreExport.php`
3. Crear `resources/views/reports/nombre.blade.php`
4. Crear `resources/views/filament/pages/nombre-report.blade.php`
5. Agregar fila a esta tabla
