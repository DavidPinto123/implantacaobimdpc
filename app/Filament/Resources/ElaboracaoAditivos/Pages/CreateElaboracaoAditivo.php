<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Pages;

use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateElaboracaoAditivo extends CreateRecord
{
    protected static string $resource = ElaboracaoAditivoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['construtora_id'] = Auth::user()?->construtoras_id;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
