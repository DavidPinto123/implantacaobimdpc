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
        if (! Schema::hasColumn('controle_nota_fiscal_items', 'autorizacao_servico_id')) {
            return;
        }

        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('autorizacao_servico_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('controle_nota_fiscal_items', 'autorizacao_servico_id')) {
            return;
        }

        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->foreignId('autorizacao_servico_id')
                ->nullable()
                ->after('as_escopo_id')
                ->constrained('autorizacao_servicos')
                ->nullOnDelete();
        });
    }
};
