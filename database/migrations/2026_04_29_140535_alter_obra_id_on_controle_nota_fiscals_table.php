<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controle_nota_fiscals')) {
            return;
        }

        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->dropForeign(['obra_id']);
            $table->unique('obra_id', 'controle_nota_fiscals_obra_id_unique');
            $table->foreign('obra_id')
                ->references('id')
                ->on('obras')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('controle_nota_fiscals')) {
            return;
        }

        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->dropForeign(['obra_id']);
            $table->dropUnique('controle_nota_fiscals_obra_id_unique');
            $table->foreign('obra_id')
                ->references('id')
                ->on('obras')
                ->nullOnDelete();
        });
    }
};
