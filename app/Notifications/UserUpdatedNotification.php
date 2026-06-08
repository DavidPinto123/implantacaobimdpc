<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;

class UserUpdatedNotification extends BaseNotification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database']; // 'mail' é opcional
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Usuário atualizado',
            'body' => 'As alterações foram salvas com sucesso.',
        ];
    }

    public function toFilament($notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Usuário Atualizado')
            ->body('As alterações do usuário foram salvas com sucesso.')
            ->success();
    }
}
