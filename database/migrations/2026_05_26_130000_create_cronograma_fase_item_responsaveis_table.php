<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_fase_item_responsaveis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cronograma_fase_item_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['cronograma_fase_item_id', 'user_id'], 'cfir_unique');
            $table->foreign('cronograma_fase_item_id', 'cfir_item_fk')
                ->references('id')->on('cronograma_fase_itens')->cascadeOnDelete();
            $table->foreign('user_id', 'cfir_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fase_item_responsaveis');
    }
};
