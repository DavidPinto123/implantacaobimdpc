<?php

namespace App\Filament\Widgets;

use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Resources\CapexSimulacaos\RelationManagers\ItensRelationManager;
use App\Models\AsEscopo;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\GrupoOi;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class CapexSimulacaoItensTreeWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions {
        mountAction as protected mountActionFromTrait;
    }
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.capex-simulacao-itens-tree';

    protected static bool $isLazy = false;

    public ?CapexSimulacao $record = null;

    protected int|string|array $columnSpan = 'full';

    #[On('capex-itens-recarregados')]
    public function recarregar(): void
    {
        $this->record?->refresh();
    }

    /**
     * Limpa o "mountedActions" antes de montar um action novo. Sem o reset,
     * o $wire.entangle dos inputs do modal anterior deixa lixo em
     * mountedActions.0.data no cliente, e a próxima mountAction recebe esse
     * estado fantasma (sem "name"), o que faz o modal renderizar vazio.
     */
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed
    {
        $this->mountedActions = [];

        return $this->mountActionFromTrait($name, $arguments, $context);
    }

    /**
     * Retorna grupos folha (sem filhos) com o caminho completo a partir da raiz.
     *
     * @return array<int, string>
     */
    protected static function opcoesGruposFolha(): array
    {
        $grupos = GrupoOi::query()
            ->ativos()
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->keyBy('id');

        $idsComFilhos = $grupos
            ->pluck('parent_id')
            ->filter()
            ->unique()
            ->all();

        return $grupos
            ->reject(fn (GrupoOi $g): bool => in_array($g->id, $idsComFilhos, true))
            ->mapWithKeys(function (GrupoOi $folha) use ($grupos): array {
                $caminho = collect();
                $atual = $folha;

                while ($atual) {
                    $caminho->prepend($atual->nome);
                    $atual = $atual->parent_id ? $grupos->get($atual->parent_id) : null;
                }

                return [$folha->id => $caminho->implode(' › ')];
            })
            ->sort()
            ->all();
    }

    public function inserirEscopoManualAction(): Action
    {
        return Action::make('inserirEscopoManual')
            ->label('Adicionar escopo manual')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Adicionar escopo manual')
            ->modalSubmitActionLabel('Adicionar')
            ->schema([
                Hidden::make('tipo')->default('manual'),

                
                Select::make('grupo_oi_id')
                    ->label('Grupo OI')
                    ->helperText('Onde o item será exibido na árvore. Selecione um grupo folha.')
                    ->options(fn (): array => self::opcoesGruposFolha())
                    ->searchable()
                    ->preload()
                    ->native(false),

                Grid::make(2)->schema([
                    TextInput::make('nome_escopo')
                        ->label('Nome exibido na OI')
                        ->required(fn (Get $get): bool => blank($get('as_escopo_id')))
                        ->maxLength(255),

                    TextInput::make('numero_complemento')
                        ->label('Complemento')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('C1, C2…')
                        ->helperText('Vazio para principal.'),
                ]),

                MoneyInput::makeNonNull('valor_base_m2', 'Valor manual (R$)')
                    ->label('Valor manual (R$)')
                    ->required()
                    ->minValue(0)
                    ->default(0),

                Toggle::make('incluir')
                    ->label('Incluir no total')
                    ->default(true),

                Textarea::make('comentario')
                    ->label('Comentário')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Detalhes do escopo manual, premissas ou origem do valor.'),
            ])
            ->action(function (array $data): void {
                if (! $this->record) {
                    return;
                }

                $escopo = filled($data['as_escopo_id'] ?? null)
                    ? AsEscopo::query()->find((int) $data['as_escopo_id'])
                    : null;

                $valorBase = max((float) ($data['valor_base_m2'] ?? 0), 0.0);
                $incluir = (bool) ($data['incluir'] ?? true);
                $fator = (float) $this->record->fator_correcao;

                $custoEstimado = $incluir ? ($valorBase * $fator) : 0.0;

                $grupoOiId = filled($data['grupo_oi_id'] ?? null)
                    ? (int) $data['grupo_oi_id']
                    : $escopo?->grupo_oi_id;

                $this->record->itens()->create([
                    'as_escopo_id' => $escopo?->id,
                    'grupo_oi_id' => $grupoOiId,
                    'tipo' => 'manual',
                    'incluir' => $incluir,
                    'nome_escopo' => filled($data['nome_escopo'] ?? null)
                        ? $data['nome_escopo']
                        : $escopo?->escopo,
                    'numero_complemento' => filled($data['numero_complemento'] ?? null)
                        ? $data['numero_complemento']
                        : null,
                    'valor_base_m2' => $valorBase,
                    'valor_base_m2_editado' => false,
                    'area' => null,
                    'fator_correcao' => $fator,
                    'custo_estimado' => $custoEstimado,
                    'percentual' => 0,
                    'comentario' => filled($data['comentario'] ?? null) ? $data['comentario'] : null,
                ]);

                $this->recalcularPercentuaisETotais();

                Notification::make()
                    ->title('Escopo manual adicionado.')
                    ->success()
                    ->send();
            });
    }

    public function alternarIncluir(int $itemId): void
    {
        $item = $this->itemDaSimulacao($itemId);

        if (! $item) {
            return;
        }

        $novoEstado = ! $item->incluir;

        $custoEstimado = $this->calcularCustoEstimado($item, (float) $item->valor_base_m2, $novoEstado);

        $item->update([
            'incluir' => $novoEstado,
            'custo_estimado' => $custoEstimado,
        ]);

        $this->recalcularPercentuaisETotais();
    }

    public function atualizarValorBase(int $itemId, mixed $valor): void
    {
        $item = $this->itemDaSimulacao($itemId);

        if (! $item) {
            return;
        }

        $valorBase = max(MoneyInput::parse($valor) ?? 0.0, 0.0);
        $valorAnterior = (float) $item->valor_base_m2;

        $custoEstimado = $this->calcularCustoEstimado($item, $valorBase, (bool) $item->incluir);

        $item->update([
            'valor_base_m2' => $valorBase,
            'valor_base_m2_editado' => $valorBase !== $valorAnterior,
            'custo_estimado' => $custoEstimado,
        ]);

        $this->recalcularPercentuaisETotais();
    }

    public function converterParaManualAction(): Action
    {
        return Action::make('converterParaManual')
            ->label('Converter para manual')
            ->modalHeading('Converter para item manual')
            ->modalDescription('O item deixará de ser calculado pela faixa/área e passará a usar um valor fixo. A área ficará em N/A.')
            ->modalSubmitActionLabel('Converter')
            ->schema([
                MoneyInput::makeNonNull('valor_base_m2', 'Valor manual (R$)')
                    ->label('Valor manual (R$)')
                    ->helperText('Digite os centavos da direita pra esquerda. Ex.: 358465052 → 3.584.650,52')
                    ->required(),
            ])
            ->fillForm(function (array $arguments): array {
                $item = $this->itemDaSimulacao((int) ($arguments['itemId'] ?? 0));

                return [
                    'valor_base_m2' => (float) ($item?->valor_base_m2 ?? 0),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $item = $this->itemDaSimulacao((int) ($arguments['itemId'] ?? 0));

                if (! $item || $item->tipo === 'manual') {
                    return;
                }

                $valorBase = max((float) ($data['valor_base_m2'] ?? 0), 0.0);
                $fator = (float) $this->record->fator_correcao;

                $grupoOiId = $item->grupo_oi_id ?? $item->escopo?->grupo_oi_id;

                $item->update([
                    'tipo' => 'manual',
                    'as_escopo_id' => null,
                    'grupo_oi_id' => $grupoOiId,
                    'area' => null,
                    'valor_base_m2' => $valorBase,
                    'valor_base_m2_editado' => false,
                    'fator_correcao' => $fator,
                    'custo_estimado' => $item->incluir ? ($valorBase * $fator) : 0.0,
                ]);

                $this->recalcularPercentuaisETotais();

                Notification::make()
                    ->title("Item \"{$item->nome_escopo}\" convertido para manual.")
                    ->success()
                    ->send();
            });
    }

    public function restaurarValorOriginal(int $itemId): void
    {
        $item = $this->itemDaSimulacao($itemId);

        if (! $item || $item->tipo !== 'auto') {
            return;
        }

        $item->update(['valor_base_m2_editado' => false]);

        $this->record->recalcularItensAutomaticosETotais();
        $this->record->refresh();

        $this->dispatch('capex-itens-recarregados')->to(ItensRelationManager::class);

        Notification::make()
            ->title("Valor original restaurado em {$item->nome_escopo}.")
            ->success()
            ->send();
    }

    private function itemDaSimulacao(int $itemId): ?CapexSimulacaoItem
    {
        return $this->record
            ?->itens()
            ->whereKey($itemId)
            ->first();
    }

    private function calcularCustoEstimado(CapexSimulacaoItem $item, float $valorBase, bool $incluir): float
    {
        if (! $incluir) {
            return 0.0;
        }

        if ($item->tipo === 'manual') {
            return $valorBase * (float) $this->record->fator_correcao;
        }

        return $valorBase
            * (float) ($item->area ?? $this->record->area_unidade)
            * (float) ($item->fator_correcao ?? $this->record->fator_correcao);
    }

    private function recalcularPercentuaisETotais(): void
    {
        $this->record->refresh();
        $this->record->load('itens');

        $total = $this->record->itens
            ->where('incluir', true)
            ->sum(fn (CapexSimulacaoItem $i): float => (float) $i->custo_estimado);

        foreach ($this->record->itens as $item) {
            $percentual = ($total > 0 && $item->incluir)
                ? (((float) $item->custo_estimado / $total) * 100)
                : 0;

            $item->update(['percentual' => $percentual]);
        }

        $this->record->update([
            'custo_total_estimado' => $total,
            'custo_por_m2' => ((float) $this->record->area_unidade > 0)
                ? ($total / (float) $this->record->area_unidade)
                : 0,
        ]);

        $this->record->refresh();

        // Atualiza apenas a tabela legada. O dispatch para EditCapexSimulacao
        // causava re-fill do form pai e remontagem do widget, derrubando o
        // estado de mountedActions e impedindo abrir o próximo modal.
        $this->dispatch('capex-itens-recarregados')
            ->to(ItensRelationManager::class);
    }

    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'arvore' => collect(),
                'semGrupo' => collect(),
                'semGrupoTotal' => 0.0,
                'totalGeral' => 0.0,
            ];
        }

        $itens = $this->record
            ->itens()
            ->with(['escopo.grupoOi', 'grupoOi'])
            ->orderBy('ordem')
            ->get();

        foreach ($itens as $item) {
            if (! $item->grupo_oi_id && $item->escopo?->grupo_oi_id) {
                $item->grupo_oi_id = $item->escopo->grupo_oi_id;
            }
        }

        [$comGrupo, $semGrupo] = $itens->partition(
            fn (CapexSimulacaoItem $item): bool => filled($item->grupo_oi_id)
        );

        $arvore = $this->montarArvore($comGrupo);

        $totalGeral = $itens
            ->where('incluir', true)
            ->sum(fn (CapexSimulacaoItem $item): float => (float) $item->custo_estimado);

        $semGrupoTotal = $semGrupo
            ->where('incluir', true)
            ->sum(fn (CapexSimulacaoItem $item): float => (float) $item->custo_estimado);

        return [
            'arvore' => $arvore,
            'semGrupo' => $semGrupo->values(),
            'semGrupoTotal' => (float) $semGrupoTotal,
            'totalGeral' => (float) $totalGeral,
            'idsGrupos' => $this->coletarIdsGrupos($arvore),
        ];
    }

    private function coletarIdsGrupos(Collection $arvore): array
    {
        $ids = [];
        $percorrer = function (Collection $nos) use (&$percorrer, &$ids): void {
            foreach ($nos as $no) {
                $ids[] = $no['grupo']->id;
                $percorrer($no['filhos']);
            }
        };
        $percorrer($arvore);

        return $ids;
    }

    /**
     * Monta a árvore hierárquica de grupos com itens nas folhas.
     *
     * Estrutura de cada nó:
     * [
     *   'grupo' => GrupoOi,
     *   'filhos' => Collection<int, array>,
     *   'itens' => Collection<int, CapexSimulacaoItem>,
     *   'total' => float,
     * ]
     */
    private function montarArvore(Collection $itensComGrupo): Collection
    {
        if ($itensComGrupo->isEmpty()) {
            return collect();
        }

        $idsGruposAlcancados = collect();
        foreach ($itensComGrupo as $item) {
            $grupo = GrupoOi::find($item->grupo_oi_id);
            while ($grupo) {
                $idsGruposAlcancados->push($grupo->id);
                $grupo = $grupo->parent_id
                    ? GrupoOi::find($grupo->parent_id)
                    : null;
            }
        }

        $idsUnicos = $idsGruposAlcancados->unique()->values()->all();

        $grupos = GrupoOi::query()
            ->whereIn('id', $idsUnicos)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->keyBy('id');

        $itensPorGrupo = $itensComGrupo->groupBy(
            fn (CapexSimulacaoItem $item): int => (int) $item->grupo_oi_id
        );

        $construir = function (?int $paiId) use (&$construir, $grupos, $itensPorGrupo): Collection {
            return $grupos
                ->filter(fn (GrupoOi $g): bool => $g->parent_id === $paiId)
                ->sortBy(fn (GrupoOi $g): array => [$g->ordem, $g->nome])
                ->values()
                ->map(function (GrupoOi $grupo) use (&$construir, $itensPorGrupo): array {
                    $filhos = $construir($grupo->id);
                    $itens = $itensPorGrupo->get($grupo->id, collect())->values();

                    $totalItens = $itens
                        ->where('incluir', true)
                        ->sum(fn (CapexSimulacaoItem $i): float => (float) $i->custo_estimado);

                    $totalFilhos = $filhos->sum(fn (array $no): float => $no['total']);

                    return [
                        'grupo' => $grupo,
                        'filhos' => $filhos,
                        'itens' => $itens,
                        'total' => (float) $totalItens + (float) $totalFilhos,
                    ];
                });
        };

        return $construir(null);
    }
}
