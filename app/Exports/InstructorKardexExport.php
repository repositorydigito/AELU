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

class InstructorKardexExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $enrollments,
        protected string $instructorName,
        protected string $workshopInfo,
    ) {}

    public function collection()
    {
        return collect($this->enrollments);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Tipo Doc.',
            'N° Documento',
            'Código Socio',
            'Apellidos y Nombres',
            'Condición',
            'Moneda',
            'Importe (S/)',
            'Cajero',
            'N° Clases',
            'Estado Ticket',
        ];
    }

    public function map($row): array
    {
        return [
            $row['fecha'],
            $row['hora'],
            $row['tipo_documento'],
            $row['numero_documento'],
            $row['codigo_socio'],
            $row['apellidos_nombres'],
            $row['condicion'],
            $row['moneda'],
            number_format($row['importe'], 2),
            $row['cajero'],
            $row['number_of_classes'],
            $row['ticket_status'],
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
            'A1:L' . (count($this->enrollments) + 1) => [
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
        return 'Kardex';
    }
}
