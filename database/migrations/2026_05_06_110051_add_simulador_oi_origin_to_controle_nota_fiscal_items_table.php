<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->foreignId('capex_simulacao_item_id')
                ->nullable()
                ->after('autorizacao_servico_id')
                ->constrained('capex_simulacao_itens')
                ->nullOnDelete();

            $table->decimal('valor_estimado_as_simulador', 15, 2)
                ->nullable()
                ->after('valor_estimado_as');

            $table->boolean('valor_estimado_as_editado_manualmente')
                ->default(false)
                ->after('valor_estimado_as_simulador');

            $table->index(
                ['controle_nota_fiscal_id', 'as_escopo_id', 'numero_complemento'],
                'cnf_item_controle_escopo_compl_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropIndex('cnf_item_controle_escopo_compl_index');
            $table->dropConstrainedForeignId('capex_simulacao_item_id');
            $table->dropColumn([
                'valor_estimado_as_simulador',
                'valor_estimado_as_editado_manualmente',
            ]);
        });
    }
};
