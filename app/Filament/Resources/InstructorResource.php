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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
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
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Profesores';
    protected static ?string $pluralModelLabel = 'Profesores';
    protected static ?string $modelLabel = 'Profesor';
    protected static ?string $navigationGroup = 'Gestión';
    protected static ?int $navigationSort = 2;

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
                                                ->nullable()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxDate(now()),
                                            TextInput::make('nationality')
                                                ->label('Nacionalidad')
                                                ->nullable()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(20),
                                        ]),
                                    /* Grid::make(2)
                                        ->schema([
                                            Select::make('instructor_type')
                                                ->label('Modalidad de Profesor')
                                                ->options([
                                                    'Por horas' => 'Por horas',
                                                    'Voluntario' => 'Voluntario',
                                                ])
                                                ->nullable()
                                                ->validationMessages(['required' => 'Este campo es obligatorio']),
                                        ]), */
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
                                                    ->label('Condiciones médicas que padece')
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
                                                    ->disableOptionWhen(function (string $value, callable $get) {
                                                        $selected = $get('medical_conditions') ?? [];
                                                        return in_array('Ninguna', $selected) && $value !== 'Ninguna';
                                                    })
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

                                                TextInput::make('medical_conditions_other')
                                                    ->label('Especifique otra condición médica')
                                                    ->hidden(fn (callable $get) => !in_array('Otros', $get('medical_conditions') ?? []))
                                                    ->reactive(),
                                            ]),

                                            // Columna derecha - Operaciones
                                            Forms\Components\Group::make([
                                                CheckboxList::make('surgical_operations')
                                                    ->label('Operaciones a las que se ha sometido')
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
                                                    ->reactive()
                                                    ->disableOptionWhen(function (string $value, callable $get) {
                                                        $selected = $get('surgical_operations') ?? [];
                                                        return in_array('Ninguna', $selected) && $value !== 'Ninguna';
                                                    }),

                                                TextInput::make('surgical_operation_details')
                                                    ->label('Especificar')
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
                    Step::make('Talleres y Modalidad de Pago')
                        ->schema([
                            Section::make('Configuración de Talleres')
                                ->description('Configura los talleres que dictará este instructor y su modalidad de pago')
                                ->schema([
                                    Repeater::make('instructorWorkshops')
                                        ->relationship('instructorWorkshops')
                                        ->label('Talleres')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    // Columna izquierda - Información del Taller
                                                    Grid::make(1)
                                                        ->columnSpan(1)
                                                        ->schema([
                                                            Select::make('workshop_id')
                                                                ->label('Taller')
                                                                ->options(Workshop::all()->pluck('name', 'id'))
                                                                ->required()
                                                                ->searchable()
                                                                ->reactive(),

                                                            // Campo de solo lectura para mostrar horarios
                                                            Placeholder::make('schedule_display')
                                                                ->label('Horario')
                                                                ->content(function (callable $get) {
                                                                    $workshopId = $get('workshop_id');
                                                                    if (!$workshopId) {
                                                                        return 'Selecciona un taller para ver el horario';
                                                                    }

                                                                    $workshop = Workshop::find($workshopId);
                                                                    if (!$workshop) {
                                                                        return 'Horario no disponible';
                                                                    }

                                                                    // Aquí debes ajustar según la estructura de tu modelo Workshop
                                                                    // Ejemplo asumiendo que tienes campos day_of_week y start_time
                                                                    /* $dayNames = [
                                                                        'monday' => 'Lunes',
                                                                        'tuesday' => 'Martes',
                                                                        'wednesday' => 'Miércoles',
                                                                        'thursday' => 'Jueves',
                                                                        'friday' => 'Viernes',
                                                                        'saturday' => 'Sábado',
                                                                        'sunday' => 'Domingo'
                                                                    ];

                                                                    // Opción 1: Si tienes campos day_of_week y start_time
                                                                    if ($workshop->day_of_week && $workshop->start_time) {
                                                                        $dayName = $dayNames[$workshop->day_of_week] ?? $workshop->day_of_week;
                                                                        $time = \Carbon\Carbon::parse($workshop->start_time)->format('h:i A');
                                                                        return "{$dayName} {$time}";
                                                                    } */

                                                                    if ($workshop->day_of_week && $workshop->start_time) {
                                                                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i A');
                                                                        $endTime = $workshop->end_time ? \Carbon\Carbon::parse($workshop->end_time)->format('H:i A') : 'N/A';
                                                                        
                                                                        return "{$workshop->day_of_week}: {$startTime} - {$endTime}";
                                                                    }

                                                                    return 'Horario no configurado';
                                                                })
                                                                ->visible(fn (callable $get) => $get('workshop_id')),
                                                        ]),

                                                    // Columna derecha - Modalidad de Pago
                                                    Grid::make(1)
                                                        ->columnSpan(1)
                                                        ->schema([
                                                            Fieldset::make('Modalidad de Pago')
                                                                ->schema([
                                                                    Radio::make('payment_type')
                                                                        ->label('Tipo de Pago')
                                                                        ->options([
                                                                            'volunteer' => 'Voluntario',
                                                                            'hourly' => 'Por Horas',
                                                                        ])
                                                                        ->required()
                                                                        ->reactive()
                                                                        ->columnSpanFull(),

                                                                    // Campos para Modalidad Voluntario
                                                                    TextInput::make('custom_volunteer_percentage')
                                                                        ->label('Porcentaje de Pago (%)')
                                                                        ->numeric()
                                                                        ->minValue(0)
                                                                        ->maxValue(100)
                                                                        ->suffix('%')
                                                                        ->visible(fn (callable $get) => $get('payment_type') === 'volunteer')
                                                                        ->required(fn (callable $get) => $get('payment_type') === 'volunteer'),

                                                                    // Campos para Modalidad Por Horas
                                                                    Grid::make(2)
                                                                        ->schema([
                                                                            TextInput::make('hourly_rate')
                                                                                ->label('Honorario por Hora')
                                                                                ->prefix('S/')
                                                                                ->numeric()
                                                                                ->minValue(0)
                                                                                ->visible(fn (callable $get) => $get('payment_type') === 'hourly')
                                                                                ->required(fn (callable $get) => $get('payment_type') === 'hourly'),

                                                                            /* TextInput::make('duration_hours')
                                                                                ->label('Duración (horas)')
                                                                                ->numeric()
                                                                                ->step(0.5)
                                                                                ->minValue(0.5)
                                                                                ->suffix('hrs')
                                                                                ->visible(fn (callable $get) => $get('payment_type') === 'hourly')
                                                                                ->required(fn (callable $get) => $get('payment_type') === 'hourly'), */
                                                                        ])
                                                                        ->visible(fn (callable $get) => $get('payment_type') === 'hourly'),
                                                                ]),
                                                        ]),
                                                ]),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Agregar Taller')
                                        ->itemLabel(fn (array $state): ?string =>
                                            Workshop::find($state['workshop_id'])?->name ?? 'Taller'
                                        )
                                        ->defaultItems(0),
                                ]),
                        ]),
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
                TextColumn::make('last_names')->label('Apellidos')->searchable(),
                TextColumn::make('first_names')->label('Nombres')->searchable(),                
                                
                TextColumn::make('unique_workshops')
                    ->label('Talleres')
                    ->getStateUsing(function (Instructor $record) {
                        return $record->instructorWorkshops
                            ->groupBy('workshop.name')
                            ->keys()
                            ->toArray();
                    })
                    ->badge()
                    ->separator(',')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('instructorWorkshops.workshop', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->tooltip(function (Instructor $record) {
                        return $record->instructorWorkshops
                            ->groupBy('workshop.name')
                            ->map(function ($workshops, $workshopName) {
                                $schedules = $workshops->map(function ($iw) {
                                    $workshop = $iw->workshop;
                                    if (!$workshop) return 'Horario no disponible';
                                    
                                    $startTime = $workshop->start_time ? 
                                        \Carbon\Carbon::parse($workshop->start_time)->format('H:i') : 'N/A';
                                    $endTime = $workshop->end_time ? 
                                        \Carbon\Carbon::parse($workshop->end_time)->format('H:i') : 'N/A';
                                    $day = $workshop->day_of_week ?? 'N/A';
                                    
                                    return "{$day} {$startTime}-{$endTime}";
                                })->unique()->implode(', ');
                                
                                return "{$workshopName}: {$schedules}";
                            })
                            ->implode("\n");
                    }),

                TextColumn::make('total_schedules')
                    ->label('Horarios')
                    ->getStateUsing(function (Instructor $record) {
                        $totalSchedules = $record->instructorWorkshops->count();
                        return $totalSchedules . ($totalSchedules === 1 ? ' horario' : ' horarios');
                    })
                    ->badge()
                    ->color(function (Instructor $record) {
                        $count = $record->instructorWorkshops->count();
                        return match (true) {
                            $count === 0 => 'gray',
                            $count === 1 => 'success',
                            $count <= 3 => 'warning',
                            default => 'danger'
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withCount('instructorWorkshops')
                            ->orderBy('instructor_workshops_count', $direction);
                    }),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

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

    public static function getBadgeCount(): int
    {
        return Instructor::count();
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getBadgeCount();
    }
}

