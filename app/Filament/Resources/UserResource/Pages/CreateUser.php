<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\UserAccessInvitationNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuário criado.')
            ->body('Usuário criado com sucesso. O e-mail para concluir o acesso foi enviado.')
            ->sendToDatabase(auth()->user());
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $user->notify(new UserAccessInvitationNotification());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['is_fornecedor'])) {
            $data['construtoras_id'] = null;
        }

        if (blank($data['password'] ?? null)) {
            $data['password'] = Str::random(32);
        }

        return $data;
    }
}
