<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Filament\Resources\EnrollmentResource\RelationManagers;
use App\Models\StudentEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                            // Selección del estudiante
                            Forms\Components\Select::make('student_id')
                                ->label('Estudiante')
                                ->relationship('student', 'first_names')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    "{$record->last_names} {$record->first_names} - {$record->student_code}"
                                )
                                ->searchable(['first_names', 'last_names', 'student_code'])
                                ->preload()
                                ->required()
                                ->placeholder('Buscar estudiante...')
                                ->helperText(function (Forms\Get $get) {
                                    $studentId = $get('student_id');
                                    if (!$studentId) {
                                        return 'Selecciona el estudiante que se inscribirá';
                                    }

                                    $student = \App\Models\Student::find($studentId);
                                    if (!$student) {
                                        return 'Estudiante no encontrado';
                                    }

                                    if (!$student->monthly_maintenance_paid) {
                                        return '⚠️ Este estudiante NO está al día con el mantenimiento mensual';
                                    }

                                    return '✅ Estudiante al día con el mantenimiento mensual';
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) {
                                        $set('selected_workshops', '[]');
                                        return;
                                    }

                                    $student = \App\Models\Student::find($state);
                                    if (!$student) {
                                        $set('selected_workshops', '[]');
                                        return;
                                    }

                                    // Si el estudiante no está al día con el mantenimiento, mostrar notificación
                                    if (!$student->monthly_maintenance_paid) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Estudiante no al día')
                                            ->body("El estudiante {$student->first_names} {$student->last_names} no está al día con el pago del mantenimiento mensual. No se puede proceder con la inscripción.")
                                            ->danger()
                                            ->persistent()
                                            ->send();

                                        // Limpiar talleres seleccionados
                                        $set('selected_workshops', '[]');
                                    } else {
                                        // Si está al día, mostrar notificación de éxito
                                        \Filament\Notifications\Notification::make()
                                            ->title('Estudiante verificado')
                                            ->body("El estudiante {$student->first_names} {$student->last_names} está al día con el mantenimiento mensual. Puede proceder con la inscripción.")
                                            ->success()
                                            ->send();
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Select::make('selected_monthly_period_id')
                                ->label('Período Mensual')
                                ->options(function () {
                                    $currentDate = now();
                                    $nextDate = now()->addMonth();

                                    $options = [];

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
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Limpiar talleres seleccionados cuando cambie el período
                                    $set('selected_workshops', '[]');
                                })
                                ->columnSpanFull(),

                            // Separador visual
                            Forms\Components\Section::make('Catálogo de Talleres')
                                ->description('Selecciona los talleres en los que deseas inscribir al estudiante')
                                ->schema([
                                    // Campo oculto para almacenar los talleres seleccionados
                                    Forms\Components\Hidden::make('selected_workshops')
                                        ->default('[]')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            // Sincronizar los talleres seleccionados con el paso 2
                                            $selectedWorkshops = json_decode($state ?? '[]', true);
                                            $workshopDetails = [];

                                            foreach ($selectedWorkshops as $workshopId) {
                                                $workshopDetails[] = [
                                                    'instructor_workshop_id' => $workshopId,
                                                    'enrollment_type' => 'full_month',
                                                    'number_of_classes' => 1,
                                                    'enrollment_date' => now()->format('Y-m-d'),
                                                ];
                                            }

                                            $set('workshop_details', $workshopDetails);
                                        }),

                                    // Vista personalizada de talleres en cards
                                    Forms\Components\ViewField::make('workshop_cards')
                                        ->label('')
                                        ->view('filament.forms.components.workshop-cards')
                                        ->viewData(function (Forms\Get $get) {
                                            $studentId = $get('student_id');
                                            $selectedWorkshops = json_decode($get('selected_workshops') ?? '[]', true);
                                            $selectedMonthlyPeriodId = $get('selected_monthly_period_id');

                                            // Obtener todos los talleres disponibles
                                            $workshops = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                ->get()
                                                ->map(function ($instructorWorkshop) use ($selectedWorkshops, $selectedMonthlyPeriodId) {

                                                    // CALCULAR CUPOS DISPONIBLES
                                                    $capacity = $instructorWorkshop->max_capacity ?? $instructorWorkshop->workshop->capacity ?? 0;
                                                    $currentEnrollments = 0;
                                                    $availableSpots = $capacity;

                                                    // Si hay período seleccionado, calcular inscripciones actuales
                                                    if ($selectedMonthlyPeriodId) {
                                                        $currentEnrollments = \App\Models\StudentEnrollment::where('instructor_workshop_id', $instructorWorkshop->id)
                                                            ->where('monthly_period_id', $selectedMonthlyPeriodId)
                                                            ->whereIn('payment_status', ['completed', 'pending'])
                                                            ->distinct('student_id')
                                                            ->count();

                                                        \Log::info("Workshop {$instructorWorkshop->workshop->name} (ID: {$instructorWorkshop->id}): {$currentEnrollments}/{$capacity}");
                                                        
                                                        $availableSpots = $capacity - $currentEnrollments;
                                                        
                                                        \Log::info("Workshop Debug - {$instructorWorkshop->workshop->name}: {$currentEnrollments}/{$capacity} = {$availableSpots} disponibles");
                                                    }

                                                    // Convertir día de la semana (número) a texto en español
                                                    $dayNames = [
                                                        1 => 'Lunes',
                                                        2 => 'Martes',
                                                        3 => 'Miércoles',
                                                        4 => 'Jueves',
                                                        5 => 'Viernes',
                                                        6 => 'Sábado',
                                                        7 => 'Domingo',
                                                        0 => 'Domingo', // Por si usan 0 para domingo
                                                    ];

                                                    $dayInSpanish = $dayNames[$instructorWorkshop->day_of_week] ?? 'Día ' . $instructorWorkshop->day_of_week;

                                                    return [
                                                        'id' => $instructorWorkshop->id,
                                                        'name' => $instructorWorkshop->workshop->name,
                                                        'instructor' => $instructorWorkshop->instructor->first_names . ' ' . $instructorWorkshop->instructor->last_names,
                                                        'day' => $dayInSpanish,
                                                        'start_time' => \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i'),
                                                        'end_time' => $instructorWorkshop->workshop->end_time ? \Carbon\Carbon::parse($instructorWorkshop->workshop->end_time)->format('H:i') : 'N/A',
                                                        'price' => $instructorWorkshop->workshop->standard_monthly_fee,
                                                        'max_classes' => $instructorWorkshop->workshop->number_of_classes,
                                                        'selected' => in_array($instructorWorkshop->id, $selectedWorkshops),
                                                        'capacity' => $capacity,
                                                        'current_enrollments' => $currentEnrollments,
                                                        'available_spots' => $availableSpots,
                                                        'is_full' => $availableSpots <= 0,
                                                    ];
                                                });

                                            return [
                                                'workshops' => $workshops,
                                                'student_id' => $studentId,
                                            ];
                                        })
                                        ->live()
                                        ->key(fn (Forms\Get $get) => 'workshops_' . $get('selected_monthly_period_id') . '_' . now()->timestamp)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // Paso 2: Configuración de Detalles
                    Forms\Components\Wizard\Step::make('Configurar Detalles')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->beforeValidation(function (Forms\Get $get) {
                            $studentId = $get('student_id');
                            if (!$studentId) {
                                throw ValidationException::withMessages(['student_id' => 'Debe seleccionar un estudiante']);
                            }

                            $student = \App\Models\Student::find($studentId);
                            if (!$student || !$student->monthly_maintenance_paid) {
                                throw ValidationException::withMessages(['student_id' => 'El estudiante seleccionado no está al día con el mantenimiento mensual']);
                            }

                            $selectedWorkshops = json_decode($get('selected_workshops') ?? '[]', true);
                            if (empty($selectedWorkshops)) {
                                throw ValidationException::withMessages(['selected_workshops' => 'Debe seleccionar al menos un taller']);
                            }

                            $selectedMonthlyPeriodId = $get('selected_monthly_period_id');
                            if (!$selectedMonthlyPeriodId) {
                                throw ValidationException::withMessages(['selected_monthly_period_id' => 'Debe seleccionar un período mensual']);
                            }
                        })
                        ->schema([
                            /* Forms\Components\Select::make('selected_monthly_period_id')
                                ->label('Período Mensual')
                                ->options(function () {
                                    $currentDate = now();
                                    $nextDate = now()->addMonth();

                                    $options = [];

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
                                ->columnSpanFull(), */

                            Forms\Components\Repeater::make('workshop_details')
                                ->label('Detalles de los Talleres Seleccionados')
                                ->schema([
                                    Forms\Components\Hidden::make('instructor_workshop_id'),

                                    Forms\Components\Placeholder::make('workshop_info')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            $workshopId = $get('instructor_workshop_id');
                                            if (!$workshopId) return 'Taller no seleccionado';

                                            $workshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                ->find($workshopId);

                                            if (!$workshop) return 'Taller no encontrado';

                                            // Convertir día de la semana (número) a texto en español
                                            $dayNames = [
                                                1 => 'Lunes',
                                                2 => 'Martes',
                                                3 => 'Miércoles',
                                                4 => 'Jueves',
                                                5 => 'Viernes',
                                                6 => 'Sábado',
                                                7 => 'Domingo',
                                                0 => 'Domingo', // Por si usan 0 para domingo
                                            ];

                                            $dayInSpanish = $dayNames[$workshop->day_of_week] ?? 'Día ' . $workshop->day_of_week;

                                            return new \Illuminate\Support\HtmlString("
                                                <div class='bg-gray-50 p-4 rounded-lg'>
                                                    <h3 class='font-semibold text-lg text-gray-900'>{$workshop->workshop->name}</h3>
                                                    <div class='mt-2 space-y-1 text-sm text-gray-600'>
                                                        <p><strong>Profesor:</strong> {$workshop->instructor->first_names} {$workshop->instructor->last_names}</p>
                                                        <p><strong>Día:</strong> {$dayInSpanish}</p>
                                                        <p><strong>Hora:</strong> " . \Carbon\Carbon::parse($workshop->start_time)->format('H:i') . " - " . \Carbon\Carbon::parse($workshop->end_time)->format('H:i') . "</p>
                                                        <p><strong>Precio:</strong> S/ {$workshop->workshop->standard_monthly_fee}</p>
                                                    </div>
                                                </div>
                                            ");
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Radio::make('enrollment_type')
                                                ->label('Tipo de Clase')
                                                ->options([
                                                    'full_month' => 'Regular',
                                                    'specific_classes' => 'Recuperación',
                                                ])
                                                ->default('full_month')
                                                ->required(),

                                            Forms\Components\Select::make('number_of_classes')
                                                ->label('Cantidad de Clases')
                                                ->options(function (Forms\Get $get) {
                                                    $workshopId = $get('instructor_workshop_id');
                                                    if (!$workshopId) return [];

                                                    $workshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);
                                                    if (!$workshop) return [];

                                                    $maxClasses = $workshop->workshop->number_of_classes;
                                                    $options = [];

                                                    for ($i = 1; $i <= $maxClasses; $i++) {
                                                        $options[$i] = $i . ($i === 1 ? ' Clase' : ' Clases');
                                                    }

                                                    return $options;
                                                })
                                                ->required()
                                                ->placeholder('Seleccionar cantidad'),

                                            Forms\Components\DatePicker::make('enrollment_date')
                                                ->label('Fecha de Inicio')
                                                ->default(now())
                                                ->required()
                                                ->helperText('Fecha de la primera clase'),
                                        ]),
                                ])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columnSpanFull(),

                            // Notas generales
                            Forms\Components\Textarea::make('notes')
                                ->label('Notas Generales')
                                ->placeholder('Agregar comentarios generales sobre las inscripciones...')
                                ->rows(3)
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
                            Forms\Components\Section::make('Resumen de Inscripciones')
                                ->description('Revisa los talleres seleccionados y sus precios')
                                ->schema([
                                    Forms\Components\Placeholder::make('enrollment_summary')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            $workshopDetails = $get('workshop_details') ?? [];
                                            if (empty($workshopDetails)) {
                                                return 'No hay talleres seleccionados';
                                            }

                                            // 🔥 OBTENER INFORMACIÓN DEL ESTUDIANTE PARA VERIFICAR SI ES PRE-PAMA
                                            $studentId = $get('student_id');
                                            $student = \App\Models\Student::find($studentId);
                                            $isPrepama = $student && $student->category_partner === 'Individual PRE-PAMA';

                                            $html = '<div class="space-y-4">';
                                            $subtotal = 0;
                                            $prepamaTotal = 0; // 🔥 NUEVO: Total del recargo PRE-PAMA

                                            foreach ($workshopDetails as $detail) {
                                                $workshopId = $detail['instructor_workshop_id'] ?? null;
                                                $numberOfClasses = $detail['number_of_classes'] ?? 1;
                                                $enrollmentType = $detail['enrollment_type'] ?? 'specific_classes';

                                                if (!$workshopId) continue;

                                                $instructorWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                                                    ->find($workshopId);

                                                if (!$instructorWorkshop) continue;

                                                // Obtener el precio base desde workshop_pricings
                                                $pricing = \App\Models\WorkshopPricing::where('workshop_id', $instructorWorkshop->workshop->id)
                                                    ->where('number_of_classes', $numberOfClasses)
                                                    ->where('for_volunteer_workshop', false)
                                                    ->first();

                                                $basePrice = $pricing ? $pricing->price : ($instructorWorkshop->workshop->standard_monthly_fee * $numberOfClasses / 4);
                                                
                                                // 🔥 CALCULAR RECARGO PRE-PAMA
                                                $prepamaCharge = $isPrepama ? ($basePrice * 0.5) : 0;
                                                $finalPrice = $basePrice + $prepamaCharge;
                                                
                                                $subtotal += $basePrice;
                                                $prepamaTotal += $prepamaCharge;

                                                // Convertir día de la semana
                                                $dayNames = [
                                                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                                    7 => 'Domingo', 0 => 'Domingo'
                                                ];
                                                $dayInSpanish = $dayNames[$instructorWorkshop->day_of_week] ?? 'Día ' . $instructorWorkshop->day_of_week;

                                                $typeLabel = $enrollmentType === 'full_month' ? 'Regular' : 'Recuperación';
                                                $classesLabel = $numberOfClasses . ($numberOfClasses === 1 ? ' clase' : ' clases');

                                                // 🔥 MOSTRAR INFORMACIÓN DEL RECARGO SI APLICA
                                                $priceInfo = "S/ " . number_format($basePrice, 2);
                                                if ($isPrepama && $prepamaCharge > 0) {
                                                    $priceInfo = "S/ " . number_format($basePrice, 2) . " + S/ " . number_format($prepamaCharge, 2) . " (PRE-PAMA) = S/ " . number_format($finalPrice, 2);
                                                }

                                                $html .= "
                                                    <div class='bg-green-50 border border-green-200 rounded-lg p-4'>
                                                        <div class='flex justify-between items-start'>
                                                            <div>
                                                                <h4 class='font-semibold text-green-800'>{$instructorWorkshop->workshop->name}</h4>
                                                                <p class='text-sm text-green-600 mt-1'>
                                                                    <span class='inline-block bg-green-100 px-2 py-1 rounded text-xs mr-2'>{$typeLabel}</span>
                                                                    {$dayInSpanish} • " . \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i') . " • {$classesLabel}
                                                                </p>
                                                            </div>
                                                            <div class='text-right'>
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
                                                                <span>S/ " . number_format($subtotal, 2) . "</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm " . ($prepamaTotal > 0 ? 'text-orange-600 font-medium' : 'text-gray-600') . "'>
                                                                <span>Recargo PRE-PAMA 50%</span>
                                                                <span>S/ " . number_format($prepamaTotal, 2) . "</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>Anticipos</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>Descuentos</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between font-semibold border-t pt-2'>
                                                                <span>Valor Venta</span>
                                                                <span>S/ " . number_format($totalFinal, 2) . "</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>IGV</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>Otros Cargos</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>Otros Tributos</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between text-sm text-gray-600'>
                                                                <span>Monto de Redondeo</span>
                                                                <span>S/ 0.00</span>
                                                            </div>
                                                            <div class='flex justify-between text-lg font-bold border-t pt-2 text-blue-800'>
                                                                <span>Importe Total</span>
                                                                <span>S/ " . number_format($totalFinal, 2) . "</span>
                                                            </div>
                                                        </div>
                                                    </div>";

                                            // 🔥 AGREGAR NOTA INFORMATIVA SI ES PRE-PAMA
                                            if ($isPrepama && $prepamaTotal > 0) {
                                                $html .= "
                                                    <div class='mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg'>
                                                        <div class='flex items-center'>
                                                            <svg class='w-5 h-5 text-orange-500 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                                                            </svg>
                                                            <div>
                                                                <p class='text-sm font-medium text-orange-800'>Estudiante PRE-PAMA</p>
                                                                <p class='text-xs text-orange-700'>Se ha aplicado un recargo adicional del 50% según la categoría del estudiante.</p>
                                                            </div>
                                                        </div>
                                                    </div>";
                                            }

                                            $html .= "</div></div>";

                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ]),                
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
                                            // Actualizar el estado de pago según el método seleccionado
                                            if ($state === 'cash') {
                                                $set('payment_status', 'completed');
                                            } elseif ($state === 'link') {
                                                $set('payment_status', 'pending');
                                            }
                                        })
                                        ->columnSpanFull(),

                                    // Campo oculto para el estado de pago
                                    Forms\Components\Hidden::make('payment_status')
                                        ->default('pending'),
                                ])
                                ->columnSpanFull(),

                            // Información de pago adicional
                            Forms\Components\Section::make('Información de Pago')
                                ->description('Información adicional sobre fechas y documentos de pago')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\DatePicker::make('payment_due_date')
                                                ->label('Fecha Límite de Pago')
                                                ->helperText('Fecha límite para realizar el pago')
                                                ->disabled(fn (Forms\Get $get): bool => $get('payment_method') === 'cash')
                                                ->placeholder('Seleccionar fecha límite'),

                                            Forms\Components\DatePicker::make('payment_date')
                                                ->label('Fecha de Pago')
                                                ->helperText('Fecha en que se realizó el pago')
                                                ->disabled(fn (Forms\Get $get): bool => $get('payment_method') === 'cash')
                                                ->placeholder('Seleccionar fecha de pago'),
                                        ]),

                                    Forms\Components\FileUpload::make('payment_document')
                                        ->label('Documento de Pago')
                                        ->helperText('Subir comprobante de pago (PDF o imagen)')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(5120) // 5MB
                                        ->directory('payment-documents')
                                        ->visibility('private')
                                        ->disabled(fn (Forms\Get $get): bool => $get('payment_method') === 'cash')
                                        ->columnSpanFull(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Estudiante')
                    ->searchable(['student.first_names', 'student.last_names'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) =>
                        $record->student->first_names . ' ' . $record->student->last_names
                    ),

                Tables\Columns\TextColumn::make('instructorWorkshop.workshop.name')
                    ->label('Taller')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment_type')
                    ->label('Tipo de Inscripción')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full_month' => 'Regular',
                        'specific_classes' => 'Recuperación',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_month' => 'success',
                        'specific_classes' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('number_of_classes')
                    ->label('Cantidad de Clases')
                    ->formatStateUsing(fn (int $state): string => $state . ($state === 1 ? ' Clase' : ' Clases'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En Proceso',
                        'to_pay' => 'Por Pagar',
                        'completed' => 'Inscrito',
                        'credit_favor' => 'Crédito a Favor',
                        'refunded' => 'Devuelto',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'to_pay' => 'danger',
                        'completed' => 'success',
                        'credit_favor' => 'info',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_due_date')
                    ->label('Fecha Límite')
                    ->date('d/m/Y')
                    ->placeholder('No definida')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->placeholder('No pagado')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('enrollment_type')
                    ->label('Tipo de Inscripción')
                    ->options([
                        'full_month' => 'Regular',
                        'specific_classes' => 'Recuperación',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'En Proceso',
                        'to_pay' => 'Por Pagar',
                        'completed' => 'Inscrito',
                        'credit_favor' => 'Crédito a Favor',
                        'refunded' => 'Devuelto',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                    ]),

                Tables\Filters\SelectFilter::make('number_of_classes')
                    ->label('Cantidad de Clases')
                    ->options([
                        1 => '1 Clase',
                        2 => '2 Clases',
                        3 => '3 Clases',
                        4 => '4 Clases',
                        5 => '5 Clases',
                        6 => '6 Clases',
                        7 => '7 Clases',
                        8 => '8 Clases',
                    ]),

                Tables\Filters\Filter::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->form([
                        Forms\Components\DatePicker::make('enrollment_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('enrollment_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['enrollment_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '>=', $date),
                            )
                            ->when(
                                $data['enrollment_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('download_ticket')
                    ->label('Descargar Ticket')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (StudentEnrollment $record): string => route('enrollment.ticket', ['enrollmentId' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (StudentEnrollment $record): bool => $record->payment_status === 'completed' && $record->payment_method === 'cash')
                    ->color('success'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Inscripción')
                    ->modalDescription('¿Estás seguro de que deseas eliminar esta inscripción? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
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
