<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DecimalOrFloat;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationLabel = 'Egresos';
    protected static ?string $pluralModelLabel = 'Egresos';
    protected static ?string $modelLabel = 'Egreso';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Tesorería';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('concept')
                    ->label('Concepto')
                    ->options([
                        'Taller de Cocina' => 'Taller de Cocina',
                        'Compra de materiales' => 'Compra de materiales',
                        'Pago a Profesores' => 'Pago a profesores',
                        'Otros' => 'Otros',
                    ])
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('expense_entries')
                    ->label('Detalle de Gastos')
                    ->relationship('expenseDetails')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->default(now()),
                        TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric()
                            ->prefix('S/.')
                            ->step(0.01)
                            ->minValue(0.01),
                        TextInput::make('notes')
                            ->label('Observaciones')
                            ->maxLength(255)
                            ->nullable(),
                    ])
                    ->columns(5)
                    ->defaultItems(1)
                    ->addActionLabel('Agregar otro gasto')
                    ->cloneable()
                    ->columnSpanFull(),

                TextInput::make('vale_code')
                    ->label('Código de Vale')
                    ->maxLength(255)
                    ->nullable(),

                FileUpload::make('voucher_path')
                    ->label('Adjuntar Recibo/Voucher')
                    ->disk('public')
                    ->directory('vouchers')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->maxSize(2048)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el Concepto
                TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable() // Permite buscar por este campo
                    ->sortable(), // Permite ordenar por este campo

                // Mostrar el monto total de todos los detalles del gasto
                TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->getStateUsing(fn ($record) => $record->expenseDetails->sum('amount'))
                    ->money('PEN') // Formato de moneda para Soles Peruanos
                    ->sortable(),

                // Opcional: Mostrar la cantidad de ítems de detalle
                TextColumn::make('total_items')
                    ->label('Cant. Ítems')
                    ->getStateUsing(fn ($record) => $record->expenseDetails->count())
                    ->sortable(),

                TextColumn::make('created_at')
                ->label('Fecha')
                ->date('d/m/Y')
                ->sortable(),

                // Columna para el Código de Vale
                TextColumn::make('vale_code')
                    ->label('Cód. Vale')
                    ->toggleable(isToggledHiddenByDefault: true) // Oculto por defecto, se puede mostrar
                    ->searchable(),
            ])
            ->filters([
                // Filtro por Concepto
                Tables\Filters\SelectFilter::make('concept')
                    ->options([
                        'Taller de Cocina' => 'Taller de Cocina',
                        'Compra de materiales' => 'Compra de materiales',
                        'Pago a Profesores' => 'Pago a profesores',
                        'Otros' => 'Otros',
                    ])
                    ->label('Filtrar por Concepto'),

                // Filtro por Fecha
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Fecha Desde'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Fecha Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->label('Filtrar por Fecha'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
