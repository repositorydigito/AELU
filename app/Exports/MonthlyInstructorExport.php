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

class MonthlyInstructorExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $volunteerWorkshops,
        protected array $hourlyWorkshops,
        protected string $periodName,
    ) {}

    public function collection()
    {
        $rows = collect();

        foreach ($this->volunteerWorkshops as $w) {
            $rows->push(array_merge($w, ['tipo' => 'Voluntario']));
        }

        foreach ($this->hourlyWorkshops as $w) {
            $rows->push(array_merge($w, ['tipo' => 'Por Horas']));
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Taller',
            'Horario',
            'Modalidad',
            'Instructor',
            'Inscripciones',
            'Tarifa (S/)',
            'Total Recaudado (S/)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['tipo'],
            $row['taller'],
            $row['horario'],
            $row['modalidad'],
            $row['instructor'],
            $row['inscripciones'],
            number_format($row['tarifa'], 2),
            number_format($row['total_recaudado'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $total = count($this->volunteerWorkshops) + count($this->hourlyWorkshops);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            'A1:H' . ($total + 1) => [
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
        return $this->periodName;
    }
}
