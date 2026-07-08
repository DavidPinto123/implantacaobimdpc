<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #1a1a1a; }
    .page { padding: 20mm 18mm 22mm; }

    /* Cabeçalho */
    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 16px; }
    .header-titulo { font-size: 18pt; font-weight: bold; color: #1e3a5f; }
    .header-sub { font-size: 9pt; color: #555; margin-top: 2px; }

    /* Projeto / título */
    .ata-projeto { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #1e7abf; letter-spacing: 0.5px; margin-bottom: 4px; }
    .ata-titulo { font-size: 14pt; font-weight: bold; color: #1a1a1a; margin-bottom: 12px; }

    /* Info grid */
    .info-grid { display: table; width: 100%; margin-bottom: 16px; border: 1px solid #ddd; border-radius: 4px; }
    .info-row { display: table-row; }
    .info-cell { display: table-cell; padding: 6px 10px; font-size: 9pt; border-bottom: 1px solid #eee; width: 25%; }
    .info-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #888; display: block; margin-bottom: 2px; }

    /* Seções */
    .section { margin-bottom: 14px; }
    .section-title { font-size: 9pt; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; border-bottom: 1px solid #1e3a5f; padding-bottom: 3px; margin-bottom: 8px; }

    /* Participantes */
    .participantes-grid { display: table; width: 100%; }
    .participante { display: table-row; }
    .participante-cell { display: table-cell; padding: 4px 8px; font-size: 9pt; border-bottom: 1px solid #f0f0f0; }
    .participante-nome { font-weight: bold; width: 35%; }
    .participante-sub { color: #666; font-size: 8.5pt; }

    /* Resumo */
    .resumo-text { font-size: 9.5pt; line-height: 1.6; white-space: pre-wrap; background: #f8f9fa; padding: 8px 10px; border-left: 3px solid #1e7abf; }

    /* Temas */
    .tema { margin-bottom: 12px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 3px; }
    .tema-numero { display: inline-block; width: 18px; height: 18px; background: #1e3a5f; color: white; font-size: 8pt; font-weight: bold; text-align: center; line-height: 18px; border-radius: 50%; margin-right: 6px; }
    .tema-titulo { font-weight: bold; font-size: 10pt; }
    .tema-descricao { font-size: 9pt; color: #444; line-height: 1.5; margin-top: 4px; padding-left: 24px; white-space: pre-wrap; }
    .tema-anexos { padding-left: 24px; margin-top: 6px; }
    .tema-anexos-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #888; margin-bottom: 4px; }

    /* Anexos / imagens */
    .imagens-grid { }
    .imagem-item { display: inline-block; margin: 4px; vertical-align: top; text-align: center; }
    .imagem-item img { max-width: 180px; max-height: 140px; border: 1px solid #ddd; border-radius: 3px; }
    .imagem-nome { font-size: 7pt; color: #888; margin-top: 2px; max-width: 180px; overflow: hidden; }
    .doc-item { padding: 5px 8px; margin-bottom: 4px; background: #f5f5f5; border-radius: 3px; font-size: 9pt; }

    /* YouTube */
    .youtube-link { font-size: 9pt; color: #c00; }

    /* Rodapé */
    .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8pt; color: #999; display: flex; justify-content: space-between; }
</style>
</head>
<body>

{{-- Script dompdf: numeração de páginas no formato N/Total --}}
<script type="text/php">
    if (isset($pdf)) {
        $w    = $pdf->get_width();
        $h    = $pdf->get_height();
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $pdf->page_text($w / 2 - 16, $h - 14, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7, array(0.6, 0.6, 0.6));
    }
</script>

<div class="page">

    {{-- Cabeçalho --}}
    <div class="header">
        <div>
            <div class="header-titulo">Ata de Reunião</div>
            <div class="header-sub">Sistema de Gestão de Implantações BIM — DPC Consultoria</div>
        </div>
        <div style="text-align:right; font-size:8.5pt; color:#555;">
            Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
        </div>
    </div>

    {{-- Projeto + Título --}}
    <div class="ata-projeto">{{ $ata->projeto?->nome ?? 'Sem projeto' }}</div>
    <div class="ata-titulo">{{ $ata->titulo }}</div>

    {{-- Informações básicas --}}
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Data</span>
                {{ $ata->data_reuniao->format('d/m/Y') }}
            </div>
            <div class="info-cell">
                <span class="info-label">Horário</span>
                @if($ata->hora_inicio)
                    {{ \Carbon\Carbon::parse($ata->hora_inicio)->format('H:i') }}
                    @if($ata->hora_fim) – {{ \Carbon\Carbon::parse($ata->hora_fim)->format('H:i') }} @endif
                @else
                    —
                @endif
            </div>
            <div class="info-cell" style="width:50%">
                <span class="info-label">Local</span>
                {{ $ata->local ?: '—' }}
            </div>
        </div>
    </div>

    {{-- Participantes --}}
    @if($ata->participantes->isNotEmpty())
    <div class="section">
        <div class="section-title">Participantes</div>
        <div class="participantes-grid">
            @foreach($ata->participantes as $p)
            <div class="participante">
                <div class="participante-cell participante-nome">{{ $p->nome }}</div>
                <div class="participante-cell participante-sub">{{ $p->cargo ?: '' }}@if($p->cargo && $p->empresa) — @endif{{ $p->empresa ?: '' }}</div>
                <div class="participante-cell participante-sub" style="color:#1e7abf">{{ $p->email ?: '' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Resumo --}}
    @if($ata->resumo)
    <div class="section">
        <div class="section-title">Resumo / Comentários</div>
        <div class="resumo-text">{{ $ata->resumo }}</div>
    </div>
    @endif

    {{-- Temas — com seus próprios anexos inline --}}
    @if($ata->temas->isNotEmpty())
    <div class="section">
        <div class="section-title">Temas Tratados</div>
        @foreach($ata->temas->load('anexos') as $i => $tema)
        <div class="tema">
            <span class="tema-numero">{{ $i + 1 }}</span>
            <span class="tema-titulo">{{ $tema->titulo }}</span>
            @if($tema->descricao)
            <div class="tema-descricao">{{ $tema->descricao }}</div>
            @endif

            {{-- Anexos deste tema --}}
            @if($tema->anexos->isNotEmpty())
            <div class="tema-anexos">
                <div class="tema-anexos-label">Arquivos do tema</div>
                <div class="imagens-grid">
                    @foreach($tema->anexos as $anexo)
                        @if($anexo->isImage())
                        @php
                            $path = $anexo->absolutePath();
                            $imgSrc = '';
                            if (file_exists($path)) {
                                $imgSrc = 'data:' . $anexo->mime_type . ';base64,' . base64_encode(file_get_contents($path));
                            }
                        @endphp
                        @if($imgSrc)
                        <div class="imagem-item">
                            <img src="{{ $imgSrc }}">
                            <div class="imagem-nome">{{ $anexo->nome_original }}</div>
                        </div>
                        @endif
                        @else
                        <div class="doc-item">📄 {{ $anexo->nome_original }} ({{ $anexo->tamanhoFormatado() }})</div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Anexos gerais (sem tema) --}}
    @php $anexosGerais = $ata->anexos->whereNull('tema_id'); @endphp
    @if($anexosGerais->isNotEmpty())
    <div class="section">
        <div class="section-title">Fotos e Anexos Gerais</div>
        <div class="imagens-grid">
            @foreach($anexosGerais as $anexo)
                @if($anexo->isImage())
                @php
                    $path = $anexo->absolutePath();
                    $imgSrc = '';
                    if (file_exists($path)) {
                        $imgSrc = 'data:' . $anexo->mime_type . ';base64,' . base64_encode(file_get_contents($path));
                    }
                @endphp
                @if($imgSrc)
                <div class="imagem-item">
                    <img src="{{ $imgSrc }}">
                    <div class="imagem-nome">{{ $anexo->nome_original }}</div>
                </div>
                @endif
                @else
                <div class="doc-item">📄 {{ $anexo->nome_original }} ({{ $anexo->tamanhoFormatado() }})</div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- YouTube --}}
    @if($ata->link_youtube)
    <div class="section">
        <div class="section-title">Gravação</div>
        <div class="youtube-link">{{ $ata->link_youtube }}</div>
    </div>
    @endif

    {{-- Rodapé --}}
    <div class="footer">
        <span>Registrado em {{ $ata->created_at->format('d/m/Y \à\s H:i') }}@if($ata->criador) por {{ $ata->criador->name }}@endif</span>
        <span>implantacaobimdpc.com.br</span>
    </div>

</div>
</body>
</html>
