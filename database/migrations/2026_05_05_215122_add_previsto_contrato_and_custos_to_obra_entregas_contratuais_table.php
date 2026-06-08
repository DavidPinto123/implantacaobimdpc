<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->boolean('previsto_em_contrato')->default(false)->after('custo_estimado');
            $table->decimal('custo_contrato', 15, 2)->default(0)->after('previsto_em_contrato');
            $table->decimal('custo_sem_contrato', 15, 2)->default(0)->after('custo_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->dropColumn(['previsto_em_contrato', 'custo_contrato', 'custo_sem_contrato']);
        });
    }
};
