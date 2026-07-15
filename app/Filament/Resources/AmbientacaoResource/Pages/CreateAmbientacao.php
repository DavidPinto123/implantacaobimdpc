<?php

namespace App\Filament\Resources\AmbientacaoResource\Pages;

use App\Filament\Resources\AmbientacaoResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAmbientacao extends CreateRecord
{
    protected static string $resource = AmbientacaoResource::class;

    protected function afterCreate(): void
    {
        $users = User::all();

        Notification::make()
            ->title('Uma nova Ambientação 360º foi criada')
            ->success()
            ->sendToDatabase($users);
    }
}
