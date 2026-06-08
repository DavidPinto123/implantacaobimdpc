<?php

namespace App\Http\Controllers;

use App\Models\HistoricoProjeto;

class ProjetoController extends Controller
{
    public function historico($projetoId)
    {
        $historico = HistoricoProjeto::with('usuario')
            ->where('projeto_id', $projetoId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('fase');

        return view('projetos.historico', compact('historico'));
    }
}
