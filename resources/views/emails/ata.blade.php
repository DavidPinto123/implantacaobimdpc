<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #1a1a1a; }
    .wrapper { max-width: 680px; margin: 24px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .top-bar { background: #1e3a5f; padding: 20px 32px; }
    .top-bar h1 { color: #fff; font-size: 20px; margin: 0 0 4px 0; }
    .top-bar p { color: #93c5fd; font-size: 12px; margin: 0; }
    .projeto-badge { background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 12px; display: inline-block; margin: 16px 32px 0; }
    .titulo { font-size: 22px; font-weight: bold; padding: 8px 32px 0; color: #111; }
    .info-bar { display: flex; gap: 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; margin: 16px 0; }
    .info-item { flex: 1; padding: 12px 16px; border-right: 1px solid #e5e7eb; }
    .info-item:last-child { border-right: none; }
    .info-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #9ca3af; margin-bottom: 3px; }
    .info-value { font-size: 13px; font-weight: 600; color: #111; }
    .section { padding: 0 32px 20px; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; margin-bottom: 12px; }
    .participante-row { display: flex; align-items: baseline; gap: 8px; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
    .participante-nome { font-weight: 600; font-size: 13px; }
    .participante-sub { color: #6b7280; font-size: 12px; }
    .participante-email { color: #1e7abf; font-size: 12px; }
    .resumo-box { background: #f8fafc; border-left: 4px solid #1e7abf; padding: 12px 14px; font-size: 13px; line-height: 1.7; white-space: pre-wrap; border-radius: 0 4px 4px 0; }
    .tema-item { margin-bottom: 12px; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 6px; }
    .tema-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .tema-num { width: 22px; height: 22px; background: #1e3a5f; color: #fff; font-size: 11px; font-weight: bold; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .tema-titulo { font-weight: 600; font-size: 14px; }
    .tema-desc { font-size: 12.5px; color: #4b5563; line-height: 1.6; padding-left: 30px; white-space: pre-wrap; }
    .imagens-grid { display: flex; flex-wrap: wrap; gap: 10px; }
    .imagem-item { text-align: center; }
    .imagem-item img { max-width: 200px; max-height: 150px; border-radius: 4px; border: 1px solid #e5e7eb; display: block; }
    .imagem-nome { font-size: 10px; color: #9ca3af; margin-top: 3px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .doc-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f9fafb; border-radius: 4px; margin-bottom: 6px; font-size: 13px; }
    .youtube-btn { display: inline-block; background: #dc2626; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; }
    .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 14px 32px; font-size: 11px; color: #9ca3af; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="wrapper">

    {{-- Topo --}}
    <div class="top-bar">
        <h1>Ata de Reunião</h1>
        <p>Sistema de Gestão de Implantações BIM — DPC Consultoria</p>
    </div>

    {{-- Projeto + Título --}}
    <div class="projeto-badge">{{ $ata->projeto?->nome ?? 'Sem projeto' }}</div>
    <div class="titulo">{{ $ata->titulo }}</div>

    {{-- Info bar --}}
    <div class="info-bar" style="margin-left:32px; margin-right:32px;">
        <div class="info-item">
            <div class="info-label">Data</div>
            <div class="info-value">{{ $ata->data_reuniao->format('d/m/Y') }}</div>
        </div>
        @if($ata->hora_inicio)
        <div class="info-item">
            <div class="info-label">Horário</div>
            <div class="info-value">
                {{ \Carbon\Carbon::parse($ata->hora_inicio)->format('H:i') }}
                @if($ata->hora_fim) – {{ \Carbon\Carbon::parse($ata->hora_fim)->format('H:i') }} @endif
            </div>
        </div>
        @endif
        @if($ata->local)
        <div class="info-item">
            <div class="info-label">Local</div>
            <div class="info-value">{{ $ata->local }}</div>
        </div>
        @endif
    </div>

    {{-- Participantes --}}
    @if($ata->participantes->isNotEmpty())
    <div class="section">
        <div class="section-title">Participantes</div>
        @foreach($ata->participantes as $p)
        <div class="participante-row">
            <span class="participante-nome">{{ $p->nome }}</span>
            @if($p->cargo || $p->empresa)
            <span class="participante-sub">{{ collect([$p->cargo, $p->empresa])->filter()->implode(' — ') }}</span>
            @endif
            @if($p->email)
            <span class="participante-email">· {{ $p->email }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Resumo --}}
    @if($ata->resumo)
    <div class="section">
        <div class="section-title">Resumo / Comentários</div>
        <div class="resumo-box">{{ $ata->resumo }}</div>
    </div>
    @endif

    {{-- Temas --}}
    @if($ata->temas->isNotEmpty())
    <div class="section">
        <div class="section-title">Temas Tratados</div>
        @foreach($ata->temas as $i => $tema)
        <div class="tema-item">
            <div class="tema-header">
                <span class="tema-num">{{ $i + 1 }}</span>
                <span class="tema-titulo">{{ $tema->titulo }}</span>
            </div>
            @if($tema->descricao)
            <div class="tema-desc">{{ $tema->descricao }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Fotos / Anexos --}}
    @if($ata->anexos->isNotEmpty())
    <div class="section">
        <div class="section-title">Fotos e Anexos</div>
        @php $imagens = $ata->anexos->filter(fn($a) => $a->isImage()); @endphp
        @php $docs = $ata->anexos->filter(fn($a) => !$a->isImage()); @endphp

        @if($imagens->isNotEmpty())
        <div class="imagens-grid" style="margin-bottom:12px;">
            @foreach($imagens as $img)
            <div class="imagem-item">
                <img src="{{ $img->url() }}" alt="{{ $img->nome_original }}">
                <div class="imagem-nome">{{ $img->nome_original }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @foreach($docs as $doc)
        <div class="doc-item">
            📄 <strong>{{ $doc->nome_original }}</strong>
            <span style="color:#9ca3af">({{ $doc->tamanhoFormatado() }})</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- YouTube --}}
    @if($ata->link_youtube)
    <div class="section">
        <div class="section-title">Gravação da Reunião</div>
        <a href="{{ $ata->link_youtube }}" class="youtube-btn">▶ Assistir gravação</a>
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
