<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    // protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Listado de Alumnos';
    // protected static ?string $pluralModelLabel = 'Alumnos';
    // protected static ?string $modelLabel = 'Alumno';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Alumnos';    

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
                                    Forms\Components\TextInput::make('student_code')
                                        ->label('Código de Estudiante')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Forms\Components\Select::make('status')
                                        ->label('Estado')
                                        ->options([
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            'suspended' => 'Suspendido',
                                        ])
                                        ->default('active')
                                        ->required(),
                                ]),
                        ]),

                    Step::make('Información de contacto')
                        ->icon('heroicon-o-phone')
                        ->schema([
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
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('emergency_contact_name')
                                        ->label('Contacto de emergencia')
                                        ->maxLength(255)
                                        ->nullable(),
                                    Forms\Components\TextInput::make('emergency_contact_phone')
                                        ->label('Teléfono de emergencia')
                                        ->tel()
                                        ->maxLength(255)
                                        ->nullable(),
                                ]),
                            FileUpload::make('photo')
                                ->label('Foto')
                                ->image()
                                ->directory('students-photos')
                                ->nullable(),
                        ]),

                    Step::make('Registro completo')
                        ->icon('heroicon-o-check-circle')
                        ->description('Revisa y confirma la información.')
                        ->schema([
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
                TextColumn::make('workshops.name')
                    ->label('Talleres')
                    ->badge()
                    ->separator(',')
                    ->colors(['info'])
                    ->wrap()
                    ->limit(50)
                    ->tooltip(function (Student $record) {
                        return $record->workshops->pluck('name')->implode(', ');
                    }),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),            
        ];
    }
    
}
