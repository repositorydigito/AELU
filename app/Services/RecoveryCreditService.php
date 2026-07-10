<?php

namespace App\Services;

use App\Models\ClassAttendance;
use App\Models\EnrollmentClass;
use App\Models\MonthlyPeriod;
use App\Models\Student;
use App\Models\StudentCredit;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RecoveryCreditService
{
    /**
     * Inscripciones pagadas del estudiante en el período con clases no asistidas
     * (feriado o inasistencia propia, RN-D1) que aún no fueron convertidas en crédito.
     * Candidatos para revisión/aprobación de staff (flujo híbrido).
     */
    public function getCandidates(Student $student, MonthlyPeriod $period): Collection
    {
        $enrollments = StudentEnrollment::where('student_id', $student->id)
            ->where('monthly_period_id', $period->id)
            ->where('payment_status', 'completed')
            ->with('enrollmentClasses.workshopClass', 'instructorWorkshop.workshop')
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $enrollmentClassIds = $enrollments->flatMap->enrollmentClasses->pluck('id');

        $alreadyCreditedIds = DB::table('student_credit_classes')
            ->whereIn('enrollment_class_id', $enrollmentClassIds)
            ->pluck('enrollment_class_id');

        $attendedKeys = ClassAttendance::whereIn('student_enrollment_id', $enrollments->pluck('id'))
            ->where('is_present', true)
            ->get(['student_enrollment_id', 'workshop_class_id'])
            ->map(fn ($a) => $a->student_enrollment_id.'-'.$a->workshop_class_id)
            ->flip();

        return $enrollments
            ->map(function (StudentEnrollment $enrollment) use ($alreadyCreditedIds, $attendedKeys) {
                $missedClasses = $enrollment->enrollmentClasses
                    ->reject(fn (EnrollmentClass $class) => $alreadyCreditedIds->contains($class->id))
                    ->reject(fn (EnrollmentClass $class) => $attendedKeys->has($enrollment->id.'-'.$class->workshop_class_id))
                    ->values();

                return [
                    'enrollment' => $enrollment,
                    'missed_classes' => $missedClasses,
                    'amount' => $missedClasses->sum('class_fee'),
                ];
            })
            ->filter(fn (array $row) => $row['missed_classes']->isNotEmpty())
            ->values();
    }

    /**
     * Crea el crédito a partir de las clases no asistidas que staff confirmó
     * como recuperables (RN-D2/RN-D3). Vigente solo hasta el período siguiente (RN-D17).
     */
    public function createCredit(StudentEnrollment $enrollment, array $enrollmentClassIds, string $origin, User $approver): StudentCredit
    {
        $classes = $enrollment->enrollmentClasses()->whereIn('id', $enrollmentClassIds)->get();

        if ($classes->isEmpty()) {
            throw new InvalidArgumentException('No se seleccionaron clases válidas para recuperar.');
        }

        $alreadyCredited = DB::table('student_credit_classes')
            ->whereIn('enrollment_class_id', $classes->pluck('id'))
            ->exists();

        if ($alreadyCredited) {
            throw new InvalidArgumentException('Una o más clases seleccionadas ya tienen un crédito generado.');
        }

        $nextPeriod = $enrollment->monthlyPeriod->nextPeriod();

        if (! $nextPeriod) {
            throw new RuntimeException('No existe el período siguiente para calcular la vigencia del crédito.');
        }

        return DB::transaction(function () use ($enrollment, $classes, $origin, $approver, $nextPeriod) {
            $credit = StudentCredit::create([
                'student_id' => $enrollment->student_id,
                'origin_student_enrollment_id' => $enrollment->id,
                'origin_monthly_period_id' => $enrollment->monthly_period_id,
                'valid_through_period_id' => $nextPeriod->id,
                'classes_count' => $classes->count(),
                'amount' => $classes->sum('class_fee'),
                'origin' => $origin,
                'status' => 'available',
                'created_by' => $approver->id,
            ]);

            $credit->enrollmentClasses()->attach($classes->pluck('id'));

            return $credit;
        });
    }
}
