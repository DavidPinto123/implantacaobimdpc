<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_items', 'quantidade')) {
                $table->decimal('quantidade', 14, 2)->nullable()->after('empresa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_items', 'quantidade')) {
                $table->dropColumn('quantidade');
            }
        });
    }
};
