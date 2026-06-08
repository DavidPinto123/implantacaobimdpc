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
        Schema::table('autorizacao_servico_adicionais', function (Blueprint $table): void {
            $table->date('as_data_inicio')->nullable()->after('descricao');
            $table->date('as_data_termino')->nullable()->after('as_data_inicio');
            $table->date('as_data_entrega')->nullable()->after('as_data_termino');
            $table->decimal('as_desconto', 10, 2)->default(0)->after('as_data_entrega');
            $table->json('as_parcelamento')->nullable()->after('as_desconto');
            $table->text('as_descricao_pdf')->nullable()->after('as_parcelamento');
            $table->json('as_itens_descricao_pdf')->nullable()->after('as_descricao_pdf');
            $table->json('as_anexos')->nullable()->after('as_itens_descricao_pdf');
            $table->string('as_pdf')->nullable()->after('as_anexos');
            $table->foreignId('as_criada_por_id')->nullable()->constrained('users')->nullOnDelete()->after('as_pdf');
            $table->timestamp('as_criada_em')->nullable()->after('as_criada_por_id');
            $table->foreignId('as_enviada_por_id')->nullable()->constrained('users')->nullOnDelete()->after('as_criada_em');
            $table->timestamp('as_enviada_em')->nullable()->after('as_enviada_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('autorizacao_servico_adicionais', function (Blueprint $table): void {
            $table->dropForeign(['as_criada_por_id']);
            $table->dropForeign(['as_enviada_por_id']);
            $table->dropColumn([
                'as_data_inicio', 'as_data_termino', 'as_data_entrega',
                'as_desconto', 'as_parcelamento', 'as_descricao_pdf',
                'as_itens_descricao_pdf', 'as_anexos', 'as_pdf',
                'as_criada_por_id', 'as_criada_em', 'as_enviada_por_id', 'as_enviada_em',
            ]);
        });
    }
};
