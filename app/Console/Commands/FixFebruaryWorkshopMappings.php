<?php

namespace App\Console\Commands;

use App\Models\EnrollmentBatch;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
use App\Models\EnrollmentClass;
use App\Models\WorkshopClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFebruaryWorkshopMappings extends Command
{
    protected $signature = 'enrollments:fix-february-mappings';
    protected $description = 'Corrige los instructor_workshop_id de las inscripciones de febrero que apuntan a talleres de enero';

    public function handle()
    {
        $this->info('Iniciando corrección de mappings de talleres para febrero 2026...');

        $febrero = MonthlyPeriod::where('year', 2026)->where('month', 2)->first();
        $enero = MonthlyPeriod::where('year', 2026)->where('month', 1)->first();

        if (!$febrero || !$enero) {
            $this->error('No se encontraron los períodos de enero o febrero 2026');
            return 1;
        }

        $this->info("Enero ID: {$enero->id}");
        $this->info("Febrero ID: {$febrero->id}");

        // Obtener todas las inscripciones de febrero que apuntan a talleres de otros períodos
        $incorrectEnrollments = StudentEnrollment::where('student_enrollments.monthly_period_id', $febrero->id)
            ->join('instructor_workshops', 'student_enrollments.instructor_workshop_id', '=', 'instructor_workshops.id')
            ->join('workshops', 'instructor_workshops.workshop_id', '=', 'workshops.id')
            ->where('workshops.monthly_period_id', '!=', $febrero->id)
            ->select('student_enrollments.*')
            ->with(['instructorWorkshop.workshop', 'instructorWorkshop.instructor'])
            ->get();

        $this->info("Inscripciones a corregir: {$incorrectEnrollments->count()}");

        if ($incorrectEnrollments->isEmpty()) {
            $this->info('No hay inscripciones que corregir');
            return 0;
        }

        $updated = 0;
        $failed = 0;
        $mappingCache = [];

        DB::beginTransaction();
        try {
            foreach ($incorrectEnrollments as $enrollment) {
                $oldInstructorWorkshop = $enrollment->instructorWorkshop;

                if (!$oldInstructorWorkshop || !$oldInstructorWorkshop->workshop) {
                    $this->warn("Enrollment {$enrollment->id}: instructor_workshop no encontrado");
                    $failed++;
                    continue;
                }

                // Usar cache para evitar búsquedas repetidas
                $cacheKey = $oldInstructorWorkshop->id;
                if (isset($mappingCache[$cacheKey])) {
                    $newInstructorWorkshop = $mappingCache[$cacheKey];
                } else {
                    // Buscar el taller equivalente en febrero
                    $newInstructorWorkshop = $this->findEquivalentWorkshopInPeriod(
                        $oldInstructorWorkshop,
                        $febrero->id
                    );
                    $mappingCache[$cacheKey] = $newInstructorWorkshop;
                }

                if (!$newInstructorWorkshop) {
                    $this->warn("Enrollment {$enrollment->id}: No se encontró taller equivalente para '{$oldInstructorWorkshop->workshop->name}'");
                    $failed++;
                    continue;
                }

                // Actualizar el instructor_workshop_id
                $enrollment->instructor_workshop_id = $newInstructorWorkshop->id;
                $enrollment->save();

                // Actualizar también las enrollment_classes si existen
                $this->updateEnrollmentClasses($enrollment, $oldInstructorWorkshop, $newInstructorWorkshop, $febrero->id);

                $updated++;

                if ($updated % 50 == 0) {
                    $this->info("Procesadas: {$updated} inscripciones...");
                }
            }

            DB::commit();
            $this->info("\n✓ Corrección completada exitosamente!");
            $this->info("Total actualizadas: {$updated}");
            $this->info("Total fallidas: {$failed}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la corrección: {$e->getMessage()}");
            return 1;
        }
    }

    private function findEquivalentWorkshopInPeriod($oldInstructorWorkshop, $newPeriodId)
    {
        $oldWorkshop = $oldInstructorWorkshop->workshop;
        $oldInstructor = $oldInstructorWorkshop->instructor;

        if (!$oldWorkshop || !$oldInstructor) {
            return null;
        }

        // Buscar talleres con el mismo nombre en el nuevo período
        $candidates = InstructorWorkshop::with(['workshop', 'instructor'])
            ->whereHas('workshop', function ($query) use ($newPeriodId, $oldWorkshop) {
                $query->where('monthly_period_id', $newPeriodId)
                    ->where('name', $oldWorkshop->name)
                    ->where('modality', $oldWorkshop->modality);
            })
            ->whereHas('instructor', function ($query) use ($oldInstructor) {
                $query->where('first_names', $oldInstructor->first_names)
                    ->where('last_names', $oldInstructor->last_names);
            })
            ->where('is_active', true)
            ->get();

        // Filtrar por día y hora exactos
        $oldStartTime = \Carbon\Carbon::parse($oldInstructorWorkshop->start_time)->format('H:i:s');

        $match = $candidates->first(function($candidate) use ($oldInstructorWorkshop, $oldStartTime) {
            $candidateTime = \Carbon\Carbon::parse($candidate->start_time)->format('H:i:s');
            return $candidate->day_of_week === $oldInstructorWorkshop->day_of_week &&
                   $candidateTime === $oldStartTime;
        });

        return $match;
    }

    private function updateEnrollmentClasses($enrollment, $oldInstructorWorkshop, $newInstructorWorkshop, $newPeriodId)
    {
        $enrollmentClasses = EnrollmentClass::where('student_enrollment_id', $enrollment->id)->get();

        if ($enrollmentClasses->isEmpty()) {
            return;
        }

        // Obtener todas las clases nuevas y viejas de una vez
        $allNewClasses = WorkshopClass::where('workshop_id', $newInstructorWorkshop->workshop->id)
            ->where('monthly_period_id', $newPeriodId)
            ->orderBy('class_date', 'asc')
            ->get();

        $allOldClasses = WorkshopClass::where('workshop_id', $oldInstructorWorkshop->workshop->id)
            ->where('monthly_period_id', $oldInstructorWorkshop->workshop->monthly_period_id)
            ->orderBy('class_date', 'asc')
            ->get();

        if ($allNewClasses->isEmpty()) {
            return;
        }

        // Eliminar las enrollment_classes existentes y crear nuevas
        // Esto evita problemas de duplicados
        $classData = [];
        foreach ($enrollmentClasses as $enrollmentClass) {
            $classData[] = [
                'class_fee' => $enrollmentClass->class_fee,
                'attendance_status' => $enrollmentClass->attendance_status,
            ];
        }

        // Eliminar las viejas
        EnrollmentClass::where('student_enrollment_id', $enrollment->id)->delete();

        // Crear las nuevas usando las clases del nuevo período
        $numberOfClassesToCreate = min(count($classData), $allNewClasses->count());

        for ($i = 0; $i < $numberOfClassesToCreate; $i++) {
            try {
                EnrollmentClass::create([
                    'student_enrollment_id' => $enrollment->id,
                    'workshop_class_id' => $allNewClasses[$i]->id,
                    'class_fee' => $classData[$i]['class_fee'] ?? ($enrollment->total_amount / $enrollment->number_of_classes),
                    'attendance_status' => $classData[$i]['attendance_status'] ?? 'enrolled',
                ]);
            } catch (\Exception $e) {
                // Si falla por duplicado, continuar con la siguiente
                continue;
            }
        }
    }
}
