<?php

namespace Database\Seeders;

use App\Models\EnrollmentBatch;
use App\Models\EnrollmentClass;
use App\Models\InstructorWorkshop;
use App\Models\MonthlyPeriod;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\WorkshopClass;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReplicationTestSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Período actual
        $currentPeriod = MonthlyPeriod::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if (! $currentPeriod) {
            $this->command->error("❌ No existe MonthlyPeriod para {$now->year}/{$now->month}. Créalo primero.");
            return;
        }

        // 2. Período siguiente (necesario para que la replicación funcione)
        $nextMonth = $now->copy()->addMonthNoOverflow();
        $nextPeriod = MonthlyPeriod::where('year', $nextMonth->year)
            ->where('month', $nextMonth->month)
            ->first();

        if (! $nextPeriod) {
            $this->command->warn("⚠️  No existe MonthlyPeriod para {$nextMonth->year}/{$nextMonth->month}.");
            $this->command->warn('   El job de replicación necesita ese período. Créalo o corre primero WorkshopReplicationService.');
        }

        // 3. Talleres del período actual que tengan workshop_classes scheduled
        $instructorWorkshops = InstructorWorkshop::with(['workshop', 'instructor'])
            ->whereHas('workshop', fn($q) => $q->where('monthly_period_id', $currentPeriod->id))
            ->whereHas('workshop.workshopClasses', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->get();

        if ($instructorWorkshops->isEmpty()) {
            $this->command->error('❌ No hay InstructorWorkshops con clases scheduled para el período actual.');
            $this->command->error('   Asegúrate de haber corrido WorkshopReplicationService para el período actual.');
            return;
        }

        // 4. Estudiantes elegibles (exonerados primero, luego cualquiera)
        $exemptCategories = ['Vitalicios', 'Hijo de Fundador', 'Transitorio Mayor de 75'];

        $students = Student::whereIn('category_partner', $exemptCategories)->take(5)->get();

        if ($students->count() < 5) {
            $remaining = 5 - $students->count();
            $existingIds = $students->pluck('id');
            $extra = Student::whereNotIn('id', $existingIds)->take($remaining)->get();
            $students = $students->merge($extra);
        }

        if ($students->isEmpty()) {
            $this->command->error('❌ No hay estudiantes en la base de datos.');
            return;
        }

        $this->command->info("📅 Período actual: {$currentPeriod->year}/{$currentPeriod->month}");
        $this->command->info("🏫 InstructorWorkshops disponibles: {$instructorWorkshops->count()}");
        $this->command->info("👥 Estudiantes a usar: {$students->count()}");
        $this->command->newLine();

        $batchesCreated = 0;

        foreach ($students->take(5) as $student) {
            // Verificar si el estudiante ya tiene un batch completed en el período actual
            $alreadyExists = EnrollmentBatch::where('student_id', $student->id)
                ->where('payment_status', 'completed')
                ->whereHas('enrollments', fn($q) => $q->where('monthly_period_id', $currentPeriod->id))
                ->exists();

            if ($alreadyExists) {
                $this->command->warn("⚠️  {$student->last_names} {$student->first_names} ya tiene batch completed en este período. Saltando.");
                continue;
            }

            // Elegir 1-2 talleres aleatorios
            $selectedWorkshops = $instructorWorkshops->random(min(2, $instructorWorkshops->count()));
            $totalAmount = 0;

            $batch = EnrollmentBatch::create([
                'student_id'     => $student->id,
                'batch_code'     => 'REPL-TEST-' . $student->id . '-' . $now->format('Ym'),
                'total_amount'   => 0,
                'payment_status' => 'completed',
                'payment_method' => 'cash',
                'enrollment_date' => $now->toDateString(),
                'notes'          => 'Seeder para prueba de replicación',
            ]);

            foreach ($selectedWorkshops as $iw) {
                $workshop       = $iw->workshop;
                $numberOfClasses = $workshop->number_of_classes ?? 4;
                $basePrice      = $workshop->standard_monthly_fee ?? 50;
                $finalPrice     = $basePrice * $student->inscription_multiplier;
                $pricePerClass  = $finalPrice / $numberOfClasses;

                $enrollment = StudentEnrollment::create([
                    'student_id'            => $student->id,
                    'instructor_workshop_id' => $iw->id,
                    'enrollment_batch_id'   => $batch->id,
                    'monthly_period_id'     => $currentPeriod->id,
                    'enrollment_type'       => 'full_month',
                    'number_of_classes'     => $numberOfClasses,
                    'price_per_quantity'    => $pricePerClass,
                    'total_amount'          => $finalPrice,
                    'payment_status'        => 'completed',
                    'payment_method'        => 'cash',
                    'enrollment_date'       => $now->toDateString(),
                    'renewal_status'        => 'not_applicable',
                    'is_renewal'            => false,
                ]);

                // Crear EnrollmentClass por cada clase scheduled del taller (máx. number_of_classes)
                $workshopClasses = WorkshopClass::where('workshop_id', $workshop->id)
                    ->where('monthly_period_id', $currentPeriod->id)
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('class_date')
                    ->limit($numberOfClasses)
                    ->get();

                foreach ($workshopClasses as $wc) {
                    EnrollmentClass::create([
                        'student_enrollment_id' => $enrollment->id,
                        'workshop_class_id'     => $wc->id,
                        'class_fee'             => $pricePerClass,
                        'attendance_status'     => 'enrolled',
                    ]);
                }

                $totalAmount += $finalPrice;

                $this->command->line("   → {$workshop->name} | {$workshopClasses->count()} clases | S/ {$finalPrice}");
            }

            $batch->update(['total_amount' => $totalAmount]);
            $batchesCreated++;

            $eligible = $student->isMaintenanceCurrent() ? '✅ elegible' : '⚠️  NO elegible (mantenimiento)';
            $this->command->info("✅ Batch creado para: {$student->last_names} {$student->first_names} — {$eligible}");
        }

        $this->command->newLine();
        $this->command->info("🎉 {$batchesCreated} batches creados en período {$currentPeriod->year}/{$currentPeriod->month}");
        $this->command->newLine();
        $this->command->warn('👉 Para probar la replicación corre:');
        $this->command->warn('   php artisan enrollments:auto-generate --force');
    }
}
