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
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->text('justificativa')->nullable()->after('ref_servico');
            $table->json('anexos')->nullable()->after('justificativa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->dropColumn(['justificativa', 'anexos']);
        });
    }
};
