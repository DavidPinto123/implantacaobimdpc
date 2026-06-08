<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_template_fases', function (Blueprint $table) {
            $table->comment('Regras de tempo por fase dentro de um template de cronograma (duração, dependência, gatilho e offset)');

            $table->id();
            $table->foreignId('cronograma_template_id')
                ->constrained('cronograma_templates')
                ->cascadeOnDelete()
                ->comment('Template pai');
            $table->string('fase', 50)->comment('Fase do cronograma (enum FaseCronograma)');
            $table->tinyInteger('ordem')->comment('Ordem de encadeamento dentro do template');
            $table->smallInteger('duracao_dias')->unsigned()->default(0)->comment('Duração da fase em dias (0 quando é marco sem duração)');
            $table->string('tipo_dias', 10)->default('corridos')->comment('Como contar a duração: uteis ou corridos');
            $table->string('depende_de_fase', 50)->nullable()->comment('Fase da qual esta depende. Se nulo, depende da fase anterior por ordem ou da âncora se for a primeira');
            $table->string('gatilho', 20)->default('fim_anterior')->comment('Define se o ciclo começa quando a fase-dependência inicia (inicio_anterior) ou termina (fim_anterior)');
            $table->smallInteger('gap_dias')->default(0)->comment('Offset em dias a partir do gatilho. Suporta valores negativos (antecipação)');
            $table->text('observacoes')->nullable()->comment('Notas livres sobre a regra desta fase');
            $table->timestamps();

            $table->unique(['cronograma_template_id', 'fase']);
            $table->index(['cronograma_template_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_template_fases');
    }
};
