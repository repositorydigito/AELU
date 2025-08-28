<?php

namespace App\Filament\Pages;

use App\Models\MonthlyPeriod;
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

class EnrollmentsReport2 extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.enrollments-report2';
    protected static ?string $title = 'Inscripciones por Mes';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedPeriod = null;
    public $monthlyEnrollments = [];
    public $periodData = null;
    public $summaryData = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period_id')
                    ->label('Seleccionar Período Mensual')
                    ->placeholder('Buscar período (ej: Enero 2025)...')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        // Si no hay búsqueda, mostrar solo períodos recientes (últimos 2 años)
                        if (empty($search)) {
                            return MonthlyPeriod::where('year', '>=', now()->year - 1)
                                ->orderBy('year', 'asc')
                                ->orderBy('month', 'asc')
                                ->limit(24)
                                ->get()
                                ->mapWithKeys(function ($period) {
                                    $periodName = $this->generatePeriodName($period->month, $period->year);
                                    return [$period->id => $periodName];
                                })
                                ->toArray();
                        }

                        // Buscar por año o nombre de mes
                        $query = MonthlyPeriod::query();

                        // Si busca un año específico
                        if (is_numeric($search)) {
                            $query->where('year', 'like', "%{$search}%");
                        } else {
                            // Buscar por nombre de mes
                            $monthNames = [
                                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                                'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                                'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
                            ];

                            $searchLower = strtolower($search);
                            $monthFound = null;

                            foreach ($monthNames as $monthName => $monthNumber) {
                                if (str_contains($monthName, $searchLower)) {
                                    $monthFound = $monthNumber;
                                    break;
                                }
                            }

                            if ($monthFound) {
                                $query->where('month', $monthFound);
                            }

                            // También buscar por año si está incluido en la búsqueda
                            if (preg_match('/\d{4}/', $search, $matches)) {
                                $query->where('year', $matches[0]);
                            }
                        }

                        return $query->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($period) {
                                $periodName = $this->generatePeriodName($period->month, $period->year);
                                return [$period->id => $periodName];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $period = MonthlyPeriod::find($value);
                        return $period ? $this->generatePeriodName($period->month, $period->year) : null;
                    })
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->loadPeriodEnrollments();
                    })
                    ->required(),
            ])
            ->statePath('data');
    }

    public function loadPeriodEnrollments(): void
    {
        if (!$this->selectedPeriod) {
            $this->monthlyEnrollments = [];
            $this->periodData = null;
            $this->summaryData = [];
            return;
        }

        // Cargar datos del período
        $this->periodData = MonthlyPeriod::find($this->selectedPeriod);

        // Cargar todas las inscripciones del período mensual seleccionado
        $enrollments = StudentEnrollment::where('monthly_period_id', $this->selectedPeriod)
            ->with([
                'student',
                'instructorWorkshop.workshop',
                'instructorWorkshop.instructor',
                'enrollmentBatch',
                'creator'
            ])
            ->orderBy('enrollment_date', 'desc')
            ->get();

        $this->monthlyEnrollments = $enrollments->map(function ($enrollment) {
            $student = $enrollment->student ?? null;
            $workshop = $enrollment->instructorWorkshop->workshop ?? null;
            $instructor = $enrollment->instructorWorkshop->instructor ?? null;
            $batch = $enrollment->enrollmentBatch ?? null;
            $cashier = $enrollment->creator ?? null;

            return [
                'id' => $enrollment->id,
                'student_name' => $student ? ($student->first_names . ' ' . $student->last_names) : 'N/A',
                'student_document' => $student ? ($student->document_type . ': ' . $student->document_number) : 'N/A',
                'workshop_name' => $workshop->name ?? 'N/A',
                'instructor_name' => $instructor ? ($instructor->first_names . ' ' . $instructor->last_names) : 'N/A',
                'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                'number_of_classes' => $enrollment->number_of_classes,
                'total_amount' => $enrollment->total_amount,
                'payment_method' => $enrollment->payment_method === 'cash' ? 'Efectivo' : ($enrollment->payment_method === 'link' ? 'Link' : ucfirst($enrollment->payment_method)),
                'modality' => $workshop->modality ?? '',
                'payment_document' => $batch ? $batch->batch_code : '',
                'cashier_name' => $cashier ? $cashier->name : 'N/A',
            ];
        })->toArray();

        // Calcular datos de resumen
        $this->calculateSummary();
    }

    public function calculateSummary(): void
    {
        $enrollments = collect($this->monthlyEnrollments);

        $this->summaryData = [
            'total_enrollments' => $enrollments->count(),
            'total_students' => $enrollments->pluck('student_name')->unique()->count(),
            'total_workshops' => $enrollments->pluck('workshop_name')->unique()->count(),
            'total_amount' => $enrollments->sum('total_amount'),
            'cash_payments' => $enrollments->where('payment_method', 'Efectivo')->count(),
            'link_payments' => $enrollments->where('payment_method', 'Link')->count(),
        ];
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->monthlyEnrollments))
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
        if (!$this->periodData || empty($this->monthlyEnrollments)) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un período con inscripciones')
                ->danger()
                ->send();
            return;
        }

        try {
            // 1. Renderizar la vista Blade a HTML
            $html = View::make('reports.monthly-enrollments', [
                'period' => $this->periodData,
                'period_name' => $this->generatePeriodName($this->periodData->month, $this->periodData->year),
                'enrollments' => $this->monthlyEnrollments,
                'summary' => $this->summaryData,
                'generated_at' => now()->format('d/m/Y H:i')
            ])->render();

            // 2. Configurar Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            // 3. Configurar papel
            $dompdf->setPaper('A4', 'portrait');

            // 4. Renderizar el PDF
            $dompdf->render();

            $fileName = 'inscripciones-' . $this->periodData->year . '-' . str_pad($this->periodData->month, 2, '0', STR_PAD_LEFT) . '.pdf';

            // 5. Descargar el PDF
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

    private function generatePeriodName($month, $year): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $monthName = $monthNames[$month] ?? 'Mes ' . $month;
        return $monthName . ' ' . $year;
    }
}
