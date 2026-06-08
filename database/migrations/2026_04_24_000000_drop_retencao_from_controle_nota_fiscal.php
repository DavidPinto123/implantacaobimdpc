<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropColumn(['percentual_retencao', 'valor_retencao_b']);
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->dropColumn(['percentual_retencao', 'valor_retencao_b']);
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->decimal('percentual_retencao', 5, 2)->default(0)->after('valor_global_a');
            $table->decimal('valor_retencao_b', 12, 2)->default(0)->after('percentual_retencao');
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->decimal('percentual_retencao', 5, 2)->default(0)->after('valor_global_a');
            $table->decimal('valor_retencao_b', 12, 2)->default(0)->after('percentual_retencao');
        });
    }
};
