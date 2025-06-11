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
use Filament\Forms\Components\TextInput; // Importar TextInput para la tarifa
use Filament\Forms\Components\Radio; // Para tipo de instructor
use Filament\Forms\Components\Section; // Para organizar el formulario
use Filament\Forms\Components\Grid; // Para organizar el formulario
use Filament\Support\RawJs;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;

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
                    Step::make('Datos Personales')
                        ->schema([
                                Section::make('Datos Personales')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                            Grid::make(1)
                                                ->columnSpan(1)
                                            ->schema([
                                                TextInput::make('last_names')
                                                    ->label('Apellidos')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(1),
                                                TextInput::make('first_names')
                                                    ->label('Nombres')
                                                    ->required()
                                                    ->maxLength(20),
                                                    //->columnSpan(1),
                                                
                                                DatePicker::make('birth_date')
                                                    ->label('Fecha de Nacimiento')
                                                    ->required()
                                                    ->maxDate(now()),
                                                TextInput::make('nationality')
                                                    ->label('Nacionalidad')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('instructor_code')
                                                    ->label('Código de Profesor')
                                                    ->required()
                                                    ->maxLength(255),
                                                Select::make('instructor_type')
                                                    ->label('Tipo de Profesor')
                                                    ->options([
                                                        'VOLUNTARIO' => 'VOLUNTARIO',
                                                        'POR HORAS' => 'POR HORAS',
                                                    ])
                                                    ->required(),
                                                TextInput::make('cell_phone')
                                                    ->label('Celular')                                                
                                                    ->maxLength(255)
                                                    ->columnSpan(1),
                                                
                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('document_type')
                                                            ->label('Tipo de documento')
                                                            ->options([
                                                                'DNI' => 'DNI',
                                                                'CE' => 'Carné de Extranjería',
                                                                'Pasaporte' => 'Pasaporte',
                                                            ])
                                                            ->required()
                                                            ->validationMessages(['required' => 'Este campo es obligatorio']),
                                                        TextInput::make('document_number')
                                                            ->label('Número de Documento')
                                                            ->required()
                                                            ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                            ->maxLength(15),
                                                    ]),
                                            ]),
                                            FileUpload::make('photo')
                                                ->label('Foto')
                                                ->image()
                                                ->directory('instructors-photos')
                                                ->maxSize(10240)
                                                ->columnSpan(1),
                                    ]),
                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('birth_date')
                                                ->label('Fecha de Nacimiento')
                                                ->required()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxDate(now()),
                                            TextInput::make('nationality')
                                                ->label('Nacionalidad')
                                                ->required()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(20),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('instructor_code')
                                                ->label('Código de Profesor')
                                                ->required()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(20),
                                            Select::make('instructor_type')
                                                ->label('Tipo de Profesor')
                                                ->options([
                                                    'Voluntario' => 'Voluntario',
                                                    'Por Horas' => 'Por Horas',
                                                ]),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('cell_phone')
                                                ->label('Celular')                                                
                                                ->maxLength(255)
                                                ->nullable(),
                                            TextInput::make('home_phone')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->maxLength(20)
                                                ->nullable(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('district') 
                                                ->label('Distrito')
                                                ->maxLength(255)                                                
                                                ->nullable(),      
                                            TextInput::make('address')
                                                ->label('Domicilio')
                                                ->maxLength(255)                                                
                                                ->nullable(),                                     
                                        ]),
                                    ]),
                        ]),
                    

                    Step::make('Talleres Asignados')
                        ->schema([
                            Section::make('Talleres que Imparte el Profesor')
                                ->description('Añade los talleres que este instructor dictará, especificando el horario y la tarifa.')
                                ->schema([
                                    Repeater::make('instructorWorkshops') 
                                        ->relationship('instructorWorkshops') 
                                        ->label('Talleres')
                                        ->schema([
                                            Select::make('workshop_id')
                                                ->label('Taller')
                                                ->options(Workshop::all()->pluck('name', 'id'))
                                                ->searchable()
                                                ->required(),                                              
                                            Select::make('day_of_week')
                                                ->label('Día de la Semana')
                                                ->options([
                                                    'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miércoles' => 'Miércoles',
                                                    'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sábado' => 'Sábado',
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
                                                ->displayFormat('H:i'),
                                            TextInput::make('class_count')
                                                ->label('Número de Clases')
                                                ->numeric()
                                                ->minValue(1)
                                                ->nullable(),
                                            TextInput::make('class_rate')
                                                ->label('Tarifa por Clase/Hora (S/.)')
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->mask(RawJs::make('$money($event.target.value)'))
                                                ->stripCharacters(',')
                                                ->nullable(),
                                            TextInput::make('place')
                                                ->label('Lugar')
                                                ->required(),                                         
                                        ])
                                        ->columns(3)
                                        ->itemLabel(fn (array $state): ?string => empty($state['workshop_id']) ? null : Workshop::find($state['workshop_id'])?->name . ' (' . ($state['day_of_week'] ?? 'N/A') . ' - ' . (\Carbon\Carbon::parse($state['start_time'])->format('H:i') ?? 'N/A') . ')' )
                                        ->defaultItems(1)
                                        ->collapsible()
                                        ->cloneable()
                                        ->grid(2)
                                        ->addActionLabel('Añadir Taller y Horario')
                                ])
                        ]),
                    Step::make('Resumen')
                        ->schema([
                            Section::make('Ficha personal del Profesor')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([                                            
                                            Placeholder::make('full_name_summary')
                                                ->label('Nombre Completo')
                                                ->content(fn ($get) => $get('first_names') . ' ' . $get('last_names')),
                                            Placeholder::make('document_summary')
                                                ->label('Documento')
                                                ->content(fn ($get) => ($get('document_type') ?? 'N/A') . ': ' . ($get('document_number') ?? 'N/A')),
                                            Placeholder::make('birth_date_summary')
                                                ->label('Fecha de Nacimiento')
                                                ->content(fn ($get) => \Carbon\Carbon::parse($get('birth_date'))->format('d/m/Y') ?? 'N/A'),
                                            Placeholder::make('nationality_summary')
                                                ->label('Nacionalidad')
                                                ->content(fn ($get) => $get('nationality') ?? 'N/A'),
                                            Placeholder::make('contact_summary')
                                                ->label('Contacto')
                                                ->content(fn ($get) => ($get('email') ?? 'Sin Email') . ' / Tel: ' . ($get('phone') ?? 'Sin Teléfono')),
                                            Placeholder::make('address_summary')
                                                ->label('Dirección')
                                                ->content(fn ($get) => ($get('address') ?? 'N/A') . ', ' . ($get('district') ?? 'N/A')),
                                            Placeholder::make('instructor_type_summary')
                                                ->label('Tipo de Instructor')
                                                ->content(fn ($get) => $get('instructor_type') ?? 'N/A'),
                                        ]),
                                ]),
                            Section::make('Resumen de Talleres Asignados')
                                ->schema([
                                    Placeholder::make('workshops_list_summary')
                                        ->label('Talleres y Horarios')
                                        ->content(function ($get) {
                                            $workshopsData = $get('instructorWorkshops'); // Obtener los datos del repeater de talleres
                                            
                                            if (empty($workshopsData)) {
                                                return 'No se han asignado talleres.';
                                            }

                                            $summary = '<div class="space-y-2">'; // Contenedor para los elementos de la lista
                                            foreach ($workshopsData as $workshopItem) {
                                                $workshopName = Workshop::find($workshopItem['workshop_id'])?->name ?? 'Taller Desconocido';
                                                $dayOfWeek = $workshopItem['day_of_week'] ?? 'N/A';
                                                
                                                $startTime = isset($workshopItem['start_time']) ? \Carbon\Carbon::parse($workshopItem['start_time'])->format('H:i') : 'N/A';
                                                $endTime = isset($workshopItem['end_time']) ? \Carbon\Carbon::parse($workshopItem['end_time'])->format('H:i') : 'N/A';
                                                
                                                $classCount = $workshopItem['class_count'] ?? 'N/A';
                                                $classRate = $workshopItem['class_rate'] ?? '0.00';
                                                $place = $workshopItem['place'];

                                                $summary .= '<div class="p-2 border rounded-md bg-gray-50 dark:bg-gray-800">';
                                                $summary .= '<p><strong class="text-primary-600">Taller:</strong> ' . $workshopName . '</p>';
                                                $summary .= '<p><strong class="text-primary-600">Horario:</strong> ' . $dayOfWeek . ' ' . $startTime . ' - ' . $endTime . '</p>';
                                                $summary .= '<p><strong class="text-primary-600">Detalles:</strong> Clases: ' . $classCount . ' / Tarifa: S/. ' . number_format($classRate, 2) . ' / Lugar: ' .$place . '</p>';
                                                $summary .= '</div>';
                                            }
                                            $summary .= '</div>';
                                            return new \Illuminate\Support\HtmlString($summary); // Importante para renderizar HTML
                                        }),
                                ])
                        ]),
                ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_names')->label('Nombres')->searchable(),
                TextColumn::make('last_names')->label('Apellidos')->searchable(),
                TextColumn::make('document_number')->label('DNI')->searchable(),
                TextColumn::make('cell_phone')->label('Teléfono'),
                BadgeColumn::make('instructor_type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'VOLUNTARIO',
                        'info' => 'POR HORAS',
                    ]),
                TextColumn::make('instructorWorkshops.workshop.name') // Accede a los nombres de los talleres a través de la relación
                    ->label('Talleres que Imparte')
                    ->listWithLineBreaks() // Muestra cada taller en una nueva línea
                    ->limit(50)
                    ->tooltip(function (Instructor $record) {
                        return $record->instructorWorkshops->map(function ($iw) {
                            // Asegura que workshop y tiempos existen antes de intentar acceder a ellos
                            $workshopName = $iw->workshop->name ?? 'Taller desconocido';
                            $startTime = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i') : 'N/A';
                            $endTime = $iw->end_time ? \Carbon\Carbon::parse($iw->end_time)->format('H:i') : 'N/A';
                            $dayOfWeek = $iw->day_of_week ?? 'N/A';
                            return $workshopName . ' (' . $dayOfWeek . ' ' . $startTime . '-' . $endTime . ')';
                        })->implode("\n");
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('instructor_type')
                    ->label('Modalidad')
                    ->options([
                        'VOLUNTARIO' => 'VOLUNTARIO',
                        'POR HORAS' => 'POR HORAS',
                    ]),
                // Filtro para talleres, ahora basado en la relación a través de instructorWorkshops
                Tables\Filters\SelectFilter::make('workshops')
                    ->label('Taller')
                    // Utiliza la relación BelongsToMany que definimos en Workshop, o puedes usar 'instructorWorkshops.workshop'
                    // Aquí usamos la relación `instructors` en el modelo `Workshop` (asumiendo que la has añadido para el filtro)
                    // Si no, la relación directa a través de la tabla pivote es Tables\Filters\SelectFilter::make('instructorWorkshops.workshop_id')
                    // Lo más robusto para un filtro de relación es:
                    ->relationship('instructorWorkshops.workshop', 'name', fn (Builder $query) => $query->distinct())
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'create' => Pages\CreateInstructor::route('/create'),
            'edit' => Pages\EditInstructor::route('/{record}/edit'),
            'import' => Pages\ImportInstructors::route('/import'),
        ];
    }
}

