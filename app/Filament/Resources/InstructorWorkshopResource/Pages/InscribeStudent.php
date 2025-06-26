<?php

namespace App\Filament\Resources\InstructorWorkshopResource\Pages;

use App\Filament\Resources\InstructorWorkshopResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker; 
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use App\Models\InstructorWorkshop;
use App\Models\Student;
use App\Models\MonthlyPeriod;
use App\Models\StudentEnrollment;
use App\Models\WorkshopClass;

class InscribeStudent extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.inscribe-student';
    protected static ?string $title = 'Inscribir Alumno';
    public InstructorWorkshop $record; 
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'enrollment_date' => now()->toDateString(),
            'enrollment_type' => 'full_month',
            'payment_status' => 'pending',
            'number_of_classes' => $this->record->class_count ?? 4,
            'price_per_quantity' => $this->record->class_rate ?? 0,
            'total_amount' => ($this->record->class_rate ?? 0) * ($this->record->class_count ?? 4),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Alumno')
                    ->options(Student::all()->mapWithKeys(fn ($student) => [
                        $student->id => "{$student->full_name} - {$student->student_code}",
                    ]))
                    ->searchable()
                    ->required(),

                Select::make('monthly_period_id')
                    ->label('Periodo mensual')
                    ->options(\App\Models\MonthlyPeriod::all()->mapWithKeys(fn ($period) => [
                        $period->id => "{$period->year} - " . \Illuminate\Support\Carbon::create()->month($period->month)->monthName,
                    ]))
                    ->searchable()
                    ->reactive()
                    ->required(),

                Select::make('enrollment_type')
                    ->label('Tipo de inscripción')
                    ->options([
                        'full_month' => 'Mes completo',
                        'specific_classes' => 'Clases específicas',
                    ])
                    ->default('full_month')
                    ->reactive()
                    ->required(),

                // Campo oculto para number_of_classes, calculado dinámicamente
                TextInput::make('number_of_classes')
                    ->label('Cantidad de clases')
                    ->numeric()
                    ->default(4)
                    ->hidden()
                    ->dehydrated(),

                CheckboxList::make('selected_classes')
                    ->label('Clases a inscribir')
                    ->options(function (callable $get) {
                        $periodId = $get('monthly_period_id');
                        $workshopId = $this->record->id;
                        if (!$periodId) return [];

                        $clases = WorkshopClass::where('instructor_workshop_id', $workshopId)
                            ->where('monthly_period_id', $periodId)
                            ->orderBy('class_date')
                            ->get();

                        return $clases->mapWithKeys(fn($clase) => [
                            $clase->id => $clase->class_date->format('d/m/Y') . ' ' . $clase->start_time->format('H:i') . ' - ' . $clase->end_time->format('H:i'),
                        ])->toArray();
                    })
                    ->visible(fn (callable $get) => $get('monthly_period_id'))
                    ->required(fn (callable $get) => $get('enrollment_type') === 'specific_classes')
                    ->disabled(fn (callable $get) => $get('enrollment_type') !== 'specific_classes')
                    ->columns(2)
                    ->reactive()
                    ->helperText('Selecciona las clases a las que deseas inscribirte. Si es mes completo, se inscribirá a todas las clases automáticamente.'),

                Select::make('payment_status')
                    ->label('Estado de pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'completed' => 'Pagado',
                    ])
                    ->default('pending')
                    ->required(),

                DatePicker::make('enrollment_date')
                    ->label('Fecha de inscripción')
                    ->default(now())
                    ->required(),
                
            ])
            ->statePath('data');
    }

    public function inscribe(): void
    {
        try {
            $data = $this->form->getState();
            $data['instructor_workshop_id'] = $this->record->id;

            // Si no viene number_of_classes, lo calculamos según la selección
            if (!isset($data['number_of_classes']) || $data['number_of_classes'] === null) {
                if (($data['enrollment_type'] ?? null) === 'specific_classes') {
                    $data['number_of_classes'] = isset($data['selected_classes']) ? count($data['selected_classes']) : 0;
                } elseif (($data['enrollment_type'] ?? null) === 'full_month') {
                    $data['number_of_classes'] = \App\Models\WorkshopClass::where('instructor_workshop_id', $this->record->id)
                        ->where('monthly_period_id', $data['monthly_period_id'])
                        ->count();
                }
            }

            $pricing = \App\Models\WorkshopPricing::where('workshop_id', $this->record->workshop_id)
                ->where('number_of_classes', $data['number_of_classes'])
                ->where(function($q) {
                    $q->where('for_volunteer_workshop', $this->record->is_volunteer)
                    ->orWhereNull('for_volunteer_workshop');
                })
                ->first();

            if ($pricing) {
                $data['price_per_quantity'] = $pricing->price;
                $data['total_amount'] = $pricing->price;
            } else {
                Notification::make()
                    ->title('No existe tarifa configurada para esa cantidad de clases.')
                    ->danger()
                    ->send();
                return;
            }

            if ($data['enrollment_type'] === 'full_month') {
                $clases = WorkshopClass::where('instructor_workshop_id', $this->record->id)
                    ->where('monthly_period_id', $data['monthly_period_id'])
                    ->orderBy('class_date')
                    ->pluck('id')
                    ->toArray();
                $data['selected_classes'] = $clases;
            }

            $enrollment = StudentEnrollment::create($data);

            foreach ($data['selected_classes'] as $classId) {
                $enrollment->enrollmentClasses()->create([
                    'workshop_class_id' => $classId,
                    'class_fee' => $data['price_per_quantity'] ?? 0,
                    'attendance_status' => 'enrolled',
                ]);
            }

            Notification::make()
                ->title('¡Inscripción exitosa!')
                ->success()
                ->send();

            $this->redirect(InstructorWorkshopResource::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al inscribir al alumno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}