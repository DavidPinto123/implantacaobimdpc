<?php

namespace App\Filament\Resources\Asas\Pages;

use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\Asas\Widgets\AsasResumoTabela;
use App\Filament\Resources\Asas\Widgets\PercentualGruposAditivos;
use App\Filament\Resources\Asas\Widgets\ResumoAditivos;
use App\Filament\Resources\Asas\Widgets\ResumoContrato;
use App\Models\Asa;
use App\Models\Obras;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class ListAsas extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AsaResource::class;

    public ?string $projetoFiltro = null;

    public ?string $construtoraFiltro = null;

    public function getTitle(): string
    {
        return 'CC - CONTROLE DE CUSTOS ADICIONAIS';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'projetoFiltro' => $this->projetoFiltro,
            'construtoraFiltro' => $this->construtoraFiltro,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('projetoFiltro')
                    ->label('Obra')
                    ->options(
                        // Use "obras" as the source, but keep the filter state as `projeto_id`
                        // since `autorizacao_servico_adicionais` relates to `projetos` via `projeto_id`.
                        Obras::query()
                            ->join('autorizacao_servico_adicionais', 'autorizacao_servico_adicionais.projeto_id', '=', 'obras.projeto_id')
                            ->whereNotNull('obras.projeto_id')
                            ->whereNotNull('obras.unidade')
                            ->where('obras.unidade', '<>', '')
                            ->distinct()
                            ->orderBy('obras.unidade')
                            ->pluck('obras.unidade', 'obras.projeto_id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->columnSpan(1)
                    ->live(),

                Select::make('construtoraFiltro')
                    ->label('Fornecedor')
                    ->options(
                        Asa::query()
                            ->whereNotNull('solicitante')
                            ->where('solicitante', '<>', '')
                            ->orderBy('solicitante')
                            ->distinct()
                            ->pluck('solicitante', 'solicitante')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->columnSpan(1)
                    ->live(),
            ])
            ->columns(2)
            ->statePath('');
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.asas.pages.list-asas-filters');
    }
    /*
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Criar ASA'),
        ];
    }
    */

    protected function getHeaderWidgets(): array
    {
        return [
            PercentualGruposAditivos::make([
                'projetoFiltro' => $this->projetoFiltro,
                'construtoraFiltro' => $this->construtoraFiltro,
            ]),
            ResumoAditivos::make([
                'projetoFiltro' => $this->projetoFiltro,
                'construtoraFiltro' => $this->construtoraFiltro,
            ]),
            ResumoContrato::make([
                'projetoFiltro' => $this->projetoFiltro,
                'construtoraFiltro' => $this->construtoraFiltro,
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AsasResumoTabela::make([
                'projetoFiltro' => $this->projetoFiltro,
                'construtoraFiltro' => $this->construtoraFiltro,
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 3,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function updatedProjetoFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedConstrutoraFiltro(): void
    {
        $this->resetTable();
    }

    public function limparFiltros(): void
    {
        $this->projetoFiltro = null;
        $this->construtoraFiltro = null;

        $this->form->fill([
            'projetoFiltro' => null,
            'construtoraFiltro' => null,
        ]);

        $this->resetTable();
    }
}
