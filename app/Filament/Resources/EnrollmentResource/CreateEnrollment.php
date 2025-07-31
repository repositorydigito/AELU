<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Models\StudentEnrollment;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;
    
    protected static ?string $title = 'Crear Inscripción';
    
    public function getTitle(): string
    {
        return 'Crear Inscripción';
    }
    
    public function getBreadcrumb(): string
    {
        return 'Crear';
    }
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Obtener los talleres seleccionados
        $selectedWorkshops = json_decode($data['selected_workshops'] ?? '[]', true);
        $workshopDetails = $data['workshop_details'] ?? [];
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $paymentStatus = $data['payment_status'] ?? 'pending';
        
        if (empty($selectedWorkshops)) {
            Notification::make()
                ->title('Error')
                ->body('Debes seleccionar al menos un taller.')
                ->danger()
                ->send();
            
            throw new \Exception('No se seleccionaron talleres');
        }
        
        // Determinar el estado de pago final
        $finalPaymentStatus = $paymentMethod === 'cash' ? 'completed' : 'pending';
        
        // Calcular el total de todas las inscripciones
        $totalAmount = 0;
        $validWorkshopDetails = [];
        $skippedWorkshops = [];
        
        foreach ($workshopDetails as $detail) {
            if (!isset($detail['instructor_workshop_id']) || !in_array($detail['instructor_workshop_id'], $selectedWorkshops)) {
                $skippedWorkshops[] = "Taller no válido o no seleccionado";
                continue;
            }
            
            // Validación específica: No permitir el mismo taller para la misma persona
            $existingEnrollment = \App\Models\StudentEnrollment::where('student_id', $data['student_id'])
                ->where('instructor_workshop_id', $detail['instructor_workshop_id'])
                ->where('payment_status', 'completed') // Solo considerar inscripciones completadas
                ->whereDate('enrollment_date', '>=', now()->subMonths(6)) // Últimos 6 meses
                ->first();
            
            if ($existingEnrollment) {
                $instructorWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                    ->find($detail['instructor_workshop_id']);
                
                if ($instructorWorkshop) {
                    $workshopName = $instructorWorkshop->workshop->name;
                    $instructorName = $instructorWorkshop->instructor->first_names . ' ' . $instructorWorkshop->instructor->last_names;
                    
                    Notification::make()
                        ->title('Taller ya inscrito')
                        ->body("El estudiante ya está inscrito en '{$workshopName}' con {$instructorName}. Este taller se omitirá de la inscripción.")
                        ->warning()
                        ->send();
                    
                    $skippedWorkshops[] = "Duplicado: {$workshopName}";
                    continue; // Saltar este taller pero continuar con los demás
                }
            }
            
            // Obtener el precio desde workshop_pricings
            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($detail['instructor_workshop_id']);
            $numberOfClasses = $detail['number_of_classes'];
            
            // Buscar el precio en la tabla workshop_pricings
            $pricing = \App\Models\WorkshopPricing::where('workshop_id', $instructorWorkshop->workshop->id)
                ->where('number_of_classes', $numberOfClasses)
                ->where('for_volunteer_workshop', false) // Asumiendo que no es voluntario por defecto
                ->first();
            
            // Si no existe el pricing, calcular basado en el precio estándar
            $workshopTotal = $pricing ? $pricing->price : ($instructorWorkshop->workshop->standard_monthly_fee * $numberOfClasses / 4);
            $totalAmount += $workshopTotal;
            
            $detail['calculated_total'] = $workshopTotal;
            $detail['price_per_class'] = $workshopTotal / $numberOfClasses;
            $validWorkshopDetails[] = $detail;
        }
        
        if (empty($validWorkshopDetails)) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo crear ninguna inscripción.')
                ->danger()
                ->send();
            
            throw new \Exception('No se crearon inscripciones');
        }
        
        // Crear el lote de inscripciones
        $enrollmentBatch = \App\Models\EnrollmentBatch::create([
            'student_id' => $data['student_id'],
            'total_amount' => $totalAmount,
            'payment_status' => $finalPaymentStatus,
            'payment_method' => $paymentMethod,
            'enrollment_date' => $validWorkshopDetails[0]['enrollment_date'], // Usar la fecha del primer taller
            'notes' => $data['notes'] ?? null,
        ]);
        
        $createdEnrollments = [];
        
        // Crear las inscripciones individuales asociadas al lote
        foreach ($validWorkshopDetails as $index => $detail) {
            // Para evitar la restricción de unicidad, generar un monthly_period_id único
            // Buscar el próximo monthly_period_id disponible para este estudiante y taller
            $existingPeriods = \App\Models\StudentEnrollment::where('student_id', $data['student_id'])
                ->where('instructor_workshop_id', $detail['instructor_workshop_id'])
                ->pluck('monthly_period_id')
                ->toArray();
            
            // Encontrar el primer ID disponible
            $monthlyPeriodId = 1;
            while (in_array($monthlyPeriodId, $existingPeriods)) {
                $monthlyPeriodId++;
            }
            
            $enrollment = StudentEnrollment::create([
                'student_id' => $data['student_id'],
                'instructor_workshop_id' => $detail['instructor_workshop_id'],
                'enrollment_batch_id' => $enrollmentBatch->id,
                'monthly_period_id' => $monthlyPeriodId,
                'enrollment_type' => $detail['enrollment_type'] ?? 'specific_classes',
                'number_of_classes' => $detail['number_of_classes'],
                'price_per_quantity' => $detail['price_per_class'],
                'total_amount' => $detail['calculated_total'],
                'payment_method' => $paymentMethod,
                'payment_status' => $finalPaymentStatus,
                'enrollment_date' => $detail['enrollment_date'],
                'pricing_notes' => $data['notes'] ?? null,
            ]);
            
            $createdEnrollments[] = $enrollment;
            
            // Crear registros en enrollment_classes
            $this->createEnrollmentClasses($enrollment);
        }
        
        // Mostrar notificación de éxito
        $count = count($createdEnrollments);
        $student = \App\Models\Student::find($data['student_id']);
        
        if ($paymentMethod === 'cash') {
            // Pago en efectivo - Estado: Inscrito - Generar PDF
            Notification::make()
                ->title('¡Inscripciones completadas!')
                ->body("Se creó un lote con {$count} inscripción" . ($count > 1 ? 'es' : '') . " correctamente. Estado: Inscrito. Se generará el ticket PDF.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download_ticket')
                        ->label('Descargar Ticket')
                        ->url(route('enrollment.batch.ticket', ['batchId' => $enrollmentBatch->id]))
                        ->openUrlInNewTab()
                        ->button(),
                ])
                ->persistent()
                ->send();
        } else {
            // Pago con link - Estado: En Proceso
            Notification::make()
                ->title('¡Inscripciones en proceso!')
                ->body("Se creó un lote con {$count} inscripción" . ($count > 1 ? 'es' : '') . " correctamente. Estado: En Proceso. El ticket PDF se generará cuando se confirme el pago.")
                ->warning()
                ->send();
        }
        
        // Retornar el lote creado (requerido por Filament)
        return $enrollmentBatch;
    }
    
    protected function createEnrollmentClasses($enrollment): void
    {
        // Obtener el número de clases seleccionado
        $numberOfClasses = $enrollment->number_of_classes;
        
        if (!$numberOfClasses) {
            return;
        }
        
        // Obtener las próximas clases del taller
        $workshopClasses = \App\Models\WorkshopClass::where('instructor_workshop_id', $enrollment->instructor_workshop_id)
            ->where('class_date', '>=', now()->format('Y-m-d'))
            ->orderBy('class_date', 'asc')
            ->limit($numberOfClasses)
            ->get();
        
        // Si no hay suficientes clases futuras, obtener las clases más recientes
        if ($workshopClasses->count() < $numberOfClasses) {
            $remainingClasses = $numberOfClasses - $workshopClasses->count();
            $pastClasses = \App\Models\WorkshopClass::where('instructor_workshop_id', $enrollment->instructor_workshop_id)
                ->where('class_date', '<', now()->format('Y-m-d'))
                ->orderBy('class_date', 'desc')
                ->limit($remainingClasses)
                ->get();
            
            $workshopClasses = $workshopClasses->merge($pastClasses)->sortBy('class_date');
        }
        
        // Obtener el precio por clase del taller
        $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($enrollment->instructor_workshop_id);
        $classFee = $instructorWorkshop ? $instructorWorkshop->workshop->standard_monthly_fee : 0;
        
        // Crear los registros en enrollment_classes
        foreach ($workshopClasses as $workshopClass) {
            \App\Models\EnrollmentClass::create([
                'student_enrollment_id' => $enrollment->id,
                'workshop_class_id' => $workshopClass->id,
                'class_fee' => $classFee,
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\EnrollmentBatchResource::getUrl('index');
    }
}