<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use App\Models\WorkshopClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationGroup = 'Gestión';
    protected static ?string $navigationLabel = 'Feriados';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            Forms\Components\DatePicker::make('date')
                ->label('Fecha')
                ->required()
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\Textarea::make('description')
                ->label('Descripción')
                ->nullable()
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_recurring')
                ->label('Se repite cada año')
                ->default(false),

            Forms\Components\Toggle::make('affects_classes')
                ->label('Cancela clases')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->header(fn (Table $t) => view('filament.tables.holidays-year-filter', [
                'filterYear' => $t->getLivewire()->filterYear,
                'years'      => [now()->year - 1 => now()->year - 1, now()->year => now()->year],
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d \d\e F \d\e Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Feriado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('—')
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Anual')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('affects_classes')
                    ->label('Cancela clases')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->defaultSort('date', 'asc')
            ->actions([
                Tables\Actions\Action::make('cancel_classes')
                    ->label('Cancelar clases')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->modalHeading(fn (Holiday $record) => "Cancelar clases — {$record->name}")
                    ->modalContent(function (Holiday $record): HtmlString {
                        $classes = WorkshopClass::query()
                            ->with('workshop')
                            ->where('class_date', $record->date->toDateString())
                            ->where('status', '!=', 'cancelled')
                            ->get();

                        if ($classes->isEmpty()) {
                            return new HtmlString(
                                '<p class="text-sm text-gray-500">No hay clases programadas para esta fecha.</p>'
                            );
                        }

                        $rows = $classes->map(fn ($c) =>
                            "<tr class='border-b'>
                                <td class='py-2 pr-4 text-sm font-medium text-gray-900'>{$c->workshop->name}</td>
                                <td class='py-2 text-sm text-gray-500'>{$c->start_time->format('H:i')}</td>
                            </tr>"
                        )->join('');

                        return new HtmlString("
                            <p class='mb-3 text-sm text-gray-600'>
                                Se cancelarán <strong>{$classes->count()}</strong> clase(s) programada(s) para el
                                <strong>{$record->date->translatedFormat('d \d\e F \d\e Y')}</strong>:
                            </p>
                            <table class='w-full mb-2'>
                                <thead>
                                    <tr class='border-b'>
                                        <th class='pb-2 text-left text-xs font-semibold uppercase text-gray-500'>Taller</th>
                                        <th class='pb-2 text-left text-xs font-semibold uppercase text-gray-500'>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>{$rows}</tbody>
                            </table>
                        ");
                    })
                    ->modalSubmitActionLabel('Sí, cancelar clases')
                    ->modalCancelActionLabel('No, volver')
                    ->action(function (Holiday $record): void {
                        $count = WorkshopClass::query()
                            ->where('class_date', $record->date->toDateString())
                            ->where('status', '!=', 'cancelled')
                            ->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title("{$count} clase(s) cancelada(s)")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Holiday $record): bool => $record->affects_classes &&
                        WorkshopClass::query()
                            ->where('class_date', $record->date->toDateString())
                            ->where('status', '!=', 'cancelled')
                            ->exists()
                    ),

                Tables\Actions\EditAction::make()
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Eliminar Feriado')
                    ->modalDescription('¿Estás seguro? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHolidays::route('/'),
        ];
    }
}
