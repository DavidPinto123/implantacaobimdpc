<?php

namespace App\Filament\Resources\GrupoOis\Pages;

use App\Filament\Resources\GrupoOis\GrupoOiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGrupoOi extends CreateRecord
{
    protected static string $resource = GrupoOiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Grupo OI criado com sucesso!';
    }
}
