<?php

// App/Filament/Pages/CashiersEnrollmentReport.php

namespace App\Filament\Pages;

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\View;

class CashiersEnrollmentReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.cashiers-enrollment-report';

    protected static ?string $title = 'Inscripciones por Cajero';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public $selectedCashier = null;

    public $selectedDateFrom = null;

    public $selectedDateTo = null;

    public $cashierEnrollments = [];

    public $paymentSummary = [
        'total_enrollments' => 0,
        'cash_count' => 0,
        'cash_amount' => 0,
        'link_count' => 0,
        'link_amount' => 0,
        'total_amount' => 0,
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('cashier_id')
                    ->label('Cajero')
                    ->placeholder('Selecciona un cajero...')
                    ->options(\App\Models\User::whereDoesntHave('roles', function ($query) {
                        $query->where('name', 'Delegado');
                    })->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedCashier = $state;
                        $this->loadCashierEnrollments();
                    }),

                DatePicker::make('date_from')
                    ->label('Fecha Desde')
                    ->placeholder('Selecciona fecha inicial...')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDateFrom = $state;
                        $this->loadCashierEnrollments();
                    }),

                DatePicker::make('date_to')
                    ->label('Fecha Hasta')
                    ->placeholder('Selecciona fecha final...')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDateTo = $state;
                        $this->loadCashierEnrollments();
                    }),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function loadCashierEnrollments(): void
    {
        if (! $this->selectedCashier || ! $this->selectedDateFrom || ! $this->selectedDateTo) {
            $this->cashierEnrollments = [];
            $this->resetPaymentSummary();
            return;
        }

        $dateFromForQuery = \Carbon\Carbon::parse($this->selectedDateFrom)->format('Y-m-d');
        $dateToForQuery = \Carbon\Carbon::parse($this->selectedDateTo)->format('Y-m-d');

        // Obtener EnrollmentBatch en lugar de StudentEnrollments individuales
        $enrollmentBatches = \App\Models\EnrollmentBatch::with([
            'student',
            'paymentRegisteredByUser',
            'enrollments.instructorWorkshop.workshop'  // Para contar talleres
        ])
            ->where('payment_registered_by_user_id', $this->selectedCashier)
            ->whereDate('payment_registered_at', '>=', $dateFromForQuery)
            ->whereDate('payment_registered_at', '<=', $dateToForQuery)
            ->orderBy('payment_registered_at', 'desc')
            ->get();

        if ($enrollmentBatches->isEmpty()) {
            $this->cashierEnrollments = [];
            $this->resetPaymentSummary();
            return;
        }

        $this->cashierEnrollments = $enrollmentBatches->map(function ($batch) {
            $student = $batch->student;
            $cashier = $batch->paymentRegisteredByUser;

            return [
                'id' => $batch->id,
                'student_name' => $student ? ($student->first_names.' '.$student->last_names) : 'N/A',
                'student_code' => $student->student_code ?? 'N/A',
                'cashier_name' => $cashier ? $cashier->name : 'N/A',
                'payment_registered_time' => $batch->payment_registered_at ? $batch->payment_registered_at->format('d/m/Y H:i') : 'N/A',
                'enrollment_date' => $batch->enrollment_date ? $batch->enrollment_date->format('d/m/Y') : 'N/A',
                'workshops_count' => $batch->enrollments->count(), // Número de talleres
                'workshops_list' => $batch->enrollments->pluck('instructorWorkshop.workshop.name')->join(', '), // Lista de talleres
                'total_amount' => $batch->total_amount,
                'amount_paid' => $batch->amount_paid,
                'payment_method' => $this->getPaymentMethodText($batch->payment_method),
                'batch_code' => $batch->batch_code ?? 'Sin código',
                'payment_status' => $this->getPaymentStatusText($batch->payment_status),
            ];
        })->toArray();

        $this->calculatePaymentSummary();
    }

    private function resetPaymentSummary(): void
    {
        $this->paymentSummary = [
            'total_enrollments' => 0,
            'cash_count' => 0,
            'cash_amount' => 0,
            'link_count' => 0,
            'link_amount' => 0,
            'total_amount' => 0,
        ];
    }

    private function calculatePaymentSummary(): void
    {
        $enrollments = collect($this->cashierEnrollments);

        // Filtrar solo los no anulados para los cálculos de montos
        $activeEnrollments = $enrollments->where('payment_status', '!=', 'Anulado');

        $this->paymentSummary = [
            'total_enrollments' => $enrollments->count(),
            'cash_count' => $activeEnrollments->where('payment_method', 'Efectivo')->count(),
            'cash_amount' => $activeEnrollments->where('payment_method', 'Efectivo')->sum('total_amount'),
            'link_count' => $activeEnrollments->where('payment_method', 'Link')->count(),
            'link_amount' => $activeEnrollments->where('payment_method', 'Link')->sum('total_amount'),
            'total_amount' => $activeEnrollments->sum('total_amount'),
        ];
    }

    private function getPaymentStatusText($status): string
    {
        return match ($status) {
            'pending' => 'En Proceso',
            'to_pay' => 'Por Pagar',
            'completed' => 'Inscrito',
            'credit_favor' => 'Crédito a Favor',
            'refunded' => 'Anulado',
            default => $status,
        };
    }

    private function getPaymentMethodText($method): string
    {
        return match ($method) {
            'cash' => 'Efectivo',
            'link' => 'Link',
            default => 'N/A',
        };
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => ! empty($this->cashierEnrollments))
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
        if (empty($this->cashierEnrollments) || ! $this->selectedCashier) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un cajero y que tenga registros de pago')
                ->danger()
                ->send();

            return;
        }

        try {
            $cashierName = User::find($this->selectedCashier)->name ?? 'N/A';

            $html = View::make('reports.cashiers-enrollment', [
                'enrollments' => $this->cashierEnrollments,
                'payment_summary' => $this->paymentSummary,
                'cashier_name' => $cashierName,
                'date_from' => \Carbon\Carbon::parse($this->selectedDateFrom)->format('d/m/Y'),
                'date_to' => \Carbon\Carbon::parse($this->selectedDateTo)->format('d/m/Y'),
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');

            $dompdf->render();

            $fileName = 'inscripciones-cajero-'.str_replace(' ', '-', strtolower($cashierName)).'-'.
                       \Carbon\Carbon::parse($this->selectedDateFrom)->format('d-m-Y').'-'.
                       \Carbon\Carbon::parse($this->selectedDateTo)->format('d-m-Y').'.pdf';

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

    protected function getActions(): array
    {
        return [
            $this->generatePDFAction(),
        ];
    }
}
