<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->text('condicoes_imovel')->nullable()->after('endereco');
            $table->string('pavimento')->nullable()->after('condicoes_imovel');
            $table->string('empreendimento')->nullable()->after('pavimento');
            $table->string('locacao')->nullable()->after('empreendimento');
            $table->string('contato_responsavel')->nullable()->after('locacao');
            $table->string('etapa_contrato')->nullable()->after('contato_responsavel');
        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn(['condicoes_imovel', 'pavimento', 'empreendimento', 'locacao', 'contato_responsavel', 'etapa_contrato']);
        });
    }
};
