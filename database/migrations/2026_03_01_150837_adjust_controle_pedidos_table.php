<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_pedidos', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | REMOVER COLUNAS QUE NÃO PERTENCEM AQUI
            |--------------------------------------------------------------------------
            */

            $table->dropColumn([
                'endereco',
                'cidade',
                'uf',
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADICIONAR COLUNAS DO CONTROLE
            |--------------------------------------------------------------------------
            */

            $table->date('elaboracao_contrato')->nullable()->after('projeto_id');

            $table->string('instal_ar')->nullable();
            $table->string('luminarias')->nullable();
            $table->string('instal_aquecedores')->nullable();
            $table->string('fachada')->nullable();
            $table->string('marcenaria')->nullable();
            $table->string('construtora_sugestao')->nullable();
            $table->string('divisorias')->nullable();
            $table->string('contr_ar')->nullable();
            $table->string('ginastica')->nullable();

            $table->decimal('valor_oi', 15, 2)->nullable();
            $table->decimal('valor_realizado', 15, 2)->nullable();
            $table->decimal('realizado_nf', 15, 2)->nullable();
            $table->decimal('saldo', 15, 2)->nullable();

            $table->string('situacao')->nullable();
            $table->string('responsavel_orc')->nullable();
            $table->string('gestor_obra')->nullable();

            $table->string('tamanho')->nullable();
            $table->integer('numero')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('controle_pedidos', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | RECRIAR COLUNAS REMOVIDAS
            |--------------------------------------------------------------------------
            */

            $table->string('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | REMOVER COLUNAS ADICIONADAS
            |--------------------------------------------------------------------------
            */

            $table->dropColumn([
                'elaboracao_contrato',
                'instal_ar',
                'luminarias',
                'instal_aquecedores',
                'fachada',
                'marcenaria',
                'construtora_sugestao',
                'divisorias',
                'contr_ar',
                'ginastica',
                'valor_oi',
                'valor_realizado',
                'realizado_nf',
                'saldo',
                'situacao',
                'responsavel_orc',
                'gestor_obra',
                'tamanho',
                'numero',
            ]);
        });
    }
};
