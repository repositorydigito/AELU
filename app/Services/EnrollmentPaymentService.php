<?php

namespace App\Services;

use App\Models\EnrollmentBatch;
use App\Models\EnrollmentPayment;
use App\Models\StudentEnrollment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnrollmentPaymentService
{
    public function processPayment(EnrollmentBatch $batch, array $studentEnrollmentIds, string $paymentMethod, string $paymentDate, ?string $notes = null): EnrollmentPayment
    {
        return DB::transaction(function () use ($batch, $studentEnrollmentIds, $paymentMethod, $paymentDate, $notes) {

            $enrollments = StudentEnrollment::whereIn('id', $studentEnrollmentIds)
                ->where('enrollment_batch_id', $batch->id)
                ->get();

            if ($enrollments->count() !== count($studentEnrollmentIds)) {
                throw new \Exception('Algunas inscripciones no pertenecen a este lote.');
            }

            foreach ($enrollments as $enrollment) {
                if ($enrollment->payment_status === 'completed') {
                    throw new \Exception("La inscripción {$enrollment->id} ya está pagada.");
                }
                if ($enrollment->payment_status === 'cancelled') {
                    throw new \Exception("La inscripción {$enrollment->id} está cancelada.");
                }
            }

            $totalAmount = $enrollments->sum('total_amount');

            $payment = EnrollmentPayment::create([
                'enrollment_batch_id' => $batch->id,
                'amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => $paymentDate,
                'status' => 'completed',
                'notes' => $notes,
            ]);

            foreach ($enrollments as $enrollment) {
                $payment->paymentItems()->create([
                    'student_enrollment_id' => $enrollment->id,
                    'amount' => $enrollment->total_amount,
                ]);

                $enrollment->update([
                    'payment_status' => 'completed',
                    'payment_method' => $paymentMethod,
                    'payment_date' => $paymentDate,
                ]);
            }

            if (! Auth::check()) {
                throw new \Exception('Debe haber un usuario autenticado para generar el ticket.');
            }

            if ($paymentMethod === 'link') {
                $ticketCode = $this->generateTicketCodeForLink(Auth::id(), $batch->batch_code);
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

            $ticket->studentEnrollments()->attach($enrollments->pluck('id'));

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

        $batch->amount_paid = $batch->total_paid;

        if ($totalEnrollments > 0 && $completedEnrollments === $totalEnrollments) {
            $batch->payment_status = 'completed';

            $lastPayment = $batch->payments()->latest()->first();
            if ($lastPayment) {
                $batch->payment_method = $lastPayment->payment_method;
                $batch->payment_date = $lastPayment->payment_date;
                $batch->payment_registered_by_user_id = $lastPayment->registered_by_user_id;
                $batch->payment_registered_at = $lastPayment->registered_at;
            }
        } elseif ($completedEnrollments > 0) {
            $batch->payment_status = 'to_pay';
        } else {
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

        if (! $user || empty($user->enrollment_code)) {
            throw new \Exception('El usuario no tiene código de inscripción configurado.');
        }

        $lastTicketNumber = \App\Models\Ticket::whereHas('enrollmentPayment', function ($query) {
            $query->where('payment_method', 'cash');
        })
            ->where('issued_by_user_id', $userId)
            ->where('ticket_code', 'LIKE', $user->enrollment_code.'-%')
            ->get()
            ->map(function ($ticket) {
                $parts = explode('-', $ticket->ticket_code);

                return isset($parts[1]) ? intval($parts[1]) : 0;
            })
            ->max();

        $nextNumber = ($lastTicketNumber ?? 0) + 1;

        return $user->enrollment_code.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function generateTicketCodeForLink(int $userId, string $manualCode): string
    {
        $manualCode = trim($manualCode);

        $lastTicketNumber = \App\Models\Ticket::whereHas('enrollmentPayment', function ($query) {
            $query->where('payment_method', 'link');
        })
            ->get()
            ->filter(function ($ticket) {
                $parts = explode('-', $ticket->ticket_code, 2);
                return isset($parts[0]) && strlen($parts[0]) === 3 && ctype_digit($parts[0]);
            })
            ->map(function ($ticket) {
                $parts = explode('-', $ticket->ticket_code, 2);
                return isset($parts[0]) ? intval($parts[0]) : 0;
            })
            ->max();

        $nextNumber = ($lastTicketNumber ?? 0) + 1;

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT).'-'.$manualCode;
    }
}
