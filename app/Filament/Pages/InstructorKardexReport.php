<?php

// App/Filament/Pages/InstructorKardexReport.php
namespace App\Filament\Pages;

use App\Models\Instructor;
use App\Models\InstructorWorkshop;
use App\Models\StudentEnrollment;
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

class InstructorKardexReport extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.pages.instructor-kardex-report';
    protected static ?string $title = 'Kardex por Profesor';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedInstructor = null;
    public $selectedWorkshop = null;
    public $kardexEnrollments = [];
    public $instructorData = null;
    public $workshopData = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('instructor_id')
                    ->label('Profesor')
                    ->placeholder('Selecciona un profesor...')
                    ->options(function () {
                        return Instructor::orderBy('first_names')
                            ->get()
                            ->mapWithKeys(function ($instructor) {
                                return [
                                    $instructor->id => $instructor->first_names . ' ' . $instructor->last_names
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedInstructor = $state;
                        $this->selectedWorkshop = null;
                        $this->kardexEnrollments = [];
                        // Corregir esta línea - usar dot notation correcta
                        $this->data['workshop_id'] = null;
                    })
                    ->required(),

                Select::make('workshop_id')
                    ->label('Taller/Horario')
                    ->placeholder('Selecciona un taller...')
                    ->options(function () {
                        if (!$this->selectedInstructor) {
                            return [];
                        }

                        return InstructorWorkshop::where('instructor_id', $this->selectedInstructor)
                            ->with(['workshop'])
                            ->get()
                            ->filter(function ($instructorWorkshop) {
                                return $instructorWorkshop->workshop !== null;
                            })
                            ->mapWithKeys(function ($instructorWorkshop) {
                                $dayNames = [
                                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                    7 => 'Domingo', 0 => 'Domingo'
                                ];

                                $dayName = $dayNames[$instructorWorkshop->day_of_week] ?? 'Día ' . $instructorWorkshop->day_of_week;
                                $startTime = \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i');
                                $endTime = \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i');
                                $modality = $instructorWorkshop->workshop->modality ?? '';

                                $label = $instructorWorkshop->workshop->name . ' - ' . $dayName . ' ' . $startTime . ' - ' . $endTime;

                                return [
                                    $instructorWorkshop->id => $label
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedWorkshop = $state;
                        $this->loadKardexEnrollments();
                    })
                    ->disabled(fn () => !$this->selectedInstructor)
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadKardexEnrollments(): void
    {
        if (!$this->selectedInstructor || !$this->selectedWorkshop) {
            $this->kardexEnrollments = [];
            $this->instructorData = null;
            $this->workshopData = null;
            return;
        }

        try {
            // Cargar datos del instructor y taller
            $this->instructorData = Instructor::find($this->selectedInstructor);
            $this->workshopData = InstructorWorkshop::with('workshop')->find($this->selectedWorkshop);

            // Verificar que se encontraron los datos
            if (!$this->instructorData || !$this->workshopData || !$this->workshopData->workshop) {
                $this->kardexEnrollments = [];
                return;
            }

            // Cargar todas las inscripciones para este taller específico
            $enrollments = StudentEnrollment::where('instructor_workshop_id', $this->selectedWorkshop)
                ->where('payment_status', 'completed') // Solo inscripciones pagadas
                ->with([
                    'student',
                    'enrollmentBatch.paymentRegisteredByUser',
                    'enrollmentBatch'
                ])
                ->orderBy('created_at', 'desc') // Ordenar por fecha de creación
                ->get();

            $this->kardexEnrollments = $enrollments->map(function ($enrollment) {
                $student = $enrollment->student;
                $batch = $enrollment->enrollmentBatch;
                $cashier = $batch && $batch->paymentRegisteredByUser ? $batch->paymentRegisteredByUser : null;

                // Determinar condición del estudiante
                $condition = 'N/A';
                if ($student) {
                    if ($student->is_pre_pama) {
                        $condition = 'PRE PAMA';
                    } else {
                        $condition = 'PAMA';
                    }
                }

                return [
                    'id' => $enrollment->id,
                    'fecha' => $enrollment->created_at->format('d/m/Y'),
                    'hora' => $enrollment->created_at->format('H:i:s'),
                    'tipo_documento' => 'Recibo', // Como en la imagen
                    'numero_documento' => $batch ? ($batch->batch_code ?? 'Sin código') : 'Sin lote',
                    'codigo_socio' => $student ? $student->student_code : 'N/A',
                    'apellidos_nombres' => $student ? ($student->last_names . ', ' . $student->first_names) : 'N/A',
                    'condicion' => $condition,
                    'moneda' => 'S/',
                    'importe' => $enrollment->total_amount,
                    'cajero' => $cashier ? $cashier->name : 'Sin registrar',
                    'number_of_classes' => $enrollment->number_of_classes,
                ];
            })->toArray();

        } catch (\Exception $e) {
            $this->kardexEnrollments = [];
            Notification::make()
                ->title('Error al cargar datos')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->kardexEnrollments))
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
        if (empty($this->kardexEnrollments) || !$this->selectedInstructor || !$this->selectedWorkshop) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un profesor y taller con inscripciones')
                ->danger()
                ->send();
            return;
        }

        try {
            // Validar que tenemos los datos necesarios
            if (!$this->instructorData || !$this->workshopData || !$this->workshopData->workshop) {
                throw new \Exception('Datos del instructor o taller no disponibles');
            }

            $instructorName = $this->instructorData->first_names . ' ' . $this->instructorData->last_names;

            $dayNames = [
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                7 => 'Domingo', 0 => 'Domingo'
            ];

            $dayName = $dayNames[$this->workshopData->day_of_week] ?? 'Día ' . $this->workshopData->day_of_week;
            $startTime = \Carbon\Carbon::parse($this->workshopData->start_time)->format('H:i');
            $endTime = \Carbon\Carbon::parse($this->workshopData->end_time)->format('H:i');

            $workshopInfo = $this->workshopData->workshop->name . ' - ' . $dayName . ' ' . $startTime . '-' . $endTime;

            $html = View::make('reports.instructor-kardex', [
                'enrollments' => $this->kardexEnrollments,
                'instructor_name' => $instructorName,
                'workshop_info' => $workshopInfo,
                'workshop_name' => $this->workshopData->workshop->name,
                'workshop_schedule' => $dayName . ' ' . $startTime . '-' . $endTime,
                'generated_at' => now()->format('d/m/Y H:i')
            ])->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $fileName = 'kardex-' . str_replace(' ', '-', strtolower($instructorName)) . '-' .
                       str_replace(' ', '-', strtolower($this->workshopData->workshop->name)) . '.pdf';

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
        if ($key === 'instructor_id') {
            $this->selectedInstructor = $value;
            $this->selectedWorkshop = null;
            $this->kardexEnrollments = [];
            $this->data['workshop_id'] = null;
        } elseif ($key === 'workshop_id') {
            $this->selectedWorkshop = $value;
            $this->loadKardexEnrollments();
        }
    }
}
