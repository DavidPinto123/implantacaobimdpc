<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cronograma_fase_historicos')) {
            return;
        }

        Schema::create('cronograma_fase_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cronograma_fase_id')->constrained('cronograma_fases')->cascadeOnDelete();
            $table->string('campo_alterado');
            $table->string('valor_anterior')->nullable();
            $table->string('valor_novo')->nullable();
            $table->text('motivo')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('automatico')->default(false);
            $table->timestamps();

            $table->index('cronograma_fase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fase_historicos');
    }
};
