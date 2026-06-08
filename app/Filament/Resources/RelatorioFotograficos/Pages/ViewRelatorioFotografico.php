<?php

namespace App\Filament\Resources\RelatorioFotograficos\Pages;

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRelatorioFotografico extends ViewRecord
{
    protected static string $resource = RelatorioFotograficoResource::class;

    public ?string $statusToSave = null;

    protected function getHeaderActions(): array
    {
        return [
            /*
            Actions\Action::make('aprovar')
                ->label('Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () =>
                    auth()->user()->hasRole('gestor_obra') &&
                    $this->record->status !== 'aprovado'
                )
                ->requiresConfirmation()
                ->modalHeading('Aprovar relatório')
                ->modalDescription('Deseja realmente aprovar este relatório?')
                ->action(function () {

                    $this->record->update([
                        'status' => 'aprovado'
                    ]);

                    // Notificação no sistema
                    Notification::make()
                        ->title('Relatório aprovado')
                        ->success()
                        ->body('O relatório foi aprovado com sucesso.')
                        ->send();

                    // Notificação para o autor
                    Notification::make()
                        ->title('Seu relatório foi aprovado')
                        ->body("O relatório da unidade {$this->record->projeto->nome} foi aprovado.")
                        ->success()
                        ->sendToDatabase($this->record->autor);
                }),

            Actions\Action::make('reprovar')
                ->label('Reprovar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () =>
                    auth()->user()->hasRole('gestor_obra') &&
                    $this->record->status !== 'reprovado'
                )
                ->requiresConfirmation()
                ->modalHeading('Reprovar relatório')
                ->modalDescription('Deseja realmente reprovar este relatório?')
                ->action(function () {

                    $this->record->update([
                        'status' => 'reprovado'
                    ]);

                    // Notificação no sistema
                    Notification::make()
                        ->title('Relatório reprovado')
                        ->danger()
                        ->body('O relatório foi reprovado.')
                        ->send();

                    // Notificação para o autor
                    Notification::make()
                        ->title('Seu relatório foi reprovado')
                        ->body("O relatório da unidade {$this->record->projeto->nome} foi reprovado.")
                        ->danger()
                        ->sendToDatabase($this->record->autor);
                }),
            */
            Actions\Action::make('pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('relatorios.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),

        ];
    }
}
