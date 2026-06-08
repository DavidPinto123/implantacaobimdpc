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
        Schema::table('asas', function (Blueprint $table): void {
            $table->boolean('shell_cabe_como_negociacao')->default(false)->after('contrato');
            $table->text('shell_justificativa_negociacao')->nullable()->after('shell_cabe_como_negociacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asas', function (Blueprint $table): void {
            $table->dropColumn([
                'shell_cabe_como_negociacao',
                'shell_justificativa_negociacao',
            ]);
        });
    }
};
