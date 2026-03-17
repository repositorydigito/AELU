<?php

namespace App\Observers;

use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyInstructorRate;
use Illuminate\Support\Facades\DB;

class InstructorWorkshopObserver
{
    public function updated(InstructorWorkshop $instructorWorkshop): void
    {
        if ($instructorWorkshop->isDirty(['payment_type', 'custom_volunteer_percentage', 'hourly_rate', 'duration_hours'])) {
            $this->recalculatePendingPayments($instructorWorkshop);
        }
    }

    private function recalculatePendingPayments(InstructorWorkshop $instructorWorkshop): void
    {
        $pendingPayments = InstructorPayment::where('instructor_workshop_id', $instructorWorkshop->id)
            ->where('payment_status', 'pending')
            ->get();

        foreach ($pendingPayments as $payment) {
            $monthlyPeriodId = $payment->monthly_period_id;

            $monthlyRate = MonthlyInstructorRate::where('monthly_period_id', $monthlyPeriodId)
                ->where('is_active', true)
                ->first();

            if ($instructorWorkshop->payment_type === 'volunteer') {
                $appliedVolunteerPercentage = $instructorWorkshop->custom_volunteer_percentage ?? 50.0;

                $monthlyRevenue = DB::table('student_enrollments')
                    ->where('instructor_workshop_id', $instructorWorkshop->id)
                    ->where('monthly_period_id', $monthlyPeriodId)
                    ->where('payment_status', 'completed')
                    ->sum('total_amount');

                $totalStudents = DB::table('student_enrollments')
                    ->where('instructor_workshop_id', $instructorWorkshop->id)
                    ->where('monthly_period_id', $monthlyPeriodId)
                    ->where('payment_status', 'completed')
                    ->distinct('student_id')
                    ->count('student_id');

                $calculatedAmount = $monthlyRevenue * ($appliedVolunteerPercentage / 100);

                $payment->update([
                    'payment_type'                => 'volunteer',
                    'total_students'              => $totalStudents,
                    'monthly_revenue'             => $monthlyRevenue,
                    'volunteer_percentage'        => $monthlyRate?->volunteer_percentage,
                    'applied_volunteer_percentage' => $appliedVolunteerPercentage / 100,
                    'calculated_amount'           => round($calculatedAmount, 2),
                    'total_hours'                 => null,
                    'hourly_rate'                 => null,
                    'applied_hourly_rate'         => null,
                ]);

            } elseif ($instructorWorkshop->payment_type === 'hourly') {
                $appliedHourlyRate = $instructorWorkshop->hourly_rate ?? 0;

                $durationHours = $instructorWorkshop->duration_hours;
                if (!$durationHours && $instructorWorkshop->workshop) {
                    $durationHours = ($instructorWorkshop->workshop->duration ?? 60) / 60;
                }
                $durationHours = $durationHours ?? 1;

                $classesCount = DB::table('workshop_classes')
                    ->where('monthly_period_id', $monthlyPeriodId)
                    ->whereIn('workshop_id', $instructorWorkshop->workshop ? [$instructorWorkshop->workshop->id] : [])
                    ->whereIn('status', ['scheduled', 'completed'])
                    ->count();

                $totalHours = $classesCount * $durationHours;
                $calculatedAmount = $totalHours * $appliedHourlyRate;

                $payment->update([
                    'payment_type'                => 'hourly',
                    'total_hours'                 => $totalHours,
                    'hourly_rate'                 => $instructorWorkshop->hourly_rate,
                    'applied_hourly_rate'         => $appliedHourlyRate,
                    'calculated_amount'           => round($calculatedAmount, 2),
                    'monthly_revenue'             => null,
                    'total_students'              => null,
                    'volunteer_percentage'        => null,
                    'applied_volunteer_percentage' => null,
                ]);
            }
        }
    }

    public function creating(InstructorWorkshop $instructorWorkshop): void
    {
        // LÓGICA ORIGINAL: Solo establecer initial_monthly_period_id si no está ya definido
        if (empty($instructorWorkshop->initial_monthly_period_id) && !empty($instructorWorkshop->workshop_id)) {
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }
        }

        // Sincronizar campos del Workshop solo si están vacíos
        $this->syncEmptyFieldsFromWorkshop($instructorWorkshop);
    }

    public function updating(InstructorWorkshop $instructorWorkshop): void
    {
        // LÓGICA ORIGINAL: Si cambia el workshop_id, actualizar también el initial_monthly_period_id
        if ($instructorWorkshop->isDirty('workshop_id') && !empty($instructorWorkshop->workshop_id)) {
            $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);

            if ($workshop && $workshop->monthly_period_id) {
                $instructorWorkshop->initial_monthly_period_id = $workshop->monthly_period_id;
            }

            // NUEVA FUNCIONALIDAD: Al cambiar workshop, sincronizar todos los campos
            $this->syncAllFieldsFromWorkshop($instructorWorkshop, $workshop);
        }
    }

    /**
     * Sincronizar solo campos vacíos desde el Workshop (para crear)
     */
    private function syncEmptyFieldsFromWorkshop(InstructorWorkshop $instructorWorkshop): void
    {
        if (empty($instructorWorkshop->workshop_id)) {
            return;
        }

        $workshop = \App\Models\Workshop::find($instructorWorkshop->workshop_id);
        if (!$workshop) {
            return;
        }

        if (is_null($instructorWorkshop->day_of_week)) {
            $instructorWorkshop->day_of_week = $workshop->day_of_week ?? ['Lunes'];
        }

        if (is_null($instructorWorkshop->start_time)) {
            $instructorWorkshop->start_time = $workshop->start_time;
        }

        if (is_null($instructorWorkshop->end_time) && $workshop->start_time && $workshop->duration) {
            try {
                $startTime = \Carbon\Carbon::parse($workshop->start_time);
                $instructorWorkshop->end_time = $startTime->addMinutes($workshop->duration)->format('H:i:s');
            } catch (\Exception $e) {
                $instructorWorkshop->end_time = $workshop->start_time;
            }
        }

        if (is_null($instructorWorkshop->max_capacity)) {
            $instructorWorkshop->max_capacity = $workshop->capacity;
        }

        if (is_null($instructorWorkshop->place)) {
            $instructorWorkshop->place = $workshop->place;
        }
    }

    /**
     * Sincronizar todos los campos desde el Workshop (cuando cambia workshop_id)
     */
    private function syncAllFieldsFromWorkshop(InstructorWorkshop $instructorWorkshop, $workshop): void
    {
        if (!$workshop) {
            return;
        }

        // Forzar actualización de todos los campos
        $instructorWorkshop->day_of_week = $workshop->day_of_week ?? ['Lunes'];
        $instructorWorkshop->start_time = $workshop->start_time;
        $instructorWorkshop->max_capacity = $workshop->capacity;
        $instructorWorkshop->place = $workshop->place;

        if ($workshop->start_time && $workshop->duration) {
            try {
                $startTime = \Carbon\Carbon::parse($workshop->start_time);
                $instructorWorkshop->end_time = $startTime->addMinutes($workshop->duration)->format('H:i:s');
            } catch (\Exception $e) {
                $instructorWorkshop->end_time = $workshop->start_time;
            }
        }
    }
}
