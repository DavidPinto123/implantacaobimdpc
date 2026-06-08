<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Tarefas</title>

    <style>
        @page {
            margin: 18px 18px 18px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7px;
            color: #111827;
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

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta td {
            border: 1px solid #d1d5db;
            padding: 4px;
            font-size: 7px;
        }

        .cards {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .cards td {
            border: 1px solid #d1d5db;
            padding: 4px;
            text-align: center;
        }

        .card-label {
            font-size: 6px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .card-value {
            font-size: 10px;
            font-weight: bold;
        }

        .section-title {
            margin: 8px 0 4px 0;
            font-size: 9px;
            font-weight: bold;
        }

        table.relatorio {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        table.relatorio th,
        table.relatorio td {
            border: 1px solid #9ca3af;
            padding: 2px 3px;
            vertical-align: top;
            line-height: 1.1;
            word-break: break-word;
        }

        table.relatorio th {
            background: #e5e7eb;
            font-size: 6px;
            text-transform: uppercase;
            text-align: left;
        }

        table.relatorio td {
            font-size: 6px;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Relatório de Tarefas</h1>
    <div class="subtitulo">
        Resumo executivo e listagem detalhada das tarefas filtradas
    </div>

    <table class="meta">
        <tr>
            <td>
                <strong>Período:</strong>
                {{ $dataInicial ? \Carbon\Carbon::parse($dataInicial)->format('d/m/Y') : '-' }}
                até
                {{ $dataFinal ? \Carbon\Carbon::parse($dataFinal)->format('d/m/Y') : '-' }}
            </td>
            <td>
                <strong>Responsável:</strong>
                {{ $responsavel ?? 'Todos' }}
            </td>
            <td>
                <strong>Total de tarefas:</strong>
                {{ $total }}
            </td>
            <td>
                <strong>Emitido em:</strong>
                {{ $emitidoEm->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            @foreach ($cards as $card)
                <td>
                    <div class="card-label">{{ $card['label'] }}</div>
                    <div class="card-value">{{ $card['value'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>



@if(!empty($charts))
    <h3>Gráficos</h3>

    @foreach($charts as $chart)
        <div style="margin-bottom:20px;">
            <img src="{{ $chart }}" style="width:100%;">
        </div>
    @endforeach
@endif

    <div class="section-title">Listagem detalhada</div>

    <table class="relatorio">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Setor</th>
                <th>Tarefa</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Sigla</th>
                <th>Unidade</th>
                <th>Solicitante</th>
                <th>Responsável</th>
                <th>Data de Início</th>
                <th>Prazo</th>
                <th>Término Programado</th>
                <th>Data de Entrega</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tarefas as $tarefa)
                <tr>
                    <td class="center nowrap">{{ $tarefa->id }}</td>

                    <td>
                        @switch($tarefa->status)
                            @case('pendente') Pendente @break
                            @case('em_andamento') Em andamento @break
                            @case('concluida') Concluída @break
                            @case('cancelada') Cancelada @break
                            @default {{ $tarefa->status }}
                        @endswitch
                    </td>

                    <td>{{ \Illuminate\Support\Str::limit($tarefa->setor->setor ?? '-', 15) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->title ?? '-', 25) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->description ?? '-', 45) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->category->name ?? '-', 16) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->sigla ?? '-', 8) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->marca->nome ?? '-', 14) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->solicitante->name ?? '-', 16) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tarefa->responsavel->name ?? '-', 16) }}</td>
                    <td class="center nowrap">{{ optional($tarefa->inicio)?->format('d/m/Y') ?? '-' }}</td>
                    <td class="center nowrap">{{ $tarefa->prazo ?? '-' }}</td>
                    <td class="center nowrap">{{ optional($tarefa->termino_programado)?->format('d/m/Y') ?? '-' }}</td>
                    <td class="center nowrap">{{ optional($tarefa->data_entrega)?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="center muted">
                        Nenhuma tarefa encontrada para os filtros selecionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>