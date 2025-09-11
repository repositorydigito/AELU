<?php

namespace App\Filament\Pages;

use App\Models\Instructor;
use App\Models\InstructorPayment;
use App\Models\MonthlyPeriod;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class InstructorPaymentsReport extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.pages.instructor-payments-report';
    protected static ?string $title = 'Reporte de pagos por Profesor';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedInstructor = null;
    public $selectedPeriod = null;
    public $instructorPayments = [];
    public $instructorData = null;
    public $periodData = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('instructor_id')
                    ->label('Seleccionar Profesor')
                    ->placeholder('Todos los profesores')
                    ->options(
                        Instructor::orderBy('last_names')
                            ->get()
                            ->mapWithKeys(function ($instructor) {
                                return [
                                    $instructor->id => $instructor->last_names . ' ' . $instructor->first_names
                                ];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedInstructor = $state;
                        $this->loadInstructorPayments();
                    })
                    ->nullable(),

                Select::make('period_id')
                    ->label('Período Mensual')
                    ->placeholder('Todos los períodos')
                    ->options(
                        MonthlyPeriod::where('year', '>=', now()->year - 2)
                            ->where('year', '<=', now()->year + 1)
                            ->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                $periodName = $this->generatePeriodName($period->month, $period->year);
                                return [$period->id => $periodName];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->loadInstructorPayments();
                    })
                    ->nullable(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadInstructorPayments(): void
    {
        // Limpiar datos previos
        $this->instructorPayments = [];
        $this->instructorData = null;
        $this->periodData = null;

        // Construir query base
        $query = InstructorPayment::where('payment_status', 'paid')
            ->with([
                'instructor',
                'instructorWorkshop.workshop',
                'monthlyPeriod',
            ]);

        // Aplicar filtros
        if ($this->selectedInstructor) {
            $query->where('instructor_id', $this->selectedInstructor);
            $this->instructorData = Instructor::find($this->selectedInstructor);
        }

        if ($this->selectedPeriod) {
            $query->where('monthly_period_id', $this->selectedPeriod);
            $this->periodData = MonthlyPeriod::find($this->selectedPeriod);
        }

        // Si no hay filtros, no cargar nada
        if (!$this->selectedInstructor && !$this->selectedPeriod) {
            return;
        }

        // Ejecutar query y procesar resultados
        $payments = $query->orderBy('created_at', 'desc')->get();

        $this->instructorPayments = $payments->map(function ($payment) {
            $workshop = $payment->instructorWorkshop->workshop ?? null;
            $period = $payment->monthlyPeriod ?? null;
            $instructor = $payment->instructor ?? null;

            // Convertir día de la semana
            $dayNames = [
                0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
            ];

            $dayOfWeek = isset($payment->instructorWorkshop->day_of_week)
                ? $dayNames[$payment->instructorWorkshop->day_of_week] ?? 'Desconocido'
                : 'N/A';

            $startTime = $payment->instructorWorkshop
                ? \Carbon\Carbon::parse($payment->instructorWorkshop->start_time)->format('H:i')
                : 'N/A';

            $endTime = $payment->instructorWorkshop
                ? \Carbon\Carbon::parse($payment->instructorWorkshop->end_time)->format('H:i')
                : 'N/A';

            return [
                'id' => $payment->id,
                'instructor_name' => $instructor ? ($instructor->first_names . ' ' . $instructor->last_names) : 'N/A',
                'workshop_name' => $workshop->name ?? 'N/A',
                'workshop_schedule' => "{$dayOfWeek} {$startTime}-{$endTime}",
                'period_name' => $period ? $this->generatePeriodName($period->month, $period->year) : 'N/A',
                'payment_type' => $payment->payment_type === 'volunteer' ? 'Voluntario' : 'Por Horas',
                'payment_details' => $this->getPaymentDetails($payment),
                'calculated_amount' => $payment->calculated_amount,
                'payment_status' => $payment->payment_status === 'paid' ? 'Pagado' : 'Pendiente',
                'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : 'Sin fecha',
                'document_number' => $payment->document_number ?? 'Sin documento',
            ];
        })->toArray();
    }

    private function getPaymentDetails($payment): string
    {
        if ($payment->payment_type === 'volunteer') {
            $revenue = $payment->monthly_revenue ?? 0;
            $percentage = ($payment->applied_volunteer_percentage ?? 0) * 100;
            $students = $payment->total_students ?? 0;
            return "{$students} estudiantes - S/ " . number_format($revenue, 2) . " × {$percentage}%";
        } else {
            $hours = $payment->total_hours ?? 0;
            $rate = $payment->applied_hourly_rate ?? 0;
            return "{$hours} horas × S/ " . number_format($rate, 2);
        }
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->instructorPayments))
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
        if (empty($this->instructorPayments)) {
            Notification::make()
                ->title('Error')
                ->body('No hay pagos para generar el reporte')
                ->danger()
                ->send();
            return;
        }

        try {
            // Calcular totales
            $totalAmount = collect($this->instructorPayments)->sum('calculated_amount');
            $totalPayments = count($this->instructorPayments);

            // Determinar el tipo de reporte
            $reportTitle = '';
            $reportSubtitle = '';

            if ($this->selectedInstructor && $this->selectedPeriod) {
                $reportTitle = 'Pagos por Profesor y Período';
                $reportSubtitle = $this->instructorData->first_names . ' ' . $this->instructorData->last_names .
                                ' - ' . $this->generatePeriodName($this->periodData->month, $this->periodData->year);
            } elseif ($this->selectedInstructor) {
                $reportTitle = 'Pagos por Profesor';
                $reportSubtitle = $this->instructorData->first_names . ' ' . $this->instructorData->last_names;
            } elseif ($this->selectedPeriod) {
                $reportTitle = 'Pagos por Período';
                $reportSubtitle = $this->generatePeriodName($this->periodData->month, $this->periodData->year);
            }

            // Renderizar la vista Blade a HTML
            $html = View::make('reports.instructor-payments', [
                'instructor' => $this->instructorData,
                'period' => $this->periodData,
                'payments' => $this->instructorPayments,
                'totals' => [
                    'total_amount' => $totalAmount,
                    'total_payments' => $totalPayments,
                ],
                'report_title' => $reportTitle,
                'report_subtitle' => $reportSubtitle,
                'show_instructor_column' => !$this->selectedInstructor, // Mostrar columna instructor solo si no se filtró por uno específico
                'generated_at' => now()->format('d/m/Y H:i')
            ])->render();

            // Configurar Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Generar nombre de archivo
            $fileName = 'pagos-';
            if ($this->selectedInstructor) {
                $fileName .= 'profesor-' . str($this->instructorData->first_names . '-' . $this->instructorData->last_names)->slug();
            }
            if ($this->selectedPeriod) {
                $fileName .= ($this->selectedInstructor ? '-' : '') . 'periodo-' . $this->periodData->year . '-' . str_pad($this->periodData->month, 2, '0', STR_PAD_LEFT);
            }
            $fileName .= '.pdf';

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

    private function generatePeriodName($month, $year): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $monthName = $monthNames[$month] ?? 'Mes ' . $month;
        return $monthName . ' ' . $year;
    }
}
