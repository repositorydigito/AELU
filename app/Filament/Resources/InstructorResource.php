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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Notifications\Notification;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;
    protected static ?string $navigationLabel = 'Listado de Profesores';
    protected static ?string $pluralModelLabel = 'Profesores';
    protected static ?string $modelLabel = 'Profesor';
    protected static ?int $navigationSort = 5;
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
                                                ->required()
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

                    Step::make('Ficha médica')
                        ->schema([
                            Section::make('Ficha Médica')
                                ->relationship('medicalRecord')
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            // Columna 1: Peso y Talla
                                            Grid::make(1)
                                                ->columnSpan(1)
                                                ->schema([
                                                    TextInput::make('weight')
                                                        ->label('Peso')
                                                        ->numeric()
                                                        ->suffix('kg'),

                                                    TextInput::make('height')
                                                        ->label('Talla')
                                                        ->numeric()
                                                        ->suffix('cm'),
                                                ]),

                                            // Columna 2: Género
                                            Radio::make('gender')
                                                ->label('Género')
                                                ->options([
                                                    'Femenino' => 'Femenino',
                                                    'Masculino' => 'Masculino',
                                                    'Prefiero no responder' => 'Prefiero no responder',
                                                ])
                                                ->columnSpan(1),

                                            // Columna 3: Fumar
                                            Grid::make(1)
                                                ->columnSpan(1)
                                                ->schema([
                                                    Radio::make('smokes')
                                                        ->label('¿Fuma?')
                                                        ->options([
                                                            'Sí' => 'Sí',
                                                            'No' => 'No',
                                                        ])
                                                        ->reactive(),

                                                    TextInput::make('cigarettes_per_day')
                                                        ->label('¿Cuántos cigarrillos al día?')
                                                        ->numeric()
                                                        ->hidden(fn (callable $get) => $get('smokes') !== 'Sí')
                                                        ->maxLength(255),
                                                ]),

                                            // Columna 4: Seguro Médico
                                            Select::make('health_insurance')
                                                ->label('Seguro Médico')
                                                ->options([
                                                    'ESSALUD' => 'ESSALUD',
                                                    'SIS' => 'SIS',
                                                    'RIMAC' => 'RIMAC',
                                                    'Pacífico' => 'Pacífico',
                                                    'MAPFRE' => 'MAPFRE',
                                                    'La Positiva' => 'La Positiva',
                                                ])
                                                ->columnSpan(1),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            // Columna izquierda - Condiciones médicas
                                            Forms\Components\Group::make([
                                                CheckboxList::make('medical_conditions')
                                                    ->label('Condiciones médicas que padece *')
                                                    ->options([
                                                        'Ninguna' => 'Ninguna',
                                                        'Hipertension Arterial' => 'Hipertensión Arterial',
                                                        'Asma, Bronquitis' => 'Asma, Bronquitis',
                                                        'Gastritis, Ulceras' => 'Gastritis, Úlceras',
                                                        'Diabetes' => 'Diabetes',
                                                        'Artrosis, Artritis' => 'Artrosis, Artritis',
                                                        'Estrés, Ansiedad, Depresión' => 'Estrés, Ansiedad, Depresión',
                                                        'Taquicardia, Angina de Pecho' => 'Taquicardia, Angina de Pecho',
                                                        'ACV (Accidente Cerebro Vascular)' => 'ACV (Accidente Cerebro Vascular)',
                                                        'Hipoacusia (Sordera)' => 'Hipoacusia (Sordera)',
                                                        'Alergias' => 'Alergias',
                                                        'Otros' => 'Otros',
                                                    ])
                                                    ->columns(1)
                                                    ->reactive(),

                                                CheckboxList::make('allergies')
                                                    ->label('')
                                                    ->options([
                                                        'Alimentos' => 'Alimentos',
                                                        'Medicinas' => 'Medicinas',
                                                        'Otros' => 'Otros',
                                                    ])
                                                    ->columns(1)
                                                    ->hidden(fn (callable $get) => !in_array('Alergias', $get('medical_conditions') ?? []))
                                                    ->reactive(),

                                                Textarea::make('allergy_details')
                                                    ->label('Detalle el tipo de alergia')
                                                    ->hidden(fn (callable $get) => !in_array('Alergias', $get('medical_conditions') ?? []) || empty($get('allergies'))),

                                                TextInput::make('medical_conditions')
                                                    ->label('Especifique otra condición médica')
                                                    ->hidden(fn (callable $get) => !in_array('Otros', $get('medical_conditions') ?? []))
                                                    ->reactive(),
                                            ]),

                                            // Columna derecha - Operaciones
                                            Forms\Components\Group::make([
                                                CheckboxList::make('surgical_operations')
                                                    ->label('Operaciones a las que se ha sometido *')
                                                    ->options([
                                                        'Ninguna' => 'Ninguna',
                                                        'Al Corazón' => 'Al Corazón',
                                                        'Al Cerebro' => 'Al Cerebro',
                                                        'A la Vista' => 'A La Vista',
                                                        'A la Columna' => 'A La Columna',
                                                        'A la Rodilla' => 'A La Rodilla',
                                                        'A la Cadera' => 'A La Cadera',
                                                        'Otros' => 'Otros',
                                                    ])
                                                    ->columns(1)
                                                    ->reactive(),

                                                TextInput::make('surgical_operation_details')
                                                    ->label('Especificar *')
                                                    ->hidden(fn (callable $get) => !in_array('Otros', $get('surgical_operations') ?? [])),
                                            ]),
                                        ]),

                                    Repeater::make('medications')
                                        ->relationship('medications')
                                        ->label('Medicamentos que toma')
                                        ->schema([
                                            Fieldset::make('Detalles del Medicamento')
                                                ->schema([
                                                    TextInput::make('medicine')
                                                        ->label('Medicina')
                                                        ->required()
                                                        ->validationMessages(['required' => 'Este campo es obligatorio']),
                                                    TextInput::make('dose')
                                                        ->label('Dosis'),
                                                ])
                                                ->columnSpan(1),
                                            Radio::make('schedule')
                                                ->label('Horario')
                                                ->options([
                                                    'Mañana' => 'Mañana',
                                                    'Tarde' => 'Tarde',
                                                    'Noche' => 'Noche',
                                                    'Mañana-Tarde' => 'Mañana-Tarde',
                                                    'Mañana-Noche' => 'Mañana-Noche',
                                                    'Tarde-Noche' => 'Tarde-Noche',
                                                    'Mañana-Tarde-Noche' => 'Mañana-Tarde-Noche',
                                                ])
                                                ->required()
                                                ->validationMessages(['required' => 'Seleccione al menos un horario'])
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Registrar Medicamento')
                                        ->defaultItems(0)
                                        ->itemLabel(fn (array $state): ?string => $state['medicine'] ?? null),
                                ]),
                        ]),


                    /* Step::make('Talleres Asignados')
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
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(3)
                                        ->itemLabel(fn (array $state): ?string => empty($state['workshop_id']) ? null : Workshop::find($state['workshop_id'])?->name . ' (' . ($state['day_of_week'] ?? 'N/A') . ' - ' . (\Carbon\Carbon::parse($state['start_time'])->format('H:i') ?? 'N/A') . ')' )
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->cloneable()
                                        ->grid(2)
                                        ->addActionLabel('Añadir Taller y Horario')
                                ])
                        ]), */
                    Step::make('Declaración jurada y resumen')
                        ->schema([
                            Section::make('Resumen de Ficha Personal')
                                ->description('Revisa los datos antes de completar la declaración jurada.')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Placeholder::make('last_names_summary')
                                                ->label('Apellidos')
                                                ->content(fn (callable $get) => $get('last_names')),
                                            Placeholder::make('first_names_summary')
                                                ->label('Nombres')
                                                ->content(fn (callable $get) => $get('first_names')),
                                            Placeholder::make('document_type_summary')
                                                ->label('Tipo de Documento')
                                                ->content(fn (callable $get) => $get('document_type')),
                                            Placeholder::make('document_number_summary')
                                                ->label('Número de Documento')
                                                ->content(fn (callable $get) => $get('document_number')),
                                            Placeholder::make('birth_date_summary')
                                                ->label('Fecha de Nacimiento')
                                                ->content(fn (callable $get) => \Carbon\Carbon::parse($get('birth_date'))->format('d/m/Y')),
                                            Placeholder::make('nationality_summary')
                                                ->label('Nacionalidad')
                                                ->content(fn (callable $get) => $get('nationality')),
                                            Placeholder::make('cell_phone_summary')
                                                ->label('Celular')
                                                ->content(fn (callable $get) => $get('cell_phone')),
                                            Placeholder::make('home_phone_summary')
                                                ->label('Teléfono de Casa')
                                                ->content(fn (callable $get) => $get('home_phone')),
                                            Placeholder::make('district_summary')
                                                ->label('Distrito')
                                                ->content(fn (callable $get) => $get('district')),
                                            Placeholder::make('address_summary')
                                                ->label('Domicilio')
                                                ->content(fn (callable $get) => $get('address')),
                                        ]),
                                ]),
                            Section::make('Ficha Médica Resumen')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Placeholder::make('weight_summary')
                                                ->label('Peso')
                                                ->content(fn (callable $get) => $get('medicalRecord.weight') ? $get('medicalRecord.weight') . ' kg' : 'N/A'),
                                            Placeholder::make('height_summary')
                                                ->label('Talla')
                                                ->content(fn (callable $get) => $get('medicalRecord.height') ? $get('medicalRecord.height') . ' m' : 'N/A'),
                                            Placeholder::make('gender_summary')
                                                ->label('Género')
                                                ->content(fn (callable $get) => $get('medicalRecord.gender') ?? 'N/A'),
                                            Placeholder::make('smokes_summary')
                                                ->label('Fuma')
                                                ->content(fn (callable $get) => $get('medicalRecord.smokes') ?? 'N/A'),
                                            Placeholder::make('cigarettes_per_day_summary')
                                                ->label('Cigarrillos al día')
                                                ->content(fn (callable $get) => $get('medicalRecord.smokes') === 'Sí' ? ($get('medicalRecord.cigarettes_per_day') ?? 'N/A') : 'No aplica'),
                                            Placeholder::make('health_insurance_summary')
                                                ->label('Seguro Médico')
                                                ->content(fn (callable $get) => $get('medicalRecord.health_insurance') ?? 'N/A'),
                                            Placeholder::make('medical_conditions_summary')
                                                ->label('Condiciones Médicas')
                                                ->content(fn (callable $get) => implode(', ', (array) $get('medicalRecord.medical_conditions')) ?: 'Ninguna'),
                                            Placeholder::make('allergies_summary')
                                                ->label('Alergias')
                                                ->content(fn (callable $get) => implode(', ', (array) $get('medicalRecord.allergies')) ?: 'Ninguna'),
                                            Placeholder::make('allergy_details_summary')
                                                ->label('Detalle de Alergias')
                                                ->content(fn (callable $get) => $get('medicalRecord.allergy_details') ?? 'N/A')
                                                ->hidden(fn (callable $get) => empty($get('medicalRecord.allergies'))),
                                            Placeholder::make('surgical_operations_summary')
                                                ->label('Operaciones Sometido')
                                                ->content(fn (callable $get) => implode(', ', (array) $get('medicalRecord.surgical_operations')) ?: 'Ninguna'),
                                            Placeholder::make('surgical_operation_details_summary')
                                                ->label('Especificar Operación')
                                                ->content(fn (callable $get) => in_array('Otros', $get('medicalRecord.surgical_operations') ?? []) ? ($get('medicalRecord.surgical_operation_details') ?? 'N/A') : 'No aplica')
                                                ->hidden(fn (callable $get) => !in_array('Otros', $get('medicalRecord.surgical_operations') ?? [])),

                                            Placeholder::make('medications_summary_text')
                                                ->label('Medicamentos que toma')
                                                ->content(function (callable $get) {
                                                    $medications = $get('medicalRecord.medications');
                                                    if (empty($medications)) {
                                                        return 'Ninguno';
                                                    }

                                                    $formattedMedications = collect($medications)->map(function ($med) {
                                                        $details = [];
                                                        if (!empty($med['medicine'])) {
                                                            $details[] = $med['medicine'];
                                                        }
                                                        if (!empty($med['dose'])) {
                                                            $details[] = '(' . $med['dose'] . ')';
                                                        }
                                                        if (!empty($med['schedule'])) {
                                                            $details[] = ' - ' . $med['schedule'];
                                                        }
                                                        return implode(' ', $details);
                                                    })->implode('<br>');

                                                    return new \Illuminate\Support\HtmlString($formattedMedications);
                                                })
                                                ->columns(3)
                                                ->hidden(fn (callable $get) => empty($get('medicalRecord.medications'))),
                                        ]),
                                ]),
                            /* Section::make('Resumen de Talleres Asignados')
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
                                ]), */
                            Section::make('Firma y Huella Digital')
                                ->relationship('affidavit')
                                ->schema([
                                    FileUpload::make('digital_signature_and_fingerprint_path')
                                        ->label('Arrastra y suelta tus archivos o subelos de tu computadora')
                                        ->image()
                                        ->nullable()
                                        ->directory('firmas-huellas-instructores')
                                        ->columnSpanFull()
                                        ->hint('Toma una foto de la firma y huella dactilar del instructor para adjuntarla en su archivo.')
                                        ->reactive()
                                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                            if ($state) {
                                                $set('firma_huella_adjuntada', true);
                                            } else {
                                                $set('firma_huella_adjuntada', false);
                                            }
                                        }),
                                    Forms\Components\Hidden::make('firma_huella_adjuntada')
                                        ->default(false)
                                        ->dehydrated(false),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('generate_affidavit_pdf')
                                            ->label('Generar Declaración Jurada')
                                            ->color('success')
                                            ->icon('heroicon-o-document-arrow-down')
                                            ->disabled(fn (callable $get, $livewire) => !$get('digital_signature_and_fingerprint_path') || !isset($livewire->record) || !$livewire->record?->id)
                                            ->action(function ($livewire, Forms\Get $get) {
                                                $instructor = $livewire->record;

                                                if ($instructor && $instructor->id) {
                                                    // Usar la ruta específica para instructores
                                                    $livewire->redirect(route('generate.affidavit.instructor.pdf', ['instructor' => $instructor->id]), navigate: false);
                                                } else {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Error de Generación')
                                                        ->body('Para generar la declaración jurada, el instructor debe haber sido guardado previamente. Por favor, finalice el registro o acceda a la edición de un instructor existente.')
                                                        ->send();
                                                }
                                            })
                                        ])
                                ]),
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
                TextColumn::make('instructorWorkshops.workshop.name') 
                    ->label('Talleres que Imparte')
                    ->listWithLineBreaks() 
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

