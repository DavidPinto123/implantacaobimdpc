<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->json('foto_antes')->nullable()->after('anexos');
            $table->json('foto_depois')->nullable()->after('foto_antes');
            $table->json('projeto_orcado')->nullable()->after('foto_depois');
            $table->json('projeto_revisado')->nullable()->after('projeto_orcado');
            $table->json('escopo_contratado')->nullable()->after('projeto_revisado');
            $table->json('escopo_real')->nullable()->after('escopo_contratado');
        });
    }

    public function down(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->dropColumn([
                'foto_antes',
                'foto_depois',
                'projeto_orcado',
                'projeto_revisado',
                'escopo_contratado',
                'escopo_real',
            ]);
        });
    }
};
