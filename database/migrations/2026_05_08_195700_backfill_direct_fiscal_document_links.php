<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillAutorizacaoServicoLinks();
        $this->backfillAsaLinks();
        $this->backfillNotaLinks();
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversible.
    }

    private function backfillAutorizacaoServicoLinks(): void
    {
        DB::statement(<<<'SQL'
            UPDATE autorizacao_servicos AS autorizacoes
            INNER JOIN controle_nota_fiscal_items AS itens
                ON itens.autorizacao_servico_id = autorizacoes.id
            SET autorizacoes.controle_nota_fiscal_item_id = itens.id
            WHERE autorizacoes.controle_nota_fiscal_item_id IS NULL
        SQL);
    }

    private function backfillAsaLinks(): void
    {
        DB::statement(<<<'SQL'
            UPDATE asas
            INNER JOIN (
                SELECT
                    asas_origem.id AS asa_id,
                    MIN(auxiliares.id) AS auxiliar_id
                FROM asas AS asas_origem
                INNER JOIN controle_nota_fiscals AS controles
                    ON controles.asa_id = asas_origem.id
                    OR (
                        controles.elaboracao_aditivo_id IS NOT NULL
                        AND controles.elaboracao_aditivo_id = asas_origem.elaboracao_aditivo_id
                    )
                INNER JOIN controle_nota_fiscal_auxiliares AS auxiliares
                    ON auxiliares.controle_nota_fiscal_id = controles.id
                WHERE asas_origem.controle_nota_fiscal_auxiliar_id IS NULL
                    AND (
                        (
                            asas_origem.codigo_as_emitida IS NOT NULL
                            AND auxiliares.numero_as = asas_origem.codigo_as_emitida
                        )
                        OR (
                            asas_origem.numero_asa IS NOT NULL
                            AND auxiliares.numero_as = asas_origem.numero_asa
                        )
                    )
                GROUP BY asas_origem.id
            ) AS vinculos
                ON vinculos.asa_id = asas.id
            SET asas.controle_nota_fiscal_auxiliar_id = vinculos.auxiliar_id
            WHERE asas.controle_nota_fiscal_auxiliar_id IS NULL
        SQL);
    }

    private function backfillNotaLinks(): void
    {
        DB::statement(<<<'SQL'
            UPDATE controle_nota_fiscal_notas AS notas
            INNER JOIN controle_nota_fiscal_items AS itens
                ON itens.id = notas.controle_nota_fiscal_item_id
            INNER JOIN autorizacao_servicos AS autorizacoes
                ON autorizacoes.controle_nota_fiscal_item_id = itens.id
            SET notas.autorizacao_servico_id = autorizacoes.id
            WHERE notas.autorizacao_servico_id IS NULL
                AND notas.controle_nota_fiscal_item_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE controle_nota_fiscal_notas AS notas
            INNER JOIN (
                SELECT
                    auxiliares.id AS auxiliar_id,
                    MIN(asas.id) AS asa_id
                FROM controle_nota_fiscal_auxiliares AS auxiliares
                INNER JOIN asas
                    ON asas.controle_nota_fiscal_auxiliar_id = auxiliares.id
                GROUP BY auxiliares.id
            ) AS vinculos
                ON vinculos.auxiliar_id = notas.controle_nota_fiscal_auxiliar_id
            SET notas.asa_id = vinculos.asa_id
            WHERE notas.asa_id IS NULL
                AND notas.controle_nota_fiscal_auxiliar_id IS NOT NULL
        SQL);
    }
};
