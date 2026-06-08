<?php

namespace Database\Seeders;

use App\Models\Dados;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class DadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Caminho do arquivo Excel
        $path = public_path('dados_exportados.xlsx');
        // Carregar os dados do arquivo Excel
        $data = Excel::toArray([], $path)[0]; // Usando a primeira aba

        // Inserir os dados na tabela de produtos
        foreach ($data as $row) {
            Dados::create([
                'nova_sigla' => $row[0],
                'unidade' => $row[1],
                'marca' => $row[2],
                'bloco_tipo' => $row[3],
                'categoria' => $row[4],
                'descricao' => $row[5],
                'quantidade' => $row[6],
                'pavimento' => $row[7],
                'status' => $row[8],
            ]);
        }
    }
}
