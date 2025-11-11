<?php

namespace App\Filament\Pages;

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\View;

class AllUsersEnrollmentReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.pages.all-users-enrollment-report';
    protected static ?string $title = 'Inscripciones - Reporte General';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedDateFrom = null;
    public $selectedDateTo = null;
    public $allEnrollments = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')
                    ->label('Fecha Desde')
                    ->placeholder('Selecciona fecha inicial...')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDateFrom = $state;
                        $this->loadAllUsersEnrollments();
                    }),

                DatePicker::make('date_to')
                    ->label('Fecha Hasta')
                    ->placeholder('Selecciona fecha final...')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDateTo = $state;
                        $this->loadAllUsersEnrollments();
                    }),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadAllUsersEnrollments(): void
    {
        if (!$this->selectedDateFrom || !$this->selectedDateTo) {
            $this->allEnrollments = [];
            return;
        }

        $dateFromForQuery = \Carbon\Carbon::parse($this->selectedDateFrom)->format('Y-m-d');
        $dateToForQuery = \Carbon\Carbon::parse($this->selectedDateTo)->format('Y-m-d');

        // Obtener todos los EnrollmentBatch
        $enrollmentBatches = \App\Models\EnrollmentBatch::with([
            'student',
            'paymentRegisteredByUser',
            'enrollments.instructorWorkshop.workshop'
        ])
            ->whereHas('paymentRegisteredByUser', function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Delegado');
                });
            })
            ->whereDate('payment_registered_at', '>=', $dateFromForQuery)
            ->whereDate('payment_registered_at', '<=', $dateToForQuery)
            ->orderBy('payment_registered_at', 'desc')
            ->get();

        if ($enrollmentBatches->isEmpty()) {
            $this->allEnrollments = [];
            return;
        }

        // Crear un array plano con todas las inscripciones
        $this->allEnrollments = $enrollmentBatches->map(function ($batch) {
            $student = $batch->student;
            $user = $batch->paymentRegisteredByUser;

            return [
                'id' => $batch->id,
                'user_name' => $user ? $user->name : 'N/A',
                'student_name' => $student ? ($student->first_names . ' ' . $student->last_names) : 'N/A',
                'student_code' => $student->student_code ?? 'N/A',
                'payment_registered_time' => $batch->payment_registered_at ? $batch->payment_registered_at->format('d/m/Y H:i') : 'N/A',
                'enrollment_date' => $batch->enrollment_date ? $batch->enrollment_date->format('d/m/Y') : 'N/A',
                'workshops_count' => $batch->enrollments->count(),
                'workshops_list' => $batch->enrollments->pluck('instructorWorkshop.workshop.name')->join(', '),
                'total_amount' => $batch->total_amount,
                'payment_method' => $this->getPaymentMethodText($batch->payment_method),
                'batch_code' => $this->getTicketCode($batch) ?? $batch->batch_code ?? 'Sin código',
                'payment_status' => $this->getPaymentStatusText($batch->payment_status),
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
            ->visible(fn() => !empty($this->allEnrollments))
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
        if (empty($this->allEnrollments)) {
            Notification::make()
                ->title('Error')
                ->body('No hay registros para generar el reporte')
                ->danger()
                ->send();

            return;
        }

        try {
            $html = View::make('reports.all-users-enrollment', [
                'all_enrollments' => $this->allEnrollments,
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

            $fileName = 'inscripciones-todos-usuarios-' .
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

    private function getTicketCode($batch): ?string
    {
        // Buscar el ticket asociado a este batch
        $ticket = \App\Models\Ticket::where('enrollment_batch_id', $batch->id)->first();
        return $ticket ? $ticket->ticket_code : null;
    }
}
