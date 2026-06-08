<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices para colunas frequentemente filtradas na tabela obras
        Schema::table('obras', function (Blueprint $table) {
            // Índices para colunas string (padrão)
            $table->index('status');
            $table->index('relatorio_fotografico');
            $table->index('termo_de_posse');
            $table->index('cronograma_implantacao');
            $table->index('homologados_em_atraso');
            $table->index('email_solicitacao_cl');
            $table->index('envio_qrcod');
            $table->index('checklist_manutencao');
        });

        // Índices para colunas TEXT (com comprimento máximo via raw SQL)
        DB::statement('CREATE INDEX obras_energia_index ON obras (energia(100))');
        DB::statement('CREATE INDEX obras_agua_index ON obras (agua(100))');
        DB::statement('CREATE INDEX obras_gas_index ON obras (gas(100))');

        // Índices para relacionamentos
        Schema::table('obras', function (Blueprint $table) {
            $table->index('projeto_id');
        });

        // Índices na tabela colunas_personalizadas para eager-load
        Schema::table('colunas_personalizadas', function (Blueprint $table) {
            $table->index('obra_id');
            $table->index('nome');
            $table->index(['obra_id', 'nome']);
        });

        // Índice na tabela projetos para filtros relacionados
        Schema::table('projetos', function (Blueprint $table) {
            $table->index('marca');
            $table->index('tipo_imovel');
            $table->index('locacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['relatorio_fotografico']);
            $table->dropIndex(['termo_de_posse']);
            $table->dropIndex(['cronograma_implantacao']);
            $table->dropIndex(['homologados_em_atraso']);
            $table->dropIndex(['email_solicitacao_cl']);
            $table->dropIndex(['envio_qrcod']);
            $table->dropIndex(['checklist_manutencao']);
            $table->dropIndex(['projeto_id']);
        });

        // Drop indices para colunas TEXT via raw SQL
        DB::statement('DROP INDEX obras_energia_index ON obras');
        DB::statement('DROP INDEX obras_agua_index ON obras');
        DB::statement('DROP INDEX obras_gas_index ON obras');

        Schema::table('coluna_personalizada', function (Blueprint $table) {
            $table->dropIndex(['obra_id']);
            $table->dropIndex(['nome']);
            $table->dropIndex(['obra_id', 'nome']);
        });

        Schema::table('projetos', function (Blueprint $table) {
            $table->dropIndex(['marca']);
            $table->dropIndex(['tipo_imovel']);
            $table->dropIndex(['locacao']);
        });
    }
};
