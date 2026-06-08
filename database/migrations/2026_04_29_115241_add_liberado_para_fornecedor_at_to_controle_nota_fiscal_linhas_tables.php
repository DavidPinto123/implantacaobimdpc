<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->timestamp('liberado_para_fornecedor_at')->nullable()->after('observacoes');
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->timestamp('liberado_para_fornecedor_at')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropColumn('liberado_para_fornecedor_at');
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->dropColumn('liberado_para_fornecedor_at');
        });
    }
};
