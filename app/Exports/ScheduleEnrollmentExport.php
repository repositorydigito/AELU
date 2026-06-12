<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleEnrollmentExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
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
            'Alumno',
            'Código',
            'Fecha Inscripción',
            'Fecha Pago',
            'Monto (S/)',
            'Método de Pago',
            'Estado',
            'Ticket',
            'Cajero',
            'N° Clases',
        ];
    }

    public function map($row): array
    {
        return [
            $row['student_name'],
            $row['student_code'],
            $row['enrollment_date'],
            $row['payment_registered_time'],
            number_format($row['total_amount'], 2),
            $row['payment_method'],
            $row['payment_status'],
            $row['ticket_code'],
            $row['user_name'],
            $row['number_of_classes'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $totalRow = count($this->enrollments) + 2;

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            'A1:J' . $totalRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
            $totalRow => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalRow = count($this->enrollments) + 2;
                $total = collect($this->enrollments)->sum('total_amount');
                $event->sheet->setCellValue("D{$totalRow}", 'Monto Total');
                $event->sheet->setCellValue("E{$totalRow}", number_format($total, 2));
            },
        ];
    }
}
