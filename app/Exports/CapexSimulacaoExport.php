<?php

namespace App\Exports;

use App\Models\CapexSimulacao;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CapexSimulacaoExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithDrawings, WithEvents
{
    // Layout de linhas espelhando o PDF:
    // 1  → Título (cinza escuro, texto branco)
    // 2  → Faixa do logo (fundo preto + imagem)
    // 3–9 → Seção de informações (4 colunas: label | valor | label-dir | valor-dir)
    // 10 → Linha separadora (vazia)
    // 11 → Cabeçalho das colunas
    // 12+→ Itens
    // N+1→ Total CAPEX
    // N+2→ Custo por m²
    private const TITLE_ROW = 1;

    private const LOGO_ROW = 2;

    private const INFO_START = 3;

    private const INFO_END = 9;

    private const SEP_ROW = 10;

    private const COL_HEADER_ROW = 11;

    private const DATA_START = 12;

    // Cores extraídas do PDF
    private const COR_CINZA = 'FF58595B';

    private const COR_CINZA_ESCURO = 'FF3A3A3C';

    private const COR_PRETO = 'FF1A1A1A';

    private const COR_AZUL_DISC = 'FF1F4E79';

    private const COR_BRANCO = 'FFFFFFFF';

    private int $itensCount;

    public function __construct(private CapexSimulacao $record)
    {
        $this->record->loadMissing(['itens', 'projeto', 'faixaArea']);
        $this->itensCount = $this->record->itens->count();
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        $itens = $this->record->itens->sortBy('ordem');
        $rows = [];

        // Linha 1 — Título
        $rows[] = ['ORDEM DE INVESTIMENTO', '', '', '', ''];

        // Linha 2 — Faixa do logo (será estilizada com fundo preto; imagem inserida via WithDrawings)
        $rows[] = ['', '', '', '', ''];

        // Linhas 3–9 — Informações (label | valor | label-direita | valor-direita)
        $rows[] = ['Sigla:', strtoupper($this->record->sigla_exibicao ?? $this->record->sigla ?? '—'), '', 'Revisão:',
  $this->record->revisao_label];
        $rows[] = ['Unidade:', $this->record->nome_exibicao ?? $this->record->nome ?? '—', '', 'Data:', Carbon::now()->format('d/m/Y')];
        $rows[] = ['UF:', strtoupper($this->record->uf_exibicao ?? $this->record->uf ?? '—'), '', '', ''];
        $rows[] = ['Endereço:', $this->record->endereco_exibicao ?? $this->record->endereco ?? '—', '', '', ''];
        $rows[] = ['Área da Unidade (m²):', number_format((float) $this->record->area_unidade, 2, ',', '.'), '', '', ''];
        $rows[] = ['Fator de Correção:', number_format((float) $this->record->fator_correcao, 4, ',', '.'), '', '', ''];
        $rows[] = ['Faixa Identificada:', $this->record->faixa_nome ?? '—', '', '', ''];

        // Linha 10 — Separador vazio
        $rows[] = ['', '', '', '', ''];

        // Linha 11 — Cabeçalho das colunas
        $rows[] = ['#', 'Disciplina', 'Valor Base (R$/m² ou R$)', 'Custo Estimado (R$)', '%'];

        // Itens — values() garante índice sequencial 0,1,2... após sortBy
        foreach ($itens->values() as $index => $item) {
            $rows[] = [
                $index + 1,
                strtoupper($item->nome_escopo),
                (float) ($item->valor_base_m2 ?? 0),
                $item->incluir ? (float) ($item->custo_estimado ?? 0) : 0.0,
                $item->incluir ? round((float) ($item->percentual ?? 0), 1) : 0.0,
            ];
        }

        // Totais
        $rows[] = ['CAPEX Total Estimado', '', '', (float) $this->record->custo_total_estimado, 100];
        $rows[] = ['Custo Total por m²', '', '', (float) $this->record->custo_por_m2, ''];

        return $rows;
    }

    public function drawings(): Drawing
    {
        $logoPath = public_path('images/logo-band.png');

        $drawing = new Drawing;
        $drawing->setName('Logo Smart Group');
        $drawing->setDescription('Smart Group');
        $drawing->setPath($logoPath);
        $drawing->setHeight(50);
        $drawing->setCoordinates('A'.self::LOGO_ROW);
        $drawing->setOffsetX(461);
        $drawing->setOffsetY(7);

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $dataEnd = self::DATA_START + $this->itensCount - 1;
                $total1Row = $dataEnd + 1;
                $total2Row = $dataEnd + 2;

                $this->estilizarTitulo($sheet);
                $this->estilizarFaixaLogo($sheet);
                $this->estilizarInfos($sheet);
                $this->estilizarSeparador($sheet);
                $this->estilizarCabecalhoColunas($sheet);
                $this->estilizarItens($sheet, $dataEnd);
                $this->forcarZerosNumericos($sheet, $dataEnd);
                $this->estilizarTotais($sheet, $total1Row, $total2Row);
                $this->configurarLarguras($sheet);
            },
        ];
    }

    private function estilizarTitulo(Worksheet $sheet): void
    {
        $row = self::TITLE_ROW;

        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COR_CINZA]],
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::COR_BRANCO], 'name' => 'Arial'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
    }

    private function estilizarFaixaLogo(Worksheet $sheet): void
    {
        $row = self::LOGO_ROW;

        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COR_PRETO]],
        ]);
        // Altura suficiente para a imagem (36px) + padding
        $sheet->getRowDimension($row)->setRowHeight(50);
    }

    private function estilizarInfos(Worksheet $sheet): void
    {
        for ($row = self::INFO_START; $row <= self::INFO_END; $row++) {
            // Fundo branco explícito
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COR_BRANCO]],
            ]);

            // Coluna A — label (cinza escuro, negrito, maiúsculo via dados)
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => self::COR_CINZA], 'name' => 'Arial', 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Coluna B+C (merged) — valor (preto, negrito)
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF222222'], 'name' => 'Arial', 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Coluna D — label direita (cinza, negrito, alinhado à direita)
            $sheet->getStyle("D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => self::COR_CINZA], 'name' => 'Arial', 'size' => 9],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Coluna E — valor direita (preto, negrito, alinhado à direita)
            $sheet->getStyle("E{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF222222'], 'name' => 'Arial', 'size' => 9],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        // Merge B:C para os valores (colunas Valor Base e Custo Estimado, dando mais espaço ao texto)
        for ($row = self::INFO_START; $row <= self::INFO_END; $row++) {
            $sheet->mergeCells("B{$row}:C{$row}");
        }

        // Bordas laterais e superior/inferior na seção de info
        $sheet->getStyle('A'.self::INFO_START.':E'.self::INFO_END)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD0D0D0'],
                ],
            ],
        ]);
    }

    private function estilizarSeparador(Worksheet $sheet): void
    {
        $row = self::SEP_ROW;

        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F0F0']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(4);
    }

    private function estilizarCabecalhoColunas(Worksheet $sheet): void
    {
        $row = self::COL_HEADER_ROW;

        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COR_CINZA]],
            'font' => ['bold' => true, 'color' => ['argb' => self::COR_BRANCO], 'name' => 'Arial', 'size' => 8],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Disciplina alinhada à esquerda (igual ao PDF: col-disc text-align:left)
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Colunas numéricas alinhadas à direita
        foreach (['C', 'D', 'E'] as $col) {
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function estilizarItens(Worksheet $sheet, int $dataEnd): void
    {
        if ($this->itensCount === 0) {
            return;
        }

        for ($row = self::DATA_START; $row <= $dataEnd; $row++) {
            // Zebra stripes: pares em cinza claro, ímpares em branco (contando a partir do DATA_START)
            $par = ($row - self::DATA_START) % 2 === 1;
            $bgColor = $par ? 'FFF5F5F5' : self::COR_BRANCO;

            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'font' => ['name' => 'Arial', 'size' => 9],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE8E8E8']],
                ],
            ]);

            // # — centralizado, cinza
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF888888'], 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Disciplina — negrito, azul escuro (igual ao PDF: color #1f4e79)
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => self::COR_AZUL_DISC], 'size' => 8],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Valores monetários — setFormatCode() garante que 0 seja exibido como 0.00
            foreach (['C', 'D'] as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1E5631'], 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }

            // Percentual
            $sheet->getStyle("E{$row}")->applyFromArray([
                'font' => ['color' => ['argb' => 'FF1E5631'], 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0"%"');

            $sheet->getRowDimension($row)->setRowHeight(16);
        }
    }

    private function forcarZerosNumericos(Worksheet $sheet, int $dataEnd): void
    {
        if ($this->itensCount === 0) {
            return;
        }

        // WithCustomValueBinder pode não ser ativado em todas as versões do maatwebsite/excel.
        // Este passo garante que células que ficaram nulas (PHP trata 0 como falsy) recebam
        // explicitamente o valor numérico 0, para que o formato #,##0.00 exiba "0,00".
        for ($row = self::DATA_START; $row <= $dataEnd; $row++) {
            foreach (['C', 'D', 'E'] as $col) {
                $cell = $sheet->getCell("{$col}{$row}");

                if ($cell->getValue() === null || $cell->getValue() === '') {
                    $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);
                }
            }
        }
    }

    private function estilizarTotais(Worksheet $sheet, int $total1Row, int $total2Row): void
    {
        $totais = [
            $total1Row => ['cor' => self::COR_CINZA, 'perc' => true],
            $total2Row => ['cor' => self::COR_CINZA_ESCURO, 'perc' => false],
        ];

        foreach ($totais as $row => $config) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $config['cor']]],
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::COR_BRANCO], 'name' => 'Arial'],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$row}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            if ($config['perc']) {
                $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0"%"');
            }

            $sheet->getRowDimension($row)->setRowHeight(22);
        }
    }

    private function configurarLarguras(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(24);  // labels de info / # nos itens
        $sheet->getColumnDimension('B')->setWidth(46);  // valores de info / Disciplina
        $sheet->getColumnDimension('C')->setWidth(22);  // (merged com B na info) / Valor Base
        $sheet->getColumnDimension('D')->setWidth(22);  // labels dir. / Custo Estimado
        $sheet->getColumnDimension('E')->setWidth(12);  // valores dir. / %
    }

    public function nomeArquivo(): string
    {
        $sigla = $this->record->sigla_exibicao ?? $this->record->sigla;
        $identificador = filled($sigla)
            ? preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $sigla)
            : $this->record->id;

        return "CAPEX-Simulacao-{$identificador}-{$this->record->revisao_label}.xlsx";
    }
}
