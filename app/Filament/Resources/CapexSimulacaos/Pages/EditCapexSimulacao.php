<?php

namespace App\Filament\Resources\CapexSimulacaos\Pages;

use App\Exports\CapexSimulacaoExport;
use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use App\Filament\Resources\CapexSimulacaos\RelationManagers\ItensRelationManager;
use App\Filament\Widgets\CapexSimulacaoItensTreeWidget;
use App\Services\CapexSimulacaoPdfService;
use App\Services\CapexSimulacaoService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Facades\Excel;

class EditCapexSimulacao extends EditRecord
{
    protected static string $resource = CapexSimulacaoResource::class;

    public function getTitle(): string
    {
        return 'SIMULADOR DE CAPEX — CUSTO/m² POR FAIXA DE METRAGEM';
    }

    protected function getFooterWidgets(): array
    {
        return [
            CapexSimulacaoItensTreeWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar_pdf')
                ->label('Exportar PDF')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (CapexSimulacaoPdfService $pdfService) {
                    $record = $this->record->fresh(['itens', 'projeto', 'faixaArea']);
                    $pdf = $pdfService->makePdf($record);
                    $nome = $pdfService->nomeArquivo($record);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $nome,
                        ['Content-Type' => 'application/pdf']
                    );
                }),

            Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->color('success')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    $record = $this->record->fresh(['itens', 'projeto', 'faixaArea']);
                    $export = new CapexSimulacaoExport($record);

                    return Excel::download($export, $export->nomeArquivo());
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->increment('revisao');
        $this->record->ordenarItensPorCustoEstimado();
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('capex-itens-recarregados');
        $this->dispatch('capex-itens-recarregados')->to(ItensRelationManager::class);
    }

    #[On('capex-totais-atualizados')]
    public function atualizarTotaisDoFormulario(): void
    {
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
    }

    #[On('capex-recalcular-itens')]
    public function recalcularItensEmTempoReal(CapexSimulacaoService $service): void
    {
        $areaUnidade = blank($this->data['area_unidade'] ?? null)
            ? 0
            : (float) $this->data['area_unidade'];

        $fatorCorrecao = blank($this->data['fator_correcao'] ?? null)
            ? 1
            : (float) $this->data['fator_correcao'];

        $service->recalcularComDadosDoFormulario($this->record, $areaUnidade, $fatorCorrecao);

        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('capex-itens-recarregados');
        $this->dispatch('capex-itens-recarregados')->to(ItensRelationManager::class);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
