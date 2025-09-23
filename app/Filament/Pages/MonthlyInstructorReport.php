<?php

// App/Filament/Pages/MonthlyInstructorReport.php

namespace App\Filament\Pages;

use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use Carbon\Carbon;
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

class MonthlyInstructorReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.pages.monthly-instructor-report';

    protected static ?string $title = 'Reporte Mensual de Inscripciones';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public $selectedPeriod = null;

    public $volunteerWorkshops = [];

    public $hourlyWorkshops = [];

    public $summary = [
        'volunteer_total_enrollments' => 0,
        'volunteer_total_amount' => 0,
        'hourly_total_enrollments' => 0,
        'hourly_total_amount' => 0,
        'grand_total_enrollments' => 0,
        'grand_total_amount' => 0,
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('monthly_period_id')
                    ->label('Período Mensual')
                    ->placeholder('Selecciona un período...')
                    ->options(MonthlyPeriod::where('year', '>=', now()->year - 2)
                        ->where('year', '<=', now()->year + 1)
                        ->orderBy('year', 'desc')
                        ->orderBy('month', 'desc')
                        ->get()
                        ->mapWithKeys(function ($period) {
                            $monthNames = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                            $displayName = ($monthNames[$period->month] ?? 'Mes ' . $period->month) . ' ' . $period->year;
                            return [$period->id => $displayName];
                        }))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedPeriod = $state;
                        $this->loadReportData();
                    }),
            ])
            ->statePath('data')
            ->columns(1);
    }

    public function loadReportData(): void
    {
        if (!$this->selectedPeriod) {
            $this->volunteerWorkshops = [];
            $this->hourlyWorkshops = [];
            $this->resetSummary();
            return;
        }

        $data = InstructorWorkshop::with(['workshop', 'instructor'])
            ->whereHas('enrollments', function($query) {
                $query->where('monthly_period_id', $this->selectedPeriod)
                      ->whereIn('payment_status', ['completed', 'pending']);
            })
            ->get()
            ->groupBy('payment_type');

        // Procesar talleres de voluntarios
        $this->volunteerWorkshops = $this->processWorkshops($data->get('volunteer', collect()));

        // Procesar talleres por horas
        $this->hourlyWorkshops = $this->processWorkshops($data->get('hourly', collect()));

        $this->calculateSummary();
    }

    private function processWorkshops($workshops)
    {
        return $workshops->map(function($instructorWorkshop) {
            $enrollments = $instructorWorkshop->enrollments()
                ->where('monthly_period_id', $this->selectedPeriod)
                ->whereIn('payment_status', ['completed', 'pending'])
                ->get();

            return [
                'taller' => $instructorWorkshop->workshop->name,
                'horario' => $this->formatSchedule($instructorWorkshop),
                'modalidad' => $instructorWorkshop->workshop->modality,
                'instructor' => $instructorWorkshop->instructor->full_name,
                'inscripciones' => $enrollments->count(),
                'tarifa' => $instructorWorkshop->workshop->standard_monthly_fee,
                'total_recaudado' => $enrollments->sum('total_amount')
            ];
        })->toArray();
    }

    private function formatSchedule($instructorWorkshop): string
    {
        $startTime = Carbon::parse($instructorWorkshop->start_time)->format('H:i');
        $endTime = Carbon::parse($instructorWorkshop->end_time)->format('H:i');
        return $startTime . ' - ' . $endTime;
    }

    private function resetSummary(): void
    {
        $this->summary = [
            'volunteer_total_enrollments' => 0,
            'volunteer_total_amount' => 0,
            'hourly_total_enrollments' => 0,
            'hourly_total_amount' => 0,
            'grand_total_enrollments' => 0,
            'grand_total_amount' => 0,
        ];
    }

    private function calculateSummary(): void
    {
        $volunteerEnrollments = collect($this->volunteerWorkshops)->sum('inscripciones');
        $volunteerAmount = collect($this->volunteerWorkshops)->sum('total_recaudado');

        $hourlyEnrollments = collect($this->hourlyWorkshops)->sum('inscripciones');
        $hourlyAmount = collect($this->hourlyWorkshops)->sum('total_recaudado');

        $this->summary = [
            'volunteer_total_enrollments' => $volunteerEnrollments,
            'volunteer_total_amount' => $volunteerAmount,
            'hourly_total_enrollments' => $hourlyEnrollments,
            'hourly_total_amount' => $hourlyAmount,
            'grand_total_enrollments' => $volunteerEnrollments + $hourlyEnrollments,
            'grand_total_amount' => $volunteerAmount + $hourlyAmount,
        ];
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->volunteerWorkshops) || !empty($this->hourlyWorkshops))
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
        if ((empty($this->volunteerWorkshops) && empty($this->hourlyWorkshops)) || !$this->selectedPeriod) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un período que tenga registros')
                ->danger()
                ->send();
            return;
        }

        try {
            $period = MonthlyPeriod::find($this->selectedPeriod);
            $monthNames = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $periodName = $period ? (($monthNames[$period->month] ?? 'Mes ' . $period->month) . ' ' . $period->year) : 'N/A';

            $html = View::make('reports.monthly-instructors', [
                'volunteer_workshops' => $this->volunteerWorkshops,
                'hourly_workshops' => $this->hourlyWorkshops,
                'summary' => $this->summary,
                'period_name' => $periodName,
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $fileName = 'reporte-mensual-instructores-'.
                       str_replace([' ', '/'], '-', strtolower($periodName)).'.pdf';

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
}
