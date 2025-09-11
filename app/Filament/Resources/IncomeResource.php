<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeResource\Pages;
use App\Models\StudentEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomeResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static ?string $navigationLabel = 'Ingresos';

    protected static ?string $pluralModelLabel = 'Ingresos';

    protected static ?string $modelLabel = 'Ingreso';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Tesorería';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Mostrar TODAS las inscripciones individuales con estado "completed"
                // Esto incluye tanto las individuales como las que pertenecen a lotes
                return $query->where('payment_status', 'completed');
            })
            ->columns([
                /* Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('No registrada'), */

                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Estudiante')
                    ->searchable(['students.first_names', 'students.last_names'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->student->first_names.' '.$record->student->last_names
                    ),

                Tables\Columns\TextColumn::make('instructorWorkshop.workshop.name')
                    ->label('Taller')
                    ->searchable(['workshops.name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'link' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full_month' => 'Regular',
                        'specific_classes' => 'Recuperación',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_month' => 'success',
                        'specific_classes' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('number_of_classes')
                    ->label('Clases')
                    ->formatStateUsing(fn (int $state): string => $state.($state === 1 ? ' Clase' : ' Clases'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                    ]),

                Tables\Filters\SelectFilter::make('enrollment_type')
                    ->label('Tipo de Inscripción')
                    ->options([
                        'full_month' => 'Regular',
                        'specific_classes' => 'Recuperación',
                    ]),

                Tables\Filters\Filter::make('enrollment_date_filter')
                    ->label('Fecha de Inscripción')
                    ->form([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('enrollment_date_from')
                                            ->label('Desde'),
                                        Forms\Components\DatePicker::make('enrollment_date_until')
                                            ->label('Hasta'),
                                    ]),
                            ])
                            ->heading('Fecha de Inscripción'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['enrollment_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '>=', $date),
                            )
                            ->when(
                                $data['enrollment_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '<=', $date),
                            );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportar Seleccionados'),
                ]),
            ])
            ->defaultSort('enrollment_date', 'desc')
            ->emptyStateHeading('No hay ingresos registrados')
            ->emptyStateDescription('Los ingresos aparecerán aquí cuando las inscripciones tengan estado "Inscrito" y fecha de pago registrada.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
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
            'index' => Pages\ListIncomes::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
