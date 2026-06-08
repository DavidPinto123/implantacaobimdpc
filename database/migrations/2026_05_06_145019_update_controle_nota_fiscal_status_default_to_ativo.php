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
        DB::table('controle_nota_fiscals')
            ->where('status', 'rascunho')
            ->update(['status' => 'ativo']);

        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->string('status')->default('ativo')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->string('status')->default('rascunho')->nullable(false)->change();
        });
    }
};
