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

    /* Info grid (cabeçalho do orçamento) */
    .info-grid { display: table; width: 100%; margin-bottom: 16px; border: 1px solid #ddd; border-radius: 4px; }
    .info-row { display: table-row; }
    .info-label-cell { display: table-cell; padding: 6px 10px; font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #888; border-bottom: 1px solid #eee; width: 30%; }
    .info-value-cell { display: table-cell; padding: 6px 10px; font-size: 9.5pt; border-bottom: 1px solid #eee; }

    /* Tabelas */
    .tabela { display: table; width: 100%; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
    .tabela-linha { display: table-row; }
    .tabela-cabecalho .tabela-celula { background: #1e3a5f; color: #fff; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
    .tabela-celula { display: table-cell; padding: 6px 8px; font-size: 9pt; border-bottom: 1px solid #eee; vertical-align: top; }
    .tabela-total .tabela-celula { background: #f3f4f6; font-weight: bold; border-bottom: none; }
    .col-num { text-align: right; }
    .col-centro { text-align: center; }

    /* Seção de categoria */
    .categoria-titulo { font-size: 12pt; font-weight: bold; color: #fff; background: #f5a623; padding: 6px 10px; margin-bottom: 8px; }
    .notas { font-size: 7.5pt; color: #888; font-style: italic; margin-top: 6px; }
    .quebra-pagina { page-break-before: always; }

    /* Resumo */
    .secao-titulo { font-size: 14pt; font-weight: bold; color: #fff; background: #1a1a1a; text-align: center; padding: 8px; margin-bottom: 12px; }
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
            <div class="header-titulo">Planilha de Orçamento</div>
            <div class="header-sub">Sistema de Gestão de Implantações BIM — DPC Consultoria</div>
        </div>
        <div style="text-align:right; font-size:8.5pt; color:#555;">
            Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
        </div>
    </div>

    {{-- Informações do orçamento --}}
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label-cell">Empresa</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->marca ?: 'Smart Fit' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Projeto</div>
            <div class="info-value-cell">{{ $orcamento->nome }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Endereço</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->endereco ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Sigla</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->sigla ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Nova Sigla</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->nova_sigla ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Marca</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->marca ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Escopo</div>
            <div class="info-value-cell">{{ $orcamento->projeto?->escopo ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Nome MKT</div>
            <div class="info-value-cell">{{ $orcamento->nome_mkt ?: '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label-cell">Data</div>
            <div class="info-value-cell">{{ $orcamento->data->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- Resumo do Orçamento --}}
    <div class="secao-titulo">Resumo do Orçamento</div>
    <div class="tabela">
        <div class="tabela-linha tabela-cabecalho">
            <div class="tabela-celula" style="width:40%;">Categoria</div>
            <div class="tabela-celula col-num" style="width:20%;">Total Mat</div>
            <div class="tabela-celula col-num" style="width:20%;">Total MO</div>
            <div class="tabela-celula col-num" style="width:20%;">Total Geral</div>
        </div>
        @foreach($orcamento->categorias as $categoria)
        <div class="tabela-linha">
            <div class="tabela-celula">{{ $categoria->nome }}</div>
            <div class="tabela-celula col-num">{{ number_format($categoria->total_material, 2, ',', '.') }}</div>
            <div class="tabela-celula col-num">{{ number_format($categoria->total_mao_de_obra, 2, ',', '.') }}</div>
            <div class="tabela-celula col-num">{{ number_format($categoria->total_geral, 2, ',', '.') }}</div>
        </div>
        @endforeach
        <div class="tabela-linha tabela-total">
            <div class="tabela-celula">TOTAL GERAL</div>
            <div class="tabela-celula col-num">{{ number_format($orcamento->total_material, 2, ',', '.') }}</div>
            <div class="tabela-celula col-num">{{ number_format($orcamento->total_mao_de_obra, 2, ',', '.') }}</div>
            <div class="tabela-celula col-num">{{ number_format($orcamento->total_geral, 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- Uma página por categoria --}}
    @foreach($orcamento->categorias as $categoria)
    <div class="quebra-pagina">
        <div style="font-size:9pt; color:#888; margin-bottom:2px;">{{ $orcamento->projeto?->sigla }} - {{ $orcamento->nome_mkt }}</div>
        <div class="categoria-titulo">{{ $categoria->nome }}</div>

        <div class="tabela">
            <div class="tabela-linha tabela-cabecalho">
                <div class="tabela-celula" style="width:10%;">Código</div>
                <div class="tabela-celula" style="width:34%;">Descrição</div>
                <div class="tabela-celula col-centro" style="width:8%;">Unid</div>
                <div class="tabela-celula col-num" style="width:10%;">Qtd</div>
                <div class="tabela-celula col-num" style="width:12%;">Mat</div>
                <div class="tabela-celula col-num" style="width:12%;">MO</div>
                <div class="tabela-celula col-num" style="width:14%;">Total</div>
            </div>
            @foreach($categoria->itens as $item)
            <div class="tabela-linha">
                <div class="tabela-celula">{{ $item->codigo ?: '—' }}</div>
                <div class="tabela-celula">{{ $item->descricao }}</div>
                <div class="tabela-celula col-centro">{{ $item->unidade }}</div>
                <div class="tabela-celula col-num">{{ number_format($item->quantidade, 2, ',', '.') }}</div>
                <div class="tabela-celula col-num">{{ number_format($item->valor_mat, 2, ',', '.') }}</div>
                <div class="tabela-celula col-num">{{ number_format($item->valor_mo, 2, ',', '.') }}</div>
                <div class="tabela-celula col-num">{{ number_format($item->valor_total, 2, ',', '.') }}</div>
            </div>
            @endforeach
            <div class="tabela-linha tabela-total">
                <div class="tabela-celula" style="width:74%;">TOTAL DA CATEGORIA</div>
                <div class="tabela-celula col-num" style="width:26%;">{{ number_format($categoria->total_geral, 2, ',', '.') }}</div>
            </div>
        </div>
        <p class="notas">Notas: Os valores acima são uma estimativa de materiais para orçamento. A lista pode variar de acordo com a execução.</p>
    </div>
    @endforeach

</div>
</body>
</html>
