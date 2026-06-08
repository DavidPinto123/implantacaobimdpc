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
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'banco_codigo')) {
                $table->string('banco_codigo', 3)->nullable()->after('banco')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'banco_codigo')) {
                $table->dropColumn('banco_codigo');
            }
        });
    }
};
