<?php

namespace Database\Seeders;

use App\Models\Departamentos;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Caminho do arquivo Excel
        $path = public_path('dados_exportados.xlsx');
        // Carregar os dados do arquivo Excel
        $data = Excel::toArray([], $path)[2]; // Usando a primeira aba

        // Inserir os dados na tabela de produtos
        foreach ($data as $row) {
            $dataRaw = $row[4];

            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw)) {
                $dataFormatada = Date::excelToDateTimeObject($dataRaw)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                // $dataFormatada = date('Y-m-d', strtotime($dataRaw));
                $dateTime = \DateTime::createFromFormat('d/m/Y', trim($dataRaw));
                $dataFormatada = $dateTime ? $dateTime->format('Y-m-d') : null;
            }
            // dd($dataFormatada);
            Departamentos::create([
                'nova_sigla' => $row[0],
                'unidade' => $row[1],
                'departamento' => $row[2],
                'area' => $row[3],
                'data_extracao' => $dataFormatada,
            ]);
        }
    }
}
