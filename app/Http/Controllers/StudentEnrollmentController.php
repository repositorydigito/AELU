<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View; 

class StudentEnrollmentController extends Controller
{    
    public function generateTicket($enrollmentId)
    {
        // 1. Cargar relaciones necesarias para el ticket
        // Aseguramos que todas las relaciones estén cargadas para evitar N+1 queries en la vista.
        $enrollment = StudentEnrollment::with([
            'student',
            'instructorWorkshop.workshop',
            'instructorWorkshop.instructor',
            'instructorWorkshop.classes',
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
        // Esto creará un array como:
        // [
        //     'Lunes' => ['08' => [], '09' => [], ...],
        //     'Martes' => ['08' => [], '09' => [], ...],
        //     ...
        // ]
        $calendarData = [];
        foreach ($daysOfWeek as $day) {
            $calendarData[$day] = [];
            foreach ($timeSlots as $hour) {
                $calendarData[$day][$hour] = [];
            }
        }

        // 5. Rellenar el calendario con las clases inscritas
        // Iteramos sobre las clases específicas a las que el alumno se inscribió.
        foreach ($enrollment->enrollmentClasses as $enrollmentClass) {
            $workshopClass = $enrollmentClass->workshopClass;

            // Depuración temporal
            //dump($workshopClass->class_date, $workshopClass->start_time, $workshopClass->end_time);

            // Asegurarse de que la clase pertenece al periodo mensual de la inscripción
            if ($workshopClass->monthly_period_id !== $enrollment->monthly_period_id) {
                continue; // Saltar clases que no corresponden al periodo de la inscripción
            }

            $classDate = Carbon::parse($workshopClass->class_date);
            $dayOfWeek = ucfirst($classDate->translatedFormat('l')); 
            $startHour = Carbon::parse($workshopClass->start_time)->format('H'); // Obtiene la hora de inicio (ej. "08")

            //dump($dayOfWeek, $startHour);

            // Si el día y la hora están dentro de nuestro rango de calendario, añadimos la clase.
            if (in_array($dayOfWeek, $daysOfWeek) && in_array($startHour, $timeSlots)) {
                $calendarData[$dayOfWeek][$startHour][] = [
                    'start_time' => $workshopClass->start_time,
                    'end_time' => $workshopClass->end_time,
                    'workshop_name' => $enrollment->instructorWorkshop->workshop->name,
                    'class_date' => $workshopClass->class_date,
                    // Puedes añadir más detalles aquí si los necesitas en la visualización
                ];
            }
        }

        // 6. Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Permitir cargar recursos remotos (fonts, si usas)

        $dompdf = new Dompdf($options);

        // 7. Renderizar la vista Blade a HTML
        $html = View::make('pdfs.enrollment_ticket', compact(
            'enrollment',
            'student',
            'timeSlots',
            'daysOfWeek',
            'calendarData'
        ))->render();

        $dompdf->loadHtml($html);

        // 8. Opcional: Establecer el tamaño del papel y la orientación (A4 por defecto, puedes cambiar a 'landscape' si el calendario es muy ancho)
        $dompdf->setPaper('A4', 'portrait');

        // 9. Renderizar el PDF
        $dompdf->render();

        // 10. Devolver el PDF al navegador
        // "Attachment" => false hará que el navegador lo muestre en lugar de descargarlo directamente
        return $dompdf->stream('ticket_inscripcion_' . ($student->student_code ?? 'N/A') . '.pdf', ["Attachment" => false]);
    }
}