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

        // 🔥 VALIDAR QUE SE HAYA SELECCIONADO UN PERÍODO (CAMPO GLOBAL)
        if (empty($data['selected_monthly_period_id'])) {
            Notification::make()
                ->title('Error')
                ->body('Debes seleccionar un período mensual.')
                ->danger()
                ->send();

            throw new \Exception('No se seleccionó período mensual');
        }

        if (empty($selectedWorkshops)) {
            Notification::make()
                ->title('Error')
                ->body('Debes seleccionar al menos un taller.')
                ->danger()
                ->send();

            throw new \Exception('No se seleccionaron talleres');
        }

        // 🔥 OBTENER EL PERÍODO SELECCIONADO (CAMPO GLOBAL)
        $selectedMonthlyPeriodId = $data['selected_monthly_period_id'];
        $monthlyPeriod = \App\Models\MonthlyPeriod::find($selectedMonthlyPeriodId);

        if (!$monthlyPeriod) {
            Notification::make()
                ->title('Error')
                ->body('El período mensual seleccionado no es válido.')
                ->danger()
                ->send();

            throw new \Exception('Período mensual no válido');
        }

        // 🔥 OBTENER INFORMACIÓN DEL ESTUDIANTE PARA VERIFICAR SI ES PRE-PAMA
        $student = \App\Models\Student::find($data['student_id']);
        $isPrepama = $student && $student->category_partner === 'Individual PRE-PAMA';

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

            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($detail['instructor_workshop_id']);
            if (!$instructorWorkshop) {
                continue;
            }

            // Contar estudiantes ya inscritos en este taller para el período seleccionado
            $currentEnrollments = \App\Models\StudentEnrollment::where('instructor_workshop_id', $detail['instructor_workshop_id'])
                ->where('monthly_period_id', $selectedMonthlyPeriodId)
                ->where('payment_status', 'completed')
                ->distinct('student_id')
                ->count('student_id');

            $capacity = $instructorWorkshop->workshop->capacity ?? 0;
            $availableSpots = $capacity - $currentEnrollments;

            // Si no hay cupos disponibles, mostrar error y saltar este taller
            if ($availableSpots <= 0) {
                $monthName = \Carbon\Carbon::create($monthlyPeriod->year, $monthlyPeriod->month, 1)->translatedFormat('F Y');

                Notification::make()
                    ->title('Cupos agotados')
                    ->body("El taller '{$instructorWorkshop->workshop->name}' ya no tiene cupos disponibles para {$monthName}. Cupos: {$currentEnrollments}/{$capacity}")
                    ->danger()
                    ->send();

                $skippedWorkshops[] = "Sin cupos: {$instructorWorkshop->workshop->name} - {$monthName}";
                continue; // Saltar este taller
            }

            // 🔥 VALIDACIÓN DE DUPLICADOS USANDO EL PERÍODO SELECCIONADO
            $existingEnrollment = \App\Models\StudentEnrollment::where('student_id', $data['student_id'])
                ->where('instructor_workshop_id', $detail['instructor_workshop_id'])
                ->where('monthly_period_id', $selectedMonthlyPeriodId) // Usar el período seleccionado
                ->where('payment_status', 'completed')
                ->first();

            if ($existingEnrollment) {
                $instructorWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                    ->find($detail['instructor_workshop_id']);

                $monthName = \Carbon\Carbon::create($monthlyPeriod->year, $monthlyPeriod->month, 1)->translatedFormat('F Y');

                if ($instructorWorkshop) {
                    $workshopName = $instructorWorkshop->workshop->name;
                    $instructorName = $instructorWorkshop->instructor->first_names . ' ' . $instructorWorkshop->instructor->last_names;

                    Notification::make()
                        ->title('Taller ya inscrito')
                        ->body("El estudiante ya está inscrito en '{$workshopName}' con {$instructorName} para {$monthName}. Este taller se omitirá de la inscripción.")
                        ->warning()
                        ->send();

                    $skippedWorkshops[] = "Duplicado: {$workshopName} - {$monthName}";
                    continue; // Saltar este taller pero continuar con los demás
                }
            }

            // 🔥 CÁLCULO DE PRECIO CON LÓGICA PRE-PAMA
            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($detail['instructor_workshop_id']);
            $numberOfClasses = $detail['number_of_classes'];

            // Buscar el precio en la tabla workshop_pricings
            $pricing = \App\Models\WorkshopPricing::where('workshop_id', $instructorWorkshop->workshop->id)
                ->where('number_of_classes', $numberOfClasses)
                ->where('for_volunteer_workshop', false) // Asumiendo que no es voluntario por defecto
                ->first();

            // Si no existe el pricing, calcular basado en el precio estándar
            $baseWorkshopTotal = $pricing ? $pricing->price : ($instructorWorkshop->workshop->standard_monthly_fee * $numberOfClasses / 4);

            // APLICAR MULTIPLICADOR SEGÚN CATEGORÍA
            $workshopTotal = $baseWorkshopTotal * $student->inscription_multiplier;

            $totalAmount += $workshopTotal;

            $detail['calculated_total'] = $workshopTotal;
            $detail['price_per_class'] = $workshopTotal / $numberOfClasses;
            $detail['monthly_period_id'] = $selectedMonthlyPeriodId; 
            $validWorkshopDetails[] = $detail;
        }

        /* if (empty($validWorkshopDetails)) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo crear ninguna inscripción.')
                ->danger()
                ->send();

            throw new \Exception('No se crearon inscripciones');
        } */

        // Crear el lote de inscripciones
        $enrollmentBatch = \App\Models\EnrollmentBatch::create([
            'student_id' => $data['student_id'],
            'total_amount' => $totalAmount,
            'payment_status' => $finalPaymentStatus,
            'payment_method' => $paymentMethod,
            'enrollment_date' => $validWorkshopDetails[0]['enrollment_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        $createdEnrollments = [];

        // Crear las inscripciones individuales asociadas al lote
        foreach ($validWorkshopDetails as $index => $detail) {
            $enrollment = StudentEnrollment::create([
                'student_id' => $data['student_id'],
                'instructor_workshop_id' => $detail['instructor_workshop_id'],
                'enrollment_batch_id' => $enrollmentBatch->id,
                'monthly_period_id' => $detail['monthly_period_id'], // 🔥 USAR EL PERÍODO SELECCIONADO
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

        // Mostrar notificación de éxito (con información PRE-PAMA si aplica)
        $count = count($createdEnrollments);
        $student = \App\Models\Student::find($data['student_id']);
        $prepamaMessage = $isPrepama ? " (Estudiante PRE-PAMA: 50% adicional aplicado)" : "";

        if ($paymentMethod === 'cash') {
            // Pago en efectivo - Estado: Inscrito - Generar PDF
            Notification::make()
                ->title('¡Inscripciones completadas!')
                ->body("Se creó un lote con {$count} inscripción" . ($count > 1 ? 'es' : '') . " correctamente{$prepamaMessage}. Estado: Inscrito. Se generará el ticket PDF.")
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
                ->body("Se creó un lote con {$count} inscripción" . ($count > 1 ? 'es' : '') . " correctamente{$prepamaMessage}. Estado: En Proceso.")
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
