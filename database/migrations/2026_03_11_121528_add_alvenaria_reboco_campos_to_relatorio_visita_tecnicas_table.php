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
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            // ALVENARIA
            $table->decimal('metros_alvenaria_periferia', 8, 2)->nullable();

            // REBOCO
            $table->decimal('metros_reboco', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            $table->dropColumn([
                'metros_alvenaria_periferia',
                'metros_reboco',
            ]);

        });
    }
};
