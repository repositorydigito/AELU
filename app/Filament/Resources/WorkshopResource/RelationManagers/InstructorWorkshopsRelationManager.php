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

    protected static ?string $title = 'Instructores Asignados';

    protected static ?string $label = 'Instructor';

    protected static ?string $pluralLabel = 'Instructores';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
    ->label('Fecha de Inicio')
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
Forms\Components\TextInput::make('number_of_classes')
    ->label('Nro. de Clases')
    ->numeric()
    ->required(),
Forms\Components\Actions::make([
    Forms\Components\Actions\Action::make('calcular_horarios')
        ->label('Calcular Horarios')
        ->action(function ($state, $livewire) {
            // Lógica para calcular fechas automáticamente
            $startDate = $state['start_date'];
            $dayOfWeek = $state['day_of_week'];
            $startTime = $state['start_time'];
            $numberOfClasses = (int) $state['number_of_classes'];
            $dates = [];
            if ($startDate && $dayOfWeek && $startTime && $numberOfClasses > 0) {
                $date = \Carbon\Carbon::parse($startDate);
                // Asegura que la fecha inicial sea el día correcto
                while ($date->format('l') !== $dayOfWeek) {
                    $date->addDay();
                }
                for ($i = 0; $i < $numberOfClasses; $i++) {
                    $dates[] = [
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $startTime,
                        'feriado' => false,
                        'nota' => null,
                    ];
                    $date->addWeek();
                }
                $livewire->form->fill(array_merge($state, ['clases_generadas' => $dates]));
            }
        })
        ->color('primary'),
]),
Forms\Components\Repeater::make('clases_generadas')
    ->label('Clases')
    ->schema([
        Forms\Components\DatePicker::make('date')
            ->label(fn ($state, $record) => 'Clase ' . ($record ? ($record["index"] + 1) : '')),
        Forms\Components\Toggle::make('feriado')
            ->label('Feriado'),
        Forms\Components\TextInput::make('nota')
            ->label('Notas'),
    ])
    ->columnSpanFull()
    ->visible(fn ($state) => !empty($state)),

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
