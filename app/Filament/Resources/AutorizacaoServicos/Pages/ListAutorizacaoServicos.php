<?php

namespace App\Filament\Resources\AutorizacaoServicos\Pages;

use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\Obras;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class ListAutorizacaoServicos extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AutorizacaoServicoResource::class;

    public ?string $obraFiltro = null;

    public ?string $construtoraFiltro = null;

    public function getTitle(): string
    {
        return 'AS';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'obraFiltro' => $this->obraFiltro,
            'construtoraFiltro' => $this->construtoraFiltro,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('obraFiltro')
                    ->label('Obra')
                    ->options(
                        Obras::query()
                            ->join('autorizacao_servicos', 'autorizacao_servicos.obra_id', '=', 'obras.id')
                            ->whereNotNull('obras.unidade')
                            ->where('obras.unidade', '<>', '')
                            ->distinct()
                            ->orderBy('obras.unidade')
                            ->pluck('obras.unidade', 'obras.id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->columnSpan(1)
                    ->live(),

                Select::make('construtoraFiltro')
                    ->label('Fornecedor')
                    ->options(
                        Construtora::query()
                            ->whereIn('id', AutorizacaoServico::query()
                                ->select('construtora_id')
                                ->whereNotNull('construtora_id'))
                            ->orderBy('nome')
                            ->pluck('nome', 'id')
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
        return view('filament.resources.autorizacao-servicos.pages.list-autorizacao-servicos-filters');
    }

    public function updatedObraFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedConstrutoraFiltro(): void
    {
        $this->resetTable();
    }

    public function limparFiltros(): void
    {
        $this->obraFiltro = null;
        $this->construtoraFiltro = null;

        $this->form->fill([
            'obraFiltro' => null,
            'construtoraFiltro' => null,
        ]);

        $this->resetTable();
    }
}
