<?php

namespace App\Support;

use App\Models\Projeto;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Limites operacionais do cronograma de expansão.
 *
 * Definidos pela Carol/Letícia na reunião 07/05 para servirem como
 * alertas visuais (não bloqueios) durante o planejamento e a operação:
 *
 * - Briefing → Início de Obras: mínimo 75 dias úteis ao time PMO.
 * - Início de Projeto → Posse: 3 meses (≈83 dias) é o ideal recomendado
 *   pelo comercial para o ciclo de captação até a posse.
 *
 * Quando o prazo cai abaixo do mínimo, a UI mostra um banner âmbar e
 * destaca as fases não concluídas que ficaram em risco.
 */
final class CronogramaLimites
{
    public const DIAS_MIN_BRIEFING_INICIO_OBRAS = 75;

    public const DIAS_IDEAL_INICIO_PROJETO_POSSE = 83;

    /**
     * Calcula o intervalo (em dias corridos) entre duas datas previstas.
     * Retorna null se alguma das datas estiver vazia.
     */
    public static function diasEntre(?CarbonInterface $de, ?CarbonInterface $ate): ?int
    {
        if (! $de || ! $ate) {
            return null;
        }

        return (int) Carbon::parse($de)->diffInDays(Carbon::parse($ate), absolute: false);
    }

    /**
     * Avalia os limites para o projeto dado e devolve um resumo com os
     * cenários violados. Cada item tem `violado`, `dias_atuais`, `limite`,
     * `mensagem`.
     *
     * @return array{
     *     briefing_obras: array{violado: bool, dias_atuais: ?int, limite: int, mensagem: ?string},
     *     inicio_posse: array{violado: bool, dias_atuais: ?int, limite: int, mensagem: ?string},
     * }
     */
    public static function avaliar(Projeto $projeto): array
    {
        $diasBriefingObras = self::diasEntre(
            $projeto->brief_plan_lay_inicio,
            $projeto->inicio_obra,
        );

        $diasInicioPosse = self::diasEntre(
            $projeto->cad_plan_inicio,
            $projeto->data_posse,
        );

        return [
            'briefing_obras' => [
                'violado' => $diasBriefingObras !== null && $diasBriefingObras < self::DIAS_MIN_BRIEFING_INICIO_OBRAS,
                'dias_atuais' => $diasBriefingObras,
                'limite' => self::DIAS_MIN_BRIEFING_INICIO_OBRAS,
                'mensagem' => $diasBriefingObras !== null && $diasBriefingObras < self::DIAS_MIN_BRIEFING_INICIO_OBRAS
                    ? "Prazo de {$diasBriefingObras} dias entre Briefing e Início de Obras — abaixo do mínimo de ".self::DIAS_MIN_BRIEFING_INICIO_OBRAS.' dias.'
                    : null,
            ],
            'inicio_posse' => [
                'violado' => $diasInicioPosse !== null && $diasInicioPosse < self::DIAS_IDEAL_INICIO_PROJETO_POSSE,
                'dias_atuais' => $diasInicioPosse,
                'limite' => self::DIAS_IDEAL_INICIO_PROJETO_POSSE,
                'mensagem' => $diasInicioPosse !== null && $diasInicioPosse < self::DIAS_IDEAL_INICIO_PROJETO_POSSE
                    ? "Posse a {$diasInicioPosse} dias do Início de Projeto — abaixo do ideal de ".self::DIAS_IDEAL_INICIO_PROJETO_POSSE.' dias.'
                    : null,
            ],
        ];
    }

    /**
     * Atalho: o projeto tem algum limite violado?
     */
    public static function temViolacao(Projeto $projeto): bool
    {
        $resumo = self::avaliar($projeto);

        return $resumo['briefing_obras']['violado'] || $resumo['inicio_posse']['violado'];
    }
}
