<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('controle_nota_fiscal_notas')
            ->whereNull('status')
            ->update(['status' => 'pendente']);

        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->string('status')->default('pendente')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->string('status')->nullable()->default(null)->change();
        });
    }
};
