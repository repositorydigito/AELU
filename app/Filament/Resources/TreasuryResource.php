<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TreasuryResource\Pages;
use App\Filament\Resources\TreasuryResource\RelationManagers;
use App\Models\Treasury;
use App\Models\Student;
use App\Models\Instructor;
use App\Models\Workshop;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;

class TreasuryResource extends Resource
{
    protected static ?string $model = Treasury::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Tesorería';
    // protected static ?string $pluralModelLabel = 'Tesorería';
    protected static ?string $modelLabel = 'Tesorería';
    protected static ?int $navigationSort = 8; 
    protected static ?string $navigationGroup = 'Tesorería';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('transaction_type')
                            ->label('Tipo de transacción')
                            ->options([
                                'income' => 'Ingreso',
                                'expense' => 'Gasto',
                                'payment' => 'Pago',
                                'refund' => 'Reembolso',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('S/')
                            ->required(),
                    ]),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Grid::make(2)
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Fecha de transacción')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('confirmed')
                            ->required(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Estudiante')
                            ->relationship('student', 'first_names')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->relationship('instructor', 'first_names')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('workshop_id')
                            ->label('Taller')
                            ->relationship('workshop', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Número de referencia')
                            ->maxLength(255)
                            ->nullable(),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'payment' => 'info',
                        'refund' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Estudiante')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('instructor.full_name')
                    ->label('Instructor')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('workshop.name')
                    ->label('Taller')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Tipo de transacción')
                    ->options([
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        'payment' => 'Pago',
                        'refund' => 'Reembolso',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
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
            'index' => Pages\ListTreasuries::route('/'),
            'create' => Pages\CreateTreasury::route('/create'),
            'edit' => Pages\EditTreasury::route('/{record}/edit'),
        ];
    }
}
