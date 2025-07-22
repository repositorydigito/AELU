<?php

namespace App\Exports;

use App\Models\StudentEnrollment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class IncomeExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = StudentEnrollment::query()
            ->with(['student', 'instructorWorkshop.workshop', 'instructorWorkshop.instructor'])
            ->where('payment_status', 'completed');

        // Aplicar filtros de fecha de pago si existen
        if (!empty($this->filters['payment_from'])) {
            $query->whereDate('payment_date', '>=', $this->filters['payment_from']);
        }

        if (!empty($this->filters['payment_until'])) {
            $query->whereDate('payment_date', '<=', $this->filters['payment_until']);
        }

        // Aplicar filtros de fecha de inscripción si existen
        if (!empty($this->filters['enrollment_date_from'])) {
            $query->whereDate('enrollment_date', '>=', $this->filters['enrollment_date_from']);
        }

        if (!empty($this->filters['enrollment_date_until'])) {
            $query->whereDate('enrollment_date', '<=', $this->filters['enrollment_date_until']);
        }

        // Aplicar filtro de método de pago si existe
        if (!empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }

        // Aplicar filtro de tipo de inscripción si existe
        if (!empty($this->filters['enrollment_type'])) {
            $query->where('enrollment_type', $this->filters['enrollment_type']);
        }

        return $query->orderBy('payment_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha de Pago',
            'Estudiante',
            'Taller',
            'Instructor',
            'Monto (S/)',
            'Método de Pago',
            'Fecha de Inscripción',
            'Tipo de Inscripción',
            'Número de Clases',
            'Notas'
        ];
    }

    public function map($enrollment): array
    {
        return [
            $enrollment->payment_date ? $enrollment->payment_date->format('d/m/Y') : 'No registrada',
            $enrollment->student->first_names . ' ' . $enrollment->student->last_names,
            $enrollment->instructorWorkshop->workshop->name ?? 'N/A',
            ($enrollment->instructorWorkshop->instructor->first_names ?? '') . ' ' . ($enrollment->instructorWorkshop->instructor->last_names ?? ''),
            number_format($enrollment->total_amount, 2),
            $enrollment->payment_method === 'cash' ? 'Efectivo' : 'Link de Pago',
            $enrollment->enrollment_date->format('d/m/Y'),
            $enrollment->enrollment_type === 'full_month' ? 'Regular' : 'Recuperación',
            $enrollment->number_of_classes . ($enrollment->number_of_classes === 1 ? ' Clase' : ' Clases'),
            $enrollment->pricing_notes ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ]
            ],
            // Bordes para toda la tabla
            'A1:L1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]
        ];
    }
}