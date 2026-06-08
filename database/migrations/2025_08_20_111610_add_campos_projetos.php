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
        Schema::table('projetos', function (Blueprint $table) {
            $table->string('pipeline')->nullable()->after('status');
            $table->string('tipo')->nullable()->after('pipeline');

            $table->date('entrega_projeto')->nullable()->after('prazo_inicio');

            $table->date('inicio_obra')->nullable()->after('entrega_projeto');
            $table->date('entrega_obra')->nullable()->after('inicio_obra');

            $table->date('inauguracao')->nullable()->after('entrega_obra');
            $table->integer('ano_inauguracao')->nullable()->after('inauguracao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline',
                'tipo',
                'entrega_projeto',
                'inicio_obra',
                'entrega_obra',
                'inauguracao',
                'ano_inauguracao',
            ]);
        });
    }
};
