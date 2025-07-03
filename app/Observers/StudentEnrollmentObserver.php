<?php

namespace App\Observers;

use App\Models\StudentEnrollment;
use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Este observer se utiliza para crear InstructorPayments cuando se crean,actualizan o eliminan StudentEnrollments.

class StudentEnrollmentObserver
{    
    public function created(StudentEnrollment $studentEnrollment): void
    {
        $this->calculateAndSaveInstructorPayment($studentEnrollment);
    }
    public function updated(StudentEnrollment $studentEnrollment): void
    {
        // Recalculate if any relevant field changes (e.g., price_per_quantity, or workshop/period linkage)
        if ($studentEnrollment->isDirty(['price_per_quantity', 'instructor_workshop_id', 'monthly_period_id'])) {
            $this->calculateAndSaveInstructorPayment($studentEnrollment);
        }
    }
    public function deleted(StudentEnrollment $studentEnrollment): void
    {
        $this->calculateAndSaveInstructorPayment($studentEnrollment);
    }
    protected function calculateAndSaveInstructorPayment(StudentEnrollment $studentEnrollment): void
    {
        $instructorWorkshopId = $studentEnrollment->instructor_workshop_id;
        $monthlyPeriodId = $studentEnrollment->monthly_period_id;

        if (!$instructorWorkshopId || !$monthlyPeriodId) {
            // Should not happen if foreign keys are set up correctly and required.
            return;
        }

        $instructorWorkshop = InstructorWorkshop::find($instructorWorkshopId);
        if (!$instructorWorkshop) {
            return;
        }

        // Initialize calculation variables
        $calculatedAmount = 0;
        $paymentType = $instructorWorkshop->is_volunteer ? 'volunteer' : 'hourly';
        $totalStudents = null;
        $monthlyRevenue = null;
        $totalHours = null;
        $hourlyRate = null;
        $volunteerPercentage = null;

        if ($instructorWorkshop->is_volunteer) {
            // Logic for volunteer instructor: sum price_per_quantity from student_enrollments
            $volunteerPercentage = $instructorWorkshop->volunteer_percentage ?? 0.50; // Use default 50% if not set

            // Sum price_per_quantity for all student enrollments in this instructor_workshop and monthly_period
            $monthlyRevenue = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->sum('price_per_quantity');

            // Count unique students for the total_students field
            $totalStudents = DB::table('student_enrollments')
                ->where('instructor_workshop_id', $instructorWorkshopId)
                ->where('monthly_period_id', $monthlyPeriodId)
                ->count('student_id');

            $calculatedAmount = $monthlyRevenue * $volunteerPercentage;

        } else {
            // Logic for hourly instructor: sum hours from completed workshop_classes
            $hourlyRate = $instructorWorkshop->workshop->hourly_rate; // Get hourly rate from workshop

            $workshopClasses = WorkshopClass::where('instructor_workshop_id', $instructorWorkshopId)
                                            ->where('monthly_period_id', $monthlyPeriodId)
                                            ->where('status', 'completed') // Only count completed classes for hourly payment
                                            ->get();

            $totalHours = 0;
            foreach ($workshopClasses as $class) {
                $start = Carbon::parse($class->start_time);
                $end = Carbon::parse($class->end_time);
                $totalHours += $end->diffInMinutes($start) / 60; // Convert minutes to hours
            }

            $calculatedAmount = $totalHours * $hourlyRate;
            $volunteerPercentage = 1.00;
        }

        // Create or update the InstructorPayment record
        InstructorPayment::updateOrCreate(
            [
                'instructor_workshop_id' => $instructorWorkshopId,
                'monthly_period_id' => $monthlyPeriodId,
            ],
            [
                'instructor_id' => $instructorWorkshop->instructor_id,
                'payment_type' => $paymentType,
                'total_students' => $totalStudents,
                'monthly_revenue' => $monthlyRevenue,
                'volunteer_percentage' => $volunteerPercentage,
                'total_hours' => $totalHours,
                'hourly_rate' => $hourlyRate,
                'calculated_amount' => round($calculatedAmount, 2),
                // Keep existing payment status and date if payment already exists
                'payment_status' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                                    ->where('monthly_period_id', $monthlyPeriodId)
                                                    ->value('payment_status') ?? 'pending',
                'payment_date' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                                    ->where('monthly_period_id', $monthlyPeriodId)
                                                    ->value('payment_date'),
                'notes' => InstructorPayment::where('instructor_workshop_id', $instructorWorkshopId)
                                                    ->where('monthly_period_id', $monthlyPeriodId)
                                                    ->value('notes'),
            ]
        );
    }
}
