<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            if (! Schema::hasColumn('autorizacao_servicos', 'controle_nota_fiscal_item_id')) {
                $table->foreignId('controle_nota_fiscal_item_id')
                    ->nullable()
                    ->after('obra_id')
                    ->constrained('controle_nota_fiscal_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            if (Schema::hasColumn('autorizacao_servicos', 'controle_nota_fiscal_item_id')) {
                $table->dropForeignKeyIfExists('autorizacao_servicos_controle_nota_fiscal_item_id_foreign');
                $table->dropColumn('controle_nota_fiscal_item_id');
            }
        });
    }
};
