<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->decimal('desconto_autorizacao_servico', 15, 2)
                ->default(0)
                ->after('valor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn('desconto_autorizacao_servico');
        });
    }
};
