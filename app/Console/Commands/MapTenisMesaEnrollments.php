<?php

namespace App\Console\Commands;

use App\Models\EnrollmentClass;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
use App\Models\WorkshopClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapTenisMesaEnrollments extends Command
{
    protected $signature = 'enrollments:map-tenis-mesa';
    protected $description = 'Mapea manualmente las inscripciones de TENIS DE MESA al nuevo instructor en febrero';

    public function handle()
    {
        $febrero = MonthlyPeriod::where('year', 2026)->where('month', 2)->first();

        if (!$febrero) {
            $this->error('No se encontró el período de febrero 2026');
            return 1;
        }

        // Buscar el taller de TENIS DE MESA en febrero
        $nuevoTaller = InstructorWorkshop::with(['workshop', 'instructor'])
            ->whereHas('workshop', function($q) use ($febrero) {
                $q->where('monthly_period_id', $febrero->id)->where('name', 'TENIS DE MESA');
            })
            ->first();

        if (!$nuevoTaller) {
            $this->error('No se encontró el taller de TENIS DE MESA en febrero');
            return 1;
        }

        $this->info("Taller destino encontrado:");
        $this->info("  Nombre: {$nuevoTaller->workshop->name}");
        $this->info("  Instructor: {$nuevoTaller->instructor->first_names} {$nuevoTaller->instructor->last_names}");
        $this->info("  Día: " . json_encode($nuevoTaller->day_of_week));
        $this->info("  Hora: {$nuevoTaller->start_time}");

        // Buscar inscripciones problemáticas
        $problematicEnrollments = StudentEnrollment::where('student_enrollments.monthly_period_id', $febrero->id)
            ->join('instructor_workshops', 'student_enrollments.instructor_workshop_id', '=', 'instructor_workshops.id')
            ->join('workshops', 'instructor_workshops.workshop_id', '=', 'workshops.id')
            ->where('workshops.name', 'TENIS DE MESA')
            ->where('workshops.monthly_period_id', '!=', $febrero->id)
            ->select('student_enrollments.*')
            ->with(['student', 'instructorWorkshop.instructor'])
            ->get();

        $this->info("\nInscripciones a mapear: {$problematicEnrollments->count()}");

        if ($problematicEnrollments->isEmpty()) {
            $this->info('No hay inscripciones que mapear');
            return 0;
        }

        // Confirmar con el usuario
        if (!$this->confirm('¿Deseas mapear estas inscripciones al nuevo instructor?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        DB::beginTransaction();
        try {
            $updated = 0;

            foreach ($problematicEnrollments as $enrollment) {
                // Actualizar el instructor_workshop_id
                $enrollment->instructor_workshop_id = $nuevoTaller->id;
                $enrollment->save();

                // Actualizar enrollment_classes
                $this->updateEnrollmentClasses($enrollment, $nuevoTaller, $febrero->id);

                $updated++;
                $this->info("  ✓ Enrollment {$enrollment->id}: {$enrollment->student->first_names} {$enrollment->student->last_names}");
            }

            DB::commit();
            $this->info("\n✓ Mapeo completado exitosamente!");
            $this->info("Total actualizadas: {$updated}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante el mapeo: {$e->getMessage()}");
            return 1;
        }
    }

    private function updateEnrollmentClasses($enrollment, $newInstructorWorkshop, $newPeriodId)
    {
        $enrollmentClasses = EnrollmentClass::where('student_enrollment_id', $enrollment->id)->get();

        if ($enrollmentClasses->isEmpty()) {
            return;
        }

        // Obtener todas las clases nuevas
        $allNewClasses = WorkshopClass::where('workshop_id', $newInstructorWorkshop->workshop->id)
            ->where('monthly_period_id', $newPeriodId)
            ->orderBy('class_date', 'asc')
            ->get();

        if ($allNewClasses->isEmpty()) {
            return;
        }

        // Guardar datos antes de eliminar
        $classData = [];
        foreach ($enrollmentClasses as $enrollmentClass) {
            $classData[] = [
                'class_fee' => $enrollmentClass->class_fee,
                'attendance_status' => $enrollmentClass->attendance_status,
            ];
        }

        // Eliminar las viejas
        EnrollmentClass::where('student_enrollment_id', $enrollment->id)->delete();

        // Crear las nuevas
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
                continue;
            }
        }
    }
}
