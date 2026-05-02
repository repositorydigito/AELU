<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Talleres';

    protected static ?string $pluralModelLabel = 'Talleres';

    protected static ?string $modelLabel = 'Taller';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Período del Taller')
                    ->description('Selecciona el mes para el cual se creará este taller')
                    ->schema([
                        Forms\Components\Select::make('monthly_period_id')
                            ->label('Período Mensual')
                            ->options(function ($livewire) {
                                $currentDate = now();
                                $query = \App\Models\MonthlyPeriod::query();

                                // Si estamos editando, incluir el período actual del taller
                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->record->monthly_period_id) {
                                    $currentWorkshopPeriodId = $livewire->record->monthly_period_id;
                                    $query->where(function ($q) use ($currentDate, $currentWorkshopPeriodId) {
                                        $q->where(function ($q) use ($currentDate) {
                                            // Meses desde el actual hasta fin del año actual
                                            $q->where('year', $currentDate->year)
                                                ->where('month', '>=', $currentDate->month);
                                        })
                                            ->orWhere('year', $currentDate->year + 1) // Todo el próximo año
                                            ->orWhere('id', $currentWorkshopPeriodId); // Mantener el período actual si está editando
                                    });
                                } else {
                                    // Para crear nuevo taller
                                    $query->where(function ($q) use ($currentDate) {
                                        $q->where(function ($q) use ($currentDate) {
                                            // Meses desde el actual hasta fin del año actual
                                            $q->where('year', $currentDate->year)
                                                ->where('month', '>=', $currentDate->month);
                                        })
                                            ->orWhere('year', $currentDate->year + 1); // Todo el próximo año
                                    });
                                }

                                return $query->orderBy('year', 'asc')
                                    ->orderBy('month', 'asc')
                                    ->get()
                                    ->mapWithKeys(function ($period) {
                                        $date = \Carbon\Carbon::create($period->year, $period->month, 1);

                                        return [$period->id => $date->translatedFormat('F Y')];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Limpiar fechas cuando cambie el período
                                $set('temp_start_date', null);
                                $set('schedule_data', []);
                            })
                            ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord &&
                                $livewire->record->hasEnrollments()
                            )
                            ->helperText(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord &&
                                $livewire->record->hasEnrollments()
                                    ? '⚠️ No se puede editar porque ya hay inscripciones'
                                    : 'Selecciona el mes del taller'
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Información del Taller')
                    ->schema([
                        Forms\Components\Hidden::make('pricing_surcharge_percentage')
                            ->default(20),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del taller')
                            ->required()
                            ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord &&
                                $livewire->record->hasEnrollments()
                            ),
                        Forms\Components\Select::make('instructor_id')
                            ->label('Profesor')
                            ->options(\App\Models\Instructor::all()->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('delegate_user_id')
                            ->label('Elegir delegado')
                            ->options(\App\Models\User::role('Delegado')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Seleccionar delegado')
                            ->nullable(),
                        Forms\Components\Select::make('day_of_week')
                            ->label('Día del taller')
                            ->multiple()
                            ->options([
                                'Lunes' => 'Lunes',
                                'Martes' => 'Martes',
                                'Miércoles' => 'Miércoles',
                                'Jueves' => 'Jueves',
                                'Viernes' => 'Viernes',
                                'Sábado' => 'Sábado',
                                'Domingo' => 'Domingo',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora')
                            ->withoutSeconds()
                            ->required(),
                        Forms\Components\TextInput::make('duration')
                            ->label('Duración de la Clase')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('minutos')
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Número de cupos (Aforo)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->rules([
                                function ($livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                        // Solo validar al editar, no al crear
                                        if (! ($livewire instanceof \Filament\Resources\Pages\EditRecord)) {
                                            return;
                                        }

                                        $workshop = $livewire->record;
                                        if (! $workshop || ! $workshop->exists) {
                                            return;
                                        }

                                        // Obtener el número actual de inscripciones activas
                                        $currentEnrollments = $workshop->enrollments()
                                            ->where('monthly_period_id', $workshop->monthly_period_id)
                                            ->whereIn('payment_status', ['completed', 'pending'])
                                            ->distinct('student_id')
                                            ->count();

                                        // Si hay inscripciones y la nueva capacidad es menor
                                        if ($currentEnrollments > 0 && $value < $currentEnrollments) {
                                            $fail("No puedes reducir los cupos a {$value} porque actualmente hay {$currentEnrollments} estudiantes inscritos en este taller. Debes cancelar inscripciones primero o establecer una capacidad de al menos {$currentEnrollments} cupos.");
                                        }
                                    };
                                },
                            ])
                            ->helperText(function ($livewire) {
                                // Mostrar información de inscripciones actuales al editar
                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->record) {
                                    $workshop = $livewire->record;
                                    $currentEnrollments = $workshop->enrollments()
                                        ->where('monthly_period_id', $workshop->monthly_period_id)
                                        ->whereIn('payment_status', ['completed', 'pending'])
                                        ->distinct('student_id')
                                        ->count();

                                    if ($currentEnrollments > 0) {
                                        return "⚠️ Actualmente hay {$currentEnrollments} estudiantes inscritos. No puedes reducir los cupos por debajo de este número.";
                                    }
                                }

                                return 'Establece el número máximo de estudiantes para este taller';
                            }),
                        Forms\Components\TextInput::make('number_of_classes')
                            ->label('Número de clases')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateScheduleDates($get, $set)),
                        Forms\Components\TextInput::make('standard_monthly_fee')
                            ->label('Tarifa del Mes')
                            ->prefix('S/.')
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('place')
                            ->label('Localización')
                            ->nullable(),
                        Forms\Components\Select::make('modality')
                            ->label('Modalidad')
                            ->options([
                                'Presencial' => 'Presencial',
                                'Virtual' => 'Virtual',
                            ])
                            ->nullable(),
                    ])
                    ->columns(5),

                Forms\Components\Section::make('Vista Previa de Tarifas')
                    ->schema([
                        Forms\Components\Placeholder::make('recargo_actual')
                            ->content(fn (Get $get) => 'Valor actual del porcentaje de recargo: '.($get('pricing_surcharge_percentage') ?? '20').'%')
                            ->extraAttributes(['style' => 'margin-bottom: 8px;'])
                            ->live(),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('Ajustes')
                                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord || $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->label('Ajustes')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->modalHeading('Ajustes')
                                ->modalSubmitActionLabel('Aplicar')
                                ->modalCancelActionLabel('Cancelar')
                                ->requiresConfirmation(false)
                                ->form([
                                    Forms\Components\TextInput::make('modal_pricing_surcharge_percentage')
                                        ->label('Porcentaje de recargo por clases sueltas')
                                        ->numeric()
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->default(fn (Get $get) => $get('pricing_surcharge_percentage') ?? 20)
                                        ->required(),
                                ])
                                ->fillForm(fn (Get $get): array => [
                                    'modal_pricing_surcharge_percentage' => $get('pricing_surcharge_percentage') ?? 20,
                                ])
                                ->action(function (array $data, Set $set) {
                                    $set('pricing_surcharge_percentage', $data['modal_pricing_surcharge_percentage']);
                                }),
                        ]),
                        Forms\Components\Placeholder::make('pricing_preview')
                            ->label('Tarifas que se generarán')
                            ->content(function (Get $get) {
                                return new \Illuminate\Support\HtmlString(self::generatePricingPreview($get));
                            })
                            ->live(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Horarios del Taller')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('temp_start_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->live()
                                    ->dehydrated(false)
                                    ->default(function ($livewire) {
                                        // Al editar, tomar la primera fecha de workshop_classes
                                        if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                            $firstClass = $livewire->record->workshopClasses()
                                                ->whereIn('status', ['scheduled', 'completed'])
                                                ->orderBy('class_date', 'asc')
                                                ->first();

                                            return $firstClass ? $firstClass->class_date : null;
                                        }

                                        return null;
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        self::calculateScheduleDates($get, $set);
                                    })
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $monthlyPeriodId = $get('monthly_period_id');
                                                if (! $monthlyPeriodId || ! $value) {
                                                    return;
                                                }

                                                $period = \App\Models\MonthlyPeriod::find($monthlyPeriodId);
                                                if (! $period) {
                                                    return;
                                                }

                                                $selectedDate = \Carbon\Carbon::parse($value);
                                                $startDate = \Carbon\Carbon::parse($period->start_date);
                                                $endDate = \Carbon\Carbon::parse($period->end_date);

                                                if ($selectedDate->lt($startDate) || $selectedDate->gt($endDate)) {
                                                    $monthName = $startDate->translatedFormat('F Y');
                                                    $fail("La fecha debe estar dentro del período seleccionado ({$monthName})");
                                                }
                                            };
                                        },
                                    ]),
                            ]),

                        Forms\Components\Placeholder::make('schedule_table')
                            ->label('Clases')
                            ->content(function (Get $get) {
                                return new \Illuminate\Support\HtmlString(
                                    view('filament.resources.workshop-resource.schedule-table', [
                                        'scheduleData' => $get('schedule_data') ?? [],
                                        'daysOfWeek' => $get('day_of_week') ?? [],
                                    ])->render()
                                );
                            })
                            ->columnSpanFull()
                            ->live(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('ajustar_fechas')
                                ->label('Ajustes')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->color('gray')
                                ->visible(fn (Get $get, $livewire) => ! empty($get('schedule_data')) && ($livewire instanceof \Filament\Resources\Pages\EditRecord || $livewire instanceof \Filament\Resources\Pages\CreateRecord))
                                ->modalHeading('Ajustes')
                                ->modalSubmitActionLabel('Aplicar')
                                ->modalCancelActionLabel('Cancelar')
                                ->form(function (Get $get) {
                                    $scheduleData = $get('schedule_data') ?? [];
                                    $fields = [];

                                    foreach ($scheduleData as $index => $class) {
                                        $fields[] = Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make("class_date_{$index}")
                                                    ->label('Clase '.($index + 1).' *')
                                                    ->default($class['raw_date'])
                                                    ->required()
                                                    ->rules([
                                                        function (Get $get) {
                                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                                $monthlyPeriodId = $get('monthly_period_id');
                                                                if (! $monthlyPeriodId || ! $value) {
                                                                    return;
                                                                }

                                                                // Obtener el periodo mensual
                                                                $period = \App\Models\MonthlyPeriod::find($monthlyPeriodId);
                                                                if (! $period) {
                                                                    return;
                                                                }

                                                                $selectedDate = \Carbon\Carbon::parse($value);
                                                                $startDate = \Carbon\Carbon::parse($period->start_date);
                                                                $endDate = \Carbon\Carbon::parse($period->end_date);

                                                                // Validar si la fecha seleccionada está dentro del periodo
                                                                if ($selectedDate->lt($startDate) || $selectedDate->gt($endDate)) {
                                                                    $monthName = $startDate->translatedFormat('F Y');
                                                                    $fail("La fecha debe estar dentro del período ({$monthName})");
                                                                }
                                                            };
                                                        },
                                                    ]),

                                                // Campo para marcar la clase como cancelada
                                                Forms\Components\Checkbox::make("is_cancelled_{$index}")
                                                    ->label('Cancelada')
                                                    ->default($class['status'] === 'cancelled'),
                                                // ->helperText('Marcar si esta clase está cancelada')
                                            ]);
                                    }

                                    return $fields;
                                })
                                ->fillForm(function (Get $get): array {
                                    $scheduleData = $get('schedule_data') ?? [];
                                    $formData = [];

                                    // Llenar los datos del formulario con las fechas de las clases
                                    foreach ($scheduleData as $index => $class) {
                                        $formData["class_date_{$index}"] = $class['raw_date'];
                                        $formData["is_cancelled_{$index}"] = $class['status'] === 'cancelled';
                                    }

                                    return $formData;
                                })
                                ->action(function (array $data, Set $set, Get $get, $livewire) {
                                    $scheduleData = $get('schedule_data') ?? [];
                                    $updatedScheduleData = [];

                                    foreach ($scheduleData as $index => $class) {
                                        $newDate = $data["class_date_{$index}"];
                                        $carbonDate = \Carbon\Carbon::parse($newDate);

                                        // Mantener el estado anterior si existe, sino usar el nuevo
                                        $isCancelled = isset($data["is_cancelled_{$index}"]) && $data["is_cancelled_{$index}"];
                                        $status = $isCancelled ? 'cancelled' : 'scheduled';

                                        $updatedScheduleData[] = [
                                            'class_number' => $class['class_number'],
                                            'date' => $carbonDate->format('d/m/Y'),
                                            'raw_date' => $carbonDate->format('Y-m-d'),
                                            'day' => $class['day'],
                                            'is_holiday' => $class['is_holiday'],
                                            'status' => $status,
                                        ];
                                    }

                                    // Actualizar los datos del formulario
                                    $set('schedule_data', $updatedScheduleData);

                                    // Si estamos editando, actualizamos las clases en la base de datos
                                    if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                        self::updateWorkshopClassesInDatabase($livewire->record, $updatedScheduleData);
                                    }
                                }),
                        ])
                            ->extraAttributes(['class' => 'flex justify-center mt-4']),

                        Forms\Components\Hidden::make('schedule_data')
                            ->default(function ($livewire) {
                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                    $classes = $livewire->record->workshopClasses()
                                        ->whereIn('status', ['scheduled', 'completed'])
                                        ->orderBy('class_date', 'asc')
                                        ->get();

                                    return $classes->map(function ($class, $index) use ($livewire) {
                                        return [
                                            'class_number' => $index + 1,
                                            'date' => \Carbon\Carbon::parse($class->class_date)->format('d/m/Y'),
                                            'raw_date' => $class->class_date,
                                            'day' => $livewire->record->day_of_week,
                                            'is_holiday' => false,
                                            'status' => $class->status,
                                        ];
                                    })->toArray();
                                }

                                return [];
                            })
                            ->dehydrated(true),

                        Forms\Components\Textarea::make('additional_comments')
                            ->label('Comentarios Adicionales')
                            ->helperText('Agrega comentarios sobre feriados, eventos especiales o cualquier información relevante para este taller')
                            ->rows(3)
                            ->maxLength(100)
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(1),
            ]);
    }

    private static function calculatePreviewPricing(Get $get, Set $set): void
    {
        // Este método actualiza la vista previa cuando cambian los valores
        // La lógica real está en generatePricingPreview
    }

    private static function generatePricingPreview(Get $get): string
    {
        $standardFee = $get('standard_monthly_fee');
        $surchargePercentage = $get('pricing_surcharge_percentage');
        $numberOfClasses = $get('number_of_classes') ?? 4;

        if (! $standardFee || $surchargePercentage === null || $surchargePercentage === '') {
            return '<p class="text-gray-500 italic">Ingresa la tarifa mensual y el porcentaje de recargo para ver la vista previa</p>';
        }

        $basePerClass = $standardFee / $numberOfClasses;
        $surchargeMultiplier = 1 + ($surchargePercentage / 100);

        // Generar tarifas para voluntarios
        $volunteerPricings = [];
        for ($i = 1; $i < $numberOfClasses; $i++) {
            // Calcular precio con recargo
            $priceWithSurcharge = round($basePerClass * $surchargeMultiplier * $i, 2);
            // Permitir que el precio supere la tarifa base
            $volunteerPricings[$i] = $priceWithSurcharge;
        }
        $volunteerPricings[$numberOfClasses] = $standardFee; // Tarifa completa sin recargo

        // Generar tarifas para no voluntarios
        $nonVolunteerPricings = [];
        for ($i = 1; $i < $numberOfClasses; $i++) {
            // Calcular precio con recargo
            $priceWithSurcharge = round($basePerClass * $surchargeMultiplier * $i, 2);
            // Permitir que el precio supere la tarifa base
            $nonVolunteerPricings[$i] = $priceWithSurcharge;
        }
        $nonVolunteerPricings[$numberOfClasses] = $standardFee; // Tarifa completa

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        // Tarifas para instructores voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-green-50">';
        $html .= '<h4 class="font-semibold text-green-800 mb-3">Instructores Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($volunteerPricings as $classes => $price) {
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} ".($classes === 1 ? 'clase' : 'clases').':</span>';
            $html .= "<span class='font-medium'>S/ ".number_format($price, 2).'</span>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        // Tarifas para instructores no voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-blue-50">';
        $html .= '<h4 class="font-semibold text-blue-800 mb-3">Instructores No Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($nonVolunteerPricings as $classes => $price) {
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} ".($classes === 1 ? 'clase' : 'clases').':</span>';
            $html .= "<span class='font-medium'>S/ ".number_format($price, 2).'</span>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('instructor.full_name')
                    ->label('Profesor')
                    ->searchable(['first_names', 'last_names']),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Día')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw('LOWER(day_of_week) LIKE LOWER(?)', ['%'.$search.'%']);
                    }),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora de Inicio')
                    ->time('H:i A'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora de Fin')
                    ->time('H:i A'),
                Tables\Columns\TextColumn::make('modality')
                    ->label('Modalidad'),
                Tables\Columns\TextColumn::make('monthlyPeriod')
                    ->label('Mes')
                    ->getStateUsing(function (Workshop $record) {
                        if (! $record->monthlyPeriod) {
                            return 'N/A';
                        }
                        $date = \Carbon\Carbon::create($record->monthlyPeriod->year, $record->monthlyPeriod->month, 1);

                        return $date->translatedFormat('F Y');
                    }),
                Tables\Columns\TextColumn::make('standard_monthly_fee')
                    ->label('Tarifa Mensual')
                    ->prefix('S/. '),
                Tables\Columns\TextColumn::make('current_period_enrollments')
                    ->label('Cupos Actuales')
                    ->getStateUsing(function (Workshop $record) {
                        $enrolled = $record->current_enrollments_count ?? 0;
                        $available = max(0, $record->capacity - $enrolled);

                        return "{$available}/{$record->capacity}";
                    })
                    ->badge()
                    ->color(function (Workshop $record) {
                        $enrolled = $record->current_enrollments_count ?? 0;
                        $available = max(0, $record->capacity - $enrolled);

                        if ($available <= 0) {
                            return 'danger';
                        } elseif ($available <= 3) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('monthly_period_id')
                    ->label('Mes')
                    ->options(function () {
                        $monthNames = [
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                        ];

                        return \App\Models\MonthlyPeriod::where('year', '>=', 2026)
                            ->where('year', '<=', now()->year + 2)
                            ->orderBy('year', 'asc')
                            ->orderBy('month', 'asc')
                            ->get()
                            ->mapWithKeys(fn ($p) => [
                                $p->id => ($monthNames[$p->month] ?? 'Mes '.$p->month).' '.$p->year,
                            ]);
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_pricings')
                    ->label('Ver Tarifas')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Workshop $record) => "Tarifas de {$record->name}")
                    ->modalContent(fn (Workshop $record) => view('filament.resources.workshop-resource.pricing-modal', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'view' => Pages\ViewWorkshop::route('/{record}'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['monthlyPeriod', 'instructor'])
            ->addSelect([
                'current_enrollments_count' => StudentEnrollment::selectRaw('COUNT(DISTINCT student_enrollments.student_id)')
                    ->join('instructor_workshops', 'student_enrollments.instructor_workshop_id', '=', 'instructor_workshops.id')
                    ->whereColumn('instructor_workshops.workshop_id', 'workshops.id')
                    ->whereColumn('student_enrollments.monthly_period_id', 'workshops.monthly_period_id')
                    ->whereIn('student_enrollments.payment_status', ['completed', 'pending']),
            ]);
    }

    public static function getBadgeCount(): int
    {
        return Workshop::count();
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getBadgeCount();
    }

    private static function calculateScheduleDates(Get $get, Set $set): void
    {
        $startDate = $get('temp_start_date');
        $daysOfWeek = $get('day_of_week');
        $numberOfClasses = (int) $get('number_of_classes');

        if (! $startDate || ! $daysOfWeek || ! $numberOfClasses || ! is_array($daysOfWeek)) {
            return;
        }

        if (empty($daysOfWeek)) {
            return;
        }

        $dias = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 0,
        ];

        $allHolidays = \App\Models\Holiday::where('affects_classes', true)->get();
        $exactHolidayDates = $allHolidays->where('is_recurring', false)
            ->keyBy(fn ($h) => $h->date->format('Y-m-d'));
        $recurringHolidays = $allHolidays->where('is_recurring', true)
            ->keyBy(fn ($h) => $h->date->format('m-d'));

        $dates = [];
        $start = \Carbon\Carbon::parse($startDate);

        // Ordenar los días de la semana seleccionados
        $targetDays = array_map(fn ($day) => $dias[$day], $daysOfWeek);
        sort($targetDays);

        // Ajustar al primer día válido
        $firstTargetDay = $targetDays[0];
        if ($start->dayOfWeek !== $firstTargetDay) {
            $start->next($firstTargetDay);
        }

        $current = $start->copy();
        $scheduledCount = 0;
        $maxDates = $numberOfClasses + 30;

        // Generar fechas hasta alcanzar N clases programadas (saltando feriados)
        while ($scheduledCount < $numberOfClasses && count($dates) < $maxDates) {
            foreach ($targetDays as $targetDay) {
                if ($scheduledCount >= $numberOfClasses || count($dates) >= $maxDates) {
                    break;
                }

                // Ajustar a este día de la semana si no estamos ya en él
                if ($current->dayOfWeek !== $targetDay) {
                    $current->next($targetDay);
                }

                $dateStr = $current->format('Y-m-d');
                $isHoliday = $exactHolidayDates->has($dateStr)
                    || $recurringHolidays->has($current->format('m-d'));
                $dayName = array_search($targetDay, $dias);

                $current->addDay();

                if ($isHoliday) {
                    continue;
                }

                $scheduledCount++;
                $dates[] = [
                    'class_number' => $scheduledCount,
                    'date' => \Carbon\Carbon::parse($dateStr)->format('d/m/Y'),
                    'raw_date' => $dateStr,
                    'day' => $dayName,
                    'is_holiday' => false,
                    'status' => 'scheduled',
                ];
            }
        }

        // Actualizar el campo schedule_data
        $set('schedule_data', $dates);
    }

    private static function updateWorkshopClassesInDatabase(Workshop $workshop, array $scheduleData): void
    {
        // 1. Obtener las clases existentes del taller directamente
        $workshopClasses = $workshop->workshopClasses()
            ->orderBy('class_date')
            ->get();

        // 2. Actualizar cada clase con la nueva fecha
        foreach ($scheduleData as $index => $classData) {
            if (isset($workshopClasses[$index])) {
                $workshopClass = $workshopClasses[$index];

                // 3. Actualizar la clase con los nuevos datos
                $workshopClass->update([
                    'class_date' => $classData['raw_date'],
                    'start_time' => $workshop->start_time,
                    'end_time' => $workshop->end_time,
                    'status' => $classData['status'],
                    'max_capacity' => $workshop->capacity,
                ]);
            } else {
                // 4. Crear nueva clase si no existe
                \App\Models\WorkshopClass::create([
                    'workshop_id' => $workshop->id,
                    'monthly_period_id' => $workshop->monthly_period_id,
                    'class_date' => $classData['raw_date'],
                    'start_time' => $workshop->start_time,
                    'end_time' => $workshop->end_time,
                    'status' => $classData['status'] ?? 'scheduled',
                    'max_capacity' => $workshop->capacity,
                ]);
            }
        }

        // 5. Eliminar clases sobrantes si hay menos clases en el nuevo horario
        if ($workshopClasses->count() > count($scheduleData)) {
            $classesToDelete = $workshopClasses->slice(count($scheduleData));
            foreach ($classesToDelete as $classToDelete) {
                $classToDelete->delete();
            }
        }
    }
}
