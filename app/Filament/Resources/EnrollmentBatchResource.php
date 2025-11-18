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

                Tables\Columns\TextColumn::make('cancelledBy.name')
                    ->label('Anulado Por')
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
                    ->label('Ver Tickets')
                    ->icon('heroicon-o-ticket')
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        $record->tickets()->where('status', 'active')->exists()
                    )
                    ->modalHeading(fn (EnrollmentBatch $record) => 'Tickets de ' . ($record->student->full_name ?? 'N/A'))
                    ->modalDescription(fn (EnrollmentBatch $record) =>
                        'Total de tickets emitidos: ' . $record->tickets()->where('status', 'active')->count()
                    )
                    ->modalContent(fn (EnrollmentBatch $record) => view('filament.modals.tickets-list', [
                        'tickets' => $record->tickets()
                            ->where('status', 'active')
                            ->with(['studentEnrollments.instructorWorkshop.workshop', 'issuedByUser'])
                            ->orderBy('issued_at', 'asc')
                            ->get()
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

                        // === PAGO POR LINK (Simplificado - Pago Completo) ===
                        if ($record->payment_method === 'link') {
                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <h3 class="font-semibold text-blue-800 mb-2">Confirmar Pago por Link</h3>
                                            <p class="text-sm text-blue-700">
                                                Se registrará el pago completo de todas las inscripciones por un monto total de
                                                <strong>S/ '.number_format($record->total_amount, 2).'</strong>
                                            </p>
                                        </div>
                                    ')),

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
                        }

                        // === PAGO EN EFECTIVO (Con selección de inscripciones - Pagos Parciales) ===
                        $pendingEnrollments = $paymentService->getPendingEnrollments($record);

                        // Inscripciones pagadas
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
                                            <div><span class="font-medium">Código:</span> '.$record->batch_code.'</div>
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

                        // Inscripciones pagadas (si existen)
                        $paidEnrollmentsSection = [];
                        if ($paidEnrollments->isNotEmpty()) {
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

                                                        $daysOfWeek = $enrollment->instructorWorkshop->day_of_week;
                                                        if (is_array($daysOfWeek)) {
                                                            $dayAbbreviations = [
                                                                'Lunes' => 'Lun', 'Martes' => 'Mar', 'Miércoles' => 'Mié',
                                                                'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sábado' => 'Sáb', 'Domingo' => 'Dom',
                                                            ];
                                                            $dayName = implode('/', array_map(fn($day) => $dayAbbreviations[$day] ?? $day, $daysOfWeek));
                                                        } else {
                                                            $dayName = $daysOfWeek ?? 'N/A';
                                                        }

                                                        $startTime = $enrollment->instructorWorkshop->start_time
                                                            ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->start_time)->format('H:i')
                                                            : 'N/A';
                                                        $endTime = $enrollment->instructorWorkshop->end_time
                                                            ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->end_time)->format('H:i')
                                                            : 'N/A';

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
                        }

                        // Selección de inscripciones
                        $enrollmentSelection = [
                            Forms\Components\CheckboxList::make('selected_enrollments')
                                ->label('Seleccionar Inscripciones a Pagar')
                                ->options(
                                    $pendingEnrollments->mapWithKeys(function ($enrollment) {
                                        $workshop = $enrollment->instructorWorkshop->workshop->name ?? 'N/A';
                                        $instructor = $enrollment->instructorWorkshop->instructor
                                            ? $enrollment->instructorWorkshop->instructor->full_name
                                            : 'N/A';
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

                                        $daysOfWeek = $instructorWorkshop->day_of_week;
                                        if (is_array($daysOfWeek)) {
                                            $dayName = implode('/', $daysOfWeek);
                                        } else {
                                            $dayName = $daysOfWeek ?? 'N/A';
                                        }

                                        $startTime = $instructorWorkshop->start_time
                                            ? \Carbon\Carbon::parse($instructorWorkshop->start_time)->format('H:i')
                                            : 'N/A';
                                        $endTime = $instructorWorkshop->end_time
                                            ? \Carbon\Carbon::parse($instructorWorkshop->end_time)->format('H:i')
                                            : 'N/A';

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

                        // Campos de pago en efectivo
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

                                    Forms\Components\TextInput::make('change_amount')
                                        ->label('Vuelto')
                                        ->numeric()
                                        ->prefix('S/')
                                        ->disabled()
                                        ->default(0)
                                        ->dehydrated(true)
                                        ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2))
                                        ->helperText('Se calcula automáticamente'),
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

                        return array_merge($batchInfo, $paidEnrollmentsSection, $enrollmentSelection, $paymentFields);
                    })
                    ->action(function (EnrollmentBatch $record, array $data): void {
                        $paymentService = app(\App\Services\EnrollmentPaymentService::class);

                        // === LÓGICA PARA PAGO POR LINK (Completo) ===
                        if ($record->payment_method === 'link') {
                            try {
                                // Obtener todas las inscripciones pendientes
                                $pendingEnrollments = $record->enrollments()
                                    ->where('payment_status', 'pending')
                                    ->pluck('id')
                                    ->toArray();

                                if (empty($pendingEnrollments)) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->body('No hay inscripciones pendientes de pago.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Procesar el pago completo
                                $payment = $paymentService->processPayment(
                                    $record,
                                    $pendingEnrollments,
                                    'link',
                                    $data['payment_date'] ?? now(),
                                    $data['payment_notes'] ?? null
                                );

                                // Actualizar el batch_code
                                $record->update(['batch_code' => $data['batch_code']]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Pago Registrado Exitosamente')
                                    ->body("Pago por link confirmado. Código: {$data['batch_code']}")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error al Procesar el Pago')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                            return;
                        }

                        // === LÓGICA PARA PAGO EN EFECTIVO (Parcial) ===
                        // Validar que se hayan seleccionado inscripciones
                        if (empty($data['selected_enrollments'])) {
                            \Filament\Notifications\Notification::make()
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
                        $amountPaid = (float) ($data['amount_paid'] ?? 0);

                        if ($amountPaid < $totalAmount) {
                            \Filament\Notifications\Notification::make()
                                ->title('Monto Insuficiente')
                                ->body("El monto recibido (S/ ".number_format($amountPaid, 2).") es menor al total seleccionado (S/ ".number_format($totalAmount, 2)."). Por favor, verifique.")
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            // Procesar el pago usando el servicio
                            $payment = $paymentService->processPayment(
                                $record,
                                $data['selected_enrollments'],
                                'cash',
                                $data['payment_date'] ?? now(),
                                $data['payment_notes'] ?? null
                            );

                            // Actualizar monto pagado y vuelto en el pago
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

                            // Refrescar el batch
                            $record->refresh();

                            $enrollmentsPaid = count($data['selected_enrollments']);
                            $totalEnrollments = $record->enrollments()->count();
                            $statusMessage = $record->payment_status === 'completed'
                                ? 'Todas las inscripciones han sido pagadas.'
                                : "Pago parcial registrado ({$enrollmentsPaid} de {$totalEnrollments} inscripciones).";

                            \Filament\Notifications\Notification::make()
                                ->title('Pago Registrado Exitosamente')
                                ->body($statusMessage)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al Procesar el Pago')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Registrar Pago')
                    ->modalDescription(function (EnrollmentBatch $record) {
                        $method = $record->payment_method === 'link' ? 'Link de Pago' : 'Efectivo';
                        $description = $record->payment_method === 'link'
                            ? 'Se registrará el pago completo de todas las inscripciones'
                            : 'Seleccione las inscripciones que desea pagar';
                        return "Método de pago: {$method} | {$description}";
                    })
                    ->modalSubmitActionLabel('Registrar Pago')
                    ->modalCancelActionLabel('Cancelar')
                    ->visible(fn (EnrollmentBatch $record): bool =>
                        $record->payment_status !== 'completed' &&
                        $record->enrollments()->where('payment_status', 'pending')->exists()
                    )
                    ->color('success')
                    ->modalWidth('4xl'),
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
