<?php

namespace App\Filament\Pages;

use App\Enums\TipoUnidade;
use App\Filament\Resources\Obras\Pages\ViewObra;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Models\Status;
use App\Models\User;
use App\Services\NumeroAsRetrofitService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use UnitEnum;

class ControlePedidosRetrofit extends Page
{
    use HasPageShield;
    use WithPagination;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Retrofit / Ampliação';

    protected static ?string $navigationLabel = 'Controle de Pedidos';

    protected static ?string $title = 'Controle de Pedidos — Retrofit / Ampliação';

    protected static ?string $slug = 'controle-pedidos-retrofit';

    protected string $view = 'filament.pages.controle-pedidos-retrofit';

    /** @var array<int, int> */
    public array $obrasExpandidas = [];

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

    /** @var array<int, string> */
    public array $escopoAsOptions = [];

    /** @var array<int, string> */
    public array $statusUnidadeOptions = [
        'Em processo' => 'Em processo',
        'Obras' => 'Obras',
        'Inaugurada' => 'Inaugurada',
        'Cancelada' => 'Cancelada',
        'Stand-by' => 'Stand-by',
        'Deletar comercial' => 'Deletar comercial',
    ];

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<string, string> */
    public array $statusColors = [
        'analisar' => 'slate',
        'cotacao' => 'pink',
        'as_enviada' => 'green',
        'entrega_programada' => 'orange',
        'em_execucao' => 'blue',
        'entregue' => 'teal',
        'verificar' => 'red',
    ];

    public int $zoom = 100;

    public function mount(): void
    {
        $this->carregarStatusOptions();

        $this->construtoraOptions = Construtora::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();

        $this->escopoAsOptions = AsEscopo::query()
            ->globais()
            ->orderBy('grupo')
            ->orderBy('numero_as')
            ->orderBy('escopo')
            ->get(['id', 'grupo', 'numero_as', 'escopo'])
            ->mapWithKeys(fn (AsEscopo $escopo): array => [
                $escopo->id => $this->formatarEscopoAsLabel($escopo),
            ])
            ->toArray();
    }

    private function carregarStatusOptions(): void
    {
        $this->statusOptions = Status::ativosPorContexto('retrofit')
            ->mapWithKeys(fn (Status $status): array => [
                $status->slug => $status->nome,
            ])
            ->toArray();
    }

    public function updatingBusca(): void
    {
        $this->resetPage();
        $this->obrasExpandidas = [];
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
        $this->obrasExpandidas = [];
    }

    public bool $abrirModalAdicionarStatus = false;

    public string $novoStatusNome = '';

    public string $novoStatusCor = '#10b981';

    #[On('openAdicionarStatusModal')]
    public function abrirModal(): void
    {
        $this->abrirModalAdicionarStatus = true;
    }

    public function submeterNovoStatus(): void
    {
        if (blank($this->novoStatusNome) || blank($this->novoStatusCor)) {
            Notification::make()
                ->title('Preencha todos os campos')
                ->warning()
                ->send();

            return;
        }

        $this->criarNovoStatus($this->novoStatusNome, $this->novoStatusCor);
        $this->abrirModalAdicionarStatus = false;
        $this->novoStatusNome = '';
        $this->novoStatusCor = '#10b981';
    }

    public function deletarStatus(string $statusKey): void
    {
        $status = Status::porSlug('retrofit', $statusKey);

        if (! $status) {
            Notification::make()
                ->title('Status não encontrado')
                ->warning()
                ->send();

            return;
        }

        if ($status->is_protected) {
            Notification::make()
                ->title('Status protegido')
                ->body('Este status é necessário ao sistema e não pode ser deletado.')
                ->warning()
                ->send();

            return;
        }

        // Verificar se há itens usando este status
        $itensComStatus = ControleNotaFiscalItem::where('status_retrofit', $statusKey)->count();

        if ($itensComStatus > 0) {
            Notification::make()
                ->title('Não é possível deletar')
                ->body("Existem {$itensComStatus} item(ns) usando este status.")
                ->warning()
                ->send();

            return;
        }

        $status->delete();
        $this->carregarStatusOptions();

        Notification::make()
            ->title('Status deletado com sucesso')
            ->success()
            ->send();
    }

    public function criarNovoStatus(string $nome, string $cor): void
    {
        $nomeNormalizado = strtoupper(trim($nome));
        $slug = strtolower(str_replace(' ', '_', $nomeNormalizado));

        $jaExiste = Status::query()
            ->where('contexto', 'retrofit')
            ->where(function ($q) use ($nomeNormalizado, $slug): void {
                $q->where('nome', $nomeNormalizado)->orWhere('slug', $slug);
            })
            ->exists();

        if ($jaExiste) {
            Notification::make()
                ->title('Status já existe')
                ->warning()
                ->send();

            return;
        }

        $maxOrdem = Status::query()->where('contexto', 'retrofit')->max('ordem') ?? 0;

        Status::create([
            'contexto' => 'retrofit',
            'slug' => $slug,
            'nome' => $nomeNormalizado,
            'cor' => $cor,
            'ordem' => $maxOrdem + 1,
            'is_active' => true,
            'is_protected' => false,
        ]);

        $this->carregarStatusOptions();

        Notification::make()
            ->title('Status criado com sucesso')
            ->success()
            ->send();
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
                            ->label('Status do subelemento')
                            ->options($this->statusOptions)
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Status do subelemento')
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
                $this->obrasExpandidas = [];
            });
    }

    private function normalizarNumero(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (string) $valor;
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
        $this->obrasExpandidas = [];
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
        $this->obrasExpandidas = [];
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

    public function getObrasProperty(): LengthAwarePaginator
    {
        $statusFiltro = $this->filtroStatus;

        $query = $this->obrasRetrofitQuery()
            ->with([
                'projeto:id,codigo,sigla',
                'controlesNotaFiscal' => function ($q) use ($statusFiltro) {
                    $q->with([
                        'itens' => function ($iq) use ($statusFiltro) {
                            if (! empty($statusFiltro)) {
                                $iq->whereIn('status_retrofit', $statusFiltro);
                            }
                        },
                        'itens.asEscopo:id,grupo,numero_as,escopo',
                    ]);
                },
            ])
            ->whereNotNull('unidade')
            ->where('unidade', '!=', '')
            ->orderBy('unidade');

        if ($this->busca !== '') {
            $termo = '%'.$this->busca.'%';
            $query->where(function ($q) use ($termo): void {
                $q->where('unidade', 'like', $termo)
                    ->orWhereHas('projeto', function ($pq) use ($termo): void {
                        $pq->where('sigla', 'like', $termo)
                            ->orWhere('nova_sigla', 'like', $termo);
                    });
            });
        }

        if (! empty($this->filtroStatusUnidade)) {
            $query->whereIn('status', $this->filtroStatusUnidade);
        }

        if (! blank($this->filtroInicioDe)) {
            $query->whereDate('inicio', '>=', $this->filtroInicioDe);
        }

        if (! blank($this->filtroInicioAte)) {
            $query->whereDate('inicio', '<=', $this->filtroInicioAte);
        }

        if (! blank($this->filtroFimDe)) {
            $query->whereDate('fim', '>=', $this->filtroFimDe);
        }

        if (! blank($this->filtroFimAte)) {
            $query->whereDate('fim', '<=', $this->filtroFimAte);
        }

        if (! blank($this->filtroCapexMin)) {
            $query->where('capex', '>=', (float) $this->filtroCapexMin);
        }

        if (! blank($this->filtroCapexMax)) {
            $query->where('capex', '<=', (float) $this->filtroCapexMax);
        }

        if (! empty($this->filtroFornecedor)) {
            $fornecedores = $this->filtroFornecedor;
            $query->whereHas('controlesNotaFiscal.itens', function ($iq) use ($fornecedores): void {
                $iq->whereIn('empresa', $fornecedores);
            });
        }

        if (! blank($this->filtroValorMin) || ! blank($this->filtroValorMax)) {
            $min = blank($this->filtroValorMin) ? null : (float) $this->filtroValorMin;
            $max = blank($this->filtroValorMax) ? null : (float) $this->filtroValorMax;

            $query->whereHas('controlesNotaFiscal', function ($cq) use ($min, $max): void {
                $cq->whereHas('itens', function ($iq) use ($min, $max): void {
                    if ($min !== null) {
                        $iq->where('valor_global_a', '>=', $min);
                    }
                    if ($max !== null) {
                        $iq->where('valor_global_a', '<=', $max);
                    }
                });
            });
        }

        return $query->paginate($this->porPagina);
    }

    protected function obrasRetrofitQuery(): Builder
    {
        return Obras::query()
            ->whereRaw("JSON_CONTAINS(COALESCE(tipos_unidade, '[]'), '\"RETROFIT\"')");
    }

    public function toggleObra(int $obraId): void
    {
        if (in_array($obraId, $this->obrasExpandidas, true)) {
            $this->obrasExpandidas = array_values(array_diff($this->obrasExpandidas, [$obraId]));

            return;
        }

        $this->obrasExpandidas[] = $obraId;
    }

    public function atualizarItem(int $itemId, string $campo, mixed $valor): void
    {
        $camposPermitidos = ['empresa', 'valor_global_a', 'data_entrega', 'escopo', 'escopo_complementar', 'quantidade', 'observacoes', 'percentual_faturamento_mao_obra', 'percentual_faturamento_material'];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        if ($campo === 'valor_global_a') {
            $valor = $this->parseMoedaBr($valor);
            if ($valor !== null && $valor < 0) {
                $valor = 0;
            }
        }

        if ($campo === 'data_entrega') {
            $valor = $valor === '' || $valor === null ? null : $valor;
        }

        if ($campo === 'empresa') {
            $valor = $valor === '' ? null : $valor;
        }

        if ($campo === 'escopo') {
            $valor = $valor === '' || $valor === null ? null : $valor;
        }

        if ($campo === 'escopo_complementar') {
            $valor = $valor === '' || $valor === null ? null : $valor;
        }

        if ($campo === 'quantidade') {
            $valor = $valor === '' || $valor === null ? null : (float) $valor;
            if ($valor !== null && $valor < 0) {
                $valor = 0;
            }
        }

        if ($campo === 'observacoes') {
            $valor = $valor === '' || $valor === null ? null : $valor;
        }

        if ($campo === 'percentual_faturamento_mao_obra' || $campo === 'percentual_faturamento_material') {
            $valor = $valor === '' || $valor === null ? null : (float) $valor;
            if ($valor !== null && $valor < 0) {
                $valor = 0;
            }
            if ($valor !== null && $valor > 100) {
                $valor = 100;
            }
        }

        $item->update([$campo => $valor]);

        Notification::make()
            ->title('Item atualizado')
            ->success()
            ->send();
    }

    public function atualizarStatusItem(int $itemId, string $novoStatus): void
    {
        $statusExiste = Status::query()
            ->where('contexto', 'retrofit')
            ->where('slug', $novoStatus)
            ->exists();

        if (! $statusExiste) {
            return;
        }

        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        $item->update(['status_retrofit' => $novoStatus]);

        Notification::make()
            ->title('Status atualizado')
            ->success()
            ->send();
    }

    public function atualizarMaoDeObraItem(int $itemId, mixed $valor): void
    {
        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        $percentual = blank($valor) ? null : (float) $valor;

        if ($percentual !== null && ($percentual < 0 || $percentual > 100)) {
            return;
        }

        $item->update(['mao_de_obra_percentual' => $percentual]);

        Notification::make()
            ->title('% Mão obra atualizado')
            ->success()
            ->send();
    }

    public function atualizarMaterialItem(int $itemId, mixed $valor): void
    {
        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        $percentual = blank($valor) ? null : (float) $valor;

        if ($percentual !== null && ($percentual < 0 || $percentual > 100)) {
            return;
        }

        $item->update(['material_percentual' => $percentual]);

        Notification::make()
            ->title('% Material atualizado')
            ->success()
            ->send();
    }

    public function atualizarValorItem(int $itemId, mixed $valor): void
    {
        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        $novoValor = $this->parseMoedaBr($valor);

        if ($novoValor !== null && $novoValor < 0) {
            return;
        }

        $item->update(['valor_global' => $novoValor]);

        Notification::make()
            ->title('Valor global atualizado')
            ->success()
            ->send();
    }

    public function excluirItemSubelemento(int $itemId): void
    {
        $item = ControleNotaFiscalItem::with('controleNotaFiscal.obra')->find($itemId);

        if (! $item) {
            return;
        }

        $item->delete();

        $this->selecionadas = array_values(array_filter(
            $this->selecionadas,
            fn (string $selecionada): bool => $selecionada !== 'item-'.$itemId,
        ));

        Notification::make()
            ->title('Subelemento excluído')
            ->success()
            ->send();
    }

    public function liberarItemParaFornecedor(int $itemId): void
    {
        $item = ControleNotaFiscalItem::with('controleNotaFiscal.obra')->find($itemId);

        if (! $item) {
            return;
        }

        if (filled($item->liberado_para_fornecedor_at)) {
            Notification::make()
                ->title('Este subelemento já foi liberado para o fornecedor.')
                ->warning()
                ->send();

            return;
        }

        if (blank($item->empresa)) {
            Notification::make()
                ->title('Selecione um fornecedor antes de liberar.')
                ->warning()
                ->send();

            return;
        }

        $construtora = Construtora::query()
            ->with('users')
            ->where('nome', trim((string) $item->empresa))
            ->first();

        if (! $construtora) {
            Notification::make()
                ->title('Fornecedor não encontrado.')
                ->warning()
                ->send();

            return;
        }

        $item->update([
            'liberado_para_fornecedor_at' => now(),
        ]);

        $item->refresh();

        $rotuloItem = collect([
            filled($item->grupo) ? $item->grupo : null,
            filled($item->numero_as) ? $item->numero_as : null,
        ])->filter()->implode(' - ');

        if ($rotuloItem === '') {
            $rotuloItem = 'Subelemento #'.$item->id;
        }

        $obraUnidade = (string) ($item->controleNotaFiscal?->obra?->unidade ?? $item->controleNotaFiscal?->unidade ?? 'obra');

        $usuariosFornecedor = User::where('construtoras_id', $construtora->id)->get();

        foreach ($usuariosFornecedor as $usuario) {
            Notification::make()
                ->title('Item liberado para fornecedor')
                ->body('Foi liberado o item '.$rotuloItem.' da unidade '.$obraUnidade.'. É necessário realizar a emissão da Nota Fiscal.')
                ->success()
                ->sendToDatabase($usuario);
        }

        Notification::make()
            ->title('Liberado para o fornecedor')
            ->body('Os usuários vinculados ao fornecedor foram notificados.')
            ->success()
            ->send();
    }

    public function criarControlePedidoRetrofitAction(): Action
    {
        return Action::make('criarControlePedidoRetrofit')
            ->label('Adicionar controle')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalHeading('Adicionar controle')
            ->modalDescription('Selecione uma unidade com a tag RETROFIT.')
            ->modalSubmitActionLabel('Adicionar controle')
            ->modalCancelActionLabel('Cancelar')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('obra_id')
                    ->label('Unidade')
                    ->options(function (): array {
                        return $this->obrasRetrofitQuery()
                            ->with('projeto:id,sigla')
                            ->orderBy('unidade')
                            ->get(['id', 'unidade', 'projeto_id', 'tipos_unidade'])
                            ->mapWithKeys(function (Obras $obra): array {
                                $rotulo = trim((string) $obra->unidade);
                                $sigla = trim((string) $obra->sigla);
                                $tipos = collect($obra->tipos_unidade ?? [])
                                    ->map(fn ($tipo) => trim((string) $tipo))
                                    ->filter(fn (string $tipo) => $tipo !== '')
                                    ->implode(', ');

                                if ($sigla !== '') {
                                    $rotulo .= $rotulo !== '' ? ' - '.$sigla : $sigla;
                                }

                                if ($tipos !== '') {
                                    $rotulo .= $rotulo !== '' ? ' - '.$tipos : $tipos;
                                }

                                return [$obra->id => $rotulo !== '' ? $rotulo : 'Obra #'.$obra->id];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
            ]))
            ->action(function (array $data): void {
                $obraId = (int) ($data['obra_id'] ?? 0);

                if ($obraId <= 0) {
                    return;
                }

                $obra = Obras::find($obraId);

                if (! $obra) {
                    Notification::make()
                        ->title('Unidade não encontrada')
                        ->warning()
                        ->send();

                    return;
                }

                // Verifica se já existe controle RETROFIT para esta obra
                $controleExistente = ControleNotaFiscal::where('obra_id', $obraId)
                    ->where('tipo_unidade', TipoUnidade::RETROFIT->value)
                    ->first();

                if ($controleExistente) {
                    // Se existe, apenas expande na tabela
                    if (! in_array($obraId, $this->obrasExpandidas, true)) {
                        $this->obrasExpandidas[] = $obraId;
                    }

                    Notification::make()
                        ->title('Controle já existe')
                        ->body('Expandindo na tabela')
                        ->info()
                        ->send();
                } else {
                    // Se não existe, cria e expande
                    $this->adicionarSubelemento($obraId);

                    Notification::make()
                        ->title('Controle criado com sucesso')
                        ->success()
                        ->send();
                }
            });
    }

    public function corStatus(string $status): ?string
    {
        $registro = Status::porSlug('retrofit', $status);

        return $registro?->cor ?? '#6b7280';
    }

    public function estiloStatus(string $status): string
    {
        $cor = $this->corStatus($status);

        return "background-color: {$cor};";
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

    private function parseMoedaBr(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $limpo = preg_replace('/[^0-9,.\-]/', '', (string) $valor);

        if ($limpo === '' || $limpo === '-') {
            return null;
        }

        if (str_contains($limpo, ',')) {
            $limpo = str_replace('.', '', $limpo);
            $limpo = str_replace(',', '.', $limpo);
        }

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    private function formatarEscopoAsLabel(AsEscopo $escopo): string
    {
        return trim((string) $escopo->escopo) ?: '—';
    }

    public function atualizarEscopoAsItem(int $itemId, mixed $asEscopoId): void
    {
        $item = ControleNotaFiscalItem::with('controleNotaFiscal')->find($itemId);

        if (! $item) {
            return;
        }

        if (blank($asEscopoId)) {
            $item->update([
                'as_escopo_id' => null,
                'grupo' => null,
                'numero_as' => null,
                'numero_complemento' => null,
                'escopo' => null,
                'escopo_complementar' => null,
            ]);

            Notification::make()
                ->title('Escopo AS removido')
                ->success()
                ->send();

            return;
        }

        $escopo = AsEscopo::query()->find((int) $asEscopoId);

        if (! $escopo) {
            return;
        }

        $controle = $item->controleNotaFiscal;

        if (! $controle || blank($controle->obra_id)) {
            return;
        }

        $numeroComplemento = $this->gerarProximoComplementoParaEscopo((int) $controle->obra_id, (int) $escopo->id);

        $item->update([
            'as_escopo_id' => $escopo->id,
            'grupo' => $escopo->grupo,
            'numero_as' => $escopo->numero_as,
            'numero_complemento' => $numeroComplemento,
            'escopo' => $escopo->escopo,
            'escopo_complementar' => null,
        ]);

        // Criar ou atualizar registro em autorizacao_servicos
        $obra = Obras::find($controle->obra_id);
        if ($obra) {
            $numeroAs = $this->gerarNumeroAsRetrofit($obra, $controle, (int) $escopo->id, $item->empresa);

            AutorizacaoServico::updateOrCreate(
                [
                    'obra_id' => $controle->obra_id,
                    'controle_nota_fiscal_item_id' => $item->id,
                ],
                [
                    'as_escopo_id' => $escopo->id,
                    'numero_complemento' => $numeroComplemento,
                    'numero_as' => $numeroAs,
                ]
            );

            $construtora = Construtora::query()
                ->where('nome', trim((string) $item->empresa))
                ->first();

            $rotuloEscopo = trim((string) $escopo->escopo) ?: '—';
            $obraUnidade = (string) $obra->unidade;

            if ($construtora) {
                $usuariosFornecedor = User::where('construtoras_id', $construtora->id)->get();

                foreach ($usuariosFornecedor as $usuario) {
                    Notification::make()
                        ->title('Nova AS gerada')
                        ->body('Foi gerado o escopo '.$rotuloEscopo.' na unidade '.$obraUnidade.'.')
                        ->icon('heroicon-o-check-circle')
                        ->success()
                        ->sendToDatabase($usuario);
                }
            }

            Notification::make()
                ->title('Nova AS gerada')
                ->body('Foi gerado o escopo '.$rotuloEscopo.' na unidade '.$obraUnidade.'.')
                ->icon('heroicon-o-check-circle')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Escopo AS atualizado')
                ->success()
                ->send();
        }
    }

    public function atualizarEscopoComplementarItem(int $itemId, mixed $valor): void
    {
        $item = ControleNotaFiscalItem::find($itemId);

        if (! $item) {
            return;
        }

        $escopoComplementar = blank($valor) ? null : $valor;

        $item->update(['escopo_complementar' => $escopoComplementar]);

        Notification::make()
            ->title('Escopo complementar atualizado')
            ->success()
            ->send();
    }

    protected function gerarProximoComplementoParaEscopo(int $obraId, int $escopoId): ?string
    {
        $complementosAs = AutorizacaoServico::query()
            ->where('obra_id', $obraId)
            ->where('as_escopo_id', $escopoId)
            ->pluck('numero_complemento')
            ->all();

        $complementosItens = ControleNotaFiscalItem::query()
            ->whereHas('controleNotaFiscal', function (Builder $query) use ($obraId): void {
                $query->where('obra_id', $obraId);
            })
            ->where('as_escopo_id', $escopoId)
            ->pluck('numero_complemento')
            ->all();

        $temRegistros = count($complementosAs) + count($complementosItens) > 0;

        if (! $temRegistros) {
            return null;
        }

        $complementosExistentes = array_values(array_filter(
            array_merge($complementosAs, $complementosItens),
            fn ($valor): bool => filled($valor),
        ));

        $proximoNumero = 1;

        while (in_array('C'.$proximoNumero, $complementosExistentes, true)) {
            $proximoNumero++;
        }

        return 'C'.$proximoNumero;
    }

    public function adicionarSubelemento(int $obraId): void
    {
        $obra = Obras::find($obraId);

        if (! $obra) {
            return;
        }

        $controle = ControleNotaFiscal::firstOrCreate(
            [
                'obra_id' => $obra->id,
                'tipo_unidade' => TipoUnidade::RETROFIT->value,
            ],
            [
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
            ],
        );

        $proximoSort = (int) ($controle->itens()->max('sort_order') ?? 0) + 1;

        ControleNotaFiscalItem::create([
            'controle_nota_fiscal_id' => $controle->id,
            'escopo' => null,
            'sort_order' => $proximoSort,
        ]);

        if (! in_array($obra->id, $this->obrasExpandidas, true)) {
            $this->obrasExpandidas[] = $obra->id;
        }

        Notification::make()
            ->title('Subelemento adicionado')
            ->success()
            ->send();
    }

    private function gerarNumeroAsRetrofit(Obras $obra, ControleNotaFiscal $controle, int $asEscopoId, ?string $fornecedor = null): string
    {
        $service = new NumeroAsRetrofitService;

        return $service->gerar($obra, $controle, $asEscopoId, $fornecedor);
    }

    public function urlViewObra(int $obraId): string
    {
        return ViewObra::getUrl(['record' => $obraId]);
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

    public function aumentarZoom(): void
    {
        if ($this->zoom < 150) {
            $this->zoom += 10;
        }
    }

    public function diminuirZoom(): void
    {
        if ($this->zoom > 70) {
            $this->zoom -= 10;
        }
    }

    public function proximaPagina(): void
    {
        if ($this->getPaginaAtualProperty() < $this->getTotalPaginasProperty()) {
            $this->gotoPage($this->getPaginaAtualProperty() + 1);
        }
    }

    public function paginaAnterior(): void
    {
        if ($this->getPaginaAtualProperty() > 1) {
            $this->gotoPage($this->getPaginaAtualProperty() - 1);
        }
    }

    public function irParaPagina(mixed $pagina): void
    {
        $pagina = (int) $pagina;
        if ($pagina >= 1 && $pagina <= $this->getTotalPaginasProperty()) {
            $this->gotoPage($pagina);
        }
    }

    public function getTotalObrasProperty(): int
    {
        return $this->obrasRetrofitQuery()->count();
    }

    public function getTotalPaginasProperty(): int
    {
        $total = $this->getTotalObrasProperty();

        return (int) ceil($total / $this->porPagina);
    }

    public function getPaginaAtualProperty(): int
    {
        return (int) request()->get('page', 1);
    }
}
