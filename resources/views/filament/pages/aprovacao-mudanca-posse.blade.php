<x-filament-panels::page>
    @if($projetos->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--vo-text-muted);font-size:0.85rem;border:1px solid var(--vo-border);border-radius:.5rem;background:var(--vo-bg-subtle, #fafafa);">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 8px;display:block;color:var(--vo-text-faint);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Nenhuma solicitação de mudança de Data de Posse pendente.
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($projetos as $projeto)
                @php
                    $motivoEnum = $projeto->data_posse_pendente_motivo_codigo
                        ? \App\Enums\MotivoAlteracaoObra::tryFrom($projeto->data_posse_pendente_motivo_codigo)
                        : null;
                    $diasShift = $projeto->data_posse && $projeto->data_posse_pendente
                        ? (int) \Carbon\Carbon::parse($projeto->data_posse)->diffInDays(\Carbon\Carbon::parse($projeto->data_posse_pendente), false)
                        : null;
                @endphp
                <div style="border:1px solid var(--vo-border);border-radius:.5rem;padding:16px;background:var(--vo-bg);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:12px;">
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vo-text);">
                                {{ $projeto->codigo ? '['.$projeto->codigo.'] ' : '' }}{{ $projeto->nome }}
                            </div>
                            <div style="font-size:.7rem;color:var(--vo-text-muted);margin-top:2px;">
                                Solicitado por <strong>{{ $projeto->dataPossePendenteSolicitante?->name ?? 'usuário' }}</strong>
                                em {{ $projeto->data_posse_pendente_solicitada_em?->format('d/m/Y H:i') ?? '—' }}
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" wire:click="aprovar({{ $projeto->id }})"
                                    wire:confirm="Aprovar a mudança da Data de Posse para {{ $projeto->data_posse_pendente?->format('d/m/Y') }}?"
                                    style="padding:8px 16px;background:#22c55e;color:#fff;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;font-size:.78rem;">
                                ✓ Aprovar
                            </button>
                            <button type="button" wire:click="rejeitar({{ $projeto->id }})"
                                    wire:confirm="Rejeitar a mudança da Data de Posse?"
                                    style="padding:8px 16px;background:transparent;color:#ef4444;border:1px solid #ef4444;border-radius:.375rem;font-weight:600;cursor:pointer;font-size:.78rem;">
                                ✗ Rejeitar
                            </button>
                        </div>
                    </div>

                    <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:12px;font-size:.78rem;">
                        <div>
                            <div style="font-size:.66rem;text-transform:uppercase;color:var(--vo-text-muted);font-weight:600;margin-bottom:2px;">Data atual</div>
                            <div style="padding:4px 10px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;">
                                {{ $projeto->data_posse?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                        <div style="display:flex;align-items:flex-end;color:var(--vo-text-faint);font-size:1rem;padding-bottom:4px;">→</div>
                        <div>
                            <div style="font-size:.66rem;text-transform:uppercase;color:var(--vo-text-muted);font-weight:600;margin-bottom:2px;">Nova data</div>
                            <div style="padding:4px 10px;background:#fffbeb;border:1px solid #fbbf24;border-radius:.25rem;font-variant-numeric:tabular-nums;font-weight:700;color:#92400e;">
                                {{ $projeto->data_posse_pendente?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                        @if($diasShift !== null)
                            <div>
                                <div style="font-size:.66rem;text-transform:uppercase;color:var(--vo-text-muted);font-weight:600;margin-bottom:2px;">Shift</div>
                                <div style="padding:4px 10px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-weight:700;background:{{ $diasShift > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $diasShift > 0 ? '#ef4444' : '#22c55e' }};">
                                    {{ $diasShift > 0 ? '+'.$diasShift.'d' : $diasShift.'d' }}
                                </div>
                            </div>
                        @endif
                        @if($motivoEnum)
                            <div>
                                <div style="font-size:.66rem;text-transform:uppercase;color:var(--vo-text-muted);font-weight:600;margin-bottom:2px;">Motivo padronizado</div>
                                <div style="padding:4px 10px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.25rem;font-weight:600;">
                                    {{ $motivoEnum->label() }}
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($projeto->data_posse_pendente_motivo)
                        <div style="padding:10px 14px;background:var(--vo-bg-subtle);border-left:3px solid var(--vo-accent, #fbba00);border-radius:.25rem;font-size:.78rem;color:var(--vo-text-secondary);font-style:italic;">
                            "{{ $projeto->data_posse_pendente_motivo }}"
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
