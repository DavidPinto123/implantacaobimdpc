<?php

namespace App\Exports;

use App\Models\Projeto;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListProjetoExport implements FromView, WithDrawings, WithEvents, WithStyles
{
    protected $tabSelecionada;

    protected $projetos;

    public function __construct($tabSelecionada)
    {
        $this->tabSelecionada = $tabSelecionada;

        $query = Projeto::with(['user', 'etapas', 'cidade', 'estado', 'pais']);

        if ($this->tabSelecionada !== 'Todos') {
            $query->whereHas('etapas', function ($q) {
                $q->where('nome', $this->tabSelecionada);
            });
        }

        $this->projetos = $query->get();
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:ZZ')->getFont()->setName('Roboto')->setSize(11);

        return [];
    }

    public function view(): View
    {
        return view('exports.listProjetos', [
            'projetos' => $this->projetos,
        ]);
    }

    public function drawings()
    {
        $drawing = new Drawing;
        $drawing->setName('Logo');
        $drawing->setDescription('Logo Empresas');
        $drawing->setPath(public_path('images/logoExcel.png'));
        $drawing->setHeight(70);
        $drawing->setCoordinates('G1');
        $drawing->setOffsetY(20);

        $robo1 = new Drawing;
        $robo1->setName('robo1');
        $robo1->setDescription('Ícone de robo');
        $robo1->setPath(public_path('images/robo1.png'));
        $robo1->setHeight(90);
        $robo1->setCoordinates('A1');
        $robo1->setOffsetY(10)->setOffsetX(20);

        $robo2 = new Drawing;
        $robo2->setName('robo2');
        $robo2->setDescription('Ícone de robo');
        $robo2->setPath(public_path('images/robo2.png'));
        $robo2->setHeight(90);
        $robo2->setCoordinates('B1');
        $robo2->setOffsetY(10)->setOffsetX(20);

        $robo3 = new Drawing;
        $robo3->setName('robo3');
        $robo3->setDescription('Ícone de robo');
        $robo3->setPath(public_path('images/robo3.png'));
        $robo3->setHeight(90);
        $robo3->setCoordinates('C1');
        $robo3->setOffsetY(10)->setOffsetX(20);

        return [$drawing, $robo1, $robo2, $robo3];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                /**
                 * =====================
                 *  AJUSTE DE ALTURA
                 * =====================
                 */
                for ($row = 3; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(25);
                }

                $sheet->getColumnDimension('F')->setWidth(40);

                /**
                 * =====================
                 *  FORMATAÇÃO NÚMEROS
                 * =====================
                 */
                $colunas = ['CU', 'CX', 'CY', 'CZ', 'CT', 'DB', 'DC', 'DG'];
                foreach ($colunas as $coluna) {
                    if ($coluna <= $lastColumn) {
                        $sheet->getStyle("{$coluna}2:{$coluna}{$lastRow}")
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                }

                /**
                 * =====================
                 *  ESTILO DO TÍTULO
                 * =====================
                 */
                $ultimaColunaTitulo = 'I';
                $rangeTitulo = "A1:{$ultimaColunaTitulo}1";

                $event->sheet->mergeCells($rangeTitulo);
                $event->sheet->getStyle($rangeTitulo)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->getStyle($rangeTitulo)->getFont()
                    ->setBold(true)
                    ->setSize(16)
                    ->getColor()->setARGB('FFFFFF');

                $event->sheet->getStyle($rangeTitulo)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('000000');

                $sheet->freezePane('K3');

                /**
                 * =========================
                 *  ESTILO PARA "Cancelada"
                 * =========================
                 */
                $primeiraLinhaDados = 4;
                $estiloCancelada = [
                    'font' => [
                        'color' => ['argb' => 'FFFF0000'],
                        'strikethrough' => true,
                    ],
                ];

                foreach ($this->projetos as $i => $projeto) {
                    if (trim(mb_strtolower($projeto->status)) === 'cancelada') {
                        $linha = $primeiraLinhaDados + $i;
                        $sheet->getStyle("A{$linha}:{$lastColumn}{$linha}")
                            ->applyFromArray($estiloCancelada);
                    }
                }
            },
        ];
    }
}
