<table>
    <thead>
        <tr>
            <th>Sigla</th>
            <th>Unidade</th>
            <th>Marca</th>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Quantidade</th>
            <th>Pavimento</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @dd($dados) <!-- Depuração -->
        @foreach($dados as $dado)
            <tr>
                <td>{{ $dado->nova_sigla }}</td>
                <td>{{ $dado->unidade }}</td>
                <td>{{ $dado->marca }}</td>
                <td>{{ $dado->categoria }}</td>
                <td>{{ $dado->descricao }}</td>
                <td>{{ $dado->quantidade }}</td>
                <td>{{ $dado->pavimento }}</td>
                <td>{{ $dado->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>