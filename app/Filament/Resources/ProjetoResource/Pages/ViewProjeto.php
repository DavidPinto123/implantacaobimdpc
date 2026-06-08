<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Exports\ProjetoExport;
use App\Filament\Resources\ProjetoResource;
use App\Services\ProjetoIAService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ViewProjeto extends ViewRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        Filament::registerRenderHook(
            'panels::page.start',
            fn () => view('filament.resources.projetos.partials.custom-title-style')
        );

        Filament::registerRenderHook(
            'panels::page.header.actions.after',
            function () {
                if (! $this->record->link_docs) {
                    return '';
                }
                $docsImgDataUri = 'data:image/png;base64,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';

                return <<<HTML
                      <a href="{$this->record->link_docs}" target="_blank" style="display:inline-block; margin-left:10px;" title="Docs">
                          <img src="{$docsImgDataUri}" alt="Docs" style="width:60px; height:60px;" />
                      </a>
                  HTML;
            }
        );

        Filament::registerRenderHook(
            'panels::page.header.actions.after',
            function () {
                if (! $this->record->pin_google) {
                    return '';
                }
                $mapImgDataUri = 'data:image/png;base64,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';

                return <<<HTML
                      <a href="{$this->record->pin_google}" target="_blank" style="display:inline-block; margin-left:10px;" title="Google Maps">
                          <img src="{$mapImgDataUri}" alt="Google Maps" style="width:60px; height:60px;" />
                      </a>
                  HTML;
            }
        );

        Filament::registerRenderHook(
            'panels::page.header.actions.after',
            function () {
                if (! $this->record->link_construct_in) {
                    return '';
                }
                $construcImgDataUri = 'data:image/png;base64,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';

                return <<<HTML
                      <a href="{$this->record->link_construct_in}" target="_blank" style="display:inline-block; margin-left:10px;" title="ConstructIN">
                          <img src="{$construcImgDataUri}" alt="ConstructIN" style="width:60px; height:60px;" />
                      </a>
                  HTML;
            }
        );

        Filament::registerRenderHook(
            'panels::page.header.actions.after',
            function () {
                if (! $this->record->link_matterport) {
                    return '';
                }

                $qr = base64_encode(QrCode::format('png')->size(60)->generate($this->record->link_matterport));
                $qrDataUri = "data:image/png;base64,{$qr}";

                return <<<HTML
                <a href="{$this->record->link_matterport}" target="_blank" style="display:inline-block; margin-left:10px;" title="Matterport">
                    <img src="{$qrDataUri}" alt="QR Code Matterport" style="width:60px; height:60px; border-radius:6px; border:1px solid #ccc;"/>
                </a>
                HTML;
            }
        );

        $actions = [
            Actions\EditAction::make(),
            /*
            Action::make('visualizar_3d')
                ->label('Visualizar 3D')
                ->icon('heroicon-o-cube-transparent')
                ->color('primary')
                // só mostra se tiver os dados mínimos (ajuste os campos conforme seu modelo)
                ->visible(fn($record) => filled($record->nova_sigla))
                ->url(fn($record) => route('filament.pages.viewer3d-projeto', [
                    'projeto' => $record->id,
                ]))
                ->openUrlInNewTab(),
            */

            Action::make('visualizar_3d')
                ->label('Visualizar 3D')
                ->icon('heroicon-o-cube-transparent')
                ->color('primary')
                ->visible(fn ($record) => filled($record->nova_sigla))
                ->url(fn ($record) => ProjetoResource::getUrl('viewer-3d', [
                    'record' => $record,
                ]))
                ->openUrlInNewTab(),

            Action::make('analisarIA') // ou ModalAction::make
                ->label('Analisar com IA')
                ->icon('heroicon-o-cpu-chip')
                ->modalHeading('SmartAI')
                ->modalContent(function ($record) {
                    // Chama a IA
                    $raw = ProjetoIAService::analisarProjeto($record->toArray());

                    // Limpa o inglês inicial e instruções
                    $analise_pt = preg_replace('/^analysis.*?Ok\./s', '', $raw);

                    // Remove múltiplos espaços e quebras de linha extras
                    $analise_pt = trim(preg_replace('/\n\s+/', "\n", $analise_pt));

                    // Passa para o Blade
                    return view('filament.modals.analise-projeto', [
                        'analise' => $analise_pt,
                    ]);
                })
                ->modalWidth('2xl') // opcional, deixa maior
                ->modalSubmitAction(false), // remove botão de submit

            Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->color('green')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return Excel::download(
                        new ProjetoExport($this->record->id),
                        $this->record->nome.'.xlsx'
                    );
                }),
        ];

        return $actions;
    }

    public function getTitle(): string
    {
        if ($this->record->nova_sigla) {
            return "{$this->record->nome} - {$this->record->nova_sigla}";
        }

        return $this->record->codigo
            ? "{$this->record->nome} - {$this->record->codigo}"
            : $this->record->nome;
    }
}
