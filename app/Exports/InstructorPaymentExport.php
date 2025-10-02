<?php

namespace App\Exports;

use App\Models\InstructorPayment;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructorPaymentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = InstructorPayment::query()
            ->with([
                'instructor', 
                'instructorWorkshop.workshop', 
                'monthlyPeriod'
            ]);

        // Aplicar filtros si existen
        if (!empty($this->filters['monthly_period_id'])) {
            $query->where('monthly_period_id', $this->filters['monthly_period_id']);
        }

        if (!empty($this->filters['payment_type'])) {
            $query->where('payment_type', $this->filters['payment_type']);
        }

        if (!empty($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        return $query->orderBy('instructor_id')
                    ->orderBy('monthly_period_id');
    }

    public function headings(): array
    {
        return [
            'Apellidos',
            'Nombres',
            'Taller',
            'Horario',
            'Período',
            'Tipo de Pago',
            'Tarifa/Porcentaje',
            'Monto Base',
            'Monto a Pagar (S/)',
            'Estado',
            'Fecha de Pago',
            'Número de Documento',
            'Notas',
        ];
    }

    public function map($payment): array
    {
        // Obtener información del taller y horario
        $workshop = $payment->instructorWorkshop;
        $workshopName = $workshop ? $workshop->workshop->name : 'N/A';
        
        $schedule = 'N/A';
        if ($workshop) {
            $dayNames = [
                0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
            ];
            $dayOfWeek = $dayNames[$workshop->day_of_week] ?? 'Desconocido';
            $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
            $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');
            $schedule = "{$dayOfWeek} {$startTime}-{$endTime}";
        }

        // Período
        $period = $payment->monthlyPeriod 
            ? \Carbon\Carbon::create()->month($payment->monthlyPeriod->month)
                ->year($payment->monthlyPeriod->year)
                ->format('m/Y')
            : 'N/A';

        // Tipo de pago
        $paymentType = match ($payment->payment_type) {
            'volunteer' => 'Voluntario',
            'hourly' => 'Por Horas',
            default => $payment->payment_type,
        };

        // Tarifa/Porcentaje
        $rateOrPercentage = 'N/A';
        if ($payment->payment_type === 'volunteer' && $payment->applied_volunteer_percentage) {
            $rateOrPercentage = number_format($payment->applied_volunteer_percentage * 100) . '%';
        } elseif ($payment->payment_type === 'hourly' && $payment->applied_hourly_rate) {
            $rateOrPercentage = 'S/ ' . number_format($payment->applied_hourly_rate, 2);
        }

        // Monto base
        $baseAmount = 'N/A';
        if ($payment->payment_type === 'volunteer' && $payment->applied_volunteer_percentage && $payment->calculated_amount > 0) {
            $base = $payment->calculated_amount / $payment->applied_volunteer_percentage;
            $baseAmount = 'S/ ' . number_format($base, 2);
        } elseif ($payment->payment_type === 'hourly' && $payment->applied_hourly_rate && $payment->calculated_amount > 0) {
            $hours = $payment->calculated_amount / $payment->applied_hourly_rate;
            $baseAmount = number_format($hours, 2) . ' horas';
        }

        // Estado
        $status = match ($payment->payment_status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            default => $payment->payment_status,
        };

        return [
            $payment->instructor->last_names ?? '',
            $payment->instructor->first_names ?? '',
            $workshopName,
            $schedule,
            $period,
            $paymentType,
            $rateOrPercentage,
            $baseAmount,
            number_format($payment->calculated_amount, 2),
            $status,
            $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '',
            $payment->document_number ?? '',
            $payment->notes ?? '',
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
            'A1:M1000' => [
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