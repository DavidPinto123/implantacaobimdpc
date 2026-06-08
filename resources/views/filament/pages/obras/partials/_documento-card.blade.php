@php
    /**
     * Variáveis esperadas:
     *   $itemExib        — array do documentosExibidos
     *   $borderRight     — string CSS
     *   $statusDocOpts   — array
     *   $badgeDocStyle   — array
     *   $construtorasDaObraDoc — array
     *   $documentosAtribuirAbertos — array
     *   $documentosVirtuaisAtribuirAbertos — array
     *   $documentosUploadBufferPorDoc — array
     *   $documentosUploadInputVersion — int
     */
    $isPersistido = $itemExib['persistido'];
    $doc = $itemExib['doc'];
    $nomeDoc = $itemExib['nome'];
    $docCategoria = $this->categoriaDoDocumento($nomeDoc);
    $isCnpjDoc = $nomeDoc === 'CNPJ (definitivo)';
    $cnpjStatusProjeto = $obra->projeto?->status_cnpj; // 'definitivo' | 'provisorio' | null
    $cnpjEhDefinitivo = $cnpjStatusProjeto === 'definitivo';
    $cnpjEhProvisorio = $cnpjStatusProjeto === 'provisorio';
    $docNomeLabel = $isCnpjDoc ? 'CNPJ' : $nomeDoc;
    $isArtTrancada = $this->isDocumentoArtTrancado($nomeDoc);

    if ($isPersistido) {
        if ($isCnpjDoc) {
            if ($cnpjEhDefinitivo) {
                $docBadgeLabel = 'Definitivo';
                $docBadgeStyle = $badgeDocStyle['enviado'];
            } elseif ($cnpjEhProvisorio) {
                $docBadgeLabel = 'Provisório';
                $docBadgeStyle = $badgeDocStyle['pendente'];
            } else {
                $docBadgeLabel = 'Pendente';
                $docBadgeStyle = $badgeDocStyle['pendente'];
            }
        } else {
            $docBadgeLabel = $statusDocOpts[$doc->status] ?? $doc->status;
            $docBadgeStyle = $badgeDocStyle[$doc->status] ?? '';
        }
        $permiteSelectFornecedor = $docCategoria === 'construtora'
            || ($docCategoria === 'manual' && (filled($doc->construtora_id) || in_array($doc->id, $documentosAtribuirAbertos, true)));
        $permiteRemover = $this->podeGerenciarDocumentos && $docCategoria !== 'automatico' && ! $isArtTrancada;
    } else {
        $docBadgeLabel = 'Pendente';
        $docBadgeStyle = $badgeDocStyle['pendente'];
        $permiteSelectFornecedor = $docCategoria === 'construtora'
            || ($docCategoria === 'manual' && in_array($nomeDoc, $documentosVirtuaisAtribuirAbertos, true));
        $permiteRemover = false;
    }
@endphp
<div class="vo-doc-item @if($isArtTrancada) vo-doc-item-locked @endif" style="{{ $borderRight }}">
    <div class="vo-doc-head">
        <span class="vo-doc-name">{{ $docNomeLabel }}</span>
        @if($isArtTrancada)
            <span title="Vinculado ao Controle de Medição"
                  style="font-size:0.55rem;padding:2px 7px;border-radius:1rem;font-weight:700;flex-shrink:0;background:var(--vo-info-bg,#e0f2fe);color:var(--vo-info-text,#0369a1);display:inline-flex;align-items:center;gap:4px;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Controle de Medição
            </span>
        @endif
        <span style="font-size:0.6rem;padding:2px 8px;border-radius:1rem;font-weight:700;flex-shrink:0;{{ $docBadgeStyle }}">
            {{ $docBadgeLabel }}
        </span>
        @if($permiteRemover)
            <button wire:click="removerDocumento({{ $doc->id }})"
                    wire:confirm="Tem certeza que deseja remover o documento '{{ $doc->nome }}'? Esta ação não pode ser desfeita."
                    title="Remover"
                    style="background:none;border:none;cursor:pointer;color:var(--vo-danger-text);padding:2px 4px;border-radius:4px;display:flex;align-items:center;flex-shrink:0;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
        @endif
    </div>

    {{-- Botão único "+ Atribuir à obra" para qualquer documento ainda não persistido --}}
    @if(! $isPersistido && $this->podeGerenciarDocumentos)
        <div style="padding:4px 0;">
            @php
                $msgConfirm = $docCategoria === 'construtora'
                    ? 'Esta ART normalmente é gerada automaticamente pelo Controle de Medição. Tem certeza que deseja atribuir manualmente?'
                    : null;
            @endphp
            <button type="button"
                    wire:click="atribuirDocumento('{{ addslashes($nomeDoc) }}')"
                    @if($msgConfirm)wire:confirm="{{ $msgConfirm }}"@endif
                    title="Atribuir este documento à obra"
                    style="background:none;border:1px dashed var(--vo-border);color:var(--vo-text-muted);font-size:0.62rem;padding:3px 10px;border-radius:4px;cursor:pointer;">
                + Atribuir à obra
            </button>
        </div>
    @endif

    {{-- Select de fornecedor: somente para documentos já persistidos --}}
    @if($isPersistido && $isArtTrancada)
        <div style="padding:4px 0;">
            <select class="vo-form-select"
                    style="margin:0;font-size:0.7rem;padding:3px 6px;width:100%;cursor:not-allowed;opacity:.85;"
                    disabled
                    title="Fornecedor definido pelo Controle de Medição">
                <option>{{ $doc->construtora?->nome ?? '— Sem fornecedor —' }}</option>
            </select>
            <div style="font-size:0.58rem;color:var(--vo-text-muted);margin-top:2px;font-style:italic;">
                Para alterar o fornecedor, edite no Controle de Medição.
            </div>
        </div>
    @elseif($isPersistido && $this->podeGerenciarDocumentos && $permiteSelectFornecedor)
        <div style="padding:4px 0;">
            <select class="vo-form-select"
                    style="margin:0;font-size:0.7rem;padding:3px 6px;width:100%;"
                    wire:model="documentosConstrutoraEdit.{{ $doc->id }}"
                    title="Fornecedor responsável">
                <option value="">— Sem fornecedor —</option>
                @foreach($construtorasDaObraDoc as $cId => $cNome)
                    <option value="{{ $cId }}">{{ $cNome }}</option>
                @endforeach
            </select>
        </div>
    @elseif($isPersistido && $this->podeGerenciarDocumentos && $docCategoria === 'manual')
        <div style="padding:2px 0;">
            <button type="button"
                    wire:click="abrirAtribuicaoFornecedor({{ $doc->id }})"
                    style="background:none;border:1px dashed var(--vo-border);color:var(--vo-text-muted);font-size:0.62rem;padding:2px 8px;border-radius:4px;cursor:pointer;">
                + Atribuir a um fornecedor?
            </button>
        </div>
    @endif

    <div class="vo-doc-row">
        <div style="flex:1;min-width:0;">
            @if($isCnpjDoc)
                @php
                    $cnpjValor = $cnpjEhDefinitivo
                        ? $obra->projeto?->cnpj
                        : $obra->projeto?->cnpj_provisorio;
                @endphp
                @if(filled($cnpjValor))
                    <span style="font-size:0.72rem;font-weight:700;color:var(--vo-text);font-family:ui-monospace,Menlo,monospace;">{{ $cnpjValor }}</span>
                @else
                    <span class="vo-doc-empty">Nenhum CNPJ cadastrado</span>
                @endif
            @elseif($isPersistido)
                @php
                    $arquivosNomes = $doc->arquivos_nomes_resolved;
                    $podeAnexarItem = $this->podeAnexarDocumentoBlade($doc);
                @endphp
                @if(count($arquivosNomes))
                    <div class="vo-doc-attachments">
                        @foreach($arquivosNomes as $index => $arquivoNome)
                            <div class="vo-doc-attachment" title="{{ $arquivoNome }}">
                                <span class="vo-doc-attachment-icon">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </span>
                                <span class="vo-doc-attachment-name">{{ $arquivoNome }}</span>
                                <span class="vo-doc-attachment-actions">
                                    <button type="button"
                                            wire:click="abrirArquivoDocumento({{ $doc->id }}, {{ $index }})"
                                            class="vo-doc-attachment-btn view"
                                            title="Visualizar">
                                        Ver
                                    </button>
                                    @if($podeAnexarItem)
                                        <button type="button"
                                                wire:click="removerArquivoDocumento({{ $doc->id }}, {{ $index }})"
                                                wire:confirm="Remover este arquivo?"
                                                class="vo-doc-attachment-btn remove"
                                                title="Remover">
                                            Remover
                                        </button>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <span class="vo-doc-empty">Sem anexo</span>
                @endif
            @endif
        </div>

        @if(! $isCnpjDoc && ! $isPersistido)
            <span class="vo-doc-empty">Clique em "+ Atribuir à obra" para criar este item</span>
        @endif
    </div>

    {{-- Bloco de upload --}}
    @if($isPersistido && ! $isCnpjDoc && $docCategoria !== 'automatico')
        @php
            $podeAnexar = $this->podeAnexarDocumentoBlade($doc);
            $bufferDoc = $documentosUploadBufferPorDoc[$doc->id] ?? [];
            $countBuffer = is_array($bufferDoc) ? count($bufferDoc) : 0;
        @endphp
        @if($podeAnexar)
            <div style="padding:8px 0 2px 0;border-top:1px dashed var(--vo-border-light);margin-top:8px;">
                <div style="font-size:0.6rem;font-weight:700;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Anexar PDFs</div>

                <div class="vo-doc-upload-wrap">
                    <button type="button" class="vo-doc-upload-btn">Selecionar PDF(s)</button>
                    <span class="vo-doc-upload-name">
                        @if($countBuffer)
                            {{ $countBuffer }} arquivo{{ $countBuffer > 1 ? 's' : '' }} pronto{{ $countBuffer > 1 ? 's' : '' }} para envio
                        @else
                            Nenhum arquivo selecionado
                        @endif
                    </span>
                    <input type="file"
                           accept="application/pdf,.pdf"
                           multiple
                           wire:key="upload-input-{{ $doc->id }}-{{ $documentosUploadInputVersion }}"
                           wire:model="documentosUploadPorDoc.{{ $doc->id }}"
                           class="vo-doc-upload-input">
                </div>

                @if($countBuffer)
                    <div class="vo-file-list" style="margin-top:6px;">
                        @foreach($bufferDoc as $bufIdx => $tmpFile)
                            <div class="vo-file-item">
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tmpFile->getClientOriginalName() }}</span>
                                <button type="button"
                                        wire:click="removerArquivoBuffer({{ $doc->id }}, {{ $bufIdx }})"
                                        class="vo-file-item-action"
                                        style="color:var(--vo-danger-text);"
                                        title="Remover da seleção">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex;gap:6px;margin-top:8px;align-items:center;">
                    <button type="button"
                            wire:click="fazerUploadDocumento({{ $doc->id }})"
                            wire:loading.attr="disabled"
                            wire:target="fazerUploadDocumento({{ $doc->id }})"
                            @disabled($countBuffer === 0)
                            style="background:{{ $countBuffer ? 'var(--vo-accent)' : 'var(--vo-border-light)' }};color:#111;border:none;padding:6px 12px;border-radius:6px;font-weight:700;font-size:0.7rem;cursor:{{ $countBuffer ? 'pointer' : 'not-allowed' }};opacity:{{ $countBuffer ? '1' : '.55' }};">
                        <span wire:loading.remove wire:target="fazerUploadDocumento({{ $doc->id }})">
                            Enviar{{ $countBuffer > 1 ? ' '.$countBuffer.' arquivos' : '' }}
                        </span>
                        <span wire:loading wire:target="fazerUploadDocumento({{ $doc->id }})">Enviando…</span>
                    </button>
                    @error('documentosUploadBufferPorDoc.'.$doc->id.'.*')
                        <span style="font-size:0.62rem;color:var(--vo-danger-text);">{{ $message }}</span>
                    @enderror
                </div>
                <div class="vo-doc-help" style="margin-top:4px;">Apenas PDF, máx 50MB cada. Pode selecionar várias vezes — os arquivos se acumulam.</div>
            </div>
        @endif
    @endif
</div>
