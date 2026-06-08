<?php

namespace App\Exports;

use App\Models\Obras;
use App\Models\ControleNotaFiscalItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ControleRetrofitExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected array $dados;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    public function collection()
    {
        $registros = [];

        foreach ($this->dados['obras'] ?? [] as $obra) {
            $registros[] = [
                'tipo' => 'OBRA',
                'codigo' => $obra->codigo,
                'sigla' => $obra->sigla,
                'unidade' => $obra->unidade,
                'status' => $obra->status,
                'inicio' => optional($obra->inicio)->format('d/m/Y'),
                'fim' => optional($obra->fim)->format('d/m/Y'),
                'endereco' => $obra->endereco,
                'capex' => $obra->capex,
                'valor_contratado' => '',
                'grupo' => '',
                'numero_as' => '',
                'escopo' => '',
                'escopo_complementar' => '',
                'quantidade' => '',
                'fornecedor' => '',
                'valor' => '',
                'status_contratacao' => '',
                'data_entrega' => '',
                'observacoes' => '',
            ];
        }

        foreach ($this->dados['itens'] ?? [] as $item) {
            $registros[] = [
                'tipo' => 'ITEM',
                'codigo' => '',
                'sigla' => '',
                'unidade' => $item->controleNotaFiscal->obra->unidade,
                'status' => '',
                'inicio' => '',
                'fim' => '',
                'endereco' => '',
                'capex' => '',
                'valor_contratado' => $item->valor_global_a,
                'grupo' => $item->grupo ?? $item->asEscopo?->grupo,
                'numero_as' => ($item->numero_as ?? $item->asEscopo?->numero_as) .
                    (filled($item->numero_complemento) ? '/' . $item->numero_complemento : ''),
                'escopo' => $item->asEscopo?->escopo,
                'escopo_complementar' => $item->escopo_complementar,
                'quantidade' => $item->quantidade,
                'fornecedor' => $item->empresa,
                'valor' => $item->valor_global_a,
                'status_contratacao' => $item->status_retrofit,
                'data_entrega' => optional($item->data_entrega)->format('d/m/Y'),
                'observacoes' => $item->observacoes,
            ];
        }

        return collect($registros);
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Código',
            'Sigla',
            'Unidade',
            'Status da Unidade',
            'Início Obra',
            'Fim Obra',
            'Endereço',
            'CAPEX',
            'Valor Contratado',
            'Grupo',
            'A.S.',
            'Escopo',
            'Escopo Complementar',
            'Qtd.',
            'Fornecedor',
            'Valor',
            'Status Contratação',
            'Data de Entrega',
            'Observações',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '1f2937'],
                ],
            ],
        ];
    }
}
