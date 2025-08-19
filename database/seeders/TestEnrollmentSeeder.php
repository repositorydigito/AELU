<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\MonthlyPeriod;
use App\Models\Workshop;
use App\Models\Instructor;
use App\Models\InstructorWorkshop;
use App\Models\StudentEnrollment;
use App\Models\EnrollmentBatch;
use Carbon\Carbon;

class TestEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear períodos mensuales (mes anterior y actual)
        $previousMonth = now()->subMonth();
        $currentMonth = now();

        $previousPeriod = MonthlyPeriod::firstOrCreate([
            'year' => $previousMonth->year,
            'month' => $previousMonth->month,
        ], [
            'start_date' => $previousMonth->startOfMonth()->toDateString(),
            'end_date' => $previousMonth->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $currentPeriod = MonthlyPeriod::firstOrCreate([
            'year' => $currentMonth->year,
            'month' => $currentMonth->month,
        ], [
            'start_date' => $currentMonth->startOfMonth()->toDateString(),
            'end_date' => $currentMonth->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        // 2. Crear estudiantes de prueba
        $continuingStudent = Student::firstOrCreate([
            'document_number' => '12345678',
        ], [
            'last_names' => 'García Pérez',
            'first_names' => 'Juan Carlos',
            'document_type' => 'DNI',
            'birth_date' => '1990-05-15',
            'student_code' => 'EST001',
            'category_partner' => 'Socio Activo',
            'pricing_multiplier' => 1.00,
            'monthly_maintenance_status' => 'al_dia',
        ]);

        $newStudent = Student::firstOrCreate([
            'document_number' => '87654321',
        ], [
            'last_names' => 'López Morales',
            'first_names' => 'María Isabel',
            'document_type' => 'DNI',
            'birth_date' => '1985-08-22',
            'student_code' => 'EST002',
            'category_partner' => 'Socio Activo',
            'pricing_multiplier' => 1.00,
            'monthly_maintenance_status' => 'al_dia',
        ]);

        $prepamaStudent = Student::firstOrCreate([
            'document_number' => '11223344',
        ], [
            'last_names' => 'Rodríguez Silva',
            'first_names' => 'Carlos Eduardo',
            'document_type' => 'DNI',
            'birth_date' => '1992-12-10',
            'student_code' => 'EST003',
            'category_partner' => 'Individual PRE-PAMA',
            'pricing_multiplier' => 1.50,
            'monthly_maintenance_status' => 'al_dia',
        ]);

        // 3. Crear instructores
        $instructor1 = Instructor::firstOrCreate([
            'document_number' => 'INST001',
        ], [
            'last_names' => 'Martínez',
            'first_names' => 'Ana',
            'document_type' => 'DNI',
            'birth_date' => '1980-03-12',
        ]);

        $instructor2 = Instructor::firstOrCreate([
            'document_number' => 'INST002',
        ], [
            'last_names' => 'Fernández',
            'first_names' => 'Roberto',
            'document_type' => 'DNI',
            'birth_date' => '1975-07-25',
        ]);

        // 4. Crear talleres
        $workshop1 = Workshop::firstOrCreate([
            'name' => 'Taller Prueba 1',
        ], [
            'description' => 'Taller de prueba para testing',
            'standard_monthly_fee' => 120.00,
            'capacity' => 15,
            'number_of_classes' => 4,
        ]);

        $workshop2 = Workshop::firstOrCreate([
            'name' => 'Taller Prueba 2',
        ], [
            'description' => 'Segundo taller de prueba',
            'standard_monthly_fee' => 150.00,
            'capacity' => 12,
            'number_of_classes' => 4,
        ]);

        $workshop3 = Workshop::firstOrCreate([
            'name' => 'Taller Prueba 3',
        ], [
            'description' => 'Tercer taller de prueba',
            'standard_monthly_fee' => 140.00,
            'capacity' => 10,
            'number_of_classes' => 4,
        ]);

        $workshop4 = Workshop::firstOrCreate([
            'name' => 'Taller Prueba 4',
        ], [
            'description' => 'Cuarto taller de prueba',
            'standard_monthly_fee' => 100.00,
            'capacity' => 20,
            'number_of_classes' => 4,
        ]);

        // 5. Crear instructor_workshops
        $instructorWorkshop1 = InstructorWorkshop::firstOrCreate([
            'instructor_id' => $instructor1->id,
            'workshop_id' => $workshop1->id,
            'day_of_week' => 1, // Lunes
            'start_time' => '09:00:00',
        ], [
            'end_time' => '10:00:00',
            'max_capacity' => 15,
            'payment_type' => 'volunteer',
        ]);

        $instructorWorkshop2 = InstructorWorkshop::firstOrCreate([
            'instructor_id' => $instructor2->id,
            'workshop_id' => $workshop2->id,
            'day_of_week' => 3, // Miércoles
            'start_time' => '18:00:00',
        ], [
            'end_time' => '19:00:00',
            'max_capacity' => 12,
            'payment_type' => 'volunteer',
        ]);

        $instructorWorkshop3 = InstructorWorkshop::firstOrCreate([
            'instructor_id' => $instructor1->id,
            'workshop_id' => $workshop3->id,
            'day_of_week' => 5, // Viernes
            'start_time' => '10:00:00',
        ], [
            'end_time' => '11:00:00',
            'max_capacity' => 10,
            'payment_type' => 'volunteer',
        ]);

        $instructorWorkshop4 = InstructorWorkshop::firstOrCreate([
            'instructor_id' => $instructor2->id,
            'workshop_id' => $workshop4->id,
            'day_of_week' => 2, // Martes
            'start_time' => '17:00:00',
        ], [
            'end_time' => '18:00:00',
            'max_capacity' => 20,
            'payment_type' => 'volunteer',
        ]);

        // 6. Crear inscripciones del mes anterior para el estudiante continuador
        $batch1 = EnrollmentBatch::create([
            'student_id' => $continuingStudent->id,
            'batch_code' => 'BATCH-' . uniqid(),
            'total_amount' => 260.00,
            'payment_status' => 'completed',
            'payment_method' => 'cash',
            'enrollment_date' => $previousMonth->toDateString(),
        ]);

        // Yoga Principiantes (mes anterior)
        StudentEnrollment::create([
            'student_id' => $continuingStudent->id,
            'instructor_workshop_id' => $instructorWorkshop1->id,
            'monthly_period_id' => $previousPeriod->id,
            'enrollment_batch_id' => $batch1->id,
            'enrollment_type' => 'full_month',
            'number_of_classes' => 4,
            'price_per_quantity' => 30.00,
            'total_amount' => 120.00,
            'payment_status' => 'completed',
            'payment_method' => 'cash',
            'enrollment_date' => $previousMonth->toDateString(),
        ]);

        // Pilates (mes anterior)
        StudentEnrollment::create([
            'student_id' => $continuingStudent->id,
            'instructor_workshop_id' => $instructorWorkshop3->id,
            'monthly_period_id' => $previousPeriod->id,
            'enrollment_batch_id' => $batch1->id,
            'enrollment_type' => 'full_month',
            'number_of_classes' => 4,
            'price_per_quantity' => 35.00,
            'total_amount' => 140.00,
            'payment_status' => 'completed',
            'payment_method' => 'cash',
            'enrollment_date' => $previousMonth->toDateString(),
        ]);

        // 7. Crear inscripciones del mes anterior para el estudiante PRE-PAMA
        $batch2 = EnrollmentBatch::create([
            'student_id' => $prepamaStudent->id,
            'batch_code' => 'BATCH-' . uniqid(),
            'total_amount' => 180.00, // Con recargo PRE-PAMA
            'payment_status' => 'completed',
            'payment_method' => 'cash',
            'enrollment_date' => $previousMonth->toDateString(),
        ]);

        // Natación (mes anterior) - con recargo PRE-PAMA
        StudentEnrollment::create([
            'student_id' => $prepamaStudent->id,
            'instructor_workshop_id' => $instructorWorkshop2->id,
            'monthly_period_id' => $previousPeriod->id,
            'enrollment_batch_id' => $batch2->id,
            'enrollment_type' => 'full_month',
            'number_of_classes' => 4,
            'price_per_quantity' => 45.00, // 37.50 * 1.5 (recargo PRE-PAMA)
            'total_amount' => 180.00, // 150 * 1.5
            'payment_status' => 'completed',
            'payment_method' => 'cash',
            'enrollment_date' => $previousMonth->toDateString(),
        ]);

        $this->command->info('✅ Datos de prueba creados exitosamente:');
        $this->command->info("📅 Período anterior: {$previousPeriod->year}-{$previousPeriod->month}");
        $this->command->info("📅 Período actual: {$currentPeriod->year}-{$currentPeriod->month}");
        $this->command->info('👥 Estudiantes:');
        $this->command->info("   - Continuador: {$continuingStudent->first_names} {$continuingStudent->last_names} (tiene 2 talleres previos)");
        $this->command->info("   - Nuevo: {$newStudent->first_names} {$newStudent->last_names} (sin talleres previos)");
        $this->command->info("   - PRE-PAMA: {$prepamaStudent->first_names} {$prepamaStudent->last_names} (tiene 1 taller previo con recargo)");
        $this->command->info('🏃‍♂️ Talleres disponibles: Taller Prueba 1, 2, 3 y 4');

        $this->command->warn('💡 Ahora puedes probar:');
        $this->command->warn('   1. Seleccionar el estudiante continuador → Debería mostrar talleres previos');
        $this->command->warn('   2. Seleccionar el estudiante nuevo → Debería mostrar como "Estudiante Nuevo"');
        $this->command->warn('   3. Seleccionar el estudiante PRE-PAMA → Debería mostrar recargo en el paso 3');
    }
}
