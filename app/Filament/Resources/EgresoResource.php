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
use Illuminate\Support\HtmlString;

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

                // Columna que indica si hay vouchers y permite abrir el modal
                Tables\Columns\TextColumn::make('voucher_status')
                    ->label('Voucher')
                    ->getStateUsing(fn ($record) => !empty($record->expense->voucher_path) ? 'Con voucher' : 'Sin voucher')
                    ->badge()
                    ->color(fn ($record): string => !empty($record->expense->voucher_path) ? 'success' : 'gray')
                    ->icon(fn ($record): string => !empty($record->expense->voucher_path) ? 'heroicon-o-document-check' : 'heroicon-o-document-minus')
                    ->toggleable(),
            ])
            ->filters([
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
                            ->heading('Fecha de Gasto'),
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
            ])
            ->actions([
                Tables\Actions\Action::make('view_vouchers')
                    ->label('Ver Voucher')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record): bool => !empty($record->expense->voucher_path))
                    ->modalHeading(fn ($record): string => 'Vouchers - ' . $record->razon_social)
                    ->modalDescription(fn ($record): string =>
                        'Concepto: ' . $record->expense->concept . ' | ' .
                        'Fecha: ' . $record->date->format('d/m/Y') . ' | ' .
                        'Monto: S/ ' . number_format($record->amount, 2)
                    )
                    ->modalContent(function ($record) {
                        if (empty($record->expense->voucher_path)) {
                            return new HtmlString('<p class="text-gray-500 text-center py-8">No hay vouchers adjuntos.</p>');
                        }

                        // Obtener el voucher (solo uno)
                        $voucher = is_array($record->expense->voucher_path)
                            ? $record->expense->voucher_path[0]
                            : $record->expense->voucher_path;

                        $voucherUrl = asset('storage/' . $voucher);
                        $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $voucher);

                        if ($isImage) {
                            $html = '<div class="text-center">';
                            $html .= '<img src="' . $voucherUrl . '" alt="Voucher" class="max-w-full h-auto rounded-lg shadow-lg mx-auto" style="max-height: 500px;">';
                            $html .= '<div class="mt-4">';
                            $html .= '<a href="' . $voucherUrl . '" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">';
                            $html .= '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>';
                            $html .= 'Ver en nueva pestaña</a>';
                            $html .= '</div>';
                            $html .= '</div>';
                        } else {
                            $html = '<div class="text-center py-8">';
                            $html .= '<div class="mb-4">';
                            $html .= '<svg class="w-16 h-16 text-red-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                            $html .= '</div>';
                            $html .= '<h3 class="text-lg font-semibold text-gray-900 mb-2">Documento PDF</h3>';
                            $html .= '<p class="text-sm text-gray-600 mb-4">' . basename($voucher) . '</p>';
                            $html .= '<a href="' . $voucherUrl . '" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">';
                            $html .= '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                            $html .= 'Descargar PDF</a>';
                            $html .= '</div>';
                        }

                        return new HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->slideOver()
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
