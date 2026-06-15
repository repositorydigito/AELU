<?php

namespace App\Filament\Pages;

use App\Exports\AllInstructorsPaymentExport;
use App\Models\Instructor;
use App\Models\InstructorPayment;
use App\Models\MonthlyPeriod;
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
use Livewire\Attributes\Url;

class AllInstructorsPaymentReport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.all-instructors-payment-report';
    protected static ?string $title = 'Pago de Profesores - General';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    #[Url(as: 'period')]
    public $selectedMonthlyPeriodId = null;
    #[Url(as: 'instructor')]
    public array $selectedInstructorIds = [];
    public $allInstructorPayments = [];
    public $totalAmount = 0;

    public function mount(): void
    {
        $this->form->fill([
            'monthly_period_id' => $this->selectedMonthlyPeriodId,
            'instructor_id'     => $this->selectedInstructorIds,
        ]);

        if ($this->selectedMonthlyPeriodId) {
            $this->loadAllInstructorPayments();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('monthly_period_id')
                    ->label('Período Mensual')
                    ->placeholder('Selecciona un período...')
                    ->options(function () {
                        return MonthlyPeriod::where('year', '>=', 2026)
                            ->where('start_date', '<=', now()->addMonth())
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
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedMonthlyPeriodId = $state;
                        $this->selectedInstructorIds = [];
                        $this->form->fill([
                            'monthly_period_id' => $state,
                            'instructor_id'     => [],
                        ]);
                        $this->loadAllInstructorPayments();
                    })
                    ->required(),
                Select::make('instructor_id')
                    ->label('Profesor')
                    ->placeholder('Todos los profesores...')
                    ->options(function () {
                        if (!$this->selectedMonthlyPeriodId) return [];
                        return Instructor::whereHas('instructorPayments', fn($q) =>
                                $q->where('monthly_period_id', $this->selectedMonthlyPeriodId))
                            ->orderBy('last_names')
                            ->get()
                            ->mapWithKeys(fn($i) => [$i->id => $i->last_names . ' ' . $i->first_names]);
                    })
                    ->multiple()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedInstructorIds = $state ?? [];
                        $this->loadAllInstructorPayments();
                    })
                    ->visible(fn() => (bool) $this->selectedMonthlyPeriodId),
            ])
            ->statePath('data');
    }

    public function loadAllInstructorPayments(): void
    {
        if (!$this->selectedMonthlyPeriodId) {
            $this->allInstructorPayments = [];
            $this->totalAmount = 0;
            return;
        }

        $instructorPayments = InstructorPayment::with([
            'instructor',
            'instructorWorkshop.workshop',
            'monthlyPeriod'
        ])
            ->where('monthly_period_id', $this->selectedMonthlyPeriodId)
            ->when(!empty($this->selectedInstructorIds), fn($q) =>
                $q->whereIn('instructor_id', $this->selectedInstructorIds))
            ->orderBy('instructor_id')
            ->get();

        if ($instructorPayments->isEmpty()) {
            $this->allInstructorPayments = [];
            $this->totalAmount = 0;
            return;
        }

        $grouped = ['volunteer' => [], 'hourly' => []];

        foreach ($instructorPayments as $payment) {
            $instructor = $payment->instructor;
            $instructorWorkshop = $payment->instructorWorkshop;
            $workshop = $instructorWorkshop->workshop ?? null;
            $modality = $payment->payment_type; // 'volunteer' o 'hourly'
            $instructorId = $instructor->id;

            // Formatear horario
            $dayOfWeek = $instructorWorkshop->day_of_week ?? 'N/A';
            if (is_array($dayOfWeek)) {
                $dayNames = [
                    0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                ];
                $dayOfWeek = collect($dayOfWeek)->map(fn($day) => $dayNames[$day] ?? $day)->join('/');
            }
            $startTime = $instructorWorkshop->start_time
                ? \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i')
                : 'N/A';
            $endTime = $instructorWorkshop->end_time
                ? \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i')
                : 'N/A';

            $amount = $payment->calculated_amount ?? 0;

            $latestEnrollments = StudentEnrollment::with(['student:id,category_partner'])
                ->where('instructor_workshop_id', $payment->instructor_workshop_id)
                ->where('monthly_period_id', $this->selectedMonthlyPeriodId)
                ->whereNotIn('payment_status', ['refunded'])
                ->select('student_id', 'number_of_classes', 'price_per_quantity', 'total_amount', 'id')
                ->get()
                ->groupBy('student_id')
                ->map(fn($rows) => $rows->sortByDesc('id')->first());

            $workshopModality = $workshop->modality ?? null;

            if (!isset($grouped[$modality][$instructorId])) {
                $grouped[$modality][$instructorId] = [
                    'instructor_id'   => $instructorId,
                    'instructor_name' => $instructor->last_names . ' ' . $instructor->first_names,
                    'instructor_code' => $instructor->code ?? 'N/A',
                    'workshops'       => [],
                    'subtotal'        => 0,
                ];
            }

            $tierGroups = $latestEnrollments
                ->groupBy(fn($row) => ((int)($row->number_of_classes ?? 0)).'|'.round((float)($row->total_amount ?? 0), 2))
                ->sortBy(function ($group, $key) {
                    [$classes, $price] = explode('|', $key);
                    return [-(int)$classes, (float)$price];
                });

            $appliedVolunteerPct = $payment->applied_volunteer_percentage ?? 0;
            $isFirst = true;

            if ($tierGroups->isEmpty()) {
                $workshopRow = [
                    'workshop_name'           => $workshop->name ?? 'N/A',
                    'schedule'                => "{$dayOfWeek} {$startTime}-{$endTime}",
                    'modality'                => $workshopModality,
                    'standard_fee'            => $workshop->standard_monthly_fee ?? 0,
                    'class_count'             => null,
                    'total_students'          => 0,
                    'students_by_category'    => [],
                    'unit_amount_by_category' => [],
                    'monthly_revenue'         => $payment->monthly_revenue ?? 0,
                    'amount'                  => $amount,
                    'payment_status'          => $this->getPaymentStatusText($payment->payment_status),
                    'document_number'         => $payment->document_number ?? null,
                ];
                if ($modality === 'hourly') {
                    $workshopRow['hours_worked']     = $payment->total_hours ?? 0;
                    $workshopRow['hourly_rate']       = $payment->applied_hourly_rate ?? 0;
                    $workshopRow['is_secondary_tier'] = false;
                } else {
                    $workshopRow['volunteer_percentage'] = $appliedVolunteerPct * 100;
                }
                $grouped[$modality][$instructorId]['workshops'][] = $workshopRow;
            } else {
                foreach ($tierGroups as $tierKey => $tierEnrollments) {
                    [$classCount, $tierPrice] = explode('|', $tierKey);
                    $classCount    = (int) $classCount;
                    $tierPrice     = (float) $tierPrice;
                    $tierRevenue   = (float) $tierEnrollments->sum('total_amount');
                    $tierCount     = $tierEnrollments->count();

                    $tierCategoryBreakdown = $tierEnrollments
                        ->groupBy(function ($row) {
                            $category = $row->student->category_partner ?? null;
                            return filled($category) ? $category : 'Sin categoría';
                        })
                        ->map(fn($group) => $group->count())
                        ->sortKeys()
                        ->toArray();

                    $tierCategoryUnitAmount = $tierEnrollments
                        ->groupBy(function ($row) {
                            $category = $row->student->category_partner ?? null;
                            return filled($category) ? $category : 'Sin categoría';
                        })
                        ->map(function ($group) {
                            $amounts = $group
                                ->map(fn($row) => round((float) ($row->total_amount ?? 0), 2))
                                ->filter(fn($amt) => $amt > 0)
                                ->values();
                            if ($amounts->isEmpty()) {
                                return 0;
                            }
                            return (float) $amounts->countBy()->sortDesc()->keys()->first();
                        })
                        ->sortKeys()
                        ->toArray();

                    $tierAmount = $modality === 'volunteer'
                        ? round($tierRevenue * $appliedVolunteerPct, 2)
                        : ($isFirst ? $amount : 0);

                    $workshopRow = [
                        'workshop_name'           => $workshop->name ?? 'N/A',
                        'schedule'                => "{$dayOfWeek} {$startTime}-{$endTime}",
                        'modality'                => $workshopModality,
                        'standard_fee'            => $tierPrice,
                        'class_count'             => $classCount,
                        'total_students'          => $tierCount,
                        'students_by_category'    => $tierCategoryBreakdown,
                        'unit_amount_by_category' => $tierCategoryUnitAmount,
                        'monthly_revenue'         => $tierRevenue,
                        'amount'                  => $tierAmount,
                        'payment_status'          => $this->getPaymentStatusText($payment->payment_status),
                        'document_number'         => $payment->document_number ?? null,
                    ];

                    if ($modality === 'hourly') {
                        $workshopRow['hours_worked']     = $isFirst ? ($payment->total_hours ?? 0) : 0;
                        $workshopRow['hourly_rate']       = $payment->applied_hourly_rate ?? 0;
                        $workshopRow['is_secondary_tier'] = !$isFirst;
                    } else {
                        $workshopRow['volunteer_percentage'] = $appliedVolunteerPct * 100;
                    }

                    $grouped[$modality][$instructorId]['workshops'][] = $workshopRow;
                    $isFirst = false;
                }
            }

            $grouped[$modality][$instructorId]['subtotal'] += $amount;
        }

        foreach (['volunteer', 'hourly'] as $type) {
            foreach ($grouped[$type] as &$instructorData) {
                usort($instructorData['workshops'], function ($a, $b) {
                    $n = strcmp($a['workshop_name'], $b['workshop_name']);
                    if ($n !== 0) return $n;
                    $s = strcmp($a['schedule'], $b['schedule']);
                    if ($s !== 0) return $s;
                    $m = strcmp($a['modality'] ?? '', $b['modality'] ?? '');
                    if ($m !== 0) return $m;
                    return ($b['class_count'] ?? 0) <=> ($a['class_count'] ?? 0);
                });

                // Pre-compute rowspan metadata for table display
                $ws    = &$instructorData['workshops'];
                $total = count($ws);
                // % column spans all rows of this instructor (same pct for all workshops)
                if ($total > 0) {
                    $ws[0]['instructor_pct_rowspan'] = $total;
                }
                for ($i = 0; $i < $total; $i++) {
                    if ($i > 0) $ws[$i]['instructor_pct_rowspan'] = 0;
                    // name_rowspan: merge consecutive rows with same workshop_name
                    if ($i === 0 || $ws[$i]['workshop_name'] !== $ws[$i - 1]['workshop_name']) {
                        $span = 1;
                        for ($j = $i + 1; $j < $total; $j++) {
                            if ($ws[$j]['workshop_name'] === $ws[$i]['workshop_name']) $span++;
                            else break;
                        }
                        $ws[$i]['name_rowspan'] = $span;
                    } else {
                        $ws[$i]['name_rowspan'] = 0;
                    }

                    // schedule_rowspan: merge consecutive rows with same name + schedule + modality
                    $sameName     = $i === 0 || $ws[$i]['workshop_name'] !== $ws[$i - 1]['workshop_name'];
                    $sameSched    = $i > 0
                        && $ws[$i]['schedule'] === $ws[$i - 1]['schedule']
                        && ($ws[$i]['modality'] ?? null) === ($ws[$i - 1]['modality'] ?? null);
                    $newSchedGroup = $sameName || !$sameSched;

                    if ($newSchedGroup) {
                        $span         = 1;
                        $schedRevenue = $ws[$i]['monthly_revenue'];
                        $schedAmount  = $ws[$i]['amount'];
                        for ($j = $i + 1; $j < $total; $j++) {
                            if ($ws[$j]['workshop_name'] === $ws[$i]['workshop_name']
                                && $ws[$j]['schedule'] === $ws[$i]['schedule']
                                && ($ws[$j]['modality'] ?? null) === ($ws[$i]['modality'] ?? null)) {
                                $span++;
                                $schedRevenue += $ws[$j]['monthly_revenue'];
                                $schedAmount  += $ws[$j]['amount'];
                            } else {
                                break;
                            }
                        }
                        $ws[$i]['schedule_rowspan']    = $span;
                        $ws[$i]['schedule_modalities'] = !empty($ws[$i]['modality']) ? [$ws[$i]['modality']] : [];
                        $ws[$i]['schedule_revenue']    = $schedRevenue;
                        $ws[$i]['schedule_amount']     = $schedAmount;
                    } else {
                        $ws[$i]['schedule_rowspan']    = 0;
                        $ws[$i]['schedule_modalities'] = [];
                        $ws[$i]['schedule_revenue']    = 0;
                        $ws[$i]['schedule_amount']     = 0;
                    }
                }
                unset($ws);
            }
            unset($instructorData);
            usort($grouped[$type], fn($a, $b) => strcmp($a['instructor_name'], $b['instructor_name']));
        }

        $grouped['volunteer'] = array_values($grouped['volunteer']);
        $grouped['hourly']    = array_values($grouped['hourly']);

        $this->allInstructorPayments = $grouped;

        $volunteerTotal = collect($grouped['volunteer'])->sum('subtotal');
        $hourlyTotal    = collect($grouped['hourly'])->sum('subtotal');

        $this->totalAmount = [
            'volunteer'   => $volunteerTotal,
            'hourly'      => $hourlyTotal,
            'grand_total' => $volunteerTotal + $hourlyTotal,
        ];
    }

    private function getPaymentStatusText($status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            default => ucfirst($status ?? 'Pendiente'),
        };
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn() => !empty($this->allInstructorPayments))
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
        if (empty($this->allInstructorPayments)) {
            Notification::make()
                ->title('Error')
                ->body('No hay registros para generar el reporte')
                ->danger()
                ->send();

            return;
        }

        try {
            $period = MonthlyPeriod::find($this->selectedMonthlyPeriodId);
            $monthNames = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $periodName = $period ? (($monthNames[$period->month] ?? 'Mes ' . $period->month) . ' ' . $period->year) : 'N/A';

            $html = View::make('reports.all-instructors-payment', [
                'grouped_payments' => $this->allInstructorPayments,
                'monthly_period'   => $periodName,
                'total_amount'     => $this->totalAmount,
                'generated_at'     => now()->format('d/m/Y H:i'),
            ])->render();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape'); // Horizontal para mejor visualización

            $dompdf->render();

            $fileName = 'pago-profesores-general-' .
                str_replace([' ', '/'], '-', strtolower($periodName)) . '.pdf';

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

    public function exportExcelAction(): Action
    {
        return Action::make('exportExcel')
            ->label('Exportar Excel')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => !empty($this->allInstructorPayments))
            ->action(function () {
                try {
                    $period = MonthlyPeriod::find($this->selectedMonthlyPeriodId);
                    $monthNames = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ];
                    $periodName = $period ? (($monthNames[$period->month] ?? 'Mes '.$period->month).' '.$period->year) : 'reporte';
                    $fileName = 'pago-profesores-general-'.str_replace([' ', '/'], '-', strtolower($periodName)).'.xlsx';

                    return (new AllInstructorsPaymentExport(
                        $this->allInstructorPayments,
                        $periodName,
                    ))->download($fileName);
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al exportar')
                        ->body('Ocurrió un error: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getActions(): array
    {
        return [
            $this->generatePDFAction(),
            $this->exportExcelAction(),
        ];
    }
}
