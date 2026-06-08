<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacao_logs', function (Blueprint $table) {
            $table->id();
            $table->string('arquivo_original');
            $table->string('arquivo_path');
            $table->string('modulo')->default('obras');
            $table->string('status')->default('pendente');
            $table->unsignedInteger('total_linhas')->default(0);
            $table->unsignedInteger('linhas_criadas')->default(0);
            $table->unsignedInteger('linhas_atualizadas')->default(0);
            $table->unsignedInteger('linhas_erro')->default(0);
            $table->json('erros')->nullable();
            $table->json('mapeamento_usado')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacao_logs');
    }
};
