<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EnrollmentBatch;
use App\Models\StudentEnrollment;
use App\Models\SystemSetting;
use App\Models\MonthlyPeriod;
use App\Services\WorkshopAutoCreationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateNextMonthEnrollments extends Command
{
    protected $signature = 'enrollments:auto-generate';    
    protected $description = 'Automatically generate enrollments for the next month based on current month completed enrollments';

    public function handle()
    {
        $this->info('Starting auto-generation process...');
        
        // Verificar si la funcionalidad está habilitada
        $isEnabled = SystemSetting::get('auto_generate_enabled', false);
        if (!$isEnabled) {
            $this->info('Auto-generation is disabled. Skipping...');
            return;
        }

        // Obtener configuraciones
        $generateDay = (int) SystemSetting::get('auto_generate_day', 20);
        $generateTime = SystemSetting::get('auto_generate_time', '23:59:59');
        
        $today = Carbon::now();
        $targetTime = Carbon::today()->setTimeFromTimeString($generateTime);
        
        // Verificar si es el día correcto del mes
        if ($today->day !== $generateDay) {
            $this->info("Today is day {$today->day}, waiting for day {$generateDay}");
            return;
        }
        
        // Verificar si ya pasó la hora configurada
        if ($today->lt($targetTime)) {
            $this->info("Current time {$today->format('H:i:s')} is before target time {$generateTime}");
            return;
        }

        $this->info("Starting auto-generation process for day {$generateDay} at {$today->format('H:i:s')}");

        // Obtener el período mensual actual
        $currentPeriod = MonthlyPeriod::where('year', $today->year)
            ->where('month', $today->month)
            ->first();

        if (!$currentPeriod) {
            $this->error('No monthly period found for current month');
            return;
        }

        // Calcular período siguiente
        $nextMonth = $currentPeriod->month + 1;
        $nextYear = $currentPeriod->year;

        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear += 1;
        }

        // Crear o verificar que existe el período siguiente
        $nextPeriod = MonthlyPeriod::firstOrCreate([
            'year' => $nextYear,
            'month' => $nextMonth,
        ]);

        $this->info("Generating enrollments from {$currentPeriod->year}/{$currentPeriod->month} to {$nextPeriod->year}/{$nextPeriod->month}");

        // Buscar lotes de inscripciones completadas del período actual
        $completedBatches = EnrollmentBatch::where('payment_status', 'completed')
            ->whereHas('enrollments', function ($query) use ($currentPeriod) {
                $query->where('monthly_period_id', $currentPeriod->id);
            })
            ->with(['enrollments.instructorWorkshop.workshop', 'student'])
            ->get();

        if ($completedBatches->isEmpty()) {
            $this->info('No completed enrollment batches found for auto-generation');
            return;
        }

        $generatedBatches = 0;
        $generatedEnrollments = 0;
        $skippedBatches = 0;
        $errors = [];

        $workshopService = new WorkshopAutoCreationService();

        DB::beginTransaction();
        
        try {
            foreach ($completedBatches as $batch) {
                try {
                    // Verificar si el estudiante ya tiene inscripciones para el próximo período
                    $existingNextMonthBatch = EnrollmentBatch::where('student_id', $batch->student_id)
                        ->whereHas('enrollments', function ($query) use ($nextPeriod) {
                            $query->where('monthly_period_id', $nextPeriod->id);
                        })
                        ->whereNotIn('payment_status', ['refunded'])
                        ->first();

                    if ($existingNextMonthBatch) {
                        $this->info("Student {$batch->student->first_names} {$batch->student->last_names} already has enrollments for next month. Skipping...");
                        $skippedBatches++;
                        continue;
                    }

                    // Verificar que el estudiante siga activo y al día
                    if (!$batch->student->is_maintenance_current) {
                        $this->info("Student {$batch->student->first_names} {$batch->student->last_names} is not current with maintenance. Skipping...");
                        $skippedBatches++;
                        continue;
                    }

                    // Crear nuevo batch para el próximo mes
                    $newBatch = EnrollmentBatch::create([
                        'student_id' => $batch->student_id,
                        'total_amount' => 0, // Se calculará después
                        'payment_status' => 'pending',
                        'payment_method' => $batch->payment_method,
                        'enrollment_date' => now()->format('Y-m-d'),
                        'notes' => 'Generación automática basada en inscripciones del mes anterior',
                        'auto_generated' => true,
                        'source_batch_id' => $batch->id,
                        'created_by' => 'null',
                    ]);

                    $totalAmount = 0;
                    $successfulEnrollments = 0;

                    // Replicar cada inscripción del batch actual
                    foreach ($batch->enrollments as $enrollment) {
                        try {
                            // Crear/obtener el taller para el próximo período
                            $newInstructorWorkshop = $workshopService->findOrCreateInstructorWorkshopForPeriod(
                                $enrollment->instructor_workshop_id, 
                                $nextPeriod->id
                            );

                            if (!$newInstructorWorkshop) {
                                $this->warn("Could not create workshop for enrollment ID {$enrollment->id}");
                                continue;
                            }

                            // Calcular precio para la nueva inscripción
                            $numberOfClasses = 4; // Por defecto 4 clases
                            $basePrice = $newInstructorWorkshop->workshop->standard_monthly_fee;
                            $finalPrice = $basePrice * $batch->student->inscription_multiplier;

                            // Crear nueva inscripción
                            $newEnrollment = StudentEnrollment::create([
                                'student_id' => $batch->student_id,
                                'instructor_workshop_id' => $newInstructorWorkshop->id,
                                'enrollment_batch_id' => $newBatch->id,
                                'monthly_period_id' => $nextPeriod->id,
                                'enrollment_type' => 'full_month',
                                'number_of_classes' => $numberOfClasses,
                                'price_per_quantity' => $finalPrice / $numberOfClasses,
                                'total_amount' => $finalPrice,
                                'payment_method' => $batch->payment_method,
                                'payment_status' => 'pending',
                                'enrollment_date' => now()->format('Y-m-d'),
                                'pricing_notes' => 'Auto-generado del mes anterior',
                            ]);

                            // Crear enrollment_classes para las nuevas fechas
                            $this->createEnrollmentClasses($newEnrollment, $newInstructorWorkshop->workshop);

                            $totalAmount += $finalPrice;
                            $successfulEnrollments++;
                            $generatedEnrollments++;

                        } catch (\Exception $e) {
                            $this->warn("Error creating enrollment for workshop {$enrollment->instructor_workshop_id}: " . $e->getMessage());
                        }
                    }

                    if ($successfulEnrollments > 0) {
                        // Actualizar el total del batch
                        $newBatch->update(['total_amount' => $totalAmount]);
                        $generatedBatches++;
                        
                        $this->info("Generated batch ID {$newBatch->id} for student {$batch->student->first_names} {$batch->student->last_names} with {$successfulEnrollments} enrollments");
                    } else {
                        // Si no se creó ninguna inscripción, eliminar el batch vacío
                        $newBatch->delete();
                        $skippedBatches++;
                        $this->warn("No enrollments created for student {$batch->student->first_names} {$batch->student->last_names}, batch deleted");
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Error generating batch for student ID {$batch->student_id}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Resumen de la operación
            $this->info("Auto-generation completed successfully");
            $this->info("- Generated batches: {$generatedBatches}");
            $this->info("- Generated enrollments: {$generatedEnrollments}");
            $this->info("- Skipped batches: {$skippedBatches}");
            
            if (!empty($errors)) {
                $this->warn("- Errors encountered: " . count($errors));
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Critical error during auto-generation: ' . $e->getMessage());
        }
    }

    private function createEnrollmentClasses(StudentEnrollment $enrollment, $workshop): void
    {
        // Obtener las 4 clases generadas automáticamente para este workshop
        $workshopClasses = \App\Models\WorkshopClass::where('workshop_id', $workshop->id)
            ->where('monthly_period_id', $enrollment->monthly_period_id)
            ->orderBy('class_date', 'asc')
            ->limit(4)
            ->get();

        $pricePerClass = $enrollment->total_amount / $enrollment->number_of_classes;

        foreach ($workshopClasses as $workshopClass) {
            \App\Models\EnrollmentClass::create([
                'student_enrollment_id' => $enrollment->id,
                'workshop_class_id' => $workshopClass->id,
                'class_fee' => $pricePerClass,
                'attendance_status' => 'enrolled',
            ]);
        }
    }
}
