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
        Schema::create('matterports', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->string('nome')->nullable();
            $table->string('pais')->nullable();
            $table->string('estado')->nullable();
            $table->string('cidade')->nullable();
            $table->string('endereco')->nullable();
            $table->string('link_matterport1')->nullable();
            $table->string('link_matterport2')->nullable();
            $table->string('link_matterport3')->nullable();
            $table->string('link_drone')->nullable();
            $table->string('imagem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matterports');
    }
};
