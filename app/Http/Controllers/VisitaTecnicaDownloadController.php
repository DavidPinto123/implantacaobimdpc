<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateVisitaTecnicaPdfJob;
use App\Models\RelatorioVisitaTecnica;
use App\Services\VisitaTecnicaPdfService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitaTecnicaDownloadController extends Controller
{
    public function download(
        RelatorioVisitaTecnica $record,
        VisitaTecnicaPdfService $pdfService
    ): StreamedResponse|RedirectResponse {
        $record->refresh();

        if ($pdfService->hasValidStoredPdf($record)) {
            $nomeArquivo = 'Relatorio-Visita-Tecnica-'.$record->numero_relatorio_vt.'.pdf';

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

            return $disk->download($record->pdf_path, $nomeArquivo);
        }

        if (! $pdfService->isGenerating($record)) {
            $pdfService->markAsGenerating($record);
            GenerateVisitaTecnicaPdfJob::dispatch($record->id);
        }

        return redirect()->back();
    }
}
