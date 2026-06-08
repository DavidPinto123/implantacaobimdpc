<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table) {
            $table->json('arquivos_paths')->nullable()->after('arquivo_nome');
            $table->json('arquivos_nomes')->nullable()->after('arquivos_paths');
        });
    }

    public function down(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table) {
            $table->dropColumn([
                'arquivos_paths',
                'arquivos_nomes',
            ]);
        });
    }
};
