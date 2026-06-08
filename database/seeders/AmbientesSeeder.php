<?php

namespace Database\Seeders;

use App\Models\Ambientes;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AmbientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Caminho do arquivo Excel
        $path = public_path('dados_exportados.xlsx');
        // Carregar os dados do arquivo Excel
        $data = Excel::toArray([], $path)[1]; // Usando a primeira aba

        // Inserir os dados na tabela de produtos
        foreach ($data as $row) {
            $dataRaw = $row[7];

            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw)) {
                $dataFormatada = Date::excelToDateTimeObject($dataRaw)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                // $dataFormatada = date('Y-m-d', strtotime($dataRaw));
                $dateTime = \DateTime::createFromFormat('d/m/Y', trim($dataRaw));
                $dataFormatada = $dateTime ? $dateTime->format('Y-m-d') : null;
            }
            Ambientes::create([
                'nova_sigla' => $row[0],
                'unidade' => $row[1],
                'marca' => $row[2],
                'departamento' => $row[3],
                'ambiente' => $row[4],
                'area' => $row[5],
                'pavimento' => $row[6],
                'data_extracao' => $dataFormatada,
            ]);
        }
    }
}
