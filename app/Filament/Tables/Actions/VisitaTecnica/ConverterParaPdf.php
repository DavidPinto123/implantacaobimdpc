<?php

namespace App\Filament\Tables\Actions\VisitaTecnica;

use App\Jobs\GenerateVisitaTecnicaPdfJob;
use App\Models\RelatorioVisitaTecnica;
use App\Services\VisitaTecnicaPdfService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ConverterParaPdf
{
    public static function make(): Action
    {
        return Action::make('gerar_pdf')
            ->label('PDF')
            ->color('danger')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function (RelatorioVisitaTecnica $record, VisitaTecnicaPdfService $pdfService) {
                $record->refresh();

                if ($pdfService->hasValidStoredPdf($record)) {
                    redirect()->to(route('download.visita.tecnica', $record->id));

                    return;
                }

                if ($pdfService->isGenerating($record)) {
                    Notification::make()
                        ->title('PDF em geração')
                        ->body('O PDF já está sendo gerado. Aguarde a conclusão.')
                        ->warning()
                        ->send();

                    return;
                }

                $record->forceFill([
                    'pdf_generated_at' => null,
                    'pdf_generating_at' => now(),
                ])->saveQuietly();

                GenerateVisitaTecnicaPdfJob::dispatch($record->id, auth()->id());

                Notification::make()
                    ->title('PDF em geração')
                    ->body('O PDF foi enviado para processamento. Você será avisado quando estiver pronto.')
                    ->warning()
                    ->send();
            });
    }
}
