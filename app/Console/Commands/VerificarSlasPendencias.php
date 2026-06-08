<?php

namespace App\Console\Commands;

use App\Enums\PosObra\StatusPendencia;
use App\Events\PosObra\SlaVencido;
use App\Models\PosObra\Pendencia;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class VerificarSlasPendencias extends Command
{
    protected $signature = 'pos-obra:verificar-slas';

    protected $description = 'Verifica pendências com SLA vencido e dispara alertas escalonados para o gestor';

    public function handle(): void
    {
        $pendenciasAtrasadas = Pendencia::query()
            ->whereNotNull('data_termino')
            ->where('data_termino', '<', now())
            ->whereNotIn('status', collect(StatusPendencia::cases())
                ->filter(fn ($s) => $s->isTerminal())
                ->map(fn ($s) => $s->value)
                ->toArray()
            )
            ->with(['gestor', 'obra'])
            ->get();

        foreach ($pendenciasAtrasadas as $pendencia) {
            $horasAtraso = Carbon::parse($pendencia->data_termino)->diffInHours(now());

            $nivel = match (true) {
                $horasAtraso >= 72 => 4, // Crítico
                $horasAtraso >= 48 => 3, // Urgente
                $horasAtraso >= 24 => 2, // Atenção
                default => 1, // Lembrete
            };

            event(new SlaVencido($pendencia, $nivel));
        }

        $this->info("Verificação concluída: {$pendenciasAtrasadas->count()} pendência(s) atrasada(s).");
    }
}
