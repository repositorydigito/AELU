<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\MonthlyPeriod;
use App\Models\InstructorWorkshop;
use App\Models\WorkshopClass;
use App\Models\WorkshopPricing;
use App\Models\StudentEnrollment;

class BulkInscribeStudents extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.bulk-inscribe-students';
    protected static ?string $title = 'Inscripción múltiple de alumno';

    public ?array $data = [];
    public array $workshops = [];

    public function mount(): void
    {
        // Recibe los IDs de los talleres seleccionados como string separado por comas o array
        $workshopIds = request()->get('workshops', []);
        if (is_string($workshopIds)) {
            $workshopIds = array_filter(explode(',', $workshopIds));
        }
        $this->workshops = InstructorWorkshop::whereIn('id', $workshopIds)->get()->all();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('student_id')
                ->label('Alumno')
                ->options(Student::all()->mapWithKeys(fn ($student) => [
                    $student->id => "{$student->full_name} - {$student->student_code}",
                ]))
                ->searchable()
                ->required(),
            Select::make('monthly_period_id')
                ->label('Periodo mensual')
                ->options(MonthlyPeriod::all()->mapWithKeys(fn ($period) => [
                    $period->id => "{$period->year} - " . \Illuminate\Support\Carbon::create()->month($period->month)->monthName,
                ]))
                ->searchable()
                ->required(),
            // Aquí, por cada taller, se agregará dinámicamente un bloque de selección
        ])->statePath('data');
    }    

    /**
     * Devuelve las clases del workshop filtradas por periodo mensual seleccionado.
     * Si no hay periodo seleccionado, retorna colección vacía.
     *
     * @param int $workshopId
     * @return \Illuminate\Support\Collection
     */
    public function getWorkshopClasses($workshopId)
    {
        // Forzar reactividad: accedemos a los datos relevantes para que Livewire detecte cambios
        $periodId = $this->data['monthly_period_id'] ?? null;
        $enrollmentType = data_get($this->data, 'workshops.' . $workshopId . '.enrollment_type', null);
        // Si no hay periodo, no hay clases
        if (!$periodId) {
            return collect();
        }
        // Siempre devolver las clases del periodo y workshop
        return WorkshopClass::where('instructor_workshop_id', $workshopId)
            ->where('monthly_period_id', $periodId)
            ->orderBy('class_date')
            ->get();
    }

    // Opcional: Si quieres forzar el refresco de la vista al cambiar el periodo o tipo de inscripción
    public function updated($propertyName)
    {
        // Si cambia el periodo o el tipo de inscripción de cualquier taller, forzar refresco
        if ($propertyName === 'data.monthly_period_id' || str_starts_with($propertyName, 'data.workshops.')) {
            $this->emitSelf('$refresh');
        }
    }

    public function inscribe()
    {
        // Aquí procesarás la inscripción múltiple, leyendo los datos de $this->data
        // y de los campos dinámicos por taller
        Notification::make()
            ->title('Funcionalidad de inscripción múltiple aún no implementada')
            ->warning()
            ->send();
    }
}
