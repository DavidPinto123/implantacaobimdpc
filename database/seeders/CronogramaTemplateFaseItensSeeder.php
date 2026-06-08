<?php

namespace Database\Seeders;

use App\Enums\FaseCronograma;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseItem;
use Illuminate\Database\Seeder;

/**
 * Popula itens-padrão (checklist) nas fases de template de Recebimento
 * de Projetos. Ao aplicar um template, esses itens são copiados
 * automaticamente para as fases do projeto com origem='template'.
 */
class CronogramaTemplateFaseItensSeeder extends Seeder
{
    private array $padroes = [
        FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value => [
            'Planta de arquitetura',
            'Local indicado da academia',
            'Cortes',
            'Fachadas',
            'Área técnica',
        ],
        FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES->value => [
            'Planta elétrica',
            'Planta hidráulica',
            'Projeto estrutural',
            'PPCI (combate a incêndio)',
            'Ar-condicionado',
        ],
        FaseCronograma::LIBERACAO_POSSE->value => [
            'Engenharia',
            'Legalização',
        ],
        FaseCronograma::ENTREGAS_PROPRIETARIO->value => [
            'Entrega de projeto contratual (PP → SF)',
            'Retorno SF: Layout',
            'Retorno SF: Planta técnica',
            'Prazo entrega Shell',
        ],
        FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value => [
            'Pré-vendas físico',
            'Pré-vendas online',
        ],
    ];

    public function run(): void
    {
        $fasesAlvo = array_keys($this->padroes);

        $templateFases = CronogramaTemplateFase::whereIn('fase', $fasesAlvo)->get();

        foreach ($templateFases as $tplFase) {
            if ($tplFase->itens()->exists()) {
                continue;
            }

            $titulos = $this->padroes[$tplFase->fase->value] ?? [];
            foreach ($titulos as $ordem => $titulo) {
                CronogramaTemplateFaseItem::create([
                    'cronograma_template_fase_id' => $tplFase->id,
                    'titulo' => $titulo,
                    'ordem' => $ordem,
                ]);
            }
        }
    }
}
