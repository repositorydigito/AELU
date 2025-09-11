<?php

namespace Database\Seeders;

use App\Models\EnrollmentBatch;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Seeder;

class SimpleEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear período del mes anterior
        $previousMonth = now()->subMonth();
        $previousPeriod = MonthlyPeriod::firstOrCreate([
            'year' => $previousMonth->year,
            'month' => $previousMonth->month,
        ], [
            'start_date' => $previousMonth->startOfMonth()->toDateString(),
            'end_date' => $previousMonth->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        // 2. Obtener algunos estudiantes existentes
        $students = Student::take(3)->get();

        if ($students->count() < 1) {
            $this->command->error('❌ No hay estudiantes en la base de datos. Necesitas crear al menos uno primero.');

            return;
        }

        // 3. Obtener algunos instructor_workshops existentes
        $instructorWorkshops = InstructorWorkshop::take(4)->get();

        if ($instructorWorkshops->count() < 1) {
            $this->command->error('❌ No hay instructor_workshops en la base de datos. Necesitas crear al menos uno primero.');

            return;
        }

        // 4. Crear inscripciones del mes anterior para cada estudiante
        foreach ($students as $index => $student) {
            // Crear batch para el estudiante
            $batch = EnrollmentBatch::create([
                'student_id' => $student->id,
                'batch_code' => 'TEST-BATCH-'.$student->id.'-'.$previousMonth->format('Y-m'),
                'total_amount' => 0, // Lo actualizaremos después
                'payment_status' => 'completed',
                'payment_method' => 'cash',
                'enrollment_date' => $previousMonth->toDateString(),
            ]);

            $totalAmount = 0;
            $enrollmentCount = rand(1, min(3, $instructorWorkshops->count())); // 1-3 inscripciones por estudiante

            // Crear inscripciones aleatorias
            $selectedWorkshops = $instructorWorkshops->random($enrollmentCount);

            foreach ($selectedWorkshops as $instructorWorkshop) {
                $workshop = $instructorWorkshop->workshop;
                $baseAmount = $workshop->standard_monthly_fee;
                $finalAmount = $baseAmount * $student->pricing_multiplier;

                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'instructor_workshop_id' => $instructorWorkshop->id,
                    'monthly_period_id' => $previousPeriod->id,
                    'enrollment_batch_id' => $batch->id,
                    'enrollment_type' => 'full_month',
                    'number_of_classes' => $workshop->number_of_classes,
                    'price_per_quantity' => $finalAmount / $workshop->number_of_classes,
                    'total_amount' => $finalAmount,
                    'payment_status' => 'completed',
                    'payment_method' => 'cash',
                    'enrollment_date' => $previousMonth->toDateString(),
                ]);

                $totalAmount += $finalAmount;
            }

            // Actualizar el total del batch
            $batch->update(['total_amount' => $totalAmount]);

            $this->command->info("✅ Creadas {$enrollmentCount} inscripciones para: {$student->first_names} {$student->last_names}");
        }

        $totalEnrollments = StudentEnrollment::where('monthly_period_id', $previousPeriod->id)->count();

        $this->command->info('🎉 Inscripciones de prueba creadas exitosamente!');
        $this->command->info("📅 Período: {$previousPeriod->year}-{$previousPeriod->month}");
        $this->command->info("📊 Total de inscripciones creadas: {$totalEnrollments}");
        $this->command->warn('💡 Ahora ya puedes probar que se muestren las inscripciones previas en los cards');
    }
}
