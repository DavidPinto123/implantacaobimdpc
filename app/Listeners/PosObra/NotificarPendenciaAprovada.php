<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\PendenciaAprovada;
use App\Services\PosObra\WhatsAppService;

class NotificarPendenciaAprovada
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(PendenciaAprovada $event): void
    {
        $pendencia = $event->pendencia->load('construtora', 'gestor', 'obra');

        $mensagem = "🎉 *Pendência aprovada e concluída!*\n"
            ."Código: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            .'Concluída em: '.now()->format('d/m/Y H:i');

        if ($pendencia->construtora?->telefone_whatsapp) {
            $this->whatsApp->enviar($pendencia->construtora->telefone_whatsapp, $mensagem, $pendencia);
        }

        if ($pendencia->gestor?->phone) {
            $this->whatsApp->enviar($pendencia->gestor->phone, $mensagem, $pendencia);
        }
    }
}
