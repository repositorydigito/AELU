<?php

namespace App\Filament\Pages;

use App\Models\MonthlyPeriod;
use App\Models\Student;
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

class EnrollmentsReport1 extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.enrollments-report1';
    protected static ?string $title = 'Inscripciones por Alumno';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedStudent = null;
    public $selectedPeriod = null;
    public $studentEnrollments = [];
    public $studentData = null;
    public $ticketsCount = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Seleccionar Alumno')
                    ->placeholder('Selecciona un alumno...')
                    ->options(
                        Student::orderBy('last_names')
                            ->get()
                            ->mapWithKeys(function ($student) {
                                return [
                                    $student->id => $student->last_names.' '.$student->first_names.' - '.$student->student_code,
                                ];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedStudent = $state;
                        $this->loadStudentEnrollments();
                    })
                    ->required(),

                Select::make('monthly_period_id')
                    ->label('Período Mensual (Opcional)')
                    ->placeholder('Selecciona un período (opcional)...')
                    ->options(
                        MonthlyPeriod::where('year', '>=', 2026)
                            ->where('start_date', '<=', now()->addMonth())
                            ->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(fn ($period) => [
                                $period->id => $this->generatePeriodName($period->month, $period->year),
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->loadStudentEnrollments();
                    })
                    ->helperText('Deja vacío para ver todas las inscripciones del alumno'),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function loadStudentEnrollments(): void
    {
        if (! $this->selectedStudent) {
            $this->studentEnrollments = [];
            $this->studentData = null;
            $this->ticketsCount = 0;
            return;
        }

        // Cargar datos del estudiante
        $this->studentData = Student::find($this->selectedStudent);

        // Construir la consulta base para obtener tickets del estudiante
        $ticketsQuery = \App\Models\Ticket::where('student_id', $this->selectedStudent)
            ->with([
                'studentEnrollments.instructorWorkshop.workshop',
                'studentEnrollments.instructorWorkshop.instructor',
                'studentEnrollments.monthlyPeriod',
                'studentEnrollments.enrollmentBatch',
                'enrollmentBatch.paymentRegisteredByUser',
                'issuedByUser'
            ]);

        // Aplicar filtro de período si está seleccionado
        if ($this->selectedPeriod) {
            $ticketsQuery->whereHas('studentEnrollments', function ($query) {
                $query->where('monthly_period_id', $this->selectedPeriod);
            });
        }

        // Ejecutar consulta y procesar resultados
        $tickets = $ticketsQuery
            ->orderBy('issued_at', 'desc')
            ->get();

        $this->ticketsCount = $tickets->count();

        // Desglosar por inscripción individual (un taller por fila)
        $this->studentEnrollments = [];

        foreach ($tickets as $ticket) {
            foreach ($ticket->studentEnrollments as $enrollment) {
                $instructorWorkshop = $enrollment->instructorWorkshop;
                $workshop = $instructorWorkshop->workshop ?? null;
                $period = $enrollment->monthlyPeriod ?? null;

                // Formatear día de la semana + horario
                $dayOfWeek = '';
                if ($instructorWorkshop->day_of_week) {
                    if (is_array($instructorWorkshop->day_of_week)) {
                        $dayOfWeek = implode('/', $instructorWorkshop->day_of_week);
                    } else {
                        $dayOfWeek = $instructorWorkshop->day_of_week;
                    }
                }

                $schedule = $dayOfWeek;
                if ($instructorWorkshop->start_time && $instructorWorkshop->end_time) {
                    $startTime = \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i');
                    $endTime = \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i');
                    $schedule .= ' ' . $startTime . '-' . $endTime;
                }

                $this->studentEnrollments[] = [
                    'id' => $enrollment->id,
                    'workshop_name' => $workshop->name ?? 'N/A',
                    'schedule' => trim($schedule) ?: 'N/A',
                    'modality' => $workshop->modality ? ucfirst($workshop->modality) : 'N/A',
                    'number_of_classes' => $enrollment->number_of_classes,
                    'period_name' => $period ? $this->generatePeriodName($period->month, $period->year) : 'N/A',
                    'enrollment_date' => $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : 'N/A',
                    'amount' => $enrollment->total_amount,
                    'payment_method' => $enrollment->payment_method === 'cash' ? 'Efectivo' :
                                       ($enrollment->payment_method === 'link' ? 'Link' : ucfirst($enrollment->payment_method)),
                    'ticket_code' => $ticket->ticket_code,
                    'ticket_status' => $ticket->status === 'active' ? 'Activo' :
                                    ($ticket->status === 'cancelled' ? 'Anulado' :
                                    ($ticket->status === 'refunded' ? 'Reembolsado' : ucfirst($ticket->status))),
                    'issued_at' => $ticket->issued_at ? $ticket->issued_at->format('d/m/Y H:i') : 'N/A'
                ];
            }
        }
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => ! empty($this->studentEnrollments))
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
        if (! $this->studentData || empty($this->studentEnrollments)) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un alumno con tickets')
                ->danger()
                ->send();

            return;
        }

        try {
            // Obtener el nombre del período si está seleccionado
            $periodName = null;
            if ($this->selectedPeriod) {
                $period = MonthlyPeriod::find($this->selectedPeriod);
                if ($period) {
                    $periodName = $this->generatePeriodName($period->month, $period->year);
                }
            }

            // 1. Renderizar la vista Blade a HTML
            $html = View::make('reports.student-enrollments', [
                'student' => $this->studentData,
                'tickets' => $this->studentEnrollments, // Ahora son tickets
                'period_filter' => $periodName,
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

            // Nombre del archivo con período si aplica
            $fileName = 'tickets-'.str($this->studentData->first_names.'-'.$this->studentData->last_names)->slug();
            if ($periodName) {
                $fileName .= '-'.str($periodName)->slug();
            }
            $fileName .= '.pdf';

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
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $monthName = $monthNames[$month] ?? 'Mes '.$month;

        return $monthName.' '.$year;
    }

    // Método para obtener información del filtro aplicado (útil para la vista)
    public function getFilterDescription(): string
    {
        if (!$this->selectedStudent) {
            return '';
        }

        $description = 'Mostrando ';

        if ($this->selectedPeriod) {
            $period = MonthlyPeriod::find($this->selectedPeriod);
            if ($period) {
                $periodName = $this->generatePeriodName($period->month, $period->year);
                $description .= "tickets de {$periodName}";
            }
        } else {
            $description .= 'todos los tickets históricos';
        }

        return $description;
    }
}
