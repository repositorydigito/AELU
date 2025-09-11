<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use App\Models\Affidavit;
use App\Models\MedicalRecord;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructor extends CreateRecord
{
    protected static string $resource = InstructorResource::class;

    public function getRedirectUrl(): string
    {
        return InstructorResource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $instructor = $this->record;
        $data = $this->form->getState();

        // Crear registro médico si hay datos
        if (isset($data['medicalRecord']) && ! empty(array_filter($data['medicalRecord']))) {
            $medicalData = $data['medicalRecord'];
            $medicalData['instructor_id'] = $instructor->id;
            $medicalData['student_id'] = null;

            $medicalRecord = MedicalRecord::create($medicalData);

            // Crear medicamentos si existen
            if (isset($medicalData['medications']) && ! empty($medicalData['medications'])) {
                foreach ($medicalData['medications'] as $medication) {
                    $medicalRecord->medications()->create($medication);
                }
            }
        }

        // Crear declaración jurada si hay datos
        if (isset($data['affidavit']) && ! empty(array_filter($data['affidavit']))) {
            $affidavitData = $data['affidavit'];
            $affidavitData['instructor_id'] = $instructor->id;
            $affidavitData['student_id'] = null;

            Affidavit::create($affidavitData);
        }
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->submit('create'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(InstructorResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
