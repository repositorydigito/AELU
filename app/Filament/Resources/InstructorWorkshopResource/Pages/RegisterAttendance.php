<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Resources\Pages\Page;
use App\Models\InstructorWorkshop;
use App\Models\Enrollment;
use App\Models\Attendance;
use Illuminate\Support\Carbon; 
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form; 

class RegisterAttendance extends Page implements HasForms
{
    use InteractsWithForms; 

    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.register-attendance';

    public InstructorWorkshop $record; 

    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;
    public array $attendanceData = []; 
    public $enrollments = []; 

    public ?array $dateFilterData = [];

    public function mount(InstructorWorkshop $record): void
    {
        $this->record = $record;
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;

        $this->dateFilterData = [
            'month' => $this->selectedMonth,
            'year' => $this->selectedYear,
        ];

        $this->form->fill($this->dateFilterData);       

        $this->loadEnrollmentsAndAttendance();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('month')
                    ->label('Mes')
                    ->options([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ])
                    ->default($this->selectedMonth)
                    ->live() 
                    ->afterStateUpdated(function ($state) {
                        $this->selectedMonth = $state;
                        $this->dateFilterData['month'] = $state;
                        $this->form->fill($this->dateFilterData);
                        $this->loadEnrollmentsAndAttendance();
                    }),
                Select::make('year')
                    ->label('Año')
                    ->options(collect(range(now()->year - 2, now()->year + 2))
                        ->mapWithKeys(fn($y) => [$y => $y])
                        ->toArray()
                    )
                    ->live()
                    ->default($this->selectedYear)
                    ->afterStateUpdated(function ($state) {
                        $this->selectedYear = $state;
                        $this->loadEnrollmentsAndAttendance();
                    }),
            ])
            ->statePath('dateFilterData');
    }

    public function getDaysInMonth(): array
    {
        if (is_null($this->selectedYear) || is_null($this->selectedMonth)) {
            return [];
        }
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $days = [];
        for ($i = 1; $i <= $date->daysInMonth; $i++) {
            $days[] = $i;
        }
        return $days;
    }

    public function loadEnrollmentsAndAttendance(): void
    {
        $this->enrollments = $this->record->enrollments()->with('student', 'attendances')->get();
        $this->attendanceData = [];

        foreach ($this->enrollments as $enrollment) {
            foreach ($this->getDaysInMonth() as $day) {
                $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day)->toDateString();
                //$attendance = $enrollment->attendances->where('attendance_date', $date)->first();
                $attendance = Attendance::where('enrollment_id', $enrollment->id)
                    ->where('attendance_date', $date)
                    ->first();
                $this->attendanceData[$enrollment->id][$day] = $attendance ? (bool)$attendance->is_present : false;
            }
        }
    }

    public function saveAttendance(): void
    {
        if (is_null($this->selectedMonth) || is_null($this->selectedYear)) {
            Notification::make()
                ->title('Error: Seleccione un mes y año.')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->attendanceData as $enrollmentId => $daysAttendance) {
            foreach ($daysAttendance as $day => $isPresent) {
                $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day)->toDateString();

                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $enrollmentId,
                        'attendance_date' => $date,
                    ],
                    [
                        'is_present' => $isPresent,
                    ]
                );
            }
        }

        $this->loadEnrollmentsAndAttendance();

        Notification::make()
            ->title('Asistencia guardada exitosamente.')
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return "Asistencia para: {$this->record->workshop->name} ({$this->record->day_of_week} {$this->record->time_range})";
    }
}