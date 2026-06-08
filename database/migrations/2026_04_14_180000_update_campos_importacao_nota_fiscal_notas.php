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
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'cnpj_fornecedor')) {
                $table->string('cnpj_fornecedor')->nullable()->after('empresa');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'instrucoes_pagamento')) {
                $table->string('instrucoes_pagamento')->nullable()->after('cnpj_faturamento');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'boleto_path')) {
                $table->string('boleto_path')->nullable()->after('arquivo_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'boleto_path')) {
                $table->dropColumn('boleto_path');
            }

            if (Schema::hasColumn('controle_nota_fiscal_notas', 'instrucoes_pagamento')) {
                $table->dropColumn('instrucoes_pagamento');
            }

            if (Schema::hasColumn('controle_nota_fiscal_notas', 'cnpj_fornecedor')) {
                $table->dropColumn('cnpj_fornecedor');
            }
        });
    }
};
