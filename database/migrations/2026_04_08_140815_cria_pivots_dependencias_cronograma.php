<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_template_fase_dependencias', function (Blueprint $table) {
            $table->comment('Dependências de uma fase de template. Uma fase pode depender de múltiplas outras fases; cada linha representa uma aresta do grafo de dependências usado no cálculo topológico das datas.');

            $table->id();
            $table->unsignedBigInteger('cronograma_template_fase_id')
                ->comment('Fase do template que possui a dependência');
            $table->foreign('cronograma_template_fase_id', 'ctfd_template_fase_id_fk')
                ->references('id')
                ->on('cronograma_template_fases')
                ->cascadeOnDelete();
            $table->string('depende_de_fase', 50)->comment('Fase da qual esta depende (value do enum FaseCronograma)');
            $table->string('gatilho', 20)->default('fim_anterior')->comment('Define se a base do cálculo é o início (inicio_anterior) ou o fim (fim_anterior) da fase-dependência');
            $table->smallInteger('gap_dias')->default(1)->comment('Deslocamento em dias aplicado a partir do gatilho. Default 1 representa "dia seguinte à finalização".');
            $table->timestamps();

            $table->unique(['cronograma_template_fase_id', 'depende_de_fase'], 'ctfd_fase_depende_unique');
            $table->index('depende_de_fase', 'ctfd_depende_de_fase_idx');
        });

        Schema::create('cronograma_fase_dependencias', function (Blueprint $table) {
            $table->comment('Override local de dependências de uma fase de cronograma de obra. Permite adicionar/remover dependências por obra sem afetar o template de origem.');

            $table->id();
            $table->foreignId('cronograma_fase_id')
                ->constrained('cronograma_fases')
                ->cascadeOnDelete()
                ->comment('Fase da obra que possui a dependência');
            $table->string('depende_de_fase', 50)->comment('Fase da qual esta depende (value do enum FaseCronograma)');
            $table->string('gatilho', 20)->default('fim_anterior')->comment('Define se a base do cálculo é o início (inicio_anterior) ou o fim (fim_anterior) da fase-dependência');
            $table->smallInteger('gap_dias')->default(1)->comment('Deslocamento em dias aplicado a partir do gatilho. Default 1 representa "dia seguinte à finalização".');
            $table->boolean('regra_customizada')->default(true)->comment('Marca que esta linha sobrescreve a dependência herdada do template');
            $table->timestamps();

            $table->unique(['cronograma_fase_id', 'depende_de_fase'], 'cfd_fase_depende_unique');
            $table->index('depende_de_fase', 'cfd_depende_de_fase_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fase_dependencias');
        Schema::dropIfExists('cronograma_template_fase_dependencias');
    }
};
