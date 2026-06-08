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
        Schema::table('capex_disciplinas', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('capex_disciplinas')
                ->nullOnDelete();

            $table->text('consideracoes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capex_disciplinas', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'consideracoes']);
        });
    }
};
