<?php

namespace Database\Seeders;

use App\Models\AsEscopo;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\Obras;
use Illuminate\Database\Seeder;

class ControleNotaFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $obra = Obras::query()->first();
        $construtora = Construtora::query()->first();

        if (! $obra) {
            return;
        }

        $controle = ControleNotaFiscal::query()->firstOrCreate(
            [
                'obra_id' => $obra->id,
            ],
            [
                'status' => ControleNotaFiscal::STATUS_ATIVO,
                'construtora_id' => $construtora?->id,
                'data_base' => now()->toDateString(),
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
                'endereco' => $obra->endereco,
            ],
        );

        $controle->fill([
            'unidade' => $obra->unidade,
            'sigla' => $obra->sigla,
            'endereco' => $obra->endereco,
        ]);
        $controle->save();

        if ($controle->itens()->exists() || $controle->auxiliares()->exists()) {
            return;
        }

        $escopos = AsEscopo::query()
            ->where('is_active', true)
            ->orderBy('grupo')
            ->orderBy('numero_as')
            ->get();

        foreach ($escopos as $index => $escopo) {
            $controle->itens()->create([
                'as_escopo_id' => $escopo->id,
                'grupo' => $escopo->grupo,
                'numero_as' => $escopo->numero_as,
                'escopo' => $escopo->escopo,
                'percentual_total' => 100,
                'percentual_faturamento_mao_obra' => 60,
                'percentual_faturamento_material' => 40,
                'sort_order' => $index,
            ]);
        }

        $sortOrder = $escopos->count();

        foreach (ControleNotaFiscalAuxiliar::GRUPOS_AUXILIARES_FIXOS as $grupoAuxiliar) {
            $controle->auxiliares()->create([
                'grupo' => $grupoAuxiliar,
                'numero_as' => null,
                'escopo' => $grupoAuxiliar,
                'percentual_total' => 100,
                'percentual_faturamento_mao_obra' => 60,
                'percentual_faturamento_material' => 40,
                'sort_order' => $sortOrder,
            ]);

            $sortOrder++;
        }
    }
}
