<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\PendenciaRegistrada;
use App\Services\PosObra\WhatsAppService;

class NotificarConstrutoraNovasPendencias
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(PendenciaRegistrada $event): void
    {
        $pendencia = $event->pendencia->load('construtora', 'obra', 'disciplina');
        $construtora = $pendencia->construtora;

        if (! $construtora?->telefone_whatsapp) {
            return;
        }

        $mensagem = "🔔 *Nova pendência registrada*\n"
            ."Código: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            ."Disciplina: {$pendencia->disciplina?->label}\n"
            ."Urgência: {$pendencia->urgencia->label()}\n"
            ."Descrição: {$pendencia->descricao}\n\n"
            .'Responda informando a *data prevista de conclusão* (dd/mm/aaaa).';

        $this->whatsApp->enviar($construtora->telefone_whatsapp, $mensagem, $pendencia);
    }
}
