<?php

namespace App\Filament\Resources\StudentRegisterResource\Pages;

use App\Filament\Resources\StudentRegisterResource;
use App\Imports\StudentsImport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ImportStudents extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = StudentRegisterResource::class;

    protected static string $view = 'filament.resources.student-register-resource.pages.import-students';

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
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', '.xlsx', '.xls'])
                    ->maxSize(5120)
                    ->required()
                    ->disk('public')
                    ->directory('temp'),
            ])
            ->statePath('data');
    }

    public function import()
    {
        $this->validate();

        try {
            if (! isset($this->data['excel_file'])) {
                throw new \Exception('No se ha seleccionado ningún archivo');
            }

            $filePath = $this->data['excel_file'];
            if (is_array($filePath)) {
                $filePath = reset($filePath);
            }

            Excel::import(
                new StudentsImport,
                $filePath,
                'public'
            );

            Notification::make()
                ->title('Archivo procesado correctamente')
                ->success()
                ->send();

            return redirect()->to(StudentRegisterResource::getUrl());

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
