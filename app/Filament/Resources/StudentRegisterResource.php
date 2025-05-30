<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentRegisterResource\Pages;
use App\Filament\Resources\StudentRegisterResource\RelationManagers;
use App\Models\StudentRegister;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentRegisterResource extends Resource
{
    // protected static ?string $model = StudentRegister::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';    
    protected static ?string $navigationLabel = 'Registrar Alumnos';
    // protected static ?string $pluralModelLabel = 'Alumnos';
    // protected static ?string $modelLabel = 'Alumno';
    protected static ?int $navigationSort = 3; 
    protected static ?string $navigationGroup = 'Alumnos'; 


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
            'index' => Pages\ListStudentRegisters::route('/'),
            'create' => Pages\CreateStudentRegister::route('/create'),
            'edit' => Pages\EditStudentRegister::route('/{record}/edit'),
        ];
    }
}
