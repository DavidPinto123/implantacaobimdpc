<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\ExecucaoIniciada;
use App\Services\PosObra\WhatsAppService;

class NotificarLiderExecucaoIniciada
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(ExecucaoIniciada $event): void
    {
        $pendencia = $event->pendencia->load('liderObra', 'construtora', 'obra');
        $lider = $pendencia->liderObra;

        if (! $lider?->phone) {
            return;
        }

        $mensagem = "🔨 *Execução iniciada*\n"
            ."Pendência: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            ."Construtora: {$pendencia->construtora?->nome}\n"
            .'Você será notificado quando o fornecedor solicitar conclusão.';

        $this->whatsApp->enviar($lider->phone, $mensagem, $pendencia);
    }
}
