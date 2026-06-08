<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ordem de Investimento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 14mm 14mm 14mm 14mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            color: #222;
            background: #fff;
        }

        /* ── TABELA PRINCIPAL (thead repete em cada página) ── */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* ── TÍTULO PRINCIPAL ── */
        .doc-title {
            background: #58595b;
            color: #fff;
            text-align: center;
            padding: 9px 0 8px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ── BANDA DE LOGO ── */
        .logo-band {
            background: #1a1a1a;
            text-align: center;
            padding: 6px 0;
        }
        .logo-band img {
            height: 36px;
            width: auto;
        }

        /* ── CABEÇALHO DE INFORMAÇÕES ── */
        .info-section {
            width: 100%;
            border-collapse: collapse;
            background-color:#fff;
        }
        .info-section td {
            padding: 2px 8px;
            vertical-align: top;
            font-size: 9px;
            background-color:#fff;
        }
        .info-label {
            font-weight: bold;
            color: #58595b;
            text-transform: uppercase;
            white-space: nowrap;
            width: 130px;
        }
        .info-value {
            font-weight: bold;
            color: #222;
        }
        .info-right-label {
            font-weight: bold;
            color: #58595b;
            text-align: right;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .info-right-value {
            font-weight: bold;
            color: #222;
            text-align: right;
            white-space: nowrap;
            padding-left: 6px;
        }

        /* ── HEADER DA COLUNA DA TABELA ── */
        .col-header th {
            background: #58595b;
            color: #fff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: center;
            letter-spacing: 0.3px;
        }
        .col-header th.col-disc {
            text-align: left;
        }

        /* ── LINHAS DE ITENS ── */
        .items-tbody tr td {
            padding: 4px 8px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: middle;
        }
        .items-tbody tr:nth-child(even) td {
            background: #f5f5f5;
        }
        .items-tbody tr:nth-child(odd) td {
            background: #fff;
        }

        .col-num {
            text-align: center;
            color: #888;
            font-weight: bold;
            font-size: 8.5px;
            width: 32px;
        }
        .col-disc {
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            color: #1f4e79;
        }
        .col-valor {
            text-align: right;
            color: #1e5631;
            font-weight: bold;
            width: 130px;
        }
        .col-custo {
            text-align: right;
            color: #1e5631;
            font-weight: bold;
            width: 140px;
        }
        .col-perc {
            text-align: right;
            color: #1e5631;
            width: 50px;
        }

        /* ── LINHAS DE TOTAL ── */
        .total-row-1 td {
            background: #58595b !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 8px;
            border: none;
        }
        .total-row-2 td {
            background: #3a3a3c !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 10px;
            padding: 6px 8px;
            border: none;
        }
        .total-label {
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total-arrow {
            color: #aaa;
            padding-right: 4px;
        }

        /* ── COMENTÁRIO ── */
        .comment-cell {
            padding-top: 14px !important;
            background: #fff !important;
            border: none !important;
        }
        .comment-box {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px 10px;
            font-size: 9px;
            color: #444;
            line-height: 1.5;
        }
        .comment-title {
            font-weight: bold;
            text-transform: uppercase;
            color: #58595b;
            font-size: 8px;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

@php
    use App\Support\PdfMedia;
    use Carbon\Carbon;

    $fmt = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

    $todosItens = $record->itens->sortBy('ordem');

    $logoPath = PdfMedia::src('images/logo-band.png');
@endphp

<table class="main-table">

    {{-- ── THEAD: repete automaticamente em cada página ── --}}
    <thead>
        {{-- Título --}}
        <tr>
            <td colspan="5" style="padding:0">
                <div class="doc-title">Ordem de Investimento</div>
            </td>
        </tr>

        {{-- Logo --}}
        <tr>
            <td colspan="5" style="padding:0">
                <div class="logo-band">
                    @if($logoPath)
                        <img src="{{ $logoPath }}" alt="Smart Group">
                    @endif
                </div>
            </td>
        </tr>

        {{-- Informações --}}
        <tr>
            <td colspan="5" style="padding:4px 0 6px 0; background:#fff !important;">
                <table class="info-section">
                    <colgroup>
                        <col style="width:18%">
                        <col style="width:42%">
                        <col style="width:20%">
                        <col style="width:20%">
                    </colgroup>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Sigla:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ strtoupper($record->sigla_exibicao ?? $record->sigla ?? '—') }}</td>
                        <td class="info-right-label" bgcolor="#ffffff">Revisão:</td>
                        <td class="info-right-value" bgcolor="#ffffff">{{ $record->revisao_label }}</td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Unidade:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ $record->nome_exibicao ?? $record->nome ?? '—' }}</td>
                        <td class="info-right-label" bgcolor="#ffffff">Data:</td>
                        <td class="info-right-value" bgcolor="#ffffff">{{ Carbon::now()->format('d/m/Y') }}</td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">UF:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ strtoupper($record->uf_exibicao ?? $record->uf ?? '—') }}</td>
                        <td bgcolor="#ffffff"></td><td bgcolor="#ffffff"></td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Endereço:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ $record->endereco_exibicao ?? $record->endereco ?? '—' }}</td>
                        <td bgcolor="#ffffff"></td><td bgcolor="#ffffff"></td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Área da Unidade (m²):</td>
                        <td class="info-value" bgcolor="#ffffff">{{ number_format((float) $record->area_unidade, 2, ',', '.') }}</td>
                        <td bgcolor="#ffffff"></td><td bgcolor="#ffffff"></td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Fator de Correção:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ number_format((float) $record->fator_correcao, 4, ',', '.') }}</td>
                        <td bgcolor="#ffffff"></td><td bgcolor="#ffffff"></td>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td class="info-label" bgcolor="#ffffff">Faixa Identificada:</td>
                        <td class="info-value" bgcolor="#ffffff">{{ $record->faixa_nome ?? '—' }}</td>
                        <td bgcolor="#ffffff"></td><td bgcolor="#ffffff"></td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Cabeçalho das colunas --}}
        <tr class="col-header">
            <th style="width:32px">#</th>
            <th class="col-disc">Disciplina</th>
            <th style="width:130px;text-align:right">Valor Base<br>(R$/m² ou R$)</th>
            <th style="width:140px;text-align:right">Custo Estimado (R$)</th>
            <th style="width:50px;text-align:right">%</th>
        </tr>
    </thead>

    {{-- ── TBODY: itens ── --}}
    <tbody class="items-tbody">
        @forelse($todosItens as $item)
        <tr>
            <td class="col-num">{{ $loop->iteration }}</td>
            <td class="col-disc">{{ strtoupper($item->nome_escopo) }}</td>
            <td class="col-valor">{{ $fmt($item->valor_base_m2) }}</td>
            <td class="col-custo">{{ $fmt($item->incluir ? $item->custo_estimado : 0) }}</td>
            <td class="col-perc">{{ $item->incluir ? number_format((float) $item->percentual, 1, ',', '.') : '0,0' }}%</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center;color:#aaa;padding:12px;">Nenhum item cadastrado.</td>
        </tr>
        @endforelse
    </tbody>

    {{-- ── TFOOT: totais ── --}}
    <tfoot>
        <tr class="total-row-1">
            <td colspan="3" class="total-label">
                CAPEX Total Estimado
            </td>
            <td style="text-align:right;color:#fff">{{ $fmt($record->custo_total_estimado) }}</td>
            <td style="text-align:right;color:#fff">100%</td>
        </tr>
        <tr class="total-row-2">
            <td colspan="3" class="total-label">
                Custo Total por m²
            </td>
            <td style="text-align:right;color:#fff">{{ $fmt($record->custo_por_m2) }}</td>
            <td></td>
        </tr>

        
    </tfoot>

</table>

</body>
</html>
