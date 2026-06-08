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
            // Comentário da Proposta Comercial
            $table->text('anexo_proposta_comercial_comentario')
                ->nullable()
                ->after('anexo_proposta_comercial');

            // Comentário do Contrato Assinado
            $table->text('anexo_contrato_assinado_comentario')
                ->nullable()
                ->after('anexo_contrato_assinado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'anexo_proposta_comercial_comentario',
                'anexo_contrato_assinado_comentario',
            ]);
        });
    }
};
