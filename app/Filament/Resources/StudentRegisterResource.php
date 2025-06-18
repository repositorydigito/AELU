<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentRegisterResource\Pages;
use App\Filament\Resources\StudentRegisterResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle; 
use Filament\Forms\Components\Textarea; 
use Filament\Forms\Components\Placeholder; 
//use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\Action; 
use Filament\Notifications\Notification; 
use Filament\Forms\Components\Fieldset;

use Filament\Tables\Columns\TextColumn;

class StudentRegisterResource extends Resource
{
    protected static ?string $model = Student::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';    
    protected static ?string $navigationLabel = 'Registrar Alumnos';
    protected static ?string $pluralModelLabel = 'Alumnos';
    protected static ?string $modelLabel = 'Alumno';
    protected static ?int $navigationSort = 3; 
    protected static ?string $navigationGroup = 'Alumnos';  
    
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
                                                                ->required()
                                                                ->validationMessages(['required' => 'Este campo es obligatorio']),

                                                            TextInput::make('document_number')
                                                                ->label('Número de documento')
                                                                ->required()
                                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                                ->maxLength(255),
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
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxDate(now()),
                                            TextInput::make('nationality')
                                                ->label('Nacionalidad')
                                                ->required()
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('student_code')
                                                ->label('Código de Asociado')
                                                ->required()
                                                ->maxLength(255),
                                            Select::make('category_partner')
                                                ->label('Categoría de Socio')
                                                ->required()
                                                ->options([
                                                    'Dependiente' => 'Dependiente',
                                                    'Individual' => 'Individual',
                                                    'Transitorio 65 años' => 'Transitorio 65 años',
                                                    'Hijo de Fundador' => 'Hijo de Fundador',
                                                    'Vitalicios' => 'Vitalicios',
                                                ]),
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
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_relationship')
                                                ->label('Parentesco o relación')                                                
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_phone')
                                                ->label('Teléfono del familiar responsable')
                                                ->tel()                                                
                                                ->validationMessages(['required' => 'Este campo es obligatorio'])
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
                                            ->disabled(fn (callable $get, $livewire) => !$get('digital_signature_and_fingerprint_path') || !isset($livewire->record) || !$livewire->record?->id)
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
                TextColumn::make('first_names')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_names')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Documento')
                    ->searchable(),
                TextColumn::make('cell_phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('enrollments.instructorWorkshop.workshop.name')
                    ->label('Talleres Inscritos')
                    ->badge()                    
                    ->colors(['info'])
                    ->wrap(),                   
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'suspended' => 'Suspendido',
                    ]),
                Tables\Filters\SelectFilter::make('workshops')
                    ->label('Taller')
                    ->relationship('workshops', 'name')
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => static::getUrl('import'))
                    ->color('primary')
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
            'index' => Pages\ListStudentRegisters::route('/'),
            'create' => Pages\CreateStudentRegister::route('/create'),
            'edit' => Pages\EditStudentRegister::route('/{record}/edit'),
            'import' => Pages\ImportStudents::route('/import'),
        ];
    }
}
