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

        // Obtener todos los tickets en lugar de EnrollmentBatch
        $tickets = \App\Models\Ticket::with([
            'student',
            'issuedByUser',
            'enrollmentBatch.paymentRegisteredByUser',
            'studentEnrollments.instructorWorkshop.workshop'
        ])
            ->whereHas('enrollmentBatch.paymentRegisteredByUser', function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Delegado');
                });
            })
            ->whereDate('issued_at', '>=', $dateFromForQuery)
            ->whereDate('issued_at', '<=', $dateToForQuery)
            ->orderBy('issued_at', 'desc')
            ->get();

        if ($tickets->isEmpty()) {
            $this->allEnrollments = [];
            return;
        }

        // Crear un array plano con todas las inscripciones (tickets)
        $this->allEnrollments = $tickets->map(function ($ticket) {
            $student = $ticket->student;
            $user = $ticket->enrollmentBatch->paymentRegisteredByUser ?? $ticket->issuedByUser;

            // Calcular totales del ticket
            $totalAmount = $ticket->studentEnrollments->sum('total_amount');
            $workshopsCount = $ticket->studentEnrollments->count();
            $workshopsList = $ticket->studentEnrollments->pluck('instructorWorkshop.workshop.name')->filter()->join(', ');

            // Obtener fecha de inscripción de la primera enrollment
            $firstEnrollment = $ticket->studentEnrollments->first();
            $enrollmentDate = $firstEnrollment ? $firstEnrollment->enrollment_date : $ticket->issued_at;

            return [
                'id' => $ticket->id,
                'user_name' => $user ? $user->name : 'N/A',
                'student_name' => $student ? ($student->last_names . ' ' . $student->first_names) : 'N/A',
                'student_code' => $student->student_code ?? 'N/A',
                'payment_registered_time' => $ticket->issued_at ? $ticket->issued_at->format('d/m/Y H:i') : 'N/A',
                'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('d/m/Y') : 'N/A',
                'workshops_count' => $workshopsCount,
                'workshops_list' => $workshopsList ?: 'N/A',
                'total_amount' => $totalAmount,
                'payment_method' => $this->getPaymentMethodText($firstEnrollment->payment_method ?? 'cash'),
                'ticket_code' => $ticket->ticket_code,
                'payment_status' => $this->getTicketStatusText($ticket->status),
            ];
        })->toArray();
    }

    private function getTicketStatusText($status): string
    {
        return match ($status) {
            'active' => 'Inscrito',
            'cancelled' => 'Anulado',
            'refunded' => 'Reembolsado',
            default => ucfirst($status),
        };
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

}
