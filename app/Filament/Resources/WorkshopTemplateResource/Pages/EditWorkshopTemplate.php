<?php

namespace App\Filament\Resources\WorkshopTemplateResource\Pages;

use App\Filament\Resources\WorkshopTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopTemplate extends EditRecord
{
    protected static string $resource = WorkshopTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->workshops()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta plantilla tiene talleres asociados. Desvincula los talleres primero.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return WorkshopTemplateResource::getUrl('index');
    }
}
