<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\PrazoInformado;
use App\Services\PosObra\WhatsAppService;

class NotificarLiderPrazoInformado
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(PrazoInformado $event): void
    {
        $pendencia = $event->pendencia->load('liderObra', 'construtora', 'obra');
        $lider = $pendencia->liderObra;

        if (! $lider?->phone) {
            return;
        }

        $prazo = $pendencia->data_termino?->format('d/m/Y') ?? 'não informado';

        $mensagem = "📅 *Prazo informado pelo fornecedor*\n"
            ."Pendência: *{$pendencia->codigo}*\n"
            ."Obra: {$pendencia->obra->sigla}\n"
            ."Construtora: {$pendencia->construtora?->nome}\n"
            ."Prazo previsto: *{$prazo}*";

        $this->whatsApp->enviar($lider->phone, $mensagem, $pendencia);
    }
}
