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
            $table->string('empreendimento_outro')
                ->nullable()
                ->after('empreendimento');

            $table->string('pavimento_outro')
                ->nullable()
                ->after('pavimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn([
                'empreendimento_outro',
                'pavimento_outro',
            ]);
        });
    }
};
