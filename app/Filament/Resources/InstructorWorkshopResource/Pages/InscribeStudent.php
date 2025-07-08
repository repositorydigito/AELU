<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker; 
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use App\Models\InstructorWorkshop;
use App\Models\Student;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
use App\Models\WorkshopClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InscribeStudent extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.inscribe-student';
    protected static ?string $title = 'Inscribir Alumno';
    public InstructorWorkshop $record; 
    public ?array $data = [];

    public function mount(): void
    {
        // Obtener el periodo mensual actual y el próximo
        $currentMonthPeriod = MonthlyPeriod::where('month', Carbon::now()->month)
                                           ->where('year', Carbon::now()->year)
                                           ->first();

        // Si no existe el periodo actual, intenta con el próximo o el primero disponible
        $defaultMonthlyPeriodId = null;
        if ($currentMonthPeriod) {
            $defaultMonthlyPeriodId = $currentMonthPeriod->id;
        } else {
            $nextMonthPeriod = MonthlyPeriod::where('month', Carbon::now()->addMonth()->month)
                                            ->where('year', Carbon::now()->addMonth()->year)
                                            ->first();
            if ($nextMonthPeriod) {
                $defaultMonthlyPeriodId = $nextMonthPeriod->id;
            } else {
                // Fallback: si no hay ni mes actual ni próximo, toma el primer periodo disponible
                $firstPeriod = MonthlyPeriod::orderBy('year')->orderBy('month')->first();
                $defaultMonthlyPeriodId = $firstPeriod ? $firstPeriod->id : null;
            }
        }

        // Obtener todas las clases para el periodo inicial si existe
        $initialSelectedClasses = [];
        if ($defaultMonthlyPeriodId) {
            $initialSelectedClasses = WorkshopClass::where('instructor_workshop_id', $this->record->id)
                ->where('monthly_period_id', $defaultMonthlyPeriodId)
                ->pluck('id')
                ->toArray();
        }

        $this->form->fill([
            'enrollment_date' => now()->toDateString(),
            'enrollment_type' => 'full_month', // Por defecto 'full_month'
            'payment_status' => 'pending',
            'number_of_classes' => $this->record->class_count ?? 4,
            'price_per_quantity' => $this->record->class_rate ?? 0,
            'total_amount' => ($this->record->class_rate ?? 0) * ($this->record->class_count ?? 4),
            'monthly_period_id' => $defaultMonthlyPeriodId, // Setear el periodo por defecto
            'selected_classes' => $initialSelectedClasses, // Setear todas las clases marcadas por defecto
        ]);
    }

    /* public function mount(): void
    {
        $this->form->fill([
            'enrollment_date' => now()->toDateString(),
            'enrollment_type' => 'full_month',
            'payment_status' => 'pending',
            'number_of_classes' => $this->record->class_count ?? 4,
            'price_per_quantity' => $this->record->class_rate ?? 0,
            'total_amount' => ($this->record->class_rate ?? 0) * ($this->record->class_count ?? 4),
        ]);
    } */

    public function form(Form $form): Form
    {
        // Obtener el mes y año actual
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Obtener el mes y año próximo
        $nextMonth = Carbon::now()->addMonth()->month;
        $nextYear = Carbon::now()->addMonth()->year;

        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Alumno')
                    ->options(Student::all()->mapWithKeys(fn ($student) => [
                        $student->id => "{$student->full_name} - {$student->student_code}",
                    ]))
                    ->searchable()
                    ->required(),
                
                Select::make('monthly_period_id')
                    ->label('Periodo mensual')
                    ->options(function () use ($currentMonth, $currentYear, $nextMonth, $nextYear) {
                        return MonthlyPeriod::where(function ($query) use ($currentMonth, $currentYear) {
                                // Condición para el mes actual
                                $query->where('month', $currentMonth)
                                      ->where('year', $currentYear);
                            })
                            ->orWhere(function ($query) use ($nextMonth, $nextYear) {
                                // Condición para el mes próximo
                                $query->where('month', $nextMonth)
                                      ->where('year', $nextYear);
                            })
                            ->orderBy('year')
                            ->orderBy('month')
                            ->get()
                            ->mapWithKeys(fn ($period) => [
                                $period->id => "{$period->year} - " . Carbon::create()->month($period->month)->monthName,
                            ])->toArray();
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        if ($get('enrollment_type') === 'full_month') {
                            $monthlyPeriodId = $get('monthly_period_id');
                            $workshopId = $this->record->id;

                            if ($monthlyPeriodId) {
                                $allClassIds = WorkshopClass::where('instructor_workshop_id', $workshopId)
                                    ->where('monthly_period_id', $monthlyPeriodId)
                                    ->pluck('id')
                                    ->toArray();
                                $set('selected_classes', $allClassIds);
                            } else {
                                $set('selected_classes', []);
                            }
                        }
                    })
                    ->required(),                

                Select::make('enrollment_type')
                    ->label('Tipo de inscripción')
                    ->options([
                        'full_month' => 'Mes completo',
                        'specific_classes' => 'Clases específicas',
                    ])
                    ->default('full_month')
                    ->reactive()
                    ->required(),

                // Campo oculto para number_of_classes, calculado dinámicamente
                TextInput::make('number_of_classes')
                    ->label('Cantidad de clases')
                    ->numeric()
                    ->default(4)
                    ->hidden()
                    ->dehydrated(),

                CheckboxList::make('selected_classes')
                    ->label('Clases a inscribir')
                    ->options(function (callable $get) {
                        $periodId = $get('monthly_period_id');
                        $workshopId = $this->record->id;
                        if (!$periodId) return [];

                        $clases = WorkshopClass::where('instructor_workshop_id', $workshopId)
                            ->where('monthly_period_id', $periodId)
                            ->orderBy('class_date')
                            ->get();

                        return $clases->mapWithKeys(fn($clase) => [
                            $clase->id => $clase->class_date->format('d/m/Y') . ' ' . $clase->start_time->format('H:i') . ' - ' . $clase->end_time->format('H:i') . ' (' . ($clase->max_capacity - $clase->enrollmentClasses()->count()) . ')',
                        ])->toArray();
                    })
                    ->visible(fn (callable $get) => $get('monthly_period_id'))
                    ->required(fn (callable $get) => $get('enrollment_type') === 'specific_classes')
                    ->disabled(fn (callable $get) => $get('enrollment_type') !== 'specific_classes')
                    ->columns(2)
                    ->reactive()
                    ->helperText('Selecciona las clases a las que deseas inscribirte. Si es mes completo, se inscribirá a todas las clases automáticamente.'),
                
                // Por default es pending
                /* Select::make('payment_status')
                    ->label('Estado de pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'completed' => 'Pagado',
                    ])
                    ->default('pending')
                    ->required(), */
                Select::make('payment_method')
                    ->label('Método de pago')
                    ->options([
                        'Link' => 'Link',
                        'Efectivo' => 'Efectivo',
                    ])
                    ->required(),

                DatePicker::make('enrollment_date')
                    ->label('Fecha de inscripción')
                    ->default(now())
                    ->required(),
                
            ])
            ->statePath('data');
    }

    /* public function inscribe(): void
    {
        try {
            $data = $this->form->getState();
            $data['instructor_workshop_id'] = $this->record->id;

            // Si no viene number_of_classes, lo calculamos según la selección
            if (!isset($data['number_of_classes']) || $data['number_of_classes'] === null) {
                if (($data['enrollment_type'] ?? null) === 'specific_classes') {
                    $data['number_of_classes'] = isset($data['selected_classes']) ? count($data['selected_classes']) : 0;
                } elseif (($data['enrollment_type'] ?? null) === 'full_month') {
                    $data['number_of_classes'] = \App\Models\WorkshopClass::where('instructor_workshop_id', $this->record->id)
                        ->where('monthly_period_id', $data['monthly_period_id'])
                        ->count();
                }
            }

            $pricing = \App\Models\WorkshopPricing::where('workshop_id', $this->record->workshop_id)
                ->where('number_of_classes', $data['number_of_classes'])
                ->where(function($q) {
                    $q->where('for_volunteer_workshop', $this->record->is_volunteer)
                    ->orWhereNull('for_volunteer_workshop');
                })
                ->first();

            if ($pricing) {
                $data['price_per_quantity'] = $pricing->price;
                $data['total_amount'] = $pricing->price;
            } else {
                Notification::make()
                    ->title('No existe tarifa configurada para esa cantidad de clases.')
                    ->danger()
                    ->send();
                return;
            }

            if ($data['enrollment_type'] === 'full_month') {
                $clases = WorkshopClass::where('instructor_workshop_id', $this->record->id)
                    ->where('monthly_period_id', $data['monthly_period_id'])
                    ->orderBy('class_date')
                    ->pluck('id')
                    ->toArray();
                $data['selected_classes'] = $clases;
            }

            $enrollment = StudentEnrollment::create($data);

            foreach ($data['selected_classes'] as $classId) {
                $enrollment->enrollmentClasses()->create([
                    'workshop_class_id' => $classId,
                    'class_fee' => $data['price_per_quantity'] ?? 0,
                    'attendance_status' => 'enrolled',
                ]);
            }

            $ticketUrl = route('enrollment.ticket', ['enrollmentId' => $enrollment->id]);

            Notification::make()
                ->title('¡Inscripción exitosa!')
                ->success()                
                ->send();
            
            //$this->dispatchBrowserEvent('open-pdf-ticket', ['url' => $ticketUrl]);
            $this->js('window.open("' . $ticketUrl . '", "_blank")');
            $this->redirect(InstructorWorkshopResource::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al inscribir al alumno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    } */

    public function inscribe(): void
    {
        // Usar una transacción de base de datos para asegurar la consistencia de los datos
        // Si algo falla, se revierte todo
        DB::beginTransaction();

        try {
            $data = $this->form->getState();
            $data['instructor_workshop_id'] = $this->record->id;

            // Calcular number_of_classes según el tipo de inscripción
            if (!isset($data['number_of_classes']) || $data['number_of_classes'] === null) {
                if (($data['enrollment_type'] ?? null) === 'specific_classes') {
                    $data['number_of_classes'] = isset($data['selected_classes']) ? count($data['selected_classes']) : 0;
                } elseif (($data['enrollment_type'] ?? null) === 'full_month') { 
                    $data['number_of_classes'] = \App\Models\WorkshopClass::where('instructor_workshop_id', $this->record->id)
                        ->where('monthly_period_id', $data['monthly_period_id'])
                        ->count();
                }
            }

            // Si es 'full_month', se seleccionan automáticamente todas las clases del período
            if ($data['enrollment_type'] === 'full_month') {
                $data['selected_classes'] = WorkshopClass::where('instructor_workshop_id', $this->record->id) 
                    ->where('monthly_period_id', $data['monthly_period_id'])
                    ->orderBy('class_date')
                    ->pluck('id')
                    ->toArray();
            }

            // --- Validación de Cupos ANTES de crear la inscripción ---
            // Solo necesitamos validar si hay clases seleccionadas (que siempre debería ser el caso aquí)
            if (empty($data['selected_classes'])) {
                Notification::make()
                    ->title('Error de inscripción')
                    ->body('No se seleccionaron clases para inscribir.')
                    ->danger()
                    ->send();
                DB::rollBack(); // Revertir la transacción
                return;
            }

            $selectedWorkshopClasses = WorkshopClass::whereIn('id', $data['selected_classes'])->get();

            foreach ($selectedWorkshopClasses as $class) {
                // Bloquear el registro de la clase para evitar condiciones de carrera durante la verificación
                $class->lockForUpdate(); // Bloqueo pesimista

                // Calcular el número de inscritos actual
                $currentEnrolledCount = $class->enrollmentClasses()->count();

                if ($currentEnrolledCount >= $class->max_capacity) {
                    Notification::make()
                        ->title('Cupos agotados')
                        ->body('La clase del ' . $class->class_date->format('d/m/Y') . ' a las ' . $class->start_time->format('H:i') . ' ya no tiene cupos disponibles. (Capacidad: ' . $class->max_capacity . ', Inscritos: ' . $currentEnrolledCount . ')')
                        ->danger()
                        ->send();
                    DB::rollBack(); // Revertir la transacción si no hay cupo
                    return; // Detener el proceso
                }
            }
            // Obtener tarifa
            $pricing = \App\Models\WorkshopPricing::where('workshop_id', $this->record->workshop_id)
                ->where('number_of_classes', $data['number_of_classes']) 
                ->where(function($q) {
                    $q->where('for_volunteer_workshop', $this->record->is_volunteer)
                        ->orWhereNull('for_volunteer_workshop'); 
                })
                ->first();

            if ($pricing) {
                $data['price_per_quantity'] = $pricing->price;
                $data['total_amount'] = $pricing->price;
            } else {
                Notification::make() 
                    ->title('No existe tarifa configurada para esa cantidad de clases.')
                    ->danger()
                    ->send();
                DB::rollBack(); // Revertir la transacción
                return; 
            }

            // Crear la inscripción del estudiante
            $enrollment = StudentEnrollment::create($data); 

            // Crear las enrollment_classes
            foreach ($selectedWorkshopClasses as $class) { // Usamos las clases ya cargadas
                $enrollment->enrollmentClasses()->create([
                    'workshop_class_id' => $class->id, 
                    'class_fee' => $data['price_per_quantity'] / $data['number_of_classes'], // Precio por clase individual
                    'attendance_status' => 'enrolled', 
                ]);
                // Ya no necesitamos incrementar un contador aquí, el count() lo hará dinámicamente
            }

            // Confirmar la transacción
            DB::commit();

            $ticketUrl = route('enrollment.ticket', ['enrollmentId' => $enrollment->id]); 
            Notification::make() 
                ->title('¡Inscripción exitosa!') 
                ->success() 
                ->send(); 
            $this->js('window.open("' . $ticketUrl . '", "_blank")'); 
            $this->redirect(InstructorWorkshopResource::getUrl('index'));

        } catch (\Exception $e) { 
            DB::rollBack(); // En caso de cualquier error, revertir la transacción
            Notification::make() 
                ->title('Error al inscribir al alumno') 
                ->body($e->getMessage())
                ->danger() 
                ->send(); 
        }
    }
}