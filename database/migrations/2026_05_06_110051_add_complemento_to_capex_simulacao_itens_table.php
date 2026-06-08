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
        Schema::table('capex_simulacao_itens', function (Blueprint $table): void {
            $table->string('numero_complemento', 10)
                ->default('')
                ->after('as_escopo_id');

            $table->index(
                ['capex_simulacao_id', 'as_escopo_id', 'numero_complemento'],
                'capex_sim_item_sim_escopo_compl_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capex_simulacao_itens', function (Blueprint $table): void {
            $table->dropIndex('capex_sim_item_sim_escopo_compl_index');
            $table->dropColumn('numero_complemento');
        });
    }
};
