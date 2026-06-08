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
            $table->date('data_inicio_servico')->nullable()->after('parcelamento_autorizacao_servico');
            $table->date('data_termino_servico')->nullable()->after('data_inicio_servico');
            $table->date('data_entrega_material')->nullable()->after('data_termino_servico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn([
                'data_inicio_servico',
                'data_termino_servico',
                'data_entrega_material',
            ]);
        });
    }
};
