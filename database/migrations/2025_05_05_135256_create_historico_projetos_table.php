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
        Schema::create('historico_projetos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')->constrained(); // Projeto associado
            $table->foreignId('usuario_id')->constrained('users'); // Usuário que fez a ação

            $table->string('setor'); // Setor do usuário
            $table->string('status'); // Ex: "iniciou a análise do projeto"
            $table->string('etapa'); // Ex: "Prospecção", "Viabilidade", etc

            $table->string('status_antigo')->nullable(); // Caso tenha mudança de status
            $table->string('status_novo')->nullable();
            $table->string('acao')->nullable();
            $table->string('fase_antiga')->nullable();
            $table->string('fase_nova')->nullable();

            $table->timestamps(); // created_at = data/hora da ação
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_projetos');
    }
};
