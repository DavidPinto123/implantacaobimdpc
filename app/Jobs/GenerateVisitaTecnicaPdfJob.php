<?php

namespace App\Jobs;

use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use App\Services\VisitaTecnicaPdfService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateVisitaTecnicaPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public int $recordId,
        public ?int $userId = null,
    ) {}

    public function handle(VisitaTecnicaPdfService $pdfService): void
    {
        $record = RelatorioVisitaTecnica::find($this->recordId);

        if (! $record) {
            return;
        }

        try {
            if (! $pdfService->hasValidStoredPdf($record)) {
                $pdfService->generateAndStorePdf($record);
            }

            $this->notifySuccess($record);
        } finally {
            $record->refresh();

            $record->forceFill([
                'pdf_generating_at' => null,
            ])->saveQuietly();
        }
    }

    public function failed(Throwable $exception): void
    {
        $record = RelatorioVisitaTecnica::find($this->recordId);

        if ($record) {
            $record->forceFill([
                'pdf_generating_at' => null,
            ])->saveQuietly();
        }

        $this->notifyFailure();

        report($exception);
    }

    private function notifySuccess(RelatorioVisitaTecnica $record): void
    {
        if (! $this->userId) {
            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('PDF gerado com sucesso')
            ->body("O PDF do relatório {$record->numero_relatorio_vt} já está disponível para download.")
            ->success()
            ->sendToDatabase($user);
    }

    private function notifyFailure(): void
    {
        if (! $this->userId) {
            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Falha ao gerar PDF')
            ->body('Ocorreu um erro ao gerar o PDF. Tente novamente.')
            ->danger()
            ->sendToDatabase($user);
    }
}
