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

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Talleres'; 
    protected static ?string $pluralModelLabel = 'Talleres'; 
    protected static ?string $modelLabel = 'Taller';    
    protected static ?int $navigationSort = 7; 
    protected static ?string $navigationGroup = 'Talleres';       

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Taller')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->nullable()
                    ->columnSpanFull(), 
                Forms\Components\TextInput::make('standard_monthly_fee')
                    ->label('Tarifa Mensual Estándar (4 clases)')
                    ->numeric()
                    ->prefix('S/.') 
                    ->step(0.01)
                    ->required(),
                Forms\Components\TextInput::make('hourly_rate')
                    ->label('Honorario por Hora (si aplica)')
                    ->numeric()
                    ->prefix('S/.') 
                    ->step(0.01)
                    ->nullable(),
                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Duración de Clase (minutos)')
                    ->numeric()
                    ->minValue(1) 
                    ->default(60) 
                    ->required(),
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
                Tables\Columns\TextColumn::make('standard_monthly_fee')
                    ->label('Tarifa Mensual')
                    ->money('PEN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Honorario p/h')
                    ->money('PEN') 
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duración (min)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }

    public static function getBadgeCount(): int
    {
        return Workshop::count();
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getBadgeCount();
    }
}
