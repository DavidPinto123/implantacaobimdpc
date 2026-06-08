<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->unsignedBigInteger('relatorio_fotografico_id')->nullable()->after('relatorio_visita_tecnica_id');
            $table->foreign('relatorio_fotografico_id')->references('id')->on('relatorio_fotograficos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropForeignKey(['relatorio_fotografico_id']);
            $table->dropColumn('relatorio_fotografico_id');
        });
    }
};
