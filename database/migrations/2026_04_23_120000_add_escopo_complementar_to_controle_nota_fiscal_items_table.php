<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->string('escopo_complementar')->nullable()->after('numero_complemento');
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropColumn('escopo_complementar');
        });
    }
};
