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
        Schema::table('dados', function (Blueprint $table) {
            $table->string('nova_sigla')->nullable();
            $table->string('unidade')->nullable();
            $table->string('marca')->nullable();
            $table->string('bloco_tipo')->nullable();
            $table->string('categoria')->nullable();
            $table->string('descricao')->nullable();
            $table->string('quantidade')->nullable();
            $table->string('pavimento')->nullable();
            $table->string('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dados', function (Blueprint $table) {
            $table->dropColumn([
                'nova_sigla',
                'unidade',
                'marca',
                'bloco_tipo',
                'categoria',
                'descricao',
                'quantidade',
                'pavimento',
                'status',
            ]);
        });
    }
};
