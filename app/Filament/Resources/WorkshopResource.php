<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
use App\Filament\Resources\WorkshopResource\RelationManagers;
use App\Models\Workshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;
    protected static ?string $navigationLabel = 'Talleres';
    protected static ?string $pluralModelLabel = 'Talleres';
    protected static ?string $modelLabel = 'Taller';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Talleres';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Taller')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Taller')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull()
                            ->rows(3),
                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Duración (horas)')
                            ->numeric()
                            ->suffix('horas'),
                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->numeric()
                            ->prefix('S/.'),
                        Forms\Components\TextInput::make('max_students')
                            ->label('Máximo de Estudiantes')
                            ->numeric(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'completed' => 'Completado',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->label('Taller')
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->searchable()
                            ->sortable(),
                        Tables\Columns\TextColumn::make('description')
                            ->label('Descripción')
                            ->limit(50)
                            ->size('sm')
                            ->color('gray'),
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('duration_hours')
                                ->label('Duración')
                                ->suffix(' hrs')
                                ->icon('heroicon-o-clock'),
                            Tables\Columns\TextColumn::make('price')
                                ->label('Precio')
                                ->money('PEN')
                                ->color('success'),
                            Tables\Columns\TextColumn::make('max_students')
                                ->label('Máx. estudiantes')
                                ->icon('heroicon-o-users'),
                        ]),
                        Tables\Columns\TextColumn::make('status')
                            ->label('Estado')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'completed' => 'Completado',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'completed' => 'info',
                                default => 'gray',
                            }),
                        Tables\Columns\TextColumn::make('instructorWorkshops_count')
                            ->label('Horarios')
                            ->counts('instructorWorkshops')
                            ->badge()
                            ->color('info'),
                    ]),
                ])->space(0),
            ])
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'completed' => 'Completado',
                    ])
                    ->label('Filtrar por Estado'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InstructorWorkshopsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'view' => Pages\ViewWorkshop::route('/{record}'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }
}
