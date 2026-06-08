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
        Schema::table('obra_recebimentos', function (Blueprint $table) {
            $table->foreignId('construtora_id')
                ->nullable()
                ->after('obra_id')
                ->constrained('construtoras')
                ->nullOnDelete();
            $table->string('foto_entrega_path')->nullable()->after('status');
            $table->string('foto_entrega_nome')->nullable()->after('foto_entrega_path');
            $table->string('nota_fiscal_path')->nullable()->after('foto_entrega_nome');
            $table->string('nota_fiscal_nome')->nullable()->after('nota_fiscal_path');

            $table->index('construtora_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obra_recebimentos', function (Blueprint $table) {
            $table->dropIndex(['construtora_id']);
            $table->dropConstrainedForeignId('construtora_id');
            $table->dropColumn([
                'foto_entrega_path',
                'foto_entrega_nome',
                'nota_fiscal_path',
                'nota_fiscal_nome',
            ]);
        });
    }
};
