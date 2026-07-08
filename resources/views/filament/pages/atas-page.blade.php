<x-filament-panels::page>

<style>
.ata-backdrop{position:fixed!important;inset:0!important;z-index:9999!important;display:flex!important;align-items:center!important;justify-content:center!important;background:rgba(0,0,0,.6)!important;padding:1rem!important;}
.ata-box{max-height:92vh!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.ata-box-lg{max-height:95vh!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.ata-header{flex-shrink:0!important;}
.ata-body{overflow-y:auto!important;flex:1 1 0%!important;min-height:0!important;}
.ata-footer{flex-shrink:0!important;}
</style>

    <div class="space-y-4">

        {{-- ── CABEÇALHO ──────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between">
            @if($projetoId)
                <div class="flex items-center gap-3">
                    <button
                        wire:click="voltar"
                        class="inline-flex items-center gap-1 text-sm text-primary-600 dark:text-primary-400 hover:underline"
                    >
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Projetos
                    </button>
                    <span class="text-gray-400 dark:text-gray-600">/</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $projetoNome }}</span>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Selecione um projeto para ver ou criar atas.</p>
            @endif

            <button
                wire:click="novaAta"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition"
            >
                <x-heroicon-o-plus class="w-4 h-4" />
                Nova Ata
            </button>
        </div>

        {{-- ── LISTA DE PROJETOS ───────────────────────────────────────────── --}}
        @unless($projetoId)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Projeto</th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-28">Atas</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-40">Última ata</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->getProjetos() as $projeto)
                    <tr
                        wire:click="selecionarProjeto({{ $projeto->id }}, @js($projeto->nome))"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                    >
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $projeto->nome }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($projeto->atas_count > 0)
                                <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-bold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ $projeto->atas_count }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $projeto->atas_max_data_reuniao ? \Carbon\Carbon::parse($projeto->atas_max_data_reuniao)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right pr-3">
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-600">
                            Nenhum projeto encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endunless

        {{-- ── LISTA DE ATAS DO PROJETO ────────────────────────────────────── --}}
        @if($projetoId)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-28">Data</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-20">Horário</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Tema</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 hidden md:table-cell">Resumo</th>
                        <th class="w-28 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->getAtasDoProjeto() as $ata)
                    <tr
                        wire:click="abrirDetalhe({{ $ata->id }})"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                    >
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $ata->data_reuniao->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            @if($ata->hora_inicio)
                                {{ \Carbon\Carbon::parse($ata->hora_inicio)->format('H:i') }}
                                @if($ata->hora_fim)— {{ \Carbon\Carbon::parse($ata->hora_fim)->format('H:i') }}@endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $ata->titulo }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 hidden md:table-cell">
                            {{ $ata->resumo ? \Illuminate\Support\Str::limit($ata->resumo, 70) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right" wire:click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    wire:click="editarAta({{ $ata->id }})"
                                    title="Editar"
                                    class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                                >
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </button>
                                <button
                                    x-on:click="if(confirm('Excluir esta ata?')) $wire.deletarAta({{ $ata->id }})"
                                    title="Excluir"
                                    class="p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500"
                                >
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-600">
                            Nenhuma ata registrada para este projeto.
                            <button wire:click="novaAta" class="ml-1 text-primary-600 dark:text-primary-400 hover:underline">Criar primeira ata</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── MODAL: DETALHE DA ATA ───────────────────────────────────────── --}}
        @if($modalDetalheAberto)
        @php $ata = $this->getAtaDetalhe(); @endphp
        @if($ata)
        <div class="ata-backdrop">
            <div class="ata-box bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-3xl">

                {{-- Header --}}
                <div class="ata-header flex items-start justify-between px-6 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400 mb-0.5">
                            {{ $ata->projeto?->nome ?? 'Sem projeto' }}
                        </p>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $ata->titulo }}</h2>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <button
                            wire:click="editarAta({{ $ata->id }})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition"
                        >
                            <x-heroicon-o-pencil class="w-3.5 h-3.5" />
                            Editar
                        </button>
                        <button
                            wire:click="fecharDetalhe"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition"
                        >
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="ata-body px-6 py-5 space-y-6">

                    {{-- Informações --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Data</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $ata->data_reuniao->format('d/m/Y') }}</p>
                        </div>
                        @if($ata->hora_inicio)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Horário</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($ata->hora_inicio)->format('H:i') }}
                                @if($ata->hora_fim) – {{ \Carbon\Carbon::parse($ata->hora_fim)->format('H:i') }} @endif
                            </p>
                        </div>
                        @endif
                        @if($ata->local)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Local</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $ata->local }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Participantes --}}
                    @if($ata->participantes->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                            <x-heroicon-o-user-group class="w-4 h-4 text-primary-500" />
                            Participantes
                        </h3>
                        <div class="space-y-1.5">
                            @foreach($ata->participantes as $p)
                            <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $p->nome }}</span>
                                    @if($p->cargo || $p->empresa)
                                    <span class="text-gray-500 dark:text-gray-400 ml-1.5">
                                        {{ collect([$p->cargo, $p->empresa])->filter()->implode(' — ') }}
                                    </span>
                                    @endif
                                </div>
                                @if($p->email)
                                <a href="mailto:{{ $p->email }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline ml-3">
                                    {{ $p->email }}
                                </a>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Resumo --}}
                    @if($ata->resumo)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                            <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-primary-500" />
                            Resumo / Comentários
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $ata->resumo }}</p>
                    </div>
                    @endif

                    {{-- Temas --}}
                    @if($ata->temas->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                            <x-heroicon-o-list-bullet class="w-4 h-4 text-primary-500" />
                            Temas Tratados
                        </h3>
                        <ol class="space-y-3">
                            @foreach($ata->temas as $i => $tema)
                            <li class="border border-gray-100 dark:border-gray-800 rounded-lg p-3">
                                <div class="flex gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold">
                                        {{ $i + 1 }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $tema->titulo }}</p>
                                        @if($tema->descricao)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 whitespace-pre-wrap">{{ $tema->descricao }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($tema->anexos->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-2 ml-9">
                                    @foreach($tema->anexos as $anx)
                                        @if($anx->isImage())
                                        <a href="{{ $anx->url() }}" target="_blank" class="block rounded overflow-hidden border border-gray-200 dark:border-gray-700 hover:opacity-90 transition" style="width:90px;">
                                            <img src="{{ $anx->url() }}" style="width:90px;height:68px;object-fit:cover;display:block;">
                                        </a>
                                        @else
                                        <a href="{{ $anx->url() }}" target="_blank" class="flex items-center gap-1.5 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition">
                                            <x-heroicon-o-document class="w-3.5 h-3.5 flex-shrink-0" />
                                            {{ $anx->nome_original }}
                                        </a>
                                        @endif
                                    @endforeach
                                </div>
                                @endif
                            </li>
                            @endforeach
                        </ol>
                    </div>
                    @endif

                    {{-- Fotos e Anexos --}}
                    @if($ata->anexos->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                            <x-heroicon-o-paper-clip class="w-4 h-4 text-primary-500" />
                            Fotos e Anexos
                        </h3>
                        @php
                            $imagens = $ata->anexos->filter(fn($a) => $a->isImage());
                            $docs    = $ata->anexos->filter(fn($a) => !$a->isImage());
                        @endphp
                        @if($imagens->isNotEmpty())
                        <div class="flex flex-wrap gap-3 mb-3">
                            @foreach($imagens as $img)
                            <a href="{{ $img->url() }}" target="_blank" class="block rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:opacity-90 transition" style="width:120px;">
                                <img src="{{ $img->url() }}" alt="{{ $img->nome_original }}" style="width:120px;height:90px;object-fit:cover;display:block;">
                                <p class="text-xs text-gray-500 truncate px-1.5 py-1">{{ $img->nome_original }}</p>
                            </a>
                            @endforeach
                        </div>
                        @endif
                        @foreach($docs as $doc)
                        <a href="{{ $doc->url() }}" target="_blank" class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition mb-1.5">
                            <x-heroicon-o-document class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $doc->nome_original }}</span>
                            <span class="text-xs text-gray-400 ml-auto">{{ $doc->tamanhoFormatado() }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Vídeo --}}
                    @if($ata->link_youtube)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                            <x-heroicon-o-video-camera class="w-4 h-4 text-red-500" />
                            Gravação da Reunião
                        </h3>
                        <button
                            onclick="window.open('{{ $ata->link_youtube }}', 'youtube_player', 'width=1366,height=800,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=yes,status=no')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-medium rounded-lg transition"
                        >
                            <x-heroicon-o-play class="w-4 h-4" />
                            Abrir vídeo em janela separada
                        </button>
                    </div>
                    @endif

                </div>{{-- /body --}}

                {{-- Footer --}}
                <div class="ata-footer px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-600">
                        Registrado em {{ $ata->created_at->format('d/m/Y \à\s H:i') }}
                        @if($ata->criador) por {{ $ata->criador->name }} @endif
                    </span>
                    <div class="flex items-center gap-2">
                        <button wire:click="enviarPorEmail({{ $ata->id }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition">
                            <x-heroicon-o-envelope class="w-3.5 h-3.5" />
                            Enviar por e-mail
                        </button>
                        <button wire:click="gerarPdf({{ $ata->id }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition">
                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                            Baixar PDF
                        </button>
                    </div>
                </div>

            </div>
        </div>
        @endif
        @endif

        {{-- ── MODAL: FORMULÁRIO (CRIAR / EDITAR) ─────────────────────────── --}}
        @if($modalFormAberto)
        @php
            $inputCls  = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none';
            $inputSmCls = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none';
            $horarios  = $this->getHorarios();
        @endphp

        <datalist id="pautas-lista">
            @foreach($this->getPautaModelos() as $pauta)
            <option value="{{ $pauta }}">
            @endforeach
        </datalist>

        <div class="ata-backdrop">
            <div class="ata-box-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-3xl">

                {{-- Header fixo --}}
                <div class="ata-header flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $editandoId ? 'Editar Ata' : 'Nova Ata de Reunião' }}
                    </h2>
                    <button wire:click="fecharModal" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Corpo rolável --}}
                <div class="ata-body px-6 py-5 space-y-6">

                        {{-- Informações --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Informações da Reunião</h3>

                            {{-- Projeto --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projeto <span class="text-red-500">*</span></label>
                                <select wire:model="formProjetoId" class="{{ $inputCls }}">
                                    <option value="">Selecione…</option>
                                    @foreach($this->getProjetosSelect() as $pid => $pnome)
                                    <option value="{{ $pid }}">{{ $pnome }}</option>
                                    @endforeach
                                </select>
                                @error('formProjetoId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Tema / Pauta com sugestões --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tema / Pauta <span class="text-red-500">*</span>
                                    <span class="ml-1 text-xs font-normal text-gray-400">(selecione uma sugestão ou digite livremente)</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="formTitulo"
                                    list="pautas-lista"
                                    autocomplete="off"
                                    placeholder="Ex: Reunião de definição de Escopo…"
                                    class="{{ $inputCls }}"
                                />
                                @error('formTitulo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Data + Horas (select) + Local --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model="formDataReuniao" class="{{ $inputCls }}" />
                                    @error('formDataReuniao') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Início</label>
                                    <select wire:model="formHoraInicio" class="{{ $inputCls }}">
                                        <option value="">—</option>
                                        @foreach($horarios as $h)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fim</label>
                                    <select wire:model="formHoraFim" class="{{ $inputCls }}">
                                        <option value="">—</option>
                                        @foreach($horarios as $h)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local</label>
                                    <input type="text" wire:model="formLocal" placeholder="Ex: Teams, Escritório…" class="{{ $inputCls }}" />
                                </div>
                            </div>
                        </div>

                        {{-- Resumo --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resumo / Comentários</label>
                            <textarea wire:model="formResumo" rows="3" placeholder="Visão geral da reunião, decisões tomadas…"
                                class="{{ $inputCls }} resize-none"></textarea>
                        </div>

                        {{-- Participantes --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Participantes</h3>
                                <button type="button" wire:click="adicionarParticipante"
                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                    Adicionar
                                </button>
                            </div>
                            @if(count($formParticipantes) === 0)
                                <p class="text-xs text-gray-400 dark:text-gray-600 italic">Nenhum participante adicionado.</p>
                            @endif
                            <div class="space-y-3">
                                @foreach($formParticipantes as $pi => $p)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-2">
                                    {{-- Select usuário do sistema --}}
                                    <div class="flex gap-2 items-center">
                                        <select
                                            wire:change="selecionarUsuario({{ $pi }}, $event.target.value)"
                                            class="{{ $inputSmCls }} text-xs"
                                        >
                                            <option value="">— Selecionar usuário do sistema (opcional) —</option>
                                            @foreach($this->getUsuariosSelect() as $uid => $uname)
                                            <option value="{{ $uid }}" {{ ($p['user_id'] ?? '') == $uid ? 'selected' : '' }}>{{ $uname }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="removerParticipante({{ $pi }})"
                                            class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-400 flex-shrink-0">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                    {{-- Campos --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <input type="text" wire:model="formParticipantes.{{ $pi }}.nome" placeholder="Nome *" class="{{ $inputSmCls }}" />
                                            @error("formParticipantes.$pi.nome") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <input type="email" wire:model="formParticipantes.{{ $pi }}.email" placeholder="Email" class="{{ $inputSmCls }}" />
                                        <input type="text" wire:model="formParticipantes.{{ $pi }}.empresa" placeholder="Empresa" class="{{ $inputSmCls }}" />
                                        <input type="text" wire:model="formParticipantes.{{ $pi }}.cargo" placeholder="Cargo" class="{{ $inputSmCls }}" />
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Temas --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Temas Tratados</h3>
                                <button type="button" wire:click="adicionarTema"
                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                    Adicionar
                                </button>
                            </div>
                            @if(count($formTemas) === 0)
                                <p class="text-xs text-gray-400 dark:text-gray-600 italic">Nenhum tema adicionado.</p>
                            @endif
                            @php $anexosPorTema = $this->getAnexosPorTema(); @endphp
                            <div class="space-y-3">
                                @foreach($formTemas as $ti => $t)
                                <div class="flex gap-2 items-start">
                                    <span class="flex-shrink-0 w-5 h-5 mt-2 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold">
                                        {{ $ti + 1 }}
                                    </span>
                                    <div class="flex-1 space-y-1.5">
                                        <input type="text" wire:model="formTemas.{{ $ti }}.titulo" placeholder="Título do tema *" class="{{ $inputSmCls }}" />
                                        @error("formTemas.$ti.titulo") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                        <textarea wire:model="formTemas.{{ $ti }}.descricao" rows="2" placeholder="Descrição / Decisões…"
                                            class="{{ $inputSmCls }} resize-none"></textarea>

                                        {{-- Anexos existentes deste tema --}}
                                        @if(!empty($anexosPorTema[$ti]) && $anexosPorTema[$ti]->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 pt-1">
                                            @foreach($anexosPorTema[$ti] as $anx)
                                            <div class="relative group border border-gray-200 dark:border-gray-700 rounded overflow-hidden" style="width:64px;">
                                                @if($anx->isImage())
                                                <img src="{{ $anx->url() }}" style="width:64px;height:48px;object-fit:cover;display:block;">
                                                @else
                                                <div style="width:64px;height:48px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;">
                                                    <x-heroicon-o-document class="w-5 h-5 text-gray-400" />
                                                </div>
                                                @endif
                                                <button type="button" wire:click="removerAnexo({{ $anx->id }})"
                                                    wire:confirm="Remover este arquivo?"
                                                    class="absolute top-0.5 right-0.5 w-4 h-4 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">×</button>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif

                                        {{-- Novos uploads para este tema --}}
                                        @if(!empty($formTemasAnexos[$ti]) && count((array)$formTemasAnexos[$ti]) > 0)
                                        <p class="text-xs text-primary-600">{{ count((array)$formTemasAnexos[$ti]) }} arquivo(s) a adicionar</p>
                                        @endif
                                    </div>
                                    {{-- Botões: lixeira + paperclip + tarefa --}}
                                    <div class="flex flex-col gap-1 mt-1">
                                        <button type="button" wire:click="removerTema({{ $ti }})"
                                            class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-400" title="Remover tema">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                        <label wire:click="setUploadTemaIndex({{ $ti }})"
                                               class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 cursor-pointer" title="Adicionar foto/arquivo ao tema">
                                            <x-heroicon-o-paper-clip class="w-4 h-4" />
                                            <input type="file" wire:model="uploadBatchTema" multiple
                                                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden" />
                                        </label>
                                        <button type="button" wire:click="abrirModalTarefa({{ $ti }})"
                                            class="p-1.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-400" title="Gerar tarefa a partir deste tema">
                                            <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- YouTube --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1.5">
                                <x-heroicon-o-video-camera class="w-4 h-4 text-red-500" />
                                Link do Vídeo (YouTube)
                            </label>
                            <input type="url" wire:model="formLinkYoutube" placeholder="https://www.youtube.com/watch?v=…" class="{{ $inputCls }}" />
                            @error('formLinkYoutube') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Fotos e Anexos --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                <x-heroicon-o-paper-clip class="w-4 h-4 text-primary-500" />
                                Fotos e Anexos
                            </h3>

                            {{-- Anexos já salvos (ao editar) --}}
                            @if($editandoId)
                            @php $anexosExistentes = $this->getAnexosEditando(); @endphp
                            @if($anexosExistentes->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach($anexosExistentes as $anx)
                                <div class="relative group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                     style="width:90px;">
                                    @if($anx->isImage())
                                    <img src="{{ $anx->url() }}" alt="{{ $anx->nome_original }}"
                                         style="width:90px;height:70px;object-fit:cover;display:block;">
                                    @else
                                    <div style="width:90px;height:70px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;">
                                        <x-heroicon-o-document class="w-8 h-8 text-gray-400" />
                                    </div>
                                    @endif
                                    <p class="text-xs text-gray-500 truncate px-1 py-0.5">{{ $anx->nome_original }}</p>
                                    <button type="button" wire:click="removerAnexo({{ $anx->id }})"
                                        wire:confirm="Remover este anexo?"
                                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        ×
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            @endif

                            {{-- Upload novos: wire:model em batch separado → hook updatedUploadBatchGeral acumula --}}
                            <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg py-4 cursor-pointer hover:border-primary-400 transition">
                                <x-heroicon-o-arrow-up-tray class="w-6 h-6 text-gray-400" />
                                <span class="text-sm text-gray-500 dark:text-gray-400">Clique para adicionar imagens ou documentos</span>
                                <span class="text-xs text-gray-400">JPG, PNG, GIF, PDF, DOC, XLS — máx 10 MB cada</span>
                                <input type="file" wire:model="uploadBatchGeral" multiple
                                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                                       class="hidden" />
                            </label>
                            @error('formAnexosNovos.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                            @if(count($formAnexosNovos) > 0)
                            <p class="text-xs text-primary-600 mt-1">
                                {{ count($formAnexosNovos) }} arquivo(s) acumulado(s) para upload.
                            </p>
                            @endif
                        </div>

                </div>{{-- /corpo rolável --}}

                {{-- Footer fixo com botões --}}
                <div class="ata-footer flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <button type="button" wire:click="fecharModal"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvar"
                        class="px-5 py-2 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition">
                        {{ $editandoId ? 'Salvar Alterações' : 'Criar Ata' }}
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- ── MINI-MODAL: GERAR TAREFA DE TEMA ───────────────────────────── --}}
        @if($modalTarefaAberto)
        @php
            $inputTarCls = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none';
        @endphp
        <div class="ata-backdrop" style="z-index:10001!important;">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-md" style="overflow:hidden;">
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-blue-500" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Gerar Tarefa</h3>
                    </div>
                    <button type="button" wire:click="fecharModalTarefa" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Corpo --}}
                <div class="px-5 py-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da tarefa *</label>
                        <input type="text" wire:model="tarefaTitulo" placeholder="Nome da tarefa" class="{{ $inputTarCls }}" />
                        @error('tarefaTitulo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <textarea wire:model="tarefaDescricao" rows="2" placeholder="Detalhes da tarefa…" class="{{ $inputTarCls }} resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria *</label>
                            <select wire:model="tarefaCategoriaId" class="{{ $inputTarCls }}">
                                <option value="">— selecione —</option>
                                @foreach($this->getCategoriasSelect() as $cid => $cname)
                                <option value="{{ $cid }}">{{ $cname }}</option>
                                @endforeach
                            </select>
                            @error('tarefaCategoriaId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Responsável <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <select wire:model="tarefaResponsavelId" class="{{ $inputTarCls }}">
                                <option value="">— selecione —</option>
                                @foreach($this->getUsuariosSelect() as $uid => $uname)
                                <option value="{{ $uid }}">{{ $uname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Início <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <input type="date" wire:model="tarefaInicio" class="{{ $inputTarCls }}" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Duração (dias) <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <input type="number" wire:model="tarefaDuracao" min="1" placeholder="ex: 30" class="{{ $inputTarCls }}" />
                            @error('tarefaDuracao') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Prazo (data) <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <input type="date" wire:model="tarefaPrazo" class="{{ $inputTarCls }}" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">O prazo por data tem prioridade. Se informar início + duração, o prazo será calculado automaticamente.</p>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <button type="button" wire:click="fecharModalTarefa"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button type="button" wire:click="criarTarefaDeTema"
                        class="px-5 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition">
                        Criar Tarefa
                    </button>
                </div>
            </div>
        </div>
        @endif


    </div>
</x-filament-panels::page>
