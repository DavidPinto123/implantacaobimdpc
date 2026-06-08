<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona dois campos auxiliares em cronograma_fases para absorver dados
 * legacy que hoje vivem em colunas específicas de projetos sem equivalente
 * normalizado:
 *
 *   data_aprovacao — data de aprovação (ex.: ordem_data_aprov).
 *   metadados      — bucket genérico JSON para campos específicos de 1 fase
 *                    (ex.: posse_engenharia, posse_legalizacao,
 *                    legal_status_consulta_prev, legal_doc_posse).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->date('data_aprovacao')
                ->nullable()
                ->after('status')
                ->comment('Data de aprovação específica da fase (ex.: aprovação da Ordem de Investimento). Nulo quando não aplicável.');

            $table->json('metadados')
                ->nullable()
                ->after('observacoes')
                ->comment('Bucket JSON para atributos específicos de uma fase que não cabem em colunas normalizadas (ex.: posse_engenharia, posse_legalizacao, legal_doc_posse).');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn(['data_aprovacao', 'metadados']);
        });
    }
};
