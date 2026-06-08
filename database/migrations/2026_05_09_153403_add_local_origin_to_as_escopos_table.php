<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->dropUnique(['numero_as']);
            $table->dropUnique(['escopo']);

            $table->foreignId('controle_nota_fiscal_id')
                ->nullable()
                ->after('created_by')
                ->constrained('controle_nota_fiscals')
                ->cascadeOnDelete();
            $table->foreignId('capex_simulacao_item_id')
                ->nullable()
                ->after('controle_nota_fiscal_id')
                ->constrained('capex_simulacao_itens')
                ->nullOnDelete();

            $table->index('numero_as');
            $table->index('escopo');
            $table->unique(
                ['controle_nota_fiscal_id', 'capex_simulacao_item_id'],
                'as_escopos_controle_simulacao_item_unique',
            );
        });
    }

    public function down(): void
    {
        DB::table('as_escopos')
            ->whereNotNull('controle_nota_fiscal_id')
            ->delete();

        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->dropForeign(['capex_simulacao_item_id']);
            $table->dropForeign(['controle_nota_fiscal_id']);
            $table->dropUnique('as_escopos_controle_simulacao_item_unique');
            $table->dropIndex(['numero_as']);
            $table->dropIndex(['escopo']);
            $table->dropColumn(['capex_simulacao_item_id', 'controle_nota_fiscal_id']);

            $table->unique('numero_as');
            $table->unique('escopo');
        });
    }
};
