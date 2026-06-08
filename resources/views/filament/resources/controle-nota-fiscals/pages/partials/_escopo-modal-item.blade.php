@php
    $optId = (int) $opt['id'];
    $jaVinculado = (bool) ($opt['ja_vinculado'] ?? false);
    $proximoComplemento = $opt['proximo_complemento'] ?? null;
    $titulo = trim(($opt['numero_as'] !== '' ? $opt['numero_as'].' · ' : '').($opt['escopo'] ?: '—'));
    $subtituloPartes = [];
    if (! empty($opt['grupo'])) {
        $subtituloPartes[] = 'Grupo: '.$opt['grupo'];
    }
    $marcas = $opt['marcas'] ?? [];
    if (! empty($marcas)) {
        $subtituloPartes[] = 'Marcas: '.implode(', ', $marcas);
    }
    if ($jaVinculado && $proximoComplemento) {
        $subtituloPartes[] = 'Será criado como complemento '.$proximoComplemento;
    }
    $searchHay = mb_strtolower(trim($opt['numero_as'].' '.$opt['escopo'].' '.$opt['grupo'].' '.implode(' ', $marcas)));
@endphp
<label
    class="cmed-escopo-item"
    data-search="{{ $searchHay }}"
    data-tab="{{ $tab }}"
    onclick="cmedToggleEscopoItem(event, this)"
>
    <input
        type="checkbox"
        value="{{ $optId }}"
        onchange="cmedUpdateEscopoSelectionState()"
    >
    <div class="cmed-escopo-item-body">
        <div class="cmed-escopo-item-title">
            {{ $titulo }}
            @if ($jaVinculado && $proximoComplemento)
                <span class="cmed-escopo-item-tag cmed-escopo-item-tag-complemento">{{ $proximoComplemento }}</span>
            @endif
        </div>
        @foreach ($subtituloPartes as $parte)
            <div class="cmed-escopo-item-sub">{{ $parte }}</div>
        @endforeach
    </div>
</label>
