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
        Schema::table('projetos', function (Blueprint $table) {
            $table->string('status')->nullable();
            $table->string('tipo_entrada')->nullable();
            $table->string('nome_contato')->nullable();
            $table->string('contato')->nullable();
            $table->longText('pin_google')->nullable();
            $table->string('tipo_de_loja')->nullable();
            $table->integer('n_vagas_livres')->nullable();
            $table->decimal('area_academia', 10, 2)->nullable();
            $table->decimal('area_terreno', 10, 2)->nullable();
            $table->integer('n_pisos')->nullable();
            $table->decimal('pe_direito', 10, 2)->nullable();
            $table->string('modelo_entrega_p')->nullable();
            $table->decimal('aluguel_cto', 15, 2)->nullable();
            $table->decimal('luvas', 15, 2)->nullable();
            $table->decimal('iptu', 15, 2)->nullable();
            $table->decimal('condominio', 15, 2)->nullable();
            $table->string('configuracao_academia')->nullable();
            $table->text('dados_engenharia')->nullable();
            $table->date('prazo_inicio')->nullable();
            $table->boolean('projeto_croqui')->default(false);
            $table->integer('potencial_alunos')->nullable();
            $table->longText('link_estudo_projecao_alunos')->nullable();
            $table->string('codigo')->unique()->nullable(); // gerado no modelo
            $table->string('imagem_ponto')->nullable(); // upload
            $table->json('anexos')->nullable(); // múltiplos uploads
            $table->text('observacoes_ponto')->nullable();
            $table->decimal('cash_on_cash', 5, 2)->nullable(); // %
            $table->string('marca')->nullable(); // SmartFit, BioRitmo, Nation
            $table->string('numero_loja')->nullable();
            $table->enum('locacao', ['Mono usuário', 'Multiusuário'])->nullable();
            $table->decimal('area_locada', 10, 2)->nullable();
            $table->integer('carencia')->nullable();
            $table->decimal('multa_contrato', 15, 2)->nullable();
            $table->string('empreendimento')->nullable();
            $table->string('tipo_imovel')->nullable();
            $table->date('data_entrega_shell')->nullable();
            $table->boolean('relocation')->default(false);
            $table->boolean('imovel_pronto')->default(false);
            $table->longText('link_docs')->nullable();
            $table->longText('link_construct_in')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'tipo_entrada', 'nome_contato', 'contato', 'pin_google',
                'tipo_de_loja', 'n_vagas_livres', 'area_academia', 'area_terreno', 'n_pisos',
                'pe_direito', 'modelo_entrega_p', 'aluguel_cto', 'luvas', 'iptu', 'condominio',
                'configuracao_academia', 'dados_engenharia', 'prazo_inicio', 'projeto_croqui',
                'potencial_alunos', 'link_estudo_projecao_alunos', 'codigo', 'imagem_ponto',
                'anexos', 'observacoes_ponto', 'cash_on_cash', 'marca', 'numero_loja', 'locacao',
                'area_locada', 'carencia', 'multa_contrato', 'empreendimento', 'tipo_imovel',
                'data_entrega_shell', 'relocation', 'imovel_pronto', 'link_docs', 'link_construct_in',
            ]);
        });
    }
};
