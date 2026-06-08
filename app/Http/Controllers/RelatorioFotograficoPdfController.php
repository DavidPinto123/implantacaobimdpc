<?php

namespace App\Http\Controllers;

use App\Models\RelatorioFotografico;
use App\Services\RelatorioFotograficoPdfService;
use Illuminate\Support\Facades\Storage;

class RelatorioFotograficoPdfController extends Controller
{
    public function generate(
        RelatorioFotografico $record,
        RelatorioFotograficoPdfService $pdfService
    ) {
        $pdf = $pdfService->makePdf($record);
        $pdfBinary = $pdf->output();
        $path = RelatorioFotograficoPdfService::pdfStoragePath($record);

        Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($path, $pdfBinary, [
            'ContentType' => 'application/pdf',
        ]);

        return response()->streamDownload(
            static function () use ($pdfBinary): void {
                print $pdfBinary;
            },
            RelatorioFotograficoPdfService::pdfFileName($record),
            ['Content-Type' => 'application/pdf']
        );
    }
}
