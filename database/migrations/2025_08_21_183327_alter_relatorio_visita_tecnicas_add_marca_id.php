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
            $table->dropColumn('marca');

            $table->foreignId('marca_id')
                ->nullable()
                ->constrained('marcas')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn('marca_id');

            $table->string('marca')->nullable();
        });
    }
};
