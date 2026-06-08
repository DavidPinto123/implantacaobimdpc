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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('nome_fantasia');
            $table->string('responsavel');
            $table->string('email');
            $table->string('contato');
            $table->string('cnpj')->unique();
            $table->string('tipo');
            $table->string('status');
            $table->foreignId('cidade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estado_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pais_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
