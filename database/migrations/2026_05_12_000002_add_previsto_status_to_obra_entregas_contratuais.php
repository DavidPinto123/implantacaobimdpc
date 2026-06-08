<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->string('previsto_status')->default('previsto_sim')->after('previsto_em_contrato');
        });

        // Backfill a partir do booleano existente.
        DB::table('obra_entregas_contratuais')->where('previsto_em_contrato', true)->update([
            'previsto_status' => 'previsto_sim',
        ]);
        DB::table('obra_entregas_contratuais')->where('previsto_em_contrato', false)->update([
            'previsto_status' => 'previsto_nao',
        ]);
    }

    public function down(): void
    {
        Schema::table('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->dropColumn('previsto_status');
        });
    }
};
