<?php

namespace App\Imports;

use App\Models\ControlePedido;
use App\Models\Projeto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ControlePedidosBaseImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            /*
            |--------------------------------------------------------------------------
            | BUSCAR PROJETO PELA SIGLA
            |--------------------------------------------------------------------------
            */
            // dd(array_keys($row->toArray()));
            $projeto = Projeto::where('nova_sigla', trim($row['sigla'] ?? ''))->first();

            if (! $projeto) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | MONTAR ARRAY DOS PEDIDOS (AJUSTADO PARA 11, 21, 31...)
            |--------------------------------------------------------------------------
            */

            $pedidos = [];

            $codigos = [
                '1.1',
                '2.1',
                '3.1',
                '4.1',
                '5.1',
                '6.1',
                '7.1',
                '8.1',
                '9.1',
                '10.1',
                '11.1',
                '12.1',
                '13.1',
                '14.1',
                '15.1',
                '16.1',
                '17.1',
                '18.1',
                '19.1',
                '20.1',
                '21.1',
                '22.1',
                '23.1',
                '24.1',
                '27.1',
                '29.1',
                '30.1',
                '31.1',
                '32.1',
                '33.1',
                '34.1',
                '35.1',
                '36.1',
                '38.1',
                '39.1',
                '40.1',
                '45.1',
                '46.1',
                '47.1',
                '48.1',
                '50.1',
                '51.1',
                '52.1',
            ];

            $rowArray = $row->toArray();

            foreach ($codigos as $codigo) {

                // 34.1 → 341 (INT REAL)
                $colunaExcel = (int) str_replace('.', '', $codigo);

                // pega direto do array convertido
                $valor = $rowArray[$colunaExcel] ?? 0;

                // chave que vai para o banco
                $chaveBanco = str_replace('.', '_', $codigo);

                $pedidos[$chaveBanco] = ((int) $valor === 1);
            }

            /*
            |--------------------------------------------------------------------------
            | SALVAR OU ATUALIZAR
            |--------------------------------------------------------------------------
            */

            ControlePedido::updateOrCreate(
                [
                    'projeto_id' => $projeto->id,
                ],
                [
                    'elaboracao_contrato' => $this->parseDate($row['elaboracao_contrato'] ?? null),
                    'cnpj' => $row['cnpj'] ?? null,
                    'status' => strtolower($row['status'] ?? null),
                    'contratacao' => $this->parseDate($row['contratacao'] ?? null),
                    'observacoes' => $row['observacoes'] ?? null,

                    'instal_ar' => $row['instal_ar'] ?? null,
                    'luminarias' => $row['luminarias'] ?? null,
                    'instal_aquecedores' => $row['instal_aquecedores'] ?? null,
                    'fachada' => $row['fachada'] ?? null,
                    'marcenaria' => $row['marcenaria'] ?? null,
                    'construtora_sugestao' => $row['construtora_sugestao'] ?? null,
                    'divisorias' => $row['divisorias'] ?? null,
                    'contr_ar' => $row['contr_ar'] ?? null,
                    'ginastica' => $row['ginastica'] ?? null,

                    'valor_oi' => $this->parseMoney($row['valor_oi'] ?? 0),
                    'valor_realizado' => $this->parseMoney($row['valor_realizado'] ?? 0),
                    'realizado_nf' => $this->parseMoney($row['realizado_nf'] ?? 0),
                    'saldo' => $this->parseMoney($row['valor_oi'] ?? 0)
                        - $this->parseMoney($row['valor_realizado'] ?? 0),

                    'situacao' => strtolower($row['situacao'] ?? 'em_processo'),
                    'responsavel_orc' => $row['responsavel_orc'] ?? null,
                    'gestor_obra' => $row['gestor_obra'] ?? null,
                    'tamanho' => $row['tamanho'] ?? null,
                    'numero' => $row['numero'] ?? null,

                    'pedidos' => $pedidos,
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRATAR DATAS (RESOLVE 1970-01-01)
    |--------------------------------------------------------------------------
    */
    private function parseDate($value)
    {
        if (! $value) {
            return null;
        }

        try {

            // Se vier como número serial do Excel
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRATAR VALORES MONETÁRIOS CORRETAMENTE
    |--------------------------------------------------------------------------
    */
    private function parseMoney($value)
    {
        if (! $value) {
            return 0;
        }

        // Remove pontos de milhar e troca vírgula por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}
