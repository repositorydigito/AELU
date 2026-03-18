<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyEnrollmentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $enrollments,
    ) {}

    public function collection()
    {
        return collect($this->enrollments);
    }

    public function headings(): array
    {
        return [
            'Estudiante',
            'Código',
            'Fecha Nac.',
            'Edad',
            'Fecha Inscripción',
            'N° Talleres',
            'Monto (S/)',
            'Método de Pago',
            'N° Ticket',
            'Estado',
            'Cajero',
        ];
    }

    public function map($row): array
    {
        return [
            $row['student_name'],
            $row['student_code'],
            $row['birth_date'],
            $row['age'],
            $row['enrollment_date'],
            $row['enrollments_count'],
            number_format($row['total_amount'], 2),
            $row['payment_method'],
            $row['ticket_code'],
            $row['ticket_status'],
            $row['cashier_name'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->enrollments) + 1;

        return [
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
            'A1:K'.$lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Inscripciones';
    }
}
