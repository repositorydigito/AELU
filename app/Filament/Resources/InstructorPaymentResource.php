<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorPaymentResource\Pages;
use App\Models\InstructorPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class InstructorPaymentResource extends Resource
{
    protected static ?string $model = InstructorPayment::class;

    protected static ?string $navigationLabel = 'Pago de Profesores';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static ?string $modelLabel = 'Pago';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Tesorería';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('monthly_period_id')
                                    ->relationship('monthlyPeriod', 'year')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->year} - ".\Carbon\Carbon::create()->month($record->month)->monthName)
                                    ->label('Periodo Mensual')
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\Select::make('instructor_id')
                                    ->relationship('instructor', 'first_names')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}")
                                    ->label('Instructor')
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('workshop_info')
                                    ->label('Taller y Horario')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        if (! $record || ! $record->instructorWorkshop) {
                                            return 'N/A';
                                        }

                                        $workshop = $record->instructorWorkshop;
                                        $dayNames = [
                                            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                        ];

                                        $dayOfWeek = $dayNames[$workshop->day_of_week] ?? 'Desconocido';
                                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');

                                        return "{$workshop->workshop->name} ({$dayOfWeek} {$startTime}-{$endTime})";
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('payment_type')
                                    ->label('Tipo de Pago')
                                    ->disabled()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'volunteer' => 'Voluntario (% de ingresos)',
                                        'hourly' => 'Por Horas (tarifa fija)',
                                        default => $state,
                                    }),

                                Forms\Components\TextInput::make('calculated_amount')
                                    ->label('Monto Calculado a Pagar')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->disabled()
                                    ->dehydrated(true),
                            ]),
                    ]),

                Forms\Components\Section::make('Detalles para Voluntarios')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_students')
                                    ->label('Total Estudiantes')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\TextInput::make('monthly_revenue')
                                    ->label('Ingreso Mensual Recaudado')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->disabled(),

                                Forms\Components\TextInput::make('applied_volunteer_percentage')
                                    ->label('Porcentaje Aplicado')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 2).'%' : 'N/A'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record?->payment_type === 'volunteer'),

                Forms\Components\Section::make('Detalles para Instructores por Horas')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_hours')
                                    ->label('Total Horas Dictadas')
                                    ->numeric()
                                    ->suffix(' horas')
                                    ->disabled(),

                                Forms\Components\TextInput::make('applied_hourly_rate')
                                    ->label('Tarifa por Hora Aplicada')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->disabled(),

                                Forms\Components\TextInput::make('calculated_hourly_total')
                                    ->label('Cálculo')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        if (! $record || $record->payment_type !== 'hourly') {
                                            return 'N/A';
                                        }
                                        $hours = $record->total_hours ?? 0;
                                        $rate = $record->applied_hourly_rate ?? 0;

                                        return "{$hours} horas × S/ {$rate} = S/ ".number_format($hours * $rate, 2);
                                    }),
                            ]),
                    ])
                    ->visible(fn ($record) => $record?->payment_type === 'hourly'),

                Forms\Components\Section::make('Estado de Pago')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_status')
                                    ->label('Estado de Pago')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'paid' => 'Pagado',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),

                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Fecha de Pago')
                                    ->nullable(),
                            ]),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->helperText('Número de recibo, comprobante o documento de pago')
                            ->placeholder('Ej: REC-001, VOUCHER-123')
                            ->maxLength(255)
                            ->visible(fn ($record) => $record?->payment_status === 'paid'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->nullable()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('instructor.last_names')
                    ->label('Apellidos')
                    ->searchable(['instructors.last_names'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('instructor.first_names')
                    ->label('Nombres')
                    ->searchable(['instructors.first_names'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('workshop_schedule')
                    ->label('Taller y Horario')
                    ->getStateUsing(function (InstructorPayment $record) {
                        $workshop = $record->instructorWorkshop;
                        if (! $workshop) {
                            return 'N/A';
                        }

                        $dayNames = [
                            0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié',
                            4 => 'Jue', 5 => 'Vie', 6 => 'Sáb',
                        ];

                        $dayOfWeek = $dayNames[$workshop->day_of_week] ?? '?';
                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');

                        return $workshop->workshop->name;
                    })
                    ->description(function (InstructorPayment $record) {
                        $workshop = $record->instructorWorkshop;
                        if (! $workshop) {
                            return '';
                        }

                        $dayNames = [
                            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                        ];

                        $dayOfWeek = $dayNames[$workshop->day_of_week] ?? 'Desconocido';
                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');

                        return "{$dayOfWeek} {$startTime}-{$endTime}";
                    })
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('period_info')
                    ->label('Período')
                    ->getStateUsing(fn (InstructorPayment $record) => \Carbon\Carbon::create()->month($record->monthlyPeriod->month)
                        ->year($record->monthlyPeriod->year)
                        ->format('m/Y')
                ),

                Tables\Columns\BadgeColumn::make('payment_type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'volunteer',
                        'info' => 'hourly',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'volunteer' => 'Voluntario',
                        'hourly' => 'Por Horas',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('rate_or_percentage')
                    ->label('Tarifa/Porcentaje')
                    ->getStateUsing(function (InstructorPayment $record) {
                        if ($record->payment_type === 'volunteer') {
                            return $record->applied_volunteer_percentage
                                ? number_format($record->applied_volunteer_percentage * 100) . '%'
                                : 'N/A';
                        } elseif ($record->payment_type === 'hourly') {
                            return $record->applied_hourly_rate
                                ? 'S/ ' . number_format($record->applied_hourly_rate, 2)
                                : 'N/A';
                        }
                        return 'N/A';
                    }),

                Tables\Columns\TextColumn::make('rate_or_percentage')
                    ->label('Tarifa/Porcentaje')
                    ->getStateUsing(function (InstructorPayment $record) {
                        if ($record->payment_type === 'volunteer') {
                            return $record->applied_volunteer_percentage
                                ? number_format($record->applied_volunteer_percentage * 100) . '%'
                                : 'N/A';
                        } elseif ($record->payment_type === 'hourly') {
                            return $record->applied_hourly_rate
                                ? 'S/ ' . number_format($record->applied_hourly_rate, 2)
                                : 'N/A';
                        }
                        return 'N/A';
                    }),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Monto Base')
                    ->getStateUsing(function (InstructorPayment $record) {
                        if ($record->payment_type === 'volunteer' && $record->applied_volunteer_percentage && $record->calculated_amount > 0) {
                            $baseAmount = $record->calculated_amount / $record->applied_volunteer_percentage;
                            return 'S/ ' . number_format($baseAmount, 2);
                        } elseif ($record->payment_type === 'hourly' && $record->applied_hourly_rate && $record->calculated_amount > 0) {
                            $hours = $record->calculated_amount / $record->applied_hourly_rate;
                            return number_format($hours, 2) . ' horas';
                        }
                        return 'N/A';
                    })
                    ->tooltip(function (InstructorPayment $record) {
                        if ($record->payment_type === 'volunteer') {
                            return 'Monto total sobre el cual se aplicó el ' . number_format($record->applied_volunteer_percentage * 100) . '%';
                        } elseif ($record->payment_type === 'hourly') {
                            return 'Horas trabajadas a S/ ' . number_format($record->applied_hourly_rate, 2) . ' por hora';
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('calculated_amount')
                    ->label('Monto a Pagar')
                    ->prefix('S/ ')
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                Tables\Columns\IconColumn::make('payment_status')
                    ->label('Estado')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'paid' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente de pago',
                        'paid' => 'Pagado',
                        default => 'Estado desconocido',
                    }),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('monthly_period_id')
                    ->relationship('monthlyPeriod', 'year')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->year.' - '.\Carbon\Carbon::create()->month($record->month)->translatedFormat('F')
                    )
                    ->label('Período')
                    ->native(false),

                Tables\Filters\SelectFilter::make('payment_type')
                    ->options([
                        'volunteer' => 'Voluntario',
                        'hourly' => 'Por Horas',
                    ])
                    ->label('Tipo de Pago')
                    ->native(false),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                    ])
                    ->label('Estado de Pago')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Marcar como Pagado')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->helperText('Número de recibo, comprobante o documento de pago')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (InstructorPayment $record, array $data) {
                        $record->update([
                            'payment_status' => 'paid',
                            'payment_date' => now()->toDateString(),
                            'document_number' => $data['document_number'],
                        ]);
                    })
                    ->visible(fn (InstructorPayment $record) => $record->payment_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pago')
                    ->modalDescription(fn (InstructorPayment $record) => 'Confirma el pago de S/ '.number_format($record->calculated_amount, 2)." para {$record->instructor->first_names} {$record->instructor->last_names}"
                    )
                    ->modalSubmitActionLabel('Confirmar Pago')
                    ->modalCancelActionLabel('Cancelar'),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

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
            'index' => Pages\ListInstructorPayments::route('/'),
            'create' => Pages\CreateInstructorPayment::route('/create'),
            'edit' => Pages\EditInstructorPayment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Los pagos se generan automáticamente via StudentEnrollmentObserver
    }
}
