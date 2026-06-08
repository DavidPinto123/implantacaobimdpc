<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remover índices duplicados que já existem (ignorando erros se não existem)
        $indexes = [
            ['table' => 'obras', 'index' => 'obras_status_index'],
            ['table' => 'obras', 'index' => 'obras_relatorio_fotografico_index'],
            ['table' => 'obras', 'index' => 'obras_termo_de_posse_index'],
            ['table' => 'obras', 'index' => 'obras_cronograma_implantacao_index'],
            ['table' => 'obras', 'index' => 'obras_homologados_em_atraso_index'],
            ['table' => 'obras', 'index' => 'obras_email_solicitacao_cl_index'],
            ['table' => 'obras', 'index' => 'obras_envio_qrcod_index'],
            ['table' => 'obras', 'index' => 'obras_checklist_manutencao_index'],
            ['table' => 'obras', 'index' => 'obras_energia_index'],
            ['table' => 'obras', 'index' => 'obras_agua_index'],
            ['table' => 'obras', 'index' => 'obras_gas_index'],
            ['table' => 'obras', 'index' => 'obras_projeto_id_index'],
            ['table' => 'colunas_personalizadas', 'index' => 'colunas_personalizadas_obra_id_index'],
            ['table' => 'colunas_personalizadas', 'index' => 'colunas_personalizadas_nome_index'],
            ['table' => 'colunas_personalizadas', 'index' => 'colunas_personalizadas_obra_id_nome_index'],
            ['table' => 'projetos', 'index' => 'projetos_marca_index'],
            ['table' => 'projetos', 'index' => 'projetos_tipo_imovel_index'],
            ['table' => 'projetos', 'index' => 'projetos_locacao_index'],
        ];
        
        foreach ($indexes as $indexInfo) {
            try {
                DB::statement("DROP INDEX {$indexInfo['index']} ON {$indexInfo['table']}");
            } catch (\Exception $e) {
                // Ignorar se índice não existe
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não precisa fazer nada no rollback
    }
};
