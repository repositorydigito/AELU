<?php

namespace App\Exports;

use App\Models\EnrollmentBatch;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrollmentBatchExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = EnrollmentBatch::query()
            ->with([
                'student', 
                'creator',
                'enrollments.instructorWorkshop.workshop',
                'enrollments.instructorWorkshop.instructor'
            ]);

        // Aplicar filtros si existen
        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }

        if (!empty($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        if (!empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }

        // Filtros de fecha de inscripción
        if (!empty($this->filters['enrollment_from'])) {
            $query->whereDate('enrollment_date', '>=', $this->filters['enrollment_from']);
        }

        if (!empty($this->filters['enrollment_until'])) {
            $query->whereDate('enrollment_date', '<=', $this->filters['enrollment_until']);
        }

        return $query->orderBy('updated_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Estudiante',
            'Código de Estudiante',
            'Usuario que Inscribió',
            'Talleres',
            'Cantidad de Talleres',
            'Total de Clases',
            'Fecha de Inscripción',
            'Estado de Pago',
            'Método de Pago',
            'Monto Total (S/)',
            'Monto Pagado (S/)',
            'Vuelto (S/)',
            'Nº Ticket',
            'Fecha de Pago',
            'Registrado por',
            'Notas',
        ];
    }

    public function map($batch): array
    {
        // Obtener información de talleres
        $workshops = $batch->enrollments
            ->map(function ($enrollment) {
                $workshop = $enrollment->instructorWorkshop->workshop ?? null;
                $instructor = $enrollment->instructorWorkshop->instructor ?? null;
                
                if (!$workshop) {
                    return 'Taller no disponible';
                }

                $dayNames = [
                    1 => 'Lun', 2 => 'Mar', 3 => 'Mié',
                    4 => 'Jue', 5 => 'Vie', 6 => 'Sáb',
                    7 => 'Dom', 0 => 'Dom',
                ];
                
                $dayInSpanish = $dayNames[$enrollment->instructorWorkshop->day_of_week] ?? 'N/A';
                $startTime = $enrollment->instructorWorkshop->start_time 
                    ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->start_time)->format('H:i')
                    : 'N/A';
                
                $instructorName = $instructor 
                    ? "{$instructor->first_names} {$instructor->last_names}"
                    : 'Sin instructor';

                return "{$workshop->name} ({$dayInSpanish} {$startTime}) - {$instructorName}";
            })
            ->join('; ');

        // Estado de pago
        $paymentStatus = match ($batch->payment_status) {
            'pending' => 'En Proceso',
            'to_pay' => 'Por Pagar',
            'completed' => 'Inscrito',
            'credit_favor' => 'Crédito a Favor',
            'refunded' => 'Anulado',
            default => $batch->payment_status,
        };

        // Método de pago
        $paymentMethod = match ($batch->payment_method) {
            'cash' => 'Efectivo',
            'link' => 'Link',
            default => $batch->payment_method,
        };

        // Usuario que registró el pago
        $paymentRegisteredBy = '';
        if ($batch->payment_registered_by_user_id && $batch->payment_registered_at) {
            $userName = $batch->paymentRegisteredByUser ? $batch->paymentRegisteredByUser->name : 'Usuario eliminado';
            $paymentRegisteredBy = $userName . ' - ' . $batch->payment_registered_at->format('d/m/Y H:i');
        }

        return [
            $batch->student ? "{$batch->student->first_names} {$batch->student->last_names}" : 'Sin estudiante',
            $batch->student ? $batch->student->student_code : 'N/A',
            $batch->creator ? $batch->creator->name : 'Sistema',
            $workshops ?: 'Sin talleres',
            $batch->enrollments->count(),
            $batch->enrollments->sum('number_of_classes'),
            $batch->updated_at ? $batch->updated_at->format('d/m/Y H:i') : '',
            $paymentStatus,
            $paymentMethod,
            number_format($batch->total_amount, 2),
            $batch->amount_paid ? number_format($batch->amount_paid, 2) : '',
            $batch->change_amount ? number_format($batch->change_amount, 2) : '',
            $batch->batch_code ?: 'Sin código',
            $batch->payment_date ? $batch->payment_date->format('d/m/Y') : '',
            $paymentRegisteredBy,
            $batch->notes ?: '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            // Bordes para toda la tabla
            'A1:P1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }
}