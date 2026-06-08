<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transforma o schema antigo de cronograma (template com direcao fixa, fase com uma
 * unica dependencia e override local via regra_*) no modelo hibrido novo:
 *
 * - cronograma_templates perde direcao e is_default.
 * - cronograma_template_fases perde depende_de_fase/gatilho/gap_dias e ganha
 *   visivel + is_ancora.
 * - cronograma_fases perde regra_depende_de_fase/regra_gatilho/regra_gap_dias e
 *   ganha visivel (nullable, herda do template).
 *
 * Os dados antigos desses campos sao descartados: o seeder recria o template
 * oficial de demonstracao com a nova estrutura e qualquer template que o usuario
 * tenha criado manualmente pelo painel terá as regras de dependencia perdidas —
 * isso e aceitavel porque ainda nenhum ambiente de producao estava usando esses
 * templates. Ambientes dev/teste devem rerodar o CronogramaTemplateSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remove FKs primeiro para permitir limpar e alterar livremente as tabelas.
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropForeign(['cronograma_template_id']);
            $table->dropForeign(['cronograma_template_fase_id']);
        });

        // Limpa referencias no lado da obra.
        DB::table('cronograma_fases')
            ->whereNotNull('cronograma_template_id')
            ->update([
                'cronograma_template_id' => null,
                'cronograma_template_fase_id' => null,
                'regra_duracao_dias' => null,
                'regra_tipo_dias' => null,
                'regra_depende_de_fase' => null,
                'regra_gatilho' => null,
                'regra_gap_dias' => null,
                'regra_customizada' => false,
            ]);

        // Com as FKs derrubadas, pode limpar templates e template_fases.
        DB::table('cronograma_template_fases')->delete();
        DB::table('cronograma_templates')->delete();

        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->dropIndex(['tipo_obra', 'direcao']);
            $table->dropIndex(['is_default']);
            $table->dropColumn(['direcao', 'is_default']);
            $table->index('tipo_obra');
        });

        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->dropColumn(['depende_de_fase', 'gatilho', 'gap_dias']);
            $table->boolean('visivel')
                ->default(true)
                ->after('tipo_dias')
                ->comment('Define se a fase entra no cronograma gerado pelo template. Quando false, a fase e criada na obra como oculta e fica fora dos calculos. Pode ser sobrescrita pela obra em cronograma_fases.visivel.');
            $table->boolean('is_ancora')
                ->default(false)
                ->after('visivel')
                ->comment('Marca a fase que recebe a data fixa vinda do ancora_campo do template. Exatamente uma fase por template deve ter este flag ativo.');
        });

        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn(['regra_depende_de_fase', 'regra_gatilho', 'regra_gap_dias']);
            $table->boolean('visivel')
                ->nullable()
                ->after('regra_customizada')
                ->comment('Override local da visibilidade da fase na obra. NULL herda do template_fase.visivel. TRUE exibe, FALSE oculta.');

            // Recria as FKs apontando para as tabelas ainda existentes.
            $table->foreign('cronograma_template_id')
                ->references('id')->on('cronograma_templates')
                ->nullOnDelete();
            $table->foreign('cronograma_template_fase_id')
                ->references('id')->on('cronograma_template_fases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn('visivel');
            $table->string('regra_depende_de_fase', 50)
                ->nullable()
                ->after('regra_tipo_dias')
                ->comment('Override local da fase-dependência. Nulo = usa a regra do template.');
            $table->string('regra_gatilho', 20)
                ->nullable()
                ->after('regra_depende_de_fase')
                ->comment('Override local do gatilho (inicio_anterior/fim_anterior). Nulo = usa a regra do template.');
            $table->smallInteger('regra_gap_dias')
                ->nullable()
                ->after('regra_gatilho')
                ->comment('Override local do offset em dias. Nulo = usa a regra do template.');
        });

        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->dropColumn(['visivel', 'is_ancora']);
            $table->string('depende_de_fase', 50)
                ->nullable()
                ->after('tipo_dias')
                ->comment('Fase da qual esta depende. Se nulo, depende da fase anterior por ordem ou da âncora se for a primeira');
            $table->string('gatilho', 20)
                ->default('fim_anterior')
                ->after('depende_de_fase')
                ->comment('Define se o ciclo começa quando a fase-dependência inicia (inicio_anterior) ou termina (fim_anterior)');
            $table->smallInteger('gap_dias')
                ->default(0)
                ->after('gatilho')
                ->comment('Offset em dias a partir do gatilho. Suporta valores negativos (antecipação)');
        });

        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->dropIndex(['tipo_obra']);
            $table->string('direcao', 20)
                ->default('forward')
                ->after('tipo_obra')
                ->comment('Direção do cálculo: forward parte da âncora para frente, backward parte da âncora para trás');
            $table->boolean('is_default')
                ->default(false)
                ->after('ancora_campo')
                ->comment('Marca o template como padrão para a combinação tipo_obra + direcao');
            $table->index(['tipo_obra', 'direcao']);
            $table->index('is_default');
        });
    }
};
