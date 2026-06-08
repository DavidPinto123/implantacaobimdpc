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
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
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

        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->dropForeign(['controle_nota_fiscal_item_id']);
            $table->unsignedBigInteger('controle_nota_fiscal_item_id')->nullable()->change();
            $table->foreign('controle_nota_fiscal_item_id')
                ->references('id')
                ->on('controle_nota_fiscal_items')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'controle_nota_fiscal_auxiliar_id')) {
                $table->dropForeign('cnf_notas_auxiliar_fk');
                $table->dropColumn('controle_nota_fiscal_auxiliar_id');
            }
        });

        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->dropForeign(['controle_nota_fiscal_item_id']);
            $table->unsignedBigInteger('controle_nota_fiscal_item_id')->nullable(false)->change();
            $table->foreign('controle_nota_fiscal_item_id')
                ->references('id')
                ->on('controle_nota_fiscal_items')
                ->cascadeOnDelete();
        });
    }
};
