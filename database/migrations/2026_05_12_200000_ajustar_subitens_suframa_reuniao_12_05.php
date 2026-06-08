<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reunião 12/05: CNPJ Suframa deixou de ser subitem da fase SUFRAMA
 * (migrou para o bloco CNPJ_LEGALIZACAO). PIN e Compras perderam o sufixo
 * "Suframa" no rótulo.
 *
 * Aplica-se a:
 *  - cronograma_template_fase_itens  (templates)
 *  - cronograma_fase_itens           (obras reais)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1) Templates ---------------------------------------------------
            $faseIdsTplSuframa = DB::table('cronograma_template_fases')
                ->where('fase', 'suframa')
                ->pluck('id');

            $itensCnpjTpl = DB::table('cronograma_template_fase_itens')
                ->whereIn('cronograma_template_fase_id', $faseIdsTplSuframa)
                ->where('titulo', 'CNPJ Suframa')
                ->pluck('id');

            if ($itensCnpjTpl->isNotEmpty()) {
                DB::table('cronograma_template_fase_item_dependencias')
                    ->whereIn('cronograma_template_fase_item_id', $itensCnpjTpl)
                    ->delete();

                DB::table('cronograma_template_fase_item_dependencias')
                    ->whereIn('depende_de_item_id', $itensCnpjTpl)
                    ->delete();

                DB::table('cronograma_template_fase_itens')
                    ->whereIn('id', $itensCnpjTpl)
                    ->delete();
            }

            DB::table('cronograma_template_fase_itens')
                ->whereIn('cronograma_template_fase_id', $faseIdsTplSuframa)
                ->where('titulo', 'PIN Suframa')
                ->update(['titulo' => 'PIN', 'ordem' => 0]);

            DB::table('cronograma_template_fase_itens')
                ->whereIn('cronograma_template_fase_id', $faseIdsTplSuframa)
                ->where('titulo', 'Compras Suframa')
                ->update(['titulo' => 'Compras', 'ordem' => 1]);

            // 2) Obras reais -------------------------------------------------
            $faseIdsObraSuframa = DB::table('cronograma_fases')
                ->where('fase', 'suframa')
                ->pluck('id');

            $itensCnpjObra = DB::table('cronograma_fase_itens')
                ->whereIn('cronograma_fase_id', $faseIdsObraSuframa)
                ->where('titulo', 'CNPJ Suframa')
                ->pluck('id');

            if ($itensCnpjObra->isNotEmpty()) {
                DB::table('cronograma_fase_itens')
                    ->whereIn('id', $itensCnpjObra)
                    ->delete();
            }

            DB::table('cronograma_fase_itens')
                ->whereIn('cronograma_fase_id', $faseIdsObraSuframa)
                ->where('titulo', 'PIN Suframa')
                ->update(['titulo' => 'PIN', 'ordem' => 0]);

            DB::table('cronograma_fase_itens')
                ->whereIn('cronograma_fase_id', $faseIdsObraSuframa)
                ->where('titulo', 'Compras Suframa')
                ->update(['titulo' => 'Compras', 'ordem' => 1]);
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Esta migration é one-way (consolidou subitens SUFRAMA). '
            .'Para reverter, restaure o banco a partir de um backup pré-migration.'
        );
    }
};
