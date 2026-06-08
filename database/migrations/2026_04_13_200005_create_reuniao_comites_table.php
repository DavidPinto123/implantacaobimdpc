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
        if (Schema::hasTable('reuniao_comites')) {
            return;
        }

        Schema::create('reuniao_comites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->onDelete('cascade');
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade');
            $table->string('unidade')->nullable();
            $table->string('status_reuniao_comite')->nullable();
            $table->boolean('relatorio_visita')->default(false);
            $table->boolean('estudo_massa')->default(false);
            $table->boolean('levantamento_cadastral')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reuniao_comites');
    }
};
