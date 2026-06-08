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
        Schema::create('aprovacao_reuniao_comite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role'); // role do Shield do usuário
            $table->enum('aprovacao', ['aprovado', 'aprovado_com_ressalva', 'reprovado']);
            $table->text('comentarios_gerais')->nullable();
            $table->text('observacoes_ressalva')->nullable();
            $table->json('anexos_ressalva')->nullable();
            $table->json('pmo_cronograma')->nullable();
            $table->json('pmo_termo_abertura')->nullable();
            $table->json('comercial_proposta')->nullable();
            $table->json('comercial_contrato')->nullable();
            $table->json('planejamento_plano')->nullable();
            $table->json('planejamento_estudo')->nullable();
            $table->timestamps(); // created_at = data da aprovação
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprovacao_reuniao_comite');
    }
};
