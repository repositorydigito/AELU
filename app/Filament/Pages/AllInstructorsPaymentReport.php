<?php

namespace App\Filament\Pages;

use App\Models\InstructorPayment;
use App\Models\MonthlyPeriod;
use App\Models\InstructorWorkshop;
use Dompdf\Dompdf;
use Dompdf\Options;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\View;

class AllInstructorsPaymentReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.all-instructors-payment-report';
    protected static ?string $title = 'Pago de Profesores - General';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedMonthlyPeriodId = null;
    public $allInstructorPayments = [];
    public $totalAmount = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('monthly_period_id')
                    ->label('Período Mensual')
                    ->placeholder('Selecciona un período...')
                    ->options(function () {
                        return MonthlyPeriod::where('year', '>=', 2026)
                            ->where('start_date', '<=', now())
                            ->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                $monthNames = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                $displayName = ($monthNames[$period->month] ?? 'Mes ' . $period->month) . ' ' . $period->year;
                                return [$period->id => $displayName];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedMonthlyPeriodId = $state;
                        $this->loadAllInstructorPayments();
                    })
                    ->required(),
            ])
            ->statePath('data');
    }

    public function loadAllInstructorPayments(): void
    {
        if (!$this->selectedMonthlyPeriodId) {
            $this->allInstructorPayments = [];
            $this->totalAmount = 0;
            return;
        }

        $instructorPayments = InstructorPayment::with([
            'instructor',
            'instructorWorkshop.workshop',
            'monthlyPeriod'
        ])
            ->where('monthly_period_id', $this->selectedMonthlyPeriodId)
            ->orderBy('instructor_id')
            ->get();

        if ($instructorPayments->isEmpty()) {
            $this->allInstructorPayments = [];
            $this->totalAmount = 0;
            return;
        }

        $grouped = ['volunteer' => [], 'hourly' => []];

        foreach ($instructorPayments as $payment) {
            $instructor = $payment->instructor;
            $instructorWorkshop = $payment->instructorWorkshop;
            $workshop = $instructorWorkshop->workshop ?? null;
            $modality = $payment->payment_type; // 'volunteer' o 'hourly'
            $instructorId = $instructor->id;

            // Formatear horario
            $dayOfWeek = $instructorWorkshop->day_of_week ?? 'N/A';
            if (is_array($dayOfWeek)) {
                $dayNames = [
                    0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                ];
                $dayOfWeek = collect($dayOfWeek)->map(fn($day) => $dayNames[$day] ?? $day)->join('/');
            }
            $startTime = $instructorWorkshop->start_time
                ? \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i')
                : 'N/A';
            $endTime = $instructorWorkshop->end_time
                ? \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i')
                : 'N/A';

            $amount = $payment->calculated_amount ?? 0;

            $workshopRow = [
                'workshop_name'       => $workshop->name ?? 'N/A',
                'schedule'            => "{$dayOfWeek} {$startTime}-{$endTime}",
                'total_students'      => $payment->total_students ?? 0,
                'monthly_revenue'     => $payment->monthly_revenue ?? 0,
                'amount'              => $amount,
                'payment_status'      => $this->getPaymentStatusText($payment->payment_status),
            ];

            if ($modality === 'hourly') {
                $workshopRow['hours_worked'] = $payment->total_hours ?? 0;
                $workshopRow['hourly_rate']  = $payment->applied_hourly_rate ?? 0;
            } else {
                $workshopRow['volunteer_percentage'] = ($payment->applied_volunteer_percentage ?? 0) * 100;
            }

            if (!isset($grouped[$modality][$instructorId])) {
                $grouped[$modality][$instructorId] = [
                    'instructor_id'   => $instructorId,
                    'instructor_name' => $instructor->last_names . ' ' . $instructor->first_names,
                    'instructor_code' => $instructor->code ?? 'N/A',
                    'workshops'       => [],
                    'subtotal'        => 0,
                ];
            }

            $grouped[$modality][$instructorId]['workshops'][] = $workshopRow;
            $grouped[$modality][$instructorId]['subtotal'] += $amount;
        }

        $grouped['volunteer'] = array_values($grouped['volunteer']);
        $grouped['hourly']    = array_values($grouped['hourly']);

        $this->allInstructorPayments = $grouped;

        $volunteerTotal = collect($grouped['volunteer'])->sum('subtotal');
        $hourlyTotal    = collect($grouped['hourly'])->sum('subtotal');

        $this->totalAmount = [
            'volunteer'   => $volunteerTotal,
            'hourly'      => $hourlyTotal,
            'grand_total' => $volunteerTotal + $hourlyTotal,
        ];
    }

    private function getPaymentStatusText($status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            default => ucfirst($status ?? 'Pendiente'),
        };
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn() => !empty($this->allInstructorPayments))
            ->action(function () {
                try {
                    return $this->generatePDF();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error en la acción')
                        ->body('Error: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function generatePDF()
    {
        if (empty($this->allInstructorPayments)) {
            Notification::make()
                ->title('Error')
                ->body('No hay registros para generar el reporte')
                ->danger()
                ->send();

            return;
        }

        try {
            $period = MonthlyPeriod::find($this->selectedMonthlyPeriodId);
            $monthNames = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $periodName = $period ? (($monthNames[$period->month] ?? 'Mes ' . $period->month) . ' ' . $period->year) : 'N/A';

            $html = View::make('reports.all-instructors-payment', [
                'grouped_payments' => $this->allInstructorPayments,
                'monthly_period'   => $periodName,
                'total_amount'     => $this->totalAmount,
                'generated_at'     => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape'); // Horizontal para mejor visualización

            $dompdf->render();

            $fileName = 'pago-profesores-general-' .
                str_replace([' ', '/'], '-', strtolower($periodName)) . '.pdf';

            return response()->stream(function () use ($dompdf) {
                echo $dompdf->output();
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getActions(): array
    {
        return [
            $this->generatePDFAction(),
        ];
    }
}
