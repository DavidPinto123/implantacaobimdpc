<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\PendenciaRejeitada;
use App\Services\PosObra\WhatsAppService;

class NotificarPendenciaRejeitada
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(PendenciaRejeitada $event): void
    {
        $pendencia = $event->pendencia->load('construtora', 'obra');

        if (! $pendencia->construtora?->telefone_whatsapp) {
            return;
        }

        $mensagem = "❌ *Conclusão rejeitada*\n"
            ."Pendência: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            ."Motivo: {$event->motivo}\n\n"
            .'Por favor, corrija o problema e envie as evidências novamente.';

        $this->whatsApp->enviar($pendencia->construtora->telefone_whatsapp, $mensagem, $pendencia);
    }
}
