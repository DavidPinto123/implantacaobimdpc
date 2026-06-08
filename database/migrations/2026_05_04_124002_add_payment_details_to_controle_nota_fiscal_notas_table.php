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
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'data_vencimento_boleto')) {
                $table->date('data_vencimento_boleto')->nullable()->after('boleto_path');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'banco')) {
                $table->string('banco')->nullable()->after('data_vencimento_boleto');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'agencia')) {
                $table->string('agencia', 30)->nullable()->after('banco');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'conta_corrente')) {
                $table->string('conta_corrente', 50)->nullable()->after('agencia');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            foreach (['conta_corrente', 'agencia', 'banco', 'data_vencimento_boleto'] as $column) {
                if (Schema::hasColumn('controle_nota_fiscal_notas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
