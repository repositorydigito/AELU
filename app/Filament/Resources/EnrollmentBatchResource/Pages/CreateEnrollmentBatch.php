<?php

namespace App\Filament\Resources\EnrollmentBatchResource\Pages;

use App\Filament\Resources\EnrollmentBatchResource;
use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrollmentBatch extends CreateRecord
{
    protected static string $resource = EnrollmentBatchResource::class;
    
    public function mount(): void
    {
        // Redirigir al wizard de creación de inscripciones
        $this->redirect(EnrollmentResource::getUrl('create'));
    }
}
