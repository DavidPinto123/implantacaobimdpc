<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cronograma_fases')->truncate();
    }

    public function down(): void
    {
        // Dados serao recriados automaticamente ao acessar o cronograma
    }
};
