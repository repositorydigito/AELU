<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationLabel = 'Gastos Extra';

    protected static ?string $pluralModelLabel = 'Gastos Extra';

    protected static ?string $modelLabel = 'Gasto';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Tesorería';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección principal con información básica
                Section::make('Información General')
                    ->description('Datos básicos del gasto')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('concept')
                                    ->label('Concepto del Gasto')
                                    ->placeholder('Selecciona el tipo de gasto')
                                    ->options([
                                        'Taller de Cocina' => '👨‍🍳 Taller de Cocina',
                                        'Compra de materiales' => '📦 Compra de materiales',
                                        // 'Pago a Profesores' => '👩‍🏫 Pago a profesores',
                                        'Otros' => '📋 Otros',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                TextInput::make('vale_code')
                                    ->label('Código de Vale')
                                    ->placeholder('Ej: VALE-2024-001')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->helperText('Código interno para identificar el vale (opcional)'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Sección de detalles de gastos
                Section::make('Detalle de Gastos')
                    ->description('Agrega los gastos individuales con sus respectivos datos')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Repeater::make('expense_entries')
                            ->relationship('expenseDetails')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Fecha del Gasto')
                                            ->required()
                                            ->default(now())
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-calendar-days'),

                                        TextInput::make('razon_social')
                                            ->label('Razón Social / Proveedor')
                                            ->placeholder('Nombre del proveedor o empresa')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-building-office'),

                                        TextInput::make('document_number')
                                            ->label('N° Documento')
                                            ->placeholder('Factura, boleta, etc.')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-document-text'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('amount')
                                            ->label('Monto')
                                            ->required()
                                            ->numeric()
                                            ->prefix('S/')
                                            ->minValue(0.01)
                                            ->placeholder('0.00')
                                            ->prefixIcon('heroicon-o-banknotes')
                                            ->inputMode('decimal')
                                            ->rules(['numeric', 'min:0.01']),

                                        Textarea::make('notes')
                                            ->label('Observaciones')
                                            ->placeholder('Detalles adicionales del gasto (opcional)')
                                            ->maxLength(500)
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => ! empty($state['razon_social']) && ! empty($state['amount'])
                                    ? $state['razon_social'].' - S/ '.number_format(floatval($state['amount'] ?? 0), 2)
                                    : 'Nuevo Gasto'
                            )
                            ->defaultItems(1)
                            ->addActionLabel('➕ Agregar otro gasto')
                            ->cloneable()
                            ->reorderable()
                            ->columnSpanFull()
                            ->minItems(1),

                        // Placeholder para mostrar el total
                        Placeholder::make('total_preview')
                            ->label('Total Estimado')
                            ->content(function ($get) {
                                $details = $get('expense_entries') ?? [];
                                $total = collect($details)->sum(function ($item) {
                                    return floatval($item['amount'] ?? 0);
                                });

                                return 'S/ '.number_format($total, 2);
                            })
                            ->live()
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600']),
                    ])
                    ->collapsible(),

                // Sección de documentos
                Section::make('Documentación')
                    ->description('Adjunta los comprobantes y documentos relacionados')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        FileUpload::make('voucher_path')
                            ->label('Recibos y Vouchers')
                            ->helperText('Sube las imágenes o PDFs de los comprobantes (máx. 2MB cada uno)')
                            ->disk('public')
                            ->directory('vouchers')
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'])
                            ->maxSize(2048)
                            ->reorderable()
                            ->downloadable()
                            ->previewable()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para el Concepto
                TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable(),

                // Mostrar el monto total de todos los detalles del gasto
                TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->getStateUsing(fn ($record) => $record->expenseDetails->sum(function ($detail) {
                        return floatval($detail->amount ?? 0);
                    }))
                    ->money('PEN')
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
                    ->toggleable(isToggledHiddenByDefault: true)
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
                                fn (Builder $query, $date): Builder => $query->whereHas('expenseDetails', function ($q) use ($date) {
                                    $q->whereDate('date', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('expenseDetails', function ($q) use ($date) {
                                    $q->whereDate('date', '<=', $date);
                                }),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
