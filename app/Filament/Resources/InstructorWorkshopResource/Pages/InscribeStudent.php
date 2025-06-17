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
use Filament\Notifications\Notification;
use App\Models\InstructorWorkshop;
use App\Models\Student;
use App\Models\Enrollment;

class InscribeStudent extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = InstructorWorkshopResource::class;
    protected static string $view = 'filament.resources.instructor-workshop-resource.pages.inscribe-student';
    public InstructorWorkshop $record; 
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'enrollment_date' => now()->toDateString(), 
            'status' => 'enrolled', 
            'payment_status' => 'pending', 
            'total_amount' => $this->record->class_rate * $this->record->class_count, 
            'paid_amount' => 0, 
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Alumno')
                    ->options(Student::all()->mapWithKeys(function ($student) {
                        return [
                            $student->id => "{$student->full_name} - {$student->student_code}",
                        ];
                    }))
                    ->searchable()
                    ->required()
                    ->columnSpanFull(), 
                
                DatePicker::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->default(now()) 
                    ->required(),

                Select::make('status')
                    ->label('Estado de Inscripción')
                    ->options([
                        'enrolled' => 'Inscrito',
                        'completed' => 'Completado',
                        'dropped' => 'Abandonado',
                        'pending' => 'Pendiente',
                    ])
                    ->default('enrolled')
                    ->required(),

                Select::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'partial' => 'Parcial',
                        'refunded' => 'Reembolsado',
                    ])
                    ->default('pending')
                    ->required(),

                TextInput::make('total_amount')
                    ->label('Monto Total')
                    ->numeric()
                    ->prefix('S/.')
                    ->readOnly() 
                    ->default(fn (callable $get) => $this->record->class_rate * $this->record->class_count) 
                    ->live(onBlur: true), 

                TextInput::make('paid_amount')
                    ->label('Monto Pagado')
                    ->numeric()
                    ->prefix('S/.')
                    ->required()
                    ->default(0) 
                    ->rules([
                        fn (callable $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $totalAmount = (float) $get('total_amount');
                            if ((float) $value > $totalAmount) {
                                $fail("El monto pagado no puede ser mayor que el monto total ({$totalAmount}).");
                            }
                        },
                    ]),

                Textarea::make('notes')
                    ->label('Notas')
                    ->maxLength(65535)
                    ->rows(3)
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function inscribe(): void
    {
        try {
            $data = $this->form->getState();

            // Asegúrate de que instructor_workshop_id se asigne correctamente
            $data['instructor_workshop_id'] = $this->record->id;

            Enrollment::create($data);

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