<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table) {
            $table->string('arquivo_path')->nullable()->after('status');
            $table->string('arquivo_nome')->nullable()->after('arquivo_path');
        });
    }

    public function down(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table) {
            $table->dropColumn([
                'arquivo_path',
                'arquivo_nome',
            ]);
        });
    }
};
