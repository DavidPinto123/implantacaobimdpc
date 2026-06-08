<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_fase_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cronograma_fase_id')
                ->constrained('cronograma_fases')
                ->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->boolean('recebido')->default(false);
            $table->text('observacoes')->nullable();
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->string('origem', 10)->default('manual');
            $table->timestamps();

            $table->index(['cronograma_fase_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fase_itens');
    }
};
