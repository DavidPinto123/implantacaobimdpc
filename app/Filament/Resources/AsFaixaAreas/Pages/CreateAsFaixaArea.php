<?php

namespace App\Filament\Resources\AsFaixaAreas\Pages;

use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAsFaixaArea extends CreateRecord
{
    protected static string $resource = AsFaixaAreaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Faixa criada com sucesso!';
    }
}
