<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllUsersEnrollmentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected array $enrollments) {}

    public function collection()
    {
        return collect($this->enrollments);
    }

    public function headings(): array
    {
        return [
            'Cajero',
            'Alumno',
            'Código',
            'Fecha/Hora Pago',
            'Fecha Inscripción',
            'N° Talleres',
            'Talleres',
            'Monto Total (S/)',
            'Método de Pago',
            'Ticket',
            'Estado',
        ];
    }

    public function map($row): array
    {
        return [
            $row['user_name'],
            $row['student_name'],
            $row['student_code'],
            $row['payment_registered_time'],
            $row['enrollment_date'],
            $row['workshops_count'],
            $row['workshops_list'],
            number_format($row['total_amount'], 2),
            $row['payment_method'],
            $row['ticket_code'],
            $row['payment_status'],
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
}
