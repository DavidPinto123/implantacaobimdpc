<?php

namespace App\Console\Commands;

use App\Models\Orcamento;
use App\Services\OrcamentoRevitSincronizador;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class VerificarSincronizacaoRevit extends Command
{
    protected $signature = 'orcamentos:sincronizar-revit';

    protected $description = 'Verifica se os arquivos do Revit vinculados a orçamentos foram atualizados e aplica as mudanças, notificando o responsável';

    public function handle(): void
    {
        $orcamentos = Orcamento::query()
            ->whereNotNull('arquivo_revit')
            ->where('arquivo_revit', '!=', '')
            ->with('criador')
            ->get();

        $atualizados = 0;

        foreach ($orcamentos as $orcamento) {
            $resultado = OrcamentoRevitSincronizador::sincronizar($orcamento);

            if (! $resultado['mudou']) {
                continue;
            }

            $atualizados++;

            if ($orcamento->criador) {
                Notification::make()
                    ->title("Orçamento \"{$orcamento->nome}\" atualizado pelo Revit")
                    ->body("{$resultado['atualizados']} item(ns) atualizados, {$resultado['novos']} novo(s). Agora na revisão {$orcamento->revisao_formatada}.")
                    ->success()
                    ->sendToDatabase($orcamento->criador);
            }
        }

        $this->info("Verificação concluída: {$orcamentos->count()} orçamento(s) vinculado(s) ao Revit, {$atualizados} atualizado(s).");
    }
}
