<x-filament-panels::page>
    <form wire:submit="gerarPreview" style="margin-bottom:14px;">
        {{ $this->form }}
        <div style="display:flex;gap:8px;margin-top:10px;">
            <x-filament::button type="submit">Gerar pré-visualização</x-filament::button>
            @if($preview && $preview->isNotEmpty())
                <x-filament::button color="success" wire:click="aplicarImportacao" wire:confirm="Aplicar a importação? Datas previstas dos subitens MKT serão atualizadas.">
                    Aplicar importação
                </x-filament::button>
            @endif
        </div>
    </form>

    @if($preview && $preview->isNotEmpty())
        @php
            $erros = $preview->filter(fn ($e) => $e['erro']);
            $conflitos = $preview->filter(fn ($e) => ! $e['erro'] && ($e['conflito_fisico'] || $e['conflito_online']));
            $ok = $preview->filter(fn ($e) => ! $e['erro'] && ! $e['conflito_fisico'] && ! $e['conflito_online']);
        @endphp

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
            <div style="padding:8px 14px;background:rgba(34,197,94,.12);color:#16a34a;border-radius:.375rem;font-size:.78rem;font-weight:600;">
                ✓ {{ $ok->count() }} OK
            </div>
            <div style="padding:8px 14px;background:rgba(251,186,0,.12);color:#92400e;border-radius:.375rem;font-size:.78rem;font-weight:600;">
                ⚠ {{ $conflitos->count() }} com conflito
            </div>
            <div style="padding:8px 14px;background:rgba(239,68,68,.12);color:#ef4444;border-radius:.375rem;font-size:.78rem;font-weight:600;">
                ✗ {{ $erros->count() }} com erro
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:.75rem;">
            <thead>
                <tr style="background:var(--vo-bg-subtle);">
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Linha</th>
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Código</th>
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Projeto</th>
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Pré-vendas físico</th>
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Pré-vendas online</th>
                    <th style="padding:8px 10px;text-align:left;border-bottom:1px solid var(--vo-border);">Decisão</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preview as $entrada)
                    <tr style="border-bottom:1px solid var(--vo-border-light);{{ $entrada['erro'] ? 'background:rgba(239,68,68,.04);' : '' }}">
                        <td style="padding:8px 10px;color:var(--vo-text-muted);">{{ $entrada['linha'] }}</td>
                        <td style="padding:8px 10px;font-weight:600;">{{ $entrada['codigo'] }}</td>
                        <td style="padding:8px 10px;">{{ $entrada['projeto_nome'] ?? '—' }}</td>
                        <td style="padding:8px 10px;font-variant-numeric:tabular-nums;">
                            @if($entrada['conflito_fisico'])
                                <span style="text-decoration:line-through;color:var(--vo-text-faint);">{{ $entrada['conflito_fisico']['atual'] }}</span>
                                → <strong>{{ $entrada['conflito_fisico']['novo'] }}</strong>
                            @else
                                {{ $entrada['data_fisico'] ?? '—' }}
                            @endif
                        </td>
                        <td style="padding:8px 10px;font-variant-numeric:tabular-nums;">
                            @if($entrada['conflito_online'])
                                <span style="text-decoration:line-through;color:var(--vo-text-faint);">{{ $entrada['conflito_online']['atual'] }}</span>
                                → <strong>{{ $entrada['conflito_online']['novo'] }}</strong>
                            @else
                                {{ $entrada['data_online'] ?? '—' }}
                            @endif
                        </td>
                        <td style="padding:8px 10px;">
                            @if($entrada['erro'])
                                <span style="color:#ef4444;font-size:.7rem;">{{ $entrada['erro'] }}</span>
                            @elseif($entrada['conflito_fisico'] || $entrada['conflito_online'])
                                <select wire:model.live="decisoes.{{ $entrada['linha'] }}"
                                        style="padding:4px 8px;font-size:.72rem;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);">
                                    <option value="sobrescrever">Sobrescrever</option>
                                    <option value="manter_atual">Manter atual</option>
                                    <option value="pular">Pular</option>
                                </select>
                            @else
                                <span style="color:#16a34a;font-size:.72rem;">OK · será aplicado</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-filament-panels::page>
