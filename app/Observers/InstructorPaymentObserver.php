<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\ExpenseDetail;
use App\Models\InstructorPayment;

class InstructorPaymentObserver
{
    /**
     * Handle the InstructorPayment "updated" event.
     */
    public function updated(InstructorPayment $instructorPayment): void
    {
        // Verificar si el estado cambió a 'paid' y no existía antes
        if ($instructorPayment->isDirty('payment_status') &&
            $instructorPayment->payment_status === 'paid' &&
            $instructorPayment->getOriginal('payment_status') === 'pending') {

            $this->createInstructorPaymentExpense($instructorPayment);
        }
    }

    /**
     * Crear un egreso automático para el pago del instructor
     */
    private function createInstructorPaymentExpense(InstructorPayment $instructorPayment): void
    {
        // Crear el registro principal de gasto (sin vale_code)
        $expense = Expense::create([
            'concept' => 'Pago a Profesores',
            'vale_code' => null, // No generar vale automáticamente
        ]);

        // Crear el detalle del gasto
        ExpenseDetail::create([
            'expense_id' => $expense->id,
            'date' => $instructorPayment->payment_date ?? now()->toDateString(),
            'razon_social' => 'AELU', // Siempre AELU por defecto
            'document_number' => $instructorPayment->document_number, // Usar el document_number del InstructorPayment
            'amount' => $instructorPayment->calculated_amount,
            'notes' => $this->generatePaymentNotes($instructorPayment),
        ]);
    }

    /**
     * Generar notas descriptivas del pago
     */
    private function generatePaymentNotes(InstructorPayment $instructorPayment): string
    {
        $instructor = $instructorPayment->instructor;
        $period = $instructorPayment->monthlyPeriod;
        $workshop = $instructorPayment->instructorWorkshop;

        $periodName = \Carbon\Carbon::create()
            ->month($period->month)
            ->year($period->year)
            ->translatedFormat('F Y');

        $daysOfWeek = $workshop->day_of_week;
        if (is_array($daysOfWeek)) {
            $dayName = implode('/', $daysOfWeek);
        } else {
            $dayName = $daysOfWeek ?? 'N/A';
        }
        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');

        $notes = "Pago a instructor: {$instructor->first_names} {$instructor->last_names}\n";
        $notes .= "Período: {$periodName}\n";
        $notes .= "Taller: {$workshop->workshop->name}\n";
        $notes .= "Horario: {$dayOfWeek} {$startTime}-{$endTime}\n";
        $notes .= 'Tipo de pago: '.($instructorPayment->payment_type === 'volunteer' ? 'Voluntario (% de ingresos)' : 'Por horas (tarifa fija)');

        // Agregar detalles específicos según el tipo de pago
        if ($instructorPayment->payment_type === 'volunteer') {
            $percentage = ($instructorPayment->applied_volunteer_percentage ?? 0) * 100;
            $revenue = $instructorPayment->monthly_revenue ?? 0;
            $students = $instructorPayment->total_students ?? 0;

            $notes .= "\nDetalles: {$students} estudiantes, S/ ".number_format($revenue, 2)." recaudado × {$percentage}%";
        } else {
            $hours = $instructorPayment->total_hours ?? 0;
            $rate = $instructorPayment->applied_hourly_rate ?? 0;

            $notes .= "\nDetalles: {$hours} horas × S/ ".number_format($rate, 2).' por hora';
        }

        return $notes;
    }
}
