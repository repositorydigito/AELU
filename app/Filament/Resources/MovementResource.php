<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovementResource\Pages;
use App\Filament\Resources\MovementResource\RelationManagers;
use App\Models\Movement;
use App\Models\MovementCategory;
use App\Models\InstructorPayment;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
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

class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;
    protected static ?string $navigationLabel = 'Movimientos';
    protected static ?string $pluralModelLabel = 'Movimientos';
    protected static ?string $modelLabel = 'Movimiento';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationGroup = 'Tesorería';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Detalles del Movimiento')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('movement_category_id')
                        ->relationship('category', 'name')
                        ->required()
                        ->label('Categoría')
                        ->placeholder('Selecciona una categoría')
                        ->options(MovementCategory::all()->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('movable_id', null);
                            $set('movable_type', null);
                            $set('concept', null); // Limpia el concepto también al cambiar la categoría
                        }),

                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),

                    Forms\Components\TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->prefix('S/.')
                        ->step(0.01)
                        ->minValue(0.01),

                    Forms\Components\Textarea::make('notes')
                        ->label('Observaciones')
                        ->placeholder('Detalles adicionales sobre el movimiento.')
                        ->rows(3)
                        ->columnSpan('full'),

                    // --- Lógica Condicional para el campo 'Concepto' y 'movable' ---

                    // Campo de selección para Pago a Profesor
                    Forms\Components\Select::make('related_instructor_payment_id')
                        ->label('Seleccionar Pago a Profesor')
                        ->options(
                            fn (Get $get) => InstructorPayment::with('instructor', 'monthlyPeriod')
                                ->where('payment_status', 'paid')
                                ->get()
                                ->mapWithKeys(function ($payment) {
                                    return [$payment->id => "{$payment->instructor->first_names} {$payment->instructor->last_names} ({$payment->monthlyPeriod->month}/{$payment->monthlyPeriod->year} - S/.{$payment->calculated_amount})"];
                                })
                        )
                        ->visible(fn (Get $get) => MovementCategory::find($get('movement_category_id'))?->name === 'Pago a profesor')
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $payment = InstructorPayment::find($state);
                                $set('movable_id', $payment->id);
                                $set('movable_type', $payment->getMorphClass());
                                $set('concept', "Pago a Prof. {$payment->instructor->first_names} {$payment->instructor->last_names}");
                            } else {
                                $set('movable_id', null);
                                $set('movable_type', null);
                                $set('concept', null);
                            }
                        })
                        ->dehydrated(false), // No guardar este campo directamente en la BD

                    // Campo de selección para Cobro de Taller
                    Forms\Components\Select::make('related_student_enrollment_id')
                        ->label('Seleccionar Inscripción de Taller')
                        ->options(
                            fn (Get $get) => StudentEnrollment::with(['student', 'instructorWorkshop.workshop', 'monthlyPeriod'])
                                ->where('payment_status', 'completed')
                                ->get()
                                ->mapWithKeys(function ($enrollment) {
                                    return [$enrollment->id => "{$enrollment->student->first_names} {$enrollment->student->last_names} - {$enrollment->instructorWorkshop->workshop->name} ({$enrollment->monthlyPeriod->month}/{$enrollment->monthlyPeriod->year} - S/.{$enrollment->total_amount})"];
                                })
                        )
                        ->visible(fn (Get $get) => MovementCategory::find($get('movement_category_id'))?->name === 'Cobro de taller')
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $enrollment = StudentEnrollment::find($state);
                                $set('movable_id', $enrollment->id);
                                $set('movable_type', $enrollment->getMorphClass());
                                $set('concept', "Cobro de Taller: {$enrollment->instructorWorkshop->workshop->name} ({$enrollment->instructorWorkshop->day_of_week} {$enrollment->instructorWorkshop->start_time->format('H:i')})");
                            } else {
                                $set('movable_id', null);
                                $set('movable_type', null);
                                $set('concept', null);
                            }
                        })
                        ->dehydrated(false),

                    // Campo de selección para Compra materiales
                    Forms\Components\Select::make('related_workshop_id')
                        ->label('Seleccionar Taller para Materiales')
                        ->options(Workshop::all()->pluck('name', 'id'))
                        ->visible(fn (Get $get) => MovementCategory::find($get('movement_category_id'))?->name === 'Compra materiales')
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $workshop = Workshop::find($state);
                                $set('movable_id', $workshop->id);
                                $set('movable_type', $workshop->getMorphClass());
                                $set('concept', "Compra materiales para Taller: {$workshop->name}");
                            } else {
                                $set('movable_id', null);
                                $set('movable_type', null);
                                $set('concept', null);
                            }
                        })
                        ->dehydrated(false),

                    // Campo 'Concepto' general - Siempre presente, pero su comportamiento es condicional
                    Forms\Components\TextInput::make('concept')
                        ->label('Concepto')
                        ->required(fn (Get $get) => in_array(MovementCategory::find($get('movement_category_id'))?->name, ['Otros ingresos', 'Otros egresos']))
                        // El campo es de solo lectura si la categoría NO es "Otros ingresos" u "Otros egresos"
                        ->readOnly(fn (Get $get) => !in_array(MovementCategory::find($get('movement_category_id'))?->name, ['Otros ingresos', 'Otros egresos']))
                        ->placeholder(fn (Get $get) => in_array(MovementCategory::find($get('movement_category_id'))?->name, ['Otros ingresos', 'Otros egresos']) ? 'Escribe un concepto para el movimiento manual' : 'Se autocompleta al seleccionar el elemento relacionado')
                        ->columnSpan('full'), // Opcional: para que ocupe todo el ancho

                    // Campos ocultos para la relación polimórfica (movable_id y movable_type)
                    Forms\Components\Hidden::make('movable_id'),
                    Forms\Components\Hidden::make('movable_type'),

                ]),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.type') // Ahora es TextColumn
                    ->label('Movimiento')
                    ->badge()
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) { // Formatear el texto
                        'income' => 'Ingreso',
                        'expense' => 'Egreso',
                        default => $state,
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'income' => 'heroicon-s-arrow-trending-up',
                        'expense' => 'heroicon-s-arrow-trending-down',
                        default => '',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->prefix('S/.')
                    ->money('PEN') // O la moneda que uses
                    ->sortable(),
                Tables\Columns\TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Observaciones')
                    ->wrap(), // Permite que el texto se ajuste si es largo
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_category_id')
                    ->relationship('category', 'name')
                    ->label('Filtrar por Categoría')
                    ->native(false),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('Desde'),
                        Forms\Components\DatePicker::make('date_to')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(), // Para ver detalles, incluyendo movable_id/type
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
            'index' => Pages\ListMovements::route('/'),
            'create' => Pages\CreateMovement::route('/create'),
            'edit' => Pages\EditMovement::route('/{record}/edit'),
        ];
    }
}
