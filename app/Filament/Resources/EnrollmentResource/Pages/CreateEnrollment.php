<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

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

        if (! $monthlyPeriod) {
            Notification::make()
                ->title('Error')
                ->body('El período mensual seleccionado no es válido.')
                ->danger()
                ->send();

            throw new \Exception('Período mensual no válido');
        }

        // 🔥 OBTENER INFORMACIÓN DEL ESTUDIANTE PARA VERIFICAR SI ES PRE-PAMA
        $student = \App\Models\Student::find($data['student_id']);
        $isPrepama = $student && in_array($student->category_partner, ['PRE PAMA 50+', 'PRE PAMA 55+']);

        // 🔥 VALIDACIÓN PREVIA DE CUPOS PARA TODOS LOS TALLERES SELECCIONADOS
        $capacityErrors = [];
        foreach ($selectedWorkshops as $workshopId) {
            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($workshopId);
            if ($instructorWorkshop && $instructorWorkshop->isFullForPeriod($selectedMonthlyPeriodId)) {
                $capacityErrors[] = $instructorWorkshop->workshop->name;
            }
        }

        if (! empty($capacityErrors)) {
            $workshopNames = implode(', ', $capacityErrors);
            Notification::make()
                ->title('Cupos agotados')
                ->body("Los siguientes talleres no tienen cupos disponibles: {$workshopNames}")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Determinar el estado de pago final
        $finalPaymentStatus = $paymentMethod === 'cash' ? 'pending' : 'pending';

        // Calcular el total de todas las inscripciones
        $totalAmount = 0;
        $validWorkshopDetails = [];
        $skippedWorkshops = [];

        // Asegurar que todos los workshops existan para el período seleccionado
        $workshopIdMapping = [];
        foreach ($selectedWorkshops as $workshopId) {
            $newWorkshopId = $this->ensureWorkshopExistsForPeriod($workshopId, $selectedMonthlyPeriodId);
            $workshopIdMapping[$workshopId] = $newWorkshopId;
        }

        // Actualizar las referencias en workshopDetails
        foreach ($workshopDetails as $index => $detail) {
            $originalWorkshopId = $detail['instructor_workshop_id'];
            if (isset($workshopIdMapping[$originalWorkshopId])) {
                $workshopDetails[$index]['instructor_workshop_id'] = $workshopIdMapping[$originalWorkshopId];
            }
        }

        // Actualizar selectedWorkshops con los nuevos IDs
        $selectedWorkshops = array_values($workshopIdMapping);

        // 🔥 VALIDACIÓN TEMPRANA DE DUPLICADOS - DETENER TODO SI HAY PROBLEMAS
        $duplicateErrors = [];
        foreach ($workshopDetails as $detail) {
            if (!isset($detail['instructor_workshop_id']) || !in_array($detail['instructor_workshop_id'], $selectedWorkshops)) {
                continue;
            }

            // Verificar duplicados activos
            $existingActiveEnrollment = \App\Models\StudentEnrollment::where('student_id', $data['student_id'])
                ->where('instructor_workshop_id', $detail['instructor_workshop_id'])
                ->where('monthly_period_id', $selectedMonthlyPeriodId)
                ->whereNotIn('payment_status', ['refunded'])
                ->first();

            if ($existingActiveEnrollment) {
                $instructorWorkshop = \App\Models\InstructorWorkshop::with(['workshop', 'instructor'])
                    ->find($detail['instructor_workshop_id']);

                if ($instructorWorkshop) {
                    $workshopName = $instructorWorkshop->workshop->name;
                    $statusText = match($existingActiveEnrollment->payment_status) {
                        'pending' => 'en proceso',
                        'completed' => 'inscrito',
                        'to_pay' => 'por pagar',
                        'credit_favor' => 'con crédito a favor',
                        default => $existingActiveEnrollment->payment_status
                    };

                    $duplicateErrors[] = "'{$workshopName}' (ya {$statusText})";
                }
            }
        }

        // Si hay duplicados, detener completamente el proceso
        if (!empty($duplicateErrors)) {
            $monthName = \Carbon\Carbon::create($monthlyPeriod->year, $monthlyPeriod->month, 1)->translatedFormat('F Y');
            $duplicateList = implode(', ', $duplicateErrors);

            Notification::make()
                ->title('Inscripciones duplicadas detectadas')
                ->body("El estudiante ya tiene inscripciones activas para {$monthName} en: {$duplicateList}. No se puede proceder hasta resolver estos duplicados.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // CONTINUAR con el foreach original (pero SIN la validación de duplicados)
        foreach ($workshopDetails as $detail) {
            if (! isset($detail['instructor_workshop_id']) || ! in_array($detail['instructor_workshop_id'], $selectedWorkshops)) {
                $skippedWorkshops[] = 'Taller no válido o no seleccionado';
                continue;
            }

            $instructorWorkshop = \App\Models\InstructorWorkshop::with('workshop')->find($detail['instructor_workshop_id']);
            if (! $instructorWorkshop) {
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

        if (empty($validWorkshopDetails)) {
            $skippedCount = count($skippedWorkshops);

            if ($skippedCount > 0) {
                $reasons = array_unique($skippedWorkshops);
                $reasonsText = implode(', ', array_slice($reasons, 0, 3));
                if (count($reasons) > 3) {
                    $reasonsText .= '...';
                }

                Notification::make()
                    ->title('No se pudieron procesar las inscripciones')
                    ->body("Se omitieron {$skippedCount} talleres: {$reasonsText}")
                    ->warning()
                    ->persistent()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error en la inscripción')
                    ->body('No se pudo crear ninguna inscripción válida.')
                    ->danger()
                    ->persistent()
                    ->send();
            }

            // Usar halt() en lugar de throw Exception
            $this->halt();
        }

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
            $this->createEnrollmentClasses($enrollment, $detail);
        }

        // Mostrar notificación de éxito (con información PRE-PAMA si aplica)
        $count = count($createdEnrollments);
        $student = \App\Models\Student::find($data['student_id']);

        if ($paymentMethod === 'cash') {
            // Pago en efectivo - Estado: Inscrito - Generar PDF
            Notification::make()
                ->title('¡Inscripciones en proceso!')
                ->body("Se creó un lote con {$count} inscripción".($count > 1 ? 'es' : '').' correctamente. Estado: En Proceso.')
                ->success()
                ->persistent()
                ->send();
        } else {
            // Pago con link - Estado: En Proceso
            Notification::make()
                ->title('¡Inscripciones en proceso!')
                ->body("Se creó un lote con {$count} inscripción".($count > 1 ? 'es' : '').' correctamente. Estado: En Proceso.')
                ->warning()
                ->send();
        }

        // Retornar el lote creado (requerido por Filament)
        return $enrollmentBatch;
    }

    protected function createEnrollmentClasses($enrollment, $workshopDetail): void
    {
        // Obtener las clases específicas seleccionadas por el usuario
        $selectedClassIds = $workshopDetail['selected_classes'] ?? [];

        if (empty($selectedClassIds)) {
            // Si no hay clases específicas seleccionadas, usar el comportamiento anterior
            $this->createEnrollmentClassesLegacy($enrollment);

            return;
        }

        // Calcular precio por clase
        $pricePerClass = $enrollment->total_amount / $enrollment->number_of_classes;

        // Crear los registros en enrollment_classes para las clases específicas seleccionadas
        foreach ($selectedClassIds as $classId) {
            $workshopClass = \App\Models\WorkshopClass::find($classId);

            if ($workshopClass) {
                \App\Models\EnrollmentClass::create([
                    'student_enrollment_id' => $enrollment->id,
                    'workshop_class_id' => $workshopClass->id,
                    'class_fee' => $pricePerClass,
                    'attendance_status' => 'enrolled',
                ]);
            }
        }
    }

    protected function createEnrollmentClassesLegacy($enrollment): void
    {
        // Método anterior para compatibilidad hacia atrás
        $numberOfClasses = $enrollment->number_of_classes;

        if (! $numberOfClasses) {
            return;
        }

        // Obtener el workshop a través del instructor_workshop
        $instructorWorkshop = $enrollment->instructorWorkshop;
        $workshop = $instructorWorkshop->workshop;

        // Buscar las clases disponibles del workshop para el período de la inscripción
        $workshopClasses = \App\Models\WorkshopClass::where('workshop_id', $workshop->id)
            ->where('monthly_period_id', $enrollment->monthly_period_id)
            ->where('class_date', '>=', $enrollment->enrollment_date)
            ->orderBy('class_date', 'asc')
            ->limit($numberOfClasses)
            ->get();

        // Si no hay suficientes clases futuras, completar con clases más recientes
        if ($workshopClasses->count() < $numberOfClasses) {
            $remainingClasses = $numberOfClasses - $workshopClasses->count();

            $pastClasses = \App\Models\WorkshopClass::where('workshop_id', $workshop->id)
                ->where('monthly_period_id', $enrollment->monthly_period_id)
                ->where('class_date', '<', $enrollment->enrollment_date)
                ->orderBy('class_date', 'desc')
                ->limit($remainingClasses)
                ->get();

            $workshopClasses = $workshopClasses->merge($pastClasses)->sortBy('class_date');
        }

        // Calcular precio por clase
        $pricePerClass = $enrollment->total_amount / $numberOfClasses;

        // Crear los registros en enrollment_classes
        foreach ($workshopClasses as $workshopClass) {
            \App\Models\EnrollmentClass::create([
                'student_enrollment_id' => $enrollment->id,
                'workshop_class_id' => $workshopClass->id,
                'class_fee' => $pricePerClass,
                'attendance_status' => 'enrolled',
            ]);
        }
    }

    private function ensureWorkshopExistsForPeriod($originalWorkshopId, $monthlyPeriodId)
    {
        $workshopService = new \App\Services\WorkshopAutoCreationService;
        $instructorWorkshop = $workshopService->findOrCreateInstructorWorkshopForPeriod($originalWorkshopId, $monthlyPeriodId);

        if (! $instructorWorkshop) {
            throw new \Exception("No se pudo crear el taller para el período: {$monthlyPeriodId}");
        }

        return $instructorWorkshop->id;
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\EnrollmentBatchResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(EnrollmentResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
