<?php

namespace App\Jobs;

use App\Services\PosObra\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(
        private readonly string $telefone,
        private readonly string $template,
        private readonly array $parametros = [],
    ) {}

    public function handle(WhatsAppService $service): void
    {
        $service->enviarTemplate($this->telefone, $this->template, $this->parametros);
    }
}
