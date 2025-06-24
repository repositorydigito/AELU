<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\Enrollment;
use App\Models\Payment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\ViewField;
use Livewire\WithPagination;

class Inscription extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationLabel = 'Inscripción';
    protected static ?string $title = 'Inscripción de Alumnos a Talleres';
    protected static ?int $navigationSort = 7; 
    protected static ?string $navigationGroup = 'Talleres'; 

    protected static string $view = 'filament.pages.inscription';

    public ?array $data = [];

    public ?Collection $selectedWorkshops;


    public function mount(): void
    {
        $this->selectedWorkshops = collect();
        $this->form->fill();
    }
    public function getPaginatedWorkshopsProperty()
    {
        return Workshop::with('instructor')->orderBy('name')->paginate(6);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // =========================================================
                    // PASO 1: Selección del Alumno y Talleres
                    // =========================================================
                    Step::make('Selección de Talleres')
                        ->schema([
                            Section::make('Información del Alumno')
                                ->schema([
                                    Select::make('student_id')
                                        ->label('Alumno')
                                        ->placeholder('Selecciona un alumno')
                                        ->options(
                                            Student::all()->mapWithKeys(function ($student) {
                                                $label = "{$student->full_name} - {$student->student_code}";
                                                return [$student->id => $label];
                                            })
                                        )
                                        ->searchable()
                                        ->required()
                                        ->live(),
                                ]),

                            Section::make('Catálogo de Talleres')
                                ->schema([
                                    Repeater::make('available_workshops')
                                        ->label(' ')
                                        ->defaultItems(0)
                                        ->disableItemCreation()
                                        ->disableItemDeletion()
                                        ->disableItemMovement()
                                        ->grid(3)
                                        ->schema([
                                            Hidden::make('workshop_id')
                                                ->required(),

                                            Section::make('')
                                                ->columns(1)
                                                ->schema([
                                                    ViewField::make('workshop_details_view') 
                                                        ->view('filament.forms.components.workshop-details') 
                                                        ->viewData(function (Get $get) { 
                                                            $workshopId = $get('workshop_id');
                                                            if (!$workshopId) return []; 

                                                            $workshop = \App\Models\Workshop::with('instructor')->find($workshopId);
                                                            if (!$workshop) return ['workshop' => null, 'instructorName' => 'Taller no encontrado']; // Maneja el caso de taller no encontrado

                                                            $instructorName = $workshop->instructor ? $workshop->instructor->name : 'N/A';
                                                            $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('h:i A');
                                                            $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('h:i A');

                                                            return [
                                                                'workshop' => $workshop,
                                                                'instructorName' => $instructorName,
                                                                'startTime' => $startTime,
                                                                'endTime' => $endTime,
                                                            ];
                                                        })
                                                        ->columnSpanFull(),                                                    

                                                    Toggle::make('is_selected')
                                                        ->label('Seleccionar para inscribir')
                                                        ->inline(false)
                                                        ->live()
                                                        ->afterStateUpdated(function (Get $get, bool $state) {
                                                            $workshopId = $get('workshop_id');
                                                            $currentSelected = $this->selectedWorkshops ?? collect();

                                                            if ($state) {
                                                                if (!$currentSelected->contains('id', $workshopId)) {
                                                                    $workshop = Workshop::with('instructor')->find($workshopId);
                                                                    if ($workshop) {
                                                                        $this->selectedWorkshops = $currentSelected->push($workshop);
                                                                    }
                                                                }
                                                            } else {
                                                                $this->selectedWorkshops = $currentSelected->reject(fn ($w) => $w->id == $workshopId);
                                                            }
                                                        })
                                                        ->columnSpanFull(),
                                                ])
                                                ->compact(),
                                        ])
                                        ->afterStateHydrated(function (Repeater $component, Get $get, Set $set) {
                                            $allWorkshops = Workshop::with('instructor')->get();
                                            $repeaterItems = [];
                                            $currentSelectedIds = ($this->selectedWorkshops ?? collect())->pluck('id')->toArray();

                                            foreach ($allWorkshops as $workshop) {
                                                $repeaterItems[] = [
                                                    'workshop_id' => $workshop->id,
                                                    'is_selected' => in_array($workshop->id, $currentSelectedIds),
                                                ];
                                            }
                                            $set('available_workshops', $repeaterItems);
                                        }),
                                ]),
                        ]),

                    // =========================================================
                    // PASO 2: Confirmación y Resumen
                    // =========================================================
                    Step::make('Confirmación de Inscripción')
                        ->schema([                           
                            Section::make('Talleres Seleccionados')
                                ->schema([
                                    Repeater::make('selected_workshops_summary')
                                        ->label(' ')
                                        ->disableItemCreation()
                                        ->disableItemDeletion()
                                        ->disableItemMovement()
                                        ->schema([
                                            Placeholder::make('workshop_summary')
                                                ->content(function (Get $get) {
                                                    $workshopId = $get('workshop_id');
                                                    $workshop = Workshop::find($workshopId);
                                                    if (!$workshop) return 'Taller no encontrado.';
                                                    return "{$workshop->name} - S/. {$workshop->final_monthly_fee}";
                                                })
                                        ])
                                        ->afterStateHydrated(function (Repeater $component, Get $get, Set $set) {
                                            // Usamos ?? collect() para asegurar que siempre trabajamos con una colección
                                            $summaryItems = ($this->selectedWorkshops ?? collect())->map(fn ($w) => ['workshop_id' => $w->id])->toArray();
                                            $set('selected_workshops_summary', $summaryItems);
                                        })
                                        ->dehydrated(false),

                                    Placeholder::make('total_amount_display')
                                        ->label('Monto Total a Pagar')
                                        ->content(function () {
                                            // Usamos ?? collect() para asegurar que siempre trabajamos con una colección
                                            $total = ($this->selectedWorkshops ?? collect())->sum('final_monthly_fee');
                                            return 'S/. ' . number_format($total, 2);
                                        }),
                                ]),

                            Section::make('Detalles de Pago y Confirmación')
                                ->schema([
                                    Select::make('payment_method')
                                        ->label('Método de Pago')
                                        ->options([
                                            'Efectivo' => 'Efectivo',
                                            'Link' => 'Link',
                                        ])
                                        ->required(),                                   

                                    TextInput::make('payment_amount_received')
                                        ->label('Monto Recibido')
                                        ->numeric()
                                        ->prefix('S/.')
                                        ->required()
                                        ->live()
                                        ->rules([
                                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                if ($value < 0) {
                                                    $fail('El monto recibido no puede ser negativo.');
                                                }
                                                // Calcula el monto total de los talleres seleccionados
                                                $total = ($this->selectedWorkshops ?? collect())->sum('final_monthly_fee');
                                                if ($value > $total) {
                                                    $fail('El monto recibido no puede ser mayor al monto total a pagar.');
                                                }
                                            },
                                        ])                                   

                                    /* TextInput::make('notes')
                                        ->label('Observaciones de Inscripción')
                                        ->maxLength(255), */
                                ]),
                        ]),
                ])
                ->columns(1)
                ->model(Student::class)
                ->statePath('data'),
            ])
            ->statePath('data');
    }

    public function toggleWorkshop($workshopId)
    {
        $current = $this->selectedWorkshops ?? collect();
        if ($current->contains('id', $workshopId)) {
            $this->selectedWorkshops = $current->reject(fn ($w) => $w->id == $workshopId)->values();
        } else {
            $workshop = Workshop::with('instructor')->find($workshopId);
            if ($workshop) {
                $this->selectedWorkshops = $current->push($workshop);
            }
        }
    }    
    
    public function submitForm() 
    {
        try {
            $data = $this->form->getState();
            //dd($data);
            // VALIDACIÓN DE TALLERES SELECCIONADOS (ESTÁ BIEN)
            if (($this->selectedWorkshops ?? collect())->isEmpty()) {
                Notification::make()
                    ->title('Error de Validación')
                    ->body('Debes seleccionar al menos un taller para inscribir al alumno.')
                    ->danger()
                    ->send();
                throw new Halt();
            }

           
            $totalAmountDueForSelections = ($this->selectedWorkshops ?? collect())->sum('final_monthly_fee');
            $paymentAmountReceived = $totalAmountDueForSelections;

            $formData = $data['data'];
            $studentId = $formData['student_id'];
            $paymentMethod = $formData['payment_method'];
            $paymentAmountReceived = $formData['payment_amount_received'];
            $notes = null; 
            $enrollmentDate = Carbon::now();

            // 1. Crear el registro de pago (si el monto recibido es > 0)
            if ($paymentAmountReceived > 0) { // Esto es correcto si $paymentAmountReceived ahora es el total
                Payment::create([
                    'student_id' => $studentId,
                    'amount' => $paymentAmountReceived,
                    'payment_date' => Carbon::now(),
                    'method' => $paymentMethod,
                    // 'notes' => $notes, // <-- ¡COMENTA O ELIMINA ESTA LÍNEA SI EL CAMPO DE NOTAS YA NO ESTÁ EN EL FORMULARIO O EN $data!
                ]);
            }

            // 2. Crear las inscripciones (Enrollments)
            foreach (($this->selectedWorkshops ?? collect()) as $workshop) {
                $amountPaidForThisEnrollment = 0;
                $enrollmentStatus = 'pending';

                $amountToApply = min($paymentAmountReceived, $workshop->final_monthly_fee);
                if ($amountToApply > 0) {
                    $amountPaidForThisEnrollment = $amountToApply;
                    $paymentAmountReceived -= $amountToApply; // Restamos del total recibido para repartir entre talleres
                    if ($amountPaidForThisEnrollment == $workshop->final_monthly_fee) {
                        $enrollmentStatus = 'paid';
                    }
                }

                Enrollment::create([
                    'student_id' => $studentId,
                    'workshop_id' => $workshop->id,
                    'enrollment_date' => $enrollmentDate,
                    'status' => $enrollmentStatus,
                    'total_amount' => $workshop->final_monthly_fee,
                    'amount_paid' => $amountPaidForThisEnrollment,
                    'notes' => $notes, // <-- ¡COMENTA O ELIMINA ESTA LÍNEA SI EL CAMPO DE NOTAS YA NO ESTÁ EN EL FORMULARIO!
                ]);
            }

            Notification::make()
                ->title('Inscripción(es) registrada(s) con éxito.')
                ->success()
                ->send();

            return redirect()->route('filament.admin.pages.inscription');

        } catch (Halt $exception) {
            return redirect()->route('filament.admin.pages.inscription');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al procesar la inscripción')
                ->body('Ocurrió un error inesperado: ' . $e->getMessage())
                ->danger()
                ->send();
            return redirect()->route('filament.admin.pages.inscription');
        }
    }  
    
}