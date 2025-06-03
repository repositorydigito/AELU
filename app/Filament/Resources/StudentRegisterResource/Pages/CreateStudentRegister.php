<?php

namespace App\Filament\Resources\StudentRegisterResource\Pages;

use App\Filament\Resources\StudentRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StudentResource;

class CreateStudentRegister extends CreateRecord
{
    protected static string $resource = StudentRegisterResource::class;

    protected function getRedirectUrl(): string
    {
        // Opciones de redirección:

        // Opción 1: Redirigir a la página de listado del StudentResource
        // Necesitas el nombre de la ruta. Por lo general, es "resource.index"
        // donde "resource" es el kebab-case del nombre de tu Resource.
        // Si tu StudentResource tiene un nombre como "student-resource", la ruta sería "student-resource.index"
        // Si es solo "StudentResource", la ruta sería "students.index"
        // Asumiendo que tu StudentResource genera una ruta 'students.index':
        return StudentResource::getUrl('index');


        // Opción 2 (menos común pero útil si no tienes el otro Resource):
        // Redirigir a la página de listado del mismo StudentRegisterResource
        // return $this->getResource()::getUrl('index');

        // Opción 3 (si quieres un mensaje de éxito diferente):
        // Puedes encadenar una notificación antes de redirigir
        // Notification::make()
        //     ->success()
        //     ->title('Estudiante registrado con éxito')
        //     ->send();
        // return StudentResource::getUrl('index');

    }
}
