<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorPaymentResource\Pages;
use App\Filament\Resources\InstructorPaymentResource\RelationManagers;
use App\Models\InstructorPayment;
use App\Models\WorkshopClass;
use App\Models\StudentEnrollment;
use App\Models\EnrollmentClass;
use App\Models\InstructorWorkshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class InstructorPaymentResource extends Resource
{
    protected static ?string $model = InstructorPayment::class;
    protected static ?string $navigationLabel = 'Pago de Profesores';
    protected static ?string $pluralModelLabel = 'Pagos';
    protected static ?string $modelLabel = 'Pago';
    protected static ?int $navigationSort = 13;
    protected static ?string $navigationGroup = 'Tesorería';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('monthly_period_id')
                    ->relationship('monthlyPeriod', 'year')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->month}/{$record->year}")
                    ->label('Periodo Mensual')
                    ->disabled() // Disabled as it's set by generation
                    ->dehydrated(true)
                    ->columnSpan(1),
                Forms\Components\Select::make('instructor_id')
                    ->relationship('instructor', 'first_names')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}")
                    ->label('Instructor')
                    ->disabled() // Disabled as it's set by generation
                    ->dehydrated(true)
                    ->columnSpan(1),
                Forms\Components\Select::make('instructor_workshop_id')
                    ->label('Taller del Instructor')
                    ->relationship('instructorWorkshop', 'id') // Relate directly
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $workshopName = $record->workshop->name;
                        $dayOfWeek = [
                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
                            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
                        ][$record->day_of_week] ?? 'Desconocido';
                        $startTime = \Carbon\Carbon::parse($record->start_time)->format('H:i');
                        $endTime = \Carbon\Carbon::parse($record->end_time)->format('H:i');
                        $modality = $record->is_volunteer ? 'Voluntario' : 'Por Horas';
                        return "{$workshopName} ({$dayOfWeek} {$startTime}-{$endTime}) - {$modality}";
                    })
                    ->disabled() // Disabled as it's set by generation
                    ->dehydrated(true)
                    ->columnSpan(1),
                Forms\Components\TextInput::make('payment_type')
                    ->label('Modalidad')
                    ->disabled()
                    ->dehydrated(true),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_students')
                            ->label('Total Estudiantes (Voluntario)')
                            ->numeric(),
                        Forms\Components\TextInput::make('monthly_revenue')
                            ->label('Ingreso Mensual (Voluntario)')
                            ->numeric()
                            ->prefix('S/'),
                        Forms\Components\TextInput::make('volunteer_percentage')
                            ->label('Porcentaje Voluntario')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('total_hours')
                            ->label('Total Horas (Por Horas)')
                            ->numeric()
                            ->suffix(' horas'),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Tarifa por Hora (Por Horas)')
                            ->numeric()
                            ->prefix('S/'),
                    ]),
                Forms\Components\TextInput::make('calculated_amount')
                    ->label('Monto Calculado a Pagar')
                    ->numeric()
                    ->prefix('S/')
                    ->disabled()
                    ->dehydrated(true),
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
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->nullable()
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('instructor.first_names')
                    ->label('Profesor')
                    ->formatStateUsing(fn ($record) => "{$record->instructor->first_names} {$record->instructor->last_names}")
                    ->searchable(['instructor.first_names', 'instructor.last_names']),
                Tables\Columns\TextColumn::make('instructorWorkshop.workshop.name')
                    ->label('Taller')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('monthlyPeriod.year')
                    ->label('Periodo')
                    ->formatStateUsing(fn ($record) => "{$record->monthlyPeriod->month}/{$record->monthlyPeriod->year}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'volunteer' => 'success',
                        'hourly' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'volunteer' => 'Voluntario',
                        'hourly' => 'Por horas',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('monto_recaudado_condicional')
                    ->label('Monto Recaudado')
                    ->money('PEN')
                    ->getStateUsing(function (InstructorPayment $record): float {
                        if ($record->payment_type === 'volunteer') {
                            return $record->monthly_revenue ?? 0.00;
                        }
                        return $record->calculated_amount ?? 0.00;
                    }),
                Tables\Columns\TextColumn::make('volunteer_percentage')
                    ->label('Porcentaje')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 0) . '%'),
                Tables\Columns\TextColumn::make('calculated_amount')
                    ->label('Monto a Pagar')
                    ->money('PEN')
                    ->sortable(),
                Tables\Columns\IconColumn::make('payment_status')
                    ->label('Estado')
                    ->icons([
                        'heroicon-o-x-circle' => 'pending',
                        'heroicon-o-check-circle' => 'paid',
                    ])
                    ->colors([
                        'danger' => 'pending',
                        'success' => 'paid',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('monthly_period_id')
                    ->relationship('monthlyPeriod', 'year')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->month}/{$record->year}")
                    ->label('Filtrar por Periodo')
                    ->native(false),
                Tables\Filters\SelectFilter::make('instructor_id')
                    ->relationship('instructor', 'first_names')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}")
                    ->label('Filtrar por Instructor')
                    ->native(false),
                Tables\Filters\SelectFilter::make('payment_type')
                    ->options([
                        'volunteer' => 'Voluntario',
                        'hourly' => 'Por Horas',
                    ])
                    ->label('Filtrar por Tipo de Pago')
                    ->native(false),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                    ])
                    ->label('Filtrar por Estado de Pago')
                    ->native(false),
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
            'index' => Pages\ListInstructorPayments::route('/'),
            'create' => Pages\CreateInstructorPayment::route('/create'),
            'edit' => Pages\EditInstructorPayment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
