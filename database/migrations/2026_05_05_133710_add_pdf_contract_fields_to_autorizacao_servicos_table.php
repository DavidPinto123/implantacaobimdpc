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
            $table->string('tipo_contratacao', 20)
                ->nullable()
                ->after('data_entrega_material');
            $table->text('descricao_servico_pdf')
                ->nullable()
                ->after('tipo_contratacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropColumn([
                'tipo_contratacao',
                'descricao_servico_pdf',
            ]);
        });
    }
};
