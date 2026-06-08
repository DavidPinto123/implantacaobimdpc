<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->string('tipo')->nullable()->after('obra_id');
            $table->text('observacoes')->nullable()->after('custo_sem_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->dropColumn(['tipo', 'observacoes']);
        });
    }
};
