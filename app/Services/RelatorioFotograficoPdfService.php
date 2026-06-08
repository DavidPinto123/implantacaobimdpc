<?php

namespace App\Services;

use App\Models\RelatorioFotografico;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioFotograficoPdfService
{
    public static function pdfFileName(RelatorioFotografico|int $record): string
    {
        $id = $record instanceof RelatorioFotografico ? $record->id : $record;

        return 'relatorio-fotografico-'.$id.'.pdf';
    }

    public static function pdfStoragePath(RelatorioFotografico|int $record): string
    {
        $id = $record instanceof RelatorioFotografico ? $record->id : $record;

        return 'relatorios-rf/'.$id.'/pdf/'.self::pdfFileName($id);
    }

    public function getViewData(RelatorioFotografico $record): array
    {
        return [
            'relatorio' => $record,
        ];
    }

    public function makePdf(RelatorioFotografico $record)
    {
        ini_set('memory_limit', '2G');

        return Pdf::loadView(
            'pdf.relatorio-fotografico',
            $this->getViewData($record)
        )
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true, // 🔥 ESSENCIAL para imagens (R2 / storage / URLs)
                'isHtml5ParserEnabled' => true,
            ]);
    }
}
