<?php

namespace App\Filament\Pages;

use App\Models\MonthlyPeriod;
use App\Models\Workshop;
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

class ScheduleEnrollmentReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.schedule-enrollment-report';
    protected static ?string $title = 'Inscripciones por Horario';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedPeriod = null;
    public $selectedWorkshop = null;
    public $scheduleEnrollments = [];
    public $periodData = null;
    public $workshopData = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('monthly_period_id')
                    ->label('Periodo Mensual')
                    ->placeholder('Selecciona un periodo...')
                    ->options(function () {
                        // Obtener todos los periodos hasta 2 meses adelante del mes actual
                        $twoMonthsAhead = now()->addMonths(2)->endOfMonth();

                        return MonthlyPeriod::where('start_date', '<=', $twoMonthsAhead)
                            ->orderBy('start_date', 'desc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                // Formatear como "Octubre 2025"
                                $startDate = \Carbon\Carbon::parse($period->start_date);
                                $monthName = ucfirst($startDate->locale('es')->isoFormat('MMMM YYYY'));

                                return [
                                    $period->id => $monthName,
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->selectedWorkshop = null;
                        $this->scheduleEnrollments = [];
                        $this->data['workshop_id'] = null;
                    })
                    ->required(),

                Select::make('workshop_id')
                    ->label('Taller')
                    ->placeholder('Selecciona un taller...')
                    ->options(function () {
                        if (!$this->selectedPeriod) {
                            return [];
                        }

                        // Obtener el periodo seleccionado
                        $period = MonthlyPeriod::find($this->selectedPeriod);
                        if (!$period) {
                            return [];
                        }

                        // Obtener todos los talleres (Workshop) del periodo seleccionado
                        return Workshop::with(['instructor'])
                            ->where('monthly_period_id', $period->id)
                            ->get()
                            ->mapWithKeys(function ($workshop) {
                                $instructorName = $workshop->instructor
                                    ? ($workshop->instructor->first_names . ' ' . $workshop->instructor->last_names)
                                    : 'Sin profesor';

                                // Manejar day_of_week como array
                                $daysOfWeek = $workshop->day_of_week;
                                if (is_array($daysOfWeek)) {
                                    $dayAbbreviations = [
                                        'Lunes' => 'Lun', 'Martes' => 'Mar', 'Miércoles' => 'Mié',
                                        'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sábado' => 'Sáb', 'Domingo' => 'Dom'
                                    ];
                                    $dayName = implode('/', array_map(fn($day) => $dayAbbreviations[$day] ?? $day, $daysOfWeek));
                                } else {
                                    $dayName = $daysOfWeek ?? 'N/A';
                                }

                                $startTime = $workshop->start_time ? \Carbon\Carbon::parse($workshop->start_time)->format('H:i') : 'N/A';
                                $endTime = $workshop->end_time ?? 'N/A';

                                $scheduleInfo = $dayName . ' | ' . $startTime . '-' . $endTime;
                                $modality = $workshop->modality ?? 'Sin modalidad';

                                $label = $workshop->name . ' - ' . $scheduleInfo . ' (' . $modality . ') - Prof. ' . $instructorName;

                                return [
                                    $workshop->id => $label,
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedWorkshop = $state;
                        $this->loadScheduleEnrollments();
                    })
                    ->disabled(fn () => !$this->selectedPeriod)
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadScheduleEnrollments(): void
    {
        if (!$this->selectedPeriod || !$this->selectedWorkshop) {
            $this->scheduleEnrollments = [];
            $this->periodData = null;
            $this->workshopData = null;
            return;
        }

        try {
            // Cargar datos del periodo y taller
            $this->periodData = MonthlyPeriod::find($this->selectedPeriod);
            $this->workshopData = Workshop::with(['instructor'])
                ->find($this->selectedWorkshop);

            if (!$this->periodData || !$this->workshopData) {
                $this->scheduleEnrollments = [];
                return;
            }

            // Obtener todos los tickets que contienen inscripciones para este taller en el periodo
            $tickets = \App\Models\Ticket::whereHas('studentEnrollments', function ($query) {
                    $query->whereHas('instructorWorkshop', function ($subQuery) {
                        $subQuery->where('workshop_id', $this->selectedWorkshop);
                    })
                    ->where('monthly_period_id', $this->selectedPeriod);
                })
                ->with([
                    'student',
                    'studentEnrollments' => function ($query) {
                        $query->whereHas('instructorWorkshop', function ($subQuery) {
                            $subQuery->where('workshop_id', $this->selectedWorkshop);
                        })
                        ->where('monthly_period_id', $this->selectedPeriod);
                    },
                    'studentEnrollments.instructorWorkshop',
                    'enrollmentBatch.paymentRegisteredByUser',
                    'issuedByUser'
                ])
                ->orderBy('issued_at', 'desc')
                ->get();

            $this->scheduleEnrollments = [];

            foreach ($tickets as $ticket) {
                $student = $ticket->student ?? null;
                $cashier = $ticket->enrollmentBatch->paymentRegisteredByUser ?? $ticket->issuedByUser ?? null;

                // Calcular datos específicos para este taller en este período
                $relevantEnrollments = $ticket->studentEnrollments;
                $totalAmount = 0;
                $totalClasses = 0;
                $paymentMethod = '';
                $enrollmentDate = null;

                foreach ($relevantEnrollments as $enrollment) {
                    $totalAmount += $enrollment->total_amount;
                    $totalClasses += $enrollment->number_of_classes;

                    if (!$paymentMethod) {
                        $paymentMethod = $this->getPaymentMethodText($enrollment->payment_method);
                    }

                    if (!$enrollmentDate) {
                        $enrollmentDate = $enrollment->enrollment_date;
                    }
                }

                $this->scheduleEnrollments[] = [
                    'student_name' => $student ? ($student->last_names . ' ' . $student->first_names) : 'N/A',
                    'student_code' => $student->student_code ?? 'N/A',
                    'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('d/m/Y') : 'N/A',
                    'payment_registered_time' => $ticket->issued_at ? $ticket->issued_at->format('d/m/Y H:i') : 'N/A',
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $this->getTicketStatusText($ticket->status),
                    'ticket_code' => $ticket->ticket_code,
                    'user_name' => $cashier ? $cashier->name : 'N/A',
                    'number_of_classes' => $totalClasses,
                ];
            }

        } catch (\Exception $e) {
            $this->scheduleEnrollments = [];
            Notification::make()
                ->title('Error al cargar datos')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function formatWorkshopSchedule($workshop): string
    {
        // Manejar day_of_week como array
        $daysOfWeek = $workshop->day_of_week;
        if (is_array($daysOfWeek)) {
            $dayAbbreviations = [
                'Lunes' => 'Lun', 'Martes' => 'Mar', 'Miércoles' => 'Mié',
                'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sábado' => 'Sáb', 'Domingo' => 'Dom'
            ];
            $dayName = implode('/', array_map(fn($day) => $dayAbbreviations[$day] ?? $day, $daysOfWeek));
        } else {
            $dayName = $daysOfWeek ?? 'N/A';
        }

        $startTime = $workshop->start_time ? \Carbon\Carbon::parse($workshop->start_time)->format('H:i') : 'N/A';
        $endTime = $workshop->end_time ?? 'N/A';

        return $dayName . ' | ' . $startTime . '-' . $endTime;
    }

    private function formatSchedule($schedule): string
    {
        $days = [];
        if ($schedule->monday) $days[] = 'Lun';
        if ($schedule->tuesday) $days[] = 'Mar';
        if ($schedule->wednesday) $days[] = 'Mié';
        if ($schedule->thursday) $days[] = 'Jue';
        if ($schedule->friday) $days[] = 'Vie';
        if ($schedule->saturday) $days[] = 'Sáb';
        if ($schedule->sunday) $days[] = 'Dom';

        $daysStr = implode(', ', $days);

        $startTime = $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '';
        $endTime = $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '';

        return $daysStr . ' | ' . $startTime . ' - ' . $endTime;
    }

    private function getTicketStatusText($status): string
    {
        return match ($status) {
            'active' => 'Activo',
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
            ->visible(fn() => !empty($this->scheduleEnrollments))
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
        if (empty($this->scheduleEnrollments) || !$this->selectedPeriod || !$this->selectedWorkshop) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un periodo y taller con inscripciones')
                ->danger()
                ->send();

            return;
        }

        try {
            if (!$this->periodData || !$this->workshopData) {
                throw new \Exception('Datos del periodo o taller no disponibles');
            }

            $workshopName = $this->workshopData->name;
            $scheduleInfo = $this->formatWorkshopSchedule($this->workshopData);
            $instructor = $this->workshopData->instructor;
            $instructorName = $instructor ? ($instructor->first_names . ' ' . $instructor->last_names) : 'Sin profesor';
            $modality = $this->workshopData->modality ?? 'Sin modalidad';

            // Formatear periodo como "Octubre 2025"
            $startDate = \Carbon\Carbon::parse($this->periodData->start_date);
            $periodName = ucfirst($startDate->locale('es')->isoFormat('MMMM YYYY'));

            $html = View::make('reports.schedule-enrollment', [
                'enrollments' => $this->scheduleEnrollments,
                'period_name' => $periodName,
                'period_dates' => \Carbon\Carbon::parse($this->periodData->start_date)->format('d/m/Y') . ' - ' .
                                 \Carbon\Carbon::parse($this->periodData->end_date)->format('d/m/Y'),
                'workshop_name' => $workshopName,
                'schedule_info' => $scheduleInfo,
                'instructor_name' => $instructorName,
                'modality' => $modality,
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');

            $dompdf->render();

            $fileName = 'tickets-horario-' .
                str_replace(' ', '-', strtolower($workshopName)) . '-' .
                str_replace(' ', '-', strtolower($periodName)) . '.pdf';

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

    // Método para limpiar el estado cuando sea necesario
    public function updatedData($value, $key)
    {
        if ($key === 'monthly_period_id') {
            $this->selectedPeriod = $value;
            $this->selectedWorkshop = null;
            $this->scheduleEnrollments = [];
            $this->data['workshop_id'] = null;
        } elseif ($key === 'workshop_id') {
            $this->selectedWorkshop = $value;
            $this->loadScheduleEnrollments();
        }
    }

}
