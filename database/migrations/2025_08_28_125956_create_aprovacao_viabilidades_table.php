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
        Schema::create('aprovacao_viabilidades', function (Blueprint $table) {
            $table->id();
            // Relacionamentos
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Dados principais
            $table->string('role')->nullable();

            // aprovado/reprovado/pendente
            $table->enum('aprovacao', ['pendente', 'aprovado', 'reprovado'])->default('pendente');

            $table->longText('comentarios_gerais')->nullable();

            // Seções + anexos (comentários e arquivos)
            $table->tinyInteger('consulta_previa')->default(0);
            $table->json('anexo_consulta_previa')->nullable();

            $table->tinyInteger('estudoviabilidade')->default(0);
            $table->json('anexo_estudoviabilidade')->nullable();

            $table->tinyInteger('visita_tecnica')->default(0);
            $table->json('anexo_visita_tecnica')->nullable();

            $table->tinyInteger('projetos_adicionais')->default(0);
            $table->json('anexo_projetos_adicionais')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprovacao_viabilidades');
    }
};
