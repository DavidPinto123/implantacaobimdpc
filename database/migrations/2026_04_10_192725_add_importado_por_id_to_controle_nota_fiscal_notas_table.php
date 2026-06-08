<?php

declare(strict_types=1);

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
            $table->foreignId('importado_por_id')
                ->nullable()
                ->after('controle_nota_fiscal_auxiliar_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('importado_por_id');
        });
    }
};
