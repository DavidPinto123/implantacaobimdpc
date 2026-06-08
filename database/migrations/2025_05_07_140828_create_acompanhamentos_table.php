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
        Schema::create('acompanhamentos', function (Blueprint $table) {
            $table->id();
            $table->string('sigla')->nullable();
            $table->string('nova_sigla')->nullable();
            $table->string('nome_mkt')->nullable();
            $table->string('tipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('escopo')->nullable();
            $table->string('pipeline')->nullable();
            $table->string('status')->nullable();
            $table->date('inicio_obra')->nullable();
            $table->date('entrega_obra')->nullable();
            $table->date('implantacao')->nullable();
            $table->date('inauguracao')->nullable();
            $table->smallInteger('ano_inauguracao')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cep')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('regiao')->nullable();
            $table->string('pais')->nullable();
            $table->string('razao_social')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('empreendimento_adm')->nullable();
            $table->string('tipo_loja')->nullable();
            $table->string('perfil_loja')->nullable();
            $table->string('tipo_obra')->nullable();
            $table->string('situacao_contratual')->nullable();
            $table->date('data_assinatura_locacao')->nullable();
            $table->date('data_assinatura_distrato')->nullable();
            $table->date('data_encerramento')->nullable();
            $table->date('data_aquisicao')->nullable();
            $table->float('area_contrato')->nullable();
            $table->float('area_util')->nullable();
            $table->float('area_producao')->nullable();
            $table->string('estacionamento')->nullable();
            $table->string('bicicletario')->nullable();
            $table->string('ginastica')->nullable();
            $table->string('spa')->nullable();
            $table->string('smartbike')->nullable();
            $table->string('strong')->nullable();
            $table->string('smartcross')->nullable();
            $table->string('smartbox')->nullable();
            $table->string('smartshape')->nullable();
            $table->string('race')->nullable();
            $table->string('vidya')->nullable();
            $table->string('jabhouse')->nullable();
            $table->string('tonus_gym')->nullable();
            $table->string('one_pilates')->nullable();
            $table->string('velocity')->nullable();
            $table->string('kore')->nullable();
            $table->string('burn')->nullable();
            $table->string('squad')->nullable();
            $table->string('skill_mill')->nullable();
            $table->string('torq')->nullable();
            $table->string('obs')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acompanhamentos');
    }
};
