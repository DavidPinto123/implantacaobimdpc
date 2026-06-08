<?php

use App\Enums\FaseCronograma;
use App\Support\CronogramaFaseSyncMap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration de dados: popula cronograma_fases a partir dos campos legacy de
 * projetos (cad_*, vis_*, brief_*, ordem_*, proj_*, orca_*, legal_*,
 * data_ass_contrato, data_posse, imp_*, inauguracao).
 *
 * Regra: não sobrescreve valores já existentes em cronograma_fases — apenas
 * preenche colunas NULL. Isso preserva eventuais correções manuais feitas
 * direto na tabela normalizada.
 *
 * Status é deixado no default ('nao_iniciado') — a tradução de strings
 * legacy para o enum StatusCronograma exige lógica que fica no observer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mapa = CronogramaFaseSyncMap::forward();
        $agora = now();

        DB::table('projetos')
            ->select(array_merge(
                ['id'],
                CronogramaFaseSyncMap::projetoFieldsObservados()
            ))
            ->orderBy('id')
            ->chunk(200, function ($projetos) use ($mapa, $agora) {
                foreach ($projetos as $projeto) {
                    foreach ($mapa as $faseValue => $campos) {
                        $fase = FaseCronograma::from($faseValue);

                        $valores = [
                            'data_prevista_inicio' => $campos['plan_inicio'] ? $projeto->{$campos['plan_inicio']} : null,
                            'data_prevista_fim' => $campos['plan_fim'] ? $projeto->{$campos['plan_fim']} : null,
                            'data_realizada_inicio' => $campos['real_inicio'] ? $projeto->{$campos['real_inicio']} : null,
                            'data_realizada_fim' => $campos['real_fim'] ? $projeto->{$campos['real_fim']} : null,
                        ];

                        $temAlgumDado = collect($valores)->filter()->isNotEmpty();

                        $existente = DB::table('cronograma_fases')
                            ->where('projeto_id', $projeto->id)
                            ->where('fase', $faseValue)
                            ->whereNull('deleted_at')
                            ->first();

                        if ($existente) {
                            $updates = [];
                            foreach ($valores as $coluna => $valor) {
                                if ($valor !== null && $existente->{$coluna} === null) {
                                    $updates[$coluna] = $valor;
                                }
                            }
                            if (! empty($updates)) {
                                $updates['updated_at'] = $agora;
                                DB::table('cronograma_fases')
                                    ->where('id', $existente->id)
                                    ->update($updates);
                            }

                            continue;
                        }

                        if (! $temAlgumDado) {
                            continue;
                        }

                        DB::table('cronograma_fases')->insert(array_merge(
                            $valores,
                            [
                                'projeto_id' => $projeto->id,
                                'fase' => $faseValue,
                                'ordem' => $fase->ordem(),
                                'marco' => $fase->marco() ? 1 : 0,
                                'status' => 'nao_iniciado',
                                'percentual_conclusao' => 0,
                                'regra_customizada' => 0,
                                'created_at' => $agora,
                                'updated_at' => $agora,
                            ]
                        ));
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversível: os dados já existiam em projetos; remover as linhas
        // criadas por este backfill destruiria possíveis edições feitas pelo
        // usuário após a migration rodar. Se precisar reverter, restaurar
        // backup ou deletar manualmente os registros identificados pelo
        // timestamp de created_at.
    }
};
