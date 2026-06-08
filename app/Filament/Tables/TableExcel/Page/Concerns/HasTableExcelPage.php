<?php

namespace App\Filament\Tables\TableExcel\Page\Concerns;

use App\Filament\Tables\TableExcel\Page\Columns\ActionsColumn;
use App\Filament\Tables\TableExcel\Page\TableExcelPage;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Trait para Filament\Pages\Page que ativa o modo Page do Table Excel.
 *
 * Persistência:
 *  - Query state (busca, filtros, paginação, ordenação) vive na URL via #[Url],
 *    permitindo compartilhar link com a lista pré-filtrada.
 *  - UI state (colunas ocultas/ordem/largura/congeladas) vive no localStorage
 *    do navegador, hidratado para o Livewire via hydrateClientPrefs().
 */
trait HasTableExcelPage
{
    /** Página atual (1-based). */
    #[Url(as: 'page', except: 1, history: true)]
    public int $paginaAtual = 1;

    /** Itens por página; 0 → usa o default da config. */
    #[Url(as: 'per_page', except: 0, history: true)]
    public int $porPagina = 0;

    /** Texto livre de busca. */
    #[Url(as: 'q', except: '', history: true)]
    public string $busca = '';

    /** Estado dos filtros: filterKey => value (array ou escalar, conforme o tipo). */
    #[Url(as: 'f', except: [], history: true)]
    public array $filtros = [];

    /** Exibir ou não o painel de filtros secundários (avançados). */
    public bool $mostrarFiltrosAvancados = false;

    /** Exibe o modal de filtros. */
    public bool $mostrarFiltros = false;

    /** Colunas atualmente ocultas (array de keys). */
    public array $colunasOcultas = [];

    /** Ordem persistida das colunas reordenáveis (array de keys). */
    public array $ordemColunas = [];

    /** Exibe o painel de "Gerenciar colunas". */
    public bool $mostrarGerenciarColunas = false;

    /** Ordenação corrente: ['coluna' => string|null, 'direcao' => 'asc'|'desc']. */
    #[Url(as: 'sort', except: ['coluna' => null, 'direcao' => 'asc'], history: true)]
    public array $ordenacao = ['coluna' => null, 'direcao' => 'asc'];

    /** IDs selecionados para operações em massa. */
    public array $selecionados = [];

    /** Colunas congeladas à esquerda (array de keys). */
    public array $frozenColumns = [];

    /** Larguras customizadas por coluna (key => px). */
    public array $columnWidths = [];

    /** Cache da config resolvida no request atual. */
    protected ?TableExcelPage $cachedTableExcelPage = null;

    abstract protected function tableExcelPage(): TableExcelPage;

    /**
     * Nomes dos métodos de Action que devem ser renderizados na toolbar
     * dentro do container da tabela. Override na Page concreta para habilitar.
     *
     * @return array<int, string>
     */
    public function getTableExcelToolbarActions(): array
    {
        return [];
    }

    public function mountHasTableExcelPage(): void
    {
        $config = $this->getTableExcelPage();

        // Query state (filtros/busca/página/per_page/sort) é hidratado pelo
        // próprio Livewire via #[Url]. UI state (colunas ocultas/ordem/
        // largura/congeladas) é hidratado pelo cliente em hydrateClientPrefs,
        // após o Alpine ler localStorage. Aqui aplicamos apenas defaults.

        // "hiddenByDefault": aplicado no primeiro carregamento se o cliente
        // ainda não informou prefs. O hydrateClientPrefs sobrescreve depois.
        if ($this->colunasOcultas === []) {
            foreach ($config->getColumns() as $col) {
                if ($col->toggleable && $col->hiddenByDefault) {
                    $this->colunasOcultas[] = $col->key;
                }
            }
        }

        // Defaults dos filtros: apenas aplicar se $filtros está completamente vazio
        // (sem nada na URL, primeira carga pura). Se houver algo na URL, respeitar.
        if ($this->filtros === [] && request()->query('f') === null) {
            foreach ($config->getFilters() as $filter) {
                if ($filter->default !== null) {
                    $this->filtros[$filter->key] = $filter->default;
                }
            }
        }
    }

    /**
     * Recebe o snapshot de UI preferences do localStorage (Alpine.$persist)
     * e aplica nas propriedades Livewire. Chamado uma vez pelo cliente logo
     * após o mount, antes da tabela se tornar visível.
     *
     * Observação: o nome NÃO pode começar com "hydrate" — Livewire 3 reserva
     * esse prefixo para lifecycle hooks e bloqueia chamadas diretas.
     *
     * @param  array{
     *   hidden?: array<int, string>,
     *   order?: array<int, string>,
     *   frozen?: array<int, string>,
     *   widths?: array<string, int>,
     *   mostrarAvanc?: bool
     * }  $prefs
     */
    public function syncClientPrefs(array $prefs): void
    {
        if (isset($prefs['hidden']) && is_array($prefs['hidden'])) {
            $this->colunasOcultas = array_values(array_unique(array_filter(
                array_map('strval', $prefs['hidden']),
                fn ($name): bool => $name !== '',
            )));
        }

        if (isset($prefs['order']) && is_array($prefs['order'])) {
            $this->ordemColunas = array_values(array_unique(array_filter(
                array_map('strval', $prefs['order']),
                fn ($name): bool => $name !== '',
            )));
        }

        if (isset($prefs['frozen']) && is_array($prefs['frozen'])) {
            $this->frozenColumns = array_values(array_unique(array_filter(
                array_map('strval', $prefs['frozen']),
                fn ($name): bool => $name !== '',
            )));
        }

        if (isset($prefs['widths']) && is_array($prefs['widths'])) {
            $widths = [];
            foreach ($prefs['widths'] as $col => $width) {
                if (! is_string($col) || $col === '' || ! is_numeric($width)) {
                    continue;
                }
                $widths[$col] = max(40, min(1200, (int) $width));
            }
            $this->columnWidths = $widths;
        }

        if (array_key_exists('mostrarAvanc', $prefs) && is_bool($prefs['mostrarAvanc'])) {
            $this->mostrarFiltrosAvancados = $prefs['mostrarAvanc'];
        }

        $this->refreshTableRows();
    }

    public function toggleColuna(string $key): void
    {
        if (in_array($key, $this->colunasOcultas, true)) {
            $this->colunasOcultas = array_values(array_filter(
                $this->colunasOcultas,
                fn ($k) => $k !== $key,
            ));
        } else {
            $this->colunasOcultas[] = $key;
        }

        $this->refreshTableRows();
    }

    public function mostrarTodasAsColunas(): void
    {
        $this->colunasOcultas = [];
        $this->refreshTableRows();
    }

    public function moverColuna(string $key, string $direcao): void
    {
        $config = $this->getTableExcelPage();
        $reorderKeys = array_values(array_filter(
            array_map(fn ($c) => $c->key, $config->getColumns()),
            fn ($k) => ($col = $config->getColumnByKey($k)) !== null && $col->reorderable,
        ));

        // Ordem atual: $this->ordemColunas (limitado às reorderable), appendando keys novos.
        $current = array_values(array_filter(
            $this->ordemColunas,
            fn ($k) => in_array($k, $reorderKeys, true),
        ));
        foreach ($reorderKeys as $k) {
            if (! in_array($k, $current, true)) {
                $current[] = $k;
            }
        }

        $idx = array_search($key, $current, true);
        if ($idx === false) {
            return;
        }

        $swap = $direcao === 'up' ? $idx - 1 : $idx + 1;
        if ($swap < 0 || $swap >= count($current)) {
            return;
        }

        [$current[$idx], $current[$swap]] = [$current[$swap], $current[$idx]];

        $this->ordemColunas = $current;
        $this->refreshTableRows();
    }

    public function resetarOrdemColunas(): void
    {
        $this->ordemColunas = [];
        $this->refreshTableRows();
    }

    public function alternarGerenciarColunas(): void
    {
        $this->mostrarGerenciarColunas = ! $this->mostrarGerenciarColunas;
    }

    public function toggleSelecao(string|int $id): void
    {
        $id = (string) $id;
        if (in_array($id, array_map('strval', $this->selecionados), true)) {
            $this->selecionados = array_values(array_filter(
                $this->selecionados,
                fn ($x) => (string) $x !== $id,
            ));
        } else {
            $this->selecionados[] = $id;
        }
    }

    public function selecionarPaginaAtual(): void
    {
        $data = $this->getTableExcelViewData();
        $keys = $data['registros']->pluck($this->getTableExcelPage()->getRecordKey())->map(fn ($k) => (string) $k)->all();

        $this->selecionados = array_values(array_unique(array_merge(
            array_map('strval', $this->selecionados),
            $keys,
        )));
    }

    public function limparSelecao(): void
    {
        $this->selecionados = [];
    }

    public function excluirSelecionados(): void
    {
        if ($this->selecionados === []) {
            return;
        }

        $config = $this->getTableExcelPage();

        if (! $config->isBulkEnabled()) {
            return;
        }

        $model = $config->buildQuery()->getModel();
        $records = $model->newQuery()->whereIn($model->getKeyName(), $this->selecionados)->get();

        $user = auth()->user();
        $authorize = $config->getBulkDeleteAuthorize();
        $deletados = 0;

        foreach ($records as $record) {
            $allowed = $authorize !== null ? (bool) $authorize($record, $user) : (bool) ($user?->can('delete', $record) ?? false);
            if (! $allowed) {
                continue;
            }

            $record->delete();
            $deletados++;
        }

        $this->selecionados = [];

        Notification::make()
            ->title($deletados > 0 ? "Removidos {$deletados} registro(s)" : 'Nenhum registro pôde ser removido')
            ->{$deletados > 0 ? 'success' : 'warning'}()
            ->send();
    }

    public function ordenarPor(string $key): void
    {
        $atual = $this->ordenacao['coluna'] ?? null;
        $dir = $this->ordenacao['direcao'] ?? 'asc';

        if ($atual === $key) {
            if ($dir === 'asc') {
                $this->ordenacao = ['coluna' => $key, 'direcao' => 'desc'];
            } else {
                $this->ordenacao = ['coluna' => null, 'direcao' => 'asc'];
            }
        } else {
            $this->ordenacao = ['coluna' => $key, 'direcao' => 'asc'];
        }

        $this->refreshTableRows();
    }

    public function alternarFiltrosAvancados(): void
    {
        // Legado — mantido para não quebrar chamadas antigas.
        $this->mostrarFiltros = ! $this->mostrarFiltros;
    }

    public function aplicarFiltrosModal(array $filters = []): void
    {
        $this->filtros = is_array($filters) ? $filters : [];
        $this->paginaAtual = 1;
        $this->refreshTableRows();
    }

    /**
     * Remove um filtro individual pelo key (usado pelo chip "X" da barra
     * Active Filters).
     */
    public function removerFiltro(string $key): void
    {
        unset($this->filtros[$key]);

        $this->paginaAtual = 1;
        $this->refreshTableRows();
    }

    public function limparFiltros(): void
    {
        $this->filtros = [];
        $this->busca = '';
        $this->paginaAtual = 1;

        $this->refreshTableRows();
    }

    public function getTableExcelPage(): TableExcelPage
    {
        return $this->cachedTableExcelPage ??= $this->tableExcelPage();
    }

    // --- Livewire event handlers ----------------------------------------

    public function updatedBusca(): void
    {
        $this->paginaAtual = 1;
        $this->refreshTableRows();
    }

    public function updatedFiltros(): void
    {
        $this->paginaAtual = 1;
        $this->refreshTableRows();
    }

    public function paginar(int $pagina): void
    {
        $this->paginaAtual = max(1, $pagina);
        $this->refreshTableRows();
    }

    public function proximaPagina(): void
    {
        $this->paginar($this->paginaAtual + 1);
    }

    public function paginaAnterior(): void
    {
        $this->paginar($this->paginaAtual - 1);
    }

    public function mudarPorPagina(int $n): void
    {
        $options = $this->getTableExcelPage()->getPerPageOptions();
        if (! in_array($n, $options, true)) {
            return;
        }

        $this->porPagina = $n;
        $this->paginaAtual = 1;
        $this->refreshTableRows();
    }

    public function irParaPagina(int $n): void
    {
        $this->paginar($n);
    }

    public function updatedPorPagina($value): void
    {
        $this->mudarPorPagina((int) $value);
    }

    /**
     * Handler global chamado pelo scripts.blade.php (vanilla JS) ao terminar
     * de arrastar uma borda de coluna. Atualiza a propriedade Livewire;
     * a persistência em localStorage é feita pelo Alpine no cliente.
     */
    public function tableExcelSetColumnWidth(string $tableKey, string $column, int $width): void
    {
        if ($column === '') {
            return;
        }

        $this->columnWidths[$column] = max(40, min(1200, $width));
    }

    public function tableExcelResetColumnWidth(string $tableKey, string $column): void
    {
        unset($this->columnWidths[$column]);
    }

    /**
     * Handler invocado pelo ManageFrozenColumnsAction quando o usuário salva
     * o modal. Atualiza a propriedade Livewire; a persistência em
     * localStorage é feita pelo Alpine no cliente via $watch.
     *
     * @param  array<int, string>  $columns
     */
    public function salvarFrozenColumns(array $columns): void
    {
        $this->frozenColumns = array_values(array_unique(array_filter(
            array_map('strval', $columns),
            fn ($name): bool => $name !== '',
        )));

        $this->refreshTableRows();
    }

    /**
     * Handler invocado pelo ManageColumnsAction ao salvar a aba Visibilidade.
     * Recebe a lista de colunas que devem ficar OCULTAS (inverso do form que
     * controla as visíveis).
     *
     * @param  array<int, string>  $hidden
     */
    public function salvarColunasVisiveis(array $hidden): void
    {
        $this->colunasOcultas = array_values(array_unique(array_filter(
            array_map('strval', $hidden),
            fn ($name): bool => $name !== '',
        )));

        $this->refreshTableRows();
    }

    /**
     * Handler invocado pelo ManageColumnsAction ao salvar a aba Reorganizar.
     *
     * @param  array<int, string>  $columns
     */
    public function salvarOrdemColunas(array $columns): void
    {
        $config = $this->getTableExcelPage();
        $allowed = array_values(array_filter(
            array_map(fn ($column) => $column->key, $config->getColumns()),
            fn (string $key): bool => ($column = $config->getColumnByKey($key)) !== null && $column->reorderable,
        ));

        $ordered = [];
        foreach ($columns as $column) {
            $key = (string) $column;

            if ($key === '' || ! in_array($key, $allowed, true) || in_array($key, $ordered, true)) {
                continue;
            }

            $ordered[] = $key;
        }

        foreach ($allowed as $key) {
            if (! in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }

        $this->ordemColunas = $ordered;
        $this->refreshTableRows();
    }

    /**
     * Handler genérico para colunas Pill editáveis. A Page pode sobrescrever.
     */
    public function mudarValorColuna(string|int $recordId, string $columnKey, string $newValue): void
    {
        $config = $this->getTableExcelPage();
        $column = $config->getColumnByKey($columnKey);

        if ($column === null || ! $column->isEditable()) {
            return;
        }

        $record = $config->buildQuery()->getModel()->newQuery()->find($recordId);

        if ($record === null) {
            return;
        }

        if (! $column->isEditAuthorized($record)) {
            Notification::make()
                ->title('Sem permissão para alterar este campo')
                ->danger()
                ->send();

            $this->skipRender();

            return;
        }

        ($column->onEditUsing)($record, $newValue);

        Notification::make()
            ->title('Atualizado')
            ->success()
            ->send();

        // Evita re-render da tabela inteira após edição inline — o pill já
        // foi atualizado otimisticamente no cliente.
        $this->skipRender();
    }

    /**
     * Handler genérico para ActionsColumn com callback (ex.: excluir registro).
     */
    public function executarAcaoLinha(string $columnKey, string $actionKey, string|int $recordId): void
    {
        $config = $this->getTableExcelPage();
        $column = $config->getColumnByKey($columnKey);

        if (! $column instanceof ActionsColumn) {
            return;
        }

        $action = $column->getActionByKey($actionKey);

        if ($action === null || ! $action->hasHandler()) {
            return;
        }

        $record = $config->buildQuery()->getModel()->newQuery()->find($recordId);

        if ($record === null) {
            return;
        }

        if (! $action->isAuthorized($record)) {
            Notification::make()
                ->title('Sem permissão para executar esta ação')
                ->danger()
                ->send();

            return;
        }

        ($action->onClickUsing)($record);

        $this->refreshTableRows();
    }

    /**
     * Dispatcha o evento que pede ao cliente para recarregar o <tbody>
     * via AJAX (fetchRowsHtml). Chamado ao final de handlers que afetam
     * a lista de registros ou o layout das células.
     */
    protected function refreshTableRows(): void
    {
        $this->dispatch('te-refresh-rows', tableKey: $this->getTableExcelPage()->getTableKey());
    }

    /**
     * Renderiza o partial do corpo da tabela e retorna o HTML como string.
     * Invocado pelo cliente via $wire.call('fetchRowsHtml') após dispatcher
     * 'te-refresh-rows'. O tbody está em wire:ignore no index, então esta
     * rota é o único caminho para atualizar o corpo depois do primeiro render.
     */
    public function fetchRowsHtml(): string
    {
        $data = $this->getTableExcelViewData();
        $config = $data['config'];
        $registros = $data['registros'];

        $rawColumns = $config->getColumns();
        $ocultas = $this->colunasOcultas ?? [];
        $userOrder = $this->ordemColunas ?? [];

        if (! empty($userOrder)) {
            $fixed = array_values(array_filter($rawColumns, fn ($c) => ! $c->reorderable));
            $reorderable = array_values(array_filter($rawColumns, fn ($c) => $c->reorderable));
            $byKey = [];
            foreach ($reorderable as $c) {
                $byKey[$c->key] = $c;
            }
            $ordered = [];
            foreach ($userOrder as $k) {
                if (isset($byKey[$k])) {
                    $ordered[] = $byKey[$k];
                    unset($byKey[$k]);
                }
            }
            foreach ($byKey as $c) {
                $ordered[] = $c;
            }
            $allColumns = array_merge($fixed, $ordered);
        } else {
            $allColumns = $rawColumns;
        }

        $columns = array_values(array_filter(
            $allColumns,
            fn ($c) => ! in_array($c->key, $ocultas, true),
        ));

        $selIds = array_map('strval', $this->selecionados ?? []);

        return view('filament.table-excel.page.partials.tbody-rows', [
            'registros' => $registros,
            'columns' => $columns,
            'config' => $config,
            'recordKey' => $config->getRecordKey(),
            'bulkEnabled' => $config->isBulkEnabled(),
            'resizable' => $config->isResizable(),
            'frozenCols' => $this->frozenColumns ?? [],
            'widths' => $this->columnWidths ?? [],
            'selIds' => $selIds,
        ])->render();
    }

    // --- View data ------------------------------------------------------

    /**
     * @return array{
     *   config: TableExcelPage,
     *   registros: Collection,
     *   totalRegistros: int,
     *   totalPaginas: int,
     *   paginaAtual: int,
     *   porPagina: int,
     *   primeiroRegistroIndex: int,
     *   ultimoRegistroIndex: int
     * }
     */
    public function getTableExcelViewData(): array
    {
        $config = $this->getTableExcelPage();

        $query = $config->buildQuery();
        $this->applySearch($query, $config);
        $this->applyFilters($query, $config);
        $this->applySorting($query, $config);

        $totalRegistros = (clone $query)->count();
        $porPagina = $this->porPagina > 0 ? $this->porPagina : $config->getPerPage();
        $totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));
        $paginaAtual = min(max(1, $this->paginaAtual), $totalPaginas);

        $registros = $query
            ->skip(($paginaAtual - 1) * $porPagina)
            ->take($porPagina)
            ->get();

        $primeiroIndex = $totalRegistros === 0 ? 0 : (($paginaAtual - 1) * $porPagina) + 1;
        $ultimoIndex = min($paginaAtual * $porPagina, $totalRegistros);

        return [
            'config' => $config,
            'registros' => $registros,
            'totalRegistros' => $totalRegistros,
            'totalPaginas' => $totalPaginas,
            'paginaAtual' => $paginaAtual,
            'porPagina' => $porPagina,
            'primeiroRegistroIndex' => $primeiroIndex,
            'ultimoRegistroIndex' => $ultimoIndex,
        ];
    }

    // --- helpers --------------------------------------------------------

    protected function applySearch(Builder $query, TableExcelPage $config): void
    {
        if (! $config->hasSearch()) {
            return;
        }

        $termo = trim($this->busca);

        if ($termo === '') {
            return;
        }

        $fields = $config->getSearchFields();

        if ($fields === []) {
            return;
        }

        $query->where(function (Builder $q) use ($fields, $termo): void {
            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);
                    $q->orWhereHas($relation, function (Builder $sub) use ($column, $termo): void {
                        $sub->where($column, 'like', '%'.$termo.'%');
                    });
                } else {
                    $q->orWhere($field, 'like', '%'.$termo.'%');
                }
            }
        });
    }

    protected function applySorting(Builder $query, TableExcelPage $config): void
    {
        $key = $this->ordenacao['coluna'] ?? null;
        if (! is_string($key) || $key === '') {
            return;
        }

        $column = $config->getColumnByKey($key);
        if ($column === null || ! $column->sortable) {
            return;
        }

        $dir = ($this->ordenacao['direcao'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $sortCol = $column->getSortColumn();

        // Relations sortadas via subquery: "projeto.nome" → ordena na coluna da relação.
        if (str_contains($sortCol, '.')) {
            [$relation, $relCol] = explode('.', $sortCol, 2);
            $model = $query->getModel();

            if (method_exists($model, $relation)) {
                $rel = $model->{$relation}();
                $related = $rel->getRelated();
                $relatedTable = $related->getTable();
                $ownerKey = method_exists($rel, 'getOwnerKeyName') ? $rel->getOwnerKeyName() : $related->getKeyName();
                $foreignKey = method_exists($rel, 'getForeignKeyName') ? $rel->getForeignKeyName() : null;

                if ($foreignKey !== null) {
                    $query->reorder()->orderBy(
                        $related::query()
                            ->select($relCol)
                            ->whereColumn("{$relatedTable}.{$ownerKey}", $model->getTable().".{$foreignKey}"),
                        $dir,
                    );

                    return;
                }
            }

            return;
        }

        $query->reorder($sortCol, $dir);
    }

    protected function applyFilters(Builder $query, TableExcelPage $config): void
    {
        foreach ($config->getFilters() as $filter) {
            if (! array_key_exists($filter->key, $this->filtros)) {
                continue;
            }

            $value = $this->filtros[$filter->key];

            if ($filter->isEmptyValue($value)) {
                continue;
            }

            $filter->apply($query, $value);
        }
    }
}
