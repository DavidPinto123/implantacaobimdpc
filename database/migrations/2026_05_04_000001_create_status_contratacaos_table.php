<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_contratacaos', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('cor', 7); // Cor em hexadecimal (#RRGGBB)
            $table->integer('ordem')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inserir status padrão
        DB::table('status_contratacaos')->insert([
            ['nome' => 'AS ENVIADA', 'cor' => '#10b981', 'ordem' => 1],
            ['nome' => 'ENTREGA PROGRAMADA', 'cor' => '#f97316', 'ordem' => 2],
            ['nome' => 'EM EXECUÇÃO', 'cor' => '#3b82f6', 'ordem' => 3],
            ['nome' => 'ENTREGUE', 'cor' => '#22c55e', 'ordem' => 4],
            ['nome' => 'COTAÇÃO', 'cor' => '#ec4899', 'ordem' => 5],
            ['nome' => 'ANALISAR', 'cor' => '#6b7280', 'ordem' => 6],
            ['nome' => 'VERIFICAR', 'cor' => '#ef4444', 'ordem' => 7],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('status_contratacaos');
    }
};
