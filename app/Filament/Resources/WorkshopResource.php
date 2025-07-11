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
use Filament\Forms\Get;
use Filament\Forms\Set;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Talleres'; 
    protected static ?string $pluralModelLabel = 'Talleres'; 
    protected static ?string $modelLabel = 'Taller';  
    protected static ?string $navigationGroup = 'Gestión';   
    protected static ?int $navigationSort = 3;  

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
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
                            ->required()
                            ->live()  // 👈 AGREGAR ESTE ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculatePreviewPricing($get, $set);
                            }),
                        Forms\Components\TextInput::make('pricing_surcharge_percentage')
                            ->label('Recargo sobre Tarifa Base (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(20.00)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculatePreviewPricing($get, $set);
                            }),
                    ])
                    ->columns(2),                   
                                
                Forms\Components\Section::make('Vista Previa de Tarifas')
                    ->schema([
                        Forms\Components\Placeholder::make('pricing_preview')
                            ->label('Tarifas que se generarán')
                            ->content(function (Get $get) {
                                return new \Illuminate\Support\HtmlString(self::generatePricingPreview($get));
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    private static function calculatePreviewPricing(Get $get, Set $set): void
    {
        // Este método actualiza la vista previa cuando cambian los valores
        // La lógica real está en generatePricingPreview
    }

    private static function generatePricingPreview(Get $get): string
    {
        $standardFee = $get('standard_monthly_fee');
        $surchargePercentage = $get('pricing_surcharge_percentage');

        if (!$standardFee || !$surchargePercentage) {
            return '<p class="text-gray-500 italic">Ingresa la tarifa mensual y el porcentaje de recargo para ver la vista previa</p>';
        }

        $basePerClass = $standardFee / 4;
        $surchargeMultiplier = 1 + ($surchargePercentage / 100);

        $volunteerPricings = [
            1 => round($basePerClass * $surchargeMultiplier, 2),
            2 => round($basePerClass * $surchargeMultiplier * 2, 2),
            3 => round($basePerClass * $surchargeMultiplier * 3, 2),
            4 => $standardFee,
            5 => round($standardFee * 1.25, 2), // 25% adicional para 5ta clase
        ];

        $nonVolunteerPricings = [
            1 => round($basePerClass * $surchargeMultiplier, 2),
            2 => round($basePerClass * $surchargeMultiplier * 2, 2),
            3 => round($basePerClass * $surchargeMultiplier * 3, 2),
            4 => $standardFee,
            5 => $standardFee, // Mismo precio que 4 clases
        ];

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        
        // Tarifas para instructores voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-green-50">';
        $html .= '<h4 class="font-semibold text-green-800 mb-3">Instructores Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($volunteerPricings as $classes => $price) {
            $isDefault = $classes === 4;
            $badge = $isDefault ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Estándar</span>' : '';
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} " . ($classes === 1 ? 'clase' : 'clases') . ":</span>";
            $html .= "<span class='font-medium'>S/ " . number_format($price, 2) . " {$badge}</span>";
            $html .= "</div>";
        }
        $html .= '</div></div>';

        // Tarifas para instructores no voluntarios
        $html .= '<div class="border rounded-lg p-4 bg-blue-50">';
        $html .= '<h4 class="font-semibold text-blue-800 mb-3">Instructores No Voluntarios</h4>';
        $html .= '<div class="space-y-2">';
        foreach ($nonVolunteerPricings as $classes => $price) {
            $isDefault = $classes === 4;
            $badge = $isDefault ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Estándar</span>' : '';
            $html .= "<div class='flex justify-between items-center'>";
            $html .= "<span>{$classes} " . ($classes === 1 ? 'clase' : 'clases') . ":</span>";
            $html .= "<span class='font-medium'>S/ " . number_format($price, 2) . " {$badge}</span>";
            $html .= "</div>";
        }
        /* $html .= '</div></div>';
        
        $html .= '</div>';
        
        $html .= '<div class="mt-4 text-sm text-gray-600">';
        $html .= "<p><strong>Tarifa base por clase:</strong> S/ " . number_format($basePerClass, 2) . "</p>";
        $html .= "<p><strong>Recargo aplicado:</strong> {$surchargePercentage}% (multiplicador: {$surchargeMultiplier})</p>";
        $html .= "<p><strong>Precio con recargo:</strong> S/ " . number_format($basePerClass * $surchargeMultiplier, 2) . " por clase individual</p>";
        $html .= '</div>'; */

        return $html;
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
                Tables\Columns\TextColumn::make('pricing_surcharge_percentage')
                    ->label('Recargo')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->color('info'),
            ])
            ->filters([
                // 
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_pricings')
                    ->label('Ver Tarifas')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Workshop $record) => "Tarifas de {$record->name}")
                    ->modalContent(fn (Workshop $record) => view('filament.resources.workshop-resource.pricing-modal', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
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
