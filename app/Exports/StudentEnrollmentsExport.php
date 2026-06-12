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

class StudentEnrollmentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $enrollments,
        protected string $studentName,
    ) {}

    public function collection()
    {
        return collect($this->enrollments);
    }

    public function headings(): array
    {
        return [
            'Taller',
            'Horario',
            'Modalidad',
            'N° Clases',
            'Período',
            'Fecha Inscripción',
            'Monto (S/)',
            'Método de Pago',
            'Ticket',
            'Estado Ticket',
            'Emitido',
        ];
    }

    public function map($row): array
    {
        return [
            $row['workshop_name'],
            $row['schedule'],
            $row['modality'],
            $row['number_of_classes'],
            $row['period_name'],
            $row['enrollment_date'],
            number_format($row['amount'], 2),
            $row['payment_method'],
            $row['ticket_code'],
            $row['ticket_status'],
            $row['issued_at'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            'A1:K' . (count($this->enrollments) + 1) => [
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
        return $this->studentName;
    }
}
