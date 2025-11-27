<?php

namespace App\Services;

use App\Models\EnrollmentBatch;
use App\Models\EnrollmentPayment;
use App\Models\StudentEnrollment;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnrollmentPaymentService
{
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
            if (!Auth::check()) {
                throw new \Exception('Debe haber un usuario autenticado para generar el ticket.');
            }

            if ($paymentMethod === 'link') {
                $ticketCode = $batch->batch_code;
            } else {
                $ticketCode = $this->generateTicketCode(Auth::id());
            }

            $ticket = \App\Models\Ticket::create([
                'ticket_code' => $ticketCode,
                'enrollment_batch_id' => $batch->id,
                'enrollment_payment_id' => $payment->id,
                'student_id' => $batch->student_id,
                'total_amount' => $totalAmount,
                'ticket_type' => 'enrollment',
                'status' => 'active',
            ]);

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
        } else {
            // Mantener en pending si aún hay inscripciones sin pagar
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
    private function generateTicketCode(int $userId): string
    {
        $user = \App\Models\User::find($userId);

        if (!$user || empty($user->enrollment_code)) {
            throw new \Exception('El usuario no tiene código de inscripción configurado.');
        }

        // Contar solo los tickets que son pago en efectivo
        $userTicketCount = \App\Models\Ticket::where('issued_by_user_id', $userId)
            ->whereHas('enrollmentPayment', function ($query) {
                $query->where('payment_method', 'cash');
            })
            ->count();

        $nextNumber = $userTicketCount + 1;

        return $user->enrollment_code . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
