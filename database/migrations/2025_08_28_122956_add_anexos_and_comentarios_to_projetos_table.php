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
            $table->json('anexo_consulta_previa')->nullable()->after('planejamento_estudo_comentario');
            $table->longText('anexo_consulta_previa_comentario')->nullable();

            $table->json('anexo_estudoviabilidade')->nullable();
            $table->longText('anexo_estudoviabilidade_comentario')->nullable();

            $table->json('anexo_visita_tecnica')->nullable();
            $table->longText('anexo_visita_tecnica_comentario')->nullable();

            $table->json('anexo_projetos_adicionais')->nullable();
            $table->longText('anexo_projetos_adicionais_comentario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'anexo_consulta_previa',
                'anexo_consulta_previa_comentario',
                'anexo_estudoviabilidade',
                'anexo_estudoviabilidade_comentario',
                'anexo_visita_tecnica',
                'anexo_visita_tecnica_comentario',
                'anexo_projetos_adicionais',
                'anexo_projetos_adicionais_comentario',
            ]);
        });
    }
};
