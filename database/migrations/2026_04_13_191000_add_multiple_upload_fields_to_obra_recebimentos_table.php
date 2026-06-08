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
        Schema::table('obra_recebimentos', function (Blueprint $table) {
            $table->json('foto_entrega_paths')->nullable()->after('foto_entrega_nome');
            $table->json('foto_entrega_nomes')->nullable()->after('foto_entrega_paths');
            $table->json('nota_fiscal_paths')->nullable()->after('nota_fiscal_nome');
            $table->json('nota_fiscal_nomes')->nullable()->after('nota_fiscal_paths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obra_recebimentos', function (Blueprint $table) {
            $table->dropColumn([
                'foto_entrega_paths',
                'foto_entrega_nomes',
                'nota_fiscal_paths',
                'nota_fiscal_nomes',
            ]);
        });
    }
};
