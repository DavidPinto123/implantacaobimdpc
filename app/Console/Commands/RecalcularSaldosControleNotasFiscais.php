<?php

namespace App\Console\Commands;

use App\Enums\StatusControleNotaFiscalNota;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use Illuminate\Console\Command;

class RecalcularSaldosControleNotasFiscais extends Command
{
    protected $signature = 'controle-nota-fiscal:recalcular-saldos';

    protected $description = 'Recalcula total medido (soma das notas MO + material) e saldo (valor global - total medido) de itens/auxiliares.';

    public function handle(): int
    {
        $itensAtualizados = $this->recalcularItens();
        $auxiliaresAtualizados = $this->recalcularAuxiliares();

        $this->info("Itens atualizados: {$itensAtualizados}");
        $this->info("Auxiliares atualizados: {$auxiliaresAtualizados}");

        return self::SUCCESS;
    }

    protected function recalcularItens(): int
    {
        $atualizados = 0;

        ControleNotaFiscalItem::query()
            ->withSum([
                'notasFiscais as acumulado_direto' => fn ($query) => $query
                    ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
                    ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value),
                'notasFiscais as acumulado_indireto' => fn ($query) => $query
                    ->tipoMaterialBucket()
                    ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value),
            ], 'valor_acumulado_medido_nf')
            ->chunkById(200, function ($itens) use (&$atualizados): void {
                foreach ($itens as $item) {
                    $valorAcumuladoMedido = (float) ($item->acumulado_direto ?? 0) + (float) ($item->acumulado_indireto ?? 0);
                    $totalMedicao = $valorAcumuladoMedido;
                    $saldo = (float) $item->valor_global_a - $totalMedicao;

                    $item->updateQuietly([
                        'total_medicao_a_menos_b' => $totalMedicao,
                        'valor_acumulado_medido' => $valorAcumuladoMedido,
                        'saldo' => $saldo,
                    ]);

                    $atualizados++;
                }
            });

        return $atualizados;
    }

    protected function recalcularAuxiliares(): int
    {
        $atualizados = 0;

        ControleNotaFiscalAuxiliar::query()
            ->withSum([
                'notasFiscais as acumulado_direto' => fn ($query) => $query
                    ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
                    ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value),
                'notasFiscais as acumulado_indireto' => fn ($query) => $query
                    ->tipoMaterialBucket()
                    ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value),
            ], 'valor_acumulado_medido_nf')
            ->chunkById(200, function ($auxiliares) use (&$atualizados): void {
                foreach ($auxiliares as $auxiliar) {
                    $valorAcumuladoMedido = (float) ($auxiliar->acumulado_direto ?? 0) + (float) ($auxiliar->acumulado_indireto ?? 0);
                    $totalMedicao = $valorAcumuladoMedido;
                    $saldo = (float) $auxiliar->valor_global_a - $totalMedicao;

                    $auxiliar->updateQuietly([
                        'total_medicao_a_menos_b' => $totalMedicao,
                        'valor_acumulado_medido' => $valorAcumuladoMedido,
                        'saldo' => $saldo,
                    ]);

                    $atualizados++;
                }
            });

        return $atualizados;
    }
}
