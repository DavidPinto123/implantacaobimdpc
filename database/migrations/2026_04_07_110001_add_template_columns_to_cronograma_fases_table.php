<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->foreignId('cronograma_template_id')
                ->nullable()
                ->after('marco')
                ->constrained('cronograma_templates')
                ->nullOnDelete()
                ->comment('Template de cronograma que originou esta fase. Nulo em obras legadas ou preenchidas manualmente.');

            $table->foreignId('cronograma_template_fase_id')
                ->nullable()
                ->after('cronograma_template_id')
                ->constrained('cronograma_template_fases')
                ->nullOnDelete()
                ->comment('Regra específica do template que gerou esta fase.');

            $table->smallInteger('regra_duracao_dias')
                ->nullable()
                ->after('observacoes')
                ->comment('Override local da duração em dias. Nulo = usa a regra do template.');

            $table->string('regra_tipo_dias', 10)
                ->nullable()
                ->after('regra_duracao_dias')
                ->comment('Override local de tipo de dias (uteis/corridos). Nulo = usa a regra do template.');

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

            $table->boolean('regra_customizada')
                ->default(false)
                ->after('regra_gap_dias')
                ->comment('Flag indicando que a fase tem ao menos um override ativo em relação à regra do template.');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropForeign(['cronograma_template_id']);
            $table->dropForeign(['cronograma_template_fase_id']);
            $table->dropColumn([
                'cronograma_template_id',
                'cronograma_template_fase_id',
                'regra_duracao_dias',
                'regra_tipo_dias',
                'regra_depende_de_fase',
                'regra_gatilho',
                'regra_gap_dias',
                'regra_customizada',
            ]);
        });
    }
};
