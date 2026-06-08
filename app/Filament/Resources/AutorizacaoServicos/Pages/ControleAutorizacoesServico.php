<?php

namespace App\Filament\Resources\AutorizacaoServicos\Pages;

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Exports\ElaboracaoAditivoPlanilhaExport;
use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Filament\Resources\Obras\Pages\ViewObra;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use App\Services\AsaFluxoService;
use App\Services\AutorizacaoServicoFluxoService;
use App\Services\ControleAutorizacaoServicoItemService;
use App\Services\ControleNotaFiscal\PreencheEscoposPadraoControleNotaFiscal;
use App\Services\SincronizarSimuladorOiControleAsService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class ControleAutorizacoesServico extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;
    use WithPagination;

    protected static string $resource = AutorizacaoServicoResource::class;

    protected static ?string $title = 'Controle de AS';

    protected string $view = 'filament.resources.autorizacao-servicos.pages.controle-autorizacoes-servico';

    public string $busca = '';

    public int $porPagina = 25;

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

    /** @var array<int, int> */
    public array $obrasExpandidas = [];

    /** @var array<int, array<string, mixed>> */
    public array $resumos = [];

    /** @var array<int, array<int, ControleNotaFiscalItem>> */
    protected array $itensPrincipaisPorObra = [];

    /** @var array<int, array<string, mixed>> */
    public array $itens = [];

    /** @var array{item_id?: int, dados?: array<string, mixed>} */
    public array $criacaoAsPendente = [];

    public ?int $gerarAsModalItemId = null;

    public ?int $gerarAsModalAuxiliarId = null;

    public bool $gerarAsModalEdicao = false;

    /** @var array<string, mixed> */
    public array $gerarAsModalDados = [];

    /** @var array<int, array{parcela: string, percentual: string, valor: string, observacao: string}> */
    public array $gerarAsParcelas = [];

    public string $gerarAsModalModo = 'criar';

    public float $gerarAsValorFechado = 0.0;

    public string $gerarAsDesconto = '0,00';

    public ?string $gerarAsDataInicio = null;

    public ?string $gerarAsDataTermino = null;

    public ?string $gerarAsDataEntrega = null;

    public ?string $gerarAsDescricaoServicoPdf = null;

    /** @var array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}> */
    public array $gerarAsDescricaoItens = [];

    public ?array $gerarAsPdfFormData = [];

    public bool $gerarAsPermitirValoresZerados = false;

    /** @var array<string, string> */
    public array $emailOptions = [];

    /** @var array<int, string> */
    public array $asEscopoOptions = [];

    /** @var array<int, array{grupo: ?string, numero_as: ?string, escopo: ?string}> */
    public array $asEscopoMetadados = [];

    /** @var array<int, string> */
    public array $construtoraOptions = [];

    /** @var array<string, int> */
    public array $construtoraIdsPorNome = [];

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
        'rascunho' => 'Rascunho',
        'aguardando_construtora' => 'Aguardando fornecedor',
        'aguardando_financeiro' => 'Aguardando financeiro',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado',
    ];

    protected $listeners = [
        'confirmar-criacao-as-valores-zerados' => 'confirmarCriacaoAsValoresZerados',
    ];

    public function mount(): void
    {
        $this->carregarAsEscopos();
        $this->carregarConstrutoras();
    }

    protected function carregarAsEscopos(): void
    {
        $asEscopos = AsEscopo::query()
            ->globais()
            ->where('is_active', true)
            ->orderBy('grupo')
            ->orderBy('numero_as')
            ->get();

        $this->asEscopoOptions = $asEscopos
            ->mapWithKeys(fn (AsEscopo $escopo): array => [
                $escopo->id => $escopo->escopo,
            ])
            ->toArray();

        $this->asEscopoMetadados = $asEscopos
            ->mapWithKeys(fn (AsEscopo $escopo): array => [
                $escopo->id => [
                    'grupo' => $escopo->grupo,
                    'numero_as' => $escopo->numero_as,
                    'escopo' => $escopo->escopo,
                    'percentual_faturamento_mao_obra_default' => $escopo->percentual_faturamento_mao_obra_default,
                    'percentual_faturamento_material_default' => $escopo->percentual_faturamento_material_default,
                ],
            ])
            ->toArray();
    }

    protected function registrarAsEscopoMetadados(?AsEscopo $escopo): void
    {
        if (! $escopo) {
            return;
        }

        $this->asEscopoMetadados[$escopo->id] = [
            'grupo' => $escopo->grupo,
            'numero_as' => $escopo->numero_as,
            'escopo' => $escopo->escopo,
            'percentual_faturamento_mao_obra_default' => $escopo->percentual_faturamento_mao_obra_default,
            'percentual_faturamento_material_default' => $escopo->percentual_faturamento_material_default,
        ];
    }

    protected function carregarConstrutoras(): void
    {
        $construtoras = Construtora::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'email']);

        $this->construtoraOptions = $construtoras
            ->pluck('nome', 'id')
            ->toArray();

        $this->construtoraIdsPorNome = $construtoras
            ->pluck('id', 'nome')
            ->toArray();

        $emailOptions = [];

        foreach ($construtoras as $construtora) {
            $emails = app(AutorizacaoServicoFluxoService::class)
                ->normalizarEmails(preg_split('/[;,\s]+/', (string) $construtora->email) ?: []);

            foreach ($emails as $email) {
                $emailOptions[$email] = "{$construtora->nome} <{$email}>";
            }
        }

        User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['name', 'email'])
            ->each(function (User $user) use (&$emailOptions): void {
                $emails = app(AutorizacaoServicoFluxoService::class)
                    ->normalizarEmails([(string) $user->email]);

                foreach ($emails as $email) {
                    $emailOptions[$email] = "{$user->name} <{$email}>";
                }
            });

        $this->emailOptions = $emailOptions;
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

                        Fieldset::make('Valor fechado (R$)')
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

    public function enviarAsAction(): Action
    {
        return Action::make('enviarAs')
            ->label('Enviar AS')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->modalHeading('Enviar AS por e-mail')
            ->modalWidth('2xl')
            ->schema($this->schemaEnviarAs())
            ->fillForm(fn (array $arguments, AutorizacaoServicoFluxoService $service): array => $this->dadosPadraoEnvioAs(
                (int) ($arguments['itemId'] ?? 0),
                $service,
            ))
            ->action(function (array $arguments, array $data, AutorizacaoServicoFluxoService $service): void {
                $this->enviarAsPeloControle(
                    (int) ($arguments['itemId'] ?? 0),
                    $data,
                    $service,
                );
            });
    }

    public function enviarAsaAction(): Action
    {
        return Action::make('enviarAsa')
            ->label('Enviar AS')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->modalHeading('Enviar AS por e-mail')
            ->modalWidth('2xl')
            ->schema($this->schemaEnviarAsa())
            ->fillForm(fn (array $arguments, AutorizacaoServicoFluxoService $service): array => $this->dadosPadraoEnvioAsa(
                (int) ($arguments['auxiliarId'] ?? 0),
                $service,
            ))
            ->action(function (array $arguments, array $data, AsaFluxoService $asaService): void {
                $this->enviarAsaPeloControle(
                    (int) ($arguments['auxiliarId'] ?? 0),
                    $data,
                    $asaService,
                );
            });
    }

    public function cancelarAsAction(): Action
    {
        return Action::make('cancelarAs')
            ->label('Cancelar AS')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Cancelar AS e notificar por e-mail')
            ->modalDescription('A AS será cancelada e os destinatários selecionados serão notificados por e-mail.')
            ->modalSubmitActionLabel('Cancelar AS')
            ->modalWidth('2xl')
            ->schema($this->schemaEnviarAs())
            ->fillForm(fn (array $arguments, AutorizacaoServicoFluxoService $service): array => $this->dadosPadraoEnvioAs(
                (int) ($arguments['itemId'] ?? 0),
                $service,
            ))
            ->action(function (array $arguments, array $data, AutorizacaoServicoFluxoService $service): void {
                $this->cancelarAsPeloControle(
                    (int) ($arguments['itemId'] ?? 0),
                    $data,
                    $service,
                );
            });
    }

    public function cancelarAsaAction(): Action
    {
        return Action::make('cancelarAsa')
            ->label('Cancelar AS')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Cancelar AS e notificar por e-mail')
            ->modalDescription('A AS adicional será cancelada e os destinatários selecionados serão notificados por e-mail.')
            ->modalSubmitActionLabel('Cancelar AS')
            ->modalWidth('2xl')
            ->schema($this->schemaEnviarAsa())
            ->fillForm(fn (array $arguments, AutorizacaoServicoFluxoService $service): array => $this->dadosPadraoEnvioAsa(
                (int) ($arguments['auxiliarId'] ?? 0),
                $service,
            ))
            ->action(function (array $arguments, array $data, AsaFluxoService $asaService): void {
                $this->cancelarAsaPeloControle(
                    (int) ($arguments['auxiliarId'] ?? 0),
                    $data,
                    $asaService,
                );
            });
    }

    public function sincronizarSimuladorOiItemAction(): Action
    {
        return Action::make('sincronizarSimuladorOiItem')
            ->label('Simulador OI')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->modalHeading('Importar valores da Simulação OI')
            ->modalDescription('A simulação aprovada da obra será usada automaticamente e pode sobrescrever valores personalizados digitados nesta linha.')
            ->modalSubmitActionLabel('Importar valores')
            ->action(function (array $arguments, SincronizarSimuladorOiControleAsService $service): void {
                $this->sincronizarItemComSimuladorOiAprovado(
                    itemId: (int) ($arguments['itemId'] ?? 0),
                    service: $service,
                );
            });
    }

    public function sincronizarSimuladorOiObraAction(): Action
    {
        return Action::make('sincronizarSimuladorOiObra')
            ->label('Simulador OI')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->modalHeading('Importar valores da Simulação OI')
            ->modalDescription('A simulação aprovada da obra será usada automaticamente e pode sobrescrever valores personalizados digitados nos escopos.')
            ->modalSubmitActionLabel('Importar valores')
            ->action(function (array $arguments, SincronizarSimuladorOiControleAsService $service): void {
                $this->sincronizarObraComSimuladorOiAprovado(
                    obraId: (int) ($arguments['obraId'] ?? 0),
                    service: $service,
                );
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
        $this->itensPrincipaisPorObra = [];

        $query = Obras::query()
            ->with([
                'projeto',
                'controleAutorizacaoServicoResumo',
                'controlesNotaFiscal' => fn ($query) => $query
                    ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                    ->latest('id')
                    ->limit(1),
                'controlesNotaFiscal.auxiliares' => fn ($query) => $query
                    ->with('notasFiscais')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'controlesNotaFiscal.itens' => fn ($query) => $query
                    ->with([
                        'asEscopo',
                        'autorizacaoServico',
                        'notasFiscais',
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->whereNotNull('unidade')
            ->where('unidade', '!=', '')
            ->when($this->busca !== '', function (Builder $query): void {
                $termo = '%'.$this->busca.'%';

                $query->where(function (Builder $builder) use ($termo): void {
                    $builder->where('codigo', 'like', $termo)
                        ->orWhere('unidade', 'like', $termo)
                        ->orWhereHas('projeto', function (Builder $projetoQuery) use ($termo): void {
                            $projetoQuery->where('sigla', 'like', $termo)
                                ->orWhere('nova_sigla', 'like', $termo);
                        });
                });
            });

        $this->aplicarFiltros($query);

        $obras = $query
            ->orderBy('unidade')
            ->paginate($this->porPagina);

        $this->carregarNotasAprovadasDasObrasExpandidas($obras);

        return $obras;
    }

    protected function carregarNotasAprovadasDasObrasExpandidas(LengthAwarePaginator $obras): void
    {
        if ($this->obrasExpandidas === []) {
            return;
        }

        $itens = $obras
            ->getCollection()
            ->whereIn('id', $this->obrasExpandidas)
            ->flatMap(fn (Obras $obra): array => $this->itensPrincipais($obra))
            ->values();

        if ($itens->isEmpty()) {
            return;
        }

        (new EloquentCollection($itens->all()))
            ->load([
                'notasFiscais' => fn ($query) => $query
                    ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value),
            ]);
    }

    protected function aplicarFiltros(Builder $query): void
    {
        if (! empty($this->filtroStatus)) {
            $status = $this->filtroStatus;
            $query->whereHas('controlesNotaFiscal', function (Builder $controleQuery) use ($status): void {
                $controleQuery
                    ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                    ->whereIn('status', $status);
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
            $min = (float) $this->filtroCapexMin;

            $query->whereHas('projeto', function (Builder $projetoQuery) use ($min): void {
                $projetoQuery->where('capex_aprovado_diretoria_valor', '>=', $min);
            });
        }

        if (! blank($this->filtroCapexMax)) {
            $max = (float) $this->filtroCapexMax;

            $query->whereHas('projeto', function (Builder $projetoQuery) use ($max): void {
                $projetoQuery->where('capex_aprovado_diretoria_valor', '<=', $max);
            });
        }

        if (! empty($this->filtroFornecedor)) {
            $fornecedores = $this->filtroFornecedor;
            $query->whereHas('controlesNotaFiscal.itens', function (Builder $itemQuery) use ($fornecedores): void {
                $itemQuery
                    ->whereIn('empresa', $fornecedores)
                    ->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery->where('tipo_unidade', TipoUnidade::EXPANSAO->value));
            });
        }

        if (! blank($this->filtroValorMin) || ! blank($this->filtroValorMax)) {
            $min = blank($this->filtroValorMin) ? null : (float) $this->filtroValorMin;
            $max = blank($this->filtroValorMax) ? null : (float) $this->filtroValorMax;

            $query->whereHas('controlesNotaFiscal.itens', function (Builder $itemQuery) use ($min, $max): void {
                $itemQuery->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery->where('tipo_unidade', TipoUnidade::EXPANSAO->value));

                if ($min !== null) {
                    $itemQuery->where('valor_global_a', '>=', $min);
                }
                if ($max !== null) {
                    $itemQuery->where('valor_global_a', '<=', $max);
                }
            });
        }
    }

    public function toggleObra(int $obraId): void
    {
        if (in_array($obraId, $this->obrasExpandidas, true)) {
            $this->obrasExpandidas = array_values(array_diff($this->obrasExpandidas, [$obraId]));

            return;
        }

        $this->obrasExpandidas[] = $obraId;
    }

    public function adicionarLinha(int $obraId): void
    {
        $this->authorizeFluxoUpdate();

        $controle = ControleNotaFiscal::query()
            ->where('obra_id', $obraId)
            ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->latest('id')
            ->first();

        if (! $controle) {
            $obra = Obras::query()
                ->with('projeto')
                ->findOrFail($obraId);

            $controle = $obra->controlesNotaFiscal()->create([
                'status' => ControleNotaFiscal::STATUS_ATIVO,
                'tipo_unidade' => TipoUnidade::EXPANSAO->value,
                'data_base' => now()->toDateString(),
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
                'endereco' => $obra->endereco,
            ]);

            app(PreencheEscoposPadraoControleNotaFiscal::class)->handle($controle);

            $this->obrasExpandidas[] = $obraId;
            $this->obrasExpandidas = array_values(array_unique($this->obrasExpandidas));

            $this->notificar(Notification::make()
                ->title('Controle de AS criado')
                ->body('Os escopos padrão foram carregados para a unidade.')
                ->success());

            return;
        }

        $this->criarLinhaRascunho($controle, $obraId);
    }

    protected function criarLinhaRascunho(ControleNotaFiscal $controle, int $obraId): void
    {
        $sortOrder = ((int) $controle->itens()->max('sort_order')) + 1;

        $controle->itens()->create([
            'percentual_total' => 100,
            'percentual_faturamento_mao_obra' => 60,
            'percentual_faturamento_material' => 40,
            'valor_estimado_as' => 0,
            'valor_global_a' => 0,
            'total_medicao_a_menos_b' => 0,
            'valor_acumulado_medido' => 0,
            'saldo' => 0,
            'sort_order' => $sortOrder,
        ]);

        $this->obrasExpandidas[] = $obraId;
        $this->obrasExpandidas = array_values(array_unique($this->obrasExpandidas));

        $this->notificar(Notification::make()
            ->title('Linha de AS adicionada')
            ->success());
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function salvarItemComDados(int $itemId, array $dados, ControleAutorizacaoServicoItemService $itemService): void
    {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with(['asEscopo', 'autorizacaoServico.construtora'])
            ->find($itemId);

        if (! $item) {
            return;
        }

        if ($this->asImutavel($item->autorizacaoServico)) {
            $this->notificar(Notification::make()
                ->title('AS enviada ou cancelada não pode ser editada')
                ->danger());

            return;
        }

        if ($dados !== []) {
            unset($dados['valor_fechado']);
            $this->itens[$itemId] = array_replace($this->itens[$itemId] ?? [], $dados);
        }

        $this->persistirItem($item, $itemService);

        $this->notificar(Notification::make()
            ->title('Linha salva')
            ->success());
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $linhas
     */
    public function salvarItensObraComDados(int $obraId, array $linhas, ControleAutorizacaoServicoItemService $itemService): void
    {
        $this->authorizeFluxoUpdate();

        $idsRecebidos = [];

        foreach ($linhas as $itemId => $dados) {
            if (is_array($dados)) {
                $itemId = (int) $itemId;
                $idsRecebidos[] = $itemId;
                unset($dados['valor_fechado']);
                $this->itens[$itemId] = array_replace($this->itens[$itemId] ?? [], $dados);
            }
        }

        $itens = ControleNotaFiscalItem::query()
            ->with(['asEscopo', 'autorizacaoServico.construtora'])
            ->whereKey($idsRecebidos)
            ->whereHas('controleNotaFiscal', function (Builder $query) use ($obraId): void {
                $query
                    ->where('obra_id', $obraId)
                    ->where('tipo_unidade', TipoUnidade::EXPANSAO->value);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($itens->isEmpty()) {
            $this->notificar(Notification::make()
                ->title('Nenhuma linha de AS carregada para salvar')
                ->body('Expanda a obra e altere as linhas antes de usar o salvamento em lote.')
                ->warning());

            return;
        }

        $salvas = 0;
        $bloqueadas = 0;

        foreach ($itens as $item) {
            if ($this->asImutavel($item->autorizacaoServico)) {
                $bloqueadas++;

                continue;
            }

            $this->persistirItem($item, $itemService);
            $salvas++;
        }

        $this->notificar(Notification::make()
            ->title($salvas === 1 ? '1 linha de AS salva' : "{$salvas} linhas de AS salvas")
            ->body($bloqueadas > 0 ? "{$bloqueadas} linha(s) com AS enviada ou cancelada foram ignorada(s)." : null)
            ->success());
    }

    protected function persistirItem(ControleNotaFiscalItem $item, ControleAutorizacaoServicoItemService $itemService): void
    {
        $itemId = $item->id;
        $dados = $this->itens[$itemId] ?? [];

        $itemService->persistir($item, $dados);
        $item->refresh()->load(['asEscopo', 'autorizacaoServico']);
        $this->registrarAsEscopoMetadados($item->asEscopo);

        $this->itens[$itemId] = array_replace($this->itens[$itemId] ?? [], [
            'as_escopo_id' => $item->as_escopo_id,
            'construtora_id' => $item->autorizacaoServico?->construtora_id
                ?? ($this->construtoraIdsPorNome[(string) $item->empresa] ?? null),
            'numero_complemento' => $item->autorizacaoServico?->numero_complemento ?: $item->numero_complemento,
            'valor_estimado' => $item->valor_estimado_as ?? 0,
            'valor_estimado_as_simulador' => $item->valor_estimado_as_simulador,
            'valor_estimado_as_editado_manualmente' => $item->valor_estimado_as_editado_manualmente,
            'valor_fechado' => $item->valor_global_a ?? 0,
            'percentual_faturamento_mao_obra' => $item->percentual_faturamento_mao_obra ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_mao_obra_default', 60),
            'percentual_faturamento_material' => $item->percentual_faturamento_material ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_material_default', 40),
        ]);
    }

    /**
     * @return array{as_escopo_id: ?int, numero_complemento: string, escopo_complementar: string}
     */
    public function atualizarEscopoItemComComplemento(
        int $itemId,
        mixed $asEscopoId,
        ControleAutorizacaoServicoItemService $itemService,
    ): array {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with(['asEscopo', 'autorizacaoServico.construtora', 'controleNotaFiscal'])
            ->find($itemId);

        if (! $item) {
            return [
                'as_escopo_id' => null,
                'numero_complemento' => '',
                'escopo_complementar' => '',
            ];
        }

        if ($item->autorizacaoServico || $this->asImutavel($item->autorizacaoServico)) {
            return [
                'as_escopo_id' => $item->as_escopo_id,
                'numero_complemento' => (string) ($item->numero_complemento ?? ''),
                'escopo_complementar' => (string) ($item->escopo_complementar ?? ''),
            ];
        }

        $this->itens[$itemId] = array_replace($this->itens[$itemId] ?? [], [
            'as_escopo_id' => filled($asEscopoId) ? (int) $asEscopoId : null,
        ]);

        $this->persistirItem($item, $itemService);
        $item->refresh();

        return [
            'as_escopo_id' => $item->as_escopo_id,
            'numero_complemento' => (string) ($item->numero_complemento ?? ''),
            'escopo_complementar' => (string) ($item->escopo_complementar ?? ''),
        ];
    }

    public function removerLinhaVazia(int $itemId): void
    {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with('notasFiscais')
            ->find($itemId);

        if (! $item) {
            return;
        }

        if (! $this->linhaRascunhoRemovivel($item)) {
            $this->notificar(Notification::make()
                ->title('Apenas linhas em rascunho podem ser removidas')
                ->danger());

            return;
        }

        $item->delete();
        unset($this->itens[$itemId]);

        $this->notificar(Notification::make()
            ->title('Linha removida')
            ->success());
    }

    /**
     * @param  array<int, mixed>  $itemIds
     */
    public function removerLinhasRascunhoObra(int $obraId, array $itemIds): void
    {
        $this->authorizeFluxoUpdate();

        $ids = collect($itemIds)
            ->map(fn (mixed $itemId): int => (int) $itemId)
            ->filter(fn (int $itemId): bool => $itemId > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->notificar(Notification::make()
                ->title('Nenhuma linha selecionada')
                ->warning());

            return;
        }

        $itens = ControleNotaFiscalItem::query()
            ->with('autorizacaoServico')
            ->whereKey($ids)
            ->whereHas('controleNotaFiscal', function (Builder $query) use ($obraId): void {
                $query
                    ->where('obra_id', $obraId)
                    ->where('tipo_unidade', TipoUnidade::EXPANSAO->value);
            })
            ->get();

        $removidas = 0;
        $bloqueadas = 0;

        foreach ($itens as $item) {
            if (! $this->linhaRascunhoRemovivel($item)) {
                $bloqueadas++;

                continue;
            }

            $item->delete();
            unset($this->itens[$item->id]);
            $removidas++;
        }

        if ($removidas === 0) {
            $this->notificar(Notification::make()
                ->title('Nenhuma linha em rascunho removível foi apagada')
                ->body($bloqueadas > 0 ? "{$bloqueadas} linha(s) vinculada(s) a AS foram ignorada(s)." : null)
                ->warning());

            return;
        }

        $this->notificar(Notification::make()
            ->title($removidas === 1 ? '1 linha em rascunho apagada' : "{$removidas} linhas em rascunho apagadas")
            ->body($bloqueadas > 0 ? "{$bloqueadas} linha(s) vinculada(s) a AS foram ignorada(s)." : null)
            ->success());
    }

    public function sincronizarSimuladorOi(int $obraId, SincronizarSimuladorOiControleAsService $service): void
    {
        $this->authorizeFluxoUpdate();

        $obra = Obras::query()
            ->with('projeto')
            ->findOrFail($obraId);

        $simulacao = $service->encontrarAprovadaParaObra($obra);

        if (! $simulacao) {
            $this->notificar(Notification::make()
                ->title('Nenhuma Simulação OI aprovada encontrada')
                ->warning());

            return;
        }

        $resultado = $service->sincronizar($obra, $simulacao);

        $this->invalidarEstadoObra($obraId);

        $this->notificarResultadoImportacaoOi($resultado, incluiCriados: true);
    }

    protected function sincronizarItemComSimuladorOiAprovado(
        int $itemId,
        SincronizarSimuladorOiControleAsService $service,
    ): void {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with(['autorizacaoServico', 'controleNotaFiscal.obra.projeto'])
            ->findOrFail($itemId);

        $obra = $item->controleNotaFiscal?->obra;
        $simulacao = $obra instanceof Obras
            ? $service->encontrarAprovadaParaObra($obra)
            : null;

        if (! $simulacao instanceof CapexSimulacao) {
            $this->notificar(Notification::make()
                ->title('Nenhuma Simulação OI aprovada encontrada')
                ->warning());

            return;
        }

        $resultado = $service->sincronizarItem($item, $simulacao);

        $this->invalidarEstadoObra(
            obraId: $item->controleNotaFiscal?->obra_id,
            itemId: $itemId,
        );

        $this->notificarResultadoImportacaoOi($resultado, incluiCriados: false);
    }

    protected function sincronizarObraComSimuladorOiAprovado(
        int $obraId,
        SincronizarSimuladorOiControleAsService $service,
    ): void {
        $this->authorizeFluxoUpdate();

        $obra = Obras::query()
            ->with('projeto')
            ->findOrFail($obraId);

        $simulacao = $service->encontrarAprovadaParaObra($obra);

        if (! $simulacao instanceof CapexSimulacao) {
            $this->notificar(Notification::make()
                ->title('Nenhuma Simulação OI aprovada encontrada')
                ->warning());

            return;
        }

        $resultado = $service->sincronizar($obra, $simulacao);

        $this->invalidarEstadoObra($obraId);

        $this->notificarResultadoImportacaoOi($resultado, incluiCriados: true);
    }

    /**
     * @param  array{preenchidos: int, criados: int, ignorados_edicao_manual: int, conflitos: array<int, string>}  $resultado
     */
    protected function notificarResultadoImportacaoOi(array $resultado, bool $incluiCriados): void
    {
        $temAvisos = $resultado['conflitos'] !== [];

        $notification = Notification::make()
            ->title($temAvisos ? 'Importação da OI concluída com avisos' : 'Importação da OI concluída')
            ->body($this->mensagemResultadoImportacaoOi($resultado, $incluiCriados));

        if ($temAvisos) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $this->notificar($notification);
    }

    /**
     * @param  array{preenchidos: int, criados: int, ignorados_edicao_manual: int, conflitos: array<int, string>}  $resultado
     */
    protected function mensagemResultadoImportacaoOi(array $resultado, bool $incluiCriados): string
    {
        $partes = [
            $this->pluralizarQuantidade(
                $resultado['preenchidos'],
                'linha atualizada com valor da OI',
                'linhas atualizadas com valor da OI',
            ),
        ];

        if ($incluiCriados) {
            $partes[] = $this->pluralizarQuantidade(
                $resultado['criados'],
                'linha criada no Controle AS',
                'linhas criadas no Controle AS',
            );
        }

        if ($resultado['ignorados_edicao_manual'] > 0) {
            $partes[] = $this->pluralizarQuantidade(
                $resultado['ignorados_edicao_manual'],
                'linha ignorada por edição manual',
                'linhas ignoradas por edição manual',
            );
        }

        $mensagem = implode('; ', $partes).'.';

        if ($resultado['conflitos'] !== []) {
            $mensagem .= ' Avisos: '.implode(' ', $resultado['conflitos']);
        }

        return $mensagem;
    }

    protected function pluralizarQuantidade(int $quantidade, string $singular, string $plural): string
    {
        return $quantidade.' '.($quantidade === 1 ? $singular : $plural);
    }

    protected function invalidarEstadoObra(?int $obraId, ?int $itemId = null): void
    {
        $this->carregarAsEscopos();

        if ($itemId !== null) {
            unset($this->itens[$itemId]);
        }

        if ($obraId === null) {
            $this->resumos = [];
            $this->itensPrincipaisPorObra = [];

            return;
        }

        if ($itemId === null) {
            ControleNotaFiscalItem::query()
                ->whereHas('controleNotaFiscal', function (Builder $query) use ($obraId): void {
                    $query
                        ->where('obra_id', $obraId)
                        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value);
                })
                ->pluck('id')
                ->each(function (int $id): void {
                    unset($this->itens[$id]);
                });
        }

        unset($this->resumos[$obraId], $this->itensPrincipaisPorObra[$obraId]);
    }

    /**
     * @param  array<string, mixed>  $estadoItem
     */
    public function valorEstimadoForaSimuladorOi(array $estadoItem): bool
    {
        $valorSimulador = $estadoItem['valor_estimado_as_simulador'] ?? null;

        if ($valorSimulador === null || $valorSimulador === '') {
            return false;
        }

        $valorEstimado = $estadoItem['valor_estimado'] ?? 0;

        return round($this->normalizarMoedaParaFloat($valorEstimado), 2) !== round($this->normalizarMoedaParaFloat($valorSimulador), 2);
    }

    protected function normalizarMoedaParaFloat(mixed $valor): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = preg_replace('/[^\d,.-]/', '', (string) $valor) ?? '';

        if (str_contains($normalizado, ',')) {
            $normalizado = str_replace('.', '', $normalizado);
            $normalizado = str_replace(',', '.', $normalizado);
        }

        return is_numeric($normalizado) ? (float) $normalizado : 0.0;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criarAsComDados(
        int $itemId,
        array $dados,
        AutorizacaoServicoFluxoService $service,
        ControleAutorizacaoServicoItemService $itemService,
    ): void {
        $this->authorizeFluxoCreate();

        $item = ControleNotaFiscalItem::query()->with('autorizacaoServico')->findOrFail($itemId);

        if ($item->autorizacaoServico) {
            $this->notificar(Notification::make()
                ->title('AS já criada')
                ->warning());

            return;
        }

        if ($item->autorizacaoServico) {
            $this->notificar(Notification::make()
                ->title('AS já criada')
                ->warning());

            return;
        }

        if ($mensagem = $this->mensagemDadosObrigatoriosCriacaoAs($item, $dados)) {
            $this->notificar(Notification::make()
                ->title('AS não criada')
                ->body($mensagem)
                ->danger());

            return;
        }

        if (! $this->valorEstimadoValido($item, $dados)) {
            $this->notificar(Notification::make()
                ->title('AS não criada')
                ->body('Defina o valor estimado antes de criar a AS.')
                ->danger());

            return;
        }

        $this->abrirModalGerarAs($itemId, $dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function editarAsComDados(int $itemId, array $dados): void
    {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()->with('autorizacaoServico')->findOrFail($itemId);

        if (! $item->autorizacaoServico) {
            $this->notificar(Notification::make()
                ->title('AS ainda não criada')
                ->warning());

            return;
        }

        if ($this->asImutavel($item->autorizacaoServico)) {
            $this->notificar(Notification::make()
                ->title('AS enviada ou cancelada não pode ser editada')
                ->danger());

            return;
        }

        $this->abrirModalGerarAs($itemId, $dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    protected function mensagemDadosObrigatoriosCriacaoAs(ControleNotaFiscalItem $item, array $dados): ?string
    {
        $estado = array_replace($this->itens[$item->id] ?? [], $dados);
        $camposPendentes = [];

        if (blank($estado['as_escopo_id'] ?? null) && blank($item->as_escopo_id)) {
            $camposPendentes[] = 'escopo';
        }

        if (blank($estado['construtora_id'] ?? null) && blank($item->empresa)) {
            $camposPendentes[] = 'empresa';
        }

        if (! $this->valorEstimadoValido($item, $dados)) {
            $camposPendentes[] = 'valor estimado';
        }

        if ($camposPendentes === []) {
            return null;
        }

        return 'Defina '.implode(' e ', $camposPendentes).' antes de criar a AS.';
    }

    public function confirmarCriacaoAsValoresZerados(): void
    {
        $itemId = (int) ($this->criacaoAsPendente['item_id'] ?? 0);
        $dados = $this->criacaoAsPendente['dados'] ?? [];
        $this->criacaoAsPendente = [];

        if ($itemId <= 0 || ! is_array($dados)) {
            return;
        }

        $item = ControleNotaFiscalItem::query()->with('autorizacaoServico')->find($itemId);

        if (! $item || $item->autorizacaoServico) {
            return;
        }

        if ($this->mensagemDadosObrigatoriosCriacaoAs($item, $dados)) {
            return;
        }

        $this->abrirModalGerarAs($itemId, $dados, permitirValoresZerados: true);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    protected function executarCriacaoAs(
        ControleNotaFiscalItem $item,
        array $dados,
        AutorizacaoServicoFluxoService $service,
        ControleAutorizacaoServicoItemService $itemService,
        bool $permitirValoresZerados = false,
        ?array $parcelamento = null,
    ): ?AutorizacaoServico {
        $modoEdicao = $item->autorizacaoServico()->exists();

        try {
            $autorizacaoServico = DB::transaction(function () use ($dados, $item, $itemService, $parcelamento, $permitirValoresZerados, $service): AutorizacaoServico {
                $asExistente = $item->autorizacaoServico()->first();

                if ($dados !== []) {
                    $this->itens[$item->id] = array_replace($this->itens[$item->id] ?? [], $dados);
                }

                $this->persistirItem($item, $itemService);
                $item->refresh();

                if (! $item->as_escopo_id) {
                    throw new DomainException('Selecione um escopo antes de criar a AS');
                }

                if ($asExistente instanceof AutorizacaoServico) {
                    if ($this->asImutavel($asExistente)) {
                        throw new DomainException('AS enviada ou cancelada não pode ser editada.');
                    }

                    $valorEstimado = $itemService->parseMoedaBr($dados['valor_estimado'] ?? null)
                        ?? (float) ($item->valor_estimado_as ?? $asExistente->valor_estimado ?? 0);
                    $valorInicial = $itemService->parseMoedaBr($dados['_valor_inicial_calculado_as'] ?? null)
                        ?? (float) ($asExistente->valor_inicial ?? $valorEstimado);
                    $valorFechado = $itemService->parseMoedaBr($dados['_valor_fechado_calculado_as'] ?? null)
                        ?? (float) ($item->valor_global_a ?? $asExistente->valor ?? 0);

                    $asExistente->forceFill([
                        'valor_estimado' => $valorEstimado,
                        'valor_inicial' => $valorInicial,
                        'valor' => $valorFechado,
                        'controle_nota_fiscal_item_id' => $item->id,
                    ])->save();

                    return $service->gerar(
                        $asExistente->refresh(),
                        $permitirValoresZerados,
                        $parcelamento,
                        $this->datasGeracaoAs(),
                    );
                }

                $autorizacaoServico = $service->criarEGerarParaItem(
                    $item,
                    Auth::user(),
                    $permitirValoresZerados,
                    $parcelamento,
                    $this->datasGeracaoAs(),
                );

                $valorInicialNovo = $itemService->parseMoedaBr($dados['_valor_inicial_calculado_as'] ?? null);
                $valorFechadoNovo = $itemService->parseMoedaBr($dados['_valor_fechado_calculado_as'] ?? null);

                if ($valorInicialNovo !== null || $valorFechadoNovo !== null) {
                    $autorizacaoServico->forceFill(array_filter([
                        'valor_inicial' => $valorInicialNovo,
                        'valor' => $valorFechadoNovo,
                    ], fn ($v): bool => $v !== null))->save();
                    $autorizacaoServico->refresh();
                }

                return $autorizacaoServico;
            });
        } catch (DomainException $exception) {
            Log::warning('AS não criada pelo controle.', [
                'item_id' => $item->id,
                'message' => $exception->getMessage(),
            ]);

            $this->notificar(Notification::make()
                ->title('AS não criada')
                ->body($exception->getMessage())
                ->danger());

            return null;
        }

        $this->notificar(Notification::make()
            ->title($modoEdicao ? 'AS atualizada' : 'AS criada')
            ->body($this->podeAtualizarFluxo() ? 'O PDF foi gerado. Revise o envio antes de encaminhar ao fornecedor.' : 'O PDF foi gerado.')
            ->success());

        $this->dispatch('as-linha-salva', itemId: $item->id, estado: $this->estadoLinhaAtualizado($item->id));

        return $autorizacaoServico;
    }

    /**
     * @return array<string, mixed>
     */
    protected function estadoLinhaAtualizado(int $itemId): array
    {
        $item = ControleNotaFiscalItem::query()
            ->with(['autorizacaoServico.construtora', 'asEscopo', 'notas'])
            ->find($itemId);

        if (! $item instanceof ControleNotaFiscalItem) {
            return [];
        }

        $valorFechado = (float) ($item->autorizacaoServico?->valor ?? $item->valor_global_a ?? 0);
        $faturado = $this->totalNotasAprovadasItem($item);
        $saldo = max($valorFechado - $faturado, 0);
        $construtoraId = $item->autorizacaoServico?->construtora_id
            ?? ($this->construtoraIdsPorNome[(string) $item->empresa] ?? null);
        $estado = [
            'as_escopo_id' => $item->as_escopo_id,
            'construtora_id' => $construtoraId,
            'numero_complemento' => $item->autorizacaoServico?->numero_complemento ?: $item->numero_complemento,
            'escopo_complementar' => $item->escopo_complementar ?? '',
            'valor_estimado' => $this->formatMoeda($item->autorizacaoServico?->valor_estimado ?? $item->valor_estimado_as ?? 0),
            'valor_estimado_as_simulador' => $item->valor_estimado_as_simulador,
            'valor_estimado_as_editado_manualmente' => $item->valor_estimado_as_editado_manualmente,
            'valor_fechado' => $this->formatMoeda($valorFechado),
            'percentual_faturamento_mao_obra' => $this->formatNumeroBr($item->percentual_faturamento_mao_obra ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_mao_obra_default', 60)),
            'percentual_faturamento_material' => $this->formatNumeroBr($item->percentual_faturamento_material ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_material_default', 40)),
            'faturado' => $this->formatMoeda($faturado),
            'saldo' => $this->formatMoeda($saldo),
            'percentual_saldo' => $this->formatPercentual($valorFechado > 0 ? round(($saldo / $valorFechado) * 100, 2) : 0),
        ];

        $this->itens[$itemId] = array_replace($this->itens[$itemId] ?? [], $estado);

        return $estado;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function estadosLinhasAtualizadasObra(int $obraId): array
    {
        return ControleNotaFiscalItem::query()
            ->whereHas('controleNotaFiscal', function (Builder $query) use ($obraId): void {
                $query->where('obra_id', $obraId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->mapWithKeys(fn (int $itemId): array => [$itemId => $this->estadoLinhaAtualizado($itemId)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    protected function dadosCriacaoAsComValorFechadoCalculado(array $dados): array
    {
        if (filled($dados['valor_fechado'] ?? null)) {
            return $dados;
        }

        $desconto = $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0;
        $dados['valor_fechado'] = $this->formatMoeda($this->valorLiquido($this->gerarAsValorFechado, $desconto));

        return $dados;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function abrirModalGerarAs(int $itemId, array $dados = [], bool $permitirValoresZerados = false): void
    {
        $item = ControleNotaFiscalItem::query()
            ->with(['autorizacaoServico', 'controleNotaFiscal.obra'])
            ->findOrFail($itemId);

        $autorizacaoServico = $item->autorizacaoServico;

        if ($autorizacaoServico) {
            $this->authorizeFluxoUpdate();
        } else {
            $this->authorizeFluxoCreate();
        }

        if ($this->asImutavel($autorizacaoServico)) {
            $this->notificar(Notification::make()
                ->title('AS enviada ou cancelada não pode ser editada')
                ->danger());

            return;
        }

        $estado = array_replace($this->itens[$itemId] ?? [], $dados);
        $this->itens[$itemId] = $estado;

        if (! $this->valorEstimadoValido($item, $dados)) {
            $this->notificar(Notification::make()
                ->title('AS não criada')
                ->body('Defina o valor estimado antes de criar a AS.')
                ->danger());

            return;
        }

        $valorEstimado = $this->parseMoedaBr($estado['valor_estimado'] ?? null)
            ?? (float) ($autorizacaoServico?->valor_estimado ?? $item->valor_estimado_as ?? 0);
        $desconto = (float) ($autorizacaoServico?->desconto_autorizacao_servico ?? 0);
        $valorLiquido = $this->valorLiquido($valorEstimado, $desconto);
        $parcelas = is_array($autorizacaoServico?->parcelamento_autorizacao_servico)
            ? $this->normalizarEstadoParcelasGerarAs($autorizacaoServico->parcelamento_autorizacao_servico)
            : [];

        $this->resetValidation(['gerarAsParcelas']);
        $this->gerarAsModalItemId = $itemId;
        $this->gerarAsModalEdicao = $autorizacaoServico instanceof AutorizacaoServico;
        $this->gerarAsModalDados = $estado;
        $this->gerarAsPermitirValoresZerados = $permitirValoresZerados;
        $this->gerarAsValorFechado = $valorEstimado;
        $this->gerarAsParcelas = $parcelas !== [] ? $parcelas : $this->parcelasPadrao($valorLiquido);
        $this->gerarAsDesconto = $this->formatMoeda($desconto);
        $this->gerarAsDataInicio = $autorizacaoServico?->data_inicio_servico?->toDateString();
        $this->gerarAsDataTermino = $autorizacaoServico?->data_termino_servico?->toDateString();
        $this->gerarAsDataEntrega = $autorizacaoServico?->data_entrega_material?->toDateString();
        $this->gerarAsDescricaoServicoPdf = filled($autorizacaoServico?->descricao_servico_pdf)
            ? (string) $autorizacaoServico->descricao_servico_pdf
            : $this->descricaoPadraoGeracaoAs($item, $estado);
        $this->gerarAsDescricaoItens = is_array($autorizacaoServico?->itens_descricao_servico_pdf)
            ? $this->normalizarEstadoDescricaoItensGerarAs($autorizacaoServico->itens_descricao_servico_pdf)
            : $this->descricaoItensPadraoGeracaoAs($item, $estado);
        $valorInicialInicial = (float) ($autorizacaoServico?->valor_inicial ?? $valorEstimado);
        $this->gerarAsValorFechado = $valorInicialInicial;
        $valorLiquido = $this->valorLiquido($valorInicialInicial, $desconto);
        $this->gerarAsPdfFormData = [
            'data_inicio_servico' => $this->gerarAsDataInicio,
            'data_termino_servico' => $this->gerarAsDataTermino,
            'data_entrega_material' => $this->gerarAsDataEntrega,
            'valor_inicial' => $this->formatMoeda($valorInicialInicial),
            'desconto_autorizacao_servico' => $this->gerarAsDesconto,
            'total_apos_desconto' => $this->formatMoeda($valorLiquido),
            'parcelamento' => $this->gerarAsParcelas,
            'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
            'descricao_arquivo' => $this->estadoUploadArquivos($this->gerarAsDescricaoItens[0]['descricao_arquivo'] ?? []),
            'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
            'anexos_autorizacao_servico' => $this->estadoUploadArquivos($autorizacaoServico?->anexos_autorizacao_servico ?? []),
        ];
        $this->gerarAsDatasForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsValoresParcelamentoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsDescricaoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsAnexosForm->fill($this->gerarAsPdfFormData);
    }

    public function abrirModalEditarPdfAs(int $itemId): void
    {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with(['autorizacaoServico', 'controleNotaFiscal.obra'])
            ->findOrFail($itemId);

        $autorizacaoServico = $item->autorizacaoServico;

        if (! $autorizacaoServico || $autorizacaoServico->status !== AsStatus::CRIADA) {
            $this->notificar(Notification::make()
                ->title('PDF não pode ser editado')
                ->body('A edição do PDF está disponível apenas para AS criada.')
                ->danger());

            return;
        }

        $estado = $this->prepararItem($item);
        $valorEstimado = $this->parseMoedaBr($estado['valor_estimado'] ?? null)
            ?? (float) ($autorizacaoServico->valor_estimado ?? $item->valor_estimado_as ?? 0);
        $desconto = (float) ($autorizacaoServico->desconto_autorizacao_servico ?? 0);
        $valorLiquido = $this->valorLiquido($valorEstimado, $desconto);
        $descricaoItem = $this->descricaoItemPdfExistente($autorizacaoServico, $item, $estado);

        $this->resetValidation(['gerarAsParcelas']);
        $this->itens[$itemId] = $estado;
        $this->gerarAsModalModo = 'editar_pdf';
        $this->gerarAsModalItemId = $itemId;
        $this->gerarAsModalEdicao = true;
        $this->gerarAsModalDados = $estado;
        $this->gerarAsPermitirValoresZerados = false;
        $this->gerarAsValorFechado = $valorEstimado;
        $this->gerarAsDesconto = $this->formatMoeda($desconto);
        $this->gerarAsDataInicio = $autorizacaoServico->data_inicio_servico?->toDateString();
        $this->gerarAsDataTermino = $autorizacaoServico->data_termino_servico?->toDateString();
        $this->gerarAsDataEntrega = $autorizacaoServico->data_entrega_material?->toDateString();
        $this->gerarAsDescricaoItens = [$descricaoItem];
        $this->gerarAsDescricaoServicoPdf = $descricaoItem['descricao'];
        $valorInicialInicial = (float) ($autorizacaoServico->valor_inicial ?? $valorEstimado);
        $this->gerarAsValorFechado = $valorInicialInicial;
        $valorLiquido = $this->valorLiquido($valorInicialInicial, $desconto);
        $this->gerarAsParcelas = $this->parcelasPdfExistente($autorizacaoServico, $valorLiquido);
        $this->gerarAsPdfFormData = [
            'data_inicio_servico' => $this->gerarAsDataInicio,
            'data_termino_servico' => $this->gerarAsDataTermino,
            'data_entrega_material' => $this->gerarAsDataEntrega,
            'valor_inicial' => $this->formatMoeda($valorInicialInicial),
            'desconto_autorizacao_servico' => $this->gerarAsDesconto,
            'total_apos_desconto' => $this->formatMoeda($valorLiquido),
            'parcelamento' => $this->gerarAsParcelas,
            'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
            'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
            'descricao_tipo' => $descricaoItem['descricao_tipo'],
            'descricao_arquivo' => $this->estadoUploadArquivos($descricaoItem['descricao_arquivo']),
            'anexos_autorizacao_servico' => $this->estadoUploadArquivos($autorizacaoServico->anexos_autorizacao_servico ?? []),
        ];
        $this->gerarAsDatasForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsValoresParcelamentoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsDescricaoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsAnexosForm->fill($this->gerarAsPdfFormData);
    }

    public function fecharModalGerarAs(): void
    {
        $this->resetValidation(['gerarAsParcelas']);
        $this->gerarAsModalModo = 'criar';
        $this->gerarAsModalItemId = null;
        $this->gerarAsModalAuxiliarId = null;
        $this->gerarAsModalEdicao = false;
        $this->gerarAsModalDados = [];
        $this->gerarAsParcelas = [];
        $this->gerarAsValorFechado = 0.0;
        $this->gerarAsDesconto = '0,00';
        $this->gerarAsDataInicio = null;
        $this->gerarAsDataTermino = null;
        $this->gerarAsDataEntrega = null;
        $this->gerarAsDescricaoServicoPdf = null;
        $this->gerarAsDescricaoItens = [];
        $this->gerarAsPdfFormData = [];
        $this->gerarAsPermitirValoresZerados = false;
    }

    public function abrirModalGerarAsAsa(int $auxiliarId): void
    {
        $this->authorizeFluxoUpdate();

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal')
            ->findOrFail($auxiliarId);

        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            $this->notificar(Notification::make()
                ->title('ASA não encontrada')
                ->danger());

            return;
        }

        $valorTotal = (float) $asa->valor_total;
        $desconto = max((float) ($asa->as_desconto ?? 0), 0.0);
        $valorLiquido = $this->valorLiquido($valorTotal, $desconto);
        $isEdicao = $asa->status === AsStatus::CRIADA;

        $parcelas = is_array($asa->as_parcelamento)
            ? $this->normalizarEstadoParcelasGerarAs($asa->as_parcelamento)
            : [];
        $descricaoItens = is_array($asa->as_itens_descricao_pdf) && $asa->as_itens_descricao_pdf !== []
            ? $this->normalizarEstadoDescricaoItensGerarAs($asa->as_itens_descricao_pdf)
            : [];
        $descricaoServico = filled($asa->as_descricao_pdf)
            ? (string) $asa->as_descricao_pdf
            : ((string) ($asa->descricao ?? '') ?: 'EXECUÇÃO DE OBRA CIVIL - ADICIONAL');

        if ($descricaoItens === []) {
            $descricaoItens = [[
                'descricao_tipo' => 'texto',
                'descricao' => $descricaoServico,
                'descricao_arquivo' => [],
            ]];
        }

        $this->resetValidation(['gerarAsParcelas']);
        $this->gerarAsModalAuxiliarId = $auxiliarId;
        $this->gerarAsModalItemId = null;
        $this->gerarAsModalModo = 'criar';
        $this->gerarAsModalEdicao = $isEdicao;
        $this->gerarAsModalDados = [];
        $this->gerarAsPermitirValoresZerados = false;
        $this->gerarAsValorFechado = $valorTotal;
        $this->gerarAsDesconto = $this->formatMoeda($desconto);
        $this->gerarAsParcelas = $parcelas !== [] ? $parcelas : $this->parcelasPadrao($valorLiquido);
        $this->gerarAsDataInicio = $asa->as_data_inicio?->toDateString();
        $this->gerarAsDataTermino = $asa->as_data_termino?->toDateString();
        $this->gerarAsDataEntrega = $asa->as_data_entrega?->toDateString();
        $this->gerarAsDescricaoServicoPdf = $descricaoServico;
        $this->gerarAsDescricaoItens = $descricaoItens;
        $this->gerarAsPdfFormData = [
            'data_inicio_servico' => $this->gerarAsDataInicio,
            'data_termino_servico' => $this->gerarAsDataTermino,
            'data_entrega_material' => $this->gerarAsDataEntrega,
            'valor_total_autorizacao_servico' => $this->formatMoeda($valorTotal),
            'desconto_autorizacao_servico' => $this->gerarAsDesconto,
            'total_apos_desconto' => $this->formatMoeda($valorLiquido),
            'parcelamento' => $this->gerarAsParcelas,
            'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
            'descricao_arquivo' => $this->estadoUploadArquivos($descricaoItens[0]['descricao_arquivo'] ?? []),
            'itens_descricao_servico_pdf' => $descricaoItens,
            'anexos_autorizacao_servico' => $this->estadoUploadArquivos((array) ($asa->as_anexos ?? [])),
        ];
        $this->gerarAsDatasForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsValoresParcelamentoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsDescricaoForm->fill($this->gerarAsPdfFormData);
        $this->gerarAsAnexosForm->fill($this->gerarAsPdfFormData);
    }

    public function regerarPdfAsLinha(int $itemId): void
    {
        $this->authorizeFluxoUpdate();

        $item = ControleNotaFiscalItem::query()
            ->with('autorizacaoServico')
            ->findOrFail($itemId);

        $autorizacaoServico = $item->autorizacaoServico;

        if (! $autorizacaoServico || $autorizacaoServico->status !== AsStatus::CRIADA) {
            $this->notificar(Notification::make()
                ->title('PDF não pode ser regenerado')
                ->body('A regeneração do PDF está disponível apenas para AS criada.')
                ->danger());

            return;
        }

        try {
            app(AutorizacaoServicoFluxoService::class)->gerar($autorizacaoServico);
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('PDF não gerado')
                ->body($exception->getMessage())
                ->danger());

            return;
        }

        $this->notificar(Notification::make()
            ->title('PDF da AS regenerado')
            ->success());
    }

    public function gerarAsDatasForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('gerarAsPdfFormData')
            ->columns(3)
            ->schema([
                DatePicker::make('data_inicio_servico')
                    ->label('Data início'),
                DatePicker::make('data_termino_servico')
                    ->label('Data término')
                    ->rules(['nullable', 'after_or_equal:data_inicio_servico']),
                DatePicker::make('data_entrega_material')
                    ->label('Data entrega'),
            ]);
    }

    public function gerarAsValoresParcelamentoForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('gerarAsPdfFormData')
            ->schema([
                Grid::make(3)
                    ->schema([
                        MoneyInput::make('valor_inicial', 'Valor Inicial')
                            ->afterStateUpdatedJs($this->atualizarValoresParcelasJs()),
                        MoneyInput::make('desconto_autorizacao_servico', 'Desconto')
                            ->afterStateUpdatedJs($this->atualizarValoresParcelasJs()),
                        TextInput::make('total_apos_desconto')
                            ->label('Valor Fechado')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Text::make(fn (): string => $this->getErrorBag()->first('gerarAsDesconto'))
                    ->color('danger')
                    ->visible(fn (): bool => $this->getErrorBag()->has('gerarAsDesconto')),
                Repeater::make('parcelamento')
                    ->hiddenLabel()
                    ->default(fn (): array => $this->gerarAsParcelas)
                    ->afterStateHydrated(function (Repeater $component, mixed $state): void {
                        if (filled($state) || $this->gerarAsParcelas === []) {
                            return;
                        }

                        $component->state($this->gerarAsParcelas);
                    })
                    ->table([
                        TableColumn::make('Parcela'),
                        TableColumn::make('%'),
                        TableColumn::make('Valor'),
                        TableColumn::make('Observação'),
                    ])
                    ->schema([
                        TextInput::make('parcela')
                            ->label('Parcela')
                            ->default('Parcela')
                            ->maxLength(50),
                        TextInput::make('percentual')
                            ->label('%')
                            ->default('0,00')
                            ->required()
                            ->maxLength(20)
                            ->inputMode('decimal')
                            ->mask($this->percentualMask())
                            ->afterStateUpdatedJs($this->atualizarValorParcelaJs()),
                        TextInput::make('valor')
                            ->label('Valor')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('observacao')
                            ->label('Observação')
                            ->maxLength(255),
                    ])
                    ->addAction(fn (Action $action): Action => $action->after(function (Repeater $component): void {
                        $parcelas = $this->normalizarEstadoParcelasGerarAs($component->getRawState() ?? []);
                        $ultimaChave = array_key_last($parcelas);

                        if ($ultimaChave === null) {
                            return;
                        }

                        $parcelas[$ultimaChave]['parcela'] = $this->nomeProximaParcelaDisponivel($parcelas, $ultimaChave);
                        $component->state($parcelas);
                    }))
                    ->addActionLabel('Adicionar parcela')
                    ->addActionAlignment(Alignment::Start)
                    ->minItems(1)
                    ->maxItems(6)
                    ->reorderable(false)
                    ->columns(4),
                Text::make('A soma dos percentuais está acima de 100%.')
                    ->color('danger')
                    ->hiddenJs($this->ocultarAvisoPercentualParcelamentoJs()),
            ]);
    }

    public function gerarAsDescricaoForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('gerarAsPdfFormData')
            ->schema([
                Textarea::make('descricao_servico_pdf')
                    ->label('Descrição')
                    ->rows(5)
                    ->maxLength(1000),
                FileUpload::make('descricao_arquivo')
                    ->label('Imagem')
                    ->disk((string) config('filesystems.media_disk', 'r2'))
                    ->directory('autorizacao-servico/tmp/descricao')
                    ->visibility('public')
                    ->image()
                    ->acceptedFileTypes([
                        'image/png',
                        'image/jpeg',
                        'image/gif',
                        'image/webp',
                        'image/avif',
                    ])
                    ->downloadable()
                    ->preserveFilenames()
                    ->fetchFileInformation(false)
                    ->maxFiles(1),
            ]);
    }

    public function gerarAsAnexosForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('gerarAsPdfFormData')
            ->schema([
                FileUpload::make('anexos_autorizacao_servico')
                    ->hiddenLabel()
                    ->helperText('Inclua aqui os arquivos que serão enviados como anexos no e-mail ao enviar a AS. São aceitos PDFs, imagens, planilhas e documentos em geral, conforme permitido pelo sistema de arquivos.')
                    ->disk((string) config('filesystems.media_disk', 'r2'))
                    ->directory('autorizacao-servico/tmp/anexos')
                    ->multiple()
                    ->panelLayout('grid')
                    ->downloadable()
                    ->fetchFileInformation(false)
                    ->preserveFilenames(),
            ]);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $parcelas
     * @param  array<string, mixed>|null  $datas
     */
    public function confirmarGeracaoAs(?array $parcelas = null, ?array $datas = null): void
    {
        if ($this->gerarAsModalAuxiliarId || $this->gerarAsModalEdicao) {
            $this->authorizeFluxoUpdate();
        } else {
            $this->authorizeFluxoCreate();
        }

        $service = app(AutorizacaoServicoFluxoService::class);
        $itemService = app(ControleAutorizacaoServicoItemService::class);

        $itemId = $this->gerarAsModalItemId;

        if (! $itemId && ! $this->gerarAsModalAuxiliarId) {
            return;
        }

        if ($parcelas !== null) {
            $this->gerarAsParcelas = $this->normalizarEstadoParcelasGerarAs($parcelas);
            $this->gerarAsPdfFormData['parcelamento'] = $this->gerarAsParcelas;
        }

        if ($datas !== null) {
            $this->gerarAsDataInicio = filled($datas['data_inicio_servico'] ?? null)
                ? (string) $datas['data_inicio_servico']
                : null;
            $this->gerarAsDataTermino = filled($datas['data_termino_servico'] ?? null)
                ? (string) $datas['data_termino_servico']
                : null;
            $this->gerarAsDataEntrega = filled($datas['data_entrega_material'] ?? null)
                ? (string) $datas['data_entrega_material']
                : null;
            $this->gerarAsDesconto = filled($datas['desconto_autorizacao_servico'] ?? null)
                ? (string) $datas['desconto_autorizacao_servico']
                : '0,00';
            $this->gerarAsDescricaoServicoPdf = filled($datas['descricao_servico_pdf'] ?? null)
                ? trim((string) $datas['descricao_servico_pdf'])
                : null;
            $this->gerarAsDescricaoItens = $this->normalizarEstadoDescricaoItensGerarAs(
                $this->estadoDescricaoGeracaoAs($datas),
            );
            $this->gerarAsPdfFormData = array_replace($this->gerarAsPdfFormData ?? [], [
                'data_inicio_servico' => $this->gerarAsDataInicio,
                'data_termino_servico' => $this->gerarAsDataTermino,
                'data_entrega_material' => $this->gerarAsDataEntrega,
                'desconto_autorizacao_servico' => $this->gerarAsDesconto,
                'total_apos_desconto' => $this->formatMoeda($this->valorLiquido($this->gerarAsValorFechado, $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0)),
                'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
                'descricao_arquivo' => $this->estadoUploadArquivos($this->gerarAsDescricaoItens[0]['descricao_arquivo'] ?? []),
                'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
            ]);
        }

        $datasData = $this->gerarAsDatasForm->getState();
        $valoresParcelamentoData = $this->gerarAsValoresParcelamentoForm->getState();
        $descricaoData = $this->gerarAsDescricaoForm->getState();
        $anexosData = $this->gerarAsAnexosForm->getState();
        $this->gerarAsPdfFormData = array_replace(
            $this->gerarAsPdfFormData ?? [],
            $datasData,
            $valoresParcelamentoData,
            $descricaoData,
            $anexosData,
        );

        if ($datas !== null) {
            $this->gerarAsPdfFormData = array_replace($this->gerarAsPdfFormData, [
                'data_inicio_servico' => $this->gerarAsDataInicio,
                'data_termino_servico' => $this->gerarAsDataTermino,
                'data_entrega_material' => $this->gerarAsDataEntrega,
                'desconto_autorizacao_servico' => $this->gerarAsDesconto,
                'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
                'descricao_arquivo' => $this->estadoUploadArquivos($this->gerarAsDescricaoItens[0]['descricao_arquivo'] ?? []),
                'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
            ]);
        }

        $pdfFormData = $this->gerarAsPdfFormData ?? [];

        if ($parcelas !== null) {
            $pdfFormData['parcelamento'] = $parcelas;
        }

        $this->gerarAsDataInicio = filled($pdfFormData['data_inicio_servico'] ?? null)
            ? (string) $pdfFormData['data_inicio_servico']
            : null;
        $this->gerarAsDataTermino = filled($pdfFormData['data_termino_servico'] ?? null)
            ? (string) $pdfFormData['data_termino_servico']
            : null;
        $this->gerarAsDataEntrega = filled($pdfFormData['data_entrega_material'] ?? null)
            ? (string) $pdfFormData['data_entrega_material']
            : null;
        $this->gerarAsDesconto = filled($pdfFormData['desconto_autorizacao_servico'] ?? null)
            ? (string) $pdfFormData['desconto_autorizacao_servico']
            : '0,00';
        $this->gerarAsParcelas = $this->normalizarEstadoParcelasGerarAs((array) ($pdfFormData['parcelamento'] ?? []));
        $this->gerarAsPdfFormData['parcelamento'] = $this->gerarAsParcelas;
        $this->gerarAsDescricaoServicoPdf = filled($pdfFormData['descricao_servico_pdf'] ?? null)
            ? trim((string) $pdfFormData['descricao_servico_pdf'])
            : null;
        $this->gerarAsDescricaoItens = $this->normalizarEstadoDescricaoItensGerarAs(
            $this->estadoDescricaoGeracaoAs($pdfFormData),
        );
        $this->gerarAsPdfFormData['descricao_arquivo'] = $this->estadoUploadArquivos($this->gerarAsDescricaoItens[0]['descricao_arquivo'] ?? []);
        $this->gerarAsPdfFormData['itens_descricao_servico_pdf'] = $this->gerarAsDescricaoItens;

        $this->validate([
            'gerarAsParcelas' => ['required', 'array', 'min:1', 'max:6'],
            'gerarAsParcelas.*.parcela' => ['nullable', 'string', 'max:50'],
            'gerarAsParcelas.*.percentual' => ['nullable', 'string', 'max:20'],
            'gerarAsParcelas.*.valor' => ['nullable', 'string', 'max:30'],
            'gerarAsParcelas.*.observacao' => ['nullable', 'string', 'max:255'],
            'gerarAsDataInicio' => ['nullable', 'date'],
            'gerarAsDataTermino' => ['nullable', 'date', 'after_or_equal:gerarAsDataInicio'],
            'gerarAsDataEntrega' => ['nullable', 'date'],
            'gerarAsDesconto' => ['nullable', 'string', 'max:30'],
            'gerarAsDescricaoItens' => ['required', 'array', 'min:1', 'max:1'],
            'gerarAsDescricaoItens.*.descricao' => ['nullable', 'string', 'max:1000'],
            'gerarAsDescricaoItens.*.descricao_arquivo' => ['nullable', 'array', 'max:1'],
        ]);

        if ($this->gerarAsModalAuxiliarId) {
            $valorLiquido = $this->valorLiquido($this->gerarAsValorFechado, $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0);

            try {
                $parcelamento = $this->normalizarParcelasFormulario($valorLiquido);
            } catch (DomainException $exception) {
                $this->addError('gerarAsParcelas', $exception->getMessage());

                return;
            }

            $this->executarGeracaoAsAsa($parcelamento);

            return;
        }

        $item = ControleNotaFiscalItem::query()->with('autorizacaoServico')->findOrFail($itemId);
        $dados = array_replace($this->itens[$itemId] ?? [], $this->gerarAsModalDados);

        $estado = array_replace($this->itens[$itemId] ?? [], $dados);

        $valorInicial = $itemService->parseMoedaBr($valoresParcelamentoData['valor_inicial'] ?? null)
            ?? $itemService->parseMoedaBr($this->gerarAsPdfFormData['valor_inicial'] ?? null)
            ?? (float) ($item->autorizacaoServico?->valor_inicial ?? 0);

        if (round($valorInicial, 2) <= 0 && ! $this->gerarAsPermitirValoresZerados) {
            $this->addError('gerarAsParcelas', 'Informe o valor inicial da AS.');

            return;
        }

        $this->gerarAsValorFechado = $valorInicial;

        $desconto = $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0;
        if (round($desconto, 2) > round($valorInicial, 2)) {
            $this->addError('gerarAsDesconto', 'O desconto não pode ser maior que o valor inicial.');

            return;
        }

        $valorLiquido = $this->valorLiquido($valorInicial, $desconto);
        $dados['_valor_fechado_calculado_as'] = $valorLiquido;
        $dados['_valor_inicial_calculado_as'] = $valorInicial;
        $this->gerarAsPdfFormData['valor_inicial'] = $this->formatMoeda($valorInicial);
        $this->gerarAsPdfFormData['total_apos_desconto'] = $this->formatMoeda($valorLiquido);

        try {
            $parcelamento = $this->normalizarParcelasFormulario($valorLiquido);
        } catch (DomainException $exception) {
            $this->addError('gerarAsParcelas', $exception->getMessage());

            return;
        }

        if ($this->gerarAsModalModo === 'editar_pdf') {
            $autorizacaoServico = $item->autorizacaoServico;

            if (! $autorizacaoServico || $autorizacaoServico->status !== AsStatus::CRIADA) {
                $this->notificar(Notification::make()
                    ->title('PDF não pode ser editado')
                    ->body('A edição do PDF está disponível apenas para AS criada.')
                    ->danger());

                return;
            }

            try {
                $autorizacaoServico->update([
                    'valor' => $valorLiquido,
                    'desconto_autorizacao_servico' => $desconto,
                    'parcelamento_autorizacao_servico' => $parcelamento,
                    'data_inicio_servico' => $this->gerarAsDataInicio,
                    'data_termino_servico' => $this->gerarAsDataTermino,
                    'data_entrega_material' => $this->gerarAsDataEntrega,
                    'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
                    'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
                    'anexos_autorizacao_servico' => array_values(array_filter((array) ($this->gerarAsPdfFormData['anexos_autorizacao_servico'] ?? []))),
                ]);

                $service->gerar($autorizacaoServico->refresh(), false, $parcelamento, $this->datasGeracaoAs());
            } catch (DomainException $exception) {
                $this->addError('gerarAsParcelas', $exception->getMessage());

                return;
            }

            $this->notificar(Notification::make()
                ->title('PDF da AS atualizado')
                ->success());

            $this->dispatch('as-linha-salva', itemId: $item->id, estado: $this->estadoLinhaAtualizado($item->id));
            $this->fecharModalGerarAs();

            return;
        }

        $autorizacaoServico = $this->executarCriacaoAs(
            $item,
            $dados,
            $service,
            $itemService,
            permitirValoresZerados: $this->gerarAsPermitirValoresZerados,
            parcelamento: $parcelamento,
        );

        if ($autorizacaoServico) {
            $this->fecharModalGerarAs();
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function schemaEnviarAs(): array
    {
        return [
            Select::make('para')
                ->label('Para')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (): array => $this->emailOptions)
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),

            Select::make('cc')
                ->label('CC')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (): array => $this->emailOptions)
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),

            Select::make('cco')
                ->label('CCO')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (): array => $this->emailOptions)
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function schemaEnviarAsa(): array
    {
        return [
            ...$this->schemaEnviarAs(),

            Select::make('modo_excel_asa')
                ->label('Planilha de aditivo')
                ->options([
                    'existente' => 'Enviar Excel anexo se existir',
                    'gerar' => 'Gerar e enviar com Excel anexo',
                ])
                ->default('existente')
                ->required()
                ->native(false),
        ];
    }

    /**
     * @return array{para: array<int, string>, cc: array<int, string>, cco: array<int, string>}
     */
    protected function dadosPadraoEnvioAs(int $itemId, AutorizacaoServicoFluxoService $service): array
    {
        $item = ControleNotaFiscalItem::query()
            ->with([
                'autorizacaoServico.construtora.users',
                'controleNotaFiscal.obra.projeto.responsavelEng',
            ])
            ->find($itemId);

        $para = $item?->autorizacaoServico
            ? $service->destinatariosFornecedor($item->autorizacaoServico)
            : [];
        $cc = $item?->autorizacaoServico
            ? $service->emailsGestorProjeto($item->autorizacaoServico)
            : $this->emailsGestorProjetoDaObra($item?->controleNotaFiscal?->obra, $service);

        return [
            'para' => $para,
            'cc' => $cc,
            'cco' => $this->copiasOcultasComUsuarioAtual($para, $cc, [], $service),
        ];
    }

    /**
     * @return array{para: array<int, string>, cc: array<int, string>, cco: array<int, string>, modo_excel_asa: string}
     */
    protected function dadosPadraoEnvioAsa(int $auxiliarId, AutorizacaoServicoFluxoService $service): array
    {
        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal.obra.projeto.responsavelEng')
            ->find($auxiliarId);

        $para = $auxiliar instanceof ControleNotaFiscalAuxiliar
            ? $this->destinatariosFornecedorAuxiliar($auxiliar, $service)
            : [];
        $cc = $this->emailsGestorProjetoDaObra($auxiliar?->controleNotaFiscal?->obra, $service);

        return [
            'para' => $para,
            'cc' => $cc,
            'cco' => $this->copiasOcultasComUsuarioAtual($para, $cc, [], $service),
            'modo_excel_asa' => 'existente',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function enviarAsPeloControle(int $itemId, array $data, AutorizacaoServicoFluxoService $service): void
    {
        $this->authorizeFluxoUpdate();

        if ($itemId <= 0) {
            return;
        }

        $item = ControleNotaFiscalItem::query()
            ->with(['autorizacaoServico', 'controleNotaFiscal'])
            ->findOrFail($itemId);

        if (! $item->autorizacaoServico) {
            $this->notificar(Notification::make()
                ->title('Crie a AS antes de enviar')
                ->warning());

            return;
        }

        if ($this->bloquearAcaoSeControleEncerrado($item->controleNotaFiscal, 'AS não enviada')) {
            return;
        }

        try {
            $copiasOcultas = $this->copiasOcultasComUsuarioAtual(
                (array) ($data['para'] ?? []),
                (array) ($data['cc'] ?? []),
                (array) ($data['cco'] ?? []),
                $service,
            );

            $service->enviar(
                $item->autorizacaoServico,
                Auth::user(),
                destinatarios: $data['para'] ?? [],
                copias: $data['cc'] ?? [],
                copiasOcultas: $copiasOcultas,
            );
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('AS não enviada')
                ->body($exception->getMessage())
                ->danger());

            return;
        }

        $this->notificar(Notification::make()
            ->title('AS enviada')
            ->success());
    }

    /**
     * @param  array<int, array<string, mixed>>  $parcelamento
     */
    protected function executarGeracaoAsAsa(array $parcelamento): void
    {
        $auxiliarId = $this->gerarAsModalAuxiliarId;

        if (! $auxiliarId) {
            return;
        }

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal')
            ->findOrFail($auxiliarId);

        if ($this->bloquearAcaoSeControleEncerrado($auxiliar->controleNotaFiscal, 'AS não gerada')) {
            return;
        }

        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            $this->notificar(Notification::make()
                ->title('ASA não encontrada')
                ->danger());

            return;
        }

        $datas = [
            'as_data_inicio' => $this->gerarAsDataInicio,
            'as_data_termino' => $this->gerarAsDataTermino,
            'as_data_entrega' => $this->gerarAsDataEntrega,
            'as_desconto' => $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0,
            'as_descricao_pdf' => $this->gerarAsDescricaoServicoPdf,
            'as_itens_descricao_pdf' => $this->gerarAsDescricaoItens,
            'as_anexos' => array_values(array_filter((array) ($this->gerarAsPdfFormData['anexos_autorizacao_servico'] ?? []))),
        ];

        try {
            app(AsaFluxoService::class)->gerarPdf($asa, Auth::user(), $datas, $parcelamento);
        } catch (DomainException $exception) {
            $this->addError('gerarAsParcelas', $exception->getMessage());

            return;
        }

        $titulo = $this->gerarAsModalEdicao ? 'PDF da AS atualizado' : 'AS criada';

        $this->notificar(Notification::make()
            ->title($titulo)
            ->success());

        $this->fecharModalGerarAs();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function aprovarAsaPeloControle(int $auxiliarId): void
    {
        $this->authorizeFluxoUpdate();

        if ($auxiliarId <= 0) {
            return;
        }

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal')
            ->findOrFail($auxiliarId);

        if ($this->bloquearAcaoSeControleEncerrado($auxiliar->controleNotaFiscal, 'ASA não aprovada')) {
            return;
        }

        $asa = $this->asaPendenteOrcamentoParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            $this->notificar(Notification::make()
                ->title('ASA não aprovada')
                ->body('A ASA não está pendente de aprovação do orçamento.')
                ->warning());

            return;
        }

        $asa->update([
            'status' => AsStatus::APROVADO,
            'data_aprovacao' => now(),
        ]);

        $this->sincronizarValorGlobalAuxiliarAsa($auxiliar, $asa);

        $asa->elaboracaoAditivo?->update([
            'status_fluxo' => 'aprovado',
            'aprovado_orcamento_por_id' => Auth::id(),
            'aprovado_orcamento_em' => now(),
            'justificativa_reprovacao_orcamento' => null,
        ]);

        $this->notificar(Notification::make()
            ->title('ASA aprovada')
            ->success());
    }

    protected function sincronizarValorGlobalAuxiliarAsa(ControleNotaFiscalAuxiliar $auxiliar, Asa $asa): void
    {
        $valorGlobal = round((float) ($asa->valor_total ?? 0), 2);
        $valorAcumulado = (float) $asa->notasFiscais()
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');
        $saldo = max(round($valorGlobal - $valorAcumulado, 2), 0.0);

        $auxiliar->forceFill([
            'valor_global_a' => $valorGlobal,
            'total_medicao_a_menos_b' => $saldo,
            'valor_acumulado_medido' => $valorAcumulado,
            'saldo' => $saldo,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function enviarAsaPeloControle(int $auxiliarId, array $data, AsaFluxoService $asaService): void
    {
        $this->authorizeFluxoUpdate();

        if ($auxiliarId <= 0) {
            return;
        }

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal.obra.projeto')
            ->findOrFail($auxiliarId);

        if ($this->bloquearAcaoSeControleEncerrado($auxiliar->controleNotaFiscal, 'AS não enviada')) {
            return;
        }

        $asa = $this->asaAprovadaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            $this->notificar(Notification::make()
                ->title('AS não enviada')
                ->body('Gere o PDF da AS antes de enviar.')
                ->warning());

            return;
        }

        try {
            $asFluxoService = app(AutorizacaoServicoFluxoService::class);
            $copiasOcultas = $this->copiasOcultasComUsuarioAtual(
                (array) ($data['para'] ?? []),
                (array) ($data['cc'] ?? []),
                (array) ($data['cco'] ?? []),
                $asFluxoService,
            );

            $asaService->enviar(
                $asa,
                Auth::user(),
                destinatarios: (array) ($data['para'] ?? []),
                copias: (array) ($data['cc'] ?? []),
                copiasOcultas: $copiasOcultas,
                modoExcel: (string) ($data['modo_excel_asa'] ?? 'existente'),
            );
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('AS não enviada')
                ->body($exception->getMessage())
                ->danger());

            return;
        }

        $this->notificar(Notification::make()
            ->title('AS enviada')
            ->success());
    }

    protected function bloquearAcaoSeControleEncerrado(?ControleNotaFiscal $controleNotaFiscal, string $titulo): bool
    {
        if ($controleNotaFiscal?->status !== ControleNotaFiscal::STATUS_ENCERRADO) {
            return false;
        }

        $this->notificar(Notification::make()
            ->title($titulo)
            ->body('Controle de nota fiscal encerrado para a unidade.')
            ->danger());

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function cancelarAsPeloControle(int $itemId, array $data, AutorizacaoServicoFluxoService $service): void
    {
        $this->authorizeFluxoUpdate();

        if ($itemId <= 0) {
            return;
        }

        $item = ControleNotaFiscalItem::query()->with('autorizacaoServico')->findOrFail($itemId);

        if (! $item->autorizacaoServico) {
            return;
        }

        $autorizacaoServico = $item->autorizacaoServico;

        try {
            $service->cancelar($autorizacaoServico, 'Cancelamento manual pelo controle de AS.', Auth::user());
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('AS não cancelada')
                ->body($exception->getMessage())
                ->danger());

            return;
        }

        $this->notificarCancelamentoAs(
            $autorizacaoServico->refresh(),
            (array) ($data['para'] ?? []),
            (array) ($data['cc'] ?? []),
            (array) ($data['cco'] ?? []),
            $service,
        );

        $this->notificar(Notification::make()
            ->title('AS cancelada')
            ->success());
    }

    /**
     * @param  array<int, mixed>  $destinatarios
     * @param  array<int, mixed>  $copias
     * @param  array<int, mixed>  $copiasOcultas
     */
    protected function notificarCancelamentoAs(
        AutorizacaoServico $autorizacaoServico,
        array $destinatarios,
        array $copias,
        array $copiasOcultas,
        AutorizacaoServicoFluxoService $service,
    ): void {
        $destinatarios = $service->normalizarEmails($destinatarios);
        $copias = $service->normalizarEmails($copias);
        $copiasOcultas = $service->normalizarEmails($copiasOcultas);

        if ($destinatarios === []) {
            throw new DomainException('Informe ao menos um e-mail válido para notificar o cancelamento da AS.');
        }

        $autorizacaoServico->loadMissing(['obra', 'construtora', 'asEscopo', 'canceladoPor']);

        $mensagem = '<p>Prezados,</p>'
            .'<p>A Autorização de Serviço <strong>'.e($autorizacaoServico->numero_as).'</strong> foi cancelada.</p>'
            .'<p><strong>Unidade:</strong> '.e($autorizacaoServico->obra?->unidade ?? '-').'<br>'
            .'<strong>Fornecedor:</strong> '.e($autorizacaoServico->construtora?->nome ?? '-').'<br>'
            .'<strong>Escopo:</strong> '.e($autorizacaoServico->asEscopo?->escopo ?? '-').'<br>'
            .'<strong>Motivo:</strong> '.e($autorizacaoServico->motivo_cancelamento ?: 'Cancelamento manual pelo controle de AS.').'</p>'
            .'<p>Este e-mail foi enviado por '.e(Auth::user()?->name ?? 'Gestão Smart').'.</p>';

        Mail::to($destinatarios)
            ->cc($copias)
            ->bcc($copiasOcultas)
            ->send(new EnviarPdfMail(
                assunto: 'AS cancelada '.$autorizacaoServico->numero_as,
                mensagemEmail: $mensagem,
                pdfBinary: '',
                nomeArquivo: '',
                nomeRemetente: Auth::user()?->name,
                emailRemetente: Auth::user()?->email,
            ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function cancelarAsaPeloControle(int $auxiliarId, array $data, AsaFluxoService $asaService): void
    {
        $this->authorizeFluxoUpdate();

        if ($auxiliarId <= 0) {
            return;
        }

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->with('controleNotaFiscal.obra')
            ->findOrFail($auxiliarId);

        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            $this->notificar(Notification::make()
                ->title('AS não cancelada')
                ->body('Não foi possível localizar a AS desta linha.')
                ->danger());

            return;
        }

        try {
            $asaService->cancelar($asa, 'Cancelamento manual pelo controle de AS.', Auth::user());
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('AS não cancelada')
                ->body($exception->getMessage())
                ->danger());

            return;
        }

        try {
            $this->notificarCancelamentoAsa(
                $asa->refresh(),
                $auxiliar,
                (array) ($data['para'] ?? []),
                (array) ($data['cc'] ?? []),
                (array) ($data['cco'] ?? []),
                app(AutorizacaoServicoFluxoService::class),
            );
        } catch (DomainException $exception) {
            $this->notificar(Notification::make()
                ->title('AS cancelada, mas e-mail não enviado')
                ->body($exception->getMessage())
                ->warning());

            return;
        }

        $this->notificar(Notification::make()
            ->title('AS cancelada')
            ->success());
    }

    /**
     * @param  array<int, mixed>  $destinatarios
     * @param  array<int, mixed>  $copias
     * @param  array<int, mixed>  $copiasOcultas
     */
    protected function notificarCancelamentoAsa(
        Asa $asa,
        ControleNotaFiscalAuxiliar $auxiliar,
        array $destinatarios,
        array $copias,
        array $copiasOcultas,
        AutorizacaoServicoFluxoService $service,
    ): void {
        $destinatarios = $service->normalizarEmails($destinatarios);
        $copias = $service->normalizarEmails($copias);
        $copiasOcultas = $service->normalizarEmails($copiasOcultas);

        if ($destinatarios === []) {
            throw new DomainException('Informe ao menos um e-mail válido para notificar o cancelamento da AS.');
        }

        $unidade = $auxiliar->controleNotaFiscal?->obra?->unidade ?? '-';
        $numeroAsa = (string) ($auxiliar->numero_as ?? $asa->numero_asa ?? '');
        $escopo = (string) ($auxiliar->escopo ?? '');
        $fornecedor = (string) ($asa->solicitante ?? $auxiliar->empresa ?? '');
        $motivo = (string) ($asa->as_motivo_cancelamento ?: 'Cancelamento manual pelo controle de AS.');

        $mensagem = '<p>Prezados,</p>'
            .'<p>A Autorização de Serviço <strong>'.e($numeroAsa ?: '-').'</strong> foi cancelada.</p>'
            .'<p><strong>Unidade:</strong> '.e($unidade).'<br>'
            .'<strong>Fornecedor:</strong> '.e($fornecedor ?: '-').'<br>'
            .'<strong>Escopo:</strong> '.e($escopo ?: '-').'<br>'
            .'<strong>Motivo:</strong> '.e($motivo).'</p>'
            .'<p>Este e-mail foi enviado por '.e(Auth::user()?->name ?? 'Gestão Smart').'.</p>';

        Mail::to($destinatarios)
            ->cc($copias)
            ->bcc($copiasOcultas)
            ->send(new EnviarPdfMail(
                assunto: 'AS cancelada '.$numeroAsa,
                mensagemEmail: $mensagem,
                pdfBinary: '',
                nomeArquivo: '',
                nomeRemetente: Auth::user()?->name,
                emailRemetente: Auth::user()?->email,
            ));

        $usuariosNotificacao = User::query()
            ->where('is_active', true)
            ->whereIn('email', $destinatarios)
            ->get();

        if ($usuariosNotificacao->isNotEmpty()) {
            Notification::make()
                ->title('Item cancelado pelo orçamentista')
                ->body('A AS '.($numeroAsa !== '' ? $numeroAsa.' - ' : '').$escopo.' da unidade '.$unidade.' foi cancelada. Motivo: '.$motivo)
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->sendToDatabase($usuariosNotificacao);
        }
    }

    /**
     * @param  array<int, mixed>  $destinatarios
     * @param  array<int, mixed>  $copias
     * @param  array<int, mixed>  $copiasOcultas
     */
    protected function liberarAuxiliarParaFornecedor(
        ControleNotaFiscalAuxiliar $auxiliar,
        mixed $liberadoEm,
        array $destinatarios,
        array $copias,
        array $copiasOcultas,
        string $modoExcelAditivo,
        Asa $asa,
        AutorizacaoServicoFluxoService $service,
    ): void {
        if ($auxiliar->liberado_para_fornecedor_at !== null) {
            return;
        }

        $construtora = $this->construtoraAuxiliar($auxiliar);

        if (! $construtora instanceof Construtora) {
            throw new DomainException('O fornecedor do aditivo/ASA não foi localizado no cadastro de fornecedores.');
        }

        $empresaNome = (string) $construtora->nome;
        $destinatarios = $service->normalizarEmails($destinatarios);
        $copias = $service->normalizarEmails($copias);
        $copiasOcultas = $service->normalizarEmails($copiasOcultas);

        if ($destinatarios === []) {
            throw new DomainException('Informe ao menos um e-mail válido para enviar a ASA.');
        }

        $unidade = $auxiliar->controleNotaFiscal?->obra?->unidade ?? $auxiliar->controleNotaFiscal?->unidade ?? '-';
        $numeroAsa = (string) ($auxiliar->numero_as ?? '');
        $escopo = (string) ($auxiliar->escopo ?? '');
        $anexos = $this->anexosExcelAditivoAsa($asa, $modoExcelAditivo);
        $mensagem = '<p>Prezados,</p>'
            .'<p>A Autorização de Serviço Adicional <strong>'.e($numeroAsa ?: '-').'</strong> foi liberada.</p>'
            .'<p><strong>Unidade:</strong> '.e($unidade).'<br>'
            .'<strong>Fornecedor:</strong> '.e($empresaNome).'<br>'
            .'<strong>Escopo:</strong> '.e($escopo ?: '-').'</p>'
            .'<p>Fica autorizada a emissão da Nota Fiscal.</p>'
            .'<p>Este e-mail foi enviado por '.e(Auth::user()?->name ?? 'Gestão Smart').'.</p>';

        Mail::to($destinatarios)
            ->cc($copias)
            ->bcc($copiasOcultas)
            ->send(new EnviarPdfMail(
                assunto: 'ASA liberada '.$numeroAsa,
                mensagemEmail: $mensagem,
                pdfBinary: '',
                nomeArquivo: '',
                anexos: $anexos,
                nomeRemetente: Auth::user()?->name,
                emailRemetente: Auth::user()?->email,
            ));

        $auxiliar->forceFill([
            'empresa' => $empresaNome,
            'liberado_para_fornecedor_at' => $liberadoEm,
        ])->save();

        $usuariosFornecedor = User::query()
            ->where('is_active', true)
            ->whereIn('email', $destinatarios)
            ->get();

        if ($usuariosFornecedor->isNotEmpty()) {
            Notification::make()
                ->title('ASA liberada para importação de nota fiscal')
                ->body('A ASA '.$numeroAsa.' da unidade '.$unidade.' foi aprovada e liberada para importação da nota fiscal.')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->actions([
                    Action::make('abrir')
                        ->label('Importar nota fiscal')
                        ->url(ConstrutoraControlesNotaFiscalPage::getUrl()),
                ])
                ->sendToDatabase($usuariosFornecedor);
        }
    }

    /**
     * @return array<int, array{conteudo: string, nome: string, mime: string}>
     */
    protected function anexosExcelAditivoAsa(Asa $asa, string $modoExcelAditivo): array
    {
        $asa->loadMissing(['elaboracaoAditivo.obra', 'elaboracaoAditivo.asEscopo']);
        $aditivo = $asa->elaboracaoAditivo;

        if (! $aditivo) {
            return [];
        }

        $nomeArquivo = $this->nomeArquivoExcelAditivo($aditivo);
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
        $path = trim((string) $asa->planilha_apresentada);

        if ($path !== '' && $disk->exists($path)) {
            return [[
                'conteudo' => (string) $disk->get($path),
                'nome' => $nomeArquivo,
                'mime' => $disk->mimeType($path) ?: $mime,
            ]];
        }

        if ($modoExcelAditivo !== 'gerar') {
            return [];
        }

        try {
            $conteudo = (string) Excel::raw(
                new ElaboracaoAditivoPlanilhaExport($aditivo->id),
                ExcelFormat::XLSX,
            );
        } catch (\Throwable $exception) {
            Log::warning('Excel do aditivo não gerado para envio da ASA.', [
                'autorizacao_servico_adicional_id' => $asa->id,
                'aditivo_id' => $aditivo->id,
                'message' => $exception->getMessage(),
            ]);

            throw new DomainException('Não foi possível gerar o Excel do aditivo para anexar ao e-mail.');
        }

        return [[
            'conteudo' => $conteudo,
            'nome' => $nomeArquivo,
            'mime' => $mime,
        ]];
    }

    protected function nomeArquivoExcelAditivo(ElaboracaoAditivo $aditivo): string
    {
        $unidade = $aditivo->obra?->unidade ?? 'sem-unidade';
        $escopo = $aditivo->asEscopo?->escopo ?? 'sem-escopo';

        return Str::of($unidade.' - '.$escopo)
            ->ascii()
            ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
            ->replace('  ', ' ')
            ->trim()
            ->lower()
            ->append('.xlsx')
            ->toString();
    }

    /**
     * @return array<int, string>
     */
    protected function destinatariosFornecedorAuxiliar(
        ControleNotaFiscalAuxiliar $auxiliar,
        AutorizacaoServicoFluxoService $service,
    ): array {
        $construtora = $this->construtoraAuxiliar($auxiliar);

        if (! $construtora instanceof Construtora) {
            return [];
        }

        $construtora->loadMissing('users');

        $emailsContatos = $construtora->users
            ->pluck('email')
            ->all();

        return $service->normalizarEmails($emailsContatos);
    }

    /**
     * @return array<int, string>
     */
    protected function emailsGestorProjetoDaObra(?Obras $obra, AutorizacaoServicoFluxoService $service): array
    {
        return $service->normalizarEmails([
            $obra?->projeto?->responsavelEng?->email,
        ]);
    }

    /**
     * @param  array<int, mixed>  $destinatarios
     * @param  array<int, mixed>  $copias
     * @param  array<int, mixed>  $copiasOcultas
     * @return array<int, string>
     */
    protected function copiasOcultasComUsuarioAtual(
        array $destinatarios,
        array $copias,
        array $copiasOcultas,
        AutorizacaoServicoFluxoService $service,
    ): array {
        $emailsVisiveis = $service->normalizarEmails([...$destinatarios, ...$copias]);
        $emailsOcultos = $service->normalizarEmails($copiasOcultas);
        $emailUsuarioAtual = $service->normalizarEmails([Auth::user()?->email])[0] ?? null;

        if ($emailUsuarioAtual === null || in_array($emailUsuarioAtual, [...$emailsVisiveis, ...$emailsOcultos], true)) {
            return $emailsOcultos;
        }

        $emailsOcultos[] = $emailUsuarioAtual;

        return array_values(array_unique($emailsOcultos));
    }

    public function itemValor(int $itemId, string $campo, mixed $fallback = null): string
    {
        $valor = data_get($this->itens, "{$itemId}.{$campo}", $fallback ?? 0);

        return $this->formatMoeda($valor);
    }

    public function formatMoeda(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }

    public function formatPercentual(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '.').'%';
    }

    public function formatNumeroBr(mixed $valor): string
    {
        return Str::of(number_format((float) $valor, 2, ',', '.'))
            ->rtrim('0')
            ->rtrim(',')
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function prepararResumo(Obras $obra): array
    {
        $resumo = $obra->controleAutorizacaoServicoResumo;
        $obraId = $obra->id;
        $itensPrincipais = $this->itensPrincipais($obra);
        $usarResumoPersistido = $itensPrincipais === [];

        $this->resumos[$obraId] = [
            'oi_shell' => $usarResumoPersistido ? ($resumo?->oi_shell ?? 0) : 0.0,
            'oi_recheio' => $usarResumoPersistido ? ($resumo?->oi_recheio ?? 0) : 0.0,
            'valor_inicial_shell' => $usarResumoPersistido ? ($resumo?->valor_inicial_shell ?? 0) : 0.0,
            'valor_inicial_recheio' => $usarResumoPersistido ? ($resumo?->valor_inicial_recheio ?? 0) : 0.0,
            'valor_final_shell' => $usarResumoPersistido ? ($resumo?->valor_final_shell ?? 0) : 0.0,
            'valor_final_recheio' => $usarResumoPersistido ? ($resumo?->valor_final_recheio ?? 0) : 0.0,
            'valor_final_adicional' => $this->totalNotasAprovadasAdicionaisObra($obra),
        ];

        foreach ($itensPrincipais as $item) {
            $estado = $this->itens[$item->id] ?? [];
            $bucket = $this->itemPertenceAoShell($item, $estado) ? 'shell' : 'recheio';
            $valorEstimado = $this->parseMoedaBr($estado['valor_estimado'] ?? null)
                ?? (float) ($item->autorizacaoServico?->valor_estimado ?? $item->valor_estimado_as ?? 0);
            $valorFechado = $this->parseMoedaBr($estado['valor_fechado'] ?? null)
                ?? (float) ($item->autorizacaoServico?->valor ?? $item->valor_global_a ?? 0);

            $this->resumos[$obraId]["oi_{$bucket}"] += $valorEstimado;
            $this->resumos[$obraId]["valor_inicial_{$bucket}"] += $valorFechado;
            $this->resumos[$obraId]["valor_final_{$bucket}"] += $valorFechado;
        }

        return $this->resumos[$obraId];
    }

    protected function totalNotasAprovadasAdicionaisObra(Obras $obra): float
    {
        return (float) $obra->controlesNotaFiscal
            ->flatMap(fn (ControleNotaFiscal $controle) => $controle->auxiliares)
            ->sum(fn (ControleNotaFiscalAuxiliar $auxiliar): float => $this->totalNotasAprovadasAuxiliar($auxiliar));
    }

    /**
     * @return array<int, ControleNotaFiscalAuxiliar>
     */
    public function itensAdicionais(Obras $obra): array
    {
        return $obra->controlesNotaFiscal
            ->flatMap(fn (ControleNotaFiscal $controle) => $controle->auxiliares)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    protected function itemPertenceAoShell(ControleNotaFiscalItem $item, array $estado): bool
    {
        $asEscopoId = data_get($estado, 'as_escopo_id') ?: $item->as_escopo_id;
        $grupo = filled($asEscopoId)
            ? data_get($this->asEscopoMetadados, "{$asEscopoId}.grupo")
            : null;

        $grupo ??= $item->asEscopo?->grupo ?? $item->grupo;

        return Str::of((string) $grupo)->trim()->lower()->toString() === 'shell';
    }

    /**
     * @return array<int, ControleNotaFiscalItem>
     */
    public function itensPrincipais(Obras $obra): array
    {
        if (array_key_exists($obra->id, $this->itensPrincipaisPorObra)) {
            return $this->itensPrincipaisPorObra[$obra->id];
        }

        return $this->itensPrincipaisPorObra[$obra->id] = $obra->controlesNotaFiscal
            ->flatMap(fn (ControleNotaFiscal $controle) => $controle->itens)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function prepararItem(ControleNotaFiscalItem $item): array
    {
        $this->registrarAsEscopoMetadados($item->asEscopo);

        $autorizacaoServico = $item->autorizacaoServico;
        $construtoraId = $autorizacaoServico?->construtora_id
            ?? ($this->construtoraIdsPorNome[(string) $item->empresa] ?? null);

        $this->itens[$item->id] ??= [
            'as_escopo_id' => $item->as_escopo_id,
            'construtora_id' => $construtoraId,
            'numero_complemento' => $autorizacaoServico?->numero_complemento ?: $item->numero_complemento,
            'escopo_complementar' => $item->escopo_complementar ?? '',
            'valor_estimado' => $autorizacaoServico?->valor_estimado ?? $item->valor_estimado_as ?? 0,
            'valor_estimado_as_simulador' => $item->valor_estimado_as_simulador,
            'valor_estimado_as_editado_manualmente' => $item->valor_estimado_as_editado_manualmente,
            'valor_fechado' => $autorizacaoServico?->valor ?? $item->valor_global_a ?? 0,
            'percentual_faturamento_mao_obra' => $item->percentual_faturamento_mao_obra ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_mao_obra_default', 60),
            'percentual_faturamento_material' => $item->percentual_faturamento_material ?? $this->percentualPadraoEscopo($item, 'percentual_faturamento_material_default', 40),
        ];
        $this->itens[$item->id]['faturado'] = $this->totalNotasAprovadasItem($item);

        return $this->itens[$item->id];
    }

    protected function percentualPadraoEscopo(ControleNotaFiscalItem $item, string $campo, float $fallback): float
    {
        $asEscopoId = data_get($this->itens, "{$item->id}.as_escopo_id") ?: $item->as_escopo_id;

        return (float) data_get($this->asEscopoMetadados, "{$asEscopoId}.{$campo}", $fallback);
    }

    public function grupoEscopoLinha(ControleNotaFiscalItem $item): string
    {
        $asEscopoId = data_get($this->itens, "{$item->id}.as_escopo_id");

        if (filled($asEscopoId)) {
            return (string) data_get($this->asEscopoMetadados, "{$asEscopoId}.grupo", '-');
        }

        return $item->asEscopo?->grupo ?? '-';
    }

    public function numeroAsEscopoLinha(ControleNotaFiscalItem $item): string
    {
        $asEscopoId = data_get($this->itens, "{$item->id}.as_escopo_id");

        if (filled($asEscopoId)) {
            return (string) data_get($this->asEscopoMetadados, "{$asEscopoId}.numero_as", '-');
        }

        return $item->asEscopo?->numero_as ?? $item->numero_as ?? '-';
    }

    public function escopoLinha(ControleNotaFiscalItem $item): string
    {
        $asEscopoId = data_get($this->itens, "{$item->id}.as_escopo_id");

        if (filled($asEscopoId)) {
            return (string) data_get($this->asEscopoMetadados, "{$asEscopoId}.escopo", '-');
        }

        return $item->asEscopo?->escopo ?? $item->escopo ?? '-';
    }

    protected function totalNotasAprovadasItem(ControleNotaFiscalItem $item): float
    {
        return (float) $this->notasFiscaisDaLinha($item)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');
    }

    public function totalNotasAprovadasAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): float
    {
        return (float) $this->notasFiscaisDaLinha($auxiliar)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');
    }

    protected function notasFiscaisDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $linha): EloquentCollection
    {
        return $linha->relationLoaded('notasFiscais')
            ? $linha->notasFiscais
            : $linha->notasFiscais()->get();
    }

    public function valorFechadoAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): float
    {
        $asa = $this->asaParaAuxiliar($auxiliar);

        return $asa instanceof Asa
            ? (float) ($asa->valor_total ?? 0)
            : (float) ($auxiliar->valor_global_a ?? 0);
    }

    /**
     * @return array{mao_obra: float, material: float, percentual_mao_obra: float, percentual_material: float}
     */
    public function faturamentoAditivoAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): array
    {
        $asa = $this->asaParaAuxiliar($auxiliar);
        $asa?->loadMissing('elaboracaoAditivo.itens');

        $itens = $asa?->elaboracaoAditivo?->itens;
        $maoObra = 0.0;
        $material = 0.0;

        if ($itens !== null && $itens->isNotEmpty()) {
            foreach ($itens as $item) {
                $quantidade = (float) ($item->quantidade ?? 0);
                $maoObra += $quantidade * (float) ($item->valor_mao_obra_unitario ?? 0);
                $material += $quantidade * (float) ($item->valor_material_unitario ?? 0);
            }
        }

        $total = $maoObra + $material;

        if ($total <= 0) {
            $valorFechado = $this->valorFechadoAuxiliar($auxiliar);
            $percentualMaoObra = (float) ($auxiliar->percentual_faturamento_mao_obra ?? 60);
            $percentualMaterial = (float) ($auxiliar->percentual_faturamento_material ?? max(0, 100 - $percentualMaoObra));

            return [
                'mao_obra' => round($valorFechado * ($percentualMaoObra / 100), 2),
                'material' => round($valorFechado * ($percentualMaterial / 100), 2),
                'percentual_mao_obra' => round($percentualMaoObra, 2),
                'percentual_material' => round($percentualMaterial, 2),
            ];
        }

        return [
            'mao_obra' => round($maoObra, 2),
            'material' => round($material, 2),
            'percentual_mao_obra' => round(($maoObra / $total) * 100, 2),
            'percentual_material' => round(($material / $total) * 100, 2),
        ];
    }

    public function fornecedorAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): ?string
    {
        return $this->construtoraAuxiliar($auxiliar)?->nome
            ?? ($this->asaParaAuxiliar($auxiliar)?->solicitante ?: null)
            ?? ($auxiliar->empresa ?: null);
    }

    public function saldoAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): float
    {
        return max($this->valorFechadoAuxiliar($auxiliar) - $this->totalNotasAprovadasAuxiliar($auxiliar), 0);
    }

    public function percentualSaldoAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): float
    {
        $valorGlobal = $this->valorFechadoAuxiliar($auxiliar);

        if ($valorGlobal <= 0) {
            return 0.0;
        }

        return round(($this->saldoAuxiliar($auxiliar) / $valorGlobal) * 100, 2);
    }

    public function podeEnviarAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        return $auxiliar->liberado_para_fornecedor_at === null
            && $auxiliar->controleNotaFiscal?->status !== ControleNotaFiscal::STATUS_ENCERRADO
            && $this->asaAprovadaParaAuxiliar($auxiliar) instanceof Asa;
    }

    public function podeCriarAsAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        return $auxiliar->liberado_para_fornecedor_at === null
            && $auxiliar->controleNotaFiscal?->status !== ControleNotaFiscal::STATUS_ENCERRADO
            && $this->asaPendenteOrcamentoParaAuxiliar($auxiliar) instanceof Asa;
    }

    public function podeEditarAsAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        $asa = $this->asaParaAuxiliar($auxiliar);

        return $auxiliar->liberado_para_fornecedor_at === null
            && $auxiliar->controleNotaFiscal?->status !== ControleNotaFiscal::STATUS_ENCERRADO
            && $asa instanceof Asa
            && $asa->status === AsStatus::CRIADA;
    }

    public function podeVisualizarAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            return false;
        }

        if (! $asa->status instanceof AsStatus || ! $asa->status->permiteVisualizar()) {
            return false;
        }

        return (bool) Auth::user()?->can('View:Asa');
    }

    public function podeCancelarAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        if (! $this->podeAtualizarFluxo()) {
            return false;
        }

        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            return false;
        }

        if (! $asa->status instanceof AsStatus || ! $asa->status->permiteCancelar()) {
            return false;
        }

        $temNotaAprovada = $asa->notasFiscais()
            ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value)
            ->exists();

        if (! $temNotaAprovada) {
            return true;
        }

        return (bool) Auth::user()?->can('CancelApproved:AutorizacaoServico');
    }

    /** @deprecated Use podeCriarAsAsa() */
    public function podeAprovarAsa(ControleNotaFiscalAuxiliar $auxiliar): bool
    {
        return $this->podeCriarAsAsa($auxiliar);
    }

    public function asaParaAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): ?Asa
    {
        $asaVinculada = Asa::query()
            ->with(['elaboracaoAditivo', 'projeto'])
            ->where('controle_nota_fiscal_auxiliar_id', $auxiliar->id)
            ->latest('id')
            ->first();

        if ($asaVinculada instanceof Asa) {
            return $asaVinculada;
        }

        return null;
    }

    protected function asaAprovadaParaAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): ?Asa
    {
        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            return null;
        }

        if (in_array($asa->status, [AsStatus::CRIADA, AsStatus::ENVIADA], true)) {
            return $asa;
        }

        $asa->loadMissing('elaboracaoAditivo');

        return $asa->status === AsStatus::APROVADO
            && $asa->elaboracaoAditivo?->status_fluxo === 'aprovado'
            && filled($asa->elaboracaoAditivo?->aprovado_orcamento_por_id)
            && filled($asa->elaboracaoAditivo?->aprovado_orcamento_em)
                ? $asa
                : null;
    }

    protected function asaPendenteOrcamentoParaAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): ?Asa
    {
        $asa = $this->asaParaAuxiliar($auxiliar);

        if (! $asa instanceof Asa) {
            return null;
        }

        $asa->loadMissing('elaboracaoAditivo');

        return $asa->status === AsStatus::EM_APROVACAO_ORCAMENTO
            && $asa->elaboracaoAditivo?->status_fluxo === 'em_aprovacao_orcamento'
            && filled($asa->elaboracaoAditivo?->aprovado_gestor_por_id)
            && filled($asa->elaboracaoAditivo?->aprovado_gestor_em)
                ? $asa
                : null;
    }

    protected function construtoraAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): ?Construtora
    {
        $asa = $this->asaParaAuxiliar($auxiliar);
        $asa?->loadMissing('elaboracaoAditivo.construtora');

        $construtora = $asa?->elaboracaoAditivo?->construtora;

        if ($construtora instanceof Construtora) {
            return $construtora;
        }

        $nomeFornecedor = trim((string) ($asa?->solicitante ?: $auxiliar->empresa));

        if ($nomeFornecedor === '') {
            return null;
        }

        return Construtora::query()
            ->where('nome', $nomeFornecedor)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    protected function asaGrupoCandidates(string $grupo): array
    {
        $normalizado = ControleNotaFiscalAuxiliar::normalizeGrupo($grupo);

        return array_values(array_unique(array_filter([$grupo, $normalizado])));
    }

    public function totalResumo(int $obraId, string $prefixo): float
    {
        return match ($prefixo) {
            'oi' => (float) data_get($this->resumos, "{$obraId}.oi_shell", 0)
                + (float) data_get($this->resumos, "{$obraId}.oi_recheio", 0),
            'valor_inicial' => (float) data_get($this->resumos, "{$obraId}.valor_inicial_shell", 0)
                + (float) data_get($this->resumos, "{$obraId}.valor_inicial_recheio", 0),
            'valor_final' => (float) data_get($this->resumos, "{$obraId}.valor_final_shell", 0)
                + (float) data_get($this->resumos, "{$obraId}.valor_final_recheio", 0)
                + (float) data_get($this->resumos, "{$obraId}.valor_final_adicional", 0),
            default => 0.0,
        };
    }

    public function desvioResumo(int $obraId): float
    {
        return $this->totalResumo($obraId, 'valor_final') - $this->totalResumo($obraId, 'oi');
    }

    public function percentualDesvioResumo(int $obraId): float
    {
        $valorTotal = $this->totalResumo($obraId, 'valor_final');

        if ($valorTotal <= 0) {
            return 0.0;
        }

        return round(($this->desvioResumo($obraId) / $valorTotal) * 100, 2);
    }

    public function percentualShellResumo(int $obraId): float
    {
        $valorFinal = $this->totalResumo($obraId, 'valor_final');

        if ($valorFinal <= 0) {
            return 0.0;
        }

        return round(((float) data_get($this->resumos, "{$obraId}.valor_inicial_shell", 0) / $valorFinal) * 100, 2);
    }

    public function percentualAdicionalResumo(int $obraId): float
    {
        $valorFinal = $this->totalResumo($obraId, 'valor_final');

        if ($valorFinal <= 0) {
            return 0.0;
        }

        $adicionais = (float) data_get($this->resumos, "{$obraId}.valor_final_adicional", 0);

        return round(($adicionais / $valorFinal) * 100, 2);
    }

    public function saldoItem(ControleNotaFiscalItem $item): float
    {
        $estado = $this->prepararItem($item);
        $valorFechado = $this->parseMoedaBr($estado['valor_fechado'] ?? null) ?? 0;
        $faturado = $this->parseMoedaBr($estado['faturado'] ?? null) ?? 0;

        return max($valorFechado - $faturado, 0);
    }

    public function percentualSaldoItem(ControleNotaFiscalItem $item): float
    {
        $estado = $this->prepararItem($item);
        $valorFechado = $this->parseMoedaBr($estado['valor_fechado'] ?? null) ?? 0;

        if ($valorFechado <= 0) {
            return 0.0;
        }

        return round(($this->saldoItem($item) / $valorFechado) * 100, 2);
    }

    public function linhaRascunhoRemovivel(ControleNotaFiscalItem $item): bool
    {
        return $item->autorizacaoServico()->doesntExist();
    }

    public function urlViewObra(int $obraId): string
    {
        return ViewObra::getUrl(['record' => $obraId]);
    }

    public function podeAtualizarFluxo(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('Update:AutorizacaoServico');
    }

    public function podeCriarFluxo(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('Create:AutorizacaoServico');
    }

    public function podeCancelarAs(ControleNotaFiscalItem $item): bool
    {
        if (! $this->podeAtualizarFluxo()) {
            return false;
        }

        $autorizacaoServico = $item->autorizacaoServico;

        if (! $autorizacaoServico || $autorizacaoServico->status === AsStatus::CANCELADA) {
            return false;
        }

        $temNotaAprovada = $this->notasFiscaisDaLinha($item)
            ->contains('status', StatusControleNotaFiscalNota::APROVADO->value);

        if (! $temNotaAprovada) {
            return true;
        }

        return (bool) Auth::user()?->can('CancelApproved:AutorizacaoServico');
    }

    protected function asImutavel(?AutorizacaoServico $autorizacaoServico): bool
    {
        return in_array($autorizacaoServico?->status, [
            AsStatus::ENVIADA,
            AsStatus::CANCELADA,
        ], true);
    }

    protected function authorizeFluxoUpdate(): void
    {
        abort_unless($this->podeAtualizarFluxo(), 403);
    }

    protected function authorizeFluxoCreate(): void
    {
        abort_unless($this->podeCriarFluxo(), 403);
    }

    protected function parseMoedaBr(mixed $valor): ?float
    {
        return $this->itemService()->parseMoedaBr($valor);
    }

    protected function percentualMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            (() => {
                let value = String($input ?? '')
                    .replace(/[^\d,]/g, '')
                    .replace(/,+/g, ',');

                const parts = value.split(',');
                let integer = parts[0] || '';
                const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                integer = integer.replace(/^0+(?=\d)/, '');

                if (integer === '') {
                    integer = decimal === null ? '' : '0';
                }

                return decimal === null ? integer : `${integer},${decimal}`;
            })()
        JS);
    }

    protected function quantidadeMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            (() => {
                let value = String($input ?? '')
                    .replace(/[^\d,.]/g, '')
                    .replace(/\./g, ',')
                    .replace(/,+/g, ',');

                const parts = value.split(',');
                let integer = parts[0] ?? '';
                const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                integer = integer.replace(/^0+(?=\d)/, '');

                if (integer === '') {
                    integer = decimal === null ? '' : '0';
                }

                return decimal === null ? integer : `${integer},${decimal}`;
            })()
        JS);
    }

    /**
     * @return array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>
     */
    protected function parcelasPadrao(float $valorFechado): array
    {
        return [[
            'parcela' => $this->nomeParcelaPadrao(1),
            'percentual' => '100,00',
            'valor' => $this->formatMoeda($valorFechado),
            'observacao' => '>> FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) DPC',
        ]];
    }

    /**
     * @return array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>
     */
    protected function parcelasPdfExistente(AutorizacaoServico $autorizacaoServico, float $valorLiquido): array
    {
        $parcelas = collect($autorizacaoServico->parcelamento_autorizacao_servico ?? [])
            ->filter(fn (mixed $parcela): bool => is_array($parcela))
            ->map(function (array $parcela): array {
                $percentual = (float) ($parcela['percentual'] ?? 0);
                $valor = (float) ($parcela['valor'] ?? 0);

                return [
                    'parcela' => (string) ($parcela['parcela'] ?? ''),
                    'percentual' => $this->formatPercentual($percentual),
                    'valor' => $this->formatMoeda($valor),
                    'observacao' => (string) ($parcela['observacao'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return $parcelas !== [] ? $parcelas : $this->parcelasPadrao($valorLiquido);
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    protected function descricaoPadraoGeracaoAs(ControleNotaFiscalItem $item, array $estado): string
    {
        $asEscopoId = (int) ($estado['as_escopo_id'] ?? $item->as_escopo_id ?? 0);
        $asEscopo = $asEscopoId > 0 ? AsEscopo::query()->find($asEscopoId) : null;
        $descricao = $estado['escopo_complementar'] ?? $item->escopo_complementar;
        $descricao = filled($descricao) ? $descricao : ($estado['escopo'] ?? $item->escopo);
        $descricao = filled($descricao) ? $descricao : $asEscopo?->escopo;

        return (string) Str::of((string) $descricao)->trim();
    }

    /**
     * @param  array<string, mixed>  $estado
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}>
     */
    protected function descricaoItensPadraoGeracaoAs(ControleNotaFiscalItem $item, array $estado): array
    {
        return [[
            'descricao_tipo' => 'texto',
            'descricao' => $this->descricaoPadraoGeracaoAs($item, $estado),
            'descricao_arquivo' => [],
        ]];
    }

    /**
     * @param  array<string, mixed>  $estado
     * @return array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}
     */
    protected function descricaoItemPdfExistente(AutorizacaoServico $autorizacaoServico, ControleNotaFiscalItem $item, array $estado): array
    {
        $descricoes = $this->normalizarEstadoDescricaoItensGerarAs((array) $autorizacaoServico->itens_descricao_servico_pdf);
        $descricao = $descricoes[0] ?? null;

        if (is_array($descricao) && (filled($descricao['descricao'] ?? null) || ($descricao['descricao_arquivo'] ?? []) !== [])) {
            return $descricao;
        }

        $texto = filled($autorizacaoServico->descricao_servico_pdf)
            ? trim((string) $autorizacaoServico->descricao_servico_pdf)
            : $this->descricaoPadraoGeracaoAs($item, $estado);

        return [
            'descricao_tipo' => 'texto',
            'descricao' => $texto,
            'descricao_arquivo' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $estado
     * @return array<int|string, mixed>
     */
    protected function estadoDescricaoGeracaoAs(array $estado): array
    {
        if (array_key_exists('descricao_servico_pdf', $estado) || array_key_exists('descricao_arquivo', $estado)) {
            return [[
                'descricao' => $estado['descricao_servico_pdf'] ?? null,
                'descricao_arquivo' => Arr::wrap($estado['descricao_arquivo'] ?? []),
            ]];
        }

        return (array) ($estado['itens_descricao_servico_pdf'] ?? $this->gerarAsDescricaoItens);
    }

    /**
     * @param  array<int|string, mixed>  $itens
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}>
     */
    protected function normalizarEstadoDescricaoItensGerarAs(array $itens): array
    {
        $normalizados = collect($itens)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->take(1)
            ->map(function (array $item): array {
                $descricaoArquivos = array_values(array_filter(Arr::wrap($item['descricao_arquivo'] ?? [])));

                return [
                    'descricao_tipo' => $descricaoArquivos === [] ? 'texto' : 'arquivo',
                    'descricao' => filled($item['descricao'] ?? null) ? trim((string) $item['descricao']) : null,
                    'descricao_arquivo' => array_slice($descricaoArquivos, 0, 1),
                ];
            })
            ->values()
            ->all();

        return $normalizados !== [] ? $normalizados : [[
            'descricao_tipo' => 'texto',
            'descricao' => null,
            'descricao_arquivo' => [],
        ]];
    }

    /**
     * @param  array<int|string, mixed>|string|null  $arquivos
     * @return array<string, string>
     */
    protected function estadoUploadArquivos(array|string|null $arquivos): array
    {
        return collect(Arr::wrap($arquivos))
            ->filter(fn (mixed $arquivo): bool => filled($arquivo))
            ->mapWithKeys(fn (mixed $arquivo): array => [(string) Str::uuid() => (string) $arquivo])
            ->all();
    }

    /**
     * @return array{data_inicio_servico: ?string, data_termino_servico: ?string, data_entrega_material: ?string, desconto_autorizacao_servico: float, descricao_servico_pdf: ?string, itens_descricao_servico_pdf: array<int, array<string, mixed>>, anexos_autorizacao_servico: array<int, string>}
     */
    protected function datasGeracaoAs(): array
    {
        $pdfFormData = $this->gerarAsPdfFormData ?? [];

        return [
            'data_inicio_servico' => $this->gerarAsDataInicio,
            'data_termino_servico' => $this->gerarAsDataTermino,
            'data_entrega_material' => $this->gerarAsDataEntrega,
            'desconto_autorizacao_servico' => $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0,
            'valor_fechado' => $this->valorLiquido($this->gerarAsValorFechado, $this->parseMoedaBr($this->gerarAsDesconto) ?? 0.0),
            'valor_total_autorizacao_servico' => $this->gerarAsValorFechado,
            'descricao_servico_pdf' => $this->gerarAsDescricaoServicoPdf,
            'itens_descricao_servico_pdf' => $this->gerarAsDescricaoItens,
            'anexos_autorizacao_servico' => array_values(array_filter((array) ($pdfFormData['anexos_autorizacao_servico'] ?? []))),
        ];
    }

    /**
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    protected function normalizarParcelasFormulario(float $valorFechado): array
    {
        $parcelamento = collect($this->gerarAsParcelas)
            ->map(function (array $parcela, int $indice): array {
                return [
                    'parcela' => filled($parcela['parcela'] ?? null)
                        ? (string) $parcela['parcela']
                        : $this->nomeParcelaPadrao($indice + 1),
                    'percentual' => $this->parseNumeroBr($parcela['percentual'] ?? null) ?? 0.0,
                    'valor' => 0.0,
                    'observacao' => (string) ($parcela['observacao'] ?? ''),
                ];
            })
            ->all();

        $somaValores = 0.0;
        $ultimoIndiceComPercentual = null;

        foreach ($parcelamento as $indice => $parcela) {
            $valor = round($valorFechado * ($parcela['percentual'] / 100), 2);
            $parcelamento[$indice]['valor'] = $valor;
            $somaValores += $valor;

            if ($parcela['percentual'] > 0) {
                $ultimoIndiceComPercentual = $indice;
            }
        }

        if ($ultimoIndiceComPercentual !== null) {
            $parcelamento[$ultimoIndiceComPercentual]['valor'] = round(
                $parcelamento[$ultimoIndiceComPercentual]['valor'] + ($valorFechado - $somaValores),
                2,
            );
        }

        return app(AutorizacaoServicoFluxoService::class)->normalizarParcelamento($parcelamento, $valorFechado);
    }

    protected function valorLiquido(float $valorFechado, float $desconto): float
    {
        return max(round($valorFechado - max($desconto, 0), 2), 0.0);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    protected function valorEstimadoValido(ControleNotaFiscalItem $item, array $dados): bool
    {
        $estado = array_replace($this->itens[$item->id] ?? [], $dados);

        $valorEstimado = array_key_exists('valor_estimado', $estado)
            ? $this->parseMoedaBr($estado['valor_estimado'] ?? null)
            : (filled($item->valor_estimado_as) ? (float) $item->valor_estimado_as : null);

        return $valorEstimado !== null && $valorEstimado > 0;
    }

    protected function nomeParcelaPadrao(int $indice): string
    {
        return 'Parcela '.$indice;
    }

    /**
     * @param  array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>  $parcelas
     */
    protected function nomeProximaParcelaDisponivel(array $parcelas, ?int $ignorarIndice = null): string
    {
        $numerosUsados = collect($parcelas)
            ->except($ignorarIndice === null ? [] : [$ignorarIndice])
            ->map(fn (array $parcela): ?int => $this->numeroDaParcela($parcela['parcela'] ?? ''))
            ->filter()
            ->values()
            ->all();

        $proximo = 1;

        while (in_array($proximo, $numerosUsados, true)) {
            $proximo++;
        }

        return $this->nomeParcelaPadrao($proximo);
    }

    protected function numeroDaParcela(string $parcela): ?int
    {
        return preg_match('/^Parcela\s+(\d+)$/i', trim($parcela), $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    /**
     * @param  array<int, mixed>  $parcelas
     * @return array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>
     */
    protected function normalizarEstadoParcelasGerarAs(array $parcelas): array
    {
        return collect($parcelas)
            ->filter(fn (mixed $parcela): bool => is_array($parcela))
            ->values()
            ->map(fn (array $parcela, int $indice): array => [
                'parcela' => filled($parcela['parcela'] ?? null)
                    ? (string) $parcela['parcela']
                    : $this->nomeParcelaPadrao($indice + 1),
                'percentual' => filled($parcela['percentual'] ?? null)
                    ? (string) $parcela['percentual']
                    : '0,00',
                'valor' => filled($parcela['valor'] ?? null)
                    ? (string) $parcela['valor']
                    : '0,00',
                'observacao' => (string) ($parcela['observacao'] ?? ''),
            ])
            ->all();
    }

    protected function atualizarValorParcelaJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorFechado = parseNumero($get('../../valor_total_autorizacao_servico'));
            const desconto = parseNumero($get('../../desconto_autorizacao_servico'));
            const valorLiquido = Math.max(valorFechado - Math.max(desconto, 0), 0);
            const percentual = parseNumero($state);

            $set('valor', formatMoeda(Math.round((valorLiquido * (percentual / 100)) * 100) / 100));
        JS;
    }

    protected function atualizarValoresParcelasJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorInicial = parseNumero($get('valor_inicial'));
            const desconto = parseNumero($get('desconto_autorizacao_servico'));
            const valorLiquido = Math.max(valorInicial - Math.max(desconto, 0), 0);

            $set('total_apos_desconto', formatMoeda(valorLiquido));

            Object.entries($get('parcelamento') || {}).forEach(([chave, parcela]) => {
                const percentual = parseNumero(parcela?.percentual);
                const valor = Math.round((valorLiquido * (percentual / 100)) * 100) / 100;

                $set(`parcelamento.${chave}.valor`, formatMoeda(valor));
            });
        JS;
    }

    protected function ocultarAvisoPercentualParcelamentoJs(): string
    {
        return <<<'JS'
            (() => {
                const parseNumero = (valor) => {
                    if (valor === null || valor === undefined || valor === '') {
                        return 0;
                    }

                    if (typeof valor === 'number') {
                        return valor;
                    }

                    const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                    const numero = Number(normalizado);

                    return Number.isFinite(numero) ? numero : 0;
                };

                const total = Object.values($get('parcelamento') || {}).reduce((soma, parcela) => {
                    return soma + parseNumero(parcela?.percentual);
                }, 0);

                return total <= 100;
            })()
        JS;
    }

    protected function parseNumeroBr(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(['.', ','], ['', '.'], (string) $valor);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    protected function itemService(): ControleAutorizacaoServicoItemService
    {
        return app(ControleAutorizacaoServicoItemService::class);
    }

    protected function notificar(Notification $notification): void
    {
        $notification->send();

        if ($user = Auth::user()) {
            $notification->sendToDatabase($user);
        }
    }
}
