<?php

namespace App\Observers;

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Models\User;
use App\Support\CronogramaFaseSyncMap;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Sincroniza Projeto → CronogramaFase (forward sync).
 *
 * Quando campos legacy do Projeto (cad_*, vis_*, brief_*, proj_*, orca_*,
 * ordem_*, legal_*, data_ass_contrato, data_posse, imp_*, inauguracao) são
 * alterados, propaga as datas correspondentes para os registros normalizados
 * em cronograma_fases.
 *
 * Cria a linha se ela não existir. Status legacy NÃO é propagado pelo observer
 * (o mapeamento de strings como "EM ASSINATURA" para o enum StatusCronograma
 * vive em CronogramaService::mapearStatusTexto() — chame a reconciliação pelo
 * serviço quando for relevante).
 */
class ProjetoSyncObserver
{
    private const MAPA_COLUNAS = [
        'plan_inicio' => 'data_prevista_inicio',
        'plan_fim' => 'data_prevista_fim',
        'real_inicio' => 'data_realizada_inicio',
        'real_fim' => 'data_realizada_fim',
    ];

    /**
     * Campos do Projeto cuja alteração por usuário com role Engenharia
     * dispara alerta para o time PMO. Conforme reunião 08/05.
     */
    private const CAMPOS_CRITICOS_ENGENHARIA = [
        'data_posse',
    ];

    public function saved(Projeto $projeto): void
    {
        $this->dispararAlertaEngenharia($projeto);

        if (CronogramaFaseSyncMap::$sincronizando) {
            return;
        }

        $mudou = collect(CronogramaFaseSyncMap::projetoFieldsObservados())
            ->contains(fn (string $campo) => $projeto->wasChanged($campo));

        if (! $mudou) {
            return;
        }

        CronogramaFaseSyncMap::semLoop(function () use ($projeto) {
            foreach (CronogramaFaseSyncMap::forward() as $faseValue => $campos) {
                $atualizacoes = [];

                foreach (self::MAPA_COLUNAS as $chaveCampo => $colunaFase) {
                    $campoProjeto = $campos[$chaveCampo] ?? null;
                    if ($campoProjeto === null) {
                        continue;
                    }
                    if (! $projeto->wasChanged($campoProjeto)) {
                        continue;
                    }
                    $atualizacoes[$colunaFase] = $projeto->{$campoProjeto};
                }

                if (empty($atualizacoes)) {
                    continue;
                }

                $fase = CronogramaFase::withTrashed()
                    ->where('projeto_id', $projeto->id)
                    ->where('fase', $faseValue)
                    ->first();

                if ($fase && $fase->trashed()) {
                    // Fase foi soft-deletada — respeita a intenção e não reanima.
                    continue;
                }

                if (! $fase) {
                    $faseEnum = FaseCronograma::from($faseValue);
                    $fase = new CronogramaFase([
                        'projeto_id' => $projeto->id,
                        'fase' => $faseValue,
                        'ordem' => $faseEnum->ordem(),
                        'marco' => $faseEnum->marco(),
                        'status' => 'nao_iniciado',
                        'percentual_conclusao' => 0,
                    ]);
                }

                foreach ($atualizacoes as $coluna => $valor) {
                    $fase->{$coluna} = $valor;
                }

                $fase->saveQuietly();
            }
        });
    }

    /**
     * Notifica PMO/Planejamento Estratégico quando usuário com role
     * Engenharia altera campos críticos do projeto (data_posse).
     */
    private function dispararAlertaEngenharia(Projeto $projeto): void
    {
        $usuario = Auth::user();
        if (! $usuario instanceof User) {
            return;
        }

        if (! $usuario->hasAnyRole(['Engenharia'])) {
            return;
        }

        $alterouCampoCritico = collect(self::CAMPOS_CRITICOS_ENGENHARIA)
            ->contains(fn (string $campo) => $projeto->wasChanged($campo));

        if (! $alterouCampoCritico) {
            return;
        }

        $destinatarios = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['PMO', 'Planejamento Estratégico']))
            ->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        $projetoNome = $projeto->nome ?? 'Projeto #'.$projeto->id;

        Notification::make()
            ->title('⚠️ Alerta de Engenharia')
            ->body("Engenharia ({$usuario->name}) alterou Data de Posse do projeto \"{$projetoNome}\".")
            ->icon('heroicon-o-wrench-screwdriver')
            ->warning()
            ->sendToDatabase($destinatarios);
    }
}
