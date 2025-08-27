<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClassAttendance;
use App\Models\WorkshopClass;
use App\Models\StudentEnrollment;
use App\Models\User;

class ClassAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener un usuario admin para registrar las asistencias
        $adminUser = User::first();
        
        if (!$adminUser) {
            $this->command->warn('No hay usuarios en la base de datos. No se pueden crear asistencias.');
            return;
        }

        // Obtener todas las clases de talleres
        $workshopClasses = WorkshopClass::all();
        
        if ($workshopClasses->isEmpty()) {
            $this->command->warn('No hay clases de talleres en la base de datos.');
            return;
        }

        // Obtener todas las matrículas de estudiantes
        $studentEnrollments = StudentEnrollment::with('instructorWorkshop.workshop')->get();
        
        if ($studentEnrollments->isEmpty()) {
            $this->command->warn('No hay matrículas de estudiantes en la base de datos.');
            return;
        }

        $attendanceCount = 0;
        $comments = [
            'Excelente participación',
            'Llegó un poco tarde',
            'Muy concentrado/a en clase',
            'Necesita practicar más',
            'Mostró gran interés',
            'Participó activamente',
            null, // Sin comentarios
            null,
            null,
        ];

        foreach ($workshopClasses as $workshopClass) {
            // Encontrar las matrículas que pertenecen a este taller
            $relevantEnrollments = $studentEnrollments->filter(function ($enrollment) use ($workshopClass) {
                return $enrollment->instructorWorkshop && 
                       $enrollment->instructorWorkshop->workshop && 
                       $enrollment->instructorWorkshop->workshop->id === $workshopClass->workshop_id;
            });

            foreach ($relevantEnrollments as $enrollment) {
                // Generar asistencia aleatoria (80% de probabilidad de asistir)
                $isPresent = rand(1, 100) <= 80;
                
                // Solo agregar comentarios a veces
                $comment = $isPresent && rand(1, 100) <= 30 ? $comments[array_rand($comments)] : null;

                ClassAttendance::create([
                    'workshop_class_id' => $workshopClass->id,
                    'student_enrollment_id' => $enrollment->id,
                    'is_present' => $isPresent,
                    'comments' => $comment,
                    'recorded_by' => $adminUser->id,
                ]);

                $attendanceCount++;
            }
        }

        $this->command->info("Se crearon {$attendanceCount} registros de asistencia.");
    }
}
