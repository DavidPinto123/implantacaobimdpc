<?php

namespace App\Listeners\PosObra;

use App\Events\PosObra\SlaVencido;
use App\Models\PosObra\WhatsappBotMensagem;
use App\Services\PosObra\WhatsAppService;

class NotificarSlaVencido
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function handle(SlaVencido $event): void
    {
        $pendencia = $event->pendencia->load('gestor', 'obra');

        if (! $pendencia->gestor?->phone) {
            return;
        }

        $prefixo = match ($event->nivelEscalamento) {
            1 => '🟡 Lembrete',
            2 => '🟠 Atenção',
            3 => '🔴 Urgente',
            default => '🆘 Crítico',
        };

        $horas = now()->diffInHours($pendencia->data_termino);

        $mensagem = WhatsappBotMensagem::formatar('sla.escalamento', [
            'prefixo' => $prefixo,
            'codigo' => $pendencia->codigo,
            'sigla' => $pendencia->obra->sigla,
            'urgencia' => $pendencia->urgencia->label(),
            'horas' => $horas,
            'status' => $pendencia->status->label(),
        ]);

        $this->whatsApp->enviar($pendencia->gestor->phone, $mensagem, $pendencia);
    }
}
