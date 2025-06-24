<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
use App\Filament\Resources\WorkshopResource\RelationManagers;
use App\Models\Workshop;
use App\Models\Instructor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Get; 
use Filament\Forms\Set;
use Filament\Forms\Components\Hidden; 
use Carbon\Carbon;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Talleres'; 
    protected static ?string $pluralModelLabel = 'Talleres'; 
    protected static ?string $modelLabel = 'Taller';    
    protected static ?int $navigationSort = 6; 
    protected static ?string $navigationGroup = 'Talleres';
    const DEFAULT_BASE_CLASS_COUNT = 4; 
   
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Taller')
                    ->columns(3) 
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Taller')
                            ->required()
                            ->maxLength(255),

                        Select::make('instructor_id')
                            ->label('Profesor')
                            ->relationship('instructor', 'first_names') 
                            ->options(Instructor::all()->pluck('full_name', 'id')) 
                            ->searchable()
                            ->preload() 
                            ->required(),
                        
                        Select::make('weekday')
                            ->label('Día del Taller')
                            ->options([
                                'Lunes' => 'Lunes',
                                'Martes' => 'Martes',
                                'Miércoles' => 'Miércoles',
                                'Jueves' => 'Jueves',
                                'Viernes' => 'Viernes',
                                'Sábado' => 'Sábado',
                                'Domingo' => 'Domingo',
                            ])
                            ->required(),

                        TimePicker::make('start_time')
                            ->label('Hora de Inicio')
                            ->required()
                            ->seconds(false) 
                            ->displayFormat('H:i'), 

                        TimePicker::make('end_time')
                            ->label('Hora de Fin')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('H:i')
                            ->afterOrEqual('start_time'),

                        TextInput::make('max_students')
                            ->label('Número de Cupos')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1),                       

                        TextInput::make('place')
                            ->label('Lugar')
                            ->nullable() 
                            ->maxLength(255),
                    ]),
                
                Section::make('Tarifario del Taller')
                    ->columns(3) 
                    ->schema([
                        TextInput::make('monthly_fee')
                            ->label('Tarifa del Mes (Base)')
                            ->numeric()
                            ->prefix('S/.')
                            ->required()
                            ->minValue(0)
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateFinalFee($get, $set);
                            })
                            ->columnSpan(1),
 
                        TextInput::make('class_count')
                            ->label('Cantidad de Clases')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->hint('El máximo de clases debe ser 5')
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateFinalFee($get, $set);
                                self::calculateClassDatesAndEndDate($get, $set);
                            })
                            ->columnSpan(1),
 
                        TextInput::make('surcharge_percentage')
                            ->label('Porcentaje de Recargo (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(20.00)
                            ->readOnly()
                            ->minValue(0)
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateFinalFee($get, $set);
                            })
                            ->columnSpan(1),
 
                        TextInput::make('calculated_equivalent_fee')
                            ->label('Tarifa Equivalente')
                            ->prefix('S/.')
                            ->numeric()
                            ->readOnly() 
                            ->disabled() 
                            ->live()
                            ->columnSpan(1),
 
                        TextInput::make('calculated_surcharge_amount')
                            ->label('Recargo (%)')
                            ->prefix('S/.')
                            ->numeric()
                            ->readOnly()
                            ->disabled()
                            ->live()
                            ->columnSpan(1),
 
                        /* TextInput::make('final_monthly_fee') 
                            ->label('Tarifa Final')
                            ->prefix('S/.')
                            ->numeric()
                            ->readOnly() 
                            ->disabled()
                            ->required()
                            ->columnSpan(1), */
                        
                        Hidden::make('final_monthly_fee')
                            ->dehydrateStateUsing(fn ($state) => $state), 

                        // Nuevo: Placeholder para mostrar el valor al usuario
                        Forms\Components\Placeholder::make('final_monthly_fee_display') 
                            ->label('Tarifa Final') 
                            ->content(fn (Get $get) => 'S/. ' . number_format((float) $get('final_monthly_fee'), 2)) 
                            ->live() 
                            ->columnSpan(1),

                     ]),

                Section::make('Horario del Taller')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateClassDatesAndEndDate($get, $set);
                            }),
                        
                        Hidden::make('end_date')
                            ->dehydrateStateUsing(fn ($state) => $state instanceof Carbon ? $state->toDateString() : $state),

                        Forms\Components\Placeholder::make('end_date_display')
                            ->label('Fecha de Culminación')
                            ->content(fn (Get $get) => $get('end_date') ? Carbon::parse($get('end_date'))->format('d/m/Y') : null)
                            ->live(),

                        /* TextInput::make('end_date')
                            ->label('Fecha de Culminación')
                            ->readOnly()
                            ->disabled()
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn ($state) => $state instanceof Carbon ? $state->toDateString() : $state)
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Carbon::parse($state)->format('d/m/Y') : null), */
                        
                        Forms\Components\Fieldset::make('Clases Calculadas')
                            ->columns(5) 
                            ->schema([
                                Forms\Components\Placeholder::make('class_1_date_display')
                                    ->label('Clase 1')
                                    ->content(fn (Get $get) => $get('class_1_date') ? Carbon::parse($get('class_1_date'))->format('d/m/Y') : 'N/A'),
                                Forms\Components\Placeholder::make('class_2_date_display')
                                    ->label('Clase 2')
                                    ->content(fn (Get $get) => $get('class_2_date') ? Carbon::parse($get('class_2_date'))->format('d/m/Y') : 'N/A')
                                    ->hidden(fn (Get $get) => (int) $get('class_count') < 2),
                                Forms\Components\Placeholder::make('class_3_date_display')
                                    ->label('Clase 3')
                                    ->content(fn (Get $get) => $get('class_3_date') ? Carbon::parse($get('class_3_date'))->format('d/m/Y') : 'N/A')
                                    ->hidden(fn (Get $get) => (int) $get('class_count') < 3),
                                Forms\Components\Placeholder::make('class_4_date_display')
                                    ->label('Clase 4')
                                    ->content(fn (Get $get) => $get('class_4_date') ? Carbon::parse($get('class_4_date'))->format('d/m/Y') : 'N/A')
                                    ->hidden(fn (Get $get) => (int) $get('class_count') < 4),
                                Forms\Components\Placeholder::make('class_5_date_display')
                                    ->label('Clase 5')
                                    ->content(fn (Get $get) => $get('class_5_date') ? Carbon::parse($get('class_5_date'))->format('d/m/Y') : 'N/A')
                                    ->hidden(fn (Get $get) => (int) $get('class_count') < 5),
                            ]),                          
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([                
                TextColumn::make('name')
                    ->label('Nombre del Taller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instructor.full_name')
                    ->label('Profesor')
                    ->searchable(query: function ($query, $search) {
                        $query->orWhereHas('instructor', function ($q) use ($search) {
                            $q->where('first_names', 'like', "%{$search}%")
                            ->orWhere('last_names', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                TextColumn::make('place')
                    ->label('Lugar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('weekday')
                    ->label('Día')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Inicio')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Fin')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('max_students')
                    ->label('Cupos')
                    ->sortable(),
                TextColumn::make('class_count')
                    ->label('Nro. Clases')
                    ->sortable(),
                TextColumn::make('monthly_fee')
                    ->label('Tarifa Mensual')
                    ->money('PEN')
                    ->sortable(),                
            ])
            ->filters([
                // 
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                
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
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }    
    
    public static function calculateFinalFee(Get $get, Set $set): void
    {
        $monthlyFee = (float) $get('monthly_fee');
        $classCount = (int) $get('class_count');
        $surchargePercentage = (float) $get('surcharge_percentage');
        $existingFinalMonthlyFee = (float) ($get('final_monthly_fee') ?? 0);

        if ($monthlyFee <= 0 || $classCount <= 0 || $surchargePercentage < 0) {
            $set('calculated_equivalent_fee', 0);
            $set('calculated_surcharge_amount', 0);
            $set('final_monthly_fee', $existingFinalMonthlyFee > 0 ? $existingFinalMonthlyFee : 0);
            //$set('final_monthly_fee', 0);
            return;
        }

        $baseClassCount = self::DEFAULT_BASE_CLASS_COUNT;

        $equivalentFee = 0;
        $surchargeAmount = 0;
        $finalFee = 0;

        if ($classCount === $baseClassCount) {
            $finalFee = $monthlyFee;
            $equivalentFee = $monthlyFee;
            $surchargeAmount = 0;
        } elseif ($classCount === ($baseClassCount + 1)) {
            $costPerBaseClass = $monthlyFee / $baseClassCount;
            $equivalentFee = $monthlyFee + $costPerBaseClass;
            $surchargeAmount = 0;
            $finalFee = $equivalentFee;
        } else {
            $costPerBaseClass = $monthlyFee / $baseClassCount;
            $equivalentFee = $costPerBaseClass * $classCount;

            $surchargeFactor = $surchargePercentage / 100;
            $surchargeAmount = $surchargeFactor * $equivalentFee;

            $finalFee = $equivalentFee + $surchargeAmount;
        }

        $set('calculated_equivalent_fee', round($equivalentFee, 2));
        $set('calculated_surcharge_amount', round($surchargeAmount, 2));
        $set('final_monthly_fee', round($finalFee, 2));
    }    

    public static function calculateClassDatesAndEndDate(Get $get, Set $set): void
    {
        $startDate = $get('start_date');
        $weekdayName = $get('weekday');
        $classCount = (int) $get('class_count');

        if (!$startDate || !$weekdayName || $classCount <= 0) {
            // Limpiar los campos si no hay datos suficientes
            $set('end_date', null);
            for ($i = 1; $i <= 5; $i++) { // Ajusta el límite si esperas más de 5 clases
                $set("class_{$i}_date", null);
            }
            return;
        }

        // Crear una instancia temporal de Workshop para usar el método getWeekdayNumber
        // Podríamos pasar el método como una closure o refactorizar si se usa mucho
        $tempWorkshop = new Workshop();
        $targetWeekday = $tempWorkshop->getWeekdayNumber($weekdayName);

        $currentDate = Carbon::parse($startDate);
        $classDates = [];
        $finalEndDate = null;

        for ($i = 0; $i < $classCount; $i++) {
            // Si la fecha actual no es el día de la semana objetivo, avanzar
            while ($currentDate->dayOfWeek !== $targetWeekday) {
                $currentDate->addDay();
            }

            // Asegurarse de que no nos saltemos el día de inicio si ya es el día correcto
            if ($i > 0 && $currentDate->toDateString() == Carbon::parse($startDate)->toDateString()) {
                 $currentDate->addWeek(); // Si es la segunda iteración y caemos en el mismo día, avanzar una semana
                 while ($currentDate->dayOfWeek !== $targetWeekday) {
                    $currentDate->addDay();
                }
            }


            $classDates[] = $currentDate->copy(); // Añadir una copia para no modificar la original
            $finalEndDate = $currentDate->copy(); // La última fecha es la de culminación

            // Establecer la fecha en el campo visual correspondiente
            if ($i < 5) { // Solo si tenemos un campo 'class_X_date' definido
                $set("class_". ($i + 1) . "_date", $currentDate->toDateString());
            }

            $currentDate->addWeek(); // Avanzar a la misma fecha en la siguiente semana para la próxima clase
        }

        // Limpiar campos de clase si class_count es menor que el número máximo de campos visuales
        for ($i = $classCount; $i < 5; $i++) {
            $set("class_". ($i + 1) . "_date", null);
        }

        $set('end_date', $finalEndDate ? $finalEndDate->toDateString() : null);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->recalculateAndSetFinalFee($data);
        $this->recalculateAndSetEndDate($data);
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->recalculateAndSetFinalFee($data);
        $this->recalculateAndSetEndDate($data);
        return $data;
    }
    
    private function recalculateAndSetFinalFee(array &$data): void
    {
        $monthlyFee = (float) $data['monthly_fee'];
        $classCount = (int) $data['class_count'];
        $surchargePercentage = (float) $data['surcharge_percentage'];

        if ($monthlyFee <= 0 || $classCount <= 0 || $surchargePercentage < 0) {
            $data['final_monthly_fee'] = 0;
            return;
        }

        $baseClassCount = self::DEFAULT_BASE_CLASS_COUNT;
        $finalFee = 0;

        if ($classCount === $baseClassCount) {
            $finalFee = $monthlyFee;
        } elseif ($classCount === ($baseClassCount + 1)) {
            $costPerBaseClass = $monthlyFee / $baseClassCount;
            $finalFee = $monthlyFee + $costPerBaseClass;
        } else {
            $costPerBaseClass = $monthlyFee / $baseClassCount;
            $equivalentFee = $costPerBaseClass * $classCount;
            $surchargeFactor = $surchargePercentage / 100;
            $surchargeAmount = $surchargeFactor * $equivalentFee;
            $finalFee = $equivalentFee + $surchargeAmount;
        }

        $data['final_monthly_fee'] = round($finalFee, 2);
    }
    
    private function recalculateAndSetEndDate(array &$data): void
    {
        $startDate = $data['start_date'];
        $weekdayName = $data['weekday'];
        $classCount = (int) $data['class_count'];

        if (!$startDate || !$weekdayName || $classCount <= 0) {
            $data['end_date'] = null;
            return;
        }

        $tempWorkshop = new Workshop();
        $targetWeekday = $tempWorkshop->getWeekdayNumber($weekdayName);

        $currentDate = Carbon::parse($startDate);
        $finalEndDate = null;

        for ($i = 0; $i < $classCount; $i++) {
            while ($currentDate->dayOfWeek !== $targetWeekday) {
                $currentDate->addDay();
            }

            if ($i > 0 && $currentDate->toDateString() == Carbon::parse($startDate)->toDateString()) {
                 $currentDate->addWeek();
                 while ($currentDate->dayOfWeek !== $targetWeekday) {
                    $currentDate->addDay();
                }
            }

            $finalEndDate = $currentDate->copy();
            $currentDate->addWeek();
        }

        $data['end_date'] = $finalEndDate ? $finalEndDate->toDateString() : null;
    }
    
    public static function afterSave(Forms\ComponentContainer $form): void
    {
        $workshop = $form->model; 
        if ($workshop instanceof Workshop) {
            $workshop->generateWorkshopClasses();
        }
    }
    
    public static function getForms(): array
    {
        return [
            Forms\Components\Group::make()->schema(static::form(new Form($this->getLivewire()))->getSchema())
                ->afterStateDehydrated(function ($state, Forms\ComponentContainer $form) {
                    $get = new Get(fn (string $path) => $form->getRawState()[$path] ?? null);
                    $set = new Set(fn (string $path, $value) => $form->fill([$path => $value]));
                    self::calculateClassDatesAndEndDate($get, $set);
                    self::calculateFinalFee($get, $set);
                }),
        ];
    }   

     
}
