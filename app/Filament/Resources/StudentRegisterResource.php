<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentRegisterResource\Pages;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
// use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentRegisterResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Alumnos';
    protected static ?string $pluralModelLabel = 'Alumnos';
    protected static ?string $modelLabel = 'Alumno';
    protected static ?string $navigationGroup = 'Gestión';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Datos personales')
                        ->schema([
                            Section::make('Datos personales')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Grid::make(1)
                                                ->columnSpan(1)
                                                ->schema([
                                                    TextInput::make('last_names')
                                                        ->label('Apellidos')
                                                        ->required()
                                                        ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                        ->maxLength(255),

                                                    TextInput::make('first_names')
                                                        ->label('Nombres')
                                                        ->required()
                                                        ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                        ->maxLength(255),

                                                    Grid::make(2)
                                                        ->schema([
                                                            Select::make('document_type')
                                                                ->label('Tipo de documento')
                                                                ->options([
                                                                    'DNI' => 'DNI',
                                                                    'CE' => 'Carné de Extranjería',
                                                                    'Pasaporte' => 'Pasaporte',
                                                                ])
                                                                ->nullable(),

                                                            TextInput::make('document_number')
                                                                ->label('Número de documento')
                                                                ->nullable()
                                                                ->maxLength(15),
                                                        ]),
                                                ]),
                                            FileUpload::make('photo')
                                                ->label('Foto')
                                                ->image()
                                                ->directory('student-photos')
                                                ->maxSize(10240)
                                                ->columnSpan(1),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('birth_date')
                                                ->label('Fecha de Nacimiento')
                                                ->required()
                                                ->maxDate(now())
                                                ->live(onBlur: true),

                                            Placeholder::make('age_display')
                                                ->label('Edad')
                                                ->content(function (Get $get, ?Student $record): string {
                                                    // En edición con record
                                                    if ($record?->birth_date && !$get('birth_date')) {
                                                        return $record->age . ' años';
                                                    }

                                                    // Cuando se cambia la fecha
                                                    $birthDate = $get('birth_date');
                                                    if (!$birthDate) {
                                                        return '—';
                                                    }

                                                    if (is_string($birthDate)) {
                                                        $birthDate = Carbon::parse($birthDate);
                                                    }

                                                    return $birthDate->age . ' años';
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('student_code')
                                                ->label('Código de Asociado')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->validationMessages([
                                                    'required' => 'Este campo es obligatorio',
                                                    'unique' => 'Ya existe un estudiante con este código de asociado.',
                                                ])
                                                ->maxLength(255),
                                            Select::make('category_partner')
                                                ->label('Categoría de Socio')
                                                ->required()
                                                ->options([
                                                    'Vitalicios' => 'Vitalicios',
                                                    'Hijo de Fundador' => 'Hijo de Fundador',
                                                    'Transitorio Mayor de 75' => 'Transitorio Mayor de 75',
                                                    'Individual' => 'Individual',
                                                    'Transitorio Mayor' => 'Transitorio Mayor',
                                                    'Familiar - Titular' => 'Familiar - Titular',
                                                    'Familiar - Dependiente' => 'Familiar - Dependiente',
                                                    // PRE PAMA
                                                    'PRE PAMA 55+' => 'PRE PAMA 55+ (50% adicional)',
                                                    'PRE PAMA 50+' => 'PRE PAMA 50+ (100% adicional)',
                                                ])
                                                ->live()
                                                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                    // Categorías exentas de pago
                                                    $exemptCategories = [
                                                        'Transitorio Mayor de 75',
                                                        'Hijo de Fundador',
                                                        'Vitalicios',
                                                    ];

                                                    // Si es una categoría exenta, limpiar el período de mantenimiento
                                                    if (in_array($state, $exemptCategories)) {
                                                        $set('maintenance_period_id', null);
                                                    } /* else {
                                                        // Si no es exenta y no tiene período asignado,
                                                        // podemos sugerir el período actual por defecto
                                                        $currentPeriod = \App\Models\MaintenancePeriod::getCurrentPeriod();
                                                        if ($currentPeriod && !$get('maintenance_period_id')) {
                                                            $set('maintenance_period_id', $currentPeriod->id);
                                                        }
                                                    } */
                                                }),
                                        ]),
                                    Grid::make(1)
                                        ->schema([
                                            Select::make('maintenance_period_id')
                                                ->label('Período de Mantenimiento')
                                                ->relationship('maintenancePeriod', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->placeholder('Seleccionar período de mantenimiento')
                                                ->helperText(function (callable $get) {
                                                    $category = $get('category_partner');
                                                    $exemptCategories = [
                                                        'Transitorio Mayor de 75',
                                                        'Hijo de Fundador',
                                                        'Vitalicios',
                                                    ];

                                                    if (in_array($category, $exemptCategories)) {
                                                        return '✅ Categoría exenta de pago - No requiere asignar período';
                                                    }

                                                    return 'Selecciona hasta qué mes ha pagado el mantenimiento.';
                                                })
                                                ->disabled(function (callable $get) {
                                                    $category = $get('category_partner');
                                                    $exemptCategories = [
                                                        'Transitorio Mayor de 75',
                                                        'Hijo de Fundador',
                                                        'Vitalicios',
                                                    ];

                                                    return in_array($category, $exemptCategories);
                                                })
                                                ->options(function () {
                                                    // Mostrar desde 6 meses atrás hasta 12 meses adelante
                                                    $periods = \App\Models\MaintenancePeriod::query()
                                                        ->whereRaw('(year * 12 + month) >= ?', [
                                                            (now()->year * 12 + now()->month) - 6,
                                                        ])
                                                        ->whereRaw('(year * 12 + month) <= ?', [
                                                            (now()->year * 12 + now()->month) + 12,
                                                        ])
                                                        ->orderBy('year')
                                                        ->orderBy('month')
                                                        ->get()
                                                        ->pluck('name', 'id');

                                                    return $periods;
                                                })
                                                ->live()
                                                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                    $category = $get('category_partner');
                                                    $exemptCategories = [
                                                        'Transitorio Mayor de 75',
                                                        'Hijo de Fundador',
                                                        'Vitalicios',
                                                    ];

                                                    // Si es categoría exenta, limpiar el período
                                                    if (in_array($category, $exemptCategories)) {
                                                        $set('maintenance_period_id', null);
                                                    }
                                                }),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('cell_phone')
                                                ->label('Celular')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('home_phone')
                                                ->label('Teléfono de casa')
                                                ->tel()
                                                ->maxLength(20),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('district')
                                                ->label('Distrito')
                                                ->maxLength(255),
                                            TextInput::make('address')
                                                ->label('Domicilio')
                                                ->maxLength(255),
                                        ]),
                                ]),

                            Section::make('Contacto de emergencia')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('emergency_contact_name')
                                                ->label('Nombre del familiar responsable')
                                                ->nullable()
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_relationship')
                                                ->label('Parentesco o relación')
                                                ->nullable()
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_phone')
                                                ->label('Teléfono del familiar responsable')
                                                ->tel()
                                                ->nullable()
                                                ->maxLength(20),
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
                                                    ->columns(3)
                                                    ->hidden(fn (callable $get) => ! in_array('Alergias', $get('medical_conditions') ?? []))
                                                    ->reactive(),

                                                Textarea::make('allergy_details')
                                                    ->label('Detalle el tipo de alergia')
                                                    ->hidden(fn (callable $get) => ! in_array('Alergias', $get('medical_conditions') ?? []) || empty($get('allergies'))),

                                                TextInput::make('medical_conditions_other')
                                                    ->label('Especifique otra condición médica')
                                                    ->hidden(fn (callable $get) => ! in_array('Otros', $get('medical_conditions') ?? []))
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
                                                    ->hidden(fn (callable $get) => ! in_array('Otros', $get('surgical_operations') ?? [])),
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
                                                ->content(fn (callable $get) => $get('medicalRecord.weight') ? $get('medicalRecord.weight').' kg' : 'N/A'),
                                            Placeholder::make('height_summary')
                                                ->label('Talla')
                                                ->content(fn (callable $get) => $get('medicalRecord.height') ? $get('medicalRecord.height').' m' : 'N/A'),
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
                                                ->hidden(fn (callable $get) => ! in_array('Otros', $get('medicalRecord.surgical_operations') ?? [])),

                                            Placeholder::make('medications_summary_text')
                                                ->label('Medicamentos que toma')
                                                ->content(function (callable $get) {
                                                    $medications = $get('medicalRecord.medications');
                                                    if (empty($medications)) {
                                                        return 'Ninguno';
                                                    }

                                                    $formattedMedications = collect($medications)->map(function ($med) {
                                                        $details = [];
                                                        if (! empty($med['medicine'])) {
                                                            $details[] = $med['medicine'];
                                                        }
                                                        if (! empty($med['dose'])) {
                                                            $details[] = '('.$med['dose'].')';
                                                        }
                                                        if (! empty($med['schedule'])) {
                                                            $details[] = ' - '.$med['schedule'];
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
                                        ->directory('firmas-huellas')
                                        ->columnSpanFull()
                                        ->hint('Toma una foto de la firma y huella dactilar del alumno para adjuntarla en su archivo.')
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
                                            ->disabled(fn (callable $get, $livewire) => ! $get('digital_signature_and_fingerprint_path') || ! isset($livewire->record) || ! $livewire->record?->id)
                                            ->action(function ($livewire, Forms\Get $get) {
                                                $student = $livewire->record;

                                                if ($student && $student->id) {
                                                    $livewire->redirect(route('generate.affidavit.pdf', ['student' => $student->id]), navigate: false);
                                                } else {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Error de Generación')
                                                        ->body('Para generar la declaración jurada, el estudiante debe haber sido guardado previamente. Por favor, finalice el registro o acceda a la edición de un estudiante existente.')
                                                        ->send();
                                                }
                                            }),
                                    ]),
                                ]),
                        ]),
                ])
                    ->skippable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_names')
                    ->label('Apellidos')
                    ->searchable(),
                TextColumn::make('first_names')
                    ->label('Nombres')
                    ->searchable(),
                TextColumn::make('student_code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('category_partner')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PRE PAMA 50+', 'PRE PAMA 55+' => 'warning',
                        'Individual', 'Transitorio Mayor', 'Familiar - Dependiente', 'Familiar - Titular' => 'info',
                        'Transitorio Mayor de 75', 'Hijo de Fundador', 'Vitalicios' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PRE PAMA 50+' => 'PRE-PAMA 50+',
                        'PRE PAMA 55+' => 'PRE-PAMA 55+',
                        'Transitorio Mayor de 75' => 'Trans. >75',
                        'Transitorio Mayor' => 'Trans. Mayor',
                        'Familiar - Dependiente' => 'Familiar-Dep.',
                        'Familiar - Titular' => 'Familiar-Titular',
                        'Hijo de Fundador' => 'H. Fundador',
                        default => $state,
                    }),
                TextColumn::make('maintenance_status_display')
                    ->label('Mantenimiento')
                    ->getStateUsing(fn (Student $record) => $record->getMaintenanceStatusText())
                    ->badge()
                    ->color(fn (Student $record) => match (true) {
                        $record->isExemptFromMaintenance() => 'success',
                        $record->isMaintenanceCurrent() => 'success',
                        default => 'danger'
                    })
                    ->icon(fn (Student $record) => match (true) {
                        $record->isExemptFromMaintenance() => 'heroicon-m-shield-check',
                        $record->isMaintenanceCurrent() => 'heroicon-m-check-circle',
                        default => 'heroicon-m-x-circle'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_partner')
                    ->label('Categoría')
                    ->options([
                        'Vitalicios' => 'Vitalicios',
                        'Hijo de Fundador' => 'Hijo de Fundador',
                        'Transitorio Mayor de 75' => 'Transitorio Mayor de 75',
                        'Individual' => 'Individual',
                        'Transitorio Mayor' => 'Transitorio Mayor',
                        'Familiar - Dependiente' => 'Familiar - Dependiente',
                        'Familiar - Titular' => 'Familiar - Titular',
                        // PRE PAMA
                        'PRE PAMA 55+' => 'PRE PAMA 55+ (50% adicional)',
                        'PRE PAMA 50+' => 'PRE PAMA 50+ (100% adicional)',
                    ]),
                Tables\Filters\Filter::make('maintenance_status')
                    ->label('Estado de Mantenimiento')
                    ->form([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'current' => 'Al día',
                                'not_current' => 'No al día',
                                'exempt' => 'Exonerado',
                            ])
                            ->placeholder('Todos los estados'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['status'])) {
                            return $query;
                        }

                        return match ($data['status']) {
                            'exempt' => $query->whereIn('category_partner', [
                                'Vitalicios', 'Hijo de Fundador', 'Transitorio Mayor de 75',
                            ]),
                            'current' => $query->whereHas('maintenancePeriod', function ($q) {
                                $currentPeriod = \App\Models\MaintenancePeriod::getCurrentPeriod();
                                if ($currentPeriod) {
                                    $q->whereRaw('(year * 12 + month) >= ?', [
                                        $currentPeriod->year * 12 + $currentPeriod->month,
                                    ]);
                                }
                            })->whereNotIn('category_partner', [
                                'Vitalicios', 'Hijo de Fundador', 'Transitorio Mayor de 75',
                            ]),
                            'not_current' => $query->where(function ($q) {
                                $q->whereDoesntHave('maintenancePeriod')
                                    ->orWhereHas('maintenancePeriod', function ($subQ) {
                                        $currentPeriod = \App\Models\MaintenancePeriod::getCurrentPeriod();
                                        if ($currentPeriod) {
                                            $subQ->whereRaw('(year * 12 + month) < ?', [
                                                $currentPeriod->year * 12 + $currentPeriod->month,
                                            ]);
                                        }
                                    });
                            })->whereNotIn('category_partner', [
                                'Vitalicios', 'Hijo de Fundador', 'Transitorio Mayor de 75',
                            ]),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => static::getUrl('import'))
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                /* Tables\Actions\Action::make('update_pricing')
                    ->label('Actualizar Tarifa')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function (Student $record) {
                        $record->updatePricingFields();
                        Notification::make()
                            ->title('Tarifa actualizada')
                            ->body("Multiplicador: {$record->pricing_multiplier}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Student $record) => $record->shouldUpdateCategory()), */
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
            'index' => Pages\ListStudentRegisters::route('/'),
            'create' => Pages\CreateStudentRegister::route('/create'),
            'edit' => Pages\EditStudentRegister::route('/{record}/edit'),
            'import' => Pages\ImportStudents::route('/import'),
        ];
    }

    public static function getBadgeCount(): int
    {
        return Student::count();
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getBadgeCount();
    }
}
