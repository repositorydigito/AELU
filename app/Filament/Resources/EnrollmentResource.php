<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\StudentEnrollment;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static ?string $navigationLabel = 'Inscripciones';

    protected static ?string $pluralModelLabel = 'Inscripciones';

    protected static ?string $modelLabel = 'Inscripción';

    protected static bool $shouldRegisterNavigation = false; // Ocultar de la navegación

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // Paso 1: Selección de Estudiante y Talleres
                    Forms\Components\Wizard\Step::make('Seleccionar Estudiante y Talleres')
                        ->icon('heroicon-o-user-plus')
                        ->schema([
                            Forms\Components\Hidden::make('editing_batch_id')
                                ->default(null),
                            Forms\Components\Select::make('selected_monthly_period_id')
                                ->label('Período Mensual')
                                ->options(function () {
                                    $currentDate = now();
                                    $nextDate = now()->addMonth();
                                    $previousDate = now()->subMonth();

                                    $options = [];

                                    // Buscar período del mes anterior
                                    $previousPeriod = \App\Models\MonthlyPeriod::where('year', $previousDate->year)
                                        ->where('month', $previousDate->month)
                                        ->first();

                                    if ($previousPeriod) {
                                        $options[$previousPeriod->id] = $previousDate->translatedFormat('F Y');
                                    }

                                    // Buscar período del mes actual
                                    $currentPeriod = \App\Models\MonthlyPeriod::where('year', $currentDate->year)
                                        ->where('month', $currentDate->month)
                                        ->first();

                                    if ($currentPeriod) {
                                        $options[$currentPeriod->id] = $currentDate->translatedFormat('F Y');
                                    }

                                    // Buscar período del próximo mes
                                    $nextPeriod = \App\Models\MonthlyPeriod::where('year', $nextDate->year)
                                        ->where('month', $nextDate->month)
                                        ->first();

                                    if ($nextPeriod) {
                                        $options[$nextPeriod->id] = $nextDate->translatedFormat('F Y');
                                    }

                                    return $options;
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    // Limpiar cuando cambie el período
                                    $set('student_id', null);
                                    $set('selected_workshops', '[]');
                                    $set('workshop_details', []);
                                    $set('previous_workshops', '[]');

                                    // Buscar talleres previos si ya hay estudiante seleccionado
                                    $studentId = $get('student_id');
                                    if (! $studentId || ! $state) {
                                        return;
                                    }

                                    $previousWorkshops = static::findPreviousWorkshops($studentId, $state);
                                    $set('previous_workshops', json_encode($previousWorkshops));
                                })
                                ->validationMessages(['required' => 'El período mensual es obligatorio.'])
                                ->columnSpanFull(),

                            Forms\Components\Select::make('student_id')
                                ->label('Estudiante')
                                ->options(function () {
                                    return \App\Models\Student::all()
                                        ->mapWithKeys(function ($student) {
                                            return [$student->id => "{$student->last_names} {$student->first_names} - {$student->student_code}"];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Buscar estudiante...')
                                ->helperText(function (Forms\Get $get) {
                                    $studentId = $get('student_id');
                                    if (! $studentId) {
                                        return 'Selecciona el estudiante que se inscribirá';
                                    }

                                    $student = \App\Models\Student::find($studentId);
                                    if (! $student) {
                                        return 'Estudiante no encontrado';
                                    }

                                    if ($student->is_maintenance_current === false) {
                                        return '⚠️ Este estudiante NO está al día con el mantenimiento mensual';
                                    }

                                    return '✅ Estudiante al día con el mantenimiento mensual';
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (! $state) {
                                        $set('selected_workshops', '[]');
                                        $set('previous_workshops', '[]');
                                        $set('workshop_details', []);

                                        return;
                                    }

                                    $student = \App\Models\Student::find($state);
                                    if (! $student) {
                                        $set('selected_workshops', '[]');
                                        $set('previous_workshops', '[]');
                                        $set('workshop_details', []);

                                        return;
                                    }

                                    // Verificar mantenimiento mensual
                                    if ($student->is_maintenance_current === false) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Estudiante no al día')
                                            ->body("El estudiante {$student->first_names} {$student->last_names} no está al día con el pago del mantenimiento mensual.")
                                            ->danger()
                                            ->persistent()
                                            ->send();

                                        $set('selected_workshops', '[]');
                                        $set('previous_workshops', '[]');
                                        $set('workshop_details', []);

                                        return;
                                    }

                                    // Notificación de éxito
                                    \Filament\Notifications\Notification::make()
                                        ->title('Estudiante verificado')
                                        ->body("El estudiante {$student->first_names} {$student->last_names} está al día con el mantenimiento mensual.")
                                        ->success()
                                        ->send();

                                    // Buscar talleres previos
                                    $selectedMonthlyPeriodId = $get('selected_monthly_period_id');
                                    if ($selectedMonthlyPeriodId) {
                                        $previousWorkshops = static::findPreviousWorkshops($state, $selectedMonthlyPeriodId);
                                        $set('previous_workshops', json_encode($previousWorkshops));
                                    }

                                    // Limpiar selecciones
                                    $set('selected_workshops', '[]');
                                    $set('workshop_details', []);
                                })
                                ->columnSpanFull(),

                            // Separador visual
                            Forms\Components\Section::make('')
                                ->schema([
                                    // Campo oculto para almacenar los talleres seleccionados
                                    Forms\Components\Hidden::make('selected_workshops')
                                        ->default('[]')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $selectedWorkshops = json_decode($state ?? '[]', true);
                                            if (! is_array($selectedWorkshops)) {
                                                $selectedWorkshops = [];
                                            }

                                            // Obtener los workshop_details existentes
                                            $existingWorkshopDetails = $get('workshop_details') ?? [];

                                            // Crear un mapa de los detalles existentes por instructor_workshop_id
                                            $existingDetailsMap = [];
                                            foreach ($existingWorkshopDetails as $detail) {
                                                if (isset($detail['instructor_workshop_id'])) {
                                                    $existingDetailsMap[$detail['instructor_workshop_id']] = $detail;
                                                }
                                            }

                                            $workshopDetails = [];

                                            foreach ($selectedWorkshops as $workshopId) {
                                                // Si ya existe detail para este taller, preservarlo
                                                if (isset($existingDetailsMap[$workshopId])) {
                                                    $workshopDetails[] = $existingDetailsMap[$workshopId];
                                                    continue;
                                                }

                                                // Si no existe, crear uno nuevo con valores por defecto
                                                $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')
                                                    ->find($workshopId);

                                                $defaultClasses = $instructorWorkshop && $instructorWorkshop->workshop
                                                    ? $instructorWorkshop->workshop->number_of_classes
                                                    : 4;

                                                $workshopDetails[] = [
                                                    'instructor_workshop_id' => $workshopId,
                                                    'enrollment_type' => 'full_month',
                                                    'number_of_classes' => $defaultClasses,
                                                    'enrollment_date' => now()->format('Y-m-d'),
                                                    'selected_classes' => [],
                                                ];
                                            }

                                            $set('workshop_details', $workshopDetails);
                                        }),

                                    Forms\Components\Hidden::make('previous_workshops')
                                        ->default('[]')
                                        ->live(),

                                    // Vista personalizada de talleres en cards
                                    Forms\Components\ViewField::make('workshop_cards')
                                        ->label('')
                                        ->view('filament.forms.components.workshop-cards')
                                        ->viewData(function (Forms\Get $get) {
                                            $studentId = $get('student_id');
                                            $selectedWorkshopsRaw = $get('selected_workshops') ?? '[]';
                                            $selectedWorkshops = json_decode($selectedWorkshopsRaw, true);
                                            $selectedMonthlyPeriodId = $get('selected_monthly_period_id');
                                            $previousWorkshopIds = json_decode($get('previous_workshops') ?? '[]', true);


                                            // IMPORTANTE: Verificar si los talleres ya pertenecen al período actual
                                            // Si ya están en el período correcto, no necesitamos mapearlos
                                            if (!empty($selectedWorkshops) && $selectedMonthlyPeriodId) {
                                                // Verificar cuántos de los talleres seleccionados ya están en el período actual
                                                $workshopsInCurrentPeriod = \App\Models\InstructorWorkshop::whereIn('id', $selectedWorkshops)
                                                    ->whereHas('workshop', function ($query) use ($selectedMonthlyPeriodId) {
                                                        $query->where('monthly_period_id', $selectedMonthlyPeriodId);
                                                    })
                                                    ->pluck('id')
                                                    ->toArray();

                                                // Si todos los talleres ya están en el período actual, no hacer nada
                                                if (count($workshopsInCurrentPeriod) === count($selectedWorkshops)) {
                                                    \Log::info('ViewData - No mapping needed, workshops already in current period:', [
                                                        'workshop_ids' => $selectedWorkshops,
                                                    ]);
                                                } else {
                                                    // Solo mapear los talleres que NO están en el período actual
                                                    $workshopsToMap = array_diff($selectedWorkshops, $workshopsInCurrentPeriod);

                                                    if (!empty($workshopsToMap)) {
                                                        $originalWorkshops = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                            ->whereIn('id', $workshopsToMap)
                                                            ->get();

                                                        $mappedWorkshops = [];
                                                        foreach ($originalWorkshops as $originalWorkshop) {
                                                            $originalStartTime = \Carbon\Carbon::parse($originalWorkshop->start_time)->format('H:i:s');

                                                            $currentWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                                ->whereHas('workshop', function ($query) use ($selectedMonthlyPeriodId, $originalWorkshop) {
                                                                    $query->where('monthly_period_id', $selectedMonthlyPeriodId)
                                                                          ->where('name', $originalWorkshop->workshop->name)
                                                                          ->where('modality', $originalWorkshop->workshop->modality);
                                                                })
                                                                ->whereHas('instructor', function ($query) use ($originalWorkshop) {
                                                                    $query->where('first_names', $originalWorkshop->instructor->first_names)
                                                                          ->where('last_names', $originalWorkshop->instructor->last_names);
                                                                })
                                                                ->where('day_of_week', $originalWorkshop->day_of_week)
                                                                ->whereRaw('TIME(start_time) = ?', [$originalStartTime])
                                                                ->where('is_active', true)
                                                                ->first();

                                                            if ($currentWorkshop) {
                                                                $mappedWorkshops[$originalWorkshop->id] = $currentWorkshop->id;
                                                            }
                                                        }

                                                        // Combinar los talleres que ya estaban en el período con los mapeados
                                                        $allMappedIds = array_merge($workshopsInCurrentPeriod, array_values($mappedWorkshops));
                                                        $selectedWorkshops = $allMappedIds;

                                                        \Log::info('ViewData - Partial Workshop Mapping:', [
                                                            'already_in_period' => $workshopsInCurrentPeriod,
                                                            'newly_mapped' => $mappedWorkshops,
                                                            'final_selected' => $selectedWorkshops,
                                                        ]);
                                                    }
                                                }
                                            }

                                            // Obtener inscripciones activas del estudiante para el período actual
                                            $currentEnrolledWorkshopIds = [];
                                            if ($studentId && $selectedMonthlyPeriodId) {
                                                $query = \App\Models\StudentEnrollment::where('student_id', $studentId)
                                                    ->where('monthly_period_id', $selectedMonthlyPeriodId)
                                                    ->whereNotIn('payment_status', ['refunded']);

                                                // Excluir el batch actual si estamos editando
                                                $editingBatchId = $get('editing_batch_id');
                                                if ($editingBatchId) {
                                                    $query->where('enrollment_batch_id', '!=', $editingBatchId);
                                                }

                                                $currentEnrolledWorkshopIds = $query->pluck('instructor_workshop_id')
                                                    ->toArray();
                                            }

                                            if (! $selectedMonthlyPeriodId) {
                                                return [
                                                    'workshops' => collect(),
                                                    'student_id' => $studentId,
                                                    'previous_workshops' => [],
                                                    'current_enrolled_workshops' => $currentEnrolledWorkshopIds,
                                                    'selected_monthly_period_id' => null,
                                                ];
                                            }

                                            // Función helper para mapear datos de taller
                                            $mapWorkshopData = function ($instructorWorkshop, $selectedWorkshops, $selectedMonthlyPeriodId, $previousWorkshopIds, $currentEnrolledWorkshopIds) {
                                                try {
                                                    // Verificar que todas las relaciones están cargadas
                                                    if (! $instructorWorkshop || ! $instructorWorkshop->workshop || ! $instructorWorkshop->instructor) {
                                                        return null;
                                                    }

                                                    // CALCULAR CUPOS DISPONIBLES
                                                    $capacity = method_exists($instructorWorkshop, 'getEffectiveCapacity')
                                                        ? $instructorWorkshop->getEffectiveCapacity()
                                                        : ($instructorWorkshop->capacity ?? 20);

                                                    $currentEnrollments = 0;
                                                    $availableSpots = $capacity;

                                                    if ($selectedMonthlyPeriodId && method_exists($instructorWorkshop, 'getCurrentEnrollmentsForPeriod')) {
                                                        $currentEnrollments = $instructorWorkshop->getCurrentEnrollmentsForPeriod($selectedMonthlyPeriodId);
                                                        $availableSpots = method_exists($instructorWorkshop, 'getAvailableSpotsForPeriod')
                                                            ? $instructorWorkshop->getAvailableSpotsForPeriod($selectedMonthlyPeriodId)
                                                            : max(0, $capacity - $currentEnrollments);
                                                    }

                                                    $daysOfWeek = $instructorWorkshop->day_of_week;
                                                    if (is_array($daysOfWeek)) {
                                                        $dayInSpanish = implode('/', $daysOfWeek);
                                                    } else {
                                                        $dayInSpanish = $daysOfWeek ?? 'N/A';
                                                    }

                                                    return [
                                                        'id' => $instructorWorkshop->id,
                                                        'name' => $instructorWorkshop->workshop->name,
                                                        'modality' => $instructorWorkshop->workshop->modality ?? 'No especificada',
                                                        'instructor' => $instructorWorkshop->instructor->first_names.' '.$instructorWorkshop->instructor->last_names,
                                                        'day' => $dayInSpanish,
                                                        'start_time' => \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i'),
                                                        'end_time' => $instructorWorkshop->workshop->end_time
                                                            ? \Carbon\Carbon::parse($instructorWorkshop->workshop->end_time)->format('H:i')
                                                            : \Carbon\Carbon::parse($instructorWorkshop->start_time)->addHours(2)->format('H:i'),
                                                        'price' => $instructorWorkshop->workshop->standard_monthly_fee ?? 0,
                                                        'max_classes' => $instructorWorkshop->workshop->number_of_classes ?? 4,
                                                        'selected' => in_array($instructorWorkshop->id, $selectedWorkshops),
                                                        'capacity' => $capacity,
                                                        'current_enrollments' => $currentEnrollments,
                                                        'available_spots' => $availableSpots,
                                                        'is_full' => $availableSpots <= 0,
                                                        'is_previous' => in_array($instructorWorkshop->id, $previousWorkshopIds),
                                                        'is_enrolled' => in_array($instructorWorkshop->id, $currentEnrolledWorkshopIds),
                                                    ];
                                                } catch (\Exception $e) {
                                                    return null;
                                                }
                                            };

                                            try {
                                                // PASO 1: Obtener TODOS los talleres del período actual
                                                $allCurrentWorkshops = collect();
                                                if ($selectedMonthlyPeriodId) {
                                                    $instructorWorkshops = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                        ->whereHas('workshop', function ($query) use ($selectedMonthlyPeriodId) {
                                                            $query->where('monthly_period_id', $selectedMonthlyPeriodId);
                                                        })
                                                        ->where('is_active', true)
                                                        ->join('workshops', 'instructor_workshops.workshop_id', '=', 'workshops.id')
                                                        ->orderBy('workshops.name', 'asc')
                                                        ->select('instructor_workshops.*')
                                                        ->get();

                                                    $allWorkshopIds = $instructorWorkshops->pluck('id')->toArray();


                                                    // PASO 2: Mapear cada taller y marcarlo como previo si corresponde
                                                    foreach ($instructorWorkshops as $instructorWorkshop) {
                                                        $mappedData = $mapWorkshopData($instructorWorkshop, $selectedWorkshops, $selectedMonthlyPeriodId, $previousWorkshopIds, $currentEnrolledWorkshopIds);
                                                        if ($mappedData) {
                                                            // CORREGIDO: Marcar como previo usando el ID específico, no el nombre
                                                            // Esto evita que se marquen TODOS los horarios con el mismo nombre
                                                            if (in_array($instructorWorkshop->id, $previousWorkshopIds)) {
                                                                $mappedData['is_previous'] = true;
                                                            }
                                                            $allCurrentWorkshops->push($mappedData);
                                                        }
                                                    }
                                                }

                                                // PASO 3: Separar talleres previos para el conteo
                                                $previousWorkshopsData = $allCurrentWorkshops->where('is_previous', true);
                                                $currentPeriodPreviousWorkshopIds = $previousWorkshopsData->pluck('id')->unique()->values()->toArray();

                                                return [
                                                    'workshops' => $allCurrentWorkshops->unique('id')->values(),
                                                    'student_id' => $studentId,
                                                    'previous_workshops' => $currentPeriodPreviousWorkshopIds,
                                                    'current_enrolled_workshops' => $currentEnrolledWorkshopIds,
                                                    'selected_monthly_period_id' => $selectedMonthlyPeriodId,
                                                ];
                                            } catch (\Exception $e) {
                                                return [
                                                    'workshops' => collect(),
                                                    'student_id' => $studentId,
                                                    'previous_workshops' => [],
                                                    'current_enrolled_workshops' => [],
                                                    'selected_monthly_period_id' => $selectedMonthlyPeriodId,
                                                ];
                                            }
                                        })
                                        ->live()
                                        ->key(function (Forms\Get $get) {
                                            return 'workshops_'.md5($get('previous_workshops').$get('student_id').$get('selected_monthly_period_id').$get('selected_workshops'));
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // Paso 2: Configuración de Detalles
                    Forms\Components\Wizard\Step::make('Configurar Detalles')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->beforeValidation(function (Forms\Get $get) {
                            $studentId = $get('student_id');
                            if (! $studentId) {
                                throw ValidationException::withMessages(['student_id' => 'Debe seleccionar un estudiante']);
                            }

                            $student = \App\Models\Student::find($studentId);
                            if (! $student || $student->is_maintenance_current === false) {
                                throw ValidationException::withMessages(['student_id' => 'El estudiante seleccionado no esta al dia con el mantenimiento mensual']);
                            }

                            $selectedWorkshops = json_decode($get('selected_workshops') ?? '[]', true);
                            if (empty($selectedWorkshops)) {
                                throw ValidationException::withMessages(['selected_workshops' => 'Debe seleccionar al menos un taller']);
                            }

                            $selectedMonthlyPeriodId = $get('selected_monthly_period_id');
                            if (! $selectedMonthlyPeriodId) {
                                throw ValidationException::withMessages(['selected_monthly_period_id' => 'Debe seleccionar un periodo mensual']);
                            }

                            // Validar clases especificas para cada taller
                            $workshopDetails = $get('workshop_details') ?? [];
                            $errors = [];

                            foreach ($workshopDetails as $index => $detail) {
                                $numberOfClasses = (int) ($detail['number_of_classes'] ?? 0);
                                $selectedClasses = $detail['selected_classes'] ?? [];

                                if ($numberOfClasses > 0) {
                                    if (empty($selectedClasses)) {
                                        $errors["workshop_details.{$index}.selected_classes"] = 'Debes seleccionar las clases especificas para este taller.';
                                    } elseif (count($selectedClasses) !== $numberOfClasses) {
                                        $errors["workshop_details.{$index}.selected_classes"] = "Debes seleccionar exactamente {$numberOfClasses} clase".($numberOfClasses > 1 ? 's' : '').' para este taller.';
                                    }
                                }
                            }

                            if (! empty($errors)) {
                                throw ValidationException::withMessages($errors);
                            }
                        })
                        ->schema([
                            Forms\Components\Repeater::make('workshop_details')
                                ->label('')
                                ->schema([
                                    Forms\Components\Hidden::make('instructor_workshop_id'),

                                    // Comentarios adicionales del workshop
                                    Forms\Components\Placeholder::make('workshop_comments_section')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            $workshopId = $get('instructor_workshop_id');
                                            if (! $workshopId) {
                                                return '';
                                            }

                                            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);
                                            if (! $instructorWorkshop || empty($instructorWorkshop->workshop->additional_comments)) {
                                                return '';
                                            }

                                            return new \Illuminate\Support\HtmlString("
                                                <div style='background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 16px;'>
                                                    <div style='display: flex; align-items: flex-start;'>
                                                        <div style='margin-left: 12px;'>
                                                            <h3 style='font-size: 14px; font-weight: 500; color: #15803d;'>
                                                                Comentarios adicionales del Taller
                                                            </h3>
                                                            <div style='margin-top: 8px; font-size: 14px; color: #166534;'>
                                                                <p>".nl2br(e($instructorWorkshop->workshop->additional_comments)).'</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ');
                                        })
                                        ->visible(function (Forms\Get $get) {
                                            $workshopId = $get('instructor_workshop_id');
                                            if (! $workshopId) {
                                                return false;
                                            }

                                            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);

                                            return $instructorWorkshop && ! empty($instructorWorkshop->workshop->additional_comments);
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Hidden::make('enrollment_type')
                                        ->default('full_month'),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('number_of_classes')
                                                ->label('Cantidad de Clases')
                                                ->live()
                                                ->options(function (Forms\Get $get) {
                                                    $workshopId = $get('instructor_workshop_id');
                                                    if (! $workshopId) {
                                                        return [];
                                                    }

                                                    $workshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);
                                                    if (! $workshop) {
                                                        return [];
                                                    }

                                                    $maxClasses = $workshop->workshop->number_of_classes ?? 4;
                                                    $options = [];

                                                    for ($i = 1; $i <= $maxClasses; $i++) {
                                                        $options[$i] = $i.($i === 1 ? ' Clase' : ' Clases');
                                                    }

                                                    return $options;
                                                })
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    $set('selected_classes', []);
                                                })
                                                ->placeholder('Seleccionar cantidad'),

                                            Forms\Components\Select::make('selected_classes')
                                                ->label('Clases Específicas')
                                                ->multiple()
                                                ->searchable()
                                                ->live(onBlur: true)
                                                ->options(function (Forms\Get $get) {
                                                    $workshopId = $get('instructor_workshop_id');
                                                    $selectedMonthlyPeriodId = $get('../../selected_monthly_period_id');
                                                    $currentSelectedClasses = $get('selected_classes') ?? [];

                                                    if (! $workshopId || ! $selectedMonthlyPeriodId) {
                                                        // Si no hay contexto pero hay clases seleccionadas, cargarlas
                                                        if (! empty($currentSelectedClasses)) {
                                                            $existingClasses = \App\Models\WorkshopClass::whereIn('id', $currentSelectedClasses)->get();
                                                            $options = [];
                                                            foreach ($existingClasses as $class) {
                                                                $dayName = \Carbon\Carbon::parse($class->class_date)->translatedFormat('l');
                                                                $formattedDate = \Carbon\Carbon::parse($class->class_date)->format('d/m/Y');
                                                                $startTime = \Carbon\Carbon::parse($class->start_time)->format('H:i');
                                                                $endTime = \Carbon\Carbon::parse($class->end_time)->format('H:i');
                                                                $options[$class->id] = "{$dayName} {$formattedDate} ({$startTime} - {$endTime})";
                                                            }
                                                            return $options;
                                                        }
                                                        return [];
                                                    }

                                                    $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);
                                                    if (! $instructorWorkshop) {
                                                        return [];
                                                    }

                                                    // Obtener las clases del taller para el período seleccionado
                                                    $workshopClasses = \App\Models\WorkshopClass::where('workshop_id', $instructorWorkshop->workshop->id)
                                                        ->where('monthly_period_id', $selectedMonthlyPeriodId)
                                                        ->where('status', '!=', 'cancelled')
                                                        ->orderBy('class_date', 'asc')
                                                        ->get();

                                                    $options = [];
                                                    foreach ($workshopClasses as $class) {
                                                        $dayName = \Carbon\Carbon::parse($class->class_date)->translatedFormat('l');
                                                        $formattedDate = \Carbon\Carbon::parse($class->class_date)->format('d/m/Y');
                                                        $startTime = \Carbon\Carbon::parse($class->start_time)->format('H:i');
                                                        $endTime = \Carbon\Carbon::parse($class->end_time)->format('H:i');

                                                        $options[$class->id] = "{$dayName} {$formattedDate} ({$startTime} - {$endTime})";
                                                    }

                                                    // IMPORTANTE: Asegurar que las clases ya seleccionadas estén en las opciones
                                                    // Esto previene que se muestren solo IDs cuando se edita
                                                    if (! empty($currentSelectedClasses)) {
                                                        $missingClassIds = array_diff($currentSelectedClasses, array_keys($options));
                                                        if (! empty($missingClassIds)) {
                                                            $missingClasses = \App\Models\WorkshopClass::whereIn('id', $missingClassIds)->get();
                                                            foreach ($missingClasses as $class) {
                                                                $dayName = \Carbon\Carbon::parse($class->class_date)->translatedFormat('l');
                                                                $formattedDate = \Carbon\Carbon::parse($class->class_date)->format('d/m/Y');
                                                                $startTime = \Carbon\Carbon::parse($class->start_time)->format('H:i');
                                                                $endTime = \Carbon\Carbon::parse($class->end_time)->format('H:i');
                                                                $options[$class->id] = "{$dayName} {$formattedDate} ({$startTime} - {$endTime})";
                                                            }
                                                        }
                                                    }

                                                    return $options;
                                                })
                                                ->placeholder('Seleccionar clases específicas')
                                                ->helperText(function (Forms\Get $get) {
                                                    $numberOfClasses = $get('number_of_classes');
                                                    if (! $numberOfClasses) {
                                                        return 'Primero selecciona la cantidad de clases';
                                                    }

                                                    return $numberOfClasses == 1 ? 'Selecciona 1 clase específica' : "Selecciona {$numberOfClasses} clases específicas";
                                                })
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                    $numberOfClasses = (int) $get('number_of_classes');
                                                    $selectedClasses = is_array($state) ? $state : [];

                                                    if ($numberOfClasses && count($selectedClasses) > $numberOfClasses) {
                                                        $limitedClasses = array_slice($selectedClasses, 0, $numberOfClasses);
                                                        $set('selected_classes', $limitedClasses);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Límite de clases')
                                                            ->body("Solo puedes seleccionar {$numberOfClasses} clase".($numberOfClasses > 1 ? 's' : '').'. Se han mantenido las primeras seleccionadas.')
                                                            ->warning()
                                                            ->send();
                                                    }
                                                })
                                                ->validationAttribute('clases específicas')
                                                ->rules(['nullable']),
                                        ]),

                                    Forms\Components\Hidden::make('enrollment_date')
                                        ->default(now()->format('Y-m-d')),
                                ])
                                ->addable(false)
                                ->collapsible(true)
                                ->deletable(true)
                                ->reorderable(false)
                                ->itemLabel(function (array $state): ?string {
                                    $workshopId = $state['instructor_workshop_id'] ?? null;
                                    if (!$workshopId) {
                                        return 'Taller';
                                    }

                                    $workshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])->find($workshopId);
                                    if (!$workshop) {
                                        return 'Taller';
                                    }

                                    // Convertir día de la semana
                                    $daysOfWeek = $workshop->day_of_week;
                                    if (is_array($daysOfWeek)) {
                                        $dayInSpanish = implode('/', $daysOfWeek);
                                    } else {
                                        $dayInSpanish = $daysOfWeek ?? 'N/A';
                                    }

                                    $modality = $workshop->workshop->modality ?? 'N/A';
                                    $price = $workshop->workshop->standard_monthly_fee ?? '0.00';
                                    $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                                    $endTime = \Carbon\Carbon::parse($workshop->end_time ?? $workshop->start_time)->format('H:i');

                                    return "{$workshop->workshop->name} - Modalidad: {$modality} - Profesor: {$workshop->instructor->first_names} {$workshop->instructor->last_names} - Día: {$dayInSpanish} - Hora: {$startTime} - {$endTime} - Precio: S/ {$price}";
                                })
                                ->cloneable(false)
                                ->expandAllAction(
                                    fn (Action $action) => $action->hidden()
                                )
                                ->collapseAllAction(
                                    fn (Action $action) => $action->hidden()
                                )
                                ->columnSpanFull(),
                        ]),

                    // Paso 3: Pago y Finalización
                    Forms\Components\Wizard\Step::make('Pago y Finalización')
                        ->icon('heroicon-o-credit-card')
                        ->beforeValidation(function (Forms\Get $get) {
                            $workshopDetails = $get('workshop_details');
                            if (empty($workshopDetails)) {
                                throw ValidationException::withMessages(['workshop_details' => 'Debe configurar al menos un taller']);
                            }
                        })
                        ->schema([
                            // Resumen de talleres y cálculo de precios
                            Forms\Components\Placeholder::make('enrollment_summary')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            $workshopDetails = $get('workshop_details') ?? [];
                                            if (empty($workshopDetails)) {
                                                return 'No hay talleres seleccionados';
                                            }

                                            // Obtener información del estudiante
                                            $studentId = $get('student_id');
                                            $student = \App\Models\Student::find($studentId);
                                            $inscriptionMultiplier = $student ? $student->inscription_multiplier : 1.0;
                                            $isPrePama = $student ? $student->is_pre_pama : false;
                                            $selectedMonthlyPeriodId = $get('selected_monthly_period_id');

                                            $html = '<div class="space-y-4">';

                                            // Información del estudiante
                                            if ($student) {
                                                $categoryText = $student->category_partner ?? 'No definida';
                                                $html .= "
                                                    <div class='mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg'>
                                                        <h3 class='font-semibold text-blue-800 mb-2'>Información del Estudiante</h3>
                                                        <div class='grid grid-cols-2 gap-4 text-sm'>
                                                            <div>
                                                                <p><strong>Nombre:</strong> {$student->first_names} {$student->last_names}</p>
                                                                <p><strong>Código:</strong> {$student->student_code}</p>
                                                            </div>
                                                            <div>
                                                                <p><strong>Categoría:</strong> {$categoryText}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ";
                                            }

                                            $subtotal = 0;
                                            $prepamaTotal = 0;

                                            foreach ($workshopDetails as $detail) {
                                                $workshopId = $detail['instructor_workshop_id'] ?? null;
                                                $numberOfClasses = $detail['number_of_classes'] ?? 1;
                                                $selectedClasses = $detail['selected_classes'] ?? [];

                                                if (! $workshopId) {
                                                    continue;
                                                }

                                                $instructorWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                    ->find($workshopId);

                                                if (! $instructorWorkshop) {
                                                    continue;
                                                }

                                                // Obtener el precio base
                                                $pricing = \App\Models\WorkshopPricing::where('workshop_id', $instructorWorkshop->workshop->id)
                                                    ->where('number_of_classes', $numberOfClasses)
                                                    ->where('for_volunteer_workshop', false)
                                                    ->first();

                                                $basePrice = $pricing ? $pricing->price : ($instructorWorkshop->workshop->standard_monthly_fee * $numberOfClasses / 4);

                                                // Calcular recargo PRE-PAMA
                                                $prepamaCharge = $isPrePama ? ($basePrice * ($inscriptionMultiplier - 1)) : 0;
                                                $finalPrice = $basePrice * $inscriptionMultiplier;

                                                $subtotal += $basePrice;
                                                $prepamaTotal += $prepamaCharge;

                                                // Convertir día de la semana
                                                $daysOfWeek = $instructorWorkshop->day_of_week;
                                                if (is_array($daysOfWeek)) {
                                                    $dayInSpanish = implode('/', $daysOfWeek);
                                                } else {
                                                    $dayInSpanish = $daysOfWeek ?? 'N/A';
                                                }
                                                $classesLabel = $numberOfClasses.($numberOfClasses === 1 ? ' clase' : ' clases');

                                                // Obtener las fechas de las clases específicas
                                                $classDatesText = '';
                                                if (!empty($selectedClasses) && $selectedMonthlyPeriodId) {
                                                    $workshopClasses = \App\Models\WorkshopClass::whereIn('id', $selectedClasses)
                                                        ->orderBy('class_date', 'asc')
                                                        ->get();

                                                    $classDates = [];
                                                    foreach ($workshopClasses as $class) {
                                                        $classDates[] = \Carbon\Carbon::parse($class->class_date)->format('d/m');
                                                    }

                                                    if (!empty($classDates)) {
                                                        $classDatesText = '<div class="mt-2 text-xs text-green-600">
                                                            <strong>Fechas de clases:</strong> ' . implode(', ', $classDates) . '
                                                        </div>';
                                                    }
                                                }

                                                // Mostrar información del precio
                                                $priceInfo = 'S/ '.number_format($basePrice, 2);
                                                if ($isPrePama && $prepamaCharge > 0) {
                                                    $priceInfo = 'S/ '.number_format($basePrice, 2).' + S/ '.number_format($prepamaCharge, 2).' (PRE-PAMA) = S/ '.number_format($finalPrice, 2);
                                                }

                                                $modality = $instructorWorkshop->workshop->modality ?? 'No especificada';

                                                $html .= "
                                                    <div class='bg-green-50 border border-green-200 rounded-lg p-4'>
                                                        <div class='flex justify-between items-start'>
                                                            <div class='flex-1'>
                                                                <h4 class='font-semibold text-green-800'>{$instructorWorkshop->workshop->name}</h4>
                                                                <p class='text-sm text-green-600 mt-1'>
                                                                    <span class='font-medium'>Modalidad:</span> {$modality} • {$dayInSpanish} • ".\Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i A').' - '.\Carbon\Carbon::parse($instructorWorkshop->end_time ?? $instructorWorkshop->start_time)->format('H:i A')." • {$classesLabel}
                                                                </p>
                                                                {$classDatesText}
                                                            </div>
                                                            <div class='text-right ml-4'>
                                                                <p class='font-bold text-green-800'>{$priceInfo}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ";
                                            }

                                            $totalFinal = $subtotal + $prepamaTotal;

                                            $html .= "
                                                <div class='border-t pt-4 mt-4'>
                                                    <div class='bg-gray-50 p-4 rounded-lg'>
                                                        <div class='space-y-2'>
                                                            <div class='flex justify-between'>
                                                                <span>Sub Total Ventas</span>
                                                                <span>S/ ".number_format($subtotal, 2)."</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm ".($prepamaTotal > 0 ? 'text-orange-600 font-medium' : 'text-gray-600')."'>
                                                                <span>Recargo PRE-PAMA</span>
                                                                <span>S/ ".number_format($prepamaTotal, 2)."</span>
                                                            </div>
                                                            <div class='flex justify-between font-semibold border-t pt-2'>
                                                                <span>Valor Venta</span>
                                                                <span>S/ ".number_format($totalFinal, 2).'</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ';

                                            // Nota informativa si es PRE-PAMA
                                            if ($isPrePama && $prepamaTotal > 0) {
                                                $html .= "
                                                    <div class='mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg'>
                                                        <div class='flex items-center'>
                                                            <svg class='w-5 h-5 text-orange-500 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                                                            </svg>
                                                            <div>
                                                                <p class='text-sm font-medium text-orange-800'>Estudiante PRE-PAMA</p>
                                                                <p class='text-xs text-orange-700'>Se ha aplicado un recargo adicional según la categoría del estudiante.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ";
                                            }

                                            $html .= '</div>';

                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpanFull(),

                            // Selección de método de pago
                            Forms\Components\Section::make('Seleccionar medio de pago')
                                ->schema([
                                    Forms\Components\Radio::make('payment_method')
                                        ->label('')
                                        ->options([
                                            'cash' => 'Pago en Efectivo',
                                            'link' => 'Pago con Link',
                                        ])
                                        ->descriptions([
                                            'cash' => 'De forma presencial en la secretaría de PAMA',
                                            'link' => 'Tesorería genera enlace de pago con tarjeta',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('payment_status', 'pending');
                                        })
                                        ->columnSpanFull(),

                                    // Campo oculto para el estado de pago
                                    Forms\Components\Hidden::make('payment_status')
                                        ->default('pending'),
                                ])
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
                    ->persistStepInQueryString()
                    ->submitAction(new \Illuminate\Support\HtmlString('
                    <x-filament::button
                        type="submit"
                        size="sm"
                        color="primary"
                    >
                        Finalizar Inscripciones
                    </x-filament::button>
                ')),
            ])
            ->columns(1);
    }

    // Agregar también este método estático en la clase
    public static function findPreviousWorkshops($studentId, $selectedMonthlyPeriodId)
    {
        if (! $selectedMonthlyPeriodId) {
            return [];
        }

        try {
            $currentPeriod = \App\Models\MonthlyPeriod::find($selectedMonthlyPeriodId);
            if (! $currentPeriod) {
                return [];
            }

            // Calcular período anterior
            $previousMonth = $currentPeriod->month - 1;
            $previousYear = $currentPeriod->year;

            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear -= 1;
            }

            // Buscar período anterior
            $previousPeriod = \App\Models\MonthlyPeriod::where('year', $previousYear)
                ->where('month', $previousMonth)
                ->first();

            if (! $previousPeriod) {
                return [];
            }

            // Buscar inscripciones previas válidas (excluir solo refunded)
            $previousEnrollments = \App\Models\StudentEnrollment::where('student_id', $studentId)
                ->where('monthly_period_id', $previousPeriod->id)
                ->whereNotIn('payment_status', ['refunded'])
                ->with('instructorWorkshop.workshop')
                ->orderBy('created_at', 'desc') // Más recientes primero
                ->get();

            // DEBUG: Descomentar para ver qué inscripciones se encontraron
            // \Log::info('findPreviousWorkshops', [
            //     'student_id' => $studentId,
            //     'previous_period' => $previousPeriod->id,
            //     'enrollments_found' => $previousEnrollments->count(),
            //     'payment_statuses' => $previousEnrollments->pluck('payment_status')->toArray(),
            //     'instructor_workshop_ids' => $previousEnrollments->pluck('instructor_workshop_id')->toArray()
            // ]);

            // Obtener IDs de talleres válidos (sin duplicados por nombre de taller)
            $previousWorkshopIds = [];
            $seenWorkshopNames = []; // Para trackear nombres de talleres ya procesados

            foreach ($previousEnrollments as $enrollment) {
                if ($enrollment->instructorWorkshop &&
                    $enrollment->instructorWorkshop->is_active &&
                    $enrollment->instructorWorkshop->workshop) {

                    $workshopName = $enrollment->instructorWorkshop->workshop->name;

                    // Solo agregar si no hemos visto este NOMBRE de taller antes
                    // Esto previene duplicados cuando hay múltiples horarios del mismo taller
                    if (!in_array($workshopName, $seenWorkshopNames)) {
                        $previousWorkshopIds[] = $enrollment->instructor_workshop_id;
                        $seenWorkshopNames[] = $workshopName;
                    }
                }
            }

            return array_unique($previousWorkshopIds);
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

            ])
            ->filters([

            ])
            ->actions([

            ])
            ->bulkActions([

            ])
            ->defaultSort('enrollment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
