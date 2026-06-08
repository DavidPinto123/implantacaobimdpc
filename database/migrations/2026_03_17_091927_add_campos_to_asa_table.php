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
        Schema::table('asas', function (Blueprint $table) {
            $table->foreignId('gestor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('solicitante')->nullable();
            $table->json('planilha_apresentada')->nullable();

            $table->json('foto_antes')->nullable();
            $table->json('foto_depois')->nullable();
            $table->json('projeto_orcado')->nullable();
            $table->json('projeto_revisado')->nullable();
            $table->json('escopo_contratado')->nullable();
            $table->json('escopo_real')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asas', function (Blueprint $table) {
            $table->dropColumn('gestor_id');
            $table->dropColumn('solicitante');
            $table->dropColumn('planilha_apresentada');
            $table->dropColumn('foto_antes');
            $table->dropColumn('foto_depois');
            $table->dropColumn('projeto_orcado');
            $table->dropColumn('projeto_revisado');
            $table->dropColumn('escopo_contratado');
            $table->dropColumn('escopo_real');
        });
    }
};
