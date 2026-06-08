<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_historicos', function (Blueprint $table) {
            $table->string('motivo_codigo', 50)->nullable()->after('motivo');
            $table->text('motivo_historico')->nullable()->after('motivo_codigo');

            $table->index('motivo_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_historicos', function (Blueprint $table) {
            $table->dropIndex(['motivo_codigo']);
            $table->dropColumn(['motivo_codigo', 'motivo_historico']);
        });
    }
};
