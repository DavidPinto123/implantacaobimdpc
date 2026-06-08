<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_item_id')) {
                $table->dropForeign('controle_nota_fiscal_notas_controle_nota_fiscal_item_id_foreign');
                $table->dropIndex('cnf_notas_item_status_idx');
                $table->dropColumn('controle_nota_fiscal_item_id');
            }

            if (Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_auxiliar_id')) {
                $table->dropForeign('cnf_notas_auxiliar_fk');
                $table->dropIndex('cnf_notas_auxiliar_fk');
                $table->dropColumn('controle_nota_fiscal_auxiliar_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_item_id')) {
                $table->foreignId('controle_nota_fiscal_item_id')
                    ->nullable()
                    ->after('asa_id')
                    ->constrained('controle_nota_fiscal_items')
                    ->cascadeOnDelete();

                $table->index(['controle_nota_fiscal_item_id', 'status'], 'cnf_notas_item_status_idx');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_auxiliar_id')) {
                $table->unsignedBigInteger('controle_nota_fiscal_auxiliar_id')
                    ->nullable()
                    ->after('controle_nota_fiscal_item_id');

                $table->foreign('controle_nota_fiscal_auxiliar_id', 'cnf_notas_auxiliar_fk')
                    ->references('id')
                    ->on('controle_nota_fiscal_auxiliares')
                    ->cascadeOnDelete();
            }
        });
    }
};
