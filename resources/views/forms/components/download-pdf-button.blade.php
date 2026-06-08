@php
    use App\Models\RelatorioVisitaTecnica;
    $record = $getRecord();
    $visita = $record ? RelatorioVisitaTecnica::where('projeto_id', $record->id)->first() : null;
@endphp

@if ($visita != null)
    <x-filament::button tag="a" color="danger" style="margin-top: 27px"
        href="{{ $record && $visita ? route('download.visita.tecnica', ['record' => $visita->id]) : '#' }}"
        target="_blank" rel="noopener" :disabled="!$record || !$visita">
        <x-heroicon-o-document-arrow-down class="w-5 h-5" />
        Baixar PDF
    </x-filament::button>
@endif
