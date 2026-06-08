<?php

namespace App\Http\Controllers;

use App\Models\AutorizacaoServico;
use App\Services\AutorizacaoServicoPdfService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AutorizacaoServicoDownloadController extends Controller
{
    public function pdf(
        AutorizacaoServico $record,
        AutorizacaoServicoPdfService $pdfService,
    ): StreamedResponse {
        $this->authorizeDownload();

        $path = (string) $record->anexo_autorizacao_servico;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($pdfService->diskName());

        abort_if($path === '' || ! $disk->exists($path), 404);

        return $disk->download($path, $pdfService->nomeArquivo($record));
    }

    protected function authorizeDownload(): void
    {
        abort_unless((bool) Auth::user()?->can('View:AutorizacaoServico'), 403);
    }
}
