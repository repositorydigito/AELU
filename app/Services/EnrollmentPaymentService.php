<?php

namespace App\Services;

use App\Models\EnrollmentBatch;
use App\Models\EnrollmentPayment;
use App\Models\StudentCredit;
use App\Models\StudentEnrollment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnrollmentPaymentService
{
    /**
     * Aplica un crédito de recuperación (RN-D3: paga solo la diferencia) a una
     * inscripción específica del mes siguiente. Si el crédito no cubre el total,
     * el saldo restante se cobra con $remainingPaymentMethod en el mismo lote.
     */
    public function processPaymentWithCredit(
        EnrollmentBatch $batch,
        StudentEnrollment $enrollment,
        StudentCredit $credit,
        string $remainingPaymentMethod,
        string $paymentDate,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($batch, $enrollment, $credit, $remainingPaymentMethod, $paymentDate, $notes) {
            if ($enrollment->enrollment_batch_id !== $batch->id) {
                throw new \Exception('La inscripción no pertenece a este lote.');
            }

            if ($enrollment->payment_status === 'completed') {
                throw new \Exception("La inscripción {$enrollment->id} ya está pagada.");
            }

            if (! $credit->isApplicableTo($enrollment)) {
                throw new \Exception('El crédito no es aplicable a esta inscripción (vencido, ya consumido, o taller distinto).');
            }

            if (! Auth::check()) {
                throw new \Exception('Debe haber un usuario autenticado para generar el ticket.');
            }

            $creditApplied = min((float) $credit->amount, (float) $enrollment->total_amount);
            $remaining = round((float) $enrollment->total_amount - $creditApplied, 2);

            $creditPayment = EnrollmentPayment::create([
                'enrollment_batch_id' => $batch->id,
                'amount' => $creditApplied,
                'payment_method' => 'credito',
                'payment_date' => $paymentDate,
                'status' => 'completed',
                'notes' => "Crédito de recuperación #{$credit->id} aplicado.",
            ]);
            $creditPayment->paymentItems()->create([
                'student_enrollment_id' => $enrollment->id,
                'amount' => $creditApplied,
            ]);

            $credit->update([
                'status' => 'consumed',
                'consumed_at' => now(),
                'consumed_student_enrollment_id' => $enrollment->id,
            ]);

            $remainingPayment = null;
            if ($remaining > 0) {
                $remainingPayment = EnrollmentPayment::create([
                    'enrollment_batch_id' => $batch->id,
                    'amount' => $remaining,
                    'payment_method' => $remainingPaymentMethod,
                    'payment_date' => $paymentDate,
                    'status' => 'completed',
                    'notes' => $notes,
                ]);
                $remainingPayment->paymentItems()->create([
                    'student_enrollment_id' => $enrollment->id,
                    'amount' => $remaining,
                ]);
            }

            $enrollment->update([
                'payment_status' => 'completed',
                'payment_method' => $remaining > 0 ? $remainingPaymentMethod : 'credito',
                'payment_date' => $paymentDate,
            ]);

            $ticketMethod = $remaining > 0 ? $remainingPaymentMethod : 'cash';
            $ticketPayment = $remainingPayment ?? $creditPayment;
            $ticket = $this->createTicketWithUniqueCode($batch, $ticketPayment, $ticketMethod, (float) $enrollment->total_amount);
            $ticket->studentEnrollments()->attach($enrollment->id);

            $this->updateBatchStatus($batch);

            return [
                'credit_payment' => $creditPayment,
                'remaining_payment' => $remainingPayment,
                'ticket' => $ticket,
                'credit_applied' => $creditApplied,
                'remaining' => $remaining,
            ];
        });
    }

    public function processPayment(EnrollmentBatch $batch, array $studentEnrollmentIds, string $paymentMethod, string $paymentDate, ?string $notes = null): EnrollmentPayment
    {
        return DB::transaction(function () use ($batch, $studentEnrollmentIds, $paymentMethod, $paymentDate, $notes) {

            // 1. Validar que las inscripciones pertenezcan al batch
            $enrollments = StudentEnrollment::whereIn('id', $studentEnrollmentIds)
                ->where('enrollment_batch_id', $batch->id)
                ->get();

            if ($enrollments->count() !== count($studentEnrollmentIds)) {
                throw new \Exception('Algunas inscripciones no pertenecen a este lote.');
            }

            // 2. Validar que ninguna esté ya pagada o cancelada
            foreach ($enrollments as $enrollment) {
                if ($enrollment->payment_status === 'completed') {
                    throw new \Exception("La inscripción {$enrollment->id} ya está pagada.");
                }
                if ($enrollment->payment_status === 'cancelled') {
                    throw new \Exception("La inscripción {$enrollment->id} está cancelada.");
                }
            }

            // 3. Calcular el monto total a pagar
            $totalAmount = $enrollments->sum('total_amount');

            // 4. Crear el registro de pago
            $payment = EnrollmentPayment::create([
                'enrollment_batch_id' => $batch->id,
                'amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => $paymentDate,
                'status' => 'completed',
                'notes' => $notes,
            ]);

            // 5. Crear los items de pago y actualizar cada inscripción
            foreach ($enrollments as $enrollment) {
                // Crear item de pago
                $payment->paymentItems()->create([
                    'student_enrollment_id' => $enrollment->id,
                    'amount' => $enrollment->total_amount,
                ]);

                // Actualizar estado de la inscripción individual
                $enrollment->update([
                    'payment_status' => 'completed',
                    'payment_method' => $paymentMethod,
                    'payment_date' => $paymentDate,
                ]);
            }

            // 6. Crear el ticket para este pago
            if (! Auth::check()) {
                throw new \Exception('Debe haber un usuario autenticado para generar el ticket.');
            }

            $ticket = $this->createTicketWithUniqueCode($batch, $payment, $paymentMethod, $totalAmount);

            // 7. Relacionar el ticket con las inscripciones pagadas
            $ticket->studentEnrollments()->attach($enrollments->pluck('id'));

            // 8. Actualizar el batch
            $this->updateBatchStatus($batch);

            return $payment;
        });
    }

    public function updateBatchStatus(EnrollmentBatch $batch): void
    {
        $batch->refresh();

        $totalEnrollments = $batch->enrollments()->whereNull('cancelled_at')->count();
        $completedEnrollments = $batch->enrollments()
            ->whereNull('cancelled_at')
            ->where('payment_status', 'completed')
            ->count();

        // Actualizar campos legacy del batch
        $batch->amount_paid = $batch->total_paid;

        // Si todas las inscripciones están pagadas, el batch está completo
        if ($totalEnrollments > 0 && $completedEnrollments === $totalEnrollments) {
            $batch->payment_status = 'completed';

            // Actualizar con info del último pago
            $lastPayment = $batch->payments()->latest()->first();
            if ($lastPayment) {
                $batch->payment_method = $lastPayment->payment_method;
                $batch->payment_date = $lastPayment->payment_date;
                $batch->payment_registered_by_user_id = $lastPayment->registered_by_user_id;
                $batch->payment_registered_at = $lastPayment->registered_at;
            }
        } elseif ($completedEnrollments > 0) {
            // Si hay PAGOS PARCIALES (algunas completed, otras pending)
            // Cambiar a 'to_pay' para proteger del cronjob auto-cancel
            $batch->payment_status = 'to_pay';
        } else {
            // Si NO hay ningún pago, mantener en 'pending'
            // (estos sí serán cancelados por el cronjob)
            $batch->payment_status = 'pending';
        }

        $batch->save();
    }

    public function getPendingEnrollments(EnrollmentBatch $batch)
    {
        return $batch->enrollments()
            ->with(['instructorWorkshop.workshop', 'instructorWorkshop.instructor'])
            ->whereNull('cancelled_at')
            ->where('payment_status', 'pending')
            ->get();
    }

    public function validatePaymentAmount(array $studentEnrollmentIds, float $expectedAmount): bool
    {
        $totalAmount = StudentEnrollment::whereIn('id', $studentEnrollmentIds)
            ->sum('total_amount');

        return abs($totalAmount - $expectedAmount) < 0.01;
    }

    /**
     * Crea el ticket del pago con reintento ante colisión del índice único
     * de ticket_code (red de seguridad del correlativo global).
     */
    private function createTicketWithUniqueCode(EnrollmentBatch $batch, EnrollmentPayment $payment, string $paymentMethod, float $totalAmount): \App\Models\Ticket
    {
        $attempts = 0;

        do {
            if ($paymentMethod === 'link') {
                // Para pagos por link, agregar el prefijo del usuario al código ingresado manualmente
                $ticketCode = $this->generateTicketCodeForLink(Auth::id(), $batch->batch_code);
            } else {
                $ticketCode = $this->generateTicketCode(Auth::id());
            }

            try {
                return \App\Models\Ticket::create([
                    'ticket_code' => $ticketCode,
                    'enrollment_batch_id' => $batch->id,
                    'enrollment_payment_id' => $payment->id,
                    'student_id' => $batch->student_id,
                    'total_amount' => $totalAmount,
                    'ticket_type' => 'enrollment',
                    'status' => 'active',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = violación de unicidad (ticket_code duplicado): tomar el siguiente correlativo
                if ($e->getCode() !== '23000' || ++$attempts >= 3) {
                    throw $e;
                }
            }
        } while (true);
    }

    /**
     * Correlativo atómico GLOBAL (RN-C3): un único contador compartido por
     * TODOS los cajeros, no uno por usuario. Incrementa la fila
     * `system_settings.key = 'global_ticket_seq'` bajo row lock
     * (debe ejecutarse dentro de la transacción del pago), por lo que
     * serializa la emisión de tickets de todo el sistema.
     */
    private function getNextSequential(): int
    {
        $current = DB::table('system_settings')
            ->where('key', 'global_ticket_seq')
            ->lockForUpdate()
            ->value('value');

        $next = ((int) $current) + 1;

        DB::table('system_settings')
            ->where('key', 'global_ticket_seq')
            ->update(['value' => (string) $next]);

        return $next;
    }

    private function generateTicketCode(int $userId): string
    {
        $user = \App\Models\User::find($userId);

        if (! $user || empty($user->enrollment_code)) {
            throw new \Exception('El usuario no tiene código de inscripción configurado.');
        }

        $nextNumber = $this->getNextSequential();

        return $user->enrollment_code.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function generateTicketCodeForLink(int $userId, string $manualCode): string
    {
        $user = \App\Models\User::find($userId);

        if (! $user || empty($user->enrollment_code)) {
            throw new \Exception('El usuario no tiene código de inscripción configurado.');
        }

        $nextNumber = $this->getNextSequential();
        $manualCode = trim($manualCode);

        return $user->enrollment_code.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT).'-'.$manualCode;
    }
}
