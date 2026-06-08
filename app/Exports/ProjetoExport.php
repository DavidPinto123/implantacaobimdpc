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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjetoExport implements FromView, WithDrawings, WithEvents, WithStyles
{
    protected $projeto;

    public function __construct(int $projetoId)
    {
        // Consulta única com relacionamentos
        $this->projeto = Projeto::with(['user', 'etapas', 'cidade', 'estado', 'pais'])
            ->findOrFail($projetoId);
    }

    public function styles(Worksheet $sheet)
    {
        // Define fonte Roboto em todo range usado
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A:{$lastColumn}")
            ->getFont()
            ->setName('Roboto')
            ->setSize(11);

        return [];
    }

    public function view(): View
    {
        return view('exports.projeto', [
            'projeto' => $this->projeto,
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

                // === Ajusta altura das linhas a partir da 3ª ===
                for ($row = 3; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(25);
                }

                $sheet->getColumnDimension('F')->setWidth(40);

                // === Formatos numéricos dinamicamente até última linha ===
                $colunas = ['CU', 'CX', 'CY', 'CZ', 'CT', 'DB', 'DC', 'DG'];
                foreach ($colunas as $coluna) {
                    if ($coluna <= $sheet->getHighestColumn()) {
                        $sheet->getStyle("{$coluna}2:{$coluna}{$lastRow}")
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                }

                // === Título ===
                $ultimaColunaTitulo = 'I';
                $rangeTitulo = "A1:{$ultimaColunaTitulo}1";
                $event->sheet->mergeCells($rangeTitulo);
                $event->sheet->getStyle($rangeTitulo)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle($rangeTitulo)->getFont()
                    ->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFF');

                // Congela cabeçalho
                $sheet->freezePane('K3');

                // === Cancelado? aplica vermelho + tachado ===
                if (trim(mb_strtolower($this->projeto->status)) === 'cancelada') {
                    $lastColumn = $sheet->getHighestColumn();
                    $rangeCancelado = "A4:{$lastColumn}{$lastRow}";

                    $sheet->getStyle($rangeCancelado)->getFont()
                        ->getColor()->setARGB('FFFF0000');
                    $sheet->getStyle($rangeCancelado)->getFont()
                        ->setStrikethrough(true);
                }
            },
        ];
    }
}
