<?php

namespace App\Services;

use App\Models\EnrollmentBatch;
use App\Models\EnrollmentClass;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
use App\Models\WorkshopClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EnrollmentReplicationService
{
    protected array $stats = [
        'batches_processed' => 0,
        'batches_created' => 0,
        'batches_skipped' => 0,
        'enrollments_created' => 0,
        'classes_created' => 0,
        'errors' => [],
        'warnings' => [],
    ];

    /**
     * Cache de InstructorWorkshops del siguiente período para evitar N+1
     */
    protected $nextPeriodInstructorWorkshops = null;

    /**
     * IDs de batches creados durante esta ejecución por estudiante
     * Para permitir múltiples batches del mismo estudiante
     */
    protected $createdBatchIdsByStudent = [];

    /**
     * Replica inscripciones del período actual al siguiente
     *
     * @return array{batches:int, enrollments:int, classes:int, skipped:int, warnings:array, errors:array}
     */
    public function replicateEnrollmentsToNextMonth(MonthlyPeriod $currentPeriod, MonthlyPeriod $nextPeriod): array
    {
        $this->resetStats();

        Log::info("Starting enrollment replication from period {$currentPeriod->year}/{$currentPeriod->month} to {$nextPeriod->year}/{$nextPeriod->month}");

        // Pre-cargar InstructorWorkshops del siguiente período para evitar N+1
        $this->nextPeriodInstructorWorkshops = InstructorWorkshop::with('workshop')
            ->whereHas('workshop', function ($query) use ($nextPeriod) {
                $query->where('monthly_period_id', $nextPeriod->id);
            })
            ->get()
            ->keyBy(function ($iw) {
                // Crear clave única: instructor_id + nombre + día + horario + duración
                $dayOfWeek = is_array($iw->workshop->day_of_week)
                    ? json_encode($iw->workshop->day_of_week)
                    : $iw->workshop->day_of_week;

                return $iw->instructor_id.'_'.
                       $iw->workshop->name.'_'.
                       $dayOfWeek.'_'.
                       $iw->workshop->start_time.'_'.
                       $iw->workshop->duration;
            });

        Log::info("Pre-loaded {$this->nextPeriodInstructorWorkshops->count()} instructor workshops for next period");

        // Buscar batches completados del período actual
        $completedBatches = EnrollmentBatch::where('payment_status', 'completed')
            ->whereHas('enrollments', function ($query) use ($currentPeriod) {
                $query->where('monthly_period_id', $currentPeriod->id)
                    ->whereNull('cancelled_at');
            })
            ->with([
                'enrollments.instructorWorkshop.workshop',
                'enrollments.enrollmentClasses.workshopClass',
                'student',
            ])
            ->get();

        if ($completedBatches->isEmpty()) {
            Log::info('No completed enrollment batches found for replication');

            return $this->getStats();
        }

        Log::info("Found {$completedBatches->count()} completed batches to process");

        foreach ($completedBatches as $batch) {
            $this->stats['batches_processed']++;

            try {
                $this->replicateBatch($batch, $currentPeriod, $nextPeriod);
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Batch ID {$batch->id} (Student: {$batch->student->full_name}): {$e->getMessage()}";
                Log::error("Error replicating batch {$batch->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Enrollment replication completed', $this->getStats());

        return $this->getStats();
    }

    /**
     * Replica un batch individual
     */
    protected function replicateBatch(EnrollmentBatch $batch, MonthlyPeriod $currentPeriod, MonthlyPeriod $nextPeriod): void
    {
        $student = $batch->student;

        // Validación 1: Verificar si ya existe un batch para este estudiante en el siguiente período
        // IMPORTANTE: Excluir batches que fueron creados durante ESTA ejecución
        // para permitir que un estudiante tenga múltiples batches replicados
        $excludeBatchIds = $this->createdBatchIdsByStudent[$student->id] ?? [];

        $query = EnrollmentBatch::where('student_id', $student->id)
            ->whereHas('enrollments', function ($query) use ($nextPeriod) {
                $query->where('monthly_period_id', $nextPeriod->id);
            })
            ->whereNotIn('payment_status', ['refunded']);

        if (! empty($excludeBatchIds)) {
            $query->whereNotIn('id', $excludeBatchIds);
        }

        $existingBatch = $query->first();

        if ($existingBatch) {
            $this->stats['batches_skipped']++;
            $this->stats['warnings'][] = "Student {$student->full_name} already has a batch for next period (Batch ID: {$existingBatch->id}) created manually";
            Log::info("Skipping batch {$batch->id}: Student already has manual enrollment for next period");

            return;
        }

        // Validación 2: Verificar que el estudiante esté al día con mantenimiento
        if (! $this->validateStudentEligibility($student, $nextPeriod)) {
            $this->stats['batches_skipped']++;
            $this->stats['warnings'][] = "Student {$student->full_name} is not eligible (maintenance not current)";
            Log::info("Skipping batch {$batch->id}: Student not current with maintenance");

            return;
        }

        // Crear nuevo batch
        $newBatch = $this->createNewBatch($batch, $nextPeriod);

        $totalAmount = 0;
        $successfulEnrollments = 0;

        // Replicar cada inscripción del batch
        foreach ($batch->enrollments as $enrollment) {
            if ($enrollment->cancelled_at || $enrollment->monthly_period_id != $currentPeriod->id) {
                continue;
            }

            try {
                $enrollmentAmount = $this->replicateEnrollment($enrollment, $newBatch, $nextPeriod);

                if ($enrollmentAmount > 0) {
                    $totalAmount += $enrollmentAmount;
                    $successfulEnrollments++;
                }
            } catch (\Exception $e) {
                $this->stats['warnings'][] = "Failed to replicate enrollment ID {$enrollment->id}: {$e->getMessage()}";
                Log::warning("Error replicating enrollment {$enrollment->id}", ['error' => $e->getMessage()]);
            }
        }

        // Verificar si se crearon inscripciones
        if ($successfulEnrollments > 0) {
            $newBatch->update(['total_amount' => $totalAmount]);
            $this->stats['batches_created']++;

            // Registrar este batch como creado durante esta ejecución
            if (! isset($this->createdBatchIdsByStudent[$student->id])) {
                $this->createdBatchIdsByStudent[$student->id] = [];
            }
            $this->createdBatchIdsByStudent[$student->id][] = $newBatch->id;

            Log::info("Created batch {$newBatch->id} for student {$student->full_name} with {$successfulEnrollments} enrollments");
        } else {
            // Si no se creó ninguna inscripción, eliminar el batch vacío
            $newBatch->delete();
            $this->stats['batches_skipped']++;
            $this->stats['warnings'][] = "No enrollments created for student {$student->full_name}, batch deleted";
            Log::info("Deleted empty batch for student {$student->full_name}");
        }
    }

    /**
     * Crea un nuevo batch basado en el original
     */
    protected function createNewBatch(EnrollmentBatch $batch, MonthlyPeriod $nextPeriod): EnrollmentBatch
    {
        return EnrollmentBatch::create([
            'student_id' => $batch->student_id,
            'total_amount' => 0, // Se actualizará después
            'payment_status' => 'pending',
            'payment_method' => $batch->payment_method,
            'enrollment_date' => now()->timezone(config('app.timezone', 'America/Lima'))->format('Y-m-d'),
            'payment_due_date' => $this->adjustDateToNextMonth($batch->payment_due_date, $nextPeriod),
            'notes' => "Replicación automática desde período {$batch->enrollments->first()->monthlyPeriod->year}/{$batch->enrollments->first()->monthlyPeriod->month} - Lote original: {$batch->id}",
        ]);
    }

    /**
     * Replica una inscripción individual
     */
    protected function replicateEnrollment(StudentEnrollment $enrollment, EnrollmentBatch $newBatch, MonthlyPeriod $nextPeriod): float
    {
        // Encontrar el instructor_workshop equivalente en el siguiente período
        $newInstructorWorkshop = $this->findEquivalentInstructorWorkshop(
            $enrollment->instructorWorkshop,
            $nextPeriod
        );

        if (! $newInstructorWorkshop) {
            throw new \Exception("Workshop '{$enrollment->instructorWorkshop->workshop->name}' not found in next period");
        }

        // Validar capacidad disponible
        if ($newInstructorWorkshop->isFullForPeriod($nextPeriod->id)) {
            throw new \Exception("Workshop '{$newInstructorWorkshop->workshop->name}' is full for next period");
        }

        // Recalcular precio (puede haber cambiado)
        $student = $enrollment->student;
        $workshop = $newInstructorWorkshop->workshop;
        $numberOfClasses = $enrollment->number_of_classes;

        $basePrice = $workshop->standard_monthly_fee;
        $finalPrice = $basePrice * $student->inscription_multiplier;
        $pricePerClass = $finalPrice / $numberOfClasses;

        // Crear nueva inscripción
        $newEnrollment = StudentEnrollment::create([
            'student_id' => $enrollment->student_id,
            'instructor_workshop_id' => $newInstructorWorkshop->id,
            'enrollment_batch_id' => $newBatch->id,
            'monthly_period_id' => $nextPeriod->id,
            'enrollment_type' => $enrollment->enrollment_type,
            'number_of_classes' => $numberOfClasses,
            'price_per_quantity' => $pricePerClass,
            'total_amount' => $finalPrice,
            'payment_status' => 'pending',
            'payment_method' => $enrollment->payment_method,
            'enrollment_date' => now()->timezone(config('app.timezone', 'America/Lima'))->format('Y-m-d'),
            'payment_due_date' => $this->adjustDateToNextMonth($enrollment->payment_due_date, $nextPeriod),
            'pricing_notes' => 'Replicación automática - precio recalculado',
            'previous_enrollment_id' => $enrollment->id, // Mantener cadena de renovación
            'renewal_status' => 'not_applicable',
            'is_renewal' => false,
        ]);

        // Mapear clases específicas
        $this->mapEnrollmentClasses($enrollment, $newEnrollment, $nextPeriod);

        $this->stats['enrollments_created']++;

        return $finalPrice;
    }

    /**
     * Encuentra el InstructorWorkshop equivalente en el siguiente período
     */
    protected function findEquivalentInstructorWorkshop(InstructorWorkshop $originalIW, MonthlyPeriod $nextPeriod): ?InstructorWorkshop
    {
        $originalWorkshop = $originalIW->workshop;

        // Crear clave de búsqueda: instructor_id + nombre + día + horario + duración
        $dayOfWeek = is_array($originalWorkshop->day_of_week)
            ? json_encode($originalWorkshop->day_of_week)
            : $originalWorkshop->day_of_week;

        $searchKey = $originalIW->instructor_id.'_'.
                     $originalWorkshop->name.'_'.
                     $dayOfWeek.'_'.
                     $originalWorkshop->start_time.'_'.
                     $originalWorkshop->duration;

        // Buscar en el cache pre-cargado
        return $this->nextPeriodInstructorWorkshops->get($searchKey);
    }

    /**
     * Mapea las clases de la inscripción original a las nuevas fechas
     */
    protected function mapEnrollmentClasses(
        StudentEnrollment $originalEnrollment,
        StudentEnrollment $newEnrollment,
        MonthlyPeriod $nextPeriod
    ): void {
        // Para inscripciones de mes completo, inscribir en TODAS las clases disponibles del nuevo período
        // sin intentar mapear clase por clase, ya que eventos específicos del mes (ej: aniversario)
        // no deben afectar la generación de clases del siguiente mes
        if ($originalEnrollment->enrollment_type === 'full_month') {
            $this->createDefaultEnrollmentClasses($newEnrollment, $nextPeriod);

            return;
        }

        // Para inscripciones de clases específicas, intentar mapear cada clase
        $originalClasses = $originalEnrollment->enrollmentClasses()
            ->with('workshopClass')
            ->orderBy('id')
            ->get();

        if ($originalClasses->isEmpty()) {
            // Si no hay clases específicas, crear para todas las clases del workshop
            $this->createDefaultEnrollmentClasses($newEnrollment, $nextPeriod);

            return;
        }

        // Llevar registro de las WorkshopClasses ya usadas para evitar duplicados
        $usedWorkshopClassIds = [];

        // Mapear cada clase original a su equivalente en el siguiente período
        foreach ($originalClasses as $originalEnrollmentClass) {
            $originalWorkshopClass = $originalEnrollmentClass->workshopClass;

            // Encontrar la clase equivalente en el siguiente período
            $newWorkshopClass = $this->findEquivalentWorkshopClass(
                $originalWorkshopClass,
                $newEnrollment->instructorWorkshop->workshop,
                $nextPeriod,
                $usedWorkshopClassIds
            );

            if ($newWorkshopClass) {
                EnrollmentClass::create([
                    'student_enrollment_id' => $newEnrollment->id,
                    'workshop_class_id' => $newWorkshopClass->id,
                    'class_fee' => $originalEnrollmentClass->class_fee,
                    'attendance_status' => 'enrolled',
                ]);

                // Marcar esta clase como usada
                $usedWorkshopClassIds[] = $newWorkshopClass->id;

                $this->stats['classes_created']++;
            } else {
                $this->stats['warnings'][] = "Could not map class for enrollment {$newEnrollment->id} on date {$originalWorkshopClass->class_date}";
            }
        }
    }

    /**
     * Encuentra la WorkshopClass equivalente en el siguiente período
     */
    protected function findEquivalentWorkshopClass(
        WorkshopClass $originalClass,
        Workshop $newWorkshop,
        MonthlyPeriod $nextPeriod,
        array $excludeIds = []
    ): ?WorkshopClass {
        // Buscar por: workshop equivalente + mismo día de la semana + mismos horarios
        // Carbon dayOfWeek: 0=Domingo, 1=Lunes, ..., 6=Sábado
        // MySQL DAYOFWEEK(): 1=Domingo, 2=Lunes, ..., 7=Sábado
        // Por lo tanto: DAYOFWEEK() - 1 = Carbon dayOfWeek
        $originalWeekday = Carbon::parse($originalClass->class_date)->dayOfWeek;

        $query = WorkshopClass::where('workshop_id', $newWorkshop->id)
            ->where('monthly_period_id', $nextPeriod->id)
            ->whereRaw('DAYOFWEEK(class_date) - 1 = ?', [$originalWeekday])
            ->where('start_time', $originalClass->start_time)
            ->where('end_time', $originalClass->end_time);

        // Excluir las clases ya usadas para esta inscripción
        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('class_date')->first();
    }

    /**
     * Crea EnrollmentClasses por defecto para inscripción de mes completo
     */
    protected function createDefaultEnrollmentClasses(StudentEnrollment $enrollment, MonthlyPeriod $period): void
    {
        $workshopClasses = WorkshopClass::where('workshop_id', $enrollment->instructorWorkshop->workshop->id)
            ->where('monthly_period_id', $period->id)
            ->orderBy('class_date', 'asc')
            ->limit($enrollment->number_of_classes)
            ->get();

        $pricePerClass = $enrollment->total_amount / $enrollment->number_of_classes;

        foreach ($workshopClasses as $workshopClass) {
            EnrollmentClass::create([
                'student_enrollment_id' => $enrollment->id,
                'workshop_class_id' => $workshopClass->id,
                'class_fee' => $pricePerClass,
                'attendance_status' => 'enrolled',
            ]);

            $this->stats['classes_created']++;
        }
    }

    /**
     * Valida si un estudiante es elegible para inscripción
     */
    protected function validateStudentEligibility(Student $student, MonthlyPeriod $nextPeriod): bool
    {
        // Verificar que el estudiante tenga mantenimiento vigente
        // Debe estar dentro de 2 meses de gracia
        if (! $student->maintenance_period_id) {
            return false;
        }

        $maintenancePeriod = MonthlyPeriod::find($student->maintenance_period_id);
        if (! $maintenancePeriod) {
            return false;
        }

        // Calcular diferencia en meses
        $periodDate = Carbon::create($nextPeriod->year, $nextPeriod->month, 1);
        $maintenanceDate = Carbon::create($maintenancePeriod->year, $maintenancePeriod->month, 1);

        $monthsDiff = $periodDate->diffInMonths($maintenanceDate);

        // 2 meses de gracia
        return $monthsDiff <= 2;
    }

    /**
     * Ajusta una fecha al siguiente mes
     */
    protected function adjustDateToNextMonth($date, MonthlyPeriod $nextPeriod): string
    {
        if (! $date) {
            return now()->timezone(config('app.timezone', 'America/Lima'))->format('Y-m-d');
        }

        $originalDate = Carbon::parse($date);
        $day = $originalDate->day;

        // Ajustar si el día excede los días del siguiente mes
        $maxDay = Carbon::create($nextPeriod->year, $nextPeriod->month, 1)->daysInMonth;
        if ($day > $maxDay) {
            $day = $maxDay;
        }

        return Carbon::create($nextPeriod->year, $nextPeriod->month, $day)->format('Y-m-d');
    }

    /**
     * Resetea las estadísticas
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'batches_processed' => 0,
            'batches_created' => 0,
            'batches_skipped' => 0,
            'enrollments_created' => 0,
            'classes_created' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        $this->createdBatchIdsByStudent = [];
    }

    /**
     * Obtiene las estadísticas
     */
    protected function getStats(): array
    {
        return [
            'batches' => $this->stats['batches_created'],
            'enrollments' => $this->stats['enrollments_created'],
            'classes' => $this->stats['classes_created'],
            'skipped' => $this->stats['batches_skipped'],
            'warnings' => $this->stats['warnings'],
            'errors' => $this->stats['errors'],
        ];
    }
}
