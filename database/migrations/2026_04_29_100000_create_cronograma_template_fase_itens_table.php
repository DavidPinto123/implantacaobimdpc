<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cronograma_template_fase_id');
            $table->foreign('cronograma_template_fase_id', 'ctfi_template_fase_fk')
                ->references('id')
                ->on('cronograma_template_fases')
                ->cascadeOnDelete();
            $table->string('titulo');
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['cronograma_template_fase_id', 'ordem'], 'ctfi_fase_ordem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_template_fase_itens');
    }
};
