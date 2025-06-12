<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\Enrollment;
use App\Models\InstructorWorkshop;
use App\Models\MedicalRecord;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;
    protected static string $view = 'filament.resources.report-resource.pages.list-reports';

    protected function getHeaderActions(): array
    {
        return [
            // Removemos el botón de crear ya que es un dashboard
        ];
    }

    public function getActiveStudentsData(): array
    {
        // Usamos el total de estudiantes como base
        $currentTotal = Student::count();

        // Simulamos datos del período anterior para el ejemplo
        $previousTotal = max(0, $currentTotal - rand(10, 30));
        $percentage = $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100) : 0;

        return [
            'total' => $currentTotal,
            'previous_total' => $previousTotal,
            'percentage' => $percentage,
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    public function getDoublePaysData(): array
    {
        // Simulamos casos de doble pago
        $currentTotal = rand(5, 12);
        $previousTotal = rand(3, 10);
        $percentage = $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100) : 0;

        return [
            'total' => $currentTotal,
            'previous_total' => $previousTotal,
            'percentage' => $percentage,
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    public function getUnexcusedAbsencesData(): array
    {
        // Simulamos faltas sin justificar
        $currentTotal = rand(8, 15);
        $previousTotal = rand(10, 18);
        $percentage = $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100) : 0;

        return [
            'total' => $currentTotal,
            'previous_total' => $previousTotal,
            'percentage' => $percentage,
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    public function getSpecialNeedsData(): array
    {
        // Contamos estudiantes con registros médicos
        $currentTotal = Student::whereHas('medicalRecord')->count();

        $previousTotal = max(0, $currentTotal - rand(0, 2));
        $percentage = $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100) : 0;

        return [
            'total' => $currentTotal,
            'previous_total' => $previousTotal,
            'percentage' => $percentage,
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    public function getWorkshopCategoriesData(): array
    {
        // Simulamos datos para las categorías de talleres
        $data = [
            'Expresión Artística y Cultural' => 470,
            'Recreación y Vida Práctica' => 361,
            'Bienestar Físico, Mental y espiritual' => 530,
            'Acondicionamiento Corporal' => 305,
            'Tecnología y Aprendizaje Digital' => 185,
            'Artes y Manualidades' => 155
        ];

        return $data;
    }

    public function getAttendanceData(): array
    {
        $totalStudents = Student::count();

        // Simulamos datos de asistencia
        $present = round($totalStudents * 0.72); // 72% presente
        $absent = $totalStudents - $present;

        return [
            'present' => $present,
            'absent' => $absent,
            'present_percentage' => $totalStudents > 0 ? round(($present / $totalStudents) * 100) : 0,
            'absent_percentage' => $totalStudents > 0 ? round(($absent / $totalStudents) * 100) : 0
        ];
    }

    public function getPendingPaymentsData(): array
    {
        // Simulamos datos de pagos
        $paid = 210;
        $pending = 42;
        $total = $paid + $pending;

        return [
            'paid' => $paid,
            'pending' => $pending,
            'paid_percentage' => $total > 0 ? round(($paid / $total) * 100) : 0,
            'pending_percentage' => $total > 0 ? round(($pending / $total) * 100) : 0
        ];
    }
}
