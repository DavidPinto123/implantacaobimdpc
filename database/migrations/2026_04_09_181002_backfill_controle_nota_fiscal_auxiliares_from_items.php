<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $fixedGroups = [
        'Projeto',
        'Solicitação Cliente',
        'Legalização',
        'Shell',
        'Orçamentos',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_auxiliares')) {
            return;
        }

        DB::transaction(function (): void {
            $legacyItems = DB::table('controle_nota_fiscal_items')
                ->whereNull('as_escopo_id')
                ->orderBy('controle_nota_fiscal_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $createdAuxiliares = [];

            foreach ($legacyItems as $legacyItem) {
                $group = $this->normalizeGroup($legacyItem->grupo);

                if ($group === null) {
                    continue;
                }

                $mappingKey = $legacyItem->controle_nota_fiscal_id.'|'.$group;
                $auxiliarId = $createdAuxiliares[$mappingKey] ?? null;

                if ($auxiliarId === null) {
                    $existingAuxiliar = DB::table('controle_nota_fiscal_auxiliares')
                        ->where('controle_nota_fiscal_id', $legacyItem->controle_nota_fiscal_id)
                        ->where('grupo', $group)
                        ->first();

                    if ($existingAuxiliar) {
                        $auxiliarId = $existingAuxiliar->id;
                    } else {
                        $auxiliarId = DB::table('controle_nota_fiscal_auxiliares')->insertGetId([
                            'controle_nota_fiscal_id' => $legacyItem->controle_nota_fiscal_id,
                            'grupo' => $group,
                            'numero_as' => $legacyItem->numero_as,
                            'escopo' => $this->normalizeEscopo($legacyItem->escopo, $group),
                            'empresa' => $legacyItem->empresa,
                            'percentual_total' => $legacyItem->percentual_total,
                            'percentual_faturamento_direto' => $legacyItem->percentual_faturamento_direto,
                            'percentual_faturamento_indireto' => $legacyItem->percentual_faturamento_indireto,
                            'valor_global_a' => $legacyItem->valor_global_a,
                            'percentual_retencao' => $legacyItem->percentual_retencao,
                            'valor_retencao_b' => $legacyItem->valor_retencao_b,
                            'total_medicao_a_menos_b' => $legacyItem->total_medicao_a_menos_b,
                            'valor_acumulado_medido' => $legacyItem->valor_acumulado_medido,
                            'saldo' => $legacyItem->saldo,
                            'observacoes' => $legacyItem->observacoes,
                            'sort_order' => $legacyItem->sort_order,
                            'created_at' => $legacyItem->created_at,
                            'updated_at' => $legacyItem->updated_at,
                        ]);
                    }

                    $createdAuxiliares[$mappingKey] = $auxiliarId;
                } else {
                    $existingAuxiliar = DB::table('controle_nota_fiscal_auxiliares')->where('id', $auxiliarId)->first();

                    if ($existingAuxiliar) {
                        DB::table('controle_nota_fiscal_auxiliares')
                            ->where('id', $auxiliarId)
                            ->update([
                                'numero_as' => $existingAuxiliar->numero_as ?: $legacyItem->numero_as,
                                'escopo' => $existingAuxiliar->escopo ?: $this->normalizeEscopo($legacyItem->escopo, $group),
                                'empresa' => $existingAuxiliar->empresa ?: $legacyItem->empresa,
                                'observacoes' => $existingAuxiliar->observacoes ?: $legacyItem->observacoes,
                                'percentual_total' => (float) $existingAuxiliar->percentual_total === 0.0 ? $legacyItem->percentual_total : $existingAuxiliar->percentual_total,
                                'percentual_faturamento_direto' => (float) $existingAuxiliar->percentual_faturamento_direto === 0.0 ? $legacyItem->percentual_faturamento_direto : $existingAuxiliar->percentual_faturamento_direto,
                                'percentual_faturamento_indireto' => (float) $existingAuxiliar->percentual_faturamento_indireto === 0.0 ? $legacyItem->percentual_faturamento_indireto : $existingAuxiliar->percentual_faturamento_indireto,
                                'valor_global_a' => (float) $existingAuxiliar->valor_global_a === 0.0 ? $legacyItem->valor_global_a : $existingAuxiliar->valor_global_a,
                                'percentual_retencao' => (float) $existingAuxiliar->percentual_retencao === 0.0 ? $legacyItem->percentual_retencao : $existingAuxiliar->percentual_retencao,
                                'valor_retencao_b' => (float) $existingAuxiliar->valor_retencao_b === 0.0 ? $legacyItem->valor_retencao_b : $existingAuxiliar->valor_retencao_b,
                                'total_medicao_a_menos_b' => (float) $existingAuxiliar->total_medicao_a_menos_b === 0.0 ? $legacyItem->total_medicao_a_menos_b : $existingAuxiliar->total_medicao_a_menos_b,
                                'valor_acumulado_medido' => (float) $existingAuxiliar->valor_acumulado_medido === 0.0 ? $legacyItem->valor_acumulado_medido : $existingAuxiliar->valor_acumulado_medido,
                                'saldo' => (float) $existingAuxiliar->saldo === 0.0 ? $legacyItem->saldo : $existingAuxiliar->saldo,
                                'updated_at' => $legacyItem->updated_at,
                            ]);
                    }
                }

                DB::table('controle_nota_fiscal_notas')
                    ->where('controle_nota_fiscal_item_id', $legacyItem->id)
                    ->update([
                        'controle_nota_fiscal_auxiliar_id' => $auxiliarId,
                        'controle_nota_fiscal_item_id' => null,
                    ]);

                DB::table('controle_nota_fiscal_items')
                    ->where('id', $legacyItem->id)
                    ->delete();
            }

            $controleIds = DB::table('controle_nota_fiscals')->pluck('id');

            foreach ($controleIds as $controleId) {
                $existingGroups = DB::table('controle_nota_fiscal_auxiliares')
                    ->where('controle_nota_fiscal_id', $controleId)
                    ->pluck('grupo')
                    ->map(fn (string $group): ?string => $this->normalizeGroup($group))
                    ->filter()
                    ->all();

                $nextSortOrder = (int) DB::table('controle_nota_fiscal_auxiliares')
                    ->where('controle_nota_fiscal_id', $controleId)
                    ->max('sort_order') + 1;

                foreach ($this->fixedGroups as $group) {
                    if (in_array($group, $existingGroups, true)) {
                        continue;
                    }

                    DB::table('controle_nota_fiscal_auxiliares')->insert([
                        'controle_nota_fiscal_id' => $controleId,
                        'grupo' => $group,
                        'numero_as' => null,
                        'escopo' => $group,
                        'empresa' => null,
                        'percentual_total' => 100,
                        'percentual_faturamento_direto' => 60,
                        'percentual_faturamento_indireto' => 40,
                        'valor_global_a' => 0,
                        'percentual_retencao' => 0,
                        'valor_retencao_b' => 0,
                        'total_medicao_a_menos_b' => 0,
                        'valor_acumulado_medido' => 0,
                        'saldo' => 0,
                        'observacoes' => null,
                        'sort_order' => $nextSortOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $nextSortOrder++;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_auxiliares')) {
            return;
        }

        DB::transaction(function (): void {
            $auxiliares = DB::table('controle_nota_fiscal_auxiliares')
                ->orderBy('controle_nota_fiscal_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($auxiliares as $auxiliar) {
                $itemId = DB::table('controle_nota_fiscal_items')->insertGetId([
                    'controle_nota_fiscal_id' => $auxiliar->controle_nota_fiscal_id,
                    'as_escopo_id' => null,
                    'grupo' => $auxiliar->grupo,
                    'numero_as' => $auxiliar->numero_as,
                    'escopo' => $auxiliar->escopo,
                    'empresa' => $auxiliar->empresa,
                    'percentual_total' => $auxiliar->percentual_total,
                    'percentual_faturamento_direto' => $auxiliar->percentual_faturamento_direto,
                    'percentual_faturamento_indireto' => $auxiliar->percentual_faturamento_indireto,
                    'valor_global_a' => $auxiliar->valor_global_a,
                    'percentual_retencao' => $auxiliar->percentual_retencao,
                    'valor_retencao_b' => $auxiliar->valor_retencao_b,
                    'total_medicao_a_menos_b' => $auxiliar->total_medicao_a_menos_b,
                    'valor_acumulado_medido' => $auxiliar->valor_acumulado_medido,
                    'saldo' => $auxiliar->saldo,
                    'observacoes' => $auxiliar->observacoes,
                    'sort_order' => $auxiliar->sort_order,
                    'created_at' => $auxiliar->created_at,
                    'updated_at' => $auxiliar->updated_at,
                ]);

                DB::table('controle_nota_fiscal_notas')
                    ->where('controle_nota_fiscal_auxiliar_id', $auxiliar->id)
                    ->update([
                        'controle_nota_fiscal_item_id' => $itemId,
                        'controle_nota_fiscal_auxiliar_id' => null,
                    ]);
            }

            DB::table('controle_nota_fiscal_auxiliares')->delete();
        });
    }

    private function normalizeEscopo(?string $escopo, string $group): string
    {
        $normalizedEscopo = $this->normalizeGroup($escopo);

        if ($normalizedEscopo !== null) {
            return $normalizedEscopo;
        }

        return filled($escopo) ? trim((string) $escopo) : $group;
    }

    private function normalizeGroup(?string $group): ?string
    {
        if ($group === null) {
            return null;
        }

        $normalizedGroup = trim($group);

        if ($normalizedGroup === '') {
            return null;
        }

        return match ($normalizedGroup) {
            'Projetos' => 'Projeto',
            'Solicitação', 'Solicitacao', 'Cliente', 'Solicitação Cliente', 'Solicitacao Cliente' => 'Solicitação Cliente',
            'Legalizacao', 'Legalização' => 'Legalização',
            'Orçamento', 'Orcamento', 'Orçamentos', 'Orcamentos' => 'Orçamentos',
            default => $normalizedGroup,
        };
    }
};
