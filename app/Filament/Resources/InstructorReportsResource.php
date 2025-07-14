<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorReportsResource\Pages;
use App\Models\Instructor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class InstructorReportsResource extends Resource
{
    protected static ?string $model = Instructor::class;
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $pluralModelLabel = 'Reportes';
    protected static ?string $modelLabel = 'Reporte';
    //protected static ?int $navigationSort = 4;
    //protected static ?string $navigationGroup = 'Profesores';
    protected static bool $shouldRegisterNavigation = false;    

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorReports::route('/'),
        ];
    }

    // Deshabilitar las rutas que no necesitamos
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
    public static function getNavigationVisibility(): bool
    {
        // Aquí puedes controlar la visibilidad. Retorna `false` para ocultar el recurso
        return false;
    }
}
