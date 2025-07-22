<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EgresoResource\Pages;
use App\Models\ExpenseDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EgresoResource extends Resource
{
    protected static ?string $model = ExpenseDetail::class;
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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha del Gasto')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expense.concept')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Taller de Cocina' => '👨‍🍳 Taller de Cocina',
                        'Compra de materiales' => '📦 Compra de materiales',
                        'Pago a Profesores' => '👩‍🏫 Pago a profesores',
                        'Otros' => '📋 Otros',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('razon_social')
                    ->label('Proveedor / Razón Social')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('document_number')
                    ->label('N° Documento')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('PEN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('notes')
                    ->label('Observaciones')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('Sin observaciones')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('expense.vale_code')
                    ->label('Código de Vale')
                    ->searchable()
                    ->placeholder('Sin código')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('expense.has_voucher')
                    ->label('Voucher')
                    ->getStateUsing(fn ($record) => !empty($record->expense->voucher_path))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense.concept')
                    ->label('Concepto')
                    ->relationship('expense', 'concept')
                    ->options([
                        'Taller de Cocina' => '👨‍🍳 Taller de Cocina',
                        'Compra de materiales' => '📦 Compra de materiales',
                        'Pago a Profesores' => '👩‍🏫 Pago a profesores',
                        'Otros' => '📋 Otros',
                    ]),
                
                Tables\Filters\Filter::make('date')
                    ->label('Fecha de Gasto')
                    ->form([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('date_from')
                                            ->label('Desde'),
                                        Forms\Components\DatePicker::make('date_until')
                                            ->label('Hasta'),
                                    ]),
                            ])
                            ->heading('Fecha de Gasto')
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
                    }),
                
                Tables\Filters\Filter::make('created_date')
                    ->label('Fecha de Registro')
                    ->form([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('created_from')
                                            ->label('Desde'),
                                        Forms\Components\DatePicker::make('created_until')
                                            ->label('Hasta'),
                                    ]),
                            ])
                            ->heading('Fecha de Registro')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('amount_range')
                    ->label('Rango de Monto')
                    ->form([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('amount_from')
                                            ->label('Monto Desde')
                                            ->numeric()
                                            ->prefix('S/.'),
                                        Forms\Components\TextInput::make('amount_until')
                                            ->label('Monto Hasta')
                                            ->numeric()
                                            ->prefix('S/.'),
                                    ]),
                            ])
                            ->heading('Rango de Monto')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_until'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('has_voucher')
                    ->label('Con Voucher')
                    ->placeholder('Todos')
                    ->trueLabel('Con voucher')
                    ->falseLabel('Sin voucher')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('expense', function ($q) {
                            $q->whereNotNull('voucher_path');
                        }),
                        false: fn (Builder $query) => $query->whereHas('expense', function ($q) {
                            $q->whereNull('voucher_path');
                        }),
                    ),
                
                Tables\Filters\Filter::make('razon_social')
                    ->label('Proveedor')
                    ->form([
                        Forms\Components\TextInput::make('proveedor')
                            ->label('Buscar proveedor')
                            ->placeholder('Nombre del proveedor'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['proveedor'],
                                fn (Builder $query, $proveedor): Builder => $query->where('razon_social', 'like', "%{$proveedor}%"),
                            );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportar Seleccionados'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading('No hay egresos registrados')
            ->emptyStateDescription('Los egresos aparecerán aquí cuando registres gastos en el módulo de Gastos Extra.')
            ->emptyStateIcon('heroicon-o-arrow-trending-down');
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
            'index' => Pages\ListEgresos::route('/'),
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