<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllInstructorsPaymentExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $groupedPayments,
        protected string $periodName,
    ) {}

    public function sheets(): array
    {
        return [
            new VolunteerPaymentSheet($this->groupedPayments['volunteer'] ?? [], $this->periodName),
            new HourlyPaymentSheet($this->groupedPayments['hourly'] ?? [], $this->periodName),
        ];
    }
}

class VolunteerPaymentSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected array $instructors,
        protected string $periodName,
    ) {}

    public function title(): string
    {
        return 'Voluntarios';
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->instructors as $instructor) {
            foreach ($instructor['workshops'] as $workshop) {
                $rows->push([
                    'instructor'    => $instructor['instructor_name'],
                    'taller'        => $workshop['workshop_name'],
                    'horario'       => $workshop['schedule'].(!empty($workshop['modality']) ? ' - '.$workshop['modality'] : ''),
                    'alumnos'       => $workshop['total_students'],
                    'tarifa'        => $workshop['standard_fee'] ?? 0,
                    'porcentaje'    => number_format($workshop['volunteer_percentage'] ?? 0, 0).'%',
                    'ingresos'      => ($workshop['schedule_rowspan'] ?? 1) > 0
                                        ? ($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'])
                                        : '',
                    'por_pagar'     => ($workshop['schedule_rowspan'] ?? 1) > 0
                                        ? ($workshop['schedule_amount'] ?? $workshop['amount'])
                                        : '',
                    'saldo_favor'   => ($workshop['schedule_rowspan'] ?? 1) > 0
                                        ? (($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'])
                                           - ($workshop['schedule_amount'] ?? $workshop['amount']))
                                        : '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Instructor',
            'Taller',
            'Horario',
            'Inscritos',
            'Tarifa (S/)',
            '%',
            'Ingresos (S/)',
            'Por Pagar (S/)',
            'Saldo a Favor (S/)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['instructor'],
            $row['taller'],
            $row['horario'],
            $row['alumnos'],
            number_format($row['tarifa'], 2),
            $row['porcentaje'],
            $row['ingresos'] !== '' ? number_format($row['ingresos'], 2) : '',
            $row['por_pagar'] !== '' ? number_format($row['por_pagar'], 2) : '',
            $row['saldo_favor'] !== '' ? number_format($row['saldo_favor'], 2) : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $total = array_sum(array_map(fn ($i) => count($i['workshops']), $this->instructors));

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            ],
            'A1:I'.($total + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }
}

class HourlyPaymentSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected array $instructors,
        protected string $periodName,
    ) {}

    public function title(): string
    {
        return 'Por Horas';
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->instructors as $instructor) {
            foreach ($instructor['workshops'] as $workshop) {
                $isFirst = ($workshop['schedule_rowspan'] ?? 1) > 0;
                $rows->push([
                    'instructor'   => $instructor['instructor_name'],
                    'taller'       => $workshop['workshop_name'],
                    'horario'      => $workshop['schedule'].(!empty($workshop['modality']) ? ' - '.$workshop['modality'] : ''),
                    'alumnos'      => $workshop['total_students'],
                    'tarifa'       => $workshop['standard_fee'] ?? 0,
                    'honorarios'   => $isFirst ? 'S/ '.number_format($workshop['hourly_rate'] ?? 0, 2).'/hr' : '',
                    'horas'        => $isFirst ? ($workshop['hours_worked'] ?? 0) : '',
                    'ingresos'     => $isFirst ? ($workshop['schedule_revenue'] ?? $workshop['monthly_revenue']) : '',
                    'por_pagar'    => $isFirst ? ($workshop['schedule_amount'] ?? $workshop['amount']) : '',
                    'saldo_favor'  => $isFirst
                                        ? (($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'])
                                           - ($workshop['schedule_amount'] ?? $workshop['amount']))
                                        : '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Instructor',
            'Taller',
            'Horario',
            'Inscritos',
            'Tarifa (S/)',
            'Honorarios/hora (S/)',
            'Horas',
            'Ingresos (S/)',
            'Por Pagar (S/)',
            'Saldo a Favor (S/)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['instructor'],
            $row['taller'],
            $row['horario'],
            $row['alumnos'],
            number_format($row['tarifa'], 2),
            $row['honorarios'],
            $row['horas'],
            $row['ingresos'] !== '' ? number_format($row['ingresos'], 2) : '',
            $row['por_pagar'] !== '' ? number_format($row['por_pagar'], 2) : '',
            $row['saldo_favor'] !== '' ? number_format($row['saldo_favor'], 2) : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $total = array_sum(array_map(fn ($i) => count($i['workshops']), $this->instructors));

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            ],
            'A1:J'.($total + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }
}
