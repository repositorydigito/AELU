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
                                        $dayNames = [
                                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                            7 => 'Domingo', 0 => 'Domingo',
                                        ];
                                        $dayInSpanish = $dayNames[$record->day_of_week] ?? 'Día '.$record->day_of_week;
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

                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Editado por')
                    ->placeholder('Sin ediciones')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('Pagado por')
                    ->placeholder('Pendiente')
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
                            return ucfirst(\Carbon\Carbon::createFromDate(
                                $firstEnrollment->monthlyPeriod->year,
                                $firstEnrollment->monthlyPeriod->month
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
                        'to_pay' => 'danger',
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

                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Nº Ticket')
                    ->placeholder('Sin código'),
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
                                $monthName = ucfirst(\Carbon\Carbon::createFromDate(
                                    $period->year,
                                    $period->month
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
                    ->label('Descargar Ticket')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (EnrollmentBatch $record): string => route('enrollment.batch.ticket', ['batchId' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        // Mostrar para inscripciones completadas con efectivo O anuladas que tengan batch_code Y al menos una inscripción pagada
                        /* ($record->payment_status === 'completed' && $record->payment_method === 'cash') ||
                        ($record->payment_status === 'refunded' && $record->payment_method === 'cash' && !empty($record->batch_code) && $record->batch_code !== 'Sin código') */
                        !empty($record->batch_code) &&
                        $record->batch_code !== 'Sin código' &&
                        $record->enrollments()->where('payment_status', 'completed')->exists()
                    )
                    ->color('success'),
                Tables\Actions\Action::make('view_cancellation_reason')
                    ->label('Motivo')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('Motivo de Anulación')
                    ->modalContent(function (EnrollmentBatch $record) {
                        $cancelledBy = $record->cancelledBy ? $record->cancelledBy->name : 'Usuario no disponible';
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
                Tables\Actions\Action::make('register_payment')
                    ->label('Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->form(function (EnrollmentBatch $record) {
                        $paymentService = app(\App\Services\EnrollmentPaymentService::class);
                        $pendingEnrollments = $paymentService->getPendingEnrollments($record);
                        $paidEnrollments = $record->enrollments()
                            ->with(['instructorWorkshop.workshop', 'instructorWorkshop.instructor'])
                            ->where('payment_status', 'completed')
                            ->get();

                        if ($pendingEnrollments->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('no_pending')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <p class="text-sm text-blue-700">
                                                No hay inscripciones pendientes de pago en este lote.
                                            </p>
                                        </div>
                                    ')),
                            ];
                        }

                        // Información del batch
                        $batchInfo = [
                            Forms\Components\Placeholder::make('batch_info')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div><span class="font-medium">N° Ticket:</span> '.$record->batch_code.'</div>
                                            <div><span class="font-medium">Estudiante:</span> '.($record->student->full_name ?? 'N/A').'</div>
                                            <div><span class="font-medium">Total Inscripción:</span> S/ '.number_format($record->total_amount, 2).'</div>
                                            <div><span class="font-medium">Total Pagado:</span> S/ '.number_format($record->total_paid, 2).'</div>
                                            <div class="col-span-2">
                                                <span class="font-medium">Saldo Pendiente:</span>
                                                <span class="text-red-600 font-bold">S/ '.number_format($record->balance_pending, 2).'</span>
                                            </div>
                                        </div>
                                    </div>
                                ')),
                        ];

                        $paidEnrollmentsSection = [
                            Forms\Components\Section::make('Inscripciones Pagadas')
                                ->description('Talleres que ya han sido pagados en este lote')
                                ->schema([
                                    Forms\Components\Placeholder::make('paid_enrollments_list')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="space-y-2">
                                                ' . $paidEnrollments->map(function ($enrollment) {
                                                    $workshop = $enrollment->instructorWorkshop->workshop->name ?? 'N/A';
                                                    $instructor = $enrollment->instructorWorkshop->instructor
                                                        ? $enrollment->instructorWorkshop->instructor->full_name
                                                        : 'N/A';

                                                    // Día de la semana
                                                    $dayNames = [
                                                        0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié',
                                                        4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom',
                                                    ];
                                                    $dayName = $dayNames[$enrollment->instructorWorkshop->day_of_week] ?? 'N/A';

                                                    // Horario
                                                    $startTime = $enrollment->instructorWorkshop->start_time
                                                        ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->start_time)->format('H:i')
                                                        : 'N/A';
                                                    $endTime = $enrollment->instructorWorkshop->end_time
                                                        ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->end_time)->format('H:i')
                                                        : 'N/A';

                                                    // Modalidad
                                                    $modality = $enrollment->instructorWorkshop->workshop->modality ?? 'N/A';
                                                    $modalityText = match($modality) {
                                                        'voluntary' => 'Voluntario',
                                                        'hourly' => 'Por Horas',
                                                        default => ucfirst($modality)
                                                    };

                                                    $amount = number_format($enrollment->total_amount, 2);
                                                    $paymentDate = $enrollment->payment_date ? $enrollment->payment_date->format('d/m/Y') : 'N/A';

                                                    return '
                                                        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                                                            <div class="flex-1">
                                                                <div class="font-medium text-green-900">' . $workshop . '</div>
                                                                <div class="text-sm text-green-700">Instructor: ' . $instructor . '</div>
                                                                <div class="text-xs text-green-600">' . $dayName . ' ' . $startTime . '-' . $endTime . ' | ' . $modalityText . ' | Clases: ' . $enrollment->number_of_classes . '</div>
                                                                <div class="text-xs text-green-600">Pagado: ' . $paymentDate . '</div>
                                                            </div>
                                                            <div class="text-right">
                                                                <div class="font-bold text-green-900">S/ ' . $amount . '</div>
                                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                                                    ✓ Pagado
                                                                </span>
                                                            </div>
                                                        </div>
                                                    ';
                                                })->join('') . '
                                            </div>
                                        ')),
                                ])
                                ->collapsible()
                                ->collapsed(true),
                        ];

                        // Selección de inscripciones
                        $enrollmentSelection = [
                            Forms\Components\CheckboxList::make('selected_enrollments')
                                ->label('Seleccionar Inscripciones a Pagar')
                                ->options(
                                    $pendingEnrollments->mapWithKeys(function ($enrollment) {
                                        $workshop = $enrollment->instructorWorkshop->workshop->name ?? 'N/A';
                                        $instructor = $enrollment->instructorWorkshop->instructor ? $enrollment->instructorWorkshop->instructor->full_name : 'N/A';
                                        $amount = number_format($enrollment->total_amount, 2);

                                        return [
                                            $enrollment->id => "{$workshop} - {$instructor} - S/ {$amount}"
                                        ];
                                    })
                                )
                                ->required()
                                ->columns(1)
                                ->descriptions(
                                    $pendingEnrollments->mapWithKeys(function ($enrollment) {
                                        $instructorWorkshop = $enrollment->instructorWorkshop;

                                        // Obtener día de la semana
                                        $dayNames = [
                                            0 => 'Domingo',
                                            1 => 'Lunes',
                                            2 => 'Martes',
                                            3 => 'Miércoles',
                                            4 => 'Jueves',
                                            5 => 'Viernes',
                                            6 => 'Sábado',
                                            7 => 'Domingo',
                                        ];
                                        $dayName = $dayNames[$instructorWorkshop->day_of_week] ?? 'N/A';

                                        // Obtener horario
                                        $startTime = $instructorWorkshop->start_time
                                            ? \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i')
                                            : 'N/A';
                                        $endTime = $instructorWorkshop->end_time
                                            ? \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i')
                                            : 'N/A';

                                        // Obtener modalidad del taller
                                        $modality = $instructorWorkshop->workshop->modality ?? 'N/A';
                                        $modalityText = match($modality) {
                                            'voluntary' => 'Voluntario',
                                            'hourly' => 'Por Horas',
                                            default => ucfirst($modality)
                                        };

                                        return [
                                            $enrollment->id => sprintf(
                                                'Clases: %d | %s %s-%s | Modalidad: %s',
                                                $enrollment->number_of_classes,
                                                $dayName,
                                                $startTime,
                                                $endTime,
                                                $modalityText
                                            )
                                        ];
                                    })
                                )
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) use ($pendingEnrollments) {
                                    if (empty($state)) {
                                        $set('calculated_total', 0);
                                        return;
                                    }

                                    $total = $pendingEnrollments
                                        ->whereIn('id', $state)
                                        ->sum('total_amount');

                                    $set('calculated_total', $total);
                                    $set('amount_paid', $total);
                                }),

                            Forms\Components\TextInput::make('calculated_total')
                                ->label('Total Seleccionado')
                                ->prefix('S/')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(0)
                                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),
                        ];

                        // Campos de pago según método
                        if ($record->payment_method === 'link') {
                            $paymentFields = [
                                Forms\Components\TextInput::make('batch_code')
                                    ->label('Código de Voucher/Boleta')
                                    ->required()
                                    ->maxLength(50)
                                    ->helperText('Ingresa el código del voucher/boleta/ticket de pago por link'),

                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Fecha de Pago')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Textarea::make('payment_notes')
                                    ->label('Observaciones del Pago')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales sobre el pago (opcional)'),
                            ];
                        } else {
                            $paymentFields = [
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('amount_paid')
                                            ->label('Monto Recibido')
                                            ->numeric()
                                            ->prefix('S/')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $amountPaid = (float) $state;
                                                $calculatedTotal = (float) ($get('calculated_total') ?? 0);
                                                $change = $amountPaid - $calculatedTotal;
                                                $set('change_amount', max(0, $change));
                                            })
                                            ->helperText('Ingresa el monto que recibiste del estudiante'),

                                        /* Forms\Components\TextInput::make('change_amount')
                                            ->label('Vuelto')
                                            ->numeric()
                                            ->prefix('S/')
                                            ->disabled()
                                            ->default(0)
                                            ->dehydrated(true)
                                            ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2))
                                            ->helperText('Se calcula automáticamente'), */
                                    ]),

                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('Fecha de Pago')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Textarea::make('payment_notes')
                                    ->label('Observaciones del Pago')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales sobre el pago (opcional)'),
                            ];
                        }

                        return array_merge($batchInfo, $paidEnrollmentsSection, $enrollmentSelection, $paymentFields);
                    })
                    ->action(function (EnrollmentBatch $record, array $data): void {
                        $paymentService = app(\App\Services\EnrollmentPaymentService::class);

                        // Validar que se hayan seleccionado inscripciones
                        if (empty($data['selected_enrollments'])) {
                            Notification::make()
                                ->title('Error')
                                ->body('Debe seleccionar al menos una inscripción para pagar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Obtener inscripciones seleccionadas
                        $enrollments = \App\Models\StudentEnrollment::whereIn('id', $data['selected_enrollments'])->get();
                        $totalAmount = $enrollments->sum('total_amount');

                        // Validar monto para efectivo
                        if ($record->payment_method === 'cash') {
                            $amountPaid = (float) ($data['amount_paid'] ?? 0);

                            if ($amountPaid < $totalAmount) {
                                Notification::make()
                                    ->title('Monto Insuficiente')
                                    ->body("El monto recibido (S/ ".number_format($amountPaid, 2).") es menor al total seleccionado (S/ ".number_format($totalAmount, 2)."). Por favor, verifique.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        try {
                            // Procesar el pago usando el servicio
                            $payment = $paymentService->processPayment(
                                $record,
                                $data['selected_enrollments'],
                                $record->payment_method,
                                $data['payment_date'] ?? now(),
                                $data['payment_notes'] ?? null
                            );

                            // Si es efectivo, actualizar monto pagado y vuelto en el pago
                            if ($record->payment_method === 'cash') {
                                $payment->update([
                                    'amount' => $data['amount_paid'] ?? $totalAmount,
                                ]);

                                // Guardar el vuelto en las notas si hay
                                if (isset($data['change_amount']) && $data['change_amount'] > 0) {
                                    $changeNote = "\nVuelto: S/ ".number_format($data['change_amount'], 2);
                                    $payment->update([
                                        'notes' => ($payment->notes ?? '') . $changeNote
                                    ]);
                                }
                            }

                            // Generar o actualizar batch_code si es necesario
                            if ($record->payment_method === 'cash' && empty($record->batch_code)) {
                                $userId = $record->created_by ?? auth()->id();
                                $user = \App\Models\User::find($userId);

                                if ($user && !empty($user->enrollment_code)) {
                                    $userPaidEnrollmentCount = \App\Models\EnrollmentBatch::where('created_by', $userId)
                                        ->where('payment_method', 'cash')
                                        ->whereNotNull('batch_code')
                                        ->count();

                                    $nextNumber = $userPaidEnrollmentCount + 1;
                                    $batchCode = $user->enrollment_code . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                                    $record->update(['batch_code' => $batchCode]);
                                }
                            } elseif ($record->payment_method === 'link' && isset($data['batch_code'])) {
                                $record->update(['batch_code' => $data['batch_code']]);
                            }

                            // Refrescar el batch para obtener datos actualizados
                            $record->refresh();

                            $enrollmentsPaid = count($data['selected_enrollments']);
                            $totalEnrollments = $record->enrollments()->whereNull('cancelled_at')->count();
                            $statusMessage = $record->payment_status === 'completed'
                                ? 'Todas las inscripciones han sido pagadas.'
                                : "Pago parcial registrado ({$enrollmentsPaid} de {$totalEnrollments} inscripciones).";

                            Notification::make()
                                ->title('Pago Registrado Exitosamente')
                                ->body($statusMessage)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al Procesar el Pago')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Registrar Pago')
                    ->modalDescription(function (EnrollmentBatch $record) {
                        $method = $record->payment_method === 'link' ? 'Link de Pago' : 'Efectivo';
                        return "Método de pago: {$method} | Seleccione las inscripciones que desea pagar";
                    })
                    ->modalSubmitActionLabel('Registrar Pago')
                    ->modalCancelActionLabel('Cancelar')
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        $record->payment_status !== 'completed' &&
                        $record->enrollments()->where('payment_status', 'pending')->exists()
                    )
                    ->color('success')
                    ->modalWidth('4xl'),
                /* Tables\Actions\Action::make('register_payment')
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
                                                <strong>S/ '.number_format($record->total_amount, 2).'</strong>?
                                            </p>
                                        </div>
                                    ')),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('amount_paid')
                                            ->label('Monto Pagado')
                                            ->numeric()
                                            ->prefix('S/')
                                            ->required()
                                            ->default($record->total_amount)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) use ($record) {
                                                $amountPaid = (float) $state;
                                                $totalAmount = (float) $record->total_amount;
                                                $change = $amountPaid - $totalAmount;
                                                $set('change_amount', max(0, $change));
                                            })
                                            ->rules([
                                                'required',
                                                'numeric',
                                                'min:0',
                                                function () use ($record) {
                                                    return function ($attribute, $value, $fail) use ($record) {
                                                        if ((float) $value < (float) $record->total_amount) {
                                                            $fail("El monto pagado (S/ {$value}) no puede ser menor al monto total (S/ {$record->total_amount}).");
                                                        }
                                                    };
                                                }
                                            ])
                                            ->helperText('Ingresa el monto que recibiste del estudiante/socio'),

                                        Forms\Components\TextInput::make('change_amount')
                                            ->label('Vuelto')
                                            ->numeric()
                                            ->prefix('S/')
                                            ->disabled()
                                            ->default(0)
                                            ->dehydrated(true)
                                            ->helperText('Se calcula automáticamente'),
                                    ]),

                                Forms\Components\Textarea::make('payment_notes')
                                    ->label('Observaciones del Pago')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales sobre el pago (opcional)'),
                            ];
                        }
                    })
                    ->action(function (EnrollmentBatch $record, array $data): void {
                        // VALIDACIÓN: Verificar que el monto pagado sea suficiente
                        if ($record->payment_method === 'cash') {
                            $amountPaid = (float) ($data['amount_paid'] ?? 0);
                            $totalAmount = (float) $record->total_amount;

                            if ($amountPaid < $totalAmount) {
                                Notification::make()
                                    ->title('Error en el Pago')
                                    ->body("El monto pagado (S/ ".number_format($amountPaid, 2).") es insuficiente. Se requiere S/ ".number_format($totalAmount, 2))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                return;
                            }
                        }

                        // Generar batch_code al momento del pago
                        $batchCode = null;
                        if ($record->payment_method === 'cash') {
                            $userId = $record->created_by ?? auth()->id();
                            $user = \App\Models\User::find($userId);

                            if ($user && !empty($user->enrollment_code)) {
                                $userPaidEnrollmentCount = \App\Models\EnrollmentBatch::where('created_by', $userId)
                                    ->where('payment_method', 'cash')
                                    ->where('payment_status', 'completed')
                                    ->whereNotNull('batch_code')
                                    ->count();

                                $nextNumber = $userPaidEnrollmentCount + 1;
                                $batchCode = $user->enrollment_code . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                            }
                        } elseif ($record->payment_method === 'link' && isset($data['batch_code'])) {
                            $batchCode = $data['batch_code'];
                        }

                        $updates = [
                            'payment_status' => 'completed',
                            'payment_date' => now(),
                            'payment_registered_by_user_id' => auth()->id(),
                            'payment_registered_at' => now(),
                            'paid_by' => auth()->id(),
                        ];

                        // Si es pago por link, también actualizar el código
                        if ($batchCode) {
                            $updates['batch_code'] = $batchCode;
                        }

                        // Si es pago en efectivo, guardar monto pagado y vuelto
                            if ($record->payment_method === 'cash') {
                                $updates['amount_paid'] = $data['amount_paid'] ?? $record->total_amount;
                                $updates['change_amount'] = $data['change_amount'] ?? 0;
                            }

                        // Agregar notas si se proporcionaron
                        if (! empty($data['payment_notes'])) {
                            $existingNotes = $record->notes ? $record->notes."\n\n" : '';
                            $paymentInfo = $record->payment_method === 'cash'
                                ? " (Pagado: S/ ".number_format($data['amount_paid'] ?? 0, 2).", Vuelto: S/ ".number_format($data['change_amount'] ?? 0, 2).")"
                                : "";
                            $updates['notes'] = $existingNotes.'Pago registrado por '.auth()->user()->name.' el '.now()->format('d/m/Y H:i').$paymentInfo.":\n".$data['payment_notes'];
                        }

                        $record->update($updates);

                        // Actualicación que también funciona para disparar observers:
                        foreach ($record->enrollments as $enrollment) {
                            $enrollment->update([
                                'payment_status' => 'completed',
                                'payment_date' => now(),
                            ]);
                        }

                        $message = $record->payment_method === 'link'
                            ? "Pago registrado exitosamente. Código: {$data['batch_code']}"
                            : 'Pago en efectivo confirmado exitosamente';

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
                    ->visible(fn (EnrollmentBatch $record): bool => $record->payment_status === 'pending'
                    )
                    ->color('success'), */
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

                                // Actualizar cada enrollment individualmente para disparar observers de pago de profesores
                                foreach ($record->enrollments as $enrollment) {
                                    $enrollment->update([
                                        'payment_status' => 'refunded',
                                    ]);
                                }
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
                    ->visible(fn (EnrollmentBatch $record): bool => in_array($record->payment_status, ['pending', 'completed'])
                    ),
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
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        in_array($record->payment_status, ['pending', 'to_pay'])
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
