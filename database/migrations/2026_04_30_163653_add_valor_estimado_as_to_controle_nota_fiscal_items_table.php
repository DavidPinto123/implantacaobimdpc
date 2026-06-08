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
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table) {
            $table->decimal('valor_estimado_as', 12, 2)
                ->default(0)
                ->after('empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table) {
            $table->dropColumn('valor_estimado_as');
        });
    }
};
