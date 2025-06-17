<?php

namespace App\Filament\Resources\InstructorWorkshopResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'Enrollments';
    protected static ?string $title = 'Alumnos Inscritos';   

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Alumno'),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Fecha de Inscripción'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pago'),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Pagado')
                    ->money('PEN'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                
            ])
            ->actions([
                
            ])
            ->bulkActions([
                                
            ]);
    }
}
