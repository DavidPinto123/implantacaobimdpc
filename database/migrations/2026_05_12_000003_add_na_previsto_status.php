<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existe = DB::table('statuses')
            ->where('contexto', 'entrega_contratual_previsto')
            ->where('slug', 'previsto_na')
            ->exists();

        if ($existe) {
            return;
        }

        $maxOrdem = (int) (DB::table('statuses')
            ->where('contexto', 'entrega_contratual_previsto')
            ->max('ordem') ?? 0);

        DB::table('statuses')->insert([
            'contexto' => 'entrega_contratual_previsto',
            'slug' => 'previsto_na',
            'nome' => 'N/A',
            'cor' => '#6b7280',
            'ordem' => $maxOrdem + 1,
            'is_active' => true,
            'is_protected' => true,
            'tipo_custo' => 'nenhum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('statuses')
            ->where('contexto', 'entrega_contratual_previsto')
            ->where('slug', 'previsto_na')
            ->delete();
    }
};
