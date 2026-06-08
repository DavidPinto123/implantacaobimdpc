<?php

namespace App\Http\Controllers;

use App\Models\Dados;

class DadosController extends Controller
{
    public function index()
    {
        // Recuperando os dados da tabela 'dados'
        $dados = Dados::all();

        // Verificando se a variável $dados contém registros
        if ($dados->isEmpty()) {
            dd('Nenhum dado encontrado');
        }

        // Passando os dados para a view
        return view('dados.index', compact('dados'));
    }
}
