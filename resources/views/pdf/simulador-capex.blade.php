<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 140px;
        }

        .titulo {
            text-align: right;
        }

        .titulo h2 {
            margin: 0;
            font-size: 18px;
        }

        .titulo p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #e5e5e5;
            font-weight: bold;
        }

        .grupo {
            background: #d9d9d9;
            font-weight: bold;
        }

        .total {
            font-weight: bold;
            font-size: 14px;
            text-align: right;
            margin-top: 15px;
        }
    </style>
</head>

<body>

    {{-- ================= HEADER ================= --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <img src="{{ public_path('images/logo-smart.png') }}" class="logo">
                </td>
                <td class="titulo">
                    <h2>OI - Simulador de CAPEX</h2>
                    <p>Projeto: {{ $projeto }}</p>
                    <p>Data: {{ now()->format('d/m/Y') }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- ================= TABELA ================= --}}
    <table>
        <thead>
            <tr>
                <th style="background-color:#f2b705; color:#000;">Disciplina</th>
                <th style="background-color:#f2b705; color:#000;">R$ (Padrão)</th>
                <th style="background-color:#f2b705; color:#000;">R$ (AD.)</th>
                <th style="background-color:#f2b705; color:#000;">R$ (Total)</th>
                <th style="background-color:#f2b705; color:#000;">%</th>
                <th style="background-color:#f2b705; color:#000;">R$/m²</th>
            </tr>
        </thead>
        <tbody>

            @php $grupoAtual = null; @endphp

            @foreach($linhas as $linha)

            @if($grupoAtual !== $linha['grupo'])
            @php
            $grupoAtual = $linha['grupo'];
            $totalGrupo = collect($linhas)
            ->where('grupo', $grupoAtual)
            ->sum(fn($l) => $l['padrao'] + $l['ad']);
            @endphp

            <tr class="grupo">
                <td>{{ $grupoAtual }}</td>
                <td></td>
                <td></td>
                <td>R$ {{ number_format($totalGrupo, 2, ',', '.') }}</td>
                <td></td>
                <td></td>
            </tr>
            @endif

            @php
            $totalLinha = $linha['padrao'] + $linha['ad'];
            @endphp

            <tr>
                <td>{{ $linha['nome'] }}</td>
                <td>R$ {{ number_format($linha['padrao'], 2, ',', '.') }}</td>
                <td>R$ {{ number_format($linha['ad'], 2, ',', '.') }}</td>
                <td>R$ {{ number_format($totalLinha, 2, ',', '.') }}</td>
                <td>{{ number_format(($totalLinha / max($totalGeral,1))*100, 2) }}%</td>
                <td>R$ {{ number_format($totalLinha / max($area,1), 2, ',', '.') }}</td>
            </tr>

            @endforeach

        </tbody>
    </table>

    <div class="total">
        Total Geral: R$ {{ number_format($totalGeral, 2, ',', '.') }}
    </div>

    <br><br><br>
    
    <table width="100%" style="margin-top: 40px;">
        <tr>
            <td colspan="2" style=" background-color: #f2b705; color: #000; text-align: center; font-weight: bold; padding: 6px; font-size: 13px; "> ASSINATURAS </td>
        </tr>
        <tr>
            <td style="padding-top: 60px; text-align: center;">
                <div style=" width: 70%; margin: 0 auto; border-top: 1px solid #000; "></div>
                <div style="margin-top: 5px; font-size: 11px;"> ORÇAMENTOS </div>
            </td>
            <td style="padding-top: 60px; text-align: center;">
                <div style=" width: 70%; margin: 0 auto; border-top: 1px solid #000; "></div>
                <div style="margin-top: 5px; font-size: 11px;"> DIRETORIA </div>
            </td>
        </tr>
    </table>

</body>

</html>