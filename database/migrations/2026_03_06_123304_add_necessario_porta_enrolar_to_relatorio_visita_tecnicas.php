<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            $table->tinyInteger('necessario_porta_enrolar')
                ->nullable()
                ->after('prever_porta_enrolar');

            $table->text('descricao_necessario_porta_enrolar')
                ->nullable()
                ->after('necessario_porta_enrolar');

            $table->json('foto_necessario_porta_enrolar')
                ->nullable()
                ->after('descricao_necessario_porta_enrolar');

        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            $table->dropColumn([
                'necessario_porta_enrolar',
                'descricao_necessario_porta_enrolar',
                'foto_necessario_porta_enrolar',
            ]);

        });
    }
};
