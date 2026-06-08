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
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'arquivo_path')) {
                $table->string('arquivo_path')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'arquivo_path')) {
                $table->dropColumn('arquivo_path');
            }
        });
    }
};
