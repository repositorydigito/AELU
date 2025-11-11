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
                        MonthlyPeriod::where('year', '>=', now()->year - 2)
                            ->where('year', '<=', now()->year + 1)
                            ->orderBy('year', 'asc')
                            ->orderBy('month', 'asc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                return [
                                    $period->id => $this->generatePeriodName($period->month, $period->year),
                                ];
                            })
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
            return;
        }

        // Cargar datos del estudiante
        $this->studentData = Student::find($this->selectedStudent);

        // Construir la consulta base
        $query = StudentEnrollment::where('student_id', $this->selectedStudent)
            ->where('payment_status', 'completed')
            ->with([
                'instructorWorkshop.workshop',
                'instructorWorkshop.instructor',
                'monthlyPeriod',
                'enrollmentBatch.paymentRegisteredByUser',
                'enrollmentBatch',
                'creator',
            ]);

        // Aplicar filtro de período si está seleccionado
        if ($this->selectedPeriod) {
            $query->where('monthly_period_id', $this->selectedPeriod);
        }

        // Ejecutar consulta y procesar resultados
        $this->studentEnrollments = $query
            ->orderBy('enrollment_date', 'desc')
            ->get()
            ->map(function ($enrollment) {
                $workshop = $enrollment->instructorWorkshop->workshop ?? null;
                $instructor = $enrollment->instructorWorkshop->instructor ?? null;
                $period = $enrollment->monthlyPeriod ?? null;
                $batch = $enrollment->enrollmentBatch ?? null;
                $cashier = $enrollment->enrollmentBatch->paymentRegisteredByUser ?? null;

                return [
                    'id' => $enrollment->id,
                    'workshop_name' => $workshop->name ?? 'N/A',
                    'instructor_name' => $instructor ? ($instructor->first_names.' '.$instructor->last_names) : 'N/A',
                    'period_name' => $period ? $this->generatePeriodName($period->month, $period->year) : 'N/A',
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                    'enrollment_type' => $enrollment->enrollment_type,
                    'number_of_classes' => $enrollment->number_of_classes,
                    'total_amount' => $enrollment->total_amount,
                    'payment_method' => $enrollment->payment_method === 'cash' ? 'Efectivo' : ($enrollment->payment_method === 'link' ? 'Link' : ucfirst($enrollment->payment_method)),
                    'modality' => $workshop->modality ?? '',
                    'payment_document' => $batch ? ($this->getTicketCode($batch) ?? $batch->batch_code) : '',
                    'cashier_name' => $cashier ? $cashier->name : 'N/A',
                ];
            })
            ->toArray();
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
                ->body('Debe seleccionar un alumno con inscripciones')
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
                'enrollments' => $this->studentEnrollments,
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
            $fileName = 'inscripciones-'.str($this->studentData->first_names.'-'.$this->studentData->last_names)->slug();
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
                $description .= "inscripciones de {$periodName}";
            }
        } else {
            $description .= 'todas las inscripciones históricas';
        }

        return $description;
    }

    private function getTicketCode($batch): ?string
    {
        // Buscar el ticket asociado a este batch
        $ticket = \App\Models\Ticket::where('enrollment_batch_id', $batch->id)->first();
        return $ticket ? $ticket->ticket_code : null;
    }
}
