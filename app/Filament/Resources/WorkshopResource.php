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
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Talleres'; 
    protected static ?string $pluralModelLabel = 'Talleres'; 
    protected static ?string $modelLabel = 'Taller';    
    protected static ?int $navigationSort = 6; 
    protected static ?string $navigationGroup = 'Talleres';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Taller')
                    ->description('Define el nombre de un nuevo tipo de taller.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Taller')
                            ->required()
                            ->unique(ignoreRecord: true) // Asegura que el nombre sea único al crear y al editar
                            ->maxLength(255)
                            ->placeholder('Ej: Yoga, Pilates, Baile, Zumba'),
                    ])
                    ->columns(1), // Una columna para esta sección
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre del Taller')
                    ->searchable() // Permite buscar por nombre de taller
                    ->sortable(), // Permite ordenar por nombre de taller
                TextColumn::make('instructors_count') // Columna para mostrar cuántos instructores dictan este taller
                    ->counts('instructorWorkshops') // Usa el método counts() para contar la relación
                    ->label('N° de Profesores')
                    ->sortable(), // Permite ordenar por número de instructores
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('¿Estás seguro de que quieres eliminar este taller? Si lo eliminas, se desconectará de cualquier profesor que lo imparta.')
                    ->requiresConfirmation(), // Pide confirmación antes de eliminar
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
}
