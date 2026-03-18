<?php

namespace App\Filament\Pages;

use App\Exports\MonthlyEnrollmentsExport;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
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

class EnrollmentsReport2 extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.enrollments-report2';
    protected static ?string $title = 'Inscripciones por Mes';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedPeriod = null;
    public $monthlyEnrollments = [];
    public $periodData = null;
    public $summaryData = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period_id')
                    ->label('Seleccionar Período Mensual')
                    ->placeholder('Selecciona un período...')
                    ->options(
                        MonthlyPeriod::where('year', '>=', 2026)
                            ->where('start_date', '<=', now())
                            ->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                $periodName = $this->generatePeriodName($period->month, $period->year);

                                return [$period->id => $periodName];
                            })
                            ->toArray()
                    )
                    ->searchable() // Búsqueda del lado del cliente
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->loadPeriodEnrollments();
                    })
                    ->required(),
            ])
            ->statePath('data');
    }

    public function loadPeriodEnrollments(): void
    {
        if (! $this->selectedPeriod) {
            $this->monthlyEnrollments = [];
            $this->periodData = null;
            $this->summaryData = [];
            return;
        }

        // Cargar datos del período
        $this->periodData = MonthlyPeriod::find($this->selectedPeriod);

        // Construir la consulta base para obtener tickets del período
        $tickets = \App\Models\Ticket::whereHas('studentEnrollments', function ($query) {
                $query->where('monthly_period_id', $this->selectedPeriod);
            })
            ->with([
                'student',
                'studentEnrollments.instructorWorkshop.workshop',
                'studentEnrollments.monthlyPeriod',
                'enrollmentBatch.paymentRegisteredByUser',
                'issuedByUser'
            ])
            ->orderBy('issued_at', 'desc')
            ->get();

        $this->monthlyEnrollments = [];

        foreach ($tickets as $ticket) {
            $student = $ticket->student ?? null;
            $totalAmount = 0;
            $paymentMethod = '';
            $enrollmentDate = null;
            $cashierName = '';
            $totalEnrollments = 0;

            // Filtrar solo las inscripciones de este período
            $periodEnrollments = $ticket->studentEnrollments->where('monthly_period_id', $this->selectedPeriod);

            foreach ($periodEnrollments as $enrollment) {
                $totalAmount += $enrollment->total_amount;
                $totalEnrollments++;

                // Tomar los datos de la primera inscripción para los campos comunes
                if (!$paymentMethod) {
                    $paymentMethod = $enrollment->payment_method === 'cash' ? 'Efectivo' :
                                   ($enrollment->payment_method === 'link' ? 'Link' : ucfirst($enrollment->payment_method));
                }

                if (!$enrollmentDate) {
                    $enrollmentDate = $enrollment->enrollment_date;
                }

                if (!$cashierName && $enrollment->enrollmentBatch && $enrollment->enrollmentBatch->paymentRegisteredByUser) {
                    $cashierName = $enrollment->enrollmentBatch->paymentRegisteredByUser->name;
                }
            }

            // Solo agregar si tiene inscripciones en este período
            if ($totalEnrollments > 0) {
                $this->monthlyEnrollments[] = [
                    'id' => $ticket->id,
                    'student_name' => $student ? ($student->last_names.' '.$student->first_names) : 'N/A',
                    'student_code' => $student ? $student->student_code : 'N/A',
                    'birth_date' => $student && $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A',
                    'age' => $student && $student->birth_date ? $student->birth_date->age : 'N/A',
                    'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('d/m/Y') : 'N/A',
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'ticket_code' => $ticket->ticket_code,
                    'ticket_status' => $ticket->status === 'active' ? 'Activo' :
                                    ($ticket->status === 'cancelled' ? 'Anulado' :
                                    ($ticket->status === 'refunded' ? 'Reembolsado' : ucfirst($ticket->status))),
                    'cashier_name' => $cashierName ?: ($ticket->issuedByUser ? $ticket->issuedByUser->name : 'N/A'),
                    'issued_at' => $ticket->issued_at ? $ticket->issued_at->format('d/m/Y H:i') : 'N/A',
                    'enrollments_count' => $totalEnrollments
                ];
            }
        }

        // Calcular datos de resumen
        $this->calculateSummary();
    }

    public function calculateSummary(): void
    {
        $tickets = collect($this->monthlyEnrollments);
        $activeTickets = $tickets->where('ticket_status', 'Activo');

        $this->summaryData = [
            'total_tickets'          => $tickets->count(),
            'active_tickets'         => $activeTickets->count(),
            'cancelled_tickets'      => $tickets->where('ticket_status', 'Anulado')->count(),
            'total_amount'           => $activeTickets->sum('total_amount'),
            'monto_link'             => $activeTickets->where('payment_method', 'Link')->sum('total_amount'),
            'monto_efectivo'         => $activeTickets->where('payment_method', 'Efectivo')->sum('total_amount'),

            // 6 campos de encabezado (solo tickets activos)
            'total_inscripciones'    => $activeTickets->sum('enrollments_count'),
            'inscripciones_link'     => $activeTickets->where('payment_method', 'Link')->sum('enrollments_count'),
            'inscripciones_efectivo' => $activeTickets->where('payment_method', 'Efectivo')->sum('enrollments_count'),
            'total_inscritos'        => $activeTickets->pluck('student_code')->unique()->count(),
            'inscritos_link'         => $activeTickets->where('payment_method', 'Link')->pluck('student_code')->unique()->count(),
            'inscritos_efectivo'     => $activeTickets->where('payment_method', 'Efectivo')->pluck('student_code')->unique()->count(),
        ];
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => ! empty($this->monthlyEnrollments))
            ->action(function () {
                try {
                    return $this->generatePDF();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error en la acción')
                        ->body('Error: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function generatePDF()
    {
        if (! $this->periodData || empty($this->monthlyEnrollments)) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un período con tickets')
                ->danger()
                ->send();

            return;
        }

        try {
            // 1. Renderizar la vista Blade a HTML
            $html = View::make('reports.monthly-enrollments', [
                'period' => $this->periodData,
                'period_name' => $this->generatePeriodName($this->periodData->month, $this->periodData->year),
                'tickets' => $this->monthlyEnrollments, // Cambiar de enrollments a tickets
                'summary' => $this->summaryData,
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            // 2. Configurar Dompdf
            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            // 3. Configurar papel
            $dompdf->setPaper('A4', 'portrait');

            // 4. Renderizar el PDF
            $dompdf->render();

            $fileName = 'tickets-mensual-'.$this->periodData->year.'-'.str_pad($this->periodData->month, 2, '0', STR_PAD_LEFT).'.pdf';

            // 5. Descargar el PDF
            return response()->stream(function () use ($dompdf) {
                echo $dompdf->output();
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('Ocurrió un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportExcelAction(): Action
    {
        return Action::make('exportExcel')
            ->label('Exportar Excel')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => ! empty($this->monthlyEnrollments))
            ->action(function () {
                try {
                    $fileName = 'inscripciones-'.$this->periodData->year.'-'.str_pad($this->periodData->month, 2, '0', STR_PAD_LEFT).'.xlsx';

                    return (new MonthlyEnrollmentsExport(
                        $this->monthlyEnrollments,
                    ))->download($fileName);
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al exportar')
                        ->body('Ocurrió un error: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getActions(): array
    {
        return [
            $this->generatePDFAction(),
            $this->exportExcelAction(),
        ];
    }

    private function generatePeriodName($month, $year): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $monthName = $monthNames[$month] ?? 'Mes '.$month;

        return $monthName.' '.$year;
    }
}
