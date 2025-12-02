<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Actions;

use App\Models\EnrollmentBatch;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class RegisterPaymentAction
{
    public static function make(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('register_payment')
            ->label('Pago')
            ->icon('heroicon-o-currency-dollar')
            ->form(function (EnrollmentBatch $record) {
                $paymentService = app(\App\Services\EnrollmentPaymentService::class);

                // === PAGO POR LINK (Simplificado - Pago Completo) ===
                if ($record->payment_method === 'link') {
                    return [
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content(new HtmlString('
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
                            ->content(new HtmlString('
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
                        ->content(new HtmlString('
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
                                    ->content(new HtmlString('
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

                                                return '
                                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                                        <div class="flex justify-between items-start">
                                                            <div class="flex-1">
                                                                <h4 class="font-medium text-green-800">'.$workshop.'</h4>
                                                                <p class="text-sm text-green-700">Instructor: '.$instructor.'</p>
                                                                <p class="text-xs text-green-600">'.$dayName.' '.$startTime.'-'.$endTime.' | '.$modalityText.' | Clases: '.$enrollment->number_of_classes.'</p>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                                                    S/ '.number_format($enrollment->total_amount, 2).'
                                                                </span>
                                                            </div>
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

                return array_merge(
                    $batchInfo,
                    $paidEnrollmentsSection,
                    $enrollmentSelection,
                    $paymentFields
                );
            })
            ->action(function (EnrollmentBatch $record, array $data): void {
                $paymentService = app(\App\Services\EnrollmentPaymentService::class);

                // === LÓGICA PARA PAGO POR LINK (Pago Completo) ===
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

                        // Actualizar el batch_code
                        $record->update(['batch_code' => $data['batch_code']]);

                        // Procesar el pago completo
                        $payment = $paymentService->processPayment(
                            $record,
                            $pendingEnrollments,
                            'link',
                            $data['payment_date'] ?? now(),
                            $data['payment_notes'] ?? null
                        );

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
            ->modalWidth('4xl');
    }
}
