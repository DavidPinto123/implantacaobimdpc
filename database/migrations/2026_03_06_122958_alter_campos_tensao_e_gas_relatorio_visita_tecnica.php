<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            $table->string('entrada_de_energia')->nullable()->change();

            $table->string('rede_gas_disponivel')->nullable()->change();

        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {

            $table->boolean('entrada_de_energia')->nullable()->change();

            $table->boolean('rede_gas_disponivel')->nullable()->change();

        });
    }
};
