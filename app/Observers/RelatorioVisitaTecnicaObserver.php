<?php

namespace App\Observers;

use App\Jobs\GenerateVisitaTecnicaPdfJob;
use App\Models\RelatorioVisitaTecnica;

class RelatorioVisitaTecnicaObserver
{
    public function created(RelatorioVisitaTecnica $relatorio): void
    {
        $this->queuePdf($relatorio);
    }

    public function updated(RelatorioVisitaTecnica $relatorio): void
    {
        $changed = array_keys($relatorio->getChanges());

        $ignoredFields = [
            'pdf_path',
            'pdf_generated_at',
            'pdf_generating_at',
            'updated_at',
        ];

        $relevantChanges = array_diff($changed, $ignoredFields);

        if (empty($relevantChanges)) {
            return;
        }

        $this->queuePdf($relatorio);
    }

    private function queuePdf(RelatorioVisitaTecnica $relatorio): void
    {
        if (! empty($relatorio->pdf_generating_at) && $relatorio->pdf_generating_at->gt(now()->subMinutes(5))) {
            return;
        }

        $relatorio->forceFill([
            'pdf_generated_at' => null,
            'pdf_generating_at' => now(),
        ])->saveQuietly();

        GenerateVisitaTecnicaPdfJob::dispatch($relatorio->id);
    }
}
