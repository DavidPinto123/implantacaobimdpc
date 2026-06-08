<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #222;
        }

        /* HEADER */

        .header {
            width: 100%;
            background: #f2b705;
            padding: 10px;
        }

        .header-title {
            text-align: left;
            font-weight: bold;
            font-size: 15px;
            letter-spacing: 2px;
        }

        /* INFO */

        .info-box {
            border: 1px solid #ddd;
            padding: 8px 12px;
            margin-top: 18px;
            background: #fafafa;
        }

        .info {
            width: 100%;
            border-collapse: collapse;
        }

        .info td {
            padding: 4px 1px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 140px;
            white-space: nowrap;
            padding-right: 6px;
        }

        /* SECTIONS */

        .section {
            margin-top: 28px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 14px;
        }

        /* ENTREGAS */

        .entrega {
            border: 1px solid #e3e3e3;
            padding: 14px;
            margin-bottom: 16px;
            background: #fafafa;

            page-break-inside: avoid;
            break-inside: avoid;
        }

        .entrega-title {
            font-size: 15px;
            font-weight: bold;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .entrega-head {
            width: 100%;
        }

        .entrega-head:after {
            content: "";
            display: block;
            clear: both;
        }

        .entrega-head .status {
            float: right;
            margin-left: 10px;
        }

        .status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }

        .status-entregue {
            background: #d4edda;
            color: #155724;
        }

        .status-nao {
            background: #f8d7da;
            color: #721c24;
        }

        .entrega-comentario {
            margin-bottom: 10px;
        }

        .entrega-doc {
            margin-top: 6px;
            font-size: 12px;
        }

        /* GRID DE IMAGENS */

        .entrega-imagens {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .entrega-imagens td {
            width: 33%;
            padding: 6px;
            text-align: center;
            vertical-align: top;
        }

        /* IMAGENS DAS ENTREGAS */

        .img-fallback {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border: 1px solid #ccc;
        }

        /* FOTOS ADICIONAIS */

        .photos {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .photos td {
            width: 33%;
            padding: 6px;
            text-align: center;
        }

        .photos img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border: 1px solid #ccc;
        }

        .fotos-titulo {
            margin-top: 12px;
            margin-bottom: 10px;
            clear: both;
        }

        /* evita ícones gigantes */

        img {
            max-width: 100%;
        }



        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #444;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .resumo-entregas-lista {
            width: 100%;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .resumo-entrega-item {
            width: 100%;
            border-bottom: 1px solid #d9d9d9;
            padding: 10px 0;
            font-size: 0;
        }

        .resumo-entrega-esquerda {
            display: inline-block;
            width: 52%;
            vertical-align: middle;
            font-size: 14px;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .resumo-entrega-direita {
            display: inline-block;
            width: 48%;
            vertical-align: middle;
            text-align: right;
            font-size: 14px;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .status-linha {
            display: inline-block;
            white-space: nowrap;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .badge-status {
            display: inline;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .badge-entregue {
            background: #dff3e4;
            color: #166534;
        }

        .badge-nao-entregue {
            background: #f8dede;
            color: #991b1b;
        }

        .status-previsao-inline {
            display: inline;
            margin-left: 8px;
            font-size: 11px;
            color: #6b7280;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .sem-fotos-adicionais {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #6b7280;
            padding: 8px 0;
        }

        .cor-titulo-entregas {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #6b7280;
            padding: 8px 0;
        }
    </style>

</head>

<body>

    @php
    use App\Support\PdfMedia;
    use Illuminate\Support\Str;

    if (!function_exists('pdf_src')) {
        function pdf_src(?string $img): ?string
        {
            if (! $img) {
                return null;
            }

            return PdfMedia::src($img);
        }
    }

    if (! function_exists('pdf_href')) {
        function pdf_href(?string $arquivo): ?string
        {
            if (! $arquivo) {
                return null;
            }

            return PdfMedia::href($arquivo);
        }
    }
    @endphp


    <!-- HEADER -->

    <table class="header">
        <tr>

            <td width="5%"></td>

            <td width="75%" class="header-title">
                RELATÓRIO FOTOGRÁFICO DE POSSE DO IMÓVEL
            </td>

            <td width="20%" style="text-align:right">
                <img src="{{ pdf_src('images/logo-relatorio-fotografico.png') }}" height="32">
            </td>

        </tr>
    </table>


    <!-- INFO -->

    <div class="info-box">

        <table class="info" style="width:100%; border-collapse:collapse;">

            <tr>
                <td style="width:100px;"><strong>Sigla:</strong></td>
                <td style="width:120px;">{{ $relatorio->sigla ?? 'Não definido'}}</td>

                <td style="width:80px;"><strong>Unidade:</strong></td>
                <td>{{ $relatorio->projeto->nome ?? 'Não definido'}}</td>
            </tr>

            <tr>
                <td style="width:140px;"><strong>Gestor(a):</strong></td>
                <td>{{ $relatorio->autor->name ?? 'Não definido'}}</td>

                <td style="width:100px;"><strong>Data:</strong></td>
                <td>{{ $relatorio->created_at->format('d/m/Y H:i') }}</td>
            </tr>

        </table>


        <table class="info" style="width:100%; border-collapse:collapse; margin-top:6px;">

            <tr>
                <td style="width:140px;"><strong>Tipo da unidade:</strong></td>
                <td>
                    {{
                        $relatorio->tipo_unidade === 'bts'
                            ? 'BTS'
                            : ($relatorio->tipo_unidade === 'padrao'
                                ? 'Padrão'
                                : 'Não definido')
                    }}
                </td>
            </tr>

            <tr>
                <td style="width:140px;"><strong>Endereço:</strong></td>
                <td>{{ $relatorio->endereco ?? 'Não definido'}}</td>
            </tr>

            <tr>
                <td style="width:140px;"><strong>Data da posse:</strong></td>
                <td style=" font-size: 13px; color: red;">
                    <strong>{{ $relatorio->data_posse ? $relatorio->data_posse->format('d/m/Y') : 'Não definido' }}</strong>
                </td>
            </tr>

            <tr>
                <td style="width:140px;"><strong>Status do termo de posse:</strong></td>
                <td>
                    {{
                        match($relatorio->status_termo_de_posse) {
                            'pendente' => 'Pendente',
                            'em_validacao' => 'Em validação',
                            'em_assinatura' => 'Em assinatura',
                            'assinado' => 'Assinado',
                            default => $relatorio->status_termo_de_posse ?? 'Não definido',
                        }
                    }}
                </td>
            </tr>

        </table>

    </div>

    <!-- RESUMO DAS ENTREGAS CONTRATUAIS -->
    <div class="section">
        <div class="section-title">
            Resumo das entregas contratuais
        </div>

        @php
        $entregas = $relatorio->entregas_contratuais ?? [];

        if (is_string($entregas)) {
        $entregas = json_decode($entregas, true) ?? [];
        }

        if (!is_array($entregas)) {
        $entregas = [];
        }

        usort($entregas, function ($a, $b) {
        $statusA = ($a['status'] ?? '') === 'entregue' ? 1 : 0;
        $statusB = ($b['status'] ?? '') === 'entregue' ? 1 : 0;

        if ($statusA !== $statusB) {
        return $statusA <=> $statusB;
            }

            return strcmp($a['titulo'] ?? '', $b['titulo'] ?? '');
            });
            @endphp

            @if(count($entregas))
            <div class="resumo-entregas-lista">
                @foreach($entregas as $item)
                @php
                $entregue = ($item['status'] ?? '') === 'entregue';
                $dataPrevista = !empty($item['data_prevista'])
                ? \Carbon\Carbon::parse($item['data_prevista'])->format('d/m/Y')
                : null;
                @endphp

                <div class="resumo-entrega-item">
                    <div class="resumo-entrega-esquerda">
                        <span style="font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; font-weight: 700;">
                            {{ $item['titulo'] ?? 'Sem título' }}
                        </span>
                    </div>

                    <div class="resumo-entrega-direita">
                        @if($entregue)
                        <span class="status-linha">
                            <span class="badge-status badge-entregue">Entregue</span>
                        </span>
                        @else
                        <span class="status-linha">
                            <span class="badge-status badge-nao-entregue">Não entregue</span>

                            @if($dataPrevista)
                            <span class="status-previsao-inline">
                                Previsão: {{ $dataPrevista }}
                            </span>
                            @endif
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div style="font-family: DejaVu Sans, Arial, sans-serif;" class="cor-titulo-entregas">
                Sem entregas contratuais cadastradas.
            </div>
            @endif
    </div>

    <!-- ENTREGAS CONTRATUAIS -->

    <div class="section">

        <div class="section-title">
            Detalhamento das entregas contratuais
        </div>

        @php
        $entregas = $relatorio->entregas_contratuais ?? [];

        if (is_string($entregas)) {
        $entregas = json_decode($entregas, true) ?? [];
        }

        if (!is_array($entregas)) {
        $entregas = [];
        }

        usort($entregas, function ($a, $b) {
        $statusA = ($a['status'] ?? '') === 'entregue' ? 1 : 0;
        $statusB = ($b['status'] ?? '') === 'entregue' ? 1 : 0;

        if ($statusA !== $statusB) {
        return $statusA <=> $statusB;
            }

            return strcmp($a['titulo'] ?? '', $b['titulo'] ?? '');
            });
            @endphp

            @if(count($entregas))

            @foreach($entregas as $item)

            <div class="entrega">

                <div class="entrega-head">
                    @if(($item['status'] ?? '') == 'entregue')
                    <span class="status status-entregue">Entregue</span>
                    @else
                    <span class="status status-nao">Não entregue</span>
                    @endif

                    <div class="entrega-title">
                        {{ $item['titulo'] ?? '' }}
                    </div>
                </div>

                @if(($item['status'] ?? '') != 'entregue' && !empty($item['data_prevista']))

                <div>
                    <strong>Previsão:</strong>
                    {{ \Carbon\Carbon::parse($item['data_prevista'])->format('d/m/Y') }}
                </div>

                @endif

                @if(!empty($item['comentario']))

                <div class="entrega-comentario">
                    <strong>Comentário:</strong>
                    {{ $item['comentario'] }}
                </div>

                @endif

                @if(!empty($item['arquivo']))

                @php
                $arquivos = $item['arquivo'];

                if (!is_array($arquivos)) {
                $arquivos = [$arquivos];
                }

                $imagens = [];
                $documentos = [];

                foreach ($arquivos as $arquivo) {
                $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $imagens[] = $arquivo;
                } else {
                $documentos[] = $arquivo;
                }
                }
                @endphp

                @if(count($documentos))

                <div style="margin-top:8px;">
                    <strong>Documentos:</strong>
                </div>

                @foreach($documentos as $arquivo)

                @php
                $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                $url = pdf_href($arquivo);
                @endphp

                @if($ext == 'pdf')

                <div class="entrega-doc" style="display:block; margin-bottom:6px;">
                    📄 Documento PDF:
                    <a href="{{ $url }}" style="text-decoration:none;">Abrir</a>
                </div>

                @elseif(in_array($ext, ['doc', 'docx']))

                <div class="entrega-doc" style="display:block; margin-bottom:6px;">
                    📝 Documento Word:
                    <a href="{{ $url }}" style="text-decoration:none;">Abrir</a>
                </div>

                @endif

                @endforeach

                @endif

                @if(count($imagens))

                <div class="fotos-titulo">
                    <strong>Fotos:</strong>
                </div>

                <table class="entrega-imagens">

                    @foreach(array_chunk($imagens, 3) as $row)

                    <tr>

                        @foreach($row as $img)

                        @php
                        $src = pdf_src($img);
                        @endphp

                        <td>
                            @if($src)
                            <a href="{{ pdf_href($img) }}" target="_blank" style="text-decoration:none; display:block;">
                                <img src="{{ $src }}" class="img-fallback">
                            </a>
                            @endif
                        </td>

                        @endforeach

                        @if(count($row) < 3)
                            @for($i=0; $i < 3 - count($row); $i++)
                            <td>
                            </td>
                            @endfor
                            @endif

                    </tr>

                    @endforeach

                </table>

                @endif

                @endif

            </div>

            @endforeach

            @else

            <div class="cor-titulo-entregas">
                Sem entregas contratuais cadastradas.
            </div>

            @endif

    </div>


    <!-- FOTOS ADICIONAIS -->

    <div class="section">
        <div class="section-title">
            Fotos adicionais
        </div>

        @php
        $fotos = $relatorio->fotos ?? [];

        if (is_string($fotos)) {
        $fotos = json_decode($fotos, true) ?? [];
        }

        if (!is_array($fotos)) {
        $fotos = [];
        }

        $fotos = array_values(array_filter($fotos));
        @endphp

        @if(count($fotos))
        <table class="photos">
            @foreach(array_chunk($fotos, 3) as $row)
            <tr>
                @foreach($row as $foto)
                @php
                $src = pdf_src($foto);
                @endphp

                <td>
                    @if($src)
                    <a href="{{ pdf_href($foto) }}" style="text-decoration:none; display:block;">
                        <img src="{{ $src }}" class="img-fallback">
                    </a>
                    @else
                    <div class="img-fallback">
                        Imagem não encontrada
                    </div>
                    @endif
                </td>
                @endforeach

                @if(count($row) < 3)
                    @for($i=0; $i < 3 - count($row); $i++)
                    <td>
                    </td>
                    @endfor
                    @endif
            </tr>
            @endforeach
        </table>
        @else
        <div class="sem-fotos-adicionais">
            Sem fotos adicionais.
        </div>
        @endif
    </div>

</body>

</html>
