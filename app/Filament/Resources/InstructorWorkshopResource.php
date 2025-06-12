<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorWorkshopResource\Pages;
use App\Filament\Resources\InstructorWorkshopResource\RelationManagers;
use App\Models\Workshop;
use App\Models\InstructorWorkshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;

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
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}")
                    //->searchable(['first_names', 'last_names'])
                    ->label('Instructor') 
                    ->required(),                
                Forms\Components\Select::make('workshop_id')
                    ->relationship('workshop', 'name') 
                    ->label('Taller')
                    ->required(),
                Forms\Components\Select::make('day_of_week')
                    ->label('Día de la Semana')
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
                Forms\Components\TimePicker::make('start_time')
                    ->label('Hora de Inicio')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de Fin')
                    ->required()
                    ->seconds(false),
                Forms\Components\TextInput::make('class_count')
                    ->label('Cantidad de Clases')
                    ->numeric()
                    ->nullable(),
                Forms\Components\TextInput::make('class_rate')
                    ->label('Costo por Clase')
                    ->numeric()
                    ->prefix('S/.')
                    ->nullable(),
                Forms\Components\TextInput::make('place')
                    ->label('Lugar')
                    ->maxLength(255)
                    ->required(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('workshop.name')
                    ->label('Nombre del Taller'),                    
                TextEntry::make('instructor.full_name')
                    ->label('Nombre del Instructor'),                                   
                TextEntry::make('day_of_week')
                    ->label('Día de la Semana'),
                TextEntry::make('time_range')
                    ->label('Hora'),                  
                TextEntry::make('place')
                    ->label('Lugar'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([                   
                    Tables\Columns\Layout\Stack::make([                        
                        Tables\Columns\TextColumn::make('workshop.name')
                            ->label('Taller')                            
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->sortable(),                                                    
                        Tables\Columns\TextColumn::make('instructor.full_name')
                            ->label('Instructor')                            
                            ->size('sm')
                            ->weight(FontWeight::SemiBold)
                            ->sortable(query: function (Builder $query, string $direction): Builder {
                                return $query->orderBy(
                                    \Illuminate\Support\Facades\DB::raw("(SELECT CONCAT(last_names, ' ', first_names) FROM instructors WHERE instructors.id = instructor_workshops.instructor_id)"),
                                    $direction
                                );
                            }),                                                    
                        Tables\Columns\TextColumn::make('day_of_week')
                            ->label('Día'),
                        Tables\Columns\TextColumn::make('time_range') 
                            ->label('Hora'),                                                                           
                        Tables\Columns\TextColumn::make('place')
                            ->label('Lugar')
                            ->icon('heroicon-o-map-pin'),                        
                        Tables\Columns\TextColumn::make('class_rate')
                            ->label('Costo por Clase')
                            ->money('PEN')
                            ->sortable()                             
                            ->color('success'),
                    ]),                                        
                ])->space(0), 
            ])
            ->filters([                
                Tables\Filters\SelectFilter::make('workshop_id')
                    ->relationship('workshop', 'name')
                    ->label('Filtrar por Taller'),
                Tables\Filters\SelectFilter::make('instructor_id')
                    ->relationship('instructor', 'last_names')
                    ->label('Filtrar por Instructor'),
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options([
                        'Lunes' => 'Lunes',
                        'Martes' => 'Martes',
                        'Miércoles' => 'Miércoles',
                        'Jueves' => 'Jueves',
                        'Viernes' => 'Viernes',
                        'Sábado' => 'Sábado',
                        'Domingo' => 'Domingo',
                    ])
                    ->label('Filtrar por Día'),
            ])
            ->contentGrid([
                'default' => 1, // Una columna en pantallas pequeñas
                'sm' => 2,      // Dos columnas en pantallas medianas
                'md' => 3,      // Tres columnas en pantallas de escritorio
                'lg' => 4,      // Cuatro columnas en pantallas grandes
            ])
            ->paginated([
                12, 
                24,
                48,
                'all',
            ])
            ->actions([
                
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
            'index' => Pages\ListInstructorWorkshops::route('/'),
            'create' => Pages\CreateInstructorWorkshop::route('/create'),
            'edit' => Pages\EditInstructorWorkshop::route('/{record}/edit'),
            'view' => Pages\ViewInstructorWorkshop::route('/{record}'),
        ];
    }
}
