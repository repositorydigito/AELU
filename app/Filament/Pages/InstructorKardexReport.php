<?php

namespace App\Filament\Pages;

use App\Models\Instructor;
use App\Models\InstructorWorkshop;
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

class InstructorKardexReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

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
                                    $instructor->id => $instructor->first_names.' '.$instructor->last_names,
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
                        if (! $this->selectedInstructor) {
                            return [];
                        }

                        return InstructorWorkshop::where('instructor_id', $this->selectedInstructor)
                            ->with(['workshop'])
                            ->get()
                            ->filter(function ($instructorWorkshop) {
                                return $instructorWorkshop->workshop !== null;
                            })
                            ->mapWithKeys(function ($instructorWorkshop) {
                                $daysOfWeek = $instructorWorkshop->day_of_week;
                                if (is_array($daysOfWeek)) {
                                    $dayName = implode('/', $daysOfWeek);
                                } else {
                                    $dayName = $daysOfWeek ?? 'N/A';
                                }
                                $startTime = \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i');
                                $endTime = \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i');
                                $modality = $instructorWorkshop->workshop->modality ?? '';

                                $label = $instructorWorkshop->workshop->name.' - '.$dayName.' '.$startTime.' - '.$endTime . ' - ' .$modality;

                                return [
                                    $instructorWorkshop->id => $label,
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedWorkshop = $state;
                        $this->loadKardexEnrollments();
                    })
                    ->disabled(fn () => ! $this->selectedInstructor)
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }
    public function loadKardexEnrollments(): void
    {
        if (! $this->selectedInstructor || ! $this->selectedWorkshop) {
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
            if (! $this->instructorData || ! $this->workshopData || ! $this->workshopData->workshop) {
                $this->kardexEnrollments = [];
                return;
            }

            // Cargar todos los tickets que contienen inscripciones para este taller específico
            $tickets = \App\Models\Ticket::whereHas('studentEnrollments', function ($query) {
                    $query->where('instructor_workshop_id', $this->selectedWorkshop);
                })
                ->with([
                    'student',
                    'studentEnrollments' => function ($query) {
                        $query->where('instructor_workshop_id', $this->selectedWorkshop);
                    },
                    'enrollmentBatch.paymentRegisteredByUser',
                    'issuedByUser'
                ])
                ->orderBy('issued_at', 'desc')
                ->get();

            $this->kardexEnrollments = [];

            foreach ($tickets as $ticket) {
                $student = $ticket->student;
                $cashier = $ticket->enrollmentBatch->paymentRegisteredByUser ?? $ticket->issuedByUser;

                // Calcular datos específicos para este taller
                $relevantEnrollments = $ticket->studentEnrollments;
                $totalAmount = 0;
                $totalClasses = 0;

                foreach ($relevantEnrollments as $enrollment) {
                    $totalAmount += $enrollment->total_amount;
                    $totalClasses += $enrollment->number_of_classes;
                }

                // Determinar condición del estudiante
                $condition = 'N/A';
                if ($student) {
                    if ($student->is_pre_pama) {
                        $condition = 'PRE PAMA';
                    } else {
                        $condition = 'PAMA';
                    }
                }

                $this->kardexEnrollments[] = [
                    'id' => $ticket->id,
                    'fecha' => $ticket->issued_at->format('d/m/Y'),
                    'hora' => $ticket->issued_at->format('H:i:s'),
                    'tipo_documento' => 'Recibo',
                    'numero_documento' => $ticket->ticket_code,
                    'codigo_socio' => $student ? $student->student_code : 'N/A',
                    'apellidos_nombres' => $student ? ($student->last_names.', '.$student->first_names) : 'N/A',
                    'condicion' => $condition,
                    'moneda' => 'S/',
                    'importe' => $totalAmount,
                    'cajero' => $cashier ? $cashier->name : 'Sin registrar',
                    'number_of_classes' => $totalClasses,
                    'ticket_status' => $ticket->status === 'active' ? 'Activo' :
                                    ($ticket->status === 'cancelled' ? 'Anulado' :
                                    ($ticket->status === 'refunded' ? 'Reembolsado' : ucfirst($ticket->status))),
                ];
            }

        } catch (\Exception $e) {
            $this->kardexEnrollments = [];
            Notification::make()
                ->title('Error al cargar datos')
                ->body('Error: '.$e->getMessage())
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
            ->visible(fn () => ! empty($this->kardexEnrollments))
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
        if (empty($this->kardexEnrollments) || ! $this->selectedInstructor || ! $this->selectedWorkshop) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un profesor y taller con inscripciones')
                ->danger()
                ->send();

            return;
        }

        try {
            // Validar que tenemos los datos necesarios
            if (! $this->instructorData || ! $this->workshopData || ! $this->workshopData->workshop) {
                throw new \Exception('Datos del instructor o taller no disponibles');
            }

            $instructorName = $this->instructorData->first_names.' '.$this->instructorData->last_names;

            $daysOfWeek = $this->workshopData->day_of_week;
            if (is_array($daysOfWeek)) {
                $dayName = implode('/', $daysOfWeek);
            } else {
                $dayName = $daysOfWeek ?? 'N/A';
            }
            $startTime = \Carbon\Carbon::parse($this->workshopData->start_time)->format('H:i');
            $endTime = \Carbon\Carbon::parse($this->workshopData->end_time)->format('H:i');

            $workshopInfo = $this->workshopData->workshop->name.' - '.$dayName.' '.$startTime.'-'.$endTime;
            $modality = $this->workshopData->workshop->modality ?? '';

            $html = View::make('reports.instructor-kardex', [
                'enrollments' => $this->kardexEnrollments,
                'instructor_name' => $instructorName,
                'workshop_info' => $workshopInfo,
                'workshop_name' => $this->workshopData->workshop->name,
                'workshop_schedule' => $dayName.' '.$startTime.'-'.$endTime,
                'workshop_modality' => $modality,
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $fileName = 'kardex-'.str_replace(' ', '-', strtolower($instructorName)).'-'.
                       str_replace(' ', '-', strtolower($this->workshopData->workshop->name)).'.pdf';

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
