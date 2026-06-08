<?php

namespace App\Observers;

use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Support\CronogramaFaseSyncMap;

/**
 * Sincroniza CronogramaFase → Projeto (reverse sync).
 *
 * Apenas fases canônicas (CronogramaFaseSyncMap::reverse()) escrevem de volta
 * nos campos legacy de projetos. Alias fases (ex.: LEVANTAMENTO_CADASTRAL para
 * o grupo cad_*) não fazem reverso pra evitar ambiguidade de last-writer.
 */
class CronogramaFaseSyncObserver
{
    private const COLUNAS_SYNC = [
        'data_prevista_inicio' => 'plan_inicio',
        'data_prevista_fim' => 'plan_fim',
        'data_realizada_inicio' => 'real_inicio',
        'data_realizada_fim' => 'real_fim',
    ];

    public function saved(CronogramaFase $fase): void
    {
        if (CronogramaFaseSyncMap::$sincronizando) {
            return;
        }

        $mapa = CronogramaFaseSyncMap::reverse();
        $faseKey = $fase->fase instanceof \BackedEnum ? $fase->fase->value : (string) $fase->fase;

        if (! isset($mapa[$faseKey])) {
            return;
        }

        $campos = $mapa[$faseKey];

        $alteracoes = [];
        foreach (self::COLUNAS_SYNC as $colunaFase => $chaveCampo) {
            if (! $fase->wasChanged($colunaFase)) {
                continue;
            }
            $campoProjeto = $campos[$chaveCampo] ?? null;
            if ($campoProjeto === null) {
                continue;
            }
            $alteracoes[$campoProjeto] = $fase->{$colunaFase};
        }

        if (empty($alteracoes)) {
            return;
        }

        if (! $fase->projeto_id) {
            return;
        }

        $projeto = Projeto::find($fase->projeto_id);
        if (! $projeto) {
            return;
        }

        CronogramaFaseSyncMap::semLoop(function () use ($projeto, $alteracoes) {
            foreach ($alteracoes as $campo => $valor) {
                $projeto->{$campo} = $valor;
            }
            $projeto->saveQuietly();
        });
    }
}
