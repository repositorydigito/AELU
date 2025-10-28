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
                                
                                // Formatear día de la semana - el campo guarda strings como 'Lunes', 'Martes', etc.
                                $dayNames = [
                                    'Lunes' => 'Lun',
                                    'Martes' => 'Mar',
                                    'Miércoles' => 'Mié',
                                    'Miercoles' => 'Mié', // Sin tilde por si acaso
                                    'Jueves' => 'Jue',
                                    'Viernes' => 'Vie',
                                    'Sábado' => 'Sáb',
                                    'Sabado' => 'Sáb', // Sin tilde por si acaso
                                    'Domingo' => 'Dom'
                                ];
                                
                                $dayName = $dayNames[$workshop->day_of_week] ?? $workshop->day_of_week ?? 'N/A';
                                
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

            // Obtener todas las inscripciones para este taller en el periodo
            // A través de InstructorWorkshop -> StudentEnrollment
            $enrollments = \App\Models\StudentEnrollment::whereHas('instructorWorkshop', function ($query) {
                    $query->where('workshop_id', $this->selectedWorkshop);
                })
                ->where('monthly_period_id', $this->selectedPeriod)
                ->with([
                    'enrollmentBatch.student',
                    'enrollmentBatch.paymentRegisteredByUser',
                    'enrollmentBatch',
                    'instructorWorkshop'
                ])
                ->whereHas('enrollmentBatch', function ($query) {
                    $query->where('payment_status', '!=', 'refunded'); // Excluir anulados
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $this->scheduleEnrollments = $enrollments->map(function ($enrollment) {
                $batch = $enrollment->enrollmentBatch;
                $student = $batch->student ?? null;
                $user = $batch->paymentRegisteredByUser ?? null;

                return [
                    'student_name' => $student ? ($student->first_names . ' ' . $student->last_names) : 'N/A',
                    'student_code' => $student->student_code ?? 'N/A',
                    'enrollment_date' => $batch->enrollment_date ? $batch->enrollment_date->format('d/m/Y') : 'N/A',
                    'payment_registered_time' => $batch->payment_registered_at ? $batch->payment_registered_at->format('d/m/Y H:i') : 'N/A',
                    'total_amount' => $batch->total_amount,
                    'payment_method' => $this->getPaymentMethodText($batch->payment_method),
                    'payment_status' => $this->getPaymentStatusText($batch->payment_status),
                    'batch_code' => $batch->batch_code ?? 'Sin código',
                    'user_name' => $user ? $user->name : 'N/A',
                    'number_of_classes' => $enrollment->number_of_classes ?? 0,
                ];
            })->toArray();

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
        // Formatear día de la semana - el campo guarda strings como 'Lunes', 'Martes', etc.
        $dayNames = [
            'Lunes' => 'Lun',
            'Martes' => 'Mar',
            'Miércoles' => 'Mié',
            'Miercoles' => 'Mié', // Sin tilde por si acaso
            'Jueves' => 'Jue',
            'Viernes' => 'Vie',
            'Sábado' => 'Sáb',
            'Sabado' => 'Sáb', // Sin tilde por si acaso
            'Domingo' => 'Dom'
        ];
        
        $dayName = $dayNames[$workshop->day_of_week] ?? $workshop->day_of_week ?? 'N/A';
        
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

            $fileName = 'inscripciones-horario-' .
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