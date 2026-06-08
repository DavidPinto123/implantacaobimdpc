@push('styles')
    <style>
        /* Container para tabela com rolagem */
        .table-wrapper {
            overflow-y: auto;
            max-height: 500px;  /* Ajuste conforme necessário */
            margin-top: 20px;
        }

        /* Cabeçalho fixo */
        .filament-tables-table thead {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }

        /* Tabela */
        .filament-tables-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Tabelas com padding e borda */
        .filament-tables-table th, .filament-tables-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        /* Adicionando uma sombra leve no cabeçalho */
        .filament-tables-table thead th {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Corpo da tabela */
        .filament-tables-table tbody {
            display: block;
            overflow-y: auto;
            max-height: 400px; /* Ajuste conforme necessário */
        }

        .filament-tables-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
    </style>
@endpush

<div class="table-wrapper">
    {!! $table->render() !!}
</div>
