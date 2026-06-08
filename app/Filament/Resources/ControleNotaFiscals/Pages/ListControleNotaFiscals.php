<?php

namespace App\Filament\Resources\ControleNotaFiscals\Pages;

use App\Enums\AsStatus;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Models\Construtora;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\HtmlString;
use Livewire\WithPagination;

class ListControleNotaFiscals extends Page
{
    use WithPagination;

    protected static string $resource = ControleNotaFiscalResource::class;

    protected string $view = 'filament.resources.controle-nota-fiscals.pages.list-controle-nota-fiscals';

    /** @var array<int, int> */
    public array $controlesExpandidos = [];

    /** @var array<int, string> */
    public array $selecionadas = [];

    public string $busca = '';

    /** @var array<int, string> */
    public array $filtroStatus = [];

    /** @var array<int, string> */
    public array $filtroStatusUnidade = [];

    /** @var array<int, string> */
    public array $filtroFornecedor = [];

    public ?string $filtroInicioDe = null;

    public ?string $filtroInicioAte = null;

    public ?string $filtroFimDe = null;

    public ?string $filtroFimAte = null;

    public ?string $filtroCapexMin = null;

    public ?string $filtroCapexMax = null;

    public ?string $filtroValorMin = null;

    public ?string $filtroValorMax = null;

    public int $porPagina = 25;

    /** @var array<int, string> */
    public array $construtoraOptions = [];

    /** @var array<string, string> */
    public array $statusUnidadeOptions = [
        'Em processo' => 'Em processo',
        'Obras' => 'Obras',
        'Inaugurada' => 'Inaugurada',
        'Cancelada' => 'Cancelada',
        'Stand-by' => 'Stand-by',
        'Deletar comercial' => 'Deletar comercial',
    ];

    /** @var array<string, string> */
    public array $statusOptions = [
        'ativo' => 'Ativo',
        'aguardando_construtora' => 'Aguardando fornecedor',
        'aguardando_financeiro' => 'Aguardando financeiro',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado',
        'encerrado' => 'Encerrado',
    ];

    public function getTitle(): string
    {
        return 'Controle de Notas Fiscais';
    }

    public function mount(): void
    {
        $this->construtoraOptions = Construtora::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function filtrosModalAction(): Action
    {
        return Action::make('filtrosModal')
            ->label('Filtros')
            ->icon('heroicon-o-funnel')
            ->color('gray')
            ->modalHeading('Filtros')
            ->modalDescription(new HtmlString(
                '<button type="button" wire:click="limparFiltros" x-on:click="$dispatch(\'close-modal\', { id: $el.closest(\'[data-fi-modal-id]\').dataset.fiModalId })" class="gs-te-filter-reset-action">Limpar Filtros</button>',
            ))
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Fechar')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('gray'))
            ->modalCancelAction(false)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->extraModalWindowAttributes(['class' => 'gs-te-config-modal gs-te-config-modal--filters'])
            ->badge(fn (): ?int => $this->filtrosAtivos > 0 ? $this->filtrosAtivos : null)
            ->fillForm(fn (): array => [
                'filtroStatus' => $this->filtroStatus,
                'filtroStatusUnidade' => $this->filtroStatusUnidade,
                'filtroFornecedor' => $this->filtroFornecedor,
                'filtroInicioDe' => $this->filtroInicioDe,
                'filtroInicioAte' => $this->filtroInicioAte,
                'filtroFimDe' => $this->filtroFimDe,
                'filtroFimAte' => $this->filtroFimAte,
                'filtroCapexMin' => $this->filtroCapexMin,
                'filtroCapexMax' => $this->filtroCapexMax,
                'filtroValorMin' => $this->filtroValorMin,
                'filtroValorMax' => $this->filtroValorMax,
            ])
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Fieldset::make('Filtros')
                    ->columns(2)
                    ->extraAttributes(['class' => 'gs-te-filters-group'])
                    ->schema([
                        Select::make('filtroStatus')
                            ->label('Status do controle')
                            ->options($this->statusOptions)
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Status do controle')
                            ->extraFieldWrapperAttributes(['class' => 'gs-te-filter-row']),

                        Select::make('filtroStatusUnidade')
                            ->label('Status da unidade')
                            ->options($this->statusUnidadeOptions)
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Status da unidade')
                            ->extraFieldWrapperAttributes(['class' => 'gs-te-filter-row']),

                        Select::make('filtroFornecedor')
                            ->label('Fornecedor')
                            ->options(fn (): array => array_combine($this->construtoraOptions, $this->construtoraOptions))
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Fornecedor')
                            ->columnSpanFull()
                            ->extraFieldWrapperAttributes(['class' => 'gs-te-filter-row']),

                        Fieldset::make('Início obra')
                            ->columnSpanFull()
                            ->columns(2)
                            ->extraAttributes(['class' => 'gs-te-filter-date-range'])
                            ->schema([
                                DatePicker::make('filtroInicioDe')
                                    ->label('De')
                                    ->hiddenLabel()
                                    ->native()
                                    ->placeholder('dd/mm/aaaa'),
                                DatePicker::make('filtroInicioAte')
                                    ->label('Até')
                                    ->hiddenLabel()
                                    ->native()
                                    ->placeholder('dd/mm/aaaa'),
                            ]),

                        Fieldset::make('Fim obra')
                            ->columnSpanFull()
                            ->columns(2)
                            ->extraAttributes(['class' => 'gs-te-filter-date-range'])
                            ->schema([
                                DatePicker::make('filtroFimDe')
                                    ->label('De')
                                    ->hiddenLabel()
                                    ->native()
                                    ->placeholder('dd/mm/aaaa'),
                                DatePicker::make('filtroFimAte')
                                    ->label('Até')
                                    ->hiddenLabel()
                                    ->native()
                                    ->placeholder('dd/mm/aaaa'),
                            ]),

                        Fieldset::make('CAPEX (R$)')
                            ->columnSpanFull()
                            ->columns(2)
                            ->extraAttributes(['class' => 'gs-te-filter-date-range'])
                            ->schema([
                                TextInput::make('filtroCapexMin')
                                    ->label('Mín')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Mín'),
                                TextInput::make('filtroCapexMax')
                                    ->label('Máx')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Máx'),
                            ]),

                        Fieldset::make('Valor contratado (R$)')
                            ->columnSpanFull()
                            ->columns(2)
                            ->extraAttributes(['class' => 'gs-te-filter-date-range'])
                            ->schema([
                                TextInput::make('filtroValorMin')
                                    ->label('Mín')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Mín'),
                                TextInput::make('filtroValorMax')
                                    ->label('Máx')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Máx'),
                            ]),
                    ]),
            ]))
            ->action(function (array $data): void {
                $this->filtroStatus = (array) ($data['filtroStatus'] ?? []);
                $this->filtroStatusUnidade = (array) ($data['filtroStatusUnidade'] ?? []);
                $this->filtroFornecedor = (array) ($data['filtroFornecedor'] ?? []);
                $this->filtroInicioDe = $data['filtroInicioDe'] ?? null;
                $this->filtroInicioAte = $data['filtroInicioAte'] ?? null;
                $this->filtroFimDe = $data['filtroFimDe'] ?? null;
                $this->filtroFimAte = $data['filtroFimAte'] ?? null;
                $this->filtroCapexMin = $this->normalizarNumero($data['filtroCapexMin'] ?? null);
                $this->filtroCapexMax = $this->normalizarNumero($data['filtroCapexMax'] ?? null);
                $this->filtroValorMin = $this->normalizarNumero($data['filtroValorMin'] ?? null);
                $this->filtroValorMax = $this->normalizarNumero($data['filtroValorMax'] ?? null);

                $this->resetPage();
                $this->controlesExpandidos = [];
            });
    }

    private function normalizarNumero(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (string) $valor;
    }

    public function updatingBusca(): void
    {
        $this->resetPage();
        $this->controlesExpandidos = [];
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
        $this->controlesExpandidos = [];
    }

    public function limparFiltros(): void
    {
        $this->filtroStatus = [];
        $this->filtroStatusUnidade = [];
        $this->filtroFornecedor = [];
        $this->filtroInicioDe = null;
        $this->filtroInicioAte = null;
        $this->filtroFimDe = null;
        $this->filtroFimAte = null;
        $this->filtroCapexMin = null;
        $this->filtroCapexMax = null;
        $this->filtroValorMin = null;
        $this->filtroValorMax = null;
        $this->resetPage();
        $this->controlesExpandidos = [];
    }

    public function removerFiltro(string $chave): void
    {
        match ($chave) {
            'filtroStatus' => $this->filtroStatus = [],
            'filtroStatusUnidade' => $this->filtroStatusUnidade = [],
            'filtroFornecedor' => $this->filtroFornecedor = [],
            'filtroInicio' => [
                $this->filtroInicioDe = null,
                $this->filtroInicioAte = null,
            ],
            'filtroFim' => [
                $this->filtroFimDe = null,
                $this->filtroFimAte = null,
            ],
            'filtroCapex' => [
                $this->filtroCapexMin = null,
                $this->filtroCapexMax = null,
            ],
            'filtroValor' => [
                $this->filtroValorMin = null,
                $this->filtroValorMax = null,
            ],
            default => null,
        };

        $this->resetPage();
        $this->controlesExpandidos = [];
    }

    public function getFiltrosAtivosProperty(): int
    {
        $count = 0;
        if (! empty($this->filtroStatus)) {
            $count++;
        }
        if (! empty($this->filtroStatusUnidade)) {
            $count++;
        }
        if (! empty($this->filtroFornecedor)) {
            $count++;
        }
        if (! blank($this->filtroInicioDe) || ! blank($this->filtroInicioAte)) {
            $count++;
        }
        if (! blank($this->filtroFimDe) || ! blank($this->filtroFimAte)) {
            $count++;
        }
        if (! blank($this->filtroCapexMin) || ! blank($this->filtroCapexMax)) {
            $count++;
        }
        if (! blank($this->filtroValorMin) || ! blank($this->filtroValorMax)) {
            $count++;
        }

        return $count;
    }

    public function getControlesProperty(): LengthAwarePaginator
    {
        $query = ControleNotaFiscalResource::getEloquentQuery()
            ->with([
                'obra',
                'obra.projeto',
                'itens' => fn ($query) => $query
                    ->whereHas('autorizacaoServico', fn ($asQuery) => $asQuery->where('status', AsStatus::ENVIADA->value)),
            ]);

        if (! empty($this->filtroStatus)) {
            $query->whereIn('status', $this->filtroStatus);
        }

        if ($this->busca !== '') {
            $termo = '%'.$this->busca.'%';
            $query->where(function ($q) use ($termo): void {
                $q->whereHas('obra', function ($oq) use ($termo): void {
                    $oq->where('unidade', 'like', $termo)
                        ->orWhere('sigla', 'like', $termo)
                        ->orWhere('codigo', 'like', $termo);
                });
            });
        }

        if (! empty($this->filtroStatusUnidade)) {
            $statusUnidade = $this->filtroStatusUnidade;
            $query->whereHas('obra', function ($oq) use ($statusUnidade): void {
                $oq->whereIn('status', $statusUnidade);
            });
        }

        if (! blank($this->filtroInicioDe)) {
            $de = $this->filtroInicioDe;
            $query->whereHas('obra', function ($oq) use ($de): void {
                $oq->whereDate('inicio', '>=', $de);
            });
        }

        if (! blank($this->filtroInicioAte)) {
            $ate = $this->filtroInicioAte;
            $query->whereHas('obra', function ($oq) use ($ate): void {
                $oq->whereDate('inicio', '<=', $ate);
            });
        }

        if (! blank($this->filtroFimDe)) {
            $de = $this->filtroFimDe;
            $query->whereHas('obra', function ($oq) use ($de): void {
                $oq->whereDate('fim', '>=', $de);
            });
        }

        if (! blank($this->filtroFimAte)) {
            $ate = $this->filtroFimAte;
            $query->whereHas('obra', function ($oq) use ($ate): void {
                $oq->whereDate('fim', '<=', $ate);
            });
        }

        if (! blank($this->filtroCapexMin)) {
            $min = (float) $this->filtroCapexMin;
            $query->whereHas('obra', function ($oq) use ($min): void {
                $oq->where('capex', '>=', $min);
            });
        }

        if (! blank($this->filtroCapexMax)) {
            $max = (float) $this->filtroCapexMax;
            $query->whereHas('obra', function ($oq) use ($max): void {
                $oq->where('capex', '<=', $max);
            });
        }

        if (! empty($this->filtroFornecedor)) {
            $fornecedores = $this->filtroFornecedor;
            $query->whereHas('itens', function ($iq) use ($fornecedores): void {
                $iq->whereHas('autorizacaoServico')
                    ->whereIn('empresa', $fornecedores);
            });
        }

        if (! blank($this->filtroValorMin) || ! blank($this->filtroValorMax)) {
            $min = blank($this->filtroValorMin) ? null : (float) $this->filtroValorMin;
            $max = blank($this->filtroValorMax) ? null : (float) $this->filtroValorMax;

            $query->whereHas('itens', function ($iq) use ($min, $max): void {
                $iq->whereHas('autorizacaoServico');

                if ($min !== null) {
                    $iq->where('valor_global_a', '>=', $min);
                }
                if ($max !== null) {
                    $iq->where('valor_global_a', '<=', $max);
                }
            });
        }

        $query->orderBy('id', 'desc');

        return $query->paginate($this->porPagina);
    }

    public function toggleControle(int $controleId): void
    {
        if (in_array($controleId, $this->controlesExpandidos, true)) {
            $this->controlesExpandidos = array_values(array_diff($this->controlesExpandidos, [$controleId]));

            return;
        }

        $this->controlesExpandidos[] = $controleId;
    }

    public function urlEditarControle(int $controleId): string
    {
        return EditControleNotaFiscal::getUrl(['record' => $controleId]);
    }

    public function corStatus(?string $status): string
    {
        return match ($status) {
            'ativo' => 'ativo',
            'aguardando_construtora', 'aguardando_financeiro' => 'aguardando',
            'aprovado' => 'aprovado',
            'reprovado' => 'reprovado',
            'encerrado' => 'encerrado',
            default => 'ativo',
        };
    }

    public function corStatusObra(?string $status): string
    {
        return match ($status) {
            'Em processo' => 'info',
            'Obras' => 'warning',
            'Inaugurada' => 'success',
            'Cancelada', 'Deletar comercial' => 'danger',
            'Stand-by' => 'neutral',
            default => 'neutral',
        };
    }

    public function executarAcaoEmMassa(string $acao): void
    {
        if ($this->selecionadas === []) {
            Notification::make()
                ->title('Selecione ao menos um registro.')
                ->warning()
                ->send();

            return;
        }

        match ($acao) {
            'limpar_selecao' => $this->selecionadas = [],
            'exportar' => Notification::make()
                ->title('Exportação em breve')
                ->body(count($this->selecionadas).' selecionado(s).')
                ->info()
                ->send(),
            default => Notification::make()
                ->title('Ação não suportada')
                ->warning()
                ->send(),
        };
    }
}
