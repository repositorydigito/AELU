<?php

namespace App\Filament\Pages;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class EnrollmentsReport1 extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.enrollments-report1';
    protected static ?string $title = 'Inscripciones por Alumno';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $selectedStudent = null;
    public $studentEnrollments = [];
    public $studentData = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Seleccionar Alumno')
                    ->placeholder('Buscar alumno por nombre o documento...')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Student::where('first_names', 'like', "%{$search}%")
                            ->orWhere('last_names', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($student) {
                                return [
                                    $student->id => $student->first_names . ' ' . $student->last_names . ' - ' . $student->document_number
                                ];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $student = Student::find($value);
                        return $student ? $student->first_names . ' ' . $student->last_names . ' - ' . $student->document_number : null;
                    })
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedStudent = $state;
                        $this->loadStudentEnrollments();
                    })
                    ->required(),
            ])
            ->statePath('data');
    }

    public function loadStudentEnrollments(): void
    {
        if (!$this->selectedStudent) {
            $this->studentEnrollments = [];
            $this->studentData = null;
            return;
        }

        // Cargar datos del estudiante
        $this->studentData = Student::find($this->selectedStudent);

        // Cargar inscripciones del estudiante con todas las relaciones necesarias
        $this->studentEnrollments = StudentEnrollment::where('student_id', $this->selectedStudent)
            ->with([
                'instructorWorkshop.workshop',
                'instructorWorkshop.instructor',
                'monthlyPeriod',
                'enrollmentBatch', // Para obtener el documento/ticket
                'creator' // Para obtener el cajero
            ])
            ->orderBy('enrollment_date', 'desc')
            ->get()
            ->map(function ($enrollment) {
                $workshop = $enrollment->instructorWorkshop->workshop ?? null;
                $instructor = $enrollment->instructorWorkshop->instructor ?? null;
                $period = $enrollment->monthlyPeriod ?? null;
                $batch = $enrollment->enrollmentBatch ?? null;
                $cashier = $enrollment->creator ?? null;

                return [
                    'id' => $enrollment->id,
                    'workshop_name' => $workshop->name ?? 'N/A',
                    'instructor_name' => $instructor ? ($instructor->first_names . ' ' . $instructor->last_names) : 'N/A',
                    'period_name' => $period ? $this->generatePeriodName($period->month, $period->year) : 'N/A',
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                    'enrollment_type' => $enrollment->enrollment_type,
                    'number_of_classes' => $enrollment->number_of_classes,
                    'total_amount' => $enrollment->total_amount,
                    'payment_method' => $enrollment->payment_method === 'cash' ? 'Efectivo' : ($enrollment->payment_method === 'link' ? 'Link' : ucfirst($enrollment->payment_method)),
                    'modality' => $workshop->modality ?? '',
                    'payment_document' => $batch ? $batch->batch_code : '',
                    'cashier_name' => $cashier ? $cashier->name : 'N/A',
                ];
            })
            ->toArray();
    }

    public function generatePDFAction(): Action
    {
        return Action::make('generatePDF')
            ->label('Generar PDF')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn () => !empty($this->studentEnrollments))
            ->action(function () {
                try {
                    return $this->generatePDF();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error en la acción')
                        ->body('Error: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function generatePDF()
    {
        if (!$this->studentData || empty($this->studentEnrollments)) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un alumno con inscripciones')
                ->danger()
                ->send();
            return;
        }

        try {
            // 1. Renderizar la vista Blade a HTML
            $html = View::make('reports.student-enrollments', [
                'student' => $this->studentData,
                'enrollments' => $this->studentEnrollments,
                'generated_at' => now()->format('d/m/Y H:i')
            ])->render();

            // 2. Configurar Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            // 3. Configurar papel
            $dompdf->setPaper('A4', 'portrait');

            // 4. Renderizar el PDF
            $dompdf->render();

            $fileName = 'inscripciones-' . str($this->studentData->first_names . '-' . $this->studentData->last_names)->slug() . '.pdf';

            // 5. Descargar el PDF
            return response()->stream(function () use ($dompdf) {
                echo $dompdf->output();
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getActions(): array
    {
        return [
            $this->generatePDFAction(),
        ];
    }

    private function generatePeriodName($month, $year): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $monthName = $monthNames[$month] ?? 'Mes ' . $month;
        return $monthName . ' ' . $year;
    }
}
