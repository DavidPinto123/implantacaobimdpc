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
            $table->index(['controle_nota_fiscal_id', 'empresa'], 'cnf_items_controle_empresa_idx');
            $table->index(['controle_nota_fiscal_id', 'valor_global_a'], 'cnf_items_controle_valor_idx');
        });

        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            $table->index(['controle_nota_fiscal_item_id', 'status'], 'cnf_notas_item_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            $table->dropIndex('cnf_notas_item_status_idx');
        });

        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropIndex('cnf_items_controle_empresa_idx');
            $table->dropIndex('cnf_items_controle_valor_idx');
        });
    }
};
