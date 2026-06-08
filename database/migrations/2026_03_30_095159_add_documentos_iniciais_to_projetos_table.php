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
            $table->json('anexo_matricula_iptu')->nullable()->after('anexos');
            $table->json('anexo_habite_se')->nullable()->after('anexo_matricula_iptu');
            $table->json('anexo_avcb')->nullable()->after('anexo_habite_se');
            $table->json('anexo_projeto')->nullable()->after('anexo_avcb');
            $table->json('anexo_convencao_condominio')->nullable()->after('anexo_projeto');
            $table->json('anexo_regime_interno')->nullable()->after('anexo_convencao_condominio');
            $table->json('anexo_normas_gerais')->nullable()->after('anexo_regime_interno');
            $table->json('anexo_outros_documentos')->nullable()->after('anexo_normas_gerais');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'anexo_matricula_iptu',
                'anexo_habite_se',
                'anexo_avcb',
                'anexo_projeto',
                'anexo_convencao_condominio',
                'anexo_regime_interno',
                'anexo_normas_gerais',
                'anexo_outros_documentos',
            ]);
        });
    }
};
