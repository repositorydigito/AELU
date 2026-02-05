<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentBatchResource\Pages;
use App\Models\EnrollmentBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_names} {$record->last_names}"
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
                                        $daysOfWeek = $record->day_of_week;
                                        if (is_array($daysOfWeek)) {
                                            $dayInSpanish = implode('/', $daysOfWeek);
                                        } else {
                                            $dayInSpanish = $daysOfWeek ?? 'N/A';
                                        }
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
                    ->searchable(['students.first_names', 'students.last_names'])
                    ->formatStateUsing(fn ($record) => $record->student->last_names.' '.$record->student->first_names),

                Tables\Columns\TextColumn::make('created_by_name')
                    ->label('Usuario'),

                /* Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Editado por')
                    ->placeholder('Sin ediciones')
                    ->toggleable(isToggledHiddenByDefault: true), */

                /* Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('Pagado por')
                    ->placeholder('Pendiente')
                    ->toggleable(isToggledHiddenByDefault: true), */

                Tables\Columns\TextColumn::make('cobrado_por')
                    ->label('Cobrado por')
                    ->getStateUsing(function (EnrollmentBatch $record): string {
                        $payments = $record->payments()
                            ->with('registeredByUser')
                            ->get();

                        if ($payments->isEmpty()) {
                            return '-';
                        }

                        $users = $payments
                            ->pluck('registeredByUser.name')
                            ->filter() // Remover nulls
                            ->unique()
                            ->values();

                        if ($users->isEmpty()) {
                            return '-';
                        }

                        // Si es un solo usuario, mostrar nombre simple
                        if ($users->count() === 1) {
                            return $users->first();
                        }

                        // Si son múltiples usuarios, mostrar separados por comas
                        return $users->join(', ');
                    })
                    ->wrap()
                    ->toggleable()
                    ->tooltip(function (EnrollmentBatch $record): ?string {
                        $payments = $record->payments()
                            ->with('registeredByUser')
                            ->orderBy('registered_at')
                            ->get();

                        if ($payments->isEmpty()) {
                            return null;
                        }

                        $details = $payments->map(function ($payment) {
                            $user = $payment->registeredByUser?->name ?? 'Usuario desconocido';
                            $date = $payment->registered_at?->format('d/m/Y H:i') ?? '-';
                            $amount = 'S/ '.number_format($payment->amount, 2);

                            return "{$user} - {$date} - {$amount}";
                        })->join("\n");

                        return "Detalle de pagos:\n".$details;
                    }),

                Tables\Columns\TextColumn::make('cancelledBy.name')
                    ->label('Anulado Por')
                    ->formatStateUsing(function ($state, \App\Models\EnrollmentBatch $record) {
                        // Si se anuló y no hay usuario asociado, fue el sistema
                        if ($record->cancelled_at && empty($record->cancelled_by_user_id)) {
                            return 'Sistema';
                        }

                        return $record->cancelledBy?->name;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('workshops_list')
                    ->label('Talleres')
                    ->limit(50)
                    ->tooltip(function (EnrollmentBatch $record): ?string {
                        return $record->workshops_list;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('workshops_count')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (int $state): string => $state.($state === 1 ? ' Taller' : ' Talleres'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_classes')
                    ->label('Total Clases')
                    ->formatStateUsing(fn (int $state): string => $state.($state === 1 ? ' Clase' : ' Clases'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Inscripción')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('mes_inscripcion')
                    ->label('Mes')
                    ->getStateUsing(function ($record) {
                        $firstEnrollment = $record->enrollments->first();
                        if ($firstEnrollment && $firstEnrollment->monthlyPeriod) {
                            return ucfirst(\Carbon\Carbon::create(
                                $firstEnrollment->monthlyPeriod->year,
                                $firstEnrollment->monthlyPeriod->month,
                                1
                            )->translatedFormat('F Y'));
                        }

                        return ucfirst(\Carbon\Carbon::parse($record->updated_at)->translatedFormat('F Y'));
                    }),

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
                        'to_pay' => 'info',
                        'completed' => 'success',
                        'credit_favor' => 'info',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'link' => 'Link',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->prefix('S/'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Usuario')
                    ->relationship('creator', 'name', function (Builder $query) {
                        return $query->whereDoesntHave('roles', function (Builder $roleQuery) {
                            $roleQuery->where('name', 'Delegado');
                        });
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'En Proceso',
                        'completed' => 'Inscrito',
                        'refunded' => 'Anulado',
                        'to_pay' => 'Por Pagar',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'link' => 'Link',
                    ]),

                Tables\Filters\SelectFilter::make('monthly_period')
                    ->label('Mes de Inscripción')
                    ->options(function () {
                        $currentYear = now()->year;
                        $previousYear = $currentYear - 1;

                        return \App\Models\MonthlyPeriod::query()
                            ->whereIn('year', [$previousYear, $currentYear])
                            ->orderBy('year', 'desc')
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(function ($period) {
                                $monthName = ucfirst(\Carbon\Carbon::create(
                                    $period->year,
                                    $period->month,
                                    1
                                )->translatedFormat('F Y'));

                                return [$period->id => $monthName];
                            })
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            return $query->whereHas('enrollments', function (Builder $enrollmentQuery) use ($data) {
                                $enrollmentQuery->where('monthly_period_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('download_ticket')
                    ->label('Ver Tickets')
                    ->icon('heroicon-o-ticket')
                    ->visible(fn (EnrollmentBatch $record): bool => $record->tickets()->exists()
                    )
                    ->modalHeading(fn (EnrollmentBatch $record) => 'Tickets de '.($record->student->full_name ?? 'N/A'))
                    ->modalDescription(fn (EnrollmentBatch $record) => 'Total de tickets emitidos: '.$record->tickets()->where('status', 'active')->count()
                    )
                    ->modalContent(fn (EnrollmentBatch $record) => view('filament.modals.tickets-list', [
                        'tickets' => $record->tickets()
                            // ->where('status', 'active')
                            ->with(['studentEnrollments.instructorWorkshop.workshop', 'issuedByUser'])
                            ->orderBy('issued_at', 'asc')
                            ->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('3xl')
                    ->color('info'),
                Tables\Actions\Action::make('view_cancellation_reason')
                    ->label('Motivo')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('Motivo de Anulación')
                    ->modalContent(function (EnrollmentBatch $record) {
                        $cancelledBy = $record->cancelled_by_user_id
                            ? ($record->cancelledBy->name ?? 'Usuario eliminado')
                            : 'Sistema';
                        $cancelledAt = $record->cancelled_at ? $record->cancelled_at->format('d/m/Y H:i') : 'Fecha no disponible';
                        $reason = $record->cancellation_reason ?: 'No se especificó motivo';

                        return new \Illuminate\Support\HtmlString("
                            <div class='space-y-4'>
                                <div class='bg-red-50 border border-red-200 rounded-lg p-4'>
                                    <h4 class='font-medium text-red-800 mb-2'>Información de la Anulación</h4>
                                    <div class='text-sm text-red-700 space-y-1'>
                                        <p><strong>Anulado por:</strong> {$cancelledBy}</p>
                                        <p><strong>Fecha:</strong> {$cancelledAt}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class='font-medium text-gray-900 mb-2'>Motivo:</h4>
                                    <div class='bg-gray-50 border border-gray-200 rounded-lg p-3'>
                                        <p class='text-sm text-gray-700'>{$reason}</p>
                                    </div>
                                </div>
                            </div>
                        ");
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->visible(fn (EnrollmentBatch $record): bool => $record->payment_status === 'refunded'),
                \App\Filament\Resources\EnrollmentBatchResource\Actions\RegisterPaymentAction::make(),
                Tables\Actions\Action::make('cancel_enrollment')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Inscripción')
                    ->modalDescription(function (EnrollmentBatch $record) {
                        $statusText = match ($record->payment_status) {
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
                        // VALIDACIÓN DE SEGURIDAD: Solo usuarios autorizados pueden anular
                        $authorizedUsers = ['sdordan', 'tnamoc', 'ggonzalez'];
                        $currentUserName = auth()->user()->name ?? '';

                        if (! in_array($currentUserName, $authorizedUsers)) {
                            Notification::make()
                                ->title('Acción no permitida')
                                ->body('No tienes permisos para anular inscripciones.')
                                ->danger()
                                ->send();

                            return;
                        }

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

                            DB::transaction(function () use ($record, $data) {
                                // Actualizar el estado del lote
                                $record->update([
                                    'payment_status' => 'refunded',
                                    'cancelled_at' => now(),
                                    'cancelled_by_user_id' => auth()->id(),
                                    'cancellation_reason' => $data['cancellation_reason'],
                                    'notes' => ($record->notes ? $record->notes."\n\n" : '').
                                            'Anulación registrada por '.auth()->user()->name.' el '.now()->format('d/m/Y H:i').
                                            ":\nMotivo: ".$data['cancellation_reason'],
                                ]);

                                // Actualizar cada inscripción individualmente (estado y fecha de anulación)
                                foreach ($record->enrollments as $enrollment) {
                                    $enrollment->update([
                                        'payment_status' => 'refunded',
                                        'cancelled_at' => now(),
                                        'cancellation_reason' => $data['cancellation_reason'] ?? null,
                                    ]);
                                }

                                // Anular tickets asociados al lote
                                $record->tickets()
                                    ->where('status', 'active')
                                    ->update([
                                        'status' => 'cancelled',
                                        'cancelled_at' => now(),
                                        'cancelled_by_user_id' => auth()->id(),
                                        'cancellation_reason' => $data['cancellation_reason'] ?? 'Anulación de inscripción',
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
                                ->body('Hubo un problema al procesar la anulación: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (EnrollmentBatch $record): bool {
                        // Solo mostrar el botón si el estado permite anular Y el usuario está autorizado
                        $authorizedUsers = ['sdordan', 'tnamoc', 'ggonzalez'];
                        $currentUserName = auth()->user()->name ?? '';

                        return in_array($record->payment_status, ['pending', 'completed'])
                            && in_array($currentUserName, $authorizedUsers);
                    }),
                Tables\Actions\Action::make('edit_full')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url(function (EnrollmentBatch $record): string {
                        return EnrollmentResource::getUrl('create', [
                            'edit_batch' => $record->id,
                            'student_id' => $record->student_id,
                            'payment_method' => $record->payment_method,
                            'payment_status' => $record->payment_status,
                            'notes' => $record->notes,
                        ]);
                    })
                    ->visible(fn (EnrollmentBatch $record): bool => in_array($record->payment_status, ['pending', 'to_pay'])
                    ),
            ])
            ->bulkActions([

            ])
            ->defaultSort('updated_at', 'desc');
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
            // 'edit' => Pages\EditEnrollmentBatch::route('/{record}/edit'),
        ];
    }
}
