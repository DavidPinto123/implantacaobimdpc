<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class DateCalc
{
    /**
     * Calcula a data de fim a partir de uma data de início e um prazo em dias.
     *
     * @param  DateTimeInterface|string|null  $start  Data de início (DateTime ou string, ex: 'Y-m-d')
     * @param  int|string|null  $prazoDias  Prazo em dias (numérico)
     * @param  bool  $inclusive  true => contagem inclusiva (1 dia = mesmo dia)
     * @param  string  $format  Formato de saída (padrão 'Y-m-d')
     * @return string|null Data final formatada ou null se não der pra calcular
     */
    public static function endDate(
        DateTimeInterface|string|null $start,
        int|string|null $prazoDias,
        bool $inclusive = false,
        string $format = 'Y-m-d',
    ): ?string {
        if (blank($start) || ! is_numeric($prazoDias)) {
            return null;
        }

        $dias = (int) $prazoDias;
        if ($inclusive) {
            // Ex.: 1=>0, 2=>1, etc.
            $dias = max(0, $dias - 1);
        }

        $ini = $start instanceof DateTimeInterface
            ? CarbonImmutable::instance($start)
            : CarbonImmutable::parse((string) $start); // espera algo tipo 'Y-m-d'

        return $ini->addDays($dias)->format($format);
    }

    /**
     * Lê campos genéricos do model e escreve o campo de fim.
     *
     * @param  object  $model  Instância do Eloquent (ou similar)
     * @param  string  $startAttr  Nome do atributo de início (ex: 'cad_plan_inicio')
     * @param  string  $daysAttr  Nome do atributo de prazo (ex: 'cad_prazo')
     * @param  string  $endAttr  Nome do atributo de fim (ex: 'cad_plan_fim')
     * @param  bool  $inclusive  Contagem inclusiva?
     * @param  string  $format  Formato de saída
     */
    public static function applyToModel(
        object $model,
        string $startAttr,
        string $daysAttr,
        string $endAttr,
        bool $inclusive = false,
        string $format = 'Y-m-d',
    ): void {
        $model->{$endAttr} = self::endDate(
            $model->{$startAttr} ?? null,
            $model->{$daysAttr} ?? null,
            $inclusive,
            $format,
        );
    }
}
