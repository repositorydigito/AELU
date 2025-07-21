<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Inscripción')
                    ->description('Edita los detalles de esta inscripción específica')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Información del estudiante (solo lectura)
                                Forms\Components\Placeholder::make('student_info')
                                    ->label('Estudiante')
                                    ->content(function ($record) {
                                        if (!$record || !$record->student) {
                                            return 'No disponible';
                                        }
                                        return $record->student->first_names . ' ' . $record->student->last_names;
                                    }),

                                // Información del taller (solo lectura)
                                Forms\Components\Placeholder::make('workshop_info')
                                    ->label('Taller')
                                    ->content(function ($record) {
                                        if (!$record || !$record->instructorWorkshop) {
                                            return 'No disponible';
                                        }
                                        
                                        $workshop = $record->instructorWorkshop;
                                        $dayNames = [
                                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                            7 => 'Domingo', 0 => 'Domingo'
                                        ];
                                        
                                        $dayInSpanish = $dayNames[$workshop->day_of_week] ?? 'Día ' . $workshop->day_of_week;
                                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');
                                        
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='space-y-1'>
                                                <div class='font-semibold'>{$workshop->workshop->name}</div>
                                                <div class='text-sm text-gray-600'>Profesor: {$workshop->instructor->first_names} {$workshop->instructor->last_names}</div>
                                                <div class='text-sm text-gray-600'>Horario: {$dayInSpanish} de {$startTime} a {$endTime}</div>
                                            </div>
                                        ");
                                    }),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Tipo de inscripción (editable)
                                Forms\Components\Radio::make('enrollment_type')
                                    ->label('Tipo de Inscripción')
                                    ->options([
                                        'full_month' => 'Regular',
                                        'specific_classes' => 'Recuperación',
                                    ])
                                    ->required()
                                    ->live(),

                                // Cantidad de clases (editable)
                                Forms\Components\Select::make('number_of_classes')
                                    ->label('Cantidad de Clases')
                                    ->options(function (Forms\Get $get, $record) {
                                        if (!$record || !$record->instructorWorkshop) {
                                            return [];
                                        }
                                        
                                        $maxClasses = $record->instructorWorkshop->workshop->number_of_classes;
                                        $options = [];
                                        
                                        for ($i = 1; $i <= $maxClasses; $i++) {
                                            $options[$i] = $i . ($i === 1 ? ' Clase' : ' Clases');
                                        }
                                        
                                        return $options;
                                    })
                                    ->required()
                                    ->live(),

                                // Fecha de inscripción (editable)
                                Forms\Components\DatePicker::make('enrollment_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->helperText('Fecha de la primera clase'),
                            ]),



                        // Notas adicionales (editable)
                        Forms\Components\Textarea::make('pricing_notes')
                            ->label('Comentarios')
                            ->placeholder('Agregar notas sobre esta inscripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
