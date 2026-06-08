<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapa dos 12 valores válidos do enum AsStatus. Mantido aqui para a migration
     * funcionar mesmo após renomeações futuras do enum no app.
     *
     * @var list<string>
     */
    private const ENUM_VALUES = [
        'rascunho',
        'solicitado',
        'em_aprovacao_gestor',
        'em_aprovacao_orcamento',
        'aprovado',
        'reprovado_gestor',
        'reprovado_orcamento',
        'criada',
        'enviada',
        'em_orcamento',
        'orcada',
        'cancelada',
    ];

    public function up(): void
    {
        if (Schema::hasTable('autorizacao_servicos')) {
            DB::table('autorizacao_servicos')
                ->where(function ($q): void {
                    $q->whereNull('status')->orWhere('status', '');
                })
                ->update(['status' => 'rascunho']);

            $this->changeColumnToEnum('autorizacao_servicos', 'rascunho');
        }

        if (Schema::hasTable('autorizacao_servico_adicionais')) {
            $this->normalizarStatusAsa();
            $this->changeColumnToEnum('autorizacao_servico_adicionais', 'solicitado');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('autorizacao_servicos')) {
            DB::statement("ALTER TABLE `autorizacao_servicos` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'rascunho'");
        }

        if (Schema::hasTable('autorizacao_servico_adicionais')) {
            DB::statement("ALTER TABLE `autorizacao_servico_adicionais` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'solicitado'");
        }
    }

    private function normalizarStatusAsa(): void
    {
        $mapa = [
            'solicitado' => ['Solicitado', 'solicitado', ''],
            'em_aprovacao_orcamento' => [
                'Em aprovação do orçamento',
                'em aprovação do orçamento',
                'em_aprovacao_orcamento',
            ],
            'aprovado' => ['Aprovado', 'aprovado'],
            'reprovado_orcamento' => ['reprovado', 'Reprovado', 'reprovado_orcamento'],
        ];

        DB::table('autorizacao_servico_adicionais')
            ->whereNull('status')
            ->update(['status' => 'solicitado']);

        foreach ($mapa as $destino => $origens) {
            DB::table('autorizacao_servico_adicionais')
                ->whereIn('status', $origens)
                ->update(['status' => $destino]);
        }

        $fallback = DB::table('autorizacao_servico_adicionais')
            ->whereNotIn('status', self::ENUM_VALUES)
            ->count();

        if ($fallback > 0) {
            throw new RuntimeException(
                "Existem {$fallback} registros em autorizacao_servico_adicionais com status fora do enum AsStatus. Normalize antes de prosseguir."
            );
        }
    }

    private function changeColumnToEnum(string $table, string $default): void
    {
        $valoresQuotados = implode(',', array_map(fn (string $v): string => "'".$v."'", self::ENUM_VALUES));

        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `status` ENUM({$valoresQuotados}) NOT NULL DEFAULT '{$default}'"
        );
    }
};
