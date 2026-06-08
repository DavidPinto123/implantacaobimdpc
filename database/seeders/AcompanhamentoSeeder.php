<?php

namespace Database\Seeders;

use App\Models\Acompanhamento;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AcompanhamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Caminho do arquivo Excel
        $path = public_path('planilha_acompanhamento1.xlsx');
        // Carregar os dados do arquivo Excel
        $data = Excel::toArray([], $path)[0]; // Usando a primeira aba

        // Inserir os dados na tabela de produtos
        foreach ($data as $row) {
            $dataRaw1 = $row[8];
            $dataRaw2 = $row[9];
            $dataRaw3 = $row[10];
            $dataRaw4 = $row[11];

            $dataRaw5 = $row[27];
            $dataRaw6 = $row[28];
            $dataRaw7 = $row[29];
            $dataRaw8 = $row[30];
            $dataRaw9 = $row[57];

            $ano = $row[12];
            if (! is_numeric($ano)) {
                // tenta extrair número da string como fallback
                preg_match('/\d{4}/', $ano, $matches);
                $ano = $matches[0] ?? null;
            }

            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw1)) {
                $dataFormatada1 = Date::excelToDateTimeObject($dataRaw1)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada1 = $this->formatarData($dataRaw1);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw2)) {
                $dataFormatada2 = Date::excelToDateTimeObject($dataRaw2)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada2 = $this->formatarData($dataRaw2);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw3)) {
                $dataFormatada3 = Date::excelToDateTimeObject($dataRaw3)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada3 = $this->formatarData($dataRaw3);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw4)) {
                $dataFormatada4 = Date::excelToDateTimeObject($dataRaw4)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada4 = $this->formatarData($dataRaw4);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw5)) {
                $dataFormatada5 = Date::excelToDateTimeObject($dataRaw5)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada5 = $this->formatarData($dataRaw5);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw6)) {
                $dataFormatada6 = Date::excelToDateTimeObject($dataRaw6)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada6 = $this->formatarData($dataRaw6);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw7)) {
                $dataFormatada7 = Date::excelToDateTimeObject($dataRaw7)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada7 = $this->formatarData($dataRaw7);
            }
            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw8)) {
                $dataFormatada8 = Date::excelToDateTimeObject($dataRaw8)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada8 = $this->formatarData($dataRaw8);
            }

            // Converter valor numérico do Excel em data (YYYY-MM-DD)
            if (is_numeric($dataRaw9)) {
                $dataFormatada9 = Date::excelToDateTimeObject($dataRaw9)->format('Y-m-d');
            } else {
                // Caso já esteja em formato legível
                $dataFormatada9 = $this->formatarData($dataRaw9);
            }

            // Correção dos valores numéricos para as áreas
            $areaContrato = $this->formatarArea($row[31]);
            $areaUtil = $this->formatarArea($row[32]);
            $areaProducao = $this->formatarArea($row[33]);

            Acompanhamento::create([
                'sigla' => $row[0],
                'nova_sigla' => $row[1],
                'nome_mkt' => $row[2],
                'tipo' => $row[3],
                'marca' => $row[4],
                'escopo' => $row[5],
                'pipeline' => $row[6],
                'status' => $row[7],
                'inicio_obra' => $dataFormatada1,
                'entrega_obra' => $dataFormatada2,
                'implantacao' => $dataFormatada3,
                'inauguracao' => $dataFormatada4,
                'ano_inauguracao' => $ano,
                'endereco' => $row[13],
                'cep' => $row[14],
                'bairro' => $row[15],
                'cidade' => $row[16],
                'estado' => $row[17],
                'regiao' => $row[18],
                'pais' => $row[19],
                'razao_social' => $row[20],
                'cnpj' => $row[21],
                'empreendimento_adm' => $row[22],
                'tipo_loja' => $row[23],
                'perfil_loja' => $row[24],
                'tipo_obra' => $row[25],
                'situacao_contratual' => $row[26],
                'data_assinatura_locacao' => $dataFormatada5,
                'data_assinatura_distrato' => $dataFormatada6,
                'data_encerramento' => $dataFormatada7,
                'data_aquisicao' => $dataFormatada8,
                'area_contrato' => $areaContrato,
                'area_util' => $areaUtil,
                'area_producao' => $areaProducao,
                'estacionamento' => $row[34],
                'bicicletario' => $row[35],
                'ginastica' => $row[36],
                'spa' => $row[37],
                'smartbike' => $row[38],
                'strong' => $row[39],
                'smartcross' => $row[40],
                'smartbox' => $row[41],
                'smartshape' => $row[42],
                'race' => $row[43],
                'vidya' => $row[44],
                'jabhouse' => $row[45],
                'tonus_gym' => $row[46],
                'one_pilates' => $row[47],
                'velocity' => $row[48],
                'kore' => $row[49],
                'burn' => $row[50],
                'squad' => $row[51],
                'skill_mill' => $row[52],
                'torq' => $row[53],
                'obs' => $row[54],
                'inicio_projeto' => $dataFormatada9,
            ]);
        }
    }

    private function formatarArea($valor)
    {
        // Remove pontos de milhar e substitui vírgula por ponto para valores decimais
        return (float) str_replace(',', '.', str_replace('.', '', $valor));
    }

    public function formatarData($valor)
    {
        if (empty($valor)) {
            return null;
        }

        if (is_numeric($valor)) {
            return Date::excelToDateTimeObject($valor)->format('Y-m-d');
        }

        $timestamp = strtotime($valor);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }
}
