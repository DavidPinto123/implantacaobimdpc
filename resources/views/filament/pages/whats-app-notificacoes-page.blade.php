<x-filament-panels::page>
@php
    $templates = $this->getTemplatesComStatus();
    $corTipo = fn($tipo) => $tipo === 'broadcast' ? '#22c55e' : '#3b82f6';
    $labelTipo = fn($tipo) => $tipo === 'broadcast' ? 'Broadcast' : 'Automático';
@endphp

<style>
.wn-section-title { font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin:0 0 10px; }
.wn-card { background:var(--fi-bg,#18181b);border:1px solid #27272a;border-radius:.75rem;margin-bottom:8px;overflow:hidden;transition:border-color .15s; }
.wn-card:hover { border-color:#3f3f46; }
.wn-card-row { display:flex;align-items:center;gap:12px;padding:13px 16px; }
.wn-card-toggle { flex-shrink:0; }
.wn-card-info { flex:1;min-width:0; }
.wn-card-label { font-weight:700;font-size:.85rem;color:#f4f4f5;display:flex;align-items:center;gap:6px; }
.wn-card-desc { font-size:.72rem;color:#71717a;margin-top:2px; }
.wn-badge { font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:99px; }
.wn-badge-config { background:#16a34a22;color:#4ade80; }
.wn-badge-noconfig { background:#dc262622;color:#f87171; }
.wn-card-meta { font-size:.7rem;color:#52525b;margin-top:3px; }
.wn-card-actions { display:flex;gap:6px;align-items:center;flex-shrink:0; }
.wn-btn { font-size:.72rem;padding:5px 12px;border-radius:.375rem;cursor:pointer;font-family:inherit;border:1px solid transparent;font-weight:600;transition:background .1s; }
.wn-btn-outline { background:transparent;border-color:#3f3f46;color:#a1a1aa; }
.wn-btn-outline:hover { background:#27272a;color:#f4f4f5; }
.wn-btn-green { background:#16a34a;color:#fff;border-color:#16a34a; }
.wn-btn-green:hover { background:#15803d; }
.wn-btn-blue { background:#2563eb;color:#fff;border-color:#2563eb; }
.wn-btn-blue:hover { background:#1d4ed8; }
.wn-btn-red { background:transparent;border-color:#dc2626;color:#f87171;font-size:.66rem;padding:3px 8px; }
.wn-btn-red:hover { background:#dc262622; }
.wn-toggle { position:relative;width:38px;height:21px;cursor:pointer;border:none;background:none;padding:0; }
.wn-toggle-track { width:38px;height:21px;border-radius:99px;transition:background .2s; }
.wn-toggle-thumb { position:absolute;top:3px;width:15px;height:15px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.4); }

/* Painel de assinantes */
.wn-painel { border-top:1px solid #27272a;padding:14px 16px;background:#0f0f0f; }
.wn-painel-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:12px; }
.wn-painel-title { font-size:.75rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:.05em; }
.wn-painel-actions { display:flex;gap:6px; }
.wn-user-list { display:flex;flex-direction:column;gap:4px;max-height:260px;overflow-y:auto; }
.wn-user-row { display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:.5rem;background:#18181b;cursor:pointer;transition:background .1s; }
.wn-user-row:hover { background:#27272a; }
.wn-user-row.sem-telefone { opacity:.5;cursor:default; }
.wn-user-avatar { width:28px;height:28px;border-radius:50%;background:#374151;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#9ca3af;flex-shrink:0; }
.wn-user-name { flex:1;font-size:.8rem;color:#e4e4e7;font-weight:500; }
.wn-user-phone { font-size:.66rem;color:#52525b; }
.wn-check { width:16px;height:16px;border-radius:4px;border:2px solid #3f3f46;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .1s; }
.wn-check.checked { background:#16a34a;border-color:#16a34a; }

/* Envio manual */
.wn-manual-card { background:var(--fi-bg,#18181b);border:1px solid #27272a;border-radius:.75rem;padding:20px; }
.wn-form-row { display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap; }
.wn-form-group { display:flex;flex-direction:column;gap:4px;flex:1;min-width:180px; }
.wn-form-label { font-size:.72rem;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:.04em; }
.wn-select { background:#09090b;border:1px solid #3f3f46;color:#e4e4e7;border-radius:.375rem;padding:8px 10px;font-size:.82rem;font-family:inherit;width:100%;outline:none; }
.wn-select:focus { border-color:#6366f1; }
.wn-hint { font-size:.68rem;color:#52525b;margin-top:3px; }
</style>

{{-- ── SEÇÃO 1: Templates ─────────────────────────────────────────────── --}}
<div wire:key="templates-{{ $renderKey }}">
<p class="wn-section-title">Templates configurados</p>

@foreach($templates as $tpl)
<div class="wn-card">
    <div class="wn-card-row">

        {{-- Toggle ativo --}}
        <button class="wn-toggle" wire:click="toggleAtivo('{{ $tpl['key'] }}')" title="{{ $tpl['ativo'] ? 'Pausar' : 'Ativar' }}">
            <div class="wn-toggle-track" style="background:{{ $tpl['ativo'] ? '#16a34a' : '#3f3f46' }};"></div>
            <div class="wn-toggle-thumb" style="left:{{ $tpl['ativo'] ? '20px' : '3px' }};"></div>
        </button>

        {{-- Info --}}
        <div class="wn-card-info">
            <div class="wn-card-label">
                {{ $tpl['label'] }}
                <span class="wn-badge" style="background:{{ $corTipo($tpl['tipo']) }}22;color:{{ $corTipo($tpl['tipo']) }};">
                    {{ $labelTipo($tpl['tipo']) }}
                </span>
                @if($tpl['configurado'])
                    <span class="wn-badge wn-badge-config">✓ Meta</span>
                @else
                    <span class="wn-badge wn-badge-noconfig">! Sem template</span>
                @endif
            </div>
            <div class="wn-card-desc">{{ $tpl['descricao'] }}</div>
            @if($tpl['tipo'] === 'broadcast')
                <div class="wn-card-meta">
                    {{ $tpl['total_assinantes'] > 0 ? $tpl['total_assinantes'].' assinante(s)' : 'Sem assinantes — envia para todos os usuários com telefone' }}
                </div>
            @endif
        </div>

        {{-- Ações --}}
        <div class="wn-card-actions">
            @if($tpl['tipo'] === 'broadcast')
                <button class="wn-btn wn-btn-outline" wire:click="abrirPainel('{{ $tpl['key'] }}')">
                    {{ $painelAberto === $tpl['key'] ? '▲ Fechar' : '👥 Assinantes' }}
                </button>
                <button class="wn-btn wn-btn-green" wire:click="$set('envioTemplateKey','{{ $tpl['key'] }}')" onclick="document.getElementById('envio-manual').scrollIntoView({behavior:'smooth'})">
                    ▶ Enviar agora
                </button>
            @endif
        </div>
    </div>

    {{-- Painel de assinantes (broadcast only) --}}
    @if($tpl['tipo'] === 'broadcast' && $painelAberto === $tpl['key'])
    @php $usuarios = $this->getUsuariosParaSubscricao($tpl['key']); @endphp
    <div class="wn-painel">
        <div class="wn-painel-header">
            <span class="wn-painel-title">Assinantes — {{ $tpl['label'] }}</span>
            <div class="wn-painel-actions">
                <button class="wn-btn wn-btn-outline" wire:click="selecionarTodos('{{ $tpl['key'] }}')">Todos com tel.</button>
                <button class="wn-btn wn-btn-red" wire:click="removerTodos('{{ $tpl['key'] }}')">Remover todos</button>
            </div>
        </div>
        <div class="wn-user-list">
            @foreach($usuarios as $u)
            <div class="wn-user-row {{ !$u['phone'] ? 'sem-telefone' : '' }}"
                 @if($u['phone']) wire:click="toggleSubscricao({{ $u['id'] }}, '{{ $tpl['key'] }}')" @endif>
                <div class="wn-user-avatar">{{ strtoupper(substr($u['name'],0,1)) }}</div>
                <div class="wn-user-name">{{ $u['name'] }}</div>
                <div class="wn-user-phone">
                    @if($u['phone'])
                        {{ $u['phone'] }}
                    @else
                        <span style="color:#dc2626;font-size:.65rem;">sem telefone</span>
                    @endif
                </div>
                <div class="wn-check {{ $u['inscrito'] ? 'checked' : '' }}">
                    @if($u['inscrito'])
                        <svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endforeach
</div>

{{-- ── SEÇÃO 2: Envio Manual ──────────────────────────────────────────── --}}
<div id="envio-manual" style="margin-top:28px;">
    <p class="wn-section-title">Envio manual</p>
    <div class="wn-manual-card">
        <div class="wn-form-row">

            <div class="wn-form-group">
                <label class="wn-form-label">Template</label>
                <select class="wn-select" wire:model.live="envioTemplateKey">
                    @foreach($templates as $tpl)
                        @if($tpl['tipo'] === 'broadcast')
                            <option value="{{ $tpl['key'] }}">{{ $tpl['label'] }}</option>
                        @endif
                    @endforeach
                </select>
                <div class="wn-hint">Apenas templates Broadcast podem ser enviados manualmente</div>
            </div>

            <div class="wn-form-group">
                <label class="wn-form-label">Destinatário</label>
                <select class="wn-select" wire:model.number.live="envioUserId">
                    <option value="">Todos os assinantes (ou todos com telefone)</option>
                    @foreach($this->getUsuariosSelect() as $uid => $uname)
                        <option value="{{ $uid }}">{{ $uname }}</option>
                    @endforeach
                </select>
                <div class="wn-hint">Deixe em branco para enviar para a lista de assinantes configurada</div>
            </div>

            <div style="flex-shrink:0;padding-bottom:1px;">
                <button class="wn-btn wn-btn-blue" style="padding:9px 20px;font-size:.82rem;" wire:click="enviarManual">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Enviar agora
                </button>
            </div>
        </div>
    </div>
</div>

</x-filament-panels::page>
