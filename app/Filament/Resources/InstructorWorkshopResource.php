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
                Forms\Components\TimePicker::make('start_time')
                    ->label('Hora de Inicio')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de Fin')
                    ->required()
                    ->seconds(false)
                    ->afterOrEqual('start_time'),
                Forms\Components\TextInput::make('max_capacity')
                    ->label('Cupos máximos')
                    ->numeric()
                    ->minValue(1)
                    ->required(),                
                Forms\Components\TextInput::make('place')
                    ->label('Lugar')
                    ->required(),
                Forms\Components\Toggle::make('is_volunteer')
                    ->label('Es Voluntario (para este horario)')
                    ->helperText('Indica si el instructor es voluntario para este horario específico del taller.'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\Select::make('initial_monthly_period_id')
                    ->label('Generar clases a partir del mes')
                    ->relationship('initialMonthlyPeriod', 'month') // Puedes mostrar el mes numérico
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->year . ' - ' . Carbon::create()->month($record->month)->monthName) // Muestra "Año - Nombre del Mes"
                    ->preload()
                    ->searchable()
                    ->nullable() // Permite que sea nulo si no se quiere generar automáticamente de inmediato
                    ->default(function () { // Sugerir el próximo mes por defecto
                        $nextMonth = Carbon::now()->addMonth();
                        $period = MonthlyPeriod::where('year', $nextMonth->year)
                                                ->where('month', $nextMonth->month)
                                                ->first();
                        return $period ? $period->id : null;
                    })
                    ->helperText('Selecciona el mes a partir del cual se generarán las clases para este horario. Si se deja en blanco, la generación automática no se activará para este horario al crearlo.'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Detalles del Horario')
                    ->schema([
                        Components\TextEntry::make('workshop.name') // Muestra el nombre del taller
                            ->label('Taller')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('instructor.full_name') // Asume un accesor 'full_name' en Instructor
                            ->label('Instructor')
                            ->size(Components\TextEntry\TextEntrySize::Medium)
                            ->helperText(fn (InstructorWorkshop $record): string => $record->is_volunteer ? 'Instructor Voluntario' : 'Instructor por Horas'), // Información extra
                        Components\Grid::make(2) // Usa una cuadrícula para alinear los siguientes campos
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
                                Components\TextEntry::make('time_range') // Accesor personalizado para mostrar "HH:MM - HH:MM"
                                    ->label('Hora')
                                    ->getStateUsing(fn (InstructorWorkshop $record) => Carbon::parse($record->start_time)->format('H:i') . ' - ' . Carbon::parse($record->end_time)->format('H:i')),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('max_capacity')
                                    ->label('Capacidad Máx.'),
                                Components\TextEntry::make('current_enrollments_count') // Aquí podrías tener un `withCount` o un accesor para mostrar los alumnos inscritos
                                    ->label('Alumnos Inscritos')
                                    ->default(0), // Valor por defecto si no hay inscripciones
                            ]),
                        Components\TextEntry::make('place')
                            ->label('Lugar')
                            ->placeholder('No especificado'), // Si el lugar es null
                        Components\IconEntry::make('is_active')
                            ->label('Estado')
                            ->boolean(),
                    ])->columns(1), // Puedes ajustar las columnas del Section si quieres un diseño más compacto en cada tarjeta
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

                            Tables\Columns\TextColumn::make('capacity_info') // Nombre del campo para la info de cupos
                                ->label('Cupos (Inscritos/Máx.)')
                                ->getStateUsing(function (InstructorWorkshop $record) {
                                    // Asumo que tienes una relación 'enrollments' o similar para contar
                                    // Es importante cargar esta relación si la vas a usar aquí para evitar N+1
                                    $enrolled = $record->enrollments->count(); // Usar la colección si ya está cargada
                                    $max = $record->max_capacity;
                                    return "{$enrolled}/{$max}";
                                })
                                ->icon('heroicon-o-user-group')
                                ->size('xl')
                                ->weight(FontWeight::Bold) // Usar FontWeight
                                ->alignEnd() // Alinea al final del contenedor de la columna
                                ->columnSpan(1),
                        ]),

                    Tables\Columns\TextColumn::make('instructor.full_name_with_code') // Accesor para nombre completo + código
                        ->label('Instructor')
                        ->size('sm')
                        ->weight(FontWeight::SemiBold)
                        ->searchable(query: function (Builder $query, string $search) {
                            // Búsqueda personalizada que considera nombre, apellido y código del instructor
                            return $query->whereHas('instructor', function ($query) use ($search) {
                                $query->whereRaw("CONCAT(first_names, ' ', last_names, ' - ', instructor_code) LIKE ?", ["%{$search}%"]);
                            });
                        })
                        ->getStateUsing(fn (InstructorWorkshop $record) => "{$record->instructor->first_names} {$record->instructor->last_names} - {$record->instructor->instructor_code}"),

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
                        ->placeholder('No especificado'), // Mostrar esto si el lugar es nulo

                    Tables\Columns\TextColumn::make('class_rate')
                        ->label('Costo por Clase')
                        // Esto es un ejemplo. La lógica de 'Costo por Clase'
                        // debería venir de tu tabla 'WorkshopPricings' y ser más compleja
                        // para manejar diferentes números de clases.
                        // Por ahora, un ejemplo simplificado:
                        ->getStateUsing(fn (InstructorWorkshop $record) => number_format($record->workshop->standard_monthly_fee, 2))
                        ->money('PEN')
                        ->color('success')
                        ->tooltip('Cálculo simplificado: Tarifa mensual estándar / 4 clases.'),

                    Tables\Columns\ToggleColumn::make('is_active') // Mostrar si está activo o inactivo
                        ->label('Estado Activo'),

                ])->space(2), // Espacio entre los elementos dentro de cada tarjeta
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
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options([
                        0 => 'Domingo',
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                    ])
                    ->label('Filtrar por Día'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado del Horario')
                    ->boolean()
                    ->trueLabel('Activo')
                    ->falseLabel('Inactivo')
                    ->nullable(),
                Tables\Filters\TernaryFilter::make('is_volunteer')
                    ->label('Tipo de Instructor')
                    ->boolean()
                    ->trueLabel('Voluntario')
                    ->falseLabel('Por Horas')
                    ->nullable(),
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
                Tables\Actions\BulkAction::make('goToBulkInscribeStudent')
                    ->label('Inscribir alumno en múltiples horarios')
                    ->icon('heroicon-o-user-plus')
                    ->action(function ($records) {
                        $ids = $records->pluck('id')->toArray();
                        $url = \App\Filament\Resources\InstructorWorkshopResource::getUrl('bulk-inscribe-students', [
                            'workshops' => implode(',', $ids),
                        ], true); // Forzar el prefijo /page/
                        return redirect($url);
                    })
                    ->deselectRecordsAfterCompletion(false),
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
