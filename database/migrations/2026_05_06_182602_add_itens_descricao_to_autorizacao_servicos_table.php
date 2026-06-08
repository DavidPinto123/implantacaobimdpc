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
        Schema::table('autorizacao_servicos', function (Blueprint $table) {
            $table->json('itens_descricao_servico_pdf')
                ->nullable()
                ->after('descricao_servico_pdf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table) {
            $table->dropColumn('itens_descricao_servico_pdf');
        });
    }
};
