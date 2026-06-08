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
        Schema::dropIfExists('unidades');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sigla_antiga', 50)->nullable();
            $table->string('sigla_nova', 50);
            $table->string('unidade');
            $table->string('cnpj', 18)->unique();
            $table->string('cnpj_provisorio', 18)->nullable()->unique();
            $table->string('status_cnpj', 100);
            $table->string('uf', 2);
            $table->string('cidade');
            $table->string('empresa');
            $table->timestamps();
        });
    }
};
