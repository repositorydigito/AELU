<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InstructorsImport;

class ImportInstructors extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InstructorResource::class;

    protected static string $view = 'filament.resources.instructor-resource.pages.import-instructors';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('excel_file')
                    ->label('Archivo Excel')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(5120)
                    ->required()
                    ->disk('public')
                    ->directory('temp')
            ])
            ->statePath('data');
    }

    public function import()
    {
        $this->validate();

        try {
            if (!isset($this->data['excel_file'])) {
                throw new \Exception('No se ha seleccionado ningún archivo');
            }

            $filePath = $this->data['excel_file'];
            
            if (!$filePath) {
                throw new \Exception('No se pudo obtener la ruta del archivo');
            }

            // Si es un array, tomamos el primer elemento
            if (is_array($filePath)) {
                $filePath = reset($filePath);
            }

            // Importamos el archivo Excel usando el Storage facade
            Excel::import(
                new InstructorsImport, 
                $filePath,
                'public'
            );
            
            Notification::make()
                ->title('Archivo procesado correctamente')
                ->success()
                ->send();

            return redirect()->to(InstructorResource::getUrl());

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            
            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $error = $failure->errors()[0];
                $errorMessages[] = "Fila {$row}: {$error}";
            }

            Notification::make()
                ->title('Errores de validación')
                ->body(implode("\n", $errorMessages))
                ->danger()
                ->persistent()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al procesar el archivo')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
