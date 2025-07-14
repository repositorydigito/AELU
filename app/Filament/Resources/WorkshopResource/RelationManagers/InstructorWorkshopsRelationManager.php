<?php

namespace App\Filament\Resources\WorkshopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use App\Models\Instructor;

class InstructorWorkshopsRelationManager extends RelationManager
{
    protected static string $relationship = 'instructorWorkshops';

    protected static ?string $title = 'Horarios del Taller';

    protected static ?string $label = 'Horario';

    protected static ?string $pluralLabel = 'Horarios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('instructor_id')
                    ->relationship('instructor', 'first_names')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}")
                    ->searchable(['first_names', 'last_names'])
                    ->preload()
                    ->label('Instructor')
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_of_week')
            ->columns([
                Tables\Columns\Layout\Stack::make([                   
                    Tables\Columns\Layout\Stack::make([                        
                        Tables\Columns\TextColumn::make('instructor.full_name')
                            ->label('Instructor')                            
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->sortable(query: function (Builder $query, string $direction): Builder {
                                return $query->orderBy(
                                    \Illuminate\Support\Facades\DB::raw("(SELECT CONCAT(last_names, ' ', first_names) FROM instructors WHERE instructors.id = instructor_workshops.instructor_id)"),
                                    $direction
                                );
                            }),                                                    
                        Tables\Columns\TextColumn::make('day_of_week')
                            ->label('Día')
                            ->size('sm')
                            ->weight(FontWeight::SemiBold),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Horario'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
