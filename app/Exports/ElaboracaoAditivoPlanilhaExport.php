<?php

namespace App\Exports;

use App\Models\ElaboracaoAditivo;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElaboracaoAditivoPlanilhaExport implements FromArray, WithDrawings, WithStyles, WithTitle
{
    protected ElaboracaoAditivo $record;

    public function __construct(int $id)
    {
        $this->record = ElaboracaoAditivo::query()
            ->with([
                'obra',
                'construtora',
                'gestor',
                'asEscopo',
                'itens',
            ])
            ->findOrFail($id);
    }

    public function title(): string
    {
        return 'Aditivo';
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['', 'PLANILHA PARA ELABORAÇÃO DE ADITIVOS DE OBRA'];
        $rows[] = ['', 'EXPANSÃO / ORÇAMENTOS'];
        $rows[] = ['', 'OBRA:', $this->record->obra?->unidade ?? '-'];
        $rows[] = ['', 'GESTOR:', $this->record->gestor?->name ?? '-'];
        $rows[] = ['', 'DATA:', optional($this->record->data)->format('d/m/Y')];
        $rows[] = ['', 'REF. SERVIÇO:', $this->record->asEscopo?->escopo ?? '-'];
        $rows[] = ['', 'GERENCIADORA:', $this->record->construtora?->nome ?? '-'];
        $rows[] = [];

        $rows[] = [
            '',
            'ITEM',
            'DESCRIÇÃO DO SERVIÇO',
            'QT.',
            'UND.',
            'R$ MAT. (UNIT.)',
            'R$ M.O. (UNIT.)',
            'TOTAL UNIT (MAT + M.O.)',
            'R$ TOTAL GERAL',
        ];

        foreach ($this->record->itens as $item) {
            $rows[] = [
                '',
                $item->item,
                $item->descricao_servico,
                (float) $item->quantidade,
                $item->unidade,
                (float) $item->valor_material_unitario,
                (float) $item->valor_mao_obra_unitario,
                (float) $item->total_unitario,
                (float) $item->valor_total_geral,
            ];
        }

        $rows[] = [
            '',
            'TOTAL GERAL',
            '',
            '',
            '',
            '',
            '',
            '',
            (float) $this->record->itens->sum('valor_total_geral'),
        ];

        return $rows;
    }

    public function drawings()
    {
        $drawing = new Drawing;
        $drawing->setName('Logo');
        $drawing->setDescription('Logo da empresa');
        $drawing->setPath(public_path('images/logo_aditivo_obras.png')); // ajuste o caminho
        $drawing->setCoordinates('F3');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(-5);
        $drawing->setHeight(90);

        return [$drawing];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridlines(false);
        $headerRow = 8;
        $firstItemRow = 9;
        $lastItemRow = $firstItemRow + $this->record->itens->count() - 1;
        $totalRow = $lastItemRow + 1;

        $sheet->mergeCells('B1:I1');
        $sheet->mergeCells("B{$totalRow}:H{$totalRow}");

        $sheet->getStyle('B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
            ],
        ]);

        $sheet->getStyle('B2:C7')->applyFromArray([
            'font' => [
                'size' => 11,
            ],
        ]);

        $sheet->getStyle("B{$headerRow}:I{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F4B400',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        if ($lastItemRow >= $firstItemRow) {
            $sheet->getStyle("B{$firstItemRow}:I{$lastItemRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_HAIR,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle("B{$firstItemRow}:B{$lastItemRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("C{$firstItemRow}:C{$lastItemRow}")
                ->getAlignment()
                ->setWrapText(true);

            $sheet->getStyle("C{$firstItemRow}:C{$lastItemRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP);

            $sheet->getStyle("D{$firstItemRow}:D{$lastItemRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("E{$firstItemRow}:E{$lastItemRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("F{$firstItemRow}:I{$lastItemRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$firstItemRow}:D{$lastItemRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            $sheet->getStyle("F{$firstItemRow}:I{$lastItemRow}")
                ->getNumberFormat()
                ->setFormatCode('"R$" #,##0.00');

            for ($row = $firstItemRow; $row <= $lastItemRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(-1);
            }
        }

        $sheet->getStyle("B{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F4B400',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("B{$totalRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("I{$totalRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("I{$totalRow}")
            ->getNumberFormat()
            ->setFormatCode('"R$" #,##0.00');

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(57.89);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);

        return [];
    }
}
