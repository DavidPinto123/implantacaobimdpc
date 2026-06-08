<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colunas_personalizadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('tipo', 30)->default('texto');
            $table->json('opcoes')->nullable();
            $table->string('valor', 255)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['projeto_id', 'obra_id', 'nome'], 'colunas_pers_proj_obra_nome_unique');
            $table->index(['obra_id', 'tipo']);
        });

        $agora = now();

        $obras = DB::table('obras')
            ->select('id', 'projeto_id', 'ponto_atencao')
            ->whereNotNull('projeto_id')
            ->whereNotNull('ponto_atencao')
            ->get();

        $dados = [];

        foreach ($obras as $obra) {
            $valor = trim((string) $obra->ponto_atencao);

            if ($valor === '') {
                continue;
            }

            $dados[] = [
                'projeto_id' => $obra->projeto_id,
                'obra_id' => $obra->id,
                'nome' => 'Ponto de Atencao',
                'tipo' => 'texto',
                'valor' => substr($valor, 0, 255),
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        if (! empty($dados)) {
            DB::table('colunas_personalizadas')->insert($dados);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('colunas_personalizadas');
    }
};
