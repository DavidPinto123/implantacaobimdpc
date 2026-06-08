<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->decimal('valor_inicial', 10, 2)->nullable()->after('valor_estimado');
        });
    }

    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn('valor_inicial');
        });
    }
};
