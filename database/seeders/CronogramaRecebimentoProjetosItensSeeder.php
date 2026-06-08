<?php

namespace Database\Seeders;

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use Illuminate\Database\Seeder;

/**
 * Backfill legado dos itens-padrão (checklist sim/não) para fases de projeto.
 * Também garante que os templates recebam os mesmos itens na tabela nova.
 */
class CronogramaRecebimentoProjetosItensSeeder extends Seeder
{
    /**
     * Itens padrão por fase (ordem, título).
     */
    private array $padroes = [
        'recebimento_projetos_arquitetura' => [
            'Planta de arquitetura',
            'Local indicado da academia',
            'Cortes',
            'Fachadas',
            'Área técnica',
        ],
        'recebimento_projetos_complementares' => [
            'Planta elétrica',
            'Planta hidráulica',
            'Projeto estrutural',
            'PPCI (combate a incêndio)',
            'Ar-condicionado',
        ],
    ];

    public function run(): void
    {
        $this->call(CronogramaTemplateFaseItensSeeder::class);

        $fasesAlvo = [
            FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA,
            FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES,
        ];

        $fases = CronogramaFase::whereIn('fase', array_map(fn ($f) => $f->value, $fasesAlvo))->get();

        foreach ($fases as $fase) {
            if ($fase->itens()->exists()) {
                continue;
            }

            $padrao = $this->padroes[$fase->fase->value] ?? [];
            foreach ($padrao as $ordem => $titulo) {
                CronogramaFaseItem::create([
                    'cronograma_fase_id' => $fase->id,
                    'titulo' => $titulo,
                    'recebido' => false,
                    'ordem' => $ordem,
                    'origem' => 'template',
                ]);
            }
        }
    }
}
