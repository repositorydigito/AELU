<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentBatchResource\Pages;
use App\Filament\Resources\EnrollmentBatchResource\RelationManagers;
use App\Models\EnrollmentBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EnrollmentBatchResource extends Resource
{
    protected static ?string $model = EnrollmentBatch::class;
    protected static ?string $navigationLabel = 'Inscripciones'; 
    protected static ?string $pluralModelLabel = 'Inscripciones'; 
    protected static ?string $modelLabel = 'Inscripción';    
    protected static ?int $navigationSort = 4; 
    protected static ?string $navigationGroup = 'Gestión'; 
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Inscripción')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Estudiante')
                            ->relationship('student', 'first_names')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->first_names} {$record->last_names}"
                            )
                            ->searchable(['first_names', 'last_names'])
                            ->required()
                            ->disabled(),
                        
                        Forms\Components\DatePicker::make('enrollment_date')
                            ->label('Fecha de Inscripción')
                            ->required()
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Monto Total')
                            ->numeric()
                            ->prefix('S/')
                            ->disabled(),
                        
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'cash' => 'Efectivo',
                                'link' => 'Link de Pago',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de Pago')
                            ->options([
                                'pending' => 'En Proceso',
                                'to_pay' => 'Por Pagar',
                                'completed' => 'Inscrito',
                                'credit_favor' => 'Crédito a Favor',
                                'refunded' => 'Devuelto',
                            ])
                            ->required(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Información de Pago Adicional')
                    ->description('Gestiona fechas y documentos de pago')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('payment_due_date')
                                    ->label('Fecha Límite de Pago')
                                    ->helperText('Fecha límite para realizar el pago'),
                                
                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Fecha de Pago')
                                    ->helperText('Fecha en que se realizó el pago'),
                            ]),
                        
                        Forms\Components\FileUpload::make('payment_document')
                            ->label('Documento de Pago')
                            ->helperText('Subir comprobante de pago (PDF o imagen)')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(5120) // 5MB
                            ->directory('payment-documents')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Talleres Inscritos')
                    ->schema([
                        Forms\Components\Repeater::make('enrollments')
                            ->label('')
                            ->relationship('enrollments')
                            ->schema([
                                Forms\Components\Select::make('instructor_workshop_id')
                                    ->label('Taller')
                                    ->relationship('instructorWorkshop', 'id')
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        $dayNames = [
                                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                            7 => 'Domingo', 0 => 'Domingo'
                                        ];
                                        $dayInSpanish = $dayNames[$record->day_of_week] ?? 'Día ' . $record->day_of_week;
                                        $startTime = \Carbon\Carbon::parse($record->start_time)->format('H:i');
                                        
                                        return "{$record->workshop->name} - {$dayInSpanish} {$startTime}";
                                    })
                                    ->required()
                                    ->disabled(),
                                
                                Forms\Components\Select::make('enrollment_type')
                                    ->label('Tipo')
                                    ->options([
                                        'full_month' => 'Regular',
                                        'specific_classes' => 'Recuperación',
                                    ])
                                    ->required()
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('number_of_classes')
                                    ->label('Clases')
                                    ->numeric()
                                    ->required()
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->required()
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Estudiante')
                    ->searchable(['student.first_names', 'student.last_names'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => 
                        $record->student->first_names . ' ' . $record->student->last_names
                    ),
                
                Tables\Columns\TextColumn::make('workshops_list')
                    ->label('Talleres')
                    ->limit(50)
                    ->tooltip(function (EnrollmentBatch $record): ?string {
                        return $record->workshops_list;
                    }),
                
                Tables\Columns\TextColumn::make('workshops_count')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (int $state): string => $state . ($state === 1 ? ' Taller' : ' Talleres'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_classes')
                    ->label('Total Clases')
                    ->formatStateUsing(fn (int $state): string => $state . ($state === 1 ? ' Clase' : ' Clases'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En Proceso',
                        'to_pay' => 'Por Pagar',
                        'completed' => 'Inscrito',
                        'credit_favor' => 'Crédito a Favor',
                        'refunded' => 'Devuelto',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'to_pay' => 'danger',
                        'completed' => 'success',
                        'credit_favor' => 'info',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                        default => $state,
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payment_due_date')
                    ->label('Fecha Límite')
                    ->date('d/m/Y')
                    ->placeholder('No definida')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->placeholder('No pagado')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'En Proceso',
                        'to_pay' => 'Por Pagar',
                        'completed' => 'Inscrito',
                        'credit_favor' => 'Crédito a Favor',
                        'refunded' => 'Devuelto',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'link' => 'Link de Pago',
                    ]),
                
                Tables\Filters\Filter::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->form([
                        Forms\Components\DatePicker::make('enrollment_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('enrollment_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['enrollment_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '>=', $date),
                            )
                            ->when(
                                $data['enrollment_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrollment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('download_ticket')
                    ->label('Descargar Ticket')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (EnrollmentBatch $record): string => route('enrollment.batch.ticket', ['batchId' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (EnrollmentBatch $record): bool => $record->payment_status === 'completed')
                    ->color('success'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Lote de Inscripciones')
                    ->modalDescription('¿Estás seguro de que deseas eliminar este lote de inscripciones? Esta acción eliminará todas las inscripciones asociadas y no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
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
            'index' => Pages\ListEnrollmentBatches::route('/'),
            'create' => Pages\CreateEnrollmentBatch::route('/create'),
            'edit' => Pages\EditEnrollmentBatch::route('/{record}/edit'),
        ];
    }
}
