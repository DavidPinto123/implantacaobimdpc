@php
    $obraId = $obraId ?? null;
    $obraIds = $obraIds ?? null;
    $chave = $obraId !== null
        ? 'historico-obra-'.$obraId
        : 'historico-obras-global-'.md5(json_encode($obraIds ?? []));
@endphp

<livewire:obras.historico-obra
    :obra-id="$obraId"
    :obra-ids="$obraIds"
    :key="$chave"
/>
