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
        Schema::table('projetos', function (Blueprint $table) {
            $table->json('anexo_pmo_cronograma')->nullable();
            $table->longText('comentario_pmo_cronograma')->nullable();

            $table->json('anexo_pmo_termo_abertura')->nullable();
            $table->longText('comentario_pmo_termo_abertura')->nullable();

            $table->json('anexo_planejamento_plano')->nullable();
            $table->longText('planejamento_plano_comentario')->nullable();

            $table->json('anexo_planejamento_estudo')->nullable();
            $table->longText('planejamento_estudo_comentario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'anexo_pmo_cronograma',
                'comentario_pmo_cronograma',
                'anexo_pmo_termo_abertura',
                'comentario_pmo_termo_abertura',
                'anexo_planejamento_plano',
                'planejamento_plano_comentario',
                'anexo_planejamento_estudo',
                'planejamento_estudo_comentario',
            ]);
        });
    }
};
