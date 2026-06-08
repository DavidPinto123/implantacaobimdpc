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
        foreach ([
            'planilha_autorizacao_servico',
            'planilha_autorizacao_servico_nome',
            'planilha_autorizacao_servico_mime',
        ] as $column) {
            if (Schema::hasColumn('autorizacao_servicos', $column)) {
                Schema::table('autorizacao_servicos', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table) {
            if (! Schema::hasColumn('autorizacao_servicos', 'planilha_autorizacao_servico')) {
                $table->longText('planilha_autorizacao_servico')->nullable()->after('anexo_autorizacao_servico');
            }

            if (! Schema::hasColumn('autorizacao_servicos', 'planilha_autorizacao_servico_nome')) {
                $table->string('planilha_autorizacao_servico_nome')->nullable()->after('planilha_autorizacao_servico');
            }

            if (! Schema::hasColumn('autorizacao_servicos', 'planilha_autorizacao_servico_mime')) {
                $table->string('planilha_autorizacao_servico_mime')->nullable()->after('planilha_autorizacao_servico_nome');
            }
        });
    }
};
