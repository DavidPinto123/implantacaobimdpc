<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    {{-- Helpers para imagens no PDF (Base64 + normalização de caminho) e campos tri-estado --}}
    @php
        use Illuminate\Support\Str;
        use App\Models\Projeto;
        use App\Models\Marca;

        $projeto = Projeto::find($record?->projeto_id);
        $marca = Marca::find($record->marca_id);

        if (!function_exists('pdf_src')) {
            /**
             * Retorna um "data:image/...;base64,..." estável para o DOMPDF.
             * Aceita caminhos relativos, absolutos, public/storage e storage/app/public.
             * Aceita também URLs (http/https) e já retorna como estão.
             */
            function pdf_src(?string $img): ?string
            {
                if (!$img) {
                    return null;
                }

                // Se for URL/data/file, retorna direto
                if (Str::startsWith($img, ['http://', 'https://', 'data:', 'file://'])) {
                    return $img;
                }

                // Normaliza separadores e remove / inicial duplicado
                $img = ltrim(str_replace('\\', '/', $img), '/');

                // Candidatos de caminho físico no servidor
                $candidates = [
                    public_path($img), // ex: images/logo.png
                    public_path('storage/' . $img), // ex: storage/uploads/foo.jpg (symlink)
                    storage_path('app/public/' . $img), // ex: uploads/foo.jpg
                    $img, // se já for absoluto
                ];

                foreach ($candidates as $path) {
                    if (is_file($path)) {
                        $mime = mime_content_type($path) ?: 'image/jpeg';
                        $data = @file_get_contents($path);
                        if ($data === false) {
                            return null;
                        }
                        return 'data:' . $mime . ';base64,' . base64_encode($data);
                    }
                }
                return null;
            }
        }

        if (!function_exists('pdf_render_images')) {
            /**
             * Renderiza um array (ou string) de imagens como <img> em linha.
             * Usa pdf_src() e mostra fallback quando arquivo não existe.
             */
            function pdf_render_images($items): string
            {
                if (empty($items)) {
                    return '<p class="field-center">Sem imagens cadastradas.</p>';
                }
                if (is_string($items)) {
                    $items = [$items];
                }
                if (!is_iterable($items)) {
                    return '<p class="field-center">Sem imagens cadastradas.</p>';
                }

                $html = '';
                foreach ($items as $img) {
                    // Se vier como array (alguns uploads retornam array), tenta chaves comuns
                    $candidate = null;
                    if (is_array($img)) {
                        $candidate =
                            $img['path'] ??
                            ($img['url'] ?? ($img['file_path'] ?? ($img['filepath'] ?? ($img['src'] ?? null))));
                        if (!$candidate) {
                            $first = reset($img);
                            if (is_string($first)) {
                                $candidate = $first;
                            }
                        }
                    } else {
                        $candidate = (string) $img;
                    }

                    $src = $candidate ? pdf_src($candidate) : null;

                    if ($src) {
                        $html .= '<img src="' . $src . '" alt="" class="pdf-img">';
                    } else {
                        $html .= '<div class="pdf-img-fallback">imagem indisponível</div>';
                    }
                }
                return $html;
            }
        }

        if (!function_exists('pdf_tri')) {
            /**
             * Converte 1/0/null -> "Sim" / "Não" / "Não se aplica".
             * Aceita 1, '1', true | 0, '0', false | null/outros.
             */
            function pdf_tri($v, array $labels = ['Sim', 'Não', 'Não se aplica']): string
            {
                if (is_string($v)) {
                    $v = trim($v);
                    if ($v === '') {
                        $v = null;
                    }
                }

                if ($v === 1 || $v === '1' || $v === true) {
                    return $labels[0];
                }
                if ($v === 0 || $v === '0' || $v === false) {
                    return $labels[1];
                }
                return $labels[2];
            }
        }
    @endphp

    <style>
        /* Tipografia e layout geral */
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            line-height: 1.6;
            font-size: 13px;
            margin: 20px
        }

        .header {
            text-align: center;
            margin-bottom: 30px
        }

        .header img {
            height: 60px;
            margin-bottom: 8px
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-top: 5px;
            color: #222
        }

        .section {
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            page-break-inside: avoid
        }

        .section h3 {
            background: #f7f7f7;
            padding: 8px 12px;
            font-size: 15px;
            margin: 0;
            border-bottom: 1px solid #e0e0e0
        }

        /* IMPORTANTE: sem grid/flex para o DOMPDF */
        .section-content {
            display: block;
            padding: 10px 12px
        }

        .field {
            margin-bottom: 10px
        }

        .field strong {
            display: inline-block;
            width: 160px;
            color: #555;
            font-weight: 600;
            vertical-align: top
        }

        .field span {
            display: inline-block;
            max-width: calc(100% - 165px);
            color: #111;
            font-weight: 400;
            vertical-align: top
        }

        .field-center {
            text-align: center;
            margin-top: 10px;
            display: block;
            width: 100%
        }

        a {
            color: #1a73e8;
            text-decoration: underline
        }

        /* Imagens (layout estável p/ DOMPDF) */
        .images {
            display: block;
            margin: 12px 0;
            clear: both;
            page-break-inside: avoid
        }

        .images .image-title {
            font-weight: bold;
            margin-bottom: 6px
        }

        /* Nada de object-fit/flex/grid */
        .pdf-img {
            display: inline-block;
            width: 88mm;
            height: auto;
            /* ~2 por linha em A4 */
            margin: 0 6mm 6mm 0;
            vertical-align: top;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1)
        }

        .pdf-img-fallback {
            display: inline-flex;
            width: 88mm;
            height: 60mm;
            margin: 0 6mm 6mm 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #888;
            vertical-align: top
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ pdf_src('images/logo-smart.png') }}" alt="SmartFit">
        <div class="title">Relatório de Visita Técnica</div>
    </div>

    <!-- Informações Gerais -->
    <div class="section">
        <h3>Informações Gerais</h3>
        <div class="section-content">
            <div class="field"><strong>ID:</strong> <span>{{ $record?->id }}</span></div>
            <div class="field"><strong>Projeto:</strong> <span>{{ $projeto->nome }}</span></div>
            <div class="field"><strong>Nº Relatório:</strong> <span>{{ $record?->numero_relatorio_vt }}</span></div>
            <div class="field"><strong>Autor:</strong> <span>{{ $record?->autor }}</span></div>
            <div class="field"><strong>Responsável Técnico:</strong> <span>{{ $record?->responsavel_tecnico }}</span>
            </div>

            <div class="field"><strong>Sicronizado em:</strong>
                <span>
                    @if (!empty($record->sicronizado_em))
                        {{ \Carbon\Carbon::parse($record->sicronizado_em)->translatedFormat('d \d\e F \d\e Y') }}
                    @else
                        Não se aplica
                    @endif
                </span>
            </div>

            <div class="field"><strong>Prazo de Obras Outro:</strong>
                <span>{{ $record?->prazo_de_obras_outro ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Marca:</strong> <span>{{ $marca->nome }}</span></div>
        </div>
    </div>

    <!-- Unidade -->
    <div class="section">
        <h3>Unidade</h3>
        <div class="section-content">
            <div class="field"><strong>Unidade Relatório:</strong>
                <span>{{ $record?->unidade_relatorio ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Unidade:</strong> <span>{{ $record?->unidade ?? 'Não se aplica' }}</span></div>
            <div class="field"><strong>Endereço:</strong> <span>{{ $record?->endereco ?? 'Não se aplica' }}</span>
            </div>
        </div>
    </div>

    <!-- Prazos -->
    <div class="section">
        <h3>Prazos</h3>
        <div class="section-content">
            <div class="field"><strong>Prazo de Obras:</strong>
                <span>{{ $record->prazo_de_obras ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Iniciado em:</strong>
                <span>
                    @if (!empty($record->iniciado_em))
                        {{ \Carbon\Carbon::parse($record->iniciado_em)->translatedFormat('d \d\e F \d\e Y') }}
                    @else
                        Não se aplica
                    @endif
                </span>
            </div>
            <div class="field"><strong>Concluído em:</strong>
                <span>
                    @if (!empty($record->concluido_em))
                        {{ \Carbon\Carbon::parse($record->concluido_em)->translatedFormat('d \d\e F \d\e Y') }}
                    @else
                        Não se aplica
                    @endif
                </span>
            </div>
            <div class="field"><strong>Criado em:</strong>
                <span>
                    @if (!empty($record->created_at))
                        {{ \Carbon\Carbon::parse($record->created_at)->translatedFormat('d \d\e F \d\e Y') }}
                    @else
                        Não se aplica
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Detalhes Técnicos -->
    <div class="section">
        <h3>Detalhes Técnicos</h3>
        <div class="section-content">

            <div class="field"><strong>Tipo Estrutura:</strong>
                <span>{{ $record?->tipo_estrutura ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Tipo Estrutura Outro:</strong>
                <span>{{ $record?->tipo_estrutura_outro ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Link Drive Fotos/Vídeos:</strong>
                <span>
                    @if ($record?->link_drive_fotos_e_videos)
                        <a
                            href="{{ $record->link_drive_fotos_e_videos }}">{{ $record->link_drive_fotos_e_videos }}</a>
                    @else
                        Não se aplica
                    @endif
                </span>
            </div>

            <!-- Energia -->
            <div class="field"><strong>Entrada de Energia:</strong>
                <span>{{ pdf_tri($record?->entrada_de_energia) }}</span>
            </div>
            <div class="field"><strong>Descrição Energia:</strong>
                <span>{{ $record?->descricao_energia ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos da Entrada de Energia:</div>
                {!! pdf_render_images($record?->foto_entrada_de_energia) !!}
            </div>

            <div class="field"><strong>Energia Provisória:</strong>
                <span>{{ pdf_tri($record?->energia_provisoria) }}</span>
            </div>
            <div class="field"><strong>Descrição Energia Provisória:</strong>
                <span>{{ $record?->descricao_energia_provisoria ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Energia Provisória:</div>
                {!! pdf_render_images($record?->foto_energia_provisoria) !!}
            </div>

            <!-- Medições -->
            <div class="field"><strong>Única Medição:</strong>
                <span>{{ pdf_tri($record?->unica_medicao) }}</span>
            </div>
            <div class="field"><strong>Descrição Medição:</strong>
                <span>{{ $record?->descricao_medicao ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Única Medição:</div>
                {!! pdf_render_images($record?->foto_unica_medicao) !!}
            </div>

            <!-- SPDA -->
            <div class="field"><strong>SPDA:</strong> <span>{{ pdf_tri($record?->spda) }}</span></div>
            <div class="field"><strong>Descrição SPDA:</strong>
                <span>{{ $record?->descricao_spda ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto SPDA:</div>
                {!! pdf_render_images($record?->foto_spda) !!}
            </div>

            <!-- Telefonia -->
            <div class="field"><strong>Telegonia (DG) dentro do shell:</strong>
                <span>{{ pdf_tri($record?->telegonia_dg) }}</span>
            </div>
            <div class="field"><strong>Descrição Telefonia:</strong>
                <span>{{ $record?->descricao_telefonia ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Telefonia (DG):</div>
                {!! pdf_render_images($record?->foto_telegonia_dg) !!}
            </div>

            <!-- Cobertura / Estrutura -->
            <div class="field"><strong>Cobertura com Isolamento térmico:</strong>
                <span>{{ pdf_tri($record?->cobertura_isolamento) }}</span>
            </div>
            <div class="field"><strong>Descrição Cobertura Isolamento:</strong>
                <span>{{ $record?->descricao_cobertura_isolamento ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Cobertura Isolamento:</div>
                {!! pdf_render_images($record?->foto_cobertura_isolamento) !!}
            </div>

            <div class="field"><strong>Cobertura com vãos inferiores a 1,5m nos dois sentidos:</strong>
                <span>{{ pdf_tri($record?->necessario_estrutura_auxiliar) }}</span>
            </div>
            <div class="field"><strong>Descrição Estrutura Auxiliar:</strong>
                <span>{{ $record?->descricao_estrutura_auxiliar ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Necessário Estrutura Auxiliar:</div>
                {!! pdf_render_images($record?->foto_necessario_estrutura_auxiliar) !!}
            </div>

            <div class="field"><strong>Imóvel com estrutura para fachada:</strong>
                <span>{{ pdf_tri($record?->estrutura_fachada) }}</span>
            </div>
            <div class="field"><strong>Descrição Estrutura Fachada:</strong>
                <span>{{ $record?->descricao_estrutura_fachada ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Estrutura Fachada:</div>
                {!! pdf_render_images($record?->foto_estrutura_fachada) !!}
            </div>

            <!-- Furações Laje -->
            <div class="field"><strong>Permitidas Furações Laje:</strong>
                <span>{{ pdf_tri($record?->permitidas_furacoes_laje) }}</span>
            </div>
            <div class="field"><strong>Descrição Furações Laje:</strong>
                <span>{{ $record?->descricao_furacoes_laje ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Furações Laje:</div>
                {!! pdf_render_images($record?->foto_furacoes_laje) !!}
            </div>

            <div class="field"><strong>Sobrecarga mínima da laje (500kg/m²):</strong>
                <span>{{ pdf_tri($record?->sobrecarga_minima_laje) }}</span>
            </div>
            <div class="field"><strong>Descrição da sobrecarga da laje:</strong>
                <span>{{ $record?->descricao_sobrecarga_minima_laje ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Sobrecarga mínima da laje (500kg/m²):</div>
                {!! pdf_render_images($record?->foto_sobrecarga_minima_laje) !!}
            </div>


            <div class="field"><strong>Sobrecarga mínima de laje de teto (35kg/m²):</strong>
                <span>{{ pdf_tri($record?->sobrecarga_minima_laje_teto) }}</span>
            </div>
            <div class="field"><strong>Descrição da sobrecarga no teto:</strong>
                <span>{{ $record?->descricao_sobrecarga_minima_laje_teto ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Sobrecarga mínima de laje de teto (35kg/m²):</div>
                {!! pdf_render_images($record?->foto_sobrecarga_minima_laje_teto) !!}
            </div>



            <!-- Luminárias -->
            <div class="field"><strong>Luminárias:</strong>
                <span>{{ pdf_tri($record?->luminarias) }}</span>
            </div>
            <div class="field"><strong>Descrição Luminárias:</strong>
                <span>{{ $record?->descricao_luminarias ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Luminárias:</div>
                {!! pdf_render_images($record?->foto_luminarias) !!}
            </div>

            <!-- Ar Condicionado -->
            <div class="field"><strong>Ar Condicionado:</strong>
                <span>{{ pdf_tri($record?->ar_condicionado) }}</span>
            </div>
            <div class="field"><strong>Descrição Ar Condicionado:</strong>
                <span>{{ $record?->descricao_ar_condicionado ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Ar Condicionado:</div>
                {!! pdf_render_images($record?->foto_ar_condicionado) !!}
            </div>

            <!-- Piso / Revestimento -->
            <div class="field"><strong>Piso / Revestimento:</strong>
                <span>{{ pdf_tri($record?->piso_revestimento) }}</span>
            </div>
            <div class="field"><strong>Descrição Piso / Revestimento:</strong>
                <span>{{ $record?->descricao_piso_revestimento ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Piso / Revestimento:</div>
                {!! pdf_render_images($record?->foto_piso_revestimento) !!}
            </div>

            <!-- Hidráulica -->
            <div class="field"><strong>Hidráulica:</strong>
                <span>{{ pdf_tri($record?->hidraulica) }}</span>
            </div>
            <div class="field"><strong>Descrição Hidráulica:</strong>
                <span>{{ $record?->descricao_hidraulica ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Fotos Hidráulica:</div>
                {!! pdf_render_images($record?->foto_hidraulica) !!}
            </div>

            <!-- Local Tomada Ar Externo / Exaustão -->
            <div class="field"><strong>Existe local para tomada de ar externo/ exaustão:</strong>
                <span>{{ pdf_tri($record?->local_tomada_ar_externo_exaustao) }}</span>
            </div>
            <div class="field"><strong>Descrição Local Tomada Ar Externo / Exaustão:</strong>
                <span>{{ $record?->descricao_local_tomada_ar_externo_exaustao ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Local Tomada Ar Externo / Exaustão:</div>
                {!! pdf_render_images($record?->foto_local_tomada_ar_externo_exaustao) !!}
            </div>

            <!-- Alvenaria Periferia Existente -->
            <div class="field"><strong>Alvenaria de Periferia Existente:</strong>
                <span>{{ pdf_tri($record?->alvenaria_periferia_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Alvenaria Periferia Existente:</strong>
                <span>{{ $record?->descricao_alvenaria_periferia_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Alvenaria Periferia Existente:</div>
                {!! pdf_render_images($record?->foto_alvenaria_periferia_existente) !!}
            </div>

            <!-- Reboco Interno/Externo Existente -->
            <div class="field"><strong>Reboco interno e externo existente:</strong>
                <span>{{ pdf_tri($record?->reboco_interno_externo_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Reboco Interno/Externo Existente:</strong>
                <span>{{ $record?->descricao_reboco_interno_externo_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Reboco Interno/Externo Existente:</div>
                {!! pdf_render_images($record?->foto_reboco_interno_externo_existente) !!}
            </div>

            <!-- Estanqueidade -->
            <div class="field"><strong>Estanqueidade:</strong>
                <span>{{ pdf_tri($record?->estanqueidade) }}</span>
            </div>
            <div class="field"><strong>Estanqueidade Outro:</strong>
                <span>{{ $record?->estanqueidade_outro ?? 'Não se aplica' }}</span>
            </div>
            <div class="field"><strong>Descrição Estanqueidade:</strong>
                <span>{{ $record?->descricao_estanqueidade ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Estanqueidade:</div>
                {!! pdf_render_images($record?->foto_estanqueidade) !!}
            </div>

            <!-- Área Técnica Externa Existente -->
            <div class="field"><strong>Área Técnica Externa Existente:</strong>
                <span>{{ pdf_tri($record?->area_tecnica_externa_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Área Técnica Externa Existente:</strong>
                <span>{{ $record?->descricao_area_tecnica_externa_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Área Técnica Externa Existente:</div>
                {!! pdf_render_images($record?->foto_area_tecnica_externa_existente) !!}
            </div>

            <!-- Sugestão Área Técnica Interna -->
            <div class="field"><strong>Sugestão Área Técnica Interna:</strong>
                <span>{{ pdf_tri($record?->sugestao_area_tecnica_interna) }}</span>
            </div>
            <div class="field"><strong>Descrição Sugestão Área Técnica Interna:</strong>
                <span>{{ $record?->descricao_sugestao_area_tecnica_interna ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Sugestão Área Técnica Interna:</div>
                {!! pdf_render_images($record?->foto_sugestao_area_tecnica_interna) !!}
            </div>

            <!-- Prever Acústica Condensadores -->
            <div class="field"><strong>Prever Acústica Condensadores:</strong>
                <span>{{ pdf_tri($record?->prever_acustica_condensadores) }}</span>
            </div>
            <div class="field"><strong>Descrição Prever Acústica Condensadores:</strong>
                <span>{{ $record?->descricao_prever_acustica_condensadores ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Prever Acústica Condensadores:</div>
                {!! pdf_render_images($record?->foto_prever_acustica_condensadores) !!}
            </div>

            <!-- Prever Proteção Condensadores -->
            <div class="field"><strong>Prever Proteção Condensadores:</strong>
                <span>{{ pdf_tri($record?->prever_protecao_condensadores) }}</span>
            </div>
            <div class="field"><strong>Descrição Prever Proteção Condensadores:</strong>
                <span>{{ $record?->descricao_prever_protecao_condensadores ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Prever Proteção Condensadores:</div>
                {!! pdf_render_images($record?->foto_prever_protecao_condensadores) !!}
            </div>

            <!-- Reservatório Água Existente -->
            <div class="field"><strong>Reservatório Água Existente:</strong>
                <span>{{ pdf_tri($record?->reservatorio_agua_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Reservatório Água Existente:</strong>
                <span>{{ $record?->descricao_reservatorio_agua_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Reservatório Água Existente:</div>
                {!! pdf_render_images($record?->foto_reservatorio_agua_existente) !!}
            </div>

            <!-- Reservatório Incêndio Existente -->
            <div class="field"><strong>Reservatório Incêndio Existente:</strong>
                <span>{{ pdf_tri($record?->reservatorio_incendio_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Reservatório Incêndio Existente:</strong>
                <span>{{ $record?->descricao_reservatorio_incendio_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Reservatório Incêndio Existente:</div>
                {!! pdf_render_images($record?->foto_reservatorio_incendio_existente) !!}
            </div>

            <!-- Ponto Esgoto Existente Shell -->
            <div class="field"><strong>Ponto Esgoto Existente Shell:</strong>
                <span>{{ pdf_tri($record?->ponto_esgoto_existente_shell) }}</span>
            </div>
            <div class="field"><strong>Descrição Ponto Esgoto Existente Shell:</strong>
                <span>{{ $record?->descricao_ponto_esgoto_existente_shell ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Ponto Esgoto Existente Shell:</div>
                {!! pdf_render_images($record?->foto_ponto_esgoto_existente_shell) !!}
            </div>

            <!-- Rede de Gás Disponível -->
            <div class="field"><strong>Rede de Gás Disponível:</strong>
                <span>{{ pdf_tri($record?->rede_gas_disponivel) }}</span>
            </div>
            <div class="field"><strong>Descrição Rede de Gás Disponível:</strong>
                <span>{{ $record?->descricao_rede_gas_disponivel ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Rede de Gás Disponível:</div>
                {!! pdf_render_images($record?->foto_rede_gas_disponivel) !!}
            </div>

            <!-- Medidor de Água Instalado/Ligado -->
            <div class="field"><strong>Medidor de Água Instalado e Ligado:</strong>
                <span>{{ pdf_tri($record?->medidor_agua_instalado_ligado) }}</span>
            </div>
            <div class="field"><strong>Descrição Medidor de Água Instalado/Ligado:</strong>
                <span>{{ $record?->descricao_medidor_agua_instalado_ligado ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Medidor de Água Instalado/Ligado:</div>
                {!! pdf_render_images($record?->foto_medidor_agua_instalado_ligado) !!}
            </div>

            <!-- Sistema de Incêndio Existente -->
            <div class="field"><strong>Sistema de Incêndio Existente:</strong>
                <span>{{ pdf_tri($record?->sistema_incendio_existente) }}</span>
            </div>
            <div class="field"><strong>Descrição Sistema de Incêndio Existente:</strong>
                <span>{{ $record?->descricao_sistema_incendio_existente ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Sistema de Incêndio Existente:</div>
                {!! pdf_render_images($record?->foto_sistema_incendio_existente) !!}
            </div>

            <!-- PD Acima Livre -->
            <div class="field"><strong>PD acima de 3,5 m livres:</strong>
                <span>{{ pdf_tri($record?->pd_acima_livre) }}</span>
            </div>
            <div class="field"><strong>Descrição PD Acima Livre:</strong>
                <span>{{ $record?->descricao_pd_acima_livre ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto PD Acima Livre:</div>
                {!! pdf_render_images($record?->foto_pd_acima_livre) !!}
            </div>

            <!-- Necessário Elevador/Plataforma -->
            <div class="field"><strong>Em caso de necessidade o elevador ou plataforma é existente:</strong>
                <span>{{ pdf_tri($record?->necessario_elevador_plataforma) }}</span>
            </div>
            <div class="field"><strong>Descrição Necessário Elevador/Plataforma:</strong>
                <span>{{ $record?->descricao_necessario_elevador_plataforma ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Necessário Elevador/Plataforma:</div>
                {!! pdf_render_images($record?->foto_necessario_elevador_plataforma) !!}
            </div>

            <!-- Piso Acabamento Polido -->
            <div class="field"><strong>Piso Acabamento Polido:</strong>
                <span>{{ pdf_tri($record?->piso_acabamento_polido) }}</span>
            </div>
            <div class="field"><strong>Descrição Piso Acabamento Polido:</strong>
                <span>{{ $record?->descricao_piso_acabamento_polido ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Piso Acabamento Polido:</div>
                {!! pdf_render_images($record?->foto_piso_acabamento_polido) !!}
            </div>

            <!-- Necessário Película Fachada -->
            <div class="field"><strong>Película na fachada existente:</strong>
                <span>{{ pdf_tri($record?->necessario_pelicula_fachada) }}</span>
            </div>
            <div class="field"><strong>Descrição Necessário Película Fachada:</strong>
                <span>{{ $record?->descricao_necessario_pelicula_fachada ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Necessário Película Fachada:</div>
                {!! pdf_render_images($record?->foto_necessario_pelicula_fachada) !!}
            </div>

            <!-- Prever Marquise -->
            <div class="field"><strong>Marquise existente:</strong>
                <span>{{ pdf_tri($record?->prever_marquise) }}</span>
            </div>
            <div class="field"><strong>Descrição Prever Marquise:</strong>
                <span>{{ $record?->descricao_prever_marquise ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Prever Marquise:</div>
                {!! pdf_render_images($record?->foto_prever_marquise) !!}
            </div>

            <!-- Prever Porta Enrolar -->
            <div class="field"><strong>Porta de enrolar existente:</strong>
                <span>{{ pdf_tri($record?->prever_porta_enrolar) }}</span>
            </div>
            <div class="field"><strong>Descrição Prever Porta Enrolar:</strong>
                <span>{{ $record?->descricao_prever_porta_enrolar ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Prever Porta Enrolar:</div>
                {!! pdf_render_images($record?->foto_prever_porta_enrolar) !!}
            </div>

            <!-- Caixilhos/Vidros Existentes -->
            <div class="field"><strong>Caixilhos/Vidros Existentes:</strong>
                <span>{{ pdf_tri($record?->caixilhos_vidros_existentes) }}</span>
            </div>
            <div class="field"><strong>Descrição Caixilhos/Vidros Existentes:</strong>
                <span>{{ $record?->descricao_caixilhos_vidros_existentes ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Caixilhos/Vidros Existentes:</div>
                {!! pdf_render_images($record?->foto_caixilhos_vidros_existentes) !!}
            </div>

            <!-- Prever Impermeabilização -->
            <div class="field"><strong>Impermeabilização externa executada:</strong>
                <span>{{ pdf_tri($record?->prever_impermeabilizacao) }}</span>
            </div>
            <div class="field"><strong>Descrição Prever Impermeabilização:</strong>
                <span>{{ $record?->descricao_prever_impermeabilizacao ?? 'Não se aplica' }}</span>
            </div>

            <div class="images">
                <div class="image-title">Foto Prever Impermeabilização:</div>
                {!! pdf_render_images($record?->foto_prever_impermeabilizacao) !!}
            </div>

            <!-- Observações Gerais -->
            <div class="section">
                <h3>Observações Gerais</h3>
                <div class="section-content">
                    <div class="field">
                        <span>{{ $record?->observacoes_gerais ?? 'Sem observações' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>
