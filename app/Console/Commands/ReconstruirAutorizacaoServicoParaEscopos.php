<?php

namespace App\Console\Commands;

use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Services\AutorizacaoServicoService;
use Illuminate\Console\Command;

class ReconstruirAutorizacaoServicoParaEscopos extends Command
{
    protected $signature = 'autorizacao-servico:reconstruir';

    protected $description = 'Reconstrói as relações de AutorizacaoServico para AsEscopos baseado nos ControleNotaFiscalItems existentes';

    public function handle(): int
    {
        $controles = ControleNotaFiscal::query()
            ->whereNotNull('obra_id')
            ->with(['obra', 'itens.asEscopo'])
            ->get();

        $service = app(AutorizacaoServicoService::class);
        $criadas = 0;

        foreach ($controles as $controle) {
            $controle->itens()
                ->whereNotNull('as_escopo_id')
                ->with('asEscopo')
                ->get()
                ->each(function ($item) use ($controle, $service, &$criadas): void {
                    if (! filled($item->as_escopo_id)) {
                        return;
                    }

                    $escopo = $item->asEscopo;

                    if (! $escopo || blank($escopo->numero_as)) {
                        return;
                    }

                    $construtora = Construtora::query()
                        ->where('nome', $item->empresa)
                        ->first();

                    if (! $construtora) {
                        return;
                    }

                    $jaExiste = AutorizacaoServico::query()
                        ->where('obra_id', $controle->obra_id)
                        ->where('as_escopo_id', $item->as_escopo_id)
                        ->where('construtora_id', $construtora->id)
                        ->exists();

                    if ($jaExiste) {
                        return;
                    }

                    $numeroAsEstruturado = $service->gerarNumeroAsEstruturado(
                        $controle->obra,
                        $escopo,
                        $construtora,
                    );

                    AutorizacaoServico::create([
                        'obra_id' => $controle->obra_id,
                        'as_escopo_id' => $item->as_escopo_id,
                        'construtora_id' => $construtora->id,
                        'numero_as' => $numeroAsEstruturado,
                        'valor' => 0,
                    ]);

                    $criadas++;
                });
        }

        $this->info("Total de AutorizacaoServico criadas: {$criadas}");

        return self::SUCCESS;
    }
}
