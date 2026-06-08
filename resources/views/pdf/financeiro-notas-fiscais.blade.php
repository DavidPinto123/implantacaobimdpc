<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Notas Fiscais</title>
    <style>
        @page {
            margin: 18px 18px 28px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #111827;
            margin: 0;
        }

        h1 {
            margin: 0 0 4px 0;
            font-size: 14px;
        }

        .subtitulo {
            margin: 0 0 10px 0;
            font-size: 8px;
            color: #4b5563;
        }

        .filtros {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .filtros th,
        .filtros td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }

        .filtros th {
            background: #f3f4f6;
            font-size: 7px;
            color: #4b5563;
            width: 120px;
        }

        .filtros td {
            font-size: 8px;
        }

        table.relatorio {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.relatorio th,
        table.relatorio td {
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.relatorio thead {
            display: table-header-group;
        }

        table.relatorio tfoot {
            display: table-footer-group;
        }

        table.relatorio thead th {
            background: #f3f4f6;
            font-size: 7px;
            color: #1f2937;
            text-align: center;
        }

        table.relatorio tbody td {
            font-size: 7px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .pill {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            font-size: 6.5px;
            font-weight: bold;
        }

        .pill-success {
            background: #d1fae5;
            color: #065f46;
        }

        .pill-neutral {
            background: #e5e7eb;
            color: #374151;
        }

        tfoot td {
            background: #f9fafb;
            font-weight: bold;
            font-size: 8px;
        }

        .vazio {
            text-align: center;
            font-style: italic;
            color: #6b7280;
            padding: 16px 0;
        }

        .alerta-truncado {
            margin: 0 0 10px 0;
            padding: 6px 8px;
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #78350f;
            font-size: 8px;
            border-radius: 4px;
        }

        .footer {
            position: fixed;
            bottom: 6px;
            left: 18px;
            right: 18px;
            font-size: 6.5px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .page-number:after {
            content: counter(page) " / " counter(pages);
        }
    </style>
</head>
<body>
    <h1>Relatório de Notas Fiscais</h1>
    <p class="subtitulo">Emitido em {{ $emitidoEm->format('d/m/Y H:i') }} · {{ $totalizadores['quantidade'] }} {{ $totalizadores['quantidade'] === 1 ? 'registro' : 'registros' }}</p>

    @if (! empty($truncado))
        <p class="alerta-truncado">
            Atenção: o relatório está limitado aos primeiros {{ number_format($limiteRegistros, 0, ',', '.') }} registros.
            A consulta retornou {{ number_format($totalSemTruncamento, 0, ',', '.') }} notas — refine os filtros para incluir todas.
        </p>
    @endif

    @if (! empty($filtrosResumo))
        <table class="filtros">
            @foreach ($filtrosResumo as $label => $valor)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $valor }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="relatorio">
        <colgroup>
            <col style="width: 7%">
            <col style="width: 14%">
            <col style="width: 9%">
            <col style="width: 7%">
            <col style="width: 8%">
            <col style="width: 6%">
            <col style="width: 6%">
            <col style="width: 7%">
            <col style="width: 9%">
            <col style="width: 6%">
            <col style="width: 11%">
            <col style="width: 10%">
        </colgroup>
        <thead>
            <tr>
                <th>Nº NF</th>
                <th>Razão Social</th>
                <th>CNPJ</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Emissão</th>
                <th>Baixa</th>
                <th>Baixado por</th>
                <th>Baixado em</th>
                <th>Código</th>
                <th>Unidade</th>
                <th>Gestor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linhas as $linha)
                <tr>
                    <td class="text-center">{{ $linha['numero_nf'] }}</td>
                    <td>{{ $linha['empresa'] }}</td>
                    <td class="text-center">{{ $linha['cnpj'] }}</td>
                    <td class="text-center">{{ $linha['tipo'] }}</td>
                    <td class="text-end">{{ $linha['valor'] }}</td>
                    <td class="text-center">{{ $linha['emissao'] }}</td>
                    <td class="text-center">
                        <span class="pill {{ $linha['baixa'] === 'Baixado' ? 'pill-success' : 'pill-neutral' }}">
                            {{ $linha['baixa'] }}
                        </span>
                    </td>
                    <td>{{ $linha['baixado_por'] }}</td>
                    <td class="text-center">{{ $linha['baixado_em'] }}</td>
                    <td class="text-center">{{ $linha['codigo'] }}</td>
                    <td>{{ $linha['unidade'] }}</td>
                    <td>{{ $linha['gestor'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="vazio">Nenhuma nota fiscal encontrada para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
        @if (! empty($linhas))
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Totais</td>
                    <td class="text-end">{{ $totalizadores['valor_total_formatado'] }}</td>
                    <td colspan="7" class="text-end">{{ $totalizadores['quantidade'] }} {{ $totalizadores['quantidade'] === 1 ? 'nota' : 'notas' }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <span>Gestão Smart · Relatório de Notas Fiscais</span>
        <span class="page-number"></span>
    </div>
</body>
</html>
