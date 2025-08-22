<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Helpers\NumberToWordsHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class StudentEnrollmentController extends Controller
{
    public function generateTicket($enrollmentId)
    {
        // 1. Cargar relaciones necesarias para el ticket
        $enrollment = StudentEnrollment::with([
            'student',
            'creator', // Cargar la relación con el usuario
            'instructorWorkshop.workshop',
            'instructorWorkshop.instructor',
            'monthlyPeriod',
            'enrollmentClasses.workshopClass',
        ])->findOrFail($enrollmentId);

        // 2. Preparar datos generales del alumno
        $student = $enrollment->student;

        // 3. Preparar los rangos de tiempo y días para el calendario
        $timeSlots = [];
        // Generar franjas horarias de 8:00 a 18:00
        for ($hour = 8; $hour <= 18; $hour++) {
            $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT); // Formato "08", "09", etc.
        }

        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // 4. Inicializar la estructura de datos del calendario
        $calendarData = [];
        foreach ($daysOfWeek as $day) {
            $calendarData[$day] = [];
            foreach ($timeSlots as $hour) {
                $calendarData[$day][$hour] = [];
            }
        }

        // 5. Rellenar el calendario con las clases inscritas
        if ($enrollment->enrollmentClasses) {
            foreach ($enrollment->enrollmentClasses as $enrollmentClass) {
                $workshopClass = $enrollmentClass->workshopClass;

                // Asegurarse de que la clase pertenece al periodo mensual de la inscripción
                if ($workshopClass->monthly_period_id !== $enrollment->monthly_period_id) {
                    continue; // Saltar clases que no corresponden al periodo de la inscripción
                }

                $classDate = Carbon::parse($workshopClass->class_date);
                $dayOfWeek = ucfirst($classDate->translatedFormat('l')); 
                $startHour = Carbon::parse($workshopClass->start_time)->format('H'); // Obtiene la hora de inicio (ej. "08")

                // Si el día y la hora están dentro de nuestro rango de calendario, añadimos la clase.
                if (in_array($dayOfWeek, $daysOfWeek) && in_array($startHour, $timeSlots)) {
                    $calendarData[$dayOfWeek][$startHour][] = [
                        'start_time' => $workshopClass->start_time,
                        'end_time' => $workshopClass->end_time,
                        'workshop_name' => $enrollment->instructorWorkshop->workshop->name,
                        'class_date' => $workshopClass->class_date,
                    ];
                }
            }
        }

        // 6. Obtener información del usuario que creó la inscripción
        $created_by_user = $enrollment->created_by_name ?? 'Mayra';

        // 7. Convertir el monto total a palabras
        $totalInWords = NumberToWordsHelper::convert($enrollment->total_amount);

        // 8. Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // 9. Renderizar la vista Blade a HTML con el nuevo formato
        $html = View::make('pdfs.enrollment_ticket', compact(
            'enrollment',
            'student',
            'timeSlots',
            'daysOfWeek',
            'calendarData',
            'created_by_user',
            'totalInWords'
        ))->render();

        $dompdf->loadHtml($html);

        // 10. Establecer el tamaño del papel
        $dompdf->setPaper('A4', 'portrait');

        // 11. Renderizar el PDF
        $dompdf->render();

        // 12. Devolver el PDF al navegador
        return $dompdf->stream('ticket_inscripcion_' . ($student->student_code ?? 'N/A') . '.pdf', ["Attachment" => false]);
    }

    public function generateBatchTicket($batchId)
    {
        // 1. Cargar el lote de inscripciones con todas las relaciones necesarias
        $enrollmentBatch = \App\Models\EnrollmentBatch::with([
            'student',
            'creator', // Cargar la relación con el usuario
            'enrollments.instructorWorkshop.workshop',
            'enrollments.instructorWorkshop.instructor',
            'enrollments.monthlyPeriod',
            'enrollments.enrollmentClasses.workshopClass',
        ])->findOrFail($batchId);

        // 2. Preparar datos generales del alumno
        $student = $enrollmentBatch->student;

        // 3. Preparar los rangos de tiempo y días para el calendario
        $timeSlots = [];
        // Generar franjas horarias de 8:00 a 18:00
        for ($hour = 8; $hour <= 18; $hour++) {
            $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT); // Formato "08", "09", etc.
        }

        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // 4. Inicializar la estructura de datos del calendario
        $calendarData = [];
        foreach ($daysOfWeek as $day) {
            $calendarData[$day] = [];
            foreach ($timeSlots as $hour) {
                $calendarData[$day][$hour] = [];
            }
        }

        // 5. Rellenar el calendario con las clases de todas las inscripciones del lote
        foreach ($enrollmentBatch->enrollments as $enrollment) {
            if ($enrollment->enrollmentClasses) {
                foreach ($enrollment->enrollmentClasses as $enrollmentClass) {
                    $workshopClass = $enrollmentClass->workshopClass;

                    // Asegurarse de que la clase pertenece al periodo mensual de la inscripción
                    if ($workshopClass->monthly_period_id !== $enrollment->monthly_period_id) {
                        continue; // Saltar clases que no corresponden al periodo de la inscripción
                    }

                    $classDate = \Carbon\Carbon::parse($workshopClass->class_date);
                    $dayOfWeek = ucfirst($classDate->translatedFormat('l')); 
                    $startHour = \Carbon\Carbon::parse($workshopClass->start_time)->format('H'); // Obtiene la hora de inicio (ej. "08")

                    // Si el día y la hora están dentro de nuestro rango de calendario, añadimos la clase.
                    if (in_array($dayOfWeek, $daysOfWeek) && in_array($startHour, $timeSlots)) {
                        $calendarData[$dayOfWeek][$startHour][] = [
                            'start_time' => $workshopClass->start_time,
                            'end_time' => $workshopClass->end_time,
                            'workshop_name' => $enrollment->instructorWorkshop->workshop->name,
                            'class_date' => $workshopClass->class_date,
                        ];
                    }
                }
            }
        }

        // 6. Obtener información del usuario que creó la inscripción
        $created_by_user = $enrollmentBatch->created_by_name ?? 'Mayra';

        // 7. Convertir el monto total a palabras
        $totalInWords = NumberToWordsHelper::convert($enrollmentBatch->total_amount);

        // 8. Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // 9. Renderizar la vista Blade a HTML con el nuevo formato
        $html = View::make('pdfs.enrollment_batch_ticket', compact(
            'enrollmentBatch',
            'student',
            'timeSlots',
            'daysOfWeek',
            'calendarData',
            'created_by_user',
            'totalInWords'
        ))->render();

        $dompdf->loadHtml($html);

        // 10. Establecer el tamaño del papel
        $dompdf->setPaper('A4', 'portrait');

        // 11. Renderizar el PDF
        $dompdf->render();

        // 12. Devolver el PDF al navegador
        return $dompdf->stream('ticket_lote_' . $enrollmentBatch->batch_code . '.pdf', ["Attachment" => false]);
    }
}