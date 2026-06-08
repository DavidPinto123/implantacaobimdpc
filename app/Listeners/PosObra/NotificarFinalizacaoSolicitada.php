<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\FinalizacaoSolicitada;
use App\Services\PosObra\WhatsAppService;

class NotificarFinalizacaoSolicitada
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(FinalizacaoSolicitada $event): void
    {
        $pendencia = $event->pendencia->load('liderObra', 'gestor', 'obra', 'construtora', 'anexos');
        $totalEvidencias = $pendencia->anexos->where('tipo', 'EVIDENCIA')->count();

        $mensagem = "✅ *Solicitação de conclusão*\n"
            ."Pendência: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            ."Construtora: {$pendencia->construtora?->nome}\n"
            ."Evidências enviadas: {$totalEvidencias} foto(s)\n\n"
            ."Responda:\n1 - Aprovar\n2 - Rejeitar";

        // Notifica líder
        if ($pendencia->liderObra?->phone) {
            $this->whatsApp->enviar($pendencia->liderObra->phone, $mensagem, $pendencia);
        }

        // Notifica gestor
        if ($pendencia->gestor?->phone) {
            $this->whatsApp->enviar($pendencia->gestor->phone, $mensagem, $pendencia);
        }
    }
}
