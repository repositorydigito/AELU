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

class AllInstructorsPaymentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $groupedPayments,
        protected string $periodName,
    ) {}

    public function collection()
    {
        $rows = collect();

        foreach (['volunteer' => 'Voluntario', 'hourly' => 'Por Horas'] as $type => $typeLabel) {
            foreach ($this->groupedPayments[$type] ?? [] as $instructor) {
                foreach ($instructor['workshops'] as $workshop) {
                    $rows->push([
                        'tipo'             => $typeLabel,
                        'instructor'       => $instructor['instructor_name'],
                        'taller'           => $workshop['workshop_name'],
                        'horario'          => $workshop['schedule'],
                        'alumnos'          => $workshop['total_students'],
                        'detalle_clases'   => (function ($breakdown) {
                            $parts = [];
                            foreach ($breakdown as $n => $count) {
                                if ($count > 0) {
                                    $parts[] = ($n > 0 ? $n . 'c' : '?c') . ':' . $count;
                                }
                            }
                            return implode(' | ', $parts);
                        })($workshop['students_by_classes'] ?? []),
                        'tarifa_mensual'   => $workshop['standard_fee'] ?? 0,
                        'ingresos'         => $workshop['monthly_revenue'],
                        'tasa'             => $type === 'volunteer'
                                                ? number_format($workshop['volunteer_percentage'] ?? 0, 1).'%'
                                                : 'S/ '.number_format($workshop['hourly_rate'] ?? 0, 2).'/hr',
                        'horas'            => $type === 'hourly' ? ($workshop['hours_worked'] ?? 0) : '-',
                        'monto'            => $workshop['amount'],
                        'estado'           => $workshop['payment_status'],
                        'recibo'           => $workshop['document_number'] ?? '',
                    ]);
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Instructor',
            'Taller',
            'Horario',
            'N° Alumnos',
            'Detalle Clases',
            'Tarifa Mensual (S/)',
            'Ingresos (S/)',
            '% / Tarifa',
            'Horas',
            'Monto a Pagar (S/)',
            'Estado',
            'Recibo',
        ];
    }

    public function map($row): array
    {
        return [
            $row['tipo'],
            $row['instructor'],
            $row['taller'],
            $row['horario'],
            $row['alumnos'],
            $row['detalle_clases'],
            number_format($row['tarifa_mensual'], 2),
            number_format($row['ingresos'], 2),
            $row['tasa'],
            $row['horas'],
            number_format($row['monto'], 2),
            $row['estado'],
            $row['recibo'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $total = collect($this->groupedPayments['volunteer'] ?? [])
                    ->concat($this->groupedPayments['hourly'] ?? [])
                    ->sum(fn ($i) => count($i['workshops']));

        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            'A1:L'.($total + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CCCCCC'],
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
