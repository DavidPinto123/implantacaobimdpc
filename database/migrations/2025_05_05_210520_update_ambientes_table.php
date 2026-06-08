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
        Schema::table('ambientes', function (Blueprint $table) {
            $table->dropColumn(['bloco_tipo', 'categoria', 'descricao', 'quantidade', 'un', 'pavimento', 'status']);

            $table->string('departamento')->nullable();
            $table->string('ambiente')->nullable();
            $table->string('area')->nullable();
            $table->string('pavimento')->nullable();
            $table->string('data_extracao')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ambientes', function (Blueprint $table) {
            //
        });
    }
};
