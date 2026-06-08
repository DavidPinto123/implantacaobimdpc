<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;

class NovaAsGeradaNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public string $escopo,
        public string $obra,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Nova AS gerada',
            'body' => 'Foi gerado o escopo '.$this->escopo.' na unidade '.$this->obra.'.',
            'escopo' => $this->escopo,
            'obra' => $this->obra,
        ];
    }
}
