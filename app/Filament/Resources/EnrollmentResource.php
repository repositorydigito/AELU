<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Filament\Resources\EnrollmentResource\RelationManagers;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Inscripciones'; 
    protected static ?string $pluralModelLabel = 'Inscripciones'; 
    protected static ?string $modelLabel = 'Inscripción';    
    protected static ?int $navigationSort = 6; 
    protected static ?string $navigationGroup = 'Talleres'; 

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Estudiante')
                    ->relationship('student', 'first_names')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('instructor_workshop_id')
                    ->label('Taller')
                    ->relationship(
                        'instructorWorkshop',
                        'id',
                        fn ($query) => $query->with(['workshop', 'instructor'])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->workshop->name} - {$record->instructor->first_names} {$record->instructor->last_names}" . 
                        " ({$record->day_of_week} de " . 
                        \Carbon\Carbon::parse($record->start_time)->format('H:i') . 
                        " a " . 
                        \Carbon\Carbon::parse($record->end_time)->format('H:i') . ")"
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('enrollment_date')
                    ->label('Fecha de Inscripción')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'enrolled' => 'Inscrito',
                        'completed' => 'Completado',
                        'dropped' => 'Abandonado',
                        'pending' => 'Pendiente',
                    ])
                    ->default('enrolled')
                    ->required(),
                Forms\Components\Select::make('payment_status')
                    ->label('Estado de Pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'paid' => 'Pagado',
                        'overdue' => 'Vencido',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\TextInput::make('total_amount')
                    ->label('Monto Total')
                    ->required()
                    ->numeric()
                    ->prefix('S/.')
                    ->default(0.00),
                Forms\Components\TextInput::make('paid_amount')
                    ->label('Monto Pagado')
                    ->required()
                    ->numeric()
                    ->prefix('S/.')
                    ->default(0.00),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instructorWorkshop.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
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
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
