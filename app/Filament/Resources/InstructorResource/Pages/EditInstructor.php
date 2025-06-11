<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use App\Models\MedicalRecord;
use App\Models\Affidavit;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstructor extends EditRecord
{
    protected static string $resource = InstructorResource::class;

    protected function afterSave(): void
    {
        $instructor = $this->record;
        $data = $this->form->getState();

        // Actualizar o crear registro médico
        if (isset($data['medicalRecord'])) {
            $medicalData = $data['medicalRecord'];
            $medicalData['instructor_id'] = $instructor->id;
            $medicalData['student_id'] = null;

            $medicalRecord = $instructor->medicalRecord;
            if ($medicalRecord) {
                $medicalRecord->update($medicalData);
            } else {
                $medicalRecord = MedicalRecord::create($medicalData);
            }

            // Actualizar medicamentos
            if (isset($medicalData['medications'])) {
                $medicalRecord->medications()->delete();
                foreach ($medicalData['medications'] as $medication) {
                    $medicalRecord->medications()->create($medication);
                }
            }
        }

        // Actualizar o crear declaración jurada
        if (isset($data['affidavit'])) {
            $affidavitData = $data['affidavit'];
            $affidavitData['instructor_id'] = $instructor->id;
            $affidavitData['student_id'] = null;

            $affidavit = $instructor->affidavit;
            if ($affidavit) {
                $affidavit->update($affidavitData);
            } else {
                Affidavit::create($affidavitData);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getRedirectUrl(): string
    {
        return InstructorResource::getUrl('index');
    }
}
