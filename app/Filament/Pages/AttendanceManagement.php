<?php

namespace App\Filament\Pages;

use App\Models\ClassAttendance;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
use App\Models\WorkshopClass;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AttendanceManagement extends Page implements HasActions, HasForms
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.pages.attendance-management';

    protected static ?string $navigationLabel = 'Asistencia por Clase';

    protected static ?string $title = 'Asistencia por Clase';

    protected static ?string $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public $selectedWorkshop = null;

    public $workshops = [];

    public $filteredWorkshops = [];

    public $workshopClasses = [];

    public $studentEnrollments = [];

    public $attendanceData = [];

    public $selectedWorkshopData = null;

    // Filtros
    public $searchName = '';

    public $selectedPeriod = null;

    public $monthlyPeriods = [];

    public function mount(): void
    {
        $this->loadMonthlyPeriods();
        $this->loadWorkshops();
        $this->applyFilters();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([])->statePath('data');
    }

    public function loadMonthlyPeriods(): void
    {
        // Solo cargar períodos que están vinculados a talleres
        $this->monthlyPeriods = MonthlyPeriod::whereHas('workshops')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->mapWithKeys(function ($period) {
                $periodName = $this->generatePeriodName($period->month, $period->year);

                return [$period->id => $periodName];
            })
            ->toArray();
    }

    public function applyFilters(): void
    {
        $this->filteredWorkshops = collect($this->workshops)
            ->filter(function ($workshop) {
                // Filtro por nombre
                if (! empty($this->searchName)) {
                    $searchTerm = $this->normalizeString($this->searchName);
                    $workshopName = $this->normalizeString($workshop['name']);

                    if (! str_contains($workshopName, $searchTerm)) {
                        return false;
                    }
                }

                // Filtro por período mensual
                if (! empty($this->selectedPeriod)) {
                    if ($workshop['period_id'] != $this->selectedPeriod) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->toArray();
    }

    private function normalizeString($string): string
    {
        $string = mb_strtolower($string, 'UTF-8');
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n',
        ];
        return strtr($string, $replacements);
    }

    public function updatedSearchName(): void
    {
        $this->applyFilters();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->applyFilters();
    }

    public function clearFilters(): void
    {
        $this->searchName = '';
        $this->selectedPeriod = null;
        $this->applyFilters();
    }

    public function loadWorkshops(): void
    {
        // Verificar si el usuario tiene rol super_admin o Administrador
        $user = Auth::user();
        $isAdmin = $user->hasRole(['super_admin', 'Administrador']);

        // Construir la consulta de talleres
        $workshopQuery = Workshop::query()->with(['monthlyPeriod', 'instructor', 'workshopClasses']);

        // Si no es admin, filtrar solo los talleres donde el usuario es delegado
        if (! $isAdmin) {
            $workshopQuery->where('delegate_user_id', Auth::id());
        }

        // Cargar los talleres según los criterios
        $this->workshops = $workshopQuery
            ->get()
            ->map(function ($workshop) {
                // Calcular estudiantes inscritos PARA EL PERÍODO ESPECÍFICO DEL TALLER
                $enrolledStudents = StudentEnrollment::whereHas('instructorWorkshop', function ($query) use ($workshop) {
                    $query->where('workshop_id', $workshop->id);
                })
                    ->where('monthly_period_id', $workshop->monthly_period_id)
                    ->where('payment_status', 'completed')
                    ->count();

                // Calcular cupos disponibles
                $availableSlots = max(0, ($workshop->capacity ?? 0) - $enrolledStudents);

                // Generar nombre del período
                $periodName = 'Sin período';
                if ($workshop->monthlyPeriod) {
                    $periodName = $this->generatePeriodName($workshop->monthlyPeriod->month, $workshop->monthlyPeriod->year);
                }

                return [
                    'id' => $workshop->id,
                    'name' => $workshop->name,
                    'instructor_name' => $workshop->instructor->full_name ?? 'Sin instructor',
                    'day_of_week' => $workshop->day_of_week,
                    'start_time' => $this->formatTime($workshop->start_time),
                    'end_time' => $this->formatTime($workshop->end_time),
                    'duration' => $workshop->duration,
                    'capacity' => $workshop->capacity ?? 0,
                    'enrolled_students' => $enrolledStudents,
                    'available_slots' => $availableSlots,
                    'number_of_classes' => $workshop->number_of_classes ?? 0,
                    'standard_monthly_fee' => $workshop->standard_monthly_fee ?? 0,
                    'place' => $workshop->place,
                    'period_name' => $periodName,
                    'period_id' => $workshop->monthly_period_id,
                    'modality' => $workshop->modality,
                    'classes_count' => $workshop->workshopClasses->count(),
                ];
            })
            ->toArray();

        // Aplicar filtros después de cargar los talleres
        $this->applyFilters();
    }

    public function selectWorkshop($workshopId): void
    {
        $this->selectedWorkshop = $workshopId;
        $this->selectedWorkshopData = collect($this->workshops)->firstWhere('id', $workshopId);
        $this->loadWorkshopData();
    }

    public function loadWorkshopData(): void
    {
        if (! $this->selectedWorkshop) {
            $this->workshopClasses = [];
            $this->studentEnrollments = [];
            $this->attendanceData = [];

            return;
        }

        // Obtener el taller seleccionado con su período mensual
        $workshop = Workshop::find($this->selectedWorkshop);

        if (!$workshop) {
            $this->workshopClasses = [];
            $this->studentEnrollments = [];
            $this->attendanceData = [];
            return;
        }

        // Cargar las clases del taller
        $this->workshopClasses = WorkshopClass::where('workshop_id', $this->selectedWorkshop)
            ->orderBy('class_date')
            ->get()
            ->toArray();

        // Cargar las matrículas de estudiantes para este taller CON sus clases específicas
        // ORDENADAS ALFABÉTICAMENTE por apellidos y nombres
        // FILTRADO POR EL PERÍODO MENSUAL DEL TALLER
        $enrollments = StudentEnrollment::whereHas('instructorWorkshop', function ($query) {
            $query->where('workshop_id', $this->selectedWorkshop);
        })
            ->where('monthly_period_id', $workshop->monthly_period_id)
            ->where('payment_status', 'completed')
            ->with(['student', 'enrollmentClasses.workshopClass'])
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->orderBy('students.last_names')
            ->orderBy('students.first_names')
            ->select('student_enrollments.*')
            ->get();

        $this->studentEnrollments = [];
        foreach ($enrollments as $enrollment) {
            // Obtener los IDs de las clases específicas a las que el estudiante está inscrito
            $enrolledClassIds = $enrollment->enrollmentClasses->pluck('workshop_class_id')->toArray();

            $enrollmentData = $enrollment->toArray();
            $enrollmentData['enrolled_class_ids'] = $enrolledClassIds;

            $this->studentEnrollments[] = $enrollmentData;
        }

        // Cargar datos de asistencia existentes
        $this->loadAttendanceData();
    }

    public function loadAttendanceData(): void
    {
        if (empty($this->workshopClasses) || empty($this->studentEnrollments)) {
            $this->attendanceData = [];

            return;
        }

        $classIds = collect($this->workshopClasses)->pluck('id');
        $enrollmentIds = collect($this->studentEnrollments)->pluck('id');

        $existingAttendances = ClassAttendance::whereIn('workshop_class_id', $classIds)
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->get()
            ->groupBy(function ($attendance) {
                return $attendance->student_enrollment_id.'_'.$attendance->workshop_class_id;
            });

        $this->attendanceData = [];

        foreach ($this->studentEnrollments as $enrollment) {
            foreach ($this->workshopClasses as $class) {
                $key = $enrollment['id'].'_'.$class['id'];
                $attendance = $existingAttendances->get($key)?->first();

                $this->attendanceData[$key] = [
                    'is_present' => $attendance ? $attendance->is_present : false,
                    'comments' => $attendance ? $attendance->comments : '',
                ];
            }
        }
    }

    public function backToSelection(): void
    {
        $this->selectedWorkshop = null;
        $this->selectedWorkshopData = null;
        $this->workshopClasses = [];
        $this->studentEnrollments = [];
        $this->attendanceData = [];
    }

    public function saveAttendanceAction(): Action
    {
        return Action::make('saveAttendance')
            ->label('Guardar Asistencia')
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function () {
                $this->saveAttendance();
            });
    }

    public function saveAttendance(): void
    {
        if (! $this->selectedWorkshop) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un taller')
                ->danger()
                ->send();

            return;
        }

        $savedCount = 0;

        foreach ($this->studentEnrollments as $enrollment) {
            foreach ($this->workshopClasses as $class) {
                // Solo procesar si el estudiante está inscrito en esta clase específica
                if (! $this->isStudentEnrolledInClass($enrollment, $class['id'])) {
                    continue;
                }

                // Verificar si se puede editar la asistencia para esta fecha
                if (! $this->canEditAttendanceForDate($class['class_date'])) {
                    continue;
                }

                $key = $enrollment['id'].'_'.$class['id'];
                $attendanceInfo = $this->attendanceData[$key] ?? [];

                ClassAttendance::updateOrCreate(
                    [
                        'workshop_class_id' => $class['id'],
                        'student_enrollment_id' => $enrollment['id'],
                    ],
                    [
                        'is_present' => $attendanceInfo['is_present'] ?? false,
                        'comments' => $attendanceInfo['comments'] ?? '',
                        'recorded_by' => Auth::id(),
                    ]
                );

                $savedCount++;
            }
        }

        Notification::make()
            ->title('Éxito')
            ->body("Asistencia guardada correctamente. Se procesaron {$savedCount} registros.")
            ->success()
            ->send();
    }

    public function toggleAttendance($enrollmentId, $classId): void
    {
        // Buscar el enrollment para verificar si está inscrito en esta clase
        $enrollment = collect($this->studentEnrollments)->firstWhere('id', $enrollmentId);

        if (! $enrollment || ! $this->isStudentEnrolledInClass($enrollment, $classId)) {
            Notification::make()
                ->title('Error')
                ->body('El estudiante no está inscrito en esta clase específica.')
                ->warning()
                ->send();

            return;
        }

        // Verificar si se puede editar la asistencia para esta fecha
        $class = collect($this->workshopClasses)->firstWhere('id', $classId);
        if ($class && ! $this->canEditAttendanceForDate($class['class_date'])) {
            Notification::make()
                ->title('Restricción de fecha')
                ->body($this->getRestrictionMessageForDate($class['class_date']))
                ->warning()
                ->send();

            return;
        }

        $key = $enrollmentId.'_'.$classId;
        $this->attendanceData[$key]['is_present'] = ! ($this->attendanceData[$key]['is_present'] ?? false);
    }

    public function updateComments($enrollmentId, $classId, $comments): void
    {
        $key = $enrollmentId.'_'.$classId;
        $this->attendanceData[$key]['comments'] = $comments;
    }

    protected function getActions(): array
    {
        return [
            $this->saveAttendanceAction(),
        ];
    }

    /**
     * Verificar si un estudiante está inscrito en una clase específica
     */
    public function isStudentEnrolledInClass($enrollmentData, $classId): bool
    {
        return in_array($classId, $enrollmentData['enrolled_class_ids'] ?? []);
    }

    /**
     * Verificar si se puede editar la asistencia para una fecha específica de clase
     */
    public function canEditAttendanceForDate($classDate): bool
    {
        $classDate = \Carbon\Carbon::parse($classDate);
        $today = \Carbon\Carbon::today();
        $oneDayAfterClass = $classDate->copy()->addDay();

        // Permitir editar hasta 1 día después de la fecha de clase
        return $today->gte($classDate) && $today->lte($oneDayAfterClass);
    }

    /**
     * Obtener el mensaje de restricción para una fecha específica
     */
    public function getRestrictionMessageForDate($classDate): string
    {
        $classDate = \Carbon\Carbon::parse($classDate);
        $oneDayAfterClass = $classDate->copy()->addDay();
        if (\Carbon\Carbon::today()->gt($oneDayAfterClass)) {
            return 'La asistencia para esta clase expiró el '.$oneDayAfterClass->format('d/m/Y');
        }

        return '';
    }

    /**
     * Helper method to format time values safely
     */
    private function formatTime($time): string
    {
        if (! $time) {
            return '';
        }

        // Si ya es un string, devolverlo tal como está
        if (is_string($time)) {
            return $time;
        }

        // Si es un objeto Carbon/DateTime, formatearlo
        if (method_exists($time, 'format')) {
            return $time->format('H:i');
        }

        // Como fallback, convertir a string
        return (string) $time;
    }

    /**
     * Helper method to generate period name from month and year
     */
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
}
