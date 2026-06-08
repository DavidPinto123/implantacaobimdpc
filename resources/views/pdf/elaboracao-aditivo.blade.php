<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Planilha do Aditivo</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 20px;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .subtitulo {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-table td {
            border: none;
            padding: 4px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .info-table .label {
            width: 160px;
            font-weight: bold;
            white-space: nowrap;
        }

        .info-table .value {
            padding-left: -10px;
        }

        .itens-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .itens-table th,
        .itens-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }

        .itens-table th {
            background: #f4b400;
            text-align: center;
            font-weight: bold;
        }

        .col-item {
            width: 10%;
            text-align: center;
        }

        .col-desc {
            width: 40%;
            word-wrap: break-word;
            word-break: break-word;
        }

        .col-qt {
            width: 8%;
            text-align: right;
        }

        .col-und {
            width: 6%;
            text-align: center;
        }

        .col-val {
            width: 12%;
            text-align: right;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            background: #f4b400;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    @php
        $totalGeral = $record->itens->sum('valor_total_geral');
    @endphp

    <div class="titulo">PLANILHA PARA ELABORAÇÃO DE ADITIVOS DE OBRA</div>
    <div class="subtitulo">EXPANSÃO / ORÇAMENTOS</div>

    <table class="info-table">
        <tr>
            <td class="label">OBRA:</td>
            <td class="value">{{ $record->obra?->unidade ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">GESTOR:</td>
            <td class="value">{{ $record->gestor?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">DATA:</td>
            <td class="value">{{ optional($record->data)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">REF. SERVIÇO:</td>
            <td class="value">{{ $record->asEscopo?->escopo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">GERENCIADORA:</td>
            <td class="value">{{ $record->construtora?->nome ?? '-' }}</td>
        </tr>
    </table>

    <table class="itens-table">
        <thead>
            <tr>
                <th class="col-item">ITEM</th>
                <th class="col-desc">DESCRIÇÃO DO SERVIÇO</th>
                <th class="col-qt">QT.</th>
                <th class="col-und">UND.</th>
                <th class="col-val">R$ MAT. (UNIT.)</th>
                <th class="col-val">R$ M.O. (UNIT.)</th>
                <th class="col-val">TOTAL UNIT (MAT + M.O.)</th>
                <th class="col-val">R$ TOTAL GERAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->itens as $item)
                <tr>
                    <td class="text-center">{{ $item->item }}</td>
                    <td>{{ $item->descricao_servico }}</td>
                    <td class="text-right">{{ number_format((float) $item->quantidade, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->unidade }}</td>
                    <td class="text-right">R$ {{ number_format((float) $item->valor_material_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float) $item->valor_mao_obra_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float) $item->total_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float) $item->valor_total_geral, 2, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="7">TOTAL GERAL</td>
                <td class="text-right">R$ {{ number_format((float) $totalGeral, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>