<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_atividades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('grupos_atividades_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos_atividades')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->integer('duracao_dias')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('grupos_atividades_itens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos_atividades_itens');
        Schema::dropIfExists('grupos_atividades');
    }
};
