<?php

use App\Enums\FaseCronograma;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->string('titulo_personalizado', 120)->nullable()->after('fase');
        });

        // Atualiza ordens existentes para refletir a nova numeração do enum
        // (após inserção de RECEBIMENTO_PROJETOS_ARQUITETURA/2).
        foreach (FaseCronograma::cases() as $fase) {
            DB::table('cronograma_fases')
                ->where('fase', $fase->value)
                ->update(['ordem' => $fase->ordem()]);

            DB::table('cronograma_template_fases')
                ->where('fase', $fase->value)
                ->update(['ordem' => $fase->ordem()]);
        }
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn('titulo_personalizado');
        });
    }
};
