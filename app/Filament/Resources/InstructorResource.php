<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorResource\Pages;
use App\Filament\Resources\InstructorResource\RelationManagers;
use App\Models\Instructor;
use App\Models\Workshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater; // Para la lista de talleres en el formulario
use Filament\Forms\Components\Select; // Para el dropdown de talleres
use Filament\Forms\Components\TimePicker; // Para la hora

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;    
    protected static ?string $navigationLabel = 'Listado de Profesores';
    protected static ?string $pluralModelLabel = 'Profesores';
    protected static ?string $modelLabel = 'Profesor';
    protected static ?int $navigationSort = 4; 
    protected static ?string $navigationGroup = 'Profesores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Datos personales')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('last_names')
                                        ->label('Apellidos')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('first_names')
                                        ->label('Nombres')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('document_type')
                                        ->label('Tipo de documento')
                                        ->options([
                                            'DNI' => 'DNI',
                                            'Pasaporte' => 'Pasaporte',
                                            'Carnet de Extranjería' => 'Carnet de Extranjería',
                                        ])
                                        ->nullable(),
                                    Forms\Components\TextInput::make('document_number')
                                        ->label('Número de documento')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    DatePicker::make('birth_date')
                                        ->label('Fecha de Nacimiento')
                                        ->nullable(),
                                    Forms\Components\TextInput::make('nationality')
                                        ->label('Nacionalidad')
                                        ->maxLength(255)
                                        ->nullable(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('instructor_code')
                                        ->label('Código de Profesor')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Forms\Components\Select::make('instructor_type')
                                        ->label('Tipo de Profesor')
                                        ->options([
                                            'Voluntario' => 'Voluntario',
                                            'Por Horas' => 'Por Horas',
                                        ])
                                        ->required(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('cell_phone')
                                        ->label('Celular')
                                        ->tel()
                                        ->maxLength(255)
                                        ->nullable(),
                                    Forms\Components\TextInput::make('home_phone')
                                        ->label('Teléfono de casa')
                                        ->tel()
                                        ->maxLength(255)
                                        ->nullable(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('district')
                                        ->label('Distrito')
                                        ->maxLength(255)
                                        ->nullable(),
                                    Forms\Components\TextInput::make('address')
                                        ->label('Domicilio')
                                        ->maxLength(255)
                                        ->nullable(),
                                ]),
                            FileUpload::make('photo')
                                ->label('Foto')
                                ->image()
                                ->directory('instructors-photos')
                                ->nullable(),
                        ]),

                    Step::make('Talleres')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            // El Repeater es clave para la lista de talleres que dicta el instructor
                            Repeater::make('workshops_details') // Un nombre diferente para no confundir con la relación 'workshops'
                                ->label('Talleres que dicta')
                                ->relationship('workshops') // Vincula el Repeater a la relación 'workshops' del Instructor
                                ->schema([
                                    Select::make('workshop_id')
                                        ->label('Taller')
                                        ->options(Workshop::pluck('name', 'id')) // Obtiene nombres de talleres de la tabla Workshops
                                        ->searchable()
                                        ->required()
                                        ->distinct() // Evita seleccionar el mismo taller varias veces en el mismo repetidor
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(), // Evita seleccionar el mismo taller en items del mismo repeater
                                    Select::make('day')
                                        ->label('Día')
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
                                    TimePicker::make('time')
                                        ->label('Hora')
                                        ->required()
                                        ->seconds(false), // Sin segundos
                                    Forms\Components\TextInput::make('class_count')
                                        ->label('Cantidad de clases')
                                        ->numeric()
                                        ->required()
                                        ->default(1),
                                    Forms\Components\TextInput::make('rate')
                                        ->label('Tarifa (S/)')
                                        ->numeric()
                                        ->prefix('S/')
                                        ->required(),
                                ])
                                ->columnSpanFull() // Para que el repeater ocupe todo el ancho
                                ->defaultItems(0) // Inicia sin ítems por defecto
                                ->minItems(0)
                                ->columns(5) // Ajusta el número de columnas dentro del Repeater (Taller, Día, Hora, Cantidad, Tarifa)
                                ->reorderable(true) // Permite reordenar los ítems
                                ->addActionLabel('Registrar Taller') // Texto del botón para añadir ítem
                                ->relationship('workshops') // Es importante para guardar la relación de muchos a muchos
                        ]),

                    Step::make('Registro completo')
                        ->icon('heroicon-o-check-circle')
                        ->description('Revisa y confirma la información.')
                        ->schema([
                            // Aquí podrías mostrar un resumen o simplemente dejarlo para el botón "Guardar" final.
                            Forms\Components\Placeholder::make('summary_placeholder')
                                ->content('Por favor, revisa todos los datos ingresados antes de guardar.')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_names')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_names')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instructor_code')
                    ->label('Cód. del profesor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instructor_type')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Voluntario' => 'success',
                        'Por Horas' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('cell_phone')
                    ->label('Teléfono')
                    ->searchable(),
                // Aquí, el "Tarifa" de la tabla principal es un poco engañoso si la tarifa es por taller.
                // Podríamos mostrar un resumen o un valor promedio, o quitarlo si la tarifa es solo por taller.
                // Para simplificar, si la tarifa es por instructor, la hubiéramos puesto en el modelo Instructor.
                // Como las imágenes muestran "Tarifa" en la lista de profesores y "S/X" en cada taller,
                // vamos a mantener "Tarifa" como una columna que resuma si es posible, o que muestre "Ver Talleres".
                TextColumn::make('workshops.pivot.rate') // Esto no funcionará directamente para sumar, solo para mostrar el primero
                    ->label('Tarifa (ej.)')
                    ->formatStateUsing(function (string $state, Instructor $record) {
                        if ($record->workshops->isNotEmpty()) {
                            // Muestra la tarifa del primer taller como ejemplo
                            return 'S/' . number_format($record->workshops->first()->pivot->rate, 2);
                        }
                        return '-';
                    })
                    ->tooltip(function (Instructor $record) {
                        if ($record->workshops->isNotEmpty()) {
                            $tooltip = 'Tarifas por Taller:';
                            foreach ($record->workshops as $workshop) {
                                $tooltip .= "\n- {$workshop->name}: S/" . number_format($workshop->pivot->rate, 2);
                            }
                            return $tooltip;
                        }
                        return 'Sin talleres asignados.';
                    }),

                TextColumn::make('workshops.name') // Para mostrar los nombres de los talleres
                    ->label('Talleres')
                    ->badge() // Muestra cada taller como un badge
                    ->separator(',') // Separa los badges con una coma si hay muchos
                    ->colors(['info']) // Color para los badges de talleres
                    ->wrap() // Envuelve el texto si es muy largo
                    ->limit(50) // Limita la cantidad de texto si es necesario
                    ->tooltip(function (Instructor $record) { // Tooltip para mostrar todos los talleres
                        return $record->workshops->pluck('name')->implode(', ');
                    }),
                
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('instructor_type')
                    ->label('Modalidad')
                    ->options([
                        'Voluntario' => 'Voluntario',
                        'Por Horas' => 'Por Horas',
                    ]),
                Tables\Filters\SelectFilter::make('workshops')
                    ->label('Taller')
                    ->relationship('workshops', 'name')
                    ->searchable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'create' => Pages\CreateInstructor::route('/create'),
            'edit' => Pages\EditInstructor::route('/{record}/edit'),
        ];
    }
}
