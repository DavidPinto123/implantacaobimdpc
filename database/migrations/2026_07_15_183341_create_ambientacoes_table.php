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
        Schema::create('ambientacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pais_id')->nullable();
            $table->foreignId('estado_id')->nullable();
            $table->foreignId('cidade_id')->nullable();
            $table->string('codigo');
            $table->string('nome')->nullable();
            $table->string('sigla')->nullable();
            $table->string('nova_sigla')->nullable();
            $table->string('pavimento');
            $table->string('ambiente');
            $table->longText('link_render');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambientacoes');
    }
};
