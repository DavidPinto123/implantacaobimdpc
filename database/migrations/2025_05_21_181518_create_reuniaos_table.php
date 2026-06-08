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
        Schema::create('reuniaos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->date('data');
            $table->time('hora');
            $table->enum('tipo', ['online', 'presencial']);
            $table->string('convidados')->nullable();
            $table->string('link_video')->nullable();
            $table->string('local')->nullable();
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reuniaos');
    }
};
