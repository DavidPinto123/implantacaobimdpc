<?php

namespace App\Services;

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseHistorico;
use App\Models\Obras;
use App\Models\Projeto;
use Carbon\Carbon;

class CronogramaService
{
    public static function registrarHistoricoDatas(
        CronogramaFase $fase,
        string $campo,
        ?string $valorAnterior,
        ?string $valorNovo,
        ?string $motivo = null,
        ?int $usuarioId = null,
        bool $automatico = false,
    ): void {
        if ($valorAnterior === $valorNovo) {
            return;
        }

        CronogramaFaseHistorico::create([
            'cronograma_fase_id' => $fase->id,
            'campo_alterado' => $campo,
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
            'motivo' => $motivo,
            'usuario_id' => $usuarioId,
            'automatico' => $automatico,
        ]);
    }

    public function criarFasesParaProjeto(Projeto $projeto): void
    {
        $projeto->loadMissing('obras');
        $obra = $projeto->obras->first();

        $dadosReais = $this->obterDadosReais($projeto, $obra);
        $datasEstimadas = $this->estimarDatasParaProjeto($projeto, $obra);

        foreach (FaseCronograma::cases() as $faseCronograma) {
            // PERSONALIZADA é ad-hoc por projeto e não deve ser criada em massa.
            if ($faseCronograma === FaseCronograma::PERSONALIZADA) {
                continue;
            }

            $faseKey = $faseCronograma->value;
            $real = $dadosReais[$faseKey] ?? [];
            $estimada = $datasEstimadas[$faseKey] ?? [];

            $planInicio = $this->parseData($real['plan_inicio'] ?? $estimada['inicio'] ?? null);
            $planFim = $this->parseData($real['plan_fim'] ?? $estimada['fim'] ?? null);
            $realInicio = $this->parseData($real['real_inicio'] ?? null);
            $realFim = $this->parseData($real['real_fim'] ?? null);
            $statusFonte = $real['status'] ?? null;
            $statusMapeado = $statusFonte ? $this->mapearStatusTexto($statusFonte) : null;

            if ($statusMapeado === StatusCronograma::CONCLUIDO && ! $realInicio) {
                $realInicio = $planInicio;
            }
            if ($statusMapeado === StatusCronograma::CONCLUIDO && ! $realFim) {
                $realFim = $planFim;
            }
            if ($statusMapeado === StatusCronograma::EM_ANDAMENTO && ! $realInicio) {
                $realInicio = $planInicio;
            }

            CronogramaFase::firstOrCreate(
                [
                    'projeto_id' => $projeto->id,
                    'fase' => $faseCronograma,
                ],
                [
                    'ordem' => $faseCronograma->ordem(),
                    'marco' => $faseCronograma->marco(),
                    'data_prevista_inicio' => $planInicio,
                    'data_prevista_fim' => $planFim,
                    'status' => $statusMapeado ?? StatusCronograma::NAO_INICIADO,
                    'percentual_conclusao' => $statusMapeado === StatusCronograma::CONCLUIDO ? 100 : 0,
                ]
            );
        }
    }

    public function sincronizarFasesComDadosReais(Projeto $projeto): void
    {
        $projeto->loadMissing('obras');
        $obra = $projeto->obras->first();

        $dadosReais = $this->obterDadosReais($projeto, $obra);
        $datasEstimadas = $this->estimarDatasParaProjeto($projeto, $obra);

        foreach (FaseCronograma::cases() as $faseCronograma) {
            if ($faseCronograma === FaseCronograma::PERSONALIZADA) {
                continue;
            }

            $faseKey = $faseCronograma->value;
            $real = $dadosReais[$faseKey] ?? [];
            $estimada = $datasEstimadas[$faseKey] ?? [];

            $fase = CronogramaFase::where('projeto_id', $projeto->id)
                ->where('fase', $faseCronograma)
                ->first();

            if (! $fase) {
                $this->criarFasesParaProjeto($projeto);

                return;
            }

            $atualizar = [
                'ordem' => $faseCronograma->ordem(),
            ];

            if (! empty($real['plan_inicio'])) {
                $atualizar['data_prevista_inicio'] = $this->parseData($real['plan_inicio']);
            } elseif (! $fase->data_prevista_inicio && ! empty($estimada['inicio'])) {
                $atualizar['data_prevista_inicio'] = $estimada['inicio'];
            }

            if (! empty($real['plan_fim'])) {
                $atualizar['data_prevista_fim'] = $this->parseData($real['plan_fim']);
            } elseif (! $fase->data_prevista_fim && ! empty($estimada['fim'])) {
                $atualizar['data_prevista_fim'] = $estimada['fim'];
            }

            $fase->update($atualizar);
        }
    }

    private function obterDadosReais(Projeto $projeto, ?Obras $obra): array
    {
        $obraConcluida = $obra && (
            $obra->status === 'Inaugurada'
            || (float) ($obra->percentual_obra_executado ?? $obra->percentual_obra ?? 0) >= 100
        );

        $dados = [];

        $dados['inicio_projeto'] = [
            'plan_inicio' => $projeto->cad_plan_inicio,
            'plan_fim' => $projeto->cad_plan_fim,
            'real_inicio' => $projeto->cad_rea_inicio,
            'real_fim' => $projeto->cad_rea_fim,
            'status' => $projeto->cad_status,
        ];

        $dados['assinatura_contrato'] = [
            'plan_inicio' => $projeto->data_assinatura_contrato,
            'plan_fim' => $projeto->data_assinatura_contrato,
            'real_inicio' => $projeto->status_contrato === 'ASSINADO' ? $projeto->data_assinatura_contrato : null,
            'real_fim' => $projeto->status_contrato === 'ASSINADO' ? $projeto->data_assinatura_contrato : null,
            'status' => $projeto->status_contrato === 'ASSINADO' ? 'CONCLUÍDO' : null,
        ];

        $dados['codigo_oracle'] = [
            'plan_inicio' => $projeto->ordem_planej_ini,
            'plan_fim' => $projeto->ordem_planej_fim,
            'real_inicio' => $projeto->ordem_realizado,
            'real_fim' => $projeto->ordem_realizado_fim,
            'status' => $projeto->ordem_status,
        ];

        $dados['levantamento_cadastral'] = [
            'plan_inicio' => $projeto->cad_plan_inicio,
            'plan_fim' => $projeto->cad_plan_fim,
            'real_inicio' => $projeto->cad_rea_inicio,
            'real_fim' => $projeto->cad_rea_fim,
            'status' => $projeto->cad_status,
        ];

        $dados['visita_tecnica'] = [
            'plan_inicio' => $projeto->vis_plan_inicio,
            'plan_fim' => $projeto->vis_plan_fim,
            'real_inicio' => $projeto->vis_rea_inicio,
            'real_fim' => $projeto->vis_rea_fim,
            'status' => $projeto->vis_status,
        ];

        $dados['consulta_previa'] = [
            'plan_inicio' => $projeto->legal_plan_ini,
            'plan_fim' => $projeto->legal_plan_fim,
            'real_inicio' => $projeto->legal_realizado_ini,
            'real_fim' => $projeto->legal_realizado_fim,
            'status' => $projeto->legal_status,
        ];

        $dados['briefing'] = [
            'plan_inicio' => $projeto->brief_plan_lay_inicio ?? $projeto->brief_plan,
            'plan_fim' => $projeto->brief_plan_lay_fim ?? $projeto->brief_plan,
            'real_inicio' => $projeto->brief_real_lay_inicio ?? $projeto->brief_real,
            'real_fim' => $projeto->brief_real_lay_fim ?? $projeto->brief_real,
            'status' => $projeto->brief_status,
        ];

        $dados['layout'] = [
            'plan_inicio' => $projeto->brief_plan_lay_inicio,
            'plan_fim' => $projeto->brief_plan_lay_fim,
            'real_inicio' => $projeto->brief_real_lay_inicio,
            'real_fim' => $projeto->brief_real_lay_fim,
            'status' => $projeto->brief_status,
        ];

        $dados['ordem_investimento'] = [
            'plan_inicio' => $projeto->ordem_planej_ini,
            'plan_fim' => $projeto->ordem_planej_fim,
            'real_inicio' => $projeto->ordem_realizado,
            'real_fim' => $projeto->ordem_realizado_fim,
            'status' => $projeto->ordem_status,
        ];

        $dados['start_projetos_executivos'] = [
            'plan_inicio' => $projeto->proj_plan_ini,
            'plan_fim' => $projeto->proj_plan_ini,
            'real_inicio' => $projeto->proj_real_ini,
            'real_fim' => $projeto->proj_real_ini,
            'status' => $projeto->proj_status,
        ];

        $dados['executivo'] = [
            'plan_inicio' => $projeto->proj_plan_ini,
            'plan_fim' => $projeto->proj_plan_fim,
            'real_inicio' => $projeto->proj_real_ini,
            'real_fim' => $projeto->proj_real_fim,
            'status' => $projeto->proj_status,
        ];

        $dados['kickoff'] = [
            'plan_inicio' => null,
            'plan_fim' => null,
            'real_inicio' => null,
            'real_fim' => null,
            'status' => null,
        ];

        $dados['orcamentos'] = [
            'plan_inicio' => $projeto->orca_planejado_ini,
            'plan_fim' => $projeto->orca_planejado_fim,
            'real_inicio' => $projeto->orca_real_ini,
            'real_fim' => $projeto->orca_real_fim,
            'status' => $projeto->orca_status,
        ];

        $dados['prazo_legal'] = [
            'plan_inicio' => $projeto->legal_plan_ini,
            'plan_fim' => $projeto->legal_plan_fim,
            'real_inicio' => $projeto->legal_realizado_ini,
            'real_fim' => $projeto->legal_realizado_fim,
            'status' => $projeto->legal_status,
        ];

        $dados['posse'] = [
            'plan_inicio' => $projeto->data_posse ?? $obra?->entrada_ponto,
            'plan_fim' => $projeto->data_posse ?? $obra?->entrada_ponto,
            'real_inicio' => $projeto->posse_status === 'REALIZADO' ? ($projeto->data_posse ?? $obra?->entrada_ponto) : null,
            'real_fim' => $projeto->posse_status === 'REALIZADO' ? ($projeto->data_posse ?? $obra?->entrada_ponto) : null,
            'status' => $projeto->posse_status === 'REALIZADO' ? 'CONCLUÍDO' : null,
        ];

        $dados['mkt_ativacao_pre_vendas'] = [
            'plan_inicio' => null,
            'plan_fim' => null,
            'real_inicio' => null,
            'real_fim' => null,
            'status' => null,
        ];

        if ($obra) {
            $dados['obras'] = [
                'plan_inicio' => $obra->inicio ?? $obra->inicio_real,
                'plan_fim' => $obra->fim,
                'real_inicio' => $obraConcluida ? ($obra->inicio ?? $obra->inicio_real) : ($obra->inicio_real ?? $obra->inicio),
                'real_fim' => $obraConcluida ? $obra->fim : null,
                'status' => $obraConcluida ? 'CONCLUÍDO' : (in_array($obra->status, ['Obras', 'Em processo']) ? 'EM ANDAMENTO' : null),
            ];

            $dados['implantacao'] = [
                'plan_inicio' => $projeto->imp_inicio ?? $obra->inicio_imp,
                'plan_fim' => $projeto->imp_fim ?? $obra->fim_imp,
                'real_inicio' => $obraConcluida ? ($projeto->imp_inicio ?? $obra->inicio_imp) : null,
                'real_fim' => $obraConcluida ? ($projeto->imp_fim ?? $obra->fim_imp) : null,
                'status' => $obraConcluida ? 'CONCLUÍDO' : null,
            ];
        } else {
            $dados['obras'] = [
                'plan_inicio' => null,
                'plan_fim' => null,
                'real_inicio' => null,
                'real_fim' => null,
                'status' => null,
            ];

            $dados['implantacao'] = [
                'plan_inicio' => $projeto->imp_inicio,
                'plan_fim' => $projeto->imp_fim,
                'real_inicio' => null,
                'real_fim' => null,
                'status' => null,
            ];
        }

        $dados['inauguracao'] = [
            'plan_inicio' => $projeto->inauguracao,
            'plan_fim' => $projeto->inauguracao,
            'real_inicio' => ($obra && $obraConcluida) ? $projeto->inauguracao : null,
            'real_fim' => ($obra && $obraConcluida) ? $projeto->inauguracao : null,
            'status' => ($obra && $obraConcluida) ? 'CONCLUÍDO' : null,
        ];

        return $dados;
    }

    private function mapearStatusTexto(?string $statusTexto): StatusCronograma
    {
        if (! $statusTexto) {
            return StatusCronograma::NAO_INICIADO;
        }

        return match (mb_strtoupper(trim($statusTexto))) {
            'CONCLUÍDO', 'CONCLUIDO' => StatusCronograma::CONCLUIDO,
            'FINALIZADO' => StatusCronograma::FINALIZADO,
            'REALIZADO' => StatusCronograma::REALIZADO,
            'EM ANDAMENTO' => StatusCronograma::EM_ANDAMENTO,
            'SOLICITADO' => StatusCronograma::SOLICITADO,
            'AGENDADO' => StatusCronograma::AGENDADO,
            'ATRASADO' => StatusCronograma::ATRASADO,
            'PARALISADO' => StatusCronograma::PARALISADO,
            'BLOQUEADO' => StatusCronograma::BLOQUEADO,
            'VERIFICAR' => StatusCronograma::VERIFICAR,
            'N/A' => StatusCronograma::NA,
            'ASSINADO' => StatusCronograma::ASSINADO,
            'EM ASSINATURA' => StatusCronograma::EM_ASSINATURA,
            'MINUTA' => StatusCronograma::MINUTA,
            'NEGOCIAÇÃO', 'NEGOCIACAO' => StatusCronograma::NEGOCIACAO,
            'EM APROVAÇÃO', 'EM APROVACAO' => StatusCronograma::EM_APROVACAO,
            'APROVADO' => StatusCronograma::APROVADO,
            'REVISÃO', 'REVISAO' => StatusCronograma::REVISAO,
            'PENDÊNCIA COM' => StatusCronograma::PENDENCIA_COM,
            'PENDÊNCIA LEG' => StatusCronograma::PENDENCIA_LEG,
            'PENDÊNCIA ARQ' => StatusCronograma::PENDENCIA_ARQ,
            'PENDÊNCIA DIR' => StatusCronograma::PENDENCIA_DIR,
            'PENDÊNCIA ENGª', 'PENDÊNCIA ENGENHARIA' => StatusCronograma::PENDENCIA_ENGENHARIA,
            'PENDÊNCIA', 'PENDÊNCIAS' => StatusCronograma::PENDENCIA,
            'NÃO REALIZADO' => StatusCronograma::NAO_REALIZADO,
            'PRONTO' => StatusCronograma::PRONTO,
            'OBRA PP' => StatusCronograma::OBRA_PP,
            'OBRA SF' => StatusCronograma::OBRA_SF,
            'TERRENO' => StatusCronograma::TERRENO,
            'ESTUDO' => StatusCronograma::ESTUDO,
            default => StatusCronograma::NAO_INICIADO,
        };
    }

    private function parseData(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    private function estimarDatasParaProjeto(Projeto $projeto, ?Obras $obra): array
    {
        $inicioObra = $obra?->inicio ?? $obra?->inicio_real;
        $fimObra = $obra?->fim;
        $inauguracao = $projeto->inauguracao;

        if (! $inicioObra) {
            return [];
        }

        $inicio = Carbon::parse($inicioObra);
        $fim = $inauguracao ? Carbon::parse($inauguracao) : ($fimObra ? Carbon::parse($fimObra)->addMonths(2) : $inicio->copy()->addMonths(8));
        $totalDias = max(1, $inicio->diffInDays($fim));

        $distribuicao = [
            'inicio_projeto' => [-0.18, -0.15],
            'assinatura_contrato' => [-0.15, -0.12],
            'codigo_oracle' => [-0.12, -0.10],
            'levantamento_cadastral' => [-0.10, -0.08],
            'visita_tecnica' => [-0.08, -0.05],
            'consulta_previa' => [-0.06, -0.03],
            'briefing' => [-0.05, -0.02],
            'layout' => [-0.03, 0.00],
            'ordem_investimento' => [-0.02, 0.00],
            'start_projetos_executivos' => [0.00, 0.05],
            'executivo' => [0.03, 0.15],
            'kickoff' => [0.10, 0.12],
            'orcamentos' => [0.10, 0.20],
            'prazo_legal' => [0.15, 0.25],
            'posse' => [0.20, 0.22],
            'mkt_ativacao_pre_vendas' => [0.80, 0.92],
            'obras' => [0.22, 0.90],
            'implantacao' => [0.90, 0.98],
            'inauguracao' => [1.00, 1.00],
        ];

        $datas = [];

        foreach ($distribuicao as $fase => $range) {
            $datas[$fase] = [
                'inicio' => $inicio->copy()->addDays((int) round($range[0] * $totalDias)),
                'fim' => $inicio->copy()->addDays((int) round($range[1] * $totalDias)),
            ];
        }

        return $datas;
    }

    public function calcularPercentualGeral(Projeto $projeto): float
    {
        return (float) $projeto->cronogramaFases()->avg('percentual_conclusao');
    }
}
