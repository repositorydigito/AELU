<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions;
use Illuminate\Testing\Fluent\Concerns\Has;

class SystemSettings extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Configuraciones';    
    protected static ?string $title = 'Configuraciones del Sistema';
    protected static string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'auto_cancel_enabled' => SystemSetting::get('auto_cancel_enabled', false),
            'auto_cancel_day' => SystemSetting::get('auto_cancel_day', 28),
            'auto_cancel_time' => SystemSetting::get('auto_cancel_time', '23:59:59'),
            'auto_generate_enabled' => SystemSetting::get('auto_generate_enabled', false),
            'auto_generate_day' => SystemSetting::get('auto_generate_day', 20),
            'auto_generate_time' => SystemSetting::get('auto_generate_time', '23:59:59'),
            'auto_replicate_enrollments_enabled' => SystemSetting::get('auto_replicate_enrollments_enabled', false),
            'auto_replicate_enrollments_day' => SystemSetting::get('auto_replicate_enrollments_day', 25),
            'auto_replicate_enrollments_time' => SystemSetting::get('auto_replicate_enrollments_time', '23:59:59'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Anulación Automática de Inscripciones')
                    ->description('Configuración para anular automáticamente inscripciones pendientes')
                    ->schema([
                        Forms\Components\Toggle::make('auto_cancel_enabled')
                            ->label('Habilitar Anulación Automática')
                            ->helperText('Activar o desactivar la anulación automática de inscripciones pendientes')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('auto_cancel_day')
                                    ->label('Día del Mes')
                                    ->helperText('Día del mes para ejecutar la anulación (1-31)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(28)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_cancel_enabled')),

                                Forms\Components\TimePicker::make('auto_cancel_time')
                                    ->label('Hora de Ejecución')
                                    ->helperText('Hora del día para ejecutar la anulación')
                                    ->default('23:59:59')
                                    ->seconds(false)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_cancel_enabled')),
                            ]),

                        Forms\Components\Placeholder::make('auto_cancel_info')
                            ->label('Información')
                            ->content(function (Forms\Get $get) {
                                if (!$get('auto_cancel_enabled')) {
                                    return 'La anulación automática está deshabilitada.';
                                }

                                $day = $get('auto_cancel_day') ?? 28;
                                $time = $get('auto_cancel_time') ?? '23:59:59';
                                
                                return "Las inscripciones pendientes se anularán automáticamente cada día {$day} del mes a las {$time}.";
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Replicación Automática de Talleres')
                    ->description('Configuración para duplicar talleres del mes actual al siguiente mes')
                    ->schema([
                        Forms\Components\Toggle::make('auto_generate_enabled')
                            ->label('Habilitar Replicación de Talleres')
                            ->helperText('Activar o desactivar la replicación automática de talleres')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('auto_generate_day')
                                    ->label('Día del Mes')
                                    ->helperText('Día del mes para ejecutar la replicación (1-31)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(20)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_generate_enabled')),

                                Forms\Components\TimePicker::make('auto_generate_time')
                                    ->label('Hora de Ejecución')
                                    ->helperText('Hora del día para ejecutar la replicación')
                                    ->default('23:59:59')
                                    ->seconds(false)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_generate_enabled')),
                            ]),

                        Forms\Components\Placeholder::make('auto_generate_info')
                            ->label('Información')
                            ->content(function (Forms\Get $get) {
                                if (!$get('auto_generate_enabled')) {
                                    return 'La replicación automática de talleres está deshabilitada.';
                                }

                                $day = $get('auto_generate_day') ?? 20;
                                $time = $get('auto_generate_time') ?? '23:59:59';

                                return "Los talleres del mes actual se replicarán automáticamente al siguiente mes cada día {$day} a las {$time}. Esto incluye la generación de clases según los días y horarios configurados.";
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Replicación Automática de Inscripciones')
                    ->description('Configuración para duplicar inscripciones completadas al mes siguiente')
                    ->schema([
                        Forms\Components\Toggle::make('auto_replicate_enrollments_enabled')
                            ->label('Habilitar Replicación de Inscripciones')
                            ->helperText('Activar o desactivar la replicación automática de inscripciones completadas')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('auto_replicate_enrollments_day')
                                    ->label('Día del Mes')
                                    ->helperText('Día del mes para ejecutar la replicación (1-31)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(25)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_replicate_enrollments_enabled')),

                                Forms\Components\TimePicker::make('auto_replicate_enrollments_time')
                                    ->label('Hora de Ejecución')
                                    ->helperText('Hora del día para ejecutar la replicación')
                                    ->default('23:59:59')
                                    ->seconds(false)
                                    ->required()
                                    ->disabled(fn (Forms\Get $get) => !$get('auto_replicate_enrollments_enabled')),
                            ]),

                        Forms\Components\Placeholder::make('auto_replicate_enrollments_info')
                            ->label('Información')
                            ->content(function (Forms\Get $get) {
                                if (!$get('auto_replicate_enrollments_enabled')) {
                                    return 'La replicación automática de inscripciones está deshabilitada.';
                                }

                                $day = $get('auto_replicate_enrollments_day') ?? 25;
                                $time = $get('auto_replicate_enrollments_time') ?? '23:59:59';

                                return "Las inscripciones completadas del mes actual se replicarán automáticamente al siguiente mes cada día {$day} a las {$time}. Solo se replican estudiantes con mantenimiento vigente y talleres con cupo disponible.";
                            })
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('auto_replicate_enrollments_warning')
                            ->label('⚠️ Importante')
                            ->content('La replicación de inscripciones debe ejecutarse DESPUÉS de la replicación de talleres. Recomendación: configurar replicación de talleres al día 20 e inscripciones al día 25.')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('auto_replicate_enrollments_enabled')),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Comandos de Prueba')
                    ->description('Herramientas para probar la funcionalidad manualmente')
                    ->schema([
                        Forms\Components\Placeholder::make('test_commands_info')
                            ->label('Comandos Disponibles')
                            ->content('
                                • Anular inscripciones: php artisan enrollments:auto-cancel
                                • Replicar talleres: php artisan workshops:auto-replicate --force
                                • Replicar inscripciones: php artisan enrollments:auto-generate --force

                                El flag --force permite ejecutar los comandos sin esperar el día/hora configurado.
                            ')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar Configuraciones')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            // Guardar configuraciones de anulación
            SystemSetting::set('auto_cancel_enabled', $data['auto_cancel_enabled'], 'boolean');
            if ($data['auto_cancel_enabled']) {
                SystemSetting::set('auto_cancel_day', $data['auto_cancel_day'] ?? 28, 'integer');
                SystemSetting::set('auto_cancel_time', $data['auto_cancel_time'] ?? '23:59:59', 'string');
            }

            // Guardar configuraciones de replicación de talleres
            SystemSetting::set('auto_generate_enabled', $data['auto_generate_enabled'], 'boolean');
            if ($data['auto_generate_enabled']) {
                SystemSetting::set('auto_generate_day', $data['auto_generate_day'] ?? 20, 'integer');
                SystemSetting::set('auto_generate_time', $data['auto_generate_time'] ?? '23:59:59', 'string');
            }

            // Guardar configuraciones de replicación de inscripciones
            SystemSetting::set('auto_replicate_enrollments_enabled', $data['auto_replicate_enrollments_enabled'], 'boolean');
            if ($data['auto_replicate_enrollments_enabled']) {
                SystemSetting::set('auto_replicate_enrollments_day', $data['auto_replicate_enrollments_day'] ?? 25, 'integer');
                SystemSetting::set('auto_replicate_enrollments_time', $data['auto_replicate_enrollments_time'] ?? '23:59:59', 'string');
            }

            Notification::make()
                ->title('Configuraciones Guardadas')
                ->body('Las configuraciones se han guardado correctamente.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Guardar')
                ->body('Hubo un problema al guardar las configuraciones: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
