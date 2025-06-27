<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use App\Models\Student;
use App\Models\MonthlyPeriod;
use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use App\Models\WorkshopPricing;
use App\Models\StudentEnrollment;
use Illuminate\Support\Arr;
use Filament\Notifications\Notification;

class BulkInscribeStudents extends Page
{
    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.bulk-inscribe-students';

    public $workshops;
    public $students;
    public $selectedStudent = null;
    public $enrollmentData = [];
    
    public function mount()
    {
        $ids = request()->query('workshops', '');
        $ids = array_filter(explode(',', $ids));

        $this->workshops = InstructorWorkshop::with(['workshop', 'instructor', 'classes'])->whereIn('id', $ids)->get();
        $this->students = Student::orderBy('last_names')->get();

        $defaultPeriod = MonthlyPeriod::query()->where('start_date', '>', now())->orderBy('start_date')->first();

        foreach ($this->workshops as $workshop) {
            $periodClasses = collect([]);
            if ($defaultPeriod) {
                $periodClasses = $workshop->classes->where('monthly_period_id', $defaultPeriod->id);
            }

            $this->enrollmentData[$workshop->id] = [
                'period_id' => $defaultPeriod?->id, 
                'type' => 'full_month',                 
                'classes' => $periodClasses->pluck('id')->toArray(),
                'payment_status' => 'pending', 
            ];
        }
    }
    public function updatedEnrollmentDataPeriodId($value, $key)
    {
        list($workshopId, $field) = explode('.', $key);

        $workshop = $this->workshops->firstWhere('id', $workshopId);
        if ($workshop) {
            $periodId = $this->enrollmentData[$workshopId]['period_id'];
            $type = $this->enrollmentData[$workshopId]['type'];
            $periodClasses = $workshop->classes->where('monthly_period_id', $periodId);
            
            $this->enrollmentData[$workshopId]['classes'] = ($type === 'full_month')
                ? $periodClasses->pluck('id')->toArray()
                : []; 
        }
    }
    
    public function updatedEnrollmentDataType($value, $key)
    {
        list($workshopId, $field) = explode('.', $key);

        $workshop = $this->workshops->firstWhere('id', $workshopId);
        if ($workshop) {
            $periodId = $this->enrollmentData[$workshopId]['period_id'];
            $type = $this->enrollmentData[$workshopId]['type'];

            $periodClasses = $workshop->classes->where('monthly_period_id', $periodId);            
            $this->enrollmentData[$workshopId]['classes'] = $periodClasses->pluck('id')->toArray();
        }
    }
    
    public function bulkInscribe()
    {
        if (!$this->selectedStudent) {
            Notification::make()
                ->title('Debes seleccionar un alumno.')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->workshops as $workshop) {
            $data = $this->enrollmentData[$workshop->id];
            $periodId = $data['period_id'];
            $type = $data['type'];
            $selectedClassIds = $data['classes'] ?? []; 

            $availablePeriodClassIds = $workshop->classes->where('monthly_period_id', $periodId)->pluck('id')->toArray();
            $classIdsToEnroll = array_intersect($selectedClassIds, $availablePeriodClassIds);

            if (empty($classIdsToEnroll)) {
                Notification::make()
                    ->title("Advertencia: No se seleccionaron clases para el taller '{$workshop->workshop->name}' en el periodo seleccionado. Se omitirá este taller.")
                    ->warning()
                    ->send();
                continue; 
            }

            $alreadyEnrolled = StudentEnrollment::where([
                'student_id' => $this->selectedStudent,
                'instructor_workshop_id' => $workshop->id,
                'monthly_period_id' => $periodId,
            ])->exists();

            if ($alreadyEnrolled) {
                Notification::make()
                    ->title("El alumno ya está inscrito en '{$workshop->workshop->name}' para el periodo seleccionado.")
                    ->warning()
                    ->send();
                continue;
            }

            $classCount = count($classIdsToEnroll);

            $pricing = WorkshopPricing::where('workshop_id', $workshop->workshop_id)
                ->where('number_of_classes', $classCount)
                ->first();

            $enrollment = StudentEnrollment::create([
                'student_id' => $this->selectedStudent,
                'instructor_workshop_id' => $workshop->id,
                'monthly_period_id' => $periodId,
                'enrollment_type' => $type,
                'number_of_classes' => $classCount,
                'price_per_quantity' => $pricing->price ?? 0,
                'total_amount' => $pricing->price ?? 0, 
                'payment_status' => $data['payment_status'],
                'enrollment_date' => now(),
            ]);

            foreach ($classIdsToEnroll as $classId) {
                $enrollment->enrollmentClasses()->create([
                    'workshop_class_id' => $classId,
                    'class_fee' => $pricing->price ?? 0,
                    'attendance_status' => 'enrolled',
                ]);
            }
        }

        Notification::make()
            ->title('¡Alumno inscrito en todos los horarios seleccionados!')
            ->success()
            ->send();

        return redirect(InstructorWorkshopResource::getUrl('index'));
    }    
}
