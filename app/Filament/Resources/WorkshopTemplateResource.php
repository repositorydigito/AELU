<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopTemplateResource\Pages;
use App\Models\WorkshopTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopTemplateResource extends Resource
{
    protected static ?string $model = WorkshopTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Plantillas de Talleres';

    protected static ?string $pluralModelLabel = 'Plantillas de Talleres';

    protected static ?string $modelLabel = 'Plantilla de Taller';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Plantilla')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del taller')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('day_of_week')
                            ->label('Día del taller')
                            ->multiple()
                            ->options([
                                'Lunes' => 'Lunes',
                                'Martes' => 'Martes',
                                'Miércoles' => 'Miércoles',
                                'Jueves' => 'Jueves',
                                'Viernes' => 'Viernes',
                                'Sábado' => 'Sábado',
                                'Domingo' => 'Domingo',
                            ])
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de inicio')
                            ->withoutSeconds()
                            ->required(),
                        Forms\Components\TextInput::make('duration')
                            ->label('Duración de la clase')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('minutos')
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Número de cupos (Aforo)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('number_of_classes')
                            ->label('Número de clases')
                            ->numeric()
                            ->minValue(1)
                            ->default(4)
                            ->required(),
                        Forms\Components\TextInput::make('standard_monthly_fee')
                            ->label('Tarifa mensual por defecto')
                            ->prefix('S/.')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\TextInput::make('pricing_surcharge_percentage')
                            ->label('Porcentaje de recargo')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(20)
                            ->required(),
                        Forms\Components\Select::make('modality')
                            ->label('Modalidad')
                            ->options([
                                'Presencial' => 'Presencial',
                                'Virtual' => 'Virtual',
                            ])
                            ->nullable(),
                        Forms\Components\TextInput::make('place')
                            ->label('Localización')
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('additional_comments')
                            ->label('Comentarios adicionales')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Días')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('modality')
                    ->label('Modalidad'),
                Tables\Columns\TextColumn::make('standard_monthly_fee')
                    ->label('Tarifa Mensual')
                    ->prefix('S/. ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_classes')
                    ->label('Clases')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (WorkshopTemplate $record, Tables\Actions\DeleteAction $action) {
                        if ($record->workshops()->exists()) {
                            Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Esta plantilla tiene talleres asociados. Desvincula los talleres primero.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshopTemplates::route('/'),
            'create' => Pages\CreateWorkshopTemplate::route('/create'),
            'edit' => Pages\EditWorkshopTemplate::route('/{record}/edit'),
        ];
    }
}
