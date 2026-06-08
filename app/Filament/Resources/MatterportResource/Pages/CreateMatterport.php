<?php

namespace App\Filament\Resources\MatterportResource\Pages;

use App\Filament\Resources\MatterportResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMatterport extends CreateRecord
{
    protected static string $resource = MatterportResource::class;

    protected function afterCreate(): void
    {
        $users = User::all();

        Notification::make()
            ->title('Um novo Tour 360º foi criado')
            ->success()
            ->sendToDatabase($users);
    }
}
