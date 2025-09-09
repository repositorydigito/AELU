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
use Filament\Notifications\Notification;

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

                            Forms\Components\DateTimePicker::make('created_at')
                                ->label('Fecha y Hora de Inscripción')
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
                                'link' => 'Link',
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
                                'refunded' => 'Anulado',
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Inscripción')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En Proceso',
                        'to_pay' => 'Por Pagar',
                        'completed' => 'Inscrito',
                        'credit_favor' => 'Crédito a Favor',
                        'refunded' => 'Anulado',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'to_pay' => 'danger',
                        'completed' => 'success',
                        'credit_favor' => 'info',
                        'refunded' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'link' => 'Link',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->prefix('S/')
                    ->sortable(),

                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Nº Documento')
                    ->placeholder('Sin código')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'En Proceso',
                        'completed' => 'Inscrito',
                        'refunded' => 'Anulado',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'link' => 'Link',
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
                /* Tables\Actions\EditAction::make()->label('Editar'), */
                Tables\Actions\Action::make('download_ticket')
                    ->label('Descargar Ticket')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (EnrollmentBatch $record): string => route('enrollment.batch.ticket', ['batchId' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (EnrollmentBatch $record): bool => $record->payment_status === 'completed' && $record->payment_method === 'cash')
                    ->color('success'),
                Tables\Actions\Action::make('register_payment')
                    ->label('Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->form(function (EnrollmentBatch $record) {
                        if ($record->payment_method === 'link') {
                            return [
                                Forms\Components\TextInput::make('batch_code')
                                    ->label('Código de Lote/Ticket')
                                    ->required()
                                    ->maxLength(50)
                                    ->helperText('Ingresa el código del voucher/boleta/ticket de pago por link'),

                                Forms\Components\Textarea::make('payment_notes')
                                    ->label('Observaciones del Pago')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales sobre el pago (opcional)'),
                            ];
                        } else {
                            return [
                                Forms\Components\Placeholder::make('confirmation')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <h3 class="font-semibold text-green-800 mb-2">Confirmar Pago en Efectivo</h3>
                                            <p class="text-sm text-green-700">
                                                ¿Confirmas que has recibido el pago en efectivo por el monto total de
                                                <strong>S/ ' . number_format($record->total_amount, 2) . '</strong>?
                                            </p>
                                        </div>
                                    ')),

                                Forms\Components\Textarea::make('payment_notes')
                                    ->label('Observaciones del Pago')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales sobre el pago (opcional)'),
                            ];
                        }
                    })
                    ->action(function (EnrollmentBatch $record, array $data): void {
                        $updates = [
                            'payment_status' => 'completed',
                            'payment_date' => now(),
                            'payment_registered_by_user_id' => auth()->id(),
                            'payment_registered_at' => now(),
                        ];

                        // Si es pago por link, también actualizar el código
                        if ($record->payment_method === 'link' && isset($data['batch_code'])) {
                            $updates['batch_code'] = $data['batch_code'];
                        }

                        // Agregar notas si se proporcionaron
                        if (!empty($data['payment_notes'])) {
                            $existingNotes = $record->notes ? $record->notes . "\n\n" : '';
                            $updates['notes'] = $existingNotes . "Pago registrado por " . auth()->user()->name . " el " . now()->format('d/m/Y H:i') . ":\n" . $data['payment_notes'];
                        }

                        $record->update($updates);

                        // También actualizar todas las inscripciones individuales del lote
                        $record->enrollments()->update([
                            'payment_status' => 'completed',
                            'payment_date' => now(),
                        ]);

                        $message = $record->payment_method === 'link'
                            ? "Pago registrado exitosamente. Código: {$data['batch_code']}"
                            : "Pago en efectivo confirmado exitosamente";

                        Notification::make()
                            ->title('Pago Registrado')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->modalHeading(function (EnrollmentBatch $record) {
                        return $record->payment_method === 'link'
                            ? 'Registrar Pago por Link'
                            : 'Confirmar Pago en Efectivo';
                    })
                    ->modalDescription(function (EnrollmentBatch $record) {
                        return $record->payment_method === 'link'
                            ? 'Ingresa el código del voucher/boleta/ticket generado por el sistema de pagos'
                            : 'Confirma que has recibido el pago en efectivo del estudiante';
                    })
                    ->modalSubmitActionLabel('Registrar Pago')
                    ->modalCancelActionLabel('Cancelar')
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        $record->payment_status === 'pending'
                    )
                    ->color('success'),
                Tables\Actions\Action::make('cancel_enrollment')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Inscripción')
                    ->modalDescription(function (EnrollmentBatch $record) {
                        $statusText = match($record->payment_status) {
                            'pending' => 'en proceso',
                            'completed' => 'inscrito',
                            default => $record->payment_status
                        };

                        return "¿Estás seguro de que deseas anular esta inscripción? El estado actual es '{$statusText}'. Esta acción liberará los cupos ocupados y marcará la inscripción como anulada.";
                    })
                    ->modalSubmitActionLabel('Sí, anular inscripción')
                    ->modalCancelActionLabel('Cancelar')
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Motivo de la anulación')
                            ->nullable()
                            ->rows(3),
                    ])
                    ->action(function (EnrollmentBatch $record, array $data): void {
                        try {
                            // Obtener información de los talleres ANTES de la transacción
                            $workshops = $record->enrollments()
                                ->with(['instructorWorkshop.workshop'])
                                ->get()
                                ->pluck('instructorWorkshop.workshop.name')
                                ->unique()
                                ->take(3)
                                ->implode(', ');

                            if ($record->enrollments()->count() > 3) {
                                $workshops .= '...';
                            }

                            $totalEnrollments = $record->enrollments()->count();

                            \DB::transaction(function () use ($record, $data) {
                                // Actualizar el estado del lote
                                $record->update([
                                    'payment_status' => 'refunded',
                                    'cancelled_at' => now(),
                                    'cancelled_by_user_id' => auth()->id(),
                                    'cancellation_reason' => $data['cancellation_reason'],
                                    'notes' => ($record->notes ? $record->notes . "\n\n" : '') .
                                            "Anulación registrada por " . auth()->user()->name . " el " . now()->format('d/m/Y H:i') .
                                            ":\nMotivo: " . $data['cancellation_reason']
                                ]);

                                // Actualizar todas las inscripciones individuales del lote
                                $record->enrollments()->update([
                                    'payment_status' => 'refunded',
                                ]);
                            });

                            Notification::make()
                                ->title('Inscripción anulada exitosamente')
                                ->body("Se han liberado {$totalEnrollments} cupo(s) en: {$workshops}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al anular inscripción')
                                ->body('Hubo un problema al procesar la anulación: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        in_array($record->payment_status, ['pending', 'completed'])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
