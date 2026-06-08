<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asas', function (Blueprint $table) {
            $table->longText('planilha_apresentada')->nullable()->change();
        });

        DB::statement("
            UPDATE asas
            SET planilha_apresentada = JSON_UNQUOTE(JSON_EXTRACT(planilha_apresentada, '$[0]'))
            WHERE planilha_apresentada IS NOT NULL
              AND JSON_VALID(planilha_apresentada)
        ");
    }

    public function down(): void
    {
        DB::statement('
            UPDATE asas
            SET planilha_apresentada = JSON_ARRAY(planilha_apresentada)
            WHERE planilha_apresentada IS NOT NULL
              AND NOT JSON_VALID(planilha_apresentada)
        ');

        Schema::table('asas', function (Blueprint $table) {
            $table->json('planilha_apresentada')->nullable()->change();
        });
    }
};
