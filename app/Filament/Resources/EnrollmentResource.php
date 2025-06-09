<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\InstructorWorkshop; // ¡IMPORTAR InstructorWorkshop!
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Support\RawJs;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Inscripciones';
    protected static ?string $pluralModelLabel = 'Inscripciones';
    protected static ?string $modelLabel = 'Inscripción';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Talleres';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Seleccionar Alumno y Taller')
                        ->schema([
                            Section::make('Información de Inscripción')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('student_id')
                                                ->label('Alumno')
                                                ->options(Student::all()->pluck('full_name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->preload(),
                                            // CAMBIO CLAVE AQUÍ: Seleccionar un InstructorWorkshop
                                            Select::make('instructor_workshop_id') // Apunta al ID de la tabla intermedia
                                                ->label('Taller y Horario')
                                                ->options(function () {
                                                    return InstructorWorkshop::with(['workshop', 'instructor'])
                                                        ->get()
                                                        ->mapWithKeys(function ($iw) {
                                                            $workshopName = $iw->workshop->name ?? 'Taller Desconocido';
                                                            $instructorName = $iw->instructor->full_name ?? 'Instructor Desconocido'; // Asume full_name en Instructor
                                                            $day = $iw->day_of_week ?? 'N/A';
                                                            $startTime = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i A') : 'N/A';
                                                            $endTime = $iw->end_time ? \Carbon\Carbon::parse($iw->end_time)->format('H:i A') : 'N/A';
                                                            
                                                            return [$iw->id => "$workshopName - $instructorName ($day $startTime - $endTime)"];
                                                        });
                                                })
                                                ->searchable()
                                                ->required()
                                                ->preload()
                                                ->placeholder('Selecciona un taller y horario'),
                                        ]),
                                    DatePicker::make('enrollment_date')
                                        ->label('Fecha de Inscripción')
                                        ->default(now())
                                        ->required(),
                                ]),
                        ]),

                    Step::make('Detalles de Pago y Estado')
                        ->schema([
                            Section::make('Detalles Financieros y Estado')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('total_amount')
                                                ->label('Monto Total (S/.)')
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->mask(RawJs::make('$money($event.target.value)'))
                                                ->stripCharacters(',')
                                                ->required(),
                                            TextInput::make('paid_amount')
                                                ->label('Monto Pagado (S/.)')
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->mask(RawJs::make('$money($event.target.value)'))
                                                ->stripCharacters(',')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                                    $total = (float) str_replace(',', '', $get('total_amount'));
                                                    $paid = (float) str_replace(',', '', $state);

                                                    if ($paid >= $total && $total > 0) { // Condición para "Pagado"
                                                        $set('payment_status', 'paid');
                                                    } elseif ($paid > 0 && $paid < $total) { // Condición para "Parcialmente Pagado"
                                                        $set('payment_status', 'partial');
                                                    } elseif ($total == 0) { // Si el monto total es 0, puede ser exonerado o no aplica
                                                        $set('payment_status', 'overdue'); // o 'N/A'
                                                    } else {
                                                        $set('payment_status', 'pending');
                                                    }
                                                }),
                                            Radio::make('payment_status')
                                                ->label('Estado de Pago')
                                                ->options([
                                                    'pending' => 'Pendiente',
                                                    'partial' => 'Parcialmente Pagado',
                                                    'paid' => 'Pagado',
                                                    'overdue' => 'Vencido',
                                                ])
                                                ->required()
                                                ->inline(),
                                            Radio::make('status')
                                                ->label('Estado de Inscripción')
                                                ->options([
                                                    'enrolled' => 'Inscrito',
                                                    'completed' => 'Completado',
                                                    'dropped' => 'Abandonado',
                                                    'pending' => 'Pendiente',
                                                ])
                                                ->required()
                                                ->inline(),
                                        ]),
                                    Textarea::make('notes')
                                        ->label('Notas Adicionales')
                                        ->maxLength(65535)
                                        ->columnSpanFull()
                                        ->nullable(),
                                ]),
                        ]),

                    Step::make('Resumen de Inscripción')
                        ->schema([
                            Section::make('Resumen General')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Placeholder::make('student_summary')
                                                ->label('Alumno Inscrito')
                                                ->content(fn ($get) => Student::find($get('student_id'))?->full_name ?? 'N/A'),
                                            // Mostrar detalles del InstructorWorkshop seleccionado
                                            Placeholder::make('instructor_workshop_summary')
                                                ->label('Taller y Horario')
                                                ->content(function ($get) {
                                                    $iw_id = $get('instructor_workshop_id');
                                                    if (!$iw_id) {
                                                        return 'No se ha seleccionado taller.';
                                                    }
                                                    $iw = InstructorWorkshop::with(['workshop', 'instructor'])->find($iw_id);
                                                    if (!$iw) {
                                                        return 'Taller y Horario no encontrado.';
                                                    }
                                                    $workshopName = $iw->workshop->name ?? 'Taller Desconocido';
                                                    $instructorName = $iw->instructor->full_name ?? 'Instructor Desconocido';
                                                    $day = $iw->day_of_week ?? 'N/A';
                                                    $startTime = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i A') : 'N/A';
                                                    $endTime = $iw->end_time ? \Carbon\Carbon::parse($iw->end_time)->format('H:i A') : 'N/A';

                                                    return "$workshopName por $instructorName ($day $startTime - $endTime)";
                                                }),
                                            Placeholder::make('enrollment_date_summary')
                                                ->label('Fecha de Inscripción')
                                                ->content(fn ($get) => \Carbon\Carbon::parse($get('enrollment_date'))->format('d/m/Y') ?? 'N/A'),
                                        ]),
                                ]),
                            Section::make('Resumen Financiero')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Placeholder::make('total_amount_summary')
                                                ->label('Monto Total')
                                                ->content(fn ($get) => 'S/. ' . number_format((float) str_replace(',', '', $get('total_amount')), 2)),
                                            Placeholder::make('paid_amount_summary')
                                                ->label('Monto Pagado')
                                                ->content(fn ($get) => 'S/. ' . number_format((float) str_replace(',', '', $get('paid_amount')), 2)),
                                            Placeholder::make('payment_status_summary')
                                                ->label('Estado de Pago')
                                                ->content(fn ($get) => $get('payment_status') ?? 'N/A'),
                                            Placeholder::make('status_summary')
                                                ->label('Estado de Inscripción')
                                                ->content(fn ($get) => $get('status') ?? 'N/A'),
                                        ]),
                                    Placeholder::make('notes_summary')
                                        ->label('Notas Adicionales')
                                        ->content(fn ($get) => $get('notes') ?? 'Sin notas'),
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
                TextColumn::make('student.full_name')
                    ->label('Alumno')
                    ->searchable()
                    ->sortable(),
                // Mostrar el nombre del taller y horario desde instructorWorkshop
                TextColumn::make('instructorWorkshop.workshop.name')
                    ->label('Taller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instructorWorkshop.day_of_week')
                    ->label('Día')
                    ->sortable(),
                TextColumn::make('instructorWorkshop.start_time')
                    ->label('Inicio')
                    ->time('H:i A') // Formato de hora con AM/PM
                    ->sortable(),
                TextColumn::make('instructorWorkshop.instructor.full_name')
                    ->label('Profesor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollment_date')
                    ->label('Fecha Inscripción')
                    ->date('d/m/Y')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Estado Inscripción')
                    ->colors([
                        'success' => 'Inscrito',
                        'warning' => 'Completado',
                        'info' => 'Abandonado',
                        'danger' => 'Pendiente',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enrolled' => 'Inscrito',
                        'completed' => 'Completado',
                        'dropped' => 'Abandonado',
                        'pending' => 'Pendiente',
                        default => $state, 
                    }),
                BadgeColumn::make('payment_status')
                    ->label('Estado Pago')
                    ->colors([
                        'danger' => 'Pendiente',
                        'warning' => 'Parcialmente Pagado',
                        'success' => 'Pagado',
                        'info' => 'Vencido',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'partial' => 'Parcialmente Pagado',
                        'paid' => 'Pagado',
                        'overdue' => 'Vencido',
                        default => $state, 
                    }),
                TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Monto Pagado')
                    ->money('PEN')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('instructor_workshop_id')
                    ->label('Filtrar por Taller y Horario')
                    ->options(function () {
                        return InstructorWorkshop::with(['workshop', 'instructor'])
                            ->get()
                            ->mapWithKeys(function ($iw) {
                                $workshopName = $iw->workshop->name ?? 'Taller Desconocido';
                                $instructorName = $iw->instructor->full_name ?? 'Instructor Desconocido';
                                $day = $iw->day_of_week ?? 'N/A';
                                $startTime = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i A') : 'N/A';
                                return [$iw->id => "$workshopName ($day $startTime) - $instructorName"];
                            });
                    })
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filtrar por Estado de Inscripción')
                    ->options([
                        'enrolled' => 'Inscrito',
                        'completed' => 'Completado',
                        'dropped' => 'Abandonado',
                        'pending' => 'Pendiente',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Filtrar por Estado de Pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcialmente Pagado',
                        'paid' => 'Pagado',
                        'overdue' => 'Vencido',
                    ]),
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

    // Asegúrate de que el modelo Instructor tenga este accesor
    // public function getFullNameAttribute(): string
    // {
    //     return "{$this->first_name} {$this->last_name}"; // Ajusta a tus nombres de columnas
    // }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}