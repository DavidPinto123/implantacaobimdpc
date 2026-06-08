<?php

namespace App\Support;

use App\Enums\FaseCronograma;

/**
 * Mapa central de sincronização entre cronograma_fases (fonte normalizada)
 * e os campos legacy em projetos. Usado pelos observers bidirecionais.
 *
 * forward(): Projeto → CronogramaFase (todas as fases mapeadas).
 * reverse(): CronogramaFase → Projeto (apenas fases canônicas, evita
 *            ambiguidade quando várias fases compartilham os mesmos campos
 *            legacy — ex.: inicio_projeto e levantamento_cadastral ambos
 *            leem cad_*, mas só uma escreve de volta).
 */
class CronogramaFaseSyncMap
{
    /**
     * Flag de reentrada compartilhada entre os dois observers bidirecionais.
     * Enquanto TRUE, qualquer save disparado por um lado é ignorado pelo outro,
     * evitando loop infinito Projeto ↔ CronogramaFase.
     */
    public static bool $sincronizando = false;

    /**
     * Executa $fn com a flag de sincronização ativa. Se já estiver sincronizando,
     * ignora silenciosamente (evita loop).
     */
    public static function semLoop(callable $fn): void
    {
        if (self::$sincronizando) {
            return;
        }

        self::$sincronizando = true;
        try {
            $fn();
        } finally {
            self::$sincronizando = false;
        }
    }


    /**
     * Mapa completo fase → campos do Projeto.
     * Chaves: plan_inicio, plan_fim, real_inicio, real_fim, status.
     *
     * @return array<string, array<string, ?string>>
     */
    public static function forward(): array
    {
        return [
            FaseCronograma::INICIO_PROJETO->value => [
                'plan_inicio' => 'cad_plan_inicio',
                'plan_fim' => 'cad_plan_fim',
                'real_inicio' => 'cad_rea_inicio',
                'real_fim' => 'cad_rea_fim',
                'status' => 'cad_status',
            ],
            FaseCronograma::ASSINATURA_CONTRATO->value => [
                'plan_inicio' => 'data_ass_contrato',
                'plan_fim' => 'data_ass_contrato',
                'real_inicio' => null,
                'real_fim' => null,
                'status' => 'status_contrato',
            ],
            FaseCronograma::CODIGO_ORACLE->value => [
                'plan_inicio' => 'ordem_planej_ini',
                'plan_fim' => 'ordem_planej_fim',
                'real_inicio' => 'ordem_realizado',
                'real_fim' => 'ordem_realizado_fim',
                'status' => 'ordem_status',
            ],
            FaseCronograma::LEVANTAMENTO_CADASTRAL->value => [
                'plan_inicio' => 'cad_plan_inicio',
                'plan_fim' => 'cad_plan_fim',
                'real_inicio' => 'cad_rea_inicio',
                'real_fim' => 'cad_rea_fim',
                'status' => 'cad_status',
            ],
            FaseCronograma::VISITA_TECNICA->value => [
                'plan_inicio' => 'vis_plan_inicio',
                'plan_fim' => 'vis_plan_fim',
                'real_inicio' => 'vis_rea_inicio',
                'real_fim' => 'vis_rea_fim',
                'status' => 'vis_status',
            ],
            FaseCronograma::CONSULTA_PREVIA->value => [
                'plan_inicio' => 'legal_plan_ini',
                'plan_fim' => 'legal_plan_fim',
                'real_inicio' => 'legal_realizado_ini',
                'real_fim' => 'legal_realizado_fim',
                'status' => 'legal_status',
            ],
            FaseCronograma::BRIEFING->value => [
                'plan_inicio' => 'brief_plan_lay_inicio',
                'plan_fim' => 'brief_plan_lay_fim',
                'real_inicio' => 'brief_real_lay_inicio',
                'real_fim' => 'brief_real_lay_fim',
                'status' => 'brief_status',
            ],
            FaseCronograma::LAYOUT->value => [
                'plan_inicio' => 'brief_plan_lay_inicio',
                'plan_fim' => 'brief_plan_lay_fim',
                'real_inicio' => 'brief_real_lay_inicio',
                'real_fim' => 'brief_real_lay_fim',
                'status' => 'brief_status',
            ],
            FaseCronograma::ORDEM_INVESTIMENTO->value => [
                'plan_inicio' => 'ordem_planej_ini',
                'plan_fim' => 'ordem_planej_fim',
                'real_inicio' => 'ordem_realizado',
                'real_fim' => 'ordem_realizado_fim',
                'status' => 'ordem_status',
            ],
            FaseCronograma::START_PROJETOS_EXECUTIVOS->value => [
                'plan_inicio' => 'proj_plan_ini',
                'plan_fim' => 'proj_plan_ini',
                'real_inicio' => 'proj_real_ini',
                'real_fim' => 'proj_real_ini',
                'status' => 'proj_status',
            ],
            FaseCronograma::EXECUTIVO->value => [
                'plan_inicio' => 'proj_plan_ini',
                'plan_fim' => 'proj_plan_fim',
                'real_inicio' => 'proj_real_ini',
                'real_fim' => 'proj_real_fim',
                'status' => 'proj_status',
            ],
            FaseCronograma::ORCAMENTOS->value => [
                'plan_inicio' => 'orca_planejado_ini',
                'plan_fim' => 'orca_planejado_fim',
                'real_inicio' => 'orca_real_ini',
                'real_fim' => 'orca_real_fim',
                'status' => 'orca_status',
            ],
            FaseCronograma::PRAZO_LEGAL->value => [
                'plan_inicio' => 'legal_plan_ini',
                'plan_fim' => 'legal_plan_fim',
                'real_inicio' => 'legal_realizado_ini',
                'real_fim' => 'legal_realizado_fim',
                'status' => 'legal_status',
            ],
            FaseCronograma::POSSE->value => [
                'plan_inicio' => 'data_posse',
                'plan_fim' => 'data_posse',
                'real_inicio' => null,
                'real_fim' => null,
                'status' => 'posse_status',
            ],
            FaseCronograma::IMPLANTACAO->value => [
                'plan_inicio' => 'imp_inicio',
                'plan_fim' => 'imp_fim',
                'real_inicio' => null,
                'real_fim' => null,
                'status' => null,
            ],
            FaseCronograma::INAUGURACAO->value => [
                'plan_inicio' => 'inauguracao',
                'plan_fim' => 'inauguracao',
                'real_inicio' => null,
                'real_fim' => null,
                'status' => null,
            ],
        ];
    }

    /**
     * Fases canônicas que fazem escrita de volta em projetos. Para cada grupo
     * de campos legacy compartilhados entre várias fases, apenas uma é
     * escolhida como "dona" do reverse sync.
     *
     *   cad_*      → INICIO_PROJETO (âncora dos templates forward)
     *   legal_*    → PRAZO_LEGAL    (fase com duração; CONSULTA_PREVIA usa os
     *                                mesmos campos mas é subordinada)
     *   ordem_*    → ORDEM_INVESTIMENTO (semântica clara; CODIGO_ORACLE é
     *                                   alias de marco)
     *   brief_*    → LAYOUT         (fase com duração; BRIEFING é marco 0d)
     *   proj_*     → EXECUTIVO      (fase com duração; START é marco 0d)
     *
     * @return array<string, array<string, ?string>>
     */
    public static function reverse(): array
    {
        $forward = self::forward();

        return [
            FaseCronograma::INICIO_PROJETO->value => $forward[FaseCronograma::INICIO_PROJETO->value],
            FaseCronograma::ASSINATURA_CONTRATO->value => $forward[FaseCronograma::ASSINATURA_CONTRATO->value],
            FaseCronograma::VISITA_TECNICA->value => $forward[FaseCronograma::VISITA_TECNICA->value],
            FaseCronograma::LAYOUT->value => $forward[FaseCronograma::LAYOUT->value],
            FaseCronograma::ORDEM_INVESTIMENTO->value => $forward[FaseCronograma::ORDEM_INVESTIMENTO->value],
            FaseCronograma::EXECUTIVO->value => $forward[FaseCronograma::EXECUTIVO->value],
            FaseCronograma::ORCAMENTOS->value => $forward[FaseCronograma::ORCAMENTOS->value],
            FaseCronograma::PRAZO_LEGAL->value => $forward[FaseCronograma::PRAZO_LEGAL->value],
            FaseCronograma::POSSE->value => $forward[FaseCronograma::POSSE->value],
            FaseCronograma::IMPLANTACAO->value => $forward[FaseCronograma::IMPLANTACAO->value],
            FaseCronograma::INAUGURACAO->value => $forward[FaseCronograma::INAUGURACAO->value],
        ];
    }

    /**
     * Todos os campos do Projeto referenciados pelo mapa forward. Útil para
     * detectar em ProjetoSyncObserver::saved() se o save atual tocou algum
     * campo relevante e evitar trabalho desnecessário.
     *
     * @return list<string>
     */
    public static function projetoFieldsObservados(): array
    {
        $campos = [];
        foreach (self::forward() as $mapa) {
            foreach ($mapa as $campo) {
                if ($campo !== null) {
                    $campos[$campo] = true;
                }
            }
        }

        return array_keys($campos);
    }
}
