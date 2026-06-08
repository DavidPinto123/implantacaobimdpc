<?php

namespace App\Filament\Resources\GrupoOis\Pages;

use App\Filament\Resources\GrupoOis\GrupoOiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGrupoOi extends EditRecord
{
    protected static string $resource = GrupoOiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Grupo OI atualizado com sucesso!';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
