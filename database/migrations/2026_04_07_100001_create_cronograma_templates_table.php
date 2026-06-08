<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_templates', function (Blueprint $table) {
            $table->comment('Templates de cronograma que definem regras de tempo para gerar automaticamente as datas previstas das fases de uma obra');

            $table->id();
            $table->string('nome')->comment('Nome identificador do template (ex.: Expansão - Forward Assinatura)');
            $table->string('tipo_obra', 30)->comment('Tipo de obra ao qual o template se aplica (expansao, ampliacao_retrofit)');
            $table->string('direcao', 20)->comment('Direção do cálculo: forward parte da âncora para frente, backward parte da âncora para trás');
            $table->string('ancora_campo')->comment('Caminho do atributo que serve de data-âncora, ex.: projeto.data_ass_contrato, projeto.inauguracao');
            $table->boolean('is_default')->default(false)->comment('Marca o template como padrão para a combinação tipo_obra + direcao');
            $table->boolean('ativo')->default(true)->comment('Permite desativar templates sem excluir');
            $table->text('observacoes')->nullable()->comment('Notas livres sobre o template');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_obra', 'direcao']);
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_templates');
    }
};
