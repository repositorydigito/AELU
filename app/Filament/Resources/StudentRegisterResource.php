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
use Filament\Forms\Components\Toggle; // Add Toggle for Yes/No questions
use Filament\Forms\Components\Textarea; // Add Textarea for detailed input
use Filament\Forms\Components\Placeholder; // Import Placeholder for displaying read-only data
use Filament\Forms\Components\Actions\Action; // Import Action for custom buttons
use Filament\Notifications\Notification; // For showing notifications

class StudentRegisterResource extends Resource
{
    protected static ?string $model = Student::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';    
    protected static ?string $navigationLabel = 'Registrar Alumnos';
    protected static ?string $pluralModelLabel = 'Alumnos';
    // protected static ?string $modelLabel = 'Alumno';
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
                                            TextInput::make('last_names')
                                                ->label('Apellidos')
                                                ->required()
                                                ->maxLength(255),
                                            FileUpload::make('photo')
                                                ->label('Foto')
                                                ->image()
                                                ->directory('student-photos')
                                                ->columnSpan(1),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('first_names')
                                                ->label('Nombres')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('document_type')
                                                ->label('Tipo de documento')
                                                ->options([
                                                    'DNI' => 'DNI',
                                                    'CE' => 'Carné de Extranjería',
                                                    'Pasaporte' => 'Pasaporte',
                                                ])
                                                ->required(),
                                            TextInput::make('document_number')
                                                ->label('Número de documento')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('birth_date')
                                                ->label('Fecha de Nacimiento')
                                                ->required()
                                                ->maxDate(now()),
                                            TextInput::make('nationality')
                                                ->label('Nacionalidad')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('student_code')
                                                ->label('Código de Asociado')
                                                ->maxLength(255),
                                            Select::make('category_partner')
                                                ->label('Categoría de Socio')
                                                ->options([
                                                    'Dependiente' => 'Dependiente',
                                                    'Individual' => 'Individual',
                                                    'Transitorio 75 años' => 'Transitorio 75 años',
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
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_relationship')
                                                ->label('Parentesco o relación')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('emergency_contact_phone')
                                                ->label('Teléfono del familiar responsable')
                                                ->tel()
                                                ->required()
                                                ->maxLength(20),
                                        ]),
                                ]),
                        ]),

                    Step::make('Ficha médica')
                        ->schema([
                            Section::make('Ficha Médica')
                                ->relationship('medicalRecord')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('weight')
                                                ->label('Peso')
                                                ->numeric()
                                                ->suffix('kg'),
                                            Radio::make('gender')
                                                ->label('Género')
                                                ->options([
                                                    'Femenino' => 'Femenino',
                                                    'Masculino' => 'Masculino',
                                                    'Prefiero no responder' => 'Prefiero no responder',
                                                ])
                                                ->inline(),
                                            Select::make('health_insurance')
                                                ->label('Seguro Médico')
                                                ->options([
                                                    'ESSALUD' => 'ESSALUD',
                                                    'SIS' => 'SIS',
                                                    'RIMAC' => 'RIMAC',
                                                    'Pacífico' => 'Pacífico',
                                                    'MAPFRE' => 'MAPFRE',
                                                    'La Positiva' => 'La Positiva',
                                                ]),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('height')
                                                ->label('Talla')
                                                ->numeric()
                                                ->suffix('m'),
                                            Radio::make('smokes')
                                                ->label('¿Fuma?')
                                                ->options([
                                                    'Sí' => 'Sí',
                                                    'No' => 'No',
                                                ])
                                                ->inline()
                                                ->reactive(),
                                            TextInput::make('cigarettes_per_day')
                                                ->label('¿Cuántos cigarrillos al día?')
                                                ->numeric()
                                                ->hidden(fn (callable $get) => $get('smokes') !== 'Sí')
                                                ->maxLength(255),
                                        ]),

                                    CheckboxList::make('medical_conditions')
                                        ->label('Condiciones médicas que padece')
                                        ->options([
                                            'Hipertension Arterial' => 'Hipertensión Arterial',
                                            'Asma, Bronquitis' => 'Asma, Bronquitis',
                                            'Gastritis, Ulceras' => 'Gastritis, Úlceras',
                                            'Diabetes' => 'Diabetes',
                                            'Artrosis, Artritis' => 'Artrosis, Artritis',
                                            'Estrés, Ansiedad, Depresión' => 'Estrés, Ansiedad, Depresión',
                                            'Taquicardia, Angina de Pecho' => 'Taquicardia, Angina de Pecho',
                                            'ECV (Enfermedad Cardio Vascular)' => 'ECV (Enfermedad Cardio Vascular)',
                                            'Hipoacusia (Sordera)' => 'Hipoacusia (Sordera)',
                                        ])
                                        ->columns(2),

                                    CheckboxList::make('allergies')
                                        ->label('Alergias')
                                        ->options([
                                            'Alimentos' => 'Alimentos',
                                            'Medicinas' => 'Medicinas',
                                            'Otros' => 'Otros',
                                        ])
                                        ->columns(3)
                                        ->reactive(),
                                    Textarea::make('allergy_details')
                                        ->label('Detalle el tipo de alergia')
                                        ->hidden(fn (callable $get) => empty($get('allergies'))),

                                    CheckboxList::make('surgical_operations')
                                        ->label('Operaciones a las que se ha sometido')
                                        ->options([
                                            'Ninguna' => 'Ninguna',
                                            'Al Corazón' => 'Al Corazón',
                                            'Al Cerebro' => 'Al Cerebro',
                                            'A la Vista' => 'A la Vista',
                                            'A la Columna' => 'A la Columna',
                                            'A la Rodilla' => 'A la Rodilla',
                                            'A la Cadera' => 'A la Cadera',
                                            'Otros' => 'Otros',
                                        ])
                                        ->columns(2)
                                        ->reactive(),
                                    TextInput::make('surgical_operation_details')
                                        ->label('Especificar')
                                        ->hidden(fn (callable $get) => !in_array('Otros', $get('surgical_operations') ?? [])),

                                    Repeater::make('medications')
                                        ->relationship('medications')
                                        ->label('Medicamentos que toma')
                                        ->schema([
                                            TextInput::make('medicine')
                                                ->label('Medicina')
                                                ->required(),
                                            TextInput::make('dose')
                                                ->label('Dosis'),
                                            Select::make('schedule')
                                                ->label('Horario')
                                                ->options([
                                                    'Mañana' => 'Mañana',
                                                    'Tarde' => 'Tarde',
                                                    'Noche' => 'Noche',
                                                ]),
                                        ])
                                        ->columns(3)
                                        ->addActionLabel('Registrar Medicamento')
                                        ->defaultItems(0)
                                        ->itemLabel(fn (array $state): ?string => $state['medicine'] ?? null),
                                ]),
                        ]),

                    // Nuevo paso para el resumen de la Ficha Personal y adjuntar firma/huella
                    Step::make('Declaración jurada y resumen')
                        ->description(fn (callable $get) => $get('firma_huella_adjuntada') ? 'Completado' : 'Pendiente de firma y huella')
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
                                            // **** CORRECCIONES EN LOS PLACEHOLDERS PARA LEER DE medicalRecord ****
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
                                            Placeholder::make('allergies_summary') // Nombre del campo en el modelo es 'allergies'
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

                                            // **** CORRECCIÓN EN EL REPEATER DE MEDICAMENTOS PARA LEER DE medicalRecord ****
                                            Repeater::make('medications_summary') // Nombre del Repeater
                                                ->label('Medicamentos que toma')
                                                ->disabled()
                                                // ->relationship('medications')                                                 
                                                ->schema([
                                                    Placeholder::make('medicine')
                                                        ->label('Medicina'),
                                                    Placeholder::make('dose')
                                                        ->label('Dosis'),
                                                    Placeholder::make('schedule')
                                                        ->label('Horario'),
                                                ])
                                                ->columns(3)
                                                ->defaultItems(0)
                                                // Corregir la condición hidden para el repeater
                                                ->hidden(fn (callable $get) => empty($get('medicalRecord.medications'))),
                                        ]),
                                ]),
                            Section::make('Firma y Huella Digital')
                                // **** CORRECCIÓN CLAVE AQUÍ ****
                                ->relationship('affidavit') // <-- ¡Agregado! Indica que este campo va a Affidavit
                                ->schema([
                                    FileUpload::make('digital_signature_and_fingerprint_path') // <-- Nombre de la columna en el modelo Affidavit
                                        ->label('Arrastra y suelta tus archivos o subelos de tu computadora')
                                        ->image() // Mantén .image() si solo quieres imágenes, si no, usa acceptedFileTypes
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
                                ]),
                        ]),
                ])                
                ->columnSpanFull(),                 
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table            
            ->filters([
                // Puedes añadir filtros aquí si lo necesitas
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        ];
    }
}
