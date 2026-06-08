<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Breaking change de semântica do gatilho FIM_ANTERIOR:
 *
 *  ANTES: gap é literal a partir do dia final da dependência.
 *         → gap=0 significa "mesmo dia do fim" (sobrepõe).
 *         → gap=1 significa "dia seguinte ao fim" (natural).
 *
 *  AGORA: gap é relativo ao boundary natural (dia seguinte ao fim).
 *         → gap=0 significa "dia seguinte ao fim" (natural).
 *         → Para "mesmo dia do fim", usa-se o novo gatilho
 *           FIM_ANTERIOR_MESMO_DIA (sobreposição explícita).
 *
 * Regras de migração (para preservar datas atuais):
 *   - fim_anterior + gap=0  → fim_anterior_mesmo_dia + gap=0
 *   - fim_anterior + gap>0  → fim_anterior + gap=gap-1
 *   - fim_anterior + gap<0  → fim_anterior_mesmo_dia + gap=gap
 *
 * Aplicada nas duas tabelas de dependências do domínio:
 *   - cronograma_template_fase_dependencias
 *   - cronograma_fase_dependencias
 */
return new class extends Migration
{
    private const TABELAS = [
        'cronograma_template_fase_dependencias',
        'cronograma_fase_dependencias',
    ];

    public function up(): void
    {
        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->string('gatilho', 30)->change();
            });

            DB::table($tabela)
                ->where('gatilho', 'fim_anterior')
                ->where('gap_dias', 0)
                ->update(['gatilho' => 'fim_anterior_mesmo_dia']);

            DB::table($tabela)
                ->where('gatilho', 'fim_anterior')
                ->where('gap_dias', '<', 0)
                ->update(['gatilho' => 'fim_anterior_mesmo_dia']);

            DB::table($tabela)
                ->where('gatilho', 'fim_anterior')
                ->where('gap_dias', '>', 0)
                ->update(['gap_dias' => DB::raw('gap_dias - 1')]);
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            // Reverter: fim_anterior_mesmo_dia com gap>=0 tinha fim_anterior gap inalterado
            // (o gap=0 original vira fim_anterior gap=0 de volta). Para gap<0 o original
            // já era fim_anterior gap<0, não tem como diferenciar — aceitamos como aproximação.
            DB::table($tabela)
                ->where('gatilho', 'fim_anterior_mesmo_dia')
                ->update(['gatilho' => 'fim_anterior']);

            // Os fim_anterior com gap>=0 atual foram gerados por decremento — reincrementa.
            DB::table($tabela)
                ->where('gatilho', 'fim_anterior')
                ->where('gap_dias', '>=', 0)
                ->update(['gap_dias' => DB::raw('gap_dias + 1')]);

            Schema::table($tabela, function (Blueprint $table) {
                $table->string('gatilho', 20)->change();
            });
        }
    }
};
