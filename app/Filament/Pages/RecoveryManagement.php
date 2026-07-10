<?php

namespace App\Filament\Pages;

use App\Models\MonthlyPeriod;
use App\Models\Student;
use App\Models\StudentCredit;
use App\Models\StudentEnrollment;
use App\Services\RecoveryCreditService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RecoveryManagement extends Page implements HasActions, HasForms
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static string $view = 'filament.pages.recovery-management';

    protected static ?string $navigationLabel = 'Recuperaciones';

    protected static ?string $title = 'Recuperaciones';

    protected static ?string $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    public string $searchName = '';

    public array $students = [];

    public ?int $selectedStudentId = null;

    public ?array $selectedStudentData = null;

    public array $periods = [];

    public ?int $selectedPeriodId = null;

    public array $candidates = [];

    public array $selectedClasses = [];

    public array $credits = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([])->statePath('data');
    }

    public function updatedSearchName(): void
    {
        if (mb_strlen($this->searchName) < 2) {
            $this->students = [];

            return;
        }

        $this->students = Student::query()
            ->where(function ($query) {
                $query->where('first_names', 'like', "%{$this->searchName}%")
                    ->orWhere('last_names', 'like', "%{$this->searchName}%")
                    ->orWhere('document_number', 'like', "%{$this->searchName}%");
            })
            ->limit(15)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'full_name' => trim($student->first_names.' '.$student->last_names),
                'document_number' => $student->document_number,
            ])
            ->toArray();
    }

    public function selectStudent(int $studentId): void
    {
        $student = Student::find($studentId);

        if (! $student) {
            return;
        }

        $this->selectedStudentId = $studentId;
        $this->selectedStudentData = [
            'id' => $student->id,
            'full_name' => trim($student->first_names.' '.$student->last_names),
        ];
        $this->students = [];
        $this->searchName = '';
        $this->selectedPeriodId = null;
        $this->candidates = [];
        $this->selectedClasses = [];

        $this->periods = MonthlyPeriod::whereHas('enrollments', function ($query) use ($studentId) {
            $query->where('student_id', $studentId)->where('payment_status', 'completed');
        })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->mapWithKeys(fn (MonthlyPeriod $period) => [$period->id => $this->periodLabel($period)])
            ->toArray();

        $this->loadCredits();
    }

    public function backToSearch(): void
    {
        $this->selectedStudentId = null;
        $this->selectedStudentData = null;
        $this->periods = [];
        $this->selectedPeriodId = null;
        $this->candidates = [];
        $this->selectedClasses = [];
        $this->credits = [];
    }

    public function selectPeriod($periodId): void
    {
        $this->selectedPeriodId = $periodId !== '' ? (int) $periodId : null;
        $this->loadCandidates();
    }

    protected function loadCandidates(): void
    {
        if (! $this->selectedStudentId || ! $this->selectedPeriodId) {
            $this->candidates = [];

            return;
        }

        $student = Student::find($this->selectedStudentId);
        $period = MonthlyPeriod::find($this->selectedPeriodId);

        $candidates = app(RecoveryCreditService::class)->getCandidates($student, $period);

        $this->candidates = $candidates->map(function (array $row) {
            $enrollment = $row['enrollment'];

            return [
                'enrollment_id' => $enrollment->id,
                'workshop_name' => $enrollment->instructorWorkshop->workshop->name ?? 'N/A',
                'instructor_name' => $enrollment->instructorWorkshop->instructor->full_name ?? 'N/A',
                'total_amount' => (float) $enrollment->total_amount,
                'missed_classes' => $row['missed_classes']->map(fn ($class) => [
                    'id' => $class->id,
                    'class_date' => optional($class->workshopClass)->class_date?->format('d/m/Y'),
                    'class_fee' => (float) $class->class_fee,
                    'origin' => optional($class->workshopClass)->status === 'cancelled' ? 'feriado' : 'inasistencia',
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $this->selectedClasses = [];
    }

    protected function loadCredits(): void
    {
        $this->credits = StudentCredit::where('student_id', $this->selectedStudentId)
            ->with(['originEnrollment.instructorWorkshop.workshop', 'originPeriod', 'validThroughPeriod'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (StudentCredit $credit) => [
                'id' => $credit->id,
                'workshop_name' => $credit->originEnrollment?->instructorWorkshop?->workshop?->name ?? 'N/A',
                'amount' => (float) $credit->amount,
                'classes_count' => $credit->classes_count,
                'status' => $credit->status,
                'origin' => $credit->origin,
                'valid_through' => $credit->validThroughPeriod ? $this->periodLabel($credit->validThroughPeriod) : 'N/A',
            ])
            ->toArray();
    }

    public function toggleClass(int $enrollmentId, int $classId): void
    {
        $this->selectedClasses[$enrollmentId][$classId] = ! ($this->selectedClasses[$enrollmentId][$classId] ?? false);
    }

    public function confirmRecoveryAction(): Action
    {
        return Action::make('confirmRecovery')
            ->label('Confirmar recuperación')
            ->color('success')
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->modalDescription('Se generará un crédito por el monto de las clases seleccionadas. El crédito solo será válido en el período siguiente.')
            ->action(fn () => $this->confirmRecovery());
    }

    public function confirmRecovery(): void
    {
        $service = app(RecoveryCreditService::class);
        $createdCount = 0;
        $errors = [];

        foreach ($this->candidates as $candidate) {
            $enrollmentId = $candidate['enrollment_id'];
            $checked = array_keys(array_filter($this->selectedClasses[$enrollmentId] ?? []));

            if (empty($checked)) {
                continue;
            }

            $origins = collect($candidate['missed_classes'])
                ->whereIn('id', $checked)
                ->pluck('origin')
                ->unique();

            $origin = $origins->count() > 1 ? 'mixto' : $origins->first();

            try {
                $enrollment = StudentEnrollment::findOrFail($enrollmentId);
                $service->createCredit($enrollment, $checked, $origin, Auth::user());
                $createdCount++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($createdCount > 0) {
            Notification::make()
                ->title('Recuperación registrada')
                ->body("Se generaron {$createdCount} crédito(s) de recuperación.")
                ->success()
                ->send();
        }

        foreach ($errors as $error) {
            Notification::make()
                ->title('Error al generar crédito')
                ->body($error)
                ->danger()
                ->send();
        }

        if ($createdCount === 0 && empty($errors)) {
            Notification::make()
                ->title('Nada que confirmar')
                ->body('Seleccione al menos una clase no asistida para recuperar.')
                ->warning()
                ->send();

            return;
        }

        $this->loadCandidates();
        $this->loadCredits();
    }

    protected function getActions(): array
    {
        return [
            $this->confirmRecoveryAction(),
        ];
    }

    private function periodLabel(MonthlyPeriod $period): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($monthNames[$period->month] ?? 'Mes '.$period->month).' '.$period->year;
    }
}
