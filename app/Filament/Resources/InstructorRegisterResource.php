<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorRegisterResource\Pages;
use App\Filament\Resources\InstructorRegisterResource\RelationManagers;
use App\Models\Instructor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstructorRegisterResource extends Resource
{
    protected static ?string $model = Instructor::class;    
    protected static ?string $navigationLabel = 'Registrar Profesores';
    // protected static ?string $pluralModelLabel = 'Profesores';
    // protected static ?string $modelLabel = 'Profesor';
    protected static ?int $navigationSort = 5; 

    protected static ?string $navigationGroup = 'Profesores';    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListInstructorRegisters::route('/'),
            'create' => Pages\CreateInstructorRegister::route('/create'),
            'edit' => Pages\EditInstructorRegister::route('/{record}/edit'),
        ];
    }
}
