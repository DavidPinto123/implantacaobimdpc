<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_items', 'empresa')) {
                $table->string('empresa')->nullable()->after('escopo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscal_items', 'empresa')) {
                $table->dropColumn('empresa');
            }
        });
    }
};
