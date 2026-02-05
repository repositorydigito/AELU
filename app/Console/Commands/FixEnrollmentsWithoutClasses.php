<?php

namespace App\Console\Commands;

use App\Models\EnrollmentClass;
use App\Models\StudentEnrollment;
use App\Models\WorkshopClass;
use Illuminate\Console\Command;

class FixEnrollmentsWithoutClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:fix-missing-classes
                            {--dry-run : Run without making changes}
                            {--period= : Only process specific monthly_period_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix student enrollments that have number_of_classes > 0 but no enrollment_classes records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $periodId = $this->option('period');

        $this->info('🔍 Buscando inscripciones sin clases asignadas...');

        // Buscar enrollments con number_of_classes > 0
        $query = StudentEnrollment::where('number_of_classes', '>', 0)
            ->whereNotIn('payment_status', ['refunded'])
            ->with(['instructorWorkshop.workshop', 'enrollmentClasses', 'student']);

        if ($periodId) {
            $query->where('monthly_period_id', $periodId);
            $this->info("📅 Filtrando por período ID: {$periodId}");
        }

        $enrollments = $query->get();

        $this->info("📊 Total de inscripciones activas con clases: {$enrollments->count()}");

        $problemEnrollments = $enrollments->filter(function ($enrollment) {
            return $enrollment->enrollmentClasses->isEmpty();
        });

        if ($problemEnrollments->isEmpty()) {
            $this->info('✅ No se encontraron inscripciones con problemas.');
            return 0;
        }

        $this->warn("⚠️  Encontradas {$problemEnrollments->count()} inscripciones sin clases asignadas");

        if ($isDryRun) {
            $this->info("\n📋 MODO DRY-RUN - No se realizarán cambios\n");
        } else {
            $this->info("\n⚡ Iniciando corrección...\n");
        }

        $table = [];
        $fixed = 0;
        $errors = 0;

        foreach ($problemEnrollments as $enrollment) {
            $studentName = $enrollment->student
                ? "{$enrollment->student->last_names}, {$enrollment->student->first_names}"
                : "ID: {$enrollment->student_id}";

            $workshopName = $enrollment->instructorWorkshop?->workshop?->name ?? 'N/A';
            $numberOfClasses = $enrollment->number_of_classes;

            try {
                if (!$isDryRun) {
                    $created = $this->fixEnrollment($enrollment);

                    if ($created > 0) {
                        $table[] = [
                            $enrollment->id,
                            $studentName,
                            $workshopName,
                            $numberOfClasses,
                            $created,
                            '✅ Corregido'
                        ];
                        $fixed++;
                    } else {
                        $table[] = [
                            $enrollment->id,
                            $studentName,
                            $workshopName,
                            $numberOfClasses,
                            0,
                            '❌ Sin clases disponibles'
                        ];
                        $errors++;
                    }
                } else {
                    // Dry run - solo mostrar
                    $availableClasses = $this->getAvailableClasses($enrollment);
                    $table[] = [
                        $enrollment->id,
                        $studentName,
                        $workshopName,
                        $numberOfClasses,
                        $availableClasses->count(),
                        '📝 Pendiente'
                    ];
                }
            } catch (\Exception $e) {
                $table[] = [
                    $enrollment->id,
                    $studentName,
                    $workshopName,
                    $numberOfClasses,
                    0,
                    "❌ Error: {$e->getMessage()}"
                ];
                $errors++;
            }
        }

        $this->table(
            ['ID', 'Estudiante', 'Taller', 'N° Clases', 'Asignadas', 'Estado'],
            $table
        );

        if ($isDryRun) {
            $this->info("\n💡 Ejecuta sin --dry-run para aplicar los cambios");
        } else {
            $this->info("\n✨ Resumen:");
            $this->info("   ✅ Corregidas: {$fixed}");
            if ($errors > 0) {
                $this->warn("   ❌ Errores: {$errors}");
            }
        }

        return 0;
    }

    /**
     * Fix a single enrollment by creating missing enrollment_classes
     */
    protected function fixEnrollment(StudentEnrollment $enrollment): int
    {
        $workshopClasses = $this->getAvailableClasses($enrollment);

        if ($workshopClasses->isEmpty()) {
            $this->warn("   ⚠️  No hay clases disponibles para enrollment #{$enrollment->id}");
            return 0;
        }

        // Limitar al número de clases de la inscripción
        $classesToAssign = $workshopClasses->take($enrollment->number_of_classes);

        // Calcular precio por clase
        $pricePerClass = $enrollment->total_amount / $enrollment->number_of_classes;

        $created = 0;
        foreach ($classesToAssign as $workshopClass) {
            EnrollmentClass::create([
                'student_enrollment_id' => $enrollment->id,
                'workshop_class_id' => $workshopClass->id,
                'class_fee' => $pricePerClass,
                'attendance_status' => 'enrolled',
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Get available workshop classes for an enrollment
     */
    protected function getAvailableClasses(StudentEnrollment $enrollment)
    {
        $instructorWorkshop = $enrollment->instructorWorkshop;
        if (!$instructorWorkshop) {
            return collect();
        }

        $workshop = $instructorWorkshop->workshop;
        if (!$workshop) {
            return collect();
        }

        // Buscar clases del workshop para el período de la inscripción
        // Primero intentar clases futuras desde la fecha de inscripción
        $workshopClasses = WorkshopClass::where('workshop_id', $workshop->id)
            ->where('monthly_period_id', $enrollment->monthly_period_id)
            ->where('status', '!=', 'cancelled')
            ->where('class_date', '>=', $enrollment->enrollment_date)
            ->orderBy('class_date', 'asc')
            ->get();

        // Si no hay suficientes clases futuras, incluir clases pasadas también
        if ($workshopClasses->count() < $enrollment->number_of_classes) {
            $allClasses = WorkshopClass::where('workshop_id', $workshop->id)
                ->where('monthly_period_id', $enrollment->monthly_period_id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('class_date', 'asc')
                ->get();

            return $allClasses;
        }

        return $workshopClasses;
    }
}
