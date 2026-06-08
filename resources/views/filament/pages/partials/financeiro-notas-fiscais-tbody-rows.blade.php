@php
    /**
     * Partial customizado de tbody para Financeiro › Notas Fiscais.
     * Adiciona agrupamento por unidade com cabeçalho collapsible (Alpine).
     *
     * Estado de abertura/fechamento dos grupos vive no Alpine (`openGrupos`)
     * herdado do <tbody x-data> da view financeiro-notas-fiscais-table.
     */

    $resolveUnidade = fn ($r): string => (string) ($r->obra?->unidade ?? 'Sem unidade');

    $resolveCodigo = fn ($r): ?string => $r->obra?->codigo;

    $resolveGestor = fn ($r): ?string => $r->obra?->projeto?->responsavelEng?->name;

    $grupos = $registros->groupBy(fn ($r) => $resolveUnidade($r));
    $totalCols = count($columns) + ($bulkEnabled ? 1 : 0);
    $idsSelecionados = array_map('intval', $notasSelecionadas ?? []);
@endphp

@forelse ($grupos as $unidadeKey => $registrosGrupo)
    @php
        $codigos = $registrosGrupo->map(fn ($r) => $resolveCodigo($r))->filter()->unique()->values();
        $gestores = $registrosGrupo->map(fn ($r) => $resolveGestor($r))->filter()->unique()->values();
        $codigo = $codigos->isEmpty() ? null : $codigos->implode(' / ');
        $gestor = $gestores->isEmpty() ? null : $gestores->implode(' / ');
        $grupoKey = md5((string) $unidadeKey);
        $contagem = $registrosGrupo->count();
        $pendentesGrupo = $registrosGrupo->filter(fn ($r) => ! $r->baixado)->count();
    @endphp

    @php
        $idsDoGrupo = $registrosGrupo->pluck('id')->map(fn ($i) => (int) $i)->all();
        $idsDoGrupoSelecionados = array_values(array_intersect($idsDoGrupo, $idsSelecionados));
        $grupoTodoSelecionado = $idsDoGrupo !== [] && count($idsDoGrupoSelecionados) === count($idsDoGrupo);
        $grupoParcialSelecionado = count($idsDoGrupoSelecionados) > 0 && ! $grupoTodoSelecionado;
    @endphp
    @php
        $grupoBg = $grupoTodoSelecionado
            ? 'color-mix(in srgb, var(--gs-accent) 7%, var(--gs-bg))'
            : 'var(--gs-bg-subtle, #f3f4f6)';
        $grupoTextoCor = $pendentesGrupo > 0 ? '#000' : '#6b7280';
    @endphp
    <tr
        class="gs-grupo-header"
        data-gs-grupo-key="{{ $grupoKey }}"
        style="cursor: pointer; user-select: none; background: {{ $grupoBg }}; font-weight: 600; color: {{ $grupoTextoCor }};"
        role="button"
        :aria-expanded="(openGrupos['{{ $grupoKey }}'] ?? false) ? 'true' : 'false'"
        @click="openGrupos['{{ $grupoKey }}'] = !(openGrupos['{{ $grupoKey }}'] ?? false); window.dispatchEvent(new CustomEvent('te-grupos-mudou'))"
    >
        <td colspan="{{ $totalCols }}" style="padding: 0.55rem 0.85rem; background: {{ $grupoBg }}; border-bottom: 1px solid var(--gs-border, #e5e7eb); border-top: 1px solid var(--gs-border, #e5e7eb);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <input
                        type="checkbox"
                        @click.stop="openGrupos['{{ $grupoKey }}'] = true"
                        x-data
                        x-init="$el.indeterminate = {{ $grupoParcialSelecionado ? 'true' : 'false' }}"
                        wire:click.stop="toggleGrupoSelecionado({{ json_encode($idsDoGrupo) }})"
                        @checked($grupoTodoSelecionado)
                        title="Selecionar/desmarcar todas as notas desta unidade para baixa"
                        style="cursor: pointer; width: 1rem; height: 1rem;"
                    >
                    <span
                        style="display: inline-block; width: 0.85rem; text-align: center;"
                        aria-hidden="true"
                    >
                        <span x-show="!(openGrupos['{{ $grupoKey }}'] ?? false)">▶︎</span>
                        <span x-show="openGrupos['{{ $grupoKey }}'] ?? false" x-cloak>▼︎</span>
                    </span>
                    <span>
                        <span style="font-weight: 400;">Código:</span> {{ $codigo ?? '-' }}
                        <span style="margin: 0 0.35rem;">-</span>
                        <span style="font-weight: 400;">Unidade:</span> {{ $unidadeKey }}
                        <span style="margin: 0 0.35rem;">-</span>
                        <span style="font-weight: 400;">Gestor:</span> {{ $gestor ?? 'Sem gestor' }}
                        <span style="margin: 0 0.35rem;">-</span>
                        <span style="font-weight: 400;">Total de Notas:</span> {{ $contagem }}
                        @if ($pendentesGrupo > 0)
                            <span style="margin: 0 0.35rem; color: #dc2626;">-</span>
                            <span style="color: #dc2626;">
                                <span style="font-weight: 400;">Notas Pendentes:</span> {{ $pendentesGrupo }}
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </td>
    </tr>

    @foreach ($registrosGrupo as $record)
        @php
            $url = $config->resolveRowUrl($record);
            $rowId = (string) data_get($record, $recordKey);
            $rowSelected = in_array($rowId, $selIds, true);
        @endphp
        @php
            $notaSelecionada = in_array((int) $rowId, $idsSelecionados, true);
            $notaPendente = ! $record->baixado;
        @endphp
        <tr
            x-show="openGrupos['{{ $grupoKey }}'] ?? false"
            x-cloak
            wire:key="gs-te-page-row-{{ $rowId }}"
            class="gs-table-excel-page__row {{ $url ? 'gs-table-excel-page__row--clickable' : '' }} {{ $rowSelected || $notaSelecionada ? 'gs-table-excel-page__row--selected' : '' }} {{ $notaPendente ? 'gs-table-excel-page__row--pendente' : 'gs-table-excel-page__row--baixada' }}"
            data-gs-row-id="{{ $rowId }}"
            @if ($url) data-gs-row-url="{{ $url }}" @endif
        >
            @if ($bulkEnabled)
                <td class="gs-table-excel-page__td gs-table-excel-page__td--align-center">
                    <input
                        type="checkbox"
                        aria-label="Selecionar registro"
                        @checked($rowSelected)
                        wire:click.stop="toggleSelecao('{{ $rowId }}')"
                    >
                </td>
            @endif
            @foreach ($columns as $column)
                @php
                    $tdIsFrozen = in_array($column->key, $frozenCols, true);
                    $tdW = $widths[$column->key] ?? null;
                    $tdStyle = ($resizable && $tdW) ? "width: {$tdW}px; min-width: {$tdW}px; max-width: {$tdW}px;" : '';
                    $tdClasses = [
                        'gs-table-excel-page__td',
                        "gs-table-excel-page__td--align-{$column->align}",
                    ];
                    if ($tdIsFrozen) $tdClasses[] = 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left';
                    if ($column->isEditable()) $tdClasses[] = 'gs-table-excel-page__td--editable';
                    if ($column->cellClass) $tdClasses[] = $column->cellClass;
                @endphp
                <td
                    class="{{ implode(' ', $tdClasses) }}"
                    data-gs-column="{{ $column->key }}"
                    @if ($tdIsFrozen) data-gs-frozen="1" @endif
                    @if ($tdStyle) style="{{ $tdStyle }}" @endif
                >
                    @switch($column->getType())
                        @case('select')
                            <input
                                type="checkbox"
                                @click.stop
                                wire:click.stop="toggleNotaSelecionada({{ (int) $rowId }})"
                                @checked($notaSelecionada)
                                title="Selecionar esta nota para baixa"
                                style="cursor: pointer; width: 1rem; height: 1rem;"
                            >
                        @break
                        @case('text')
                            @include('filament.table-excel.page.columns.text', ['column' => $column, 'record' => $record])
                        @break
                        @case('pill')
                            @include('filament.table-excel.page.columns.pill', ['column' => $column, 'record' => $record])
                        @break
                        @case('date')
                            @include('filament.table-excel.page.columns.date', ['column' => $column, 'record' => $record])
                        @break
                        @case('duration')
                            @include('filament.table-excel.page.columns.duration', ['column' => $column, 'record' => $record])
                        @break
                        @case('progress')
                            @include('filament.table-excel.page.columns.progress', ['column' => $column, 'record' => $record])
                        @break
                        @case('badge-count')
                            @include('filament.table-excel.page.columns.badge-count', ['column' => $column, 'record' => $record])
                        @break
                        @case('actions')
                            @include('filament.table-excel.page.columns.actions', ['column' => $column, 'record' => $record])
                        @break
                        @case('text-input')
                            @include('filament.table-excel.page.columns.text-input', ['column' => $column, 'record' => $record])
                        @break
                        @default
                            {{ $column->resolveState($record) }}
                    @endswitch
                </td>
            @endforeach
        </tr>
    @endforeach
@empty
    <tr>
        <td colspan="{{ $totalCols }}" class="gs-table-excel-page__empty">
            <div class="gs-table-excel-page__empty-heading">
                {{ $config->getEmptyStateHeading() ?? 'Nenhum registro encontrado' }}
            </div>
            @if ($config->getEmptyStateDescription())
                <div class="gs-table-excel-page__empty-desc">
                    {{ $config->getEmptyStateDescription() }}
                </div>
            @endif
        </td>
    </tr>
@endforelse
