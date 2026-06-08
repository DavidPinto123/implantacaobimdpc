<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'autorizacao_servico_id')) {
                $table->foreignId('autorizacao_servico_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('autorizacao_servicos')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'asa_id')) {
                $table->foreignId('asa_id')
                    ->nullable()
                    ->after('autorizacao_servico_id')
                    ->constrained('asas')
                    ->nullOnDelete();
            }
        });

        $this->backfillDocumentosFaturaveis();
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'asa_id')) {
                $table->dropForeignKeyIfExists('controle_nota_fiscal_notas_asa_id_foreign');
                $table->dropColumn('asa_id');
            }

            if (Schema::hasColumn('controle_nota_fiscal_notas', 'autorizacao_servico_id')) {
                $table->dropForeignKeyIfExists('controle_nota_fiscal_notas_autorizacao_servico_id_foreign');
                $table->dropColumn('autorizacao_servico_id');
            }
        });
    }

    private function backfillDocumentosFaturaveis(): void
    {
        DB::table('controle_nota_fiscal_notas')
            ->join(
                'controle_nota_fiscal_items',
                'controle_nota_fiscal_items.id',
                '=',
                'controle_nota_fiscal_notas.controle_nota_fiscal_item_id'
            )
            ->whereNotNull('controle_nota_fiscal_items.autorizacao_servico_id')
            ->update([
                'controle_nota_fiscal_notas.autorizacao_servico_id' => DB::raw('controle_nota_fiscal_items.autorizacao_servico_id'),
            ]);

        DB::table('controle_nota_fiscal_notas')
            ->join(
                'controle_nota_fiscal_auxiliares',
                'controle_nota_fiscal_auxiliares.id',
                '=',
                'controle_nota_fiscal_notas.controle_nota_fiscal_auxiliar_id'
            )
            ->join(
                'asas',
                'asas.controle_nota_fiscal_auxiliar_id',
                '=',
                'controle_nota_fiscal_auxiliares.id'
            )
            ->update([
                'controle_nota_fiscal_notas.asa_id' => DB::raw('asas.id'),
            ]);
    }
};
