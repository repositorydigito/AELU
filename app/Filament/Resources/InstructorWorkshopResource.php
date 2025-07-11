<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorWorkshopResource\Pages;
use App\Filament\Resources\InstructorWorkshopResource\RelationManagers;
use App\Models\Workshop;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;

class InstructorWorkshopResource extends Resource
{
    protected static ?string $model = InstructorWorkshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Horarios'; 
    protected static ?string $pluralModelLabel = 'Horarios'; 
    protected static ?string $modelLabel = 'Horario';    
    protected static ?int $navigationSort = 6; 
    protected static ?string $navigationGroup = 'Talleres';    

    public static function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }
        
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('instructor_id')
                    ->relationship(
                        'instructor',
                        titleAttribute: 'last_names',
                        modifyQueryUsing: fn ($query) => $query->orderBy('first_names')->orderBy('last_names')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names} - {$record->instructor_code}")
                    ->label('Instructor') 
                    ->required(),                
                Forms\Components\Select::make('workshop_id')
                    ->relationship('workshop', 'name') 
                    ->label('Taller')
                    ->required(),
                Forms\Components\Select::make('day_of_week')
                    ->label('Día de la Semana')
                    ->options([
                        '1' => 'Lunes',
                        '2' => 'Martes',
                        '3' => 'Miércoles',
                        '4' => 'Jueves',
                        '5' => 'Viernes',
                        '6' => 'Sábado',
                        '0' => 'Domingo',
                    ])
                    ->required(),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de Inicio')
                            ->required()
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::calculateDurationHours($get, $set);
                            }),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Hora de Fin')
                            ->required()
                            ->seconds(false)
                            ->afterOrEqual('start_time')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::calculateDurationHours($get, $set);
                            }),
                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Duración (Horas)')
                            ->numeric()
                            ->step(0.25)
                            ->readOnly()
                            ->helperText('Se calcula automáticamente'),
                    ]),
                Forms\Components\TextInput::make('max_capacity')
                    ->label('Cupos máximos')
                    ->numeric()
                    ->minValue(1)
                    ->required(),                
                Forms\Components\TextInput::make('place')
                    ->label('Lugar')
                    ->required(),
                
                // Sección de configuración de pago
                Forms\Components\Section::make('Configuración de Pago')
                    ->schema([
                        Forms\Components\Select::make('payment_type')
                            ->label('Tipo de Pago')
                            ->options([
                                'volunteer' => 'Voluntario (% de ingresos)',
                                'hourly' => 'Por Horas (tarifa fija)',
                            ])
                            ->default('volunteer')
                            ->required()
                            ->live()
                            ->helperText('Define cómo se calculará el pago para este horario específico'),
                        
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Tarifa por Hora (S/)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('payment_type') === 'hourly')
                            ->required(fn (Get $get): bool => $get('payment_type') === 'hourly')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::updateEstimatedPay($get, $set);
                            })
                            ->helperText('Tarifa individual para este instructor en este taller'),
                        
                        Forms\Components\TextInput::make('custom_volunteer_percentage')
                            ->label('Porcentaje Personalizado (%)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn (Get $get): bool => $get('payment_type') === 'volunteer')
                            ->helperText('Opcional: Porcentaje personalizado (si se deja vacío, usará el porcentaje mensual predeterminado)')
                            ->suffix('%')
                            ->formatStateUsing(fn ($state) => $state ? $state * 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : null),
                        
                        Forms\Components\Placeholder::make('estimated_pay')
                            ->label('Pago Estimado (Por clase)')
                            ->visible(fn (Get $get): bool => $get('payment_type') === 'hourly')
                            ->content(function (Get $get) {
                                $hourlyRate = $get('hourly_rate');
                                $durationHours = $get('duration_hours');
                                
                                if ($hourlyRate && $durationHours) {
                                    $estimated = $hourlyRate * $durationHours;
                                    return 'S/ ' . number_format($estimated, 2);
                                }
                                
                                return 'S/ 0.00';
                            }),
                    ]),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\Select::make('initial_monthly_period_id')
                    ->label('Generar clases a partir del mes')
                    ->relationship('initialMonthlyPeriod', 'month')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->year . ' - ' . Carbon::create()->month($record->month)->monthName)
                    ->preload()
                    ->searchable()
                    ->nullable()
                    ->default(function () {
                        $nextMonth = Carbon::now()->addMonth();
                        $period = MonthlyPeriod::where('year', $nextMonth->year)
                                                ->where('month', $nextMonth->month)
                                                ->first();
                        return $period ? $period->id : null;
                    })
                    ->helperText('Selecciona el mes a partir del cual se generarán las clases para este horario. Si se deja en blanco, la generación automática no se activará para este horario al crearlo.'),
            ]);
    }

    private static function calculateDurationHours(Get $get, Set $set): void
    {
        $startTime = $get('start_time');
        $endTime = $get('end_time');
        
        if ($startTime && $endTime) {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
                    
            if ($end->greaterThan($start)) {
                    $durationMinutes = $start->diffInMinutes($end);
                    $durationHours = round($durationMinutes / 60, 2);
                    $set('duration_hours', $durationHours);
                }    
        }
    }
    
    private static function updateEstimatedPay(Get $get, Set $set): void
    {
        // Este método se puede usar para actualizar campos calculados en tiempo real
        // Ya se maneja en el Placeholder 'estimated_pay'
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Detalles del Horario')
                    ->schema([
                        Components\TextEntry::make('workshop.name')
                            ->label('Taller')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('instructor.full_name')
                            ->label('Instructor')
                            ->size(Components\TextEntry\TextEntrySize::Medium)
                            ->getStateUsing(fn (InstructorWorkshop $record) => "{$record->instructor->first_names} {$record->instructor->last_names}")
                            ->helperText(fn (InstructorWorkshop $record): string => $record->isVolunteer() ? 'Instructor Voluntario' : 'Instructor por Horas'),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('day_of_week')
                                    ->label('Día')
                                    ->formatStateUsing(fn (int $state): string => match ($state) {
                                        0 => 'Domingo',
                                        1 => 'Lunes',
                                        2 => 'Martes',
                                        3 => 'Miércoles',
                                        4 => 'Jueves',
                                        5 => 'Viernes',
                                        6 => 'Sábado',
                                    }),
                                Components\TextEntry::make('time_range')
                                    ->label('Hora')
                                    ->getStateUsing(fn (InstructorWorkshop $record) => 
                                        Carbon::parse($record->start_time)->format('H:i') . ' - ' . 
                                        Carbon::parse($record->end_time)->format('H:i') . 
                                        ' (' . $record->duration_hours . ' hrs)'
                                    ),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('max_capacity')
                                    ->label('Capacidad Máx.'),
                                Components\TextEntry::make('current_enrollments_count')
                                    ->label('Alumnos Inscritos')
                                    ->default(0),
                            ]),
                        Components\TextEntry::make('place')
                            ->label('Lugar')
                            ->placeholder('No especificado'),
                    ])
                    ->columns(1),
                    
                Components\Section::make('Configuración de Pago')
                    ->schema([
                        Components\TextEntry::make('payment_type')
                            ->label('Tipo de Pago')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'volunteer' => 'Voluntario (% de ingresos)',
                                'hourly' => 'Por Horas (tarifa fija)',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'volunteer' => 'success',
                                'hourly' => 'info',
                            }),
                        Components\TextEntry::make('payment_details')
                            ->label('Detalles de Pago')
                            ->getStateUsing(function (InstructorWorkshop $record) {
                                if ($record->isVolunteer()) {
                                    $percentage = $record->custom_volunteer_percentage 
                                        ? number_format($record->custom_volunteer_percentage * 100, 2) . '% (personalizado)'
                                        : 'Porcentaje mensual predeterminado';
                                    return $percentage;
                                } else {
                                    $estimated = $record->hourly_rate * $record->duration_hours;
                                    return 'S/ ' . number_format($record->hourly_rate, 2) . '/hora → S/ ' . number_format($estimated, 2) . '/clase';
                                }
                            }),                        
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('workshop.name')
                                ->label('Taller')
                                ->size('lg')
                                ->weight(FontWeight::Bold)
                                ->searchable()
                                ->sortable()
                                ->columnSpan(1),                            
                        ]),

                    Tables\Columns\TextColumn::make('instructor_info')
                        ->label('Instructor')
                        ->size('sm')
                        ->weight(FontWeight::SemiBold)
                        ->searchable(query: function (Builder $query, string $search) {
                            return $query->whereHas('instructor', function ($query) use ($search) {
                                $query->whereRaw("CONCAT(first_names, ' ', last_names, ' - ', instructor_code) LIKE ?", ["%{$search}%"]);
                            });
                        })
                        ->getStateUsing(function (InstructorWorkshop $record) {
                            $paymentTypeBadge = match($record->payment_type) {
                                'volunteer' => 'Voluntario',
                                'hourly' => 'Por Horas',
                                default => 'No definido'
                            };
                            
                            return "{$record->instructor->first_names} {$record->instructor->last_names} ({$paymentTypeBadge})";
                        }),                    

                    Tables\Columns\TextColumn::make('day_of_week')
                        ->label('Día')
                        ->formatStateUsing(fn (int $state): string => match ($state) {
                            0 => 'Domingo',
                            1 => 'Lunes',
                            2 => 'Martes',
                            3 => 'Miércoles',
                            4 => 'Jueves',
                            5 => 'Viernes',
                            6 => 'Sábado',
                        }),

                    Tables\Columns\TextColumn::make('time_range')
                        ->label('Hora')
                        ->getStateUsing(fn (InstructorWorkshop $record) =>
                            \Carbon\Carbon::parse($record->start_time)->format('h:i a') . ' - ' .
                            \Carbon\Carbon::parse($record->end_time)->format('h:i a')
                        ),

                    Tables\Columns\TextColumn::make('place')
                        ->label('Lugar')
                        ->searchable()
                        ->icon('heroicon-o-map-pin')
                        ->placeholder('No especificado'), 

                    Tables\Columns\TextColumn::make('class_rate')
                        ->label('Costo por Clase')                        
                        ->getStateUsing(fn (InstructorWorkshop $record) => number_format($record->workshop->standard_monthly_fee, 2))
                        ->money('PEN')
                        ->color('success')
                        ->tooltip('Cálculo simplificado: Tarifa mensual estándar / 4 clases.'),

                    /* Tables\Columns\ToggleColumn::make('is_active') 
                        ->label('Estado Activo'), */

                ])->space(2), 
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('workshop_id')
                    ->relationship('workshop', 'name')
                    ->label('Filtrar por Taller')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('instructor_id')
                    ->relationship('instructor', 'first_names') // Puedes usar first_names para la selección
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}") // Mostrar nombre completo en el filtro
                    ->label('Filtrar por Instructor')
                    ->preload()
                    ->searchable(),                
            ])
            ->actions([
                Tables\Actions\Action::make('inscribe')
                    ->label('Inscribir Alumno')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    // Asume que tendrás una página o acción personalizada 'inscribe-student'
                    ->url(fn (InstructorWorkshop $record): string => InstructorWorkshopResource::getUrl('inscribe-student', ['record' => $record])),                
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(), // Descomenta si necesitas eliminar directamente desde la tarjeta
            ])
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
            ])
            ->paginated([
                12,
                24,
                48,
                'all',
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->bulkActions([
                /* Tables\Actions\BulkAction::make('goToBulkInscribeStudent')
                    ->label('Inscribir alumno en múltiples horarios')
                    ->icon('heroicon-o-user-plus')
                    ->action(function ($records) {
                        $ids = $records->pluck('id')->toArray();
                        $url = \App\Filament\Resources\InstructorWorkshopResource::getUrl('bulk-inscribe-students', [
                            'workshops' => implode(',', $ids),
                        ], true); // Forzar el prefijo /page/
                        return redirect($url);
                    })
                    ->deselectRecordsAfterCompletion(false), */
            ])
            // Si necesitas precargar relaciones para evitar N+1 en la vista de tarjetas
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['workshop', 'instructor', 'enrollments']))
            ->defaultSort('workshop.name', 'asc');
    }      

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorWorkshops::route('/'),
            'create' => Pages\CreateInstructorWorkshop::route('/create'),
            'edit' => Pages\EditInstructorWorkshop::route('/{record}/edit'),
            'view' => Pages\ViewInstructorWorkshop::route('/{record}'),
            'inscribe-student' => Pages\InscribeStudent::route('/{record}/inscribe-student'),
            'bulk-inscribe-students' => Pages\BulkInscribeStudents::route('/page/bulk-inscribe-students'),
        ];
    }
}
