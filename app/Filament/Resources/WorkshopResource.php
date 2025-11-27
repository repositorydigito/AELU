<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
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
                            ->required(),
                        Forms\Components\TextInput::make('number_of_classes')
                            ->label('Número de clases')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(),
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
                                /* ->disabled(fn ($livewire) =>
                                        $livewire instanceof \Filament\Resources\Pages\EditRecord &&
                                        $livewire->record->hasEnrollments()
                                    ), */

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('calcular_horarios')
                                        ->label('Calcular Horarios')
                                        ->color('success')
                                        ->action(function (Get $get, Set $set) {
                                            self::calculateScheduleDates($get, $set);
                                        }),
                                    /* ->disabled(fn ($livewire) =>
                                            $livewire instanceof \Filament\Resources\Pages\EditRecord &&
                                            $livewire->record->hasEnrollments()
                                        ), */
                                ])->extraAttributes(['class' => 'flex items-end justify-end']),
                            ]),

                        Forms\Components\Placeholder::make('schedule_table')
                            ->label('Clases')
                            ->content(function (Get $get) {
                                return new \Illuminate\Support\HtmlString(self::generateScheduleTable($get));
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
                                                    ->default($class['status'] === 'cancelled')
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
                                // Al editar, cargar las clases existentes
                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                    $classes = $livewire->record->workshopClasses()
                                        ->orderBy('class_date', 'asc')
                                        ->get();

                                    return $classes->map(function ($class, $index) use ($livewire) {
                                        return [
                                            'class_number' => $index + 1,
                                            'date' => \Carbon\Carbon::parse($class->class_date)->format('d/m/Y'),
                                            'raw_date' => $class->class_date,
                                            'day' => $livewire->record->day_of_week,
                                            'is_holiday' => false,
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
            // Asegurar que nunca supere la tarifa completa
            $volunteerPricings[$i] = min($priceWithSurcharge, $standardFee);
        }
        $volunteerPricings[$numberOfClasses] = $standardFee; // Tarifa completa sin recargo

        // Solo agregar opción de 5 clases si el taller tiene 4 clases base
        if ($numberOfClasses == 4) {
            $volunteerPricings[5] = round($standardFee * 1.25, 2);
        }

        // Generar tarifas para no voluntarios
        $nonVolunteerPricings = [];
        for ($i = 1; $i < $numberOfClasses; $i++) {
            // Calcular precio con recargo
            $priceWithSurcharge = round($basePerClass * $surchargeMultiplier * $i, 2);
            // Asegurar que nunca supere la tarifa completa
            $nonVolunteerPricings[$i] = min($priceWithSurcharge, $standardFee);
        }
        $nonVolunteerPricings[$numberOfClasses] = $standardFee; // Tarifa completa

        // Solo agregar opción de 5 clases si el taller tiene 4 clases base
        if ($numberOfClasses == 4) {
            $nonVolunteerPricings[5] = $standardFee;
        }

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        // Tarifas para instructores voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-green-50">';
        $html .= '<h4 class="font-semibold text-green-800 mb-3">Instructores Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($volunteerPricings as $classes => $price) {
            $isDefault = $classes === $numberOfClasses;
            $badge = $isDefault ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">Estándar</span>' : '';
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} ".($classes === 1 ? 'clase' : 'clases').':</span>';
            $html .= "<span class='font-medium'>S/ ".number_format($price, 2).$badge.'</span>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        // Tarifas para instructores no voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-blue-50">';
        $html .= '<h4 class="font-semibold text-blue-800 mb-3">Instructores No Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($nonVolunteerPricings as $classes => $price) {
            $isDefault = $classes === $numberOfClasses;
            $badge = $isDefault ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">Estándar</span>' : '';
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} ".($classes === 1 ? 'clase' : 'clases').':</span>';
            $html .= "<span class='font-medium'>S/ ".number_format($price, 2).$badge.'</span>';
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instructor.full_name')
                    ->label('Profesor')
                    ->searchable(['first_names', 'last_names'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Día')
                    ->searchable(),
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('standard_monthly_fee')
                    ->label('Tarifa Mensual')
                    ->prefix('S/. ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_period_enrollments')
                    ->label('Cupos Actuales')
                    ->getStateUsing(function (Workshop $record) {
                        // Obtener el período actual
                        $currentPeriod = \App\Models\MonthlyPeriod::where('year', now()->year)
                            ->where('month', now()->month)
                            ->first();

                        if (! $currentPeriod) {
                            return 'N/A';
                        }

                        $capacityInfo = $record->getCapacityInfoForPeriod($record->monthly_period_id);

                        return "{$capacityInfo['available_spots']}/{$capacityInfo['total_capacity']}";
                    })
                    ->badge()
                    ->color(function (Workshop $record) {
                        $currentPeriod = \App\Models\MonthlyPeriod::where('year', now()->year)
                            ->where('month', now()->month)
                            ->first();

                        if (! $currentPeriod) {
                            return 'gray';
                        }

                        $capacityInfo = $record->getCapacityInfoForPeriod($record->monthly_period_id);

                        if ($capacityInfo['is_full']) {
                            return 'danger';
                        } elseif ($capacityInfo['is_almost_full']) {
                            return 'warning';
                        } else {
                            return 'success';
                        }
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Día de la semana')
                    ->options([
                        'Lunes' => 'Lunes',
                        'Martes' => 'Martes',
                        'Miércoles' => 'Miércoles',
                        'Jueves' => 'Jueves',
                        'Viernes' => 'Viernes',
                        'Sábado' => 'Sábado',
                        'Domingo' => 'Domingo',
                    ]),

                Tables\Filters\Filter::make('instructor_search')
                    ->form([
                        Forms\Components\TextInput::make('instructor_name')
                            ->label('Buscar profesor')
                            ->placeholder('Nombre del profesor...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['instructor_name'],
                            fn (Builder $query, $search): Builder => $query->whereHas(
                                'instructor',
                                fn (Builder $query): Builder => $query->where(function ($q) use ($search) {
                                    $q->where('first_names', 'like', "%{$search}%")
                                        ->orWhere('last_names', 'like', "%{$search}%")
                                        ->orWhereRaw("CONCAT(first_names, ' ', last_names) LIKE ?", ["%{$search}%"]);
                                })
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['instructor_name']) {
                            return null;
                        }

                        return 'Profesor: '.$data['instructor_name'];
                    }),

                Tables\Filters\SelectFilter::make('monthly_period')
                    ->label('Mes')
                    ->relationship('monthlyPeriod', 'id')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $date = \Carbon\Carbon::create($record->year, $record->month, 1);

                        return $date->translatedFormat('F Y');
                    })
                    ->searchable()
                    ->preload(),
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

        $dates = [];
        $start = \Carbon\Carbon::parse($startDate);

        // Ordenar los días de la semana seleccionados
        $targetDays = array_map(fn($day) => $dias[$day], $daysOfWeek);
        sort($targetDays);

        // Ajustar al primer día válido
        $firstTargetDay = $targetDays[0];
        if ($start->dayOfWeek !== $firstTargetDay) {
            $start->next($firstTargetDay);
        }

        $current = $start->copy();
        $classCount = 0;

        // Generar las fechas basándose en el número de clases
        while ($classCount < $numberOfClasses) {
            foreach ($targetDays as $targetDay) {
                if ($classCount >= $numberOfClasses) {
                    break;
                }

                // Ajustar a este día de la semana si no estamos ya en él
                if ($current->dayOfWeek !== $targetDay) {
                    $current->next($targetDay);
                }

                $dayName = array_search($targetDay, $dias);

                $dates[] = [
                    'class_number' => $classCount + 1,
                    'date' => $current->format('d/m/Y'),
                    'raw_date' => $current->format('Y-m-d'),
                    'day' => $dayName,
                    'is_holiday' => false,
                    'status' => 'scheduled',
                ];

                $classCount++;
                $current->addDay();
            }
        }

        // Actualizar el campo schedule_data
        $set('schedule_data', $dates);
    }

    private static function generateScheduleTable(Get $get): string
    {
        $scheduleData = $get('schedule_data') ?? [];
        $daysOfWeek = $get('day_of_week') ?? 'Lunes';
        $dayOfWeekDisplay = is_array($daysOfWeek) ? implode('/', $daysOfWeek) : $daysOfWeek;

        if (empty($scheduleData)) {
            return '<div class="text-gray-500 italic p-4">Configure la fecha de inicio para generar el horario automáticamente</div>';
        }

        $totalClasses = count($scheduleData);
        $totalColumns = 2 + $totalClasses; // Día + Nro. de Clases + todas las clases

        $html = '<div class="border rounded-lg overflow-hidden">';

        // Header de la tabla
        $html .= '<div class="bg-gray-50 border-b">';
        $html .= '<div class="grid gap-px" style="grid-template-columns: repeat('.$totalColumns.', minmax(0, 1fr));">';
        $html .= '<div class="p-3 font-semibold text-sm">Día</div>';
        $html .= '<div class="p-3 font-semibold text-sm">Nro. de Clases</div>';

        // Generar headers dinámicamente para todas las clases
        for ($i = 1; $i <= $totalClasses; $i++) {
            $html .= '<div class="p-3 font-semibold text-sm">Clase '.$i.'</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Fila de datos
        $html .= '<div class="bg-white">';
        $html .= '<div class="grid gap-px border-b" style="grid-template-columns: repeat('.$totalColumns.', minmax(0, 1fr));">';

        // Día
        $html .= '<div class="p-3 text-sm">'.$dayOfWeekDisplay.'</div>';

        // Número de clases (con botón de ajustes)
        $html .= '<div class="p-3 text-sm">';
        $html .= '<div class="flex items-center gap-2">';
        $html .= '<span class="text-blue-600 underline cursor-pointer">'.$totalClasses.' clases</span>';
        // $html .= '<button type="button" class="text-gray-400 hover:text-gray-600" onclick="openAdjustmentsModal()">';
        // $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Fechas de todas las clases
        foreach ($scheduleData as $class) {
            $isCancelled = isset($class['status']) && $class['status'] === 'cancelled';
            $classStyle = $isCancelled ? 'text-red-500 line-through' : '';
            $statusText = $isCancelled ? ' (Cancelada)' : '';

            // $html .= '<div class="p-3 text-sm">'.$class['date'].'</div>';
            $html .= '<div class="p-3 text-sm ' . $classStyle . '">' . $class['date'] . $statusText . '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Sin botones de acción - se usa el botón general "Guardar cambios"

        $html .= '</div>';

        // Modal de ajustes (oculto por defecto)
        $html .= self::generateAdjustmentsModal($scheduleData);

        // JavaScript para el modal
        $html .= '<script>
        function openAdjustmentsModal() {
            document.getElementById("adjustments-modal").classList.remove("hidden");
        }
        function closeAdjustmentsModal() {
            document.getElementById("adjustments-modal").classList.add("hidden");
        }
        </script>';

        return $html;
    }

    private static function generateAdjustmentsModal(array $scheduleData): string
    {
        $html = '<div id="adjustments-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">';
        $html .= '<div class="bg-white rounded-lg p-6 w-96 max-h-96 overflow-y-auto">';

        // Header del modal
        $html .= '<div class="flex justify-between items-center mb-4">';
        $html .= '<h3 class="text-lg font-semibold">Ajustes</h3>';
        $html .= '<button type="button" onclick="closeAdjustmentsModal()" class="text-gray-400 hover:text-gray-600">';
        $html .= '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';

        // Contenido del modal
        $html .= '<div class="space-y-4">';

        foreach ($scheduleData as $index => $class) {
            $html .= '<div>';
            $html .= '<label class="block text-sm font-medium text-gray-700 mb-1">Clase '.($index + 1).' *</label>';
            $html .= '<input type="date" value="'.$class['raw_date'].'" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Footer del modal
        $html .= '<div class="flex justify-end gap-2 mt-6">';
        $html .= '<button type="button" onclick="closeAdjustmentsModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-100">Cancelar</button>';
        $html .= '<button type="button" class="px-4 py-2 text-sm text-white bg-green-600 rounded hover:bg-green-700">Aplicar</button>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
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
                ]);
            }
        }
    }
}
