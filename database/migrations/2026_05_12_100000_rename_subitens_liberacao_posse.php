<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cronograma_template_fase_itens')
            ->where('titulo', 'Liberação Engenharia')
            ->update(['titulo' => 'Engenharia']);

        DB::table('cronograma_template_fase_itens')
            ->where('titulo', 'Liberação Legalização')
            ->update(['titulo' => 'Legalização']);

        DB::table('cronograma_fase_itens')
            ->where('titulo', 'Liberação Engenharia')
            ->update(['titulo' => 'Engenharia']);

        DB::table('cronograma_fase_itens')
            ->where('titulo', 'Liberação Legalização')
            ->update(['titulo' => 'Legalização']);
    }

    public function down(): void
    {
        DB::table('cronograma_template_fase_itens')
            ->where('titulo', 'Engenharia')
            ->update(['titulo' => 'Liberação Engenharia']);

        DB::table('cronograma_template_fase_itens')
            ->where('titulo', 'Legalização')
            ->update(['titulo' => 'Liberação Legalização']);

        DB::table('cronograma_fase_itens')
            ->where('titulo', 'Engenharia')
            ->update(['titulo' => 'Liberação Engenharia']);

        DB::table('cronograma_fase_itens')
            ->where('titulo', 'Legalização')
            ->update(['titulo' => 'Liberação Legalização']);
    }
};
