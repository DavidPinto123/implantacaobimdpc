<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;

class ControleNotaFiscalItemLiberadoNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public int $itemId,
        public string $empresa,
        public string $obra,
        public string $rotuloItem,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Item liberado para fornecedor',
            'body' => 'Foi liberado o item '.$this->rotuloItem.' da unidade '.$this->obra.'. É necessário realizar a emissão da Nota Fiscal.',
            'item_id' => $this->itemId,
            'empresa' => $this->empresa,
            'obra' => $this->obra,
        ];
    }
}
