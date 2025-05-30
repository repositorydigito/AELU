<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Filament\Resources\EnrollmentResource\RelationManagers;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Workshop;
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

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Inscripciones';
    protected static ?string $pluralModelLabel = 'Inscripciones';
    protected static ?string $modelLabel = 'Inscripción';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Estudiante')
                            ->relationship('student', 'first_names')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Student $record) => "{$record->first_names} {$record->last_names} - {$record->student_code}"),
                        Forms\Components\Select::make('workshop_id')
                            ->label('Taller')
                            ->relationship('workshop', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        DatePicker::make('enrollment_date')
                            ->label('Fecha de inscripción')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'enrolled' => 'Inscrito',
                                'completed' => 'Completado',
                                'dropped' => 'Retirado',
                                'pending' => 'Pendiente',
                            ])
                            ->default('enrolled')
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de pago')
                            ->options([
                                'pending' => 'Pendiente',
                                'partial' => 'Parcial',
                                'paid' => 'Pagado',
                                'overdue' => 'Vencido',
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Monto total')
                            ->numeric()
                            ->prefix('S/')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Monto pagado')
                            ->numeric()
                            ->prefix('S/')
                            ->default(0)
                            ->required(),
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
                TextColumn::make('student.full_name')
                    ->label('Estudiante')
                    ->searchable(['first_names', 'last_names'])
                    ->sortable(),
                TextColumn::make('student.student_code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('workshop.name')
                    ->label('Taller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollment_date')
                    ->label('Fecha inscripción')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enrolled' => 'info',
                        'completed' => 'success',
                        'dropped' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Estado pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'info',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Pagado')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('remaining_amount')
                    ->label('Pendiente')
                    ->money('PEN')
                    ->getStateUsing(fn (Enrollment $record): float => $record->remaining_amount),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'enrolled' => 'Inscrito',
                        'completed' => 'Completado',
                        'dropped' => 'Retirado',
                        'pending' => 'Pendiente',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado de pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'paid' => 'Pagado',
                        'overdue' => 'Vencido',
                    ]),
                Tables\Filters\SelectFilter::make('workshop')
                    ->label('Taller')
                    ->relationship('workshop', 'name')
                    ->searchable(),
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
            ->defaultSort('enrollment_date', 'desc');
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
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
