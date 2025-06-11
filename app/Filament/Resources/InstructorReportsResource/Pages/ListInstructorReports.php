<?php

namespace App\Filament\Resources\InstructorReportsResource\Pages;

use App\Filament\Resources\InstructorReportsResource;
use App\Models\Instructor;
use App\Models\Workshop;
use App\Models\InstructorWorkshop;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ListInstructorReports extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InstructorReportsResource::class;
    protected static string $view = 'filament.resources.instructor-reports-resource.pages.list-instructor-reports';
    protected static ?string $title = 'Reportes de Profesores';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'workshop_id' => null,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    public function updatedData(): void
    {
        // Actualizar automáticamente cuando cambien los filtros
        $this->dispatch('$refresh');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Reporte de Indicadores Principales')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('workshop_id')
                                    ->label('Taller')
                                    ->placeholder('Seleccionar')
                                    ->options(Workshop::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->nullable(),
                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('start_date')
                                            ->label('Fecha inicial')
                                            ->reactive()
                                            ->required(),
                                        DatePicker::make('end_date')
                                            ->label('Fecha final')
                                            ->reactive()
                                            ->required(),
                                    ])
                                    ->columnSpan(1),
                                Select::make('month')
                                    ->label('Mes')
                                    ->placeholder('Seleccionar')
                                    ->options([
                                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                    ])
                                    ->reactive()
                                    ->nullable(),
                                Select::make('year')
                                    ->label('Año')
                                    ->placeholder('Seleccionar')
                                    ->options(collect(range(2020, now()->year + 2))->mapWithKeys(fn($year) => [$year => $year]))
                                    ->reactive()
                                    ->nullable(),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    public function getActiveInstructorsData(): array
    {
        $query = Instructor::query();
        
        if ($this->data['workshop_id']) {
            $query->whereHas('instructorWorkshops', function (Builder $q) {
                $q->where('workshop_id', $this->data['workshop_id']);
            });
        }

        $totalInstructors = $query->count();
        $volunteerInstructors = $query->where('instructor_type', 'Voluntario')->count();
        $hourlyInstructors = $query->where('instructor_type', 'Por Horas')->count();

        return [
            'total' => $totalInstructors,
            'volunteers' => $volunteerInstructors,
            'hourly' => $hourlyInstructors,
            'volunteer_percentage' => $totalInstructors > 0 ? round(($volunteerInstructors / $totalInstructors) * 100) : 0,
            'hourly_percentage' => $totalInstructors > 0 ? round(($hourlyInstructors / $totalInstructors) * 100) : 0,
        ];
    }

    public function getHoursData(): array
    {
        $startDate = Carbon::parse($this->data['start_date']);
        $endDate = Carbon::parse($this->data['end_date']);
        
        // Calcular semanas entre las fechas
        $weeks = [];
        $currentDate = $startDate->copy()->startOfWeek();
        $weekNumber = 1;
        
        while ($currentDate->lte($endDate)) {
            $weekEnd = $currentDate->copy()->endOfWeek();
            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }
            
            $weeks["Semana $weekNumber"] = [
                'start' => $currentDate->copy(),
                'end' => $weekEnd->copy(),
                'volunteers' => 0,
                'hourly' => 0,
            ];
            
            $currentDate->addWeek();
            $weekNumber++;
        }

        // Calcular horas por semana
        foreach ($weeks as $weekName => &$week) {
            $instructorWorkshops = InstructorWorkshop::with('instructor')
                ->whereHas('instructor', function (Builder $q) {
                    if ($this->data['workshop_id']) {
                        $q->whereHas('instructorWorkshops', function (Builder $subQ) {
                            $subQ->where('workshop_id', $this->data['workshop_id']);
                        });
                    }
                })
                ->get();

            foreach ($instructorWorkshops as $iw) {
                $startTime = Carbon::parse($iw->start_time);
                $endTime = Carbon::parse($iw->end_time);
                $hoursPerClass = $endTime->diffInHours($startTime);
                $totalHours = $hoursPerClass * ($iw->class_count ?? 1);

                if ($iw->instructor->instructor_type === 'Voluntario') {
                    $week['volunteers'] += $totalHours;
                } else {
                    $week['hourly'] += $totalHours;
                }
            }
        }

        return $weeks;
    }

    public function getInstructorWorkshopsData(): array
    {
        $query = InstructorWorkshop::with(['instructor', 'workshop']);
        
        if ($this->data['workshop_id']) {
            $query->where('workshop_id', $this->data['workshop_id']);
        }

        $instructorWorkshops = $query->get();
        
        $data = [];
        foreach ($instructorWorkshops as $iw) {
            $instructorName = $iw->instructor->full_name;
            $workshopName = $iw->workshop->name;
            
            if (!isset($data[$instructorName])) {
                $data[$instructorName] = [];
            }
            
            if (!isset($data[$instructorName][$workshopName])) {
                $data[$instructorName][$workshopName] = 0;
            }
            
            $data[$instructorName][$workshopName]++;
        }

        // Convertir a formato para el gráfico
        $result = [];
        foreach ($data as $instructor => $workshops) {
            $result[] = [
                'instructor' => $instructor,
                'workshops' => $workshops,
                'total' => array_sum($workshops)
            ];
        }

        // Ordenar por total descendente
        usort($result, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return array_slice($result, 0, 10); // Top 10
    }

    public function getWorkshopsData(): array
    {
        $query = Workshop::withCount('instructorWorkshops');
        
        if ($this->data['workshop_id']) {
            $query->where('id', $this->data['workshop_id']);
        }

        return $query->orderBy('instructor_workshops_count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($workshop) {
                        return [
                            'name' => $workshop->name,
                            'count' => $workshop->instructor_workshops_count
                        ];
                    })
                    ->toArray();
    }

    // Deshabilitar la tabla normal de ListRecords
    protected function getTableQuery(): Builder
    {
        return Instructor::query()->whereRaw('1 = 0'); // Query vacío
    }

    protected function getHeaderActions(): array
    {
        return []; // Sin acciones en el header
    }
}
