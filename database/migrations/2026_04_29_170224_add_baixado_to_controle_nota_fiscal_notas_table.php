<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            if (! Schema::hasColumn('controle_nota_fiscal_notas', 'baixado')) {
                $table->boolean('baixado')->default(false)->index()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            if (Schema::hasColumn('controle_nota_fiscal_notas', 'baixado')) {
                $table->dropIndex(['baixado']);
                $table->dropColumn('baixado');
            }
        });
    }
};
