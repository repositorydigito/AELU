<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AffidavitController extends Controller
{
    public function generatePdf(Student $student)
    {
        // Asegúrate de que el estudiante tenga una declaración jurada
        if (!$student->affidavit) {
            abort(404, 'Declaración jurada no encontrada para este estudiante.');
        }

        // Obtener datos del estudiante y su declaración jurada (y ficha médica si es necesario)
        $student->load('medicalRecord', 'affidavit'); // Carga las relaciones necesarias

        // Puedes pasar todos los datos relevantes a la vista Blade
        $data = [
            'student' => $student,
            // Aquí puedes añadir otros datos que necesites en el PDF
        ];

        // 1. Renderiza la vista Blade a HTML
        $html = View::make('pdfs.affidavit', $data)->render();

        // 2. Configura Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Habilitar si usas imágenes externas o fuentes de Google Fonts
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);

        // (Opcional) Configura el tamaño del papel y la orientación
        $dompdf->setPaper('A4', 'portrait');

        // 3. Renderiza el PDF
        $dompdf->render();

        // 4. Sirve el PDF para descarga
        $filename = 'declaracion_jurada_' . $student->document_number . '.pdf';
        return response()->stream(function () use ($dompdf) {
            echo $dompdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
