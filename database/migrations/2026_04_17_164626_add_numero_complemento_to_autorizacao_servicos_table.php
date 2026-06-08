<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Adiciona a coluna numero_complemento
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->string('numero_complemento', 10)->nullable()->after('numero_as_hash');
        });

        // 2) Cria a nova constraint composta ANTES de dropar a antiga.
        // Necessário porque alguma FK interna usa o índice antigo
        // e o MySQL bloqueia o drop de um índice referenciado.
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->unique(
                ['obra_id', 'numero_as_hash', 'numero_complemento'],
                'aut_serv_obra_numero_hash_compl_unique'
            );
        });

        // 3) Agora pode dropar a constraint antiga (a FK passa a usar a nova)
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropUnique('aut_serv_obra_numero_hash_unique');
        });
    }

    public function down(): void
    {
        // Recria a constraint antiga primeiro
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->unique(['obra_id', 'numero_as_hash'], 'aut_serv_obra_numero_hash_unique');
        });

        // Remove a constraint composta
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropUnique('aut_serv_obra_numero_hash_compl_unique');
        });

        // Remove a coluna
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn('numero_complemento');
        });
    }
};
