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
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->longText('planilha_autorizacao_servico')->nullable()->after('anexo_autorizacao_servico');
            $table->string('planilha_autorizacao_servico_nome')->nullable()->after('planilha_autorizacao_servico');
            $table->string('planilha_autorizacao_servico_mime')->nullable()->after('planilha_autorizacao_servico_nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn([
                'planilha_autorizacao_servico',
                'planilha_autorizacao_servico_nome',
                'planilha_autorizacao_servico_mime',
            ]);
        });
    }
};
