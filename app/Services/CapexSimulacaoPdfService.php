<?php

namespace App\Services;

use App\Models\CapexSimulacao;
use Barryvdh\DomPDF\Facade\Pdf;

class CapexSimulacaoPdfService
{
    public function getViewData(CapexSimulacao $record): array
    {
        $record->loadMissing(['itens', 'projeto', 'faixaArea']);

        return [
            'record' => $record,
        ];
    }

    public function makePdf(CapexSimulacao $record)
    {
        ini_set('memory_limit', '512M');

        return Pdf::loadView(
            'invoices.pdfCapexSimulacao',
            $this->getViewData($record)
        )
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);
    }

    public function nomeArquivo(CapexSimulacao $record): string
    {
        $sigla = $record->sigla_exibicao ?? $record->sigla;
        $identificador = filled($sigla)
            ? preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $sigla)
            : $record->id;

        return "CAPEX-Simulacao-{$identificador}-{$record->revisao_label}.pdf";
    }
}
