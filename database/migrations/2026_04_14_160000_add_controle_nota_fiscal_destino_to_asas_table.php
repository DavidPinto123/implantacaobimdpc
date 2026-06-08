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
        Schema::table('asas', function (Blueprint $table): void {
            $table->string('controle_nota_fiscal_destino')
                ->default('adicional')
                ->after('contrato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asas', function (Blueprint $table): void {
            $table->dropColumn('controle_nota_fiscal_destino');
        });
    }
};
