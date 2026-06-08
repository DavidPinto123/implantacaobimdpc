<?php

use App\Http\Controllers\ApsAuthController;
use App\Http\Controllers\ApsDocsController;
use App\Http\Controllers\Auth\UserInvitationController;
use App\Http\Controllers\AutorizacaoServicoDownloadController;
use App\Http\Controllers\CronogramaController;
use App\Http\Controllers\DadosController;
use App\Http\Controllers\ImageVariantController;
use App\Http\Controllers\PosObra\WhatsAppWebhookController;
use App\Http\Controllers\RelatorioFotograficoPdfController;
use App\Http\Controllers\Viewer3DProjetoController;
use App\Http\Controllers\VisitaTecnicaDownloadController;
use App\Models\Projeto;
use App\Services\ProjetosPorLocalidadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/dados', [DadosController::class, 'index'])->name('dados.index');

Route::get('/convite-usuario/{user}/{hash}/{token}', UserInvitationController::class)
    ->middleware('signed')
    ->name('users.invitation.complete');

Route::get('/', function () {
    // redireciona para o path configurado do Filament (padrão "admin")
    return redirect()->to(config('filament.path', 'admin'));
});

Route::get('/projetos/{projeto}/matterport-qrcode', function (Projeto $projeto) {
    $url = $projeto->link_matterport;
    if (! $url) {
        abort(404);
    }

    $qr = QrCode::format('png')->size(300)->generate($url);

    return response($qr)->header('Content-Type', 'image/png');
})->name('projetos.matterport.qrcode');

Route::get('{record}/pdf/download', [VisitaTecnicaDownloadController::class, 'download'])->name('download.visita.tecnica');

Route::middleware('auth')->prefix('autorizacoes-servico/{record}')->group(function (): void {
    Route::get('/pdf/download', [AutorizacaoServicoDownloadController::class, 'pdf'])
        ->name('autorizacoes-servico.pdf.download');
});

Route::get('/relatorios-fotograficos/{record}/pdf', [RelatorioFotograficoPdfController::class, 'generate'])->name('relatorios.pdf');

Route::middleware('auth')->get('/media/image-variants/{sourceKey}', ImageVariantController::class)
    ->name('media.image-variants.show');

Route::get('/projetos-por-estado/{sigla}', function ($sigla, ProjetosPorLocalidadeService $service) {
    $nome = ProjetosPorLocalidadeService::siglaBrParaNome($sigla);

    return response()->json($service->buscar('BR', $nome ?? $sigla));
});

Route::get('/projetos-por-localidade', function (Request $request, ProjetosPorLocalidadeService $service) {
    $pais = $request->query('pais');
    $uf = $request->query('uf');

    return response()->json($service->buscar($pais, $uf));
});
Route::get('/aps/token', [ApsAuthController::class, 'getToken'])
    ->name('aps.token');
Route::get('/aps/hubs', [ApsDocsController::class, 'hubs']);
Route::get('/aps/projetos/{hubId}', [ApsDocsController::class, 'projetos']);
Route::get('/aps/pastas/{hubId}/{projetoId}', [ApsDocsController::class, 'pastas']);
Route::get('/aps/arquivos/{projetoId}/{pastaId}', [ApsDocsController::class, 'arquivos']);
// routes/web.php
Route::get(
    '/aps/docs/rvt/{projectId}/{folderId}/{prefix}',
    [ApsDocsController::class, 'firstRvtByPrefix']
)->name('aps.docs.rvt-by-prefix');
Route::get('/admin/projetos/{projeto}/viewer-3d', Viewer3DProjetoController::class)
    ->name('filament.pages.viewer3d-projeto');

Route::middleware('auth')->prefix('cronograma')->group(function () {
    Route::get('/', [CronogramaController::class, 'index'])->name('cronograma.index');
    Route::post('/', [CronogramaController::class, 'store'])->name('cronograma.store');
    Route::put('/{cronogramaFase}', [CronogramaController::class, 'update'])->name('cronograma.update');
    Route::post('/{projeto}/recalcular', [CronogramaController::class, 'atualizarStatus'])->name('cronograma.recalcular');
});

// Pós Obra — WhatsApp Webhook (sem CSRF)
Route::prefix('webhook/whatsapp')->name('whatsapp.webhook.')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify'])->name('verify');
    Route::post('/', [WhatsAppWebhookController::class, 'receive'])->name('receive');
});
