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
        Schema::table('controle_pedidos', function (Blueprint $table) {
            $table->foreignId('construtora_id')
                ->nullable()
                ->constrained('construtoras')
                ->nullOnDelete()
                ->after('projeto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_pedidos', function (Blueprint $table) {
            $table->dropForeign(['construtora_id']);
            $table->dropColumn('construtora_id');
        });
    }
};
