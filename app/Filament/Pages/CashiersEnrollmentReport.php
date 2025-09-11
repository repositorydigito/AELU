<?php

// App/Filament/Pages/CashiersEnrollmentReport.php
namespace App\Filament\Pages;

use App\Models\EnrollmentBatch;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class CashiersEnrollmentReport extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.cashiers-enrollment-report';
    protected static ?string $title = 'Inscripciones por Cajero';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedCashier = null;
    public $selectedDateFrom = null;
    public $selectedDateTo = null;
    public $cashierEnrollments = [];

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
                    ->options(\App\Models\User::role('Cajero')->pluck('name', 'id'))
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
        if (!$this->selectedCashier || !$this->selectedDateFrom || !$this->selectedDateTo) {
            $this->cashierEnrollments = [];
            return;
        }

        $dateFromForQuery = \Carbon\Carbon::parse($this->selectedDateFrom)->format('Y-m-d');
        $dateToForQuery = \Carbon\Carbon::parse($this->selectedDateTo)->format('Y-m-d');

        // Obtener StudentEnrollments individuales en lugar de agrupar por lotes
        $enrollments = \App\Models\StudentEnrollment::with([
                'student',
                'instructorWorkshop.workshop',
                'instructorWorkshop.instructor',
                'enrollmentBatch.paymentRegisteredByUser',
                'enrollmentBatch'
            ])
            ->whereHas('enrollmentBatch', function ($query) use ($dateFromForQuery, $dateToForQuery) {
                $query->where('payment_registered_by_user_id', $this->selectedCashier)
                      ->whereDate('payment_registered_at', '>=', $dateFromForQuery)
                      ->whereDate('payment_registered_at', '<=', $dateToForQuery);
            })
            ->orderBy('enrollment_date', 'desc')
            ->get();

        if ($enrollments->isEmpty()) {
            $this->cashierEnrollments = [];
            return;
        }

        $this->cashierEnrollments = $enrollments->map(function ($enrollment) {
            $student = $enrollment->student;
            $batch = $enrollment->enrollmentBatch;
            $cashier = $batch ? $batch->paymentRegisteredByUser : null;
            $workshop = $enrollment->instructorWorkshop->workshop ?? null;
            $instructor = $enrollment->instructorWorkshop->instructor ?? null;

            return [
                'id' => $enrollment->id,
                'student_name' => $student ? ($student->first_names . ' ' . $student->last_names) : 'N/A',
                'student_code' => $student->student_code ?? 'N/A',
                'cashier_name' => $cashier ? $cashier->name : 'N/A',
                'payment_registered_time' => $batch && $batch->payment_registered_at ? $batch->payment_registered_at->format('d/m/Y H:i') : 'N/A',
                'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                'workshop_name' => $workshop->name ?? 'N/A',
                'instructor_name' => $instructor ? ($instructor->first_names . ' ' . $instructor->last_names) : 'N/A',
                'number_of_classes' => $enrollment->number_of_classes,
                'total_amount' => $enrollment->total_amount,
                'payment_method' => $batch && $batch->payment_method === 'cash' ? 'Efectivo' : 'Link',
                'batch_code' => $batch ? ($batch->batch_code ?? 'Sin código') : 'Sin lote',
                'payment_status' => $batch ? $this->getPaymentStatusText($batch->payment_status) : 'N/A',
            ];
        })->toArray();
    }

    private function getPaymentStatusText($status): string
    {
        return match ($status) {
            'pending' => 'En Proceso',
            'to_pay' => 'Por Pagar',
            'completed' => 'Inscrito',
            'credit_favor' => 'Crédito a Favor',
            'refunded' => 'Devuelto',
            default => $status,
        };
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->cashierEnrollments))
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
        if (empty($this->cashierEnrollments) || !$this->selectedCashier) {
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
                'cashier_name' => $cashierName,
                'date_from' => \Carbon\Carbon::parse($this->selectedDateFrom)->format('d/m/Y'),
                'date_to' => \Carbon\Carbon::parse($this->selectedDateTo)->format('d/m/Y'),
                'generated_at' => now()->format('d/m/Y H:i')
            ])->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape'); // Horizontal para más columnas

            $dompdf->render();

            $fileName = 'inscripciones-cajero-' . str_replace(' ', '-', strtolower($cashierName)) . '-' .
                       \Carbon\Carbon::parse($this->selectedDateFrom)->format('d-m-Y') . '-' .
                       \Carbon\Carbon::parse($this->selectedDateTo)->format('d-m-Y') . '.pdf';

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
