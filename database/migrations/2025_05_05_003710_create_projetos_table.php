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
        Schema::create('projetos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('sigla');
            $table->foreignId('user_id')->constrained()->cascadOnDelete();
            $table->foreignId('etapa_id')->constrained()->cascadOnDelete();
            $table->string('rua')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cep')->nullable();
            $table->foreignId('cidade_id')->constrained()->cascadOnDelete();
            $table->foreignId('estado_id')->constrained()->cascadOnDelete();
            $table->foreignId('pais_id')->constrained()->cascadOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
