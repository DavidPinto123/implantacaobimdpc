<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obras_id')->constrained('obras')->cascadeOnDelete();
            $table->string('fase', 50);
            $table->tinyInteger('ordem');
            $table->boolean('marco')->default(false);
            $table->date('data_prevista_inicio')->nullable();
            $table->date('data_prevista_fim')->nullable();
            $table->date('data_realizada_inicio')->nullable();
            $table->date('data_realizada_fim')->nullable();
            $table->string('status', 20)->default('nao_iniciado');
            $table->tinyInteger('percentual_conclusao')->unsigned()->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['obras_id', 'fase']);
            $table->index(['obras_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fases');
    }
};
