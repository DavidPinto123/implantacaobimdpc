<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Obras\Pages\ViewObra;
use App\Models\ObraEntregaContratual;
use App\Models\Obras;
use App\Models\Status;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use UnitEnum;

class EntregaContratual extends Page
{
    use HasPageShield;
    use WithPagination;

    private const CONTEXTO_STATUS = 'entrega_contratual_status';

    private const CONTEXTO_PREVISTO = 'entrega_contratual_previsto';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?string $navigationLabel = 'Entrega Contratual';

    protected static ?string $title = 'Entrega Contratual';

    protected static ?string $slug = 'entregas-contratuais';

    protected string $view = 'filament.pages.entrega-contratual';

    /** @var array<int, int> */
    public array $obrasExpandidas = [];

    /** @var array<int, int> */
    public array $entregasSelecionadas = [];

    public string $busca = '';

    public int $porPagina = 25;

    /** @var array<string, string> slug => nome */
    public array $statusOptions = [];

    /** @var array<string, string> slug => nome */
    public array $previstoOptions = [];

    /** @var array<string, string> slug => cor hex */
    public array $statusColors = [];

    /** @var array<string, string> slug => cor hex */
    public array $previstoColors = [];

    /** @var array<string, bool> slug => is_protected */
    public array $statusProtected = [];

    /** @var array<string, bool> slug => is_protected */
    public array $previstoProtected = [];

    /** @var array<string, ?string> slug => tipo_custo (contrato|sem_contrato|nenhum|null) */
    public array $previstoTipoCusto = [];

    public bool $abrirModalAdicionarStatus = false;

    public bool $abrirModalAdicionarPrevisto = false;

    public string $novoStatusNome = '';

    public string $novoStatusCor = '#10b981';

    public string $novoPrevistoNome = '';

    public string $novoPrevistoCor = '#10b981';

    public string $novoPrevistoTipoCusto = 'nenhum';

    /** @var array<string, string>|null */
    protected ?array $obraSelectOptionsCache = null;

    protected ?int $obraSelectDefaultIdCache = null;

    /** @var array<int, Collection<int, ObraEntregaContratual>> */
    protected array $entregasPorObraCache = [];

    public function mount(): void
    {
        $this->carregarStatusOptions();
        $this->carregarPrevistoOptions();
    }

    private function carregarStatusOptions(): void
    {
        $status = Status::ativosPorContexto(self::CONTEXTO_STATUS);

        $this->statusOptions = $status->mapWithKeys(fn (Status $s) => [$s->slug => $s->nome])->all();
        $this->statusColors = $status->mapWithKeys(fn (Status $s) => [$s->slug => $s->cor])->all();
        $this->statusProtected = $status->mapWithKeys(fn (Status $s) => [$s->slug => (bool) $s->is_protected])->all();
    }

    private function carregarPrevistoOptions(): void
    {
        $previsto = Status::ativosPorContexto(self::CONTEXTO_PREVISTO);

        $this->previstoOptions = $previsto->mapWithKeys(fn (Status $s) => [$s->slug => $s->nome])->all();
        $this->previstoColors = $previsto->mapWithKeys(fn (Status $s) => [$s->slug => $s->cor])->all();
        $this->previstoProtected = $previsto->mapWithKeys(fn (Status $s) => [$s->slug => (bool) $s->is_protected])->all();
        $this->previstoTipoCusto = $previsto->mapWithKeys(fn (Status $s) => [$s->slug => $s->tipo_custo])->all();
    }

    #[On('confirmarMudancaPrevisto')]
    public function handleConfirmarMudancaPrevisto(int $entregaId, string $novoSlug): void
    {
        $this->confirmarMudancaPrevisto($entregaId, $novoSlug);
    }

    #[On('openAdicionarStatusModal')]
    public function abrirModalStatus(): void
    {
        $this->abrirModalAdicionarStatus = true;
    }

    #[On('openAdicionarPrevistoModal')]
    public function abrirModalPrevisto(): void
    {
        $this->abrirModalAdicionarPrevisto = true;
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

    public function getObrasProperty(): LengthAwarePaginator
    {
        $query = Obras::query()
            ->select(['id', 'projeto_id', 'codigo', 'unidade'])
            ->with(['projeto:id,sigla,nova_sigla'])
            ->withCount('entregasContratuais')
            ->withSum('entregasContratuais as entregas_custo_contrato', 'custo_contrato')
            ->withSum('entregasContratuais as entregas_custo_sem_contrato', 'custo_sem_contrato')
            ->whereHas('entregasContratuais')
            ->orderBy('codigo');

        if ($this->busca !== '') {
            $termo = '%'.$this->busca.'%';
            $query->where(function ($q) use ($termo): void {
                $q->where('unidade', 'like', $termo)
                    ->orWhere('codigo', 'like', $termo)
                    ->orWhereHas('projeto', function ($pq) use ($termo): void {
                        $pq->where('sigla', 'like', $termo)
                            ->orWhere('nova_sigla', 'like', $termo);
                    });
            });
        }

        return $query->paginate($this->porPagina);
    }

    /**
     * @return array<string, string>
     */
    protected function getObraSelectOptions(): array
    {
        if ($this->obraSelectOptionsCache !== null) {
            return $this->obraSelectOptionsCache;
        }

        $this->obraSelectOptionsCache = Obras::query()
            ->select(['id', 'projeto_id', 'codigo', 'unidade'])
            ->with('projeto:id,sigla,nova_sigla')
            ->orderBy('codigo')
            ->get()
            ->mapWithKeys(function (Obras $obra): array {
                $rotulo = trim(sprintf(
                    '%s — %s — %s',
                    $obra->codigo ?? '—',
                    $obra->projeto?->sigla ?? '—',
                    $obra->unidade ?? '—',
                ));

                return [(string) $obra->id => $rotulo];
            })
            ->all();

        $firstKey = array_key_first($this->obraSelectOptionsCache);
        $this->obraSelectDefaultIdCache = is_string($firstKey) || is_int($firstKey) ? (int) $firstKey : null;

        return $this->obraSelectOptionsCache;
    }

    protected function getObraSelectDefaultId(): ?int
    {
        if ($this->obraSelectDefaultIdCache !== null) {
            return $this->obraSelectDefaultIdCache;
        }

        $options = $this->getObraSelectOptions();
        $firstKey = array_key_first($options);

        return is_string($firstKey) || is_int($firstKey) ? (int) $firstKey : null;
    }

    /**
     * @return Collection<int, ObraEntregaContratual>
     */
    public function entregasDaObra(int $obraId): Collection
    {
        if (! array_key_exists($obraId, $this->entregasPorObraCache)) {
            $this->entregasPorObraCache[$obraId] = ObraEntregaContratual::query()
                ->select([
                    'id',
                    'obra_id',
                    'tipo',
                    'entrega',
                    'descricao_entrega',
                    'descricao_existente',
                    'status',
                    'data_entrega',
                    'custo_estimado',
                    'previsto_em_contrato',
                    'previsto_status',
                    'custo_contrato',
                    'custo_sem_contrato',
                    'observacoes',
                    'sort_order',
                ])
                ->where('obra_id', $obraId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return $this->entregasPorObraCache[$obraId];
    }

    protected function limparCacheObra(int $obraId): void
    {
        unset($this->entregasPorObraCache[$obraId]);
    }

    public function toggleObra(int $obraId): void
    {
        if (in_array($obraId, $this->obrasExpandidas, true)) {
            $this->obrasExpandidas = array_values(array_diff($this->obrasExpandidas, [$obraId]));

            return;
        }

        $this->obrasExpandidas[] = $obraId;
    }

    public function podeGerenciarStatus(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (! $user->hasRole('Coordenador')) {
            return false;
        }

        return $user->setores()->where('setor', 'Obras')->exists();
    }

    public function corStatus(?string $status): string
    {
        if ($status !== null && isset($this->statusColors[$status])) {
            return $this->statusColors[$status];
        }

        return $this->statusColors['nao_entregue'] ?? '#ef4444';
    }

    public function corPrevisto(?string $slug): string
    {
        if ($slug !== null && isset($this->previstoColors[$slug])) {
            return $this->previstoColors[$slug];
        }

        return $this->previstoColors['previsto_nao'] ?? '#ef4444';
    }

    public function tipoCustoDoPrevisto(?string $slug): ?string
    {
        if ($slug === null) {
            return null;
        }

        return $this->previstoTipoCusto[$slug] ?? null;
    }

    public function urlViewObra(int $obraId): string
    {
        return ViewObra::getUrl(['record' => $obraId]);
    }

    public function adicionarEntrega(int $obraId): void
    {
        $obra = Obras::find($obraId);

        if (! $obra) {
            return;
        }

        $proximoSort = (int) ($obra->entregasContratuais()->max('sort_order') ?? 0) + 1;

        ObraEntregaContratual::create([
            'obra_id' => $obra->id,
            'entrega' => 'Nova entrega',
            'descricao_entrega' => null,
            'descricao_existente' => null,
            'status' => 'nao_entregue',
            'data_entrega' => null,
            'custo_estimado' => 0,
            'previsto_em_contrato' => false,
            'previsto_status' => 'previsto_nao',
            'sort_order' => $proximoSort,
        ]);

        if (! in_array($obra->id, $this->obrasExpandidas, true)) {
            $this->obrasExpandidas[] = $obra->id;
        }

        $this->limparCacheObra($obra->id);

        Notification::make()
            ->title('Entrega adicionada')
            ->success()
            ->send();
    }

    public function atualizarEntrega(int $entregaId, string $campo, mixed $valor): void
    {
        $camposPermitidos = ['tipo', 'entrega', 'descricao_entrega', 'descricao_existente', 'status', 'data_entrega', 'previsto_status', 'custo_contrato', 'custo_sem_contrato', 'custo_estimado', 'observacoes'];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        $entrega = ObraEntregaContratual::find($entregaId);

        if (! $entrega) {
            return;
        }

        if ($campo === 'status' && ! array_key_exists($valor, $this->statusOptions)) {
            return;
        }

        if ($campo === 'previsto_status' && ! array_key_exists($valor, $this->previstoOptions)) {
            return;
        }

        if (in_array($campo, ['custo_estimado', 'custo_contrato', 'custo_sem_contrato'], true)) {
            $valor = $this->parseMoedaBr($valor) ?? 0;
            if ($valor < 0) {
                $valor = 0;
            }
        }

        if ($campo === 'previsto_status') {
            // A confirmação modal (com zeragem do oposto) é despachada pelo front via
            // ecMostrarConfirmacaoPrevisto → confirmarMudancaPrevisto. Quando chega aqui é
            // porque o front decidiu aplicar direto (sem valor a zerar). Mantemos a notificação
            // de fallback se o front errar e enviar com valores conflitantes.
            $tipoCustoNovo = $this->previstoTipoCusto[$valor] ?? null;
            $temConflitoContrato = $tipoCustoNovo === 'contrato' && (float) $entrega->custo_sem_contrato > 0;
            $temConflitoSemContrato = $tipoCustoNovo === 'sem_contrato' && (float) $entrega->custo_contrato > 0;
            $temConflitoNenhum = $tipoCustoNovo === 'nenhum' && ((float) $entrega->custo_contrato > 0 || (float) $entrega->custo_sem_contrato > 0);

            if ($temConflitoContrato || $temConflitoSemContrato || $temConflitoNenhum) {
                Notification::make()
                    ->title('Confirme pela janela de aviso')
                    ->body('Existem valores em custo que precisam ser zerados — use o botão Confirmar do modal.')
                    ->warning()
                    ->send();

                return;
            }

            $this->aplicarMudancaPrevisto($entrega, $valor);
            $this->limparCacheObra((int) $entrega->obra_id);

            return;
        }

        if (in_array($campo, ['tipo', 'descricao_entrega', 'descricao_existente', 'observacoes'], true)) {
            $valor = $valor === '' ? null : $valor;
        }

        if ($campo === 'entrega') {
            $valor = $valor === '' || $valor === null ? 'Nova entrega' : $valor;
        }

        if ($campo === 'data_entrega') {
            // Não permite gravar data se a entrega estiver marcada como "não entregue"
            if ($entrega->status === 'nao_entregue') {
                $valor = null;
            } else {
                $valor = $valor === '' || $valor === null ? null : $valor;
            }
        }

        $dados = [$campo => $valor];

        // Ao trocar status para "não entregue", zera a data automaticamente
        if ($campo === 'status' && $valor === 'nao_entregue') {
            $dados['data_entrega'] = null;
        }

        $entrega->update($dados);
        $this->limparCacheObra((int) $entrega->obra_id);
    }

    public function confirmarMudancaPrevisto(int $entregaId, string $novoSlug): void
    {
        $entrega = ObraEntregaContratual::find($entregaId);

        if (! $entrega) {
            return;
        }

        if (! array_key_exists($novoSlug, $this->previstoOptions)) {
            return;
        }

        $this->aplicarMudancaPrevisto($entrega, $novoSlug);
        $this->limparCacheObra((int) $entrega->obra_id);

        Notification::make()
            ->title('Atualização realizada com sucesso')
            ->success()
            ->send();
    }

    private function aplicarMudancaPrevisto(ObraEntregaContratual $entrega, string $novoSlug): void
    {
        $tipoCusto = $this->previstoTipoCusto[$novoSlug] ?? null;

        $dados = [
            'previsto_status' => $novoSlug,
            // Mantém o booleano legado em sincronia para retrocompatibilidade com leituras
            // antigas (ViewObra, relatórios) enquanto a migração para 'previsto_status' não conclui.
            'previsto_em_contrato' => $tipoCusto === 'contrato',
        ];

        if ($tipoCusto === 'contrato') {
            $dados['custo_sem_contrato'] = 0;
        } elseif ($tipoCusto === 'sem_contrato') {
            $dados['custo_contrato'] = 0;
        } elseif ($tipoCusto === 'nenhum') {
            $dados['custo_contrato'] = 0;
            $dados['custo_sem_contrato'] = 0;
        }

        $entrega->update($dados);
    }

    public function submeterNovoStatus(): void
    {
        if (! $this->podeGerenciarStatus()) {
            Notification::make()->title('Sem permissão para adicionar status')->danger()->send();

            return;
        }

        if (blank($this->novoStatusNome) || blank($this->novoStatusCor)) {
            Notification::make()->title('Preencha todos os campos')->warning()->send();

            return;
        }

        $this->criarNovoStatusContexto(self::CONTEXTO_STATUS, $this->novoStatusNome, $this->novoStatusCor);

        $this->abrirModalAdicionarStatus = false;
        $this->novoStatusNome = '';
        $this->novoStatusCor = '#10b981';
    }

    public function submeterNovoPrevisto(): void
    {
        if (! $this->podeGerenciarStatus()) {
            Notification::make()->title('Sem permissão para adicionar status')->danger()->send();

            return;
        }

        if (blank($this->novoPrevistoNome) || blank($this->novoPrevistoCor)) {
            Notification::make()->title('Preencha todos os campos')->warning()->send();

            return;
        }

        if (! in_array($this->novoPrevistoTipoCusto, ['contrato', 'sem_contrato', 'nenhum'], true)) {
            Notification::make()->title('Tipo de custo inválido')->warning()->send();

            return;
        }

        $this->criarNovoStatusContexto(
            self::CONTEXTO_PREVISTO,
            $this->novoPrevistoNome,
            $this->novoPrevistoCor,
            $this->novoPrevistoTipoCusto,
        );

        $this->abrirModalAdicionarPrevisto = false;
        $this->novoPrevistoNome = '';
        $this->novoPrevistoCor = '#10b981';
        $this->novoPrevistoTipoCusto = 'nenhum';
    }

    private function criarNovoStatusContexto(string $contexto, string $nome, string $cor, ?string $tipoCusto = null): void
    {
        $nomeNormalizado = strtoupper(trim($nome));

        if ($nomeNormalizado === '') {
            return;
        }

        $slug = Str::of($nomeNormalizado)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($slug === '') {
            Notification::make()->title('Nome inválido para gerar slug')->warning()->send();

            return;
        }

        $jaExiste = Status::query()
            ->where('contexto', $contexto)
            ->where(function ($q) use ($slug, $nomeNormalizado): void {
                $q->where('slug', $slug)->orWhere('nome', $nomeNormalizado);
            })
            ->exists();

        if ($jaExiste) {
            Notification::make()->title('Status já existe')->warning()->send();

            return;
        }

        $maxOrdem = (int) (Status::query()->where('contexto', $contexto)->max('ordem') ?? 0);

        Status::create([
            'contexto' => $contexto,
            'slug' => $slug,
            'nome' => $nomeNormalizado,
            'cor' => $cor,
            'ordem' => $maxOrdem + 1,
            'is_active' => true,
            'is_protected' => false,
            'tipo_custo' => $tipoCusto,
        ]);

        if ($contexto === self::CONTEXTO_STATUS) {
            $this->carregarStatusOptions();
        } else {
            $this->carregarPrevistoOptions();
        }

        Notification::make()->title('Status criado com sucesso')->success()->send();
    }

    public function deletarStatus(string $slug): void
    {
        $this->deletarStatusDoContexto(self::CONTEXTO_STATUS, $slug);
    }

    public function deletarPrevisto(string $slug): void
    {
        $this->deletarStatusDoContexto(self::CONTEXTO_PREVISTO, $slug);
    }

    private function deletarStatusDoContexto(string $contexto, string $slug): void
    {
        $status = Status::porSlug($contexto, $slug);

        if (! $status) {
            Notification::make()->title('Status não encontrado')->warning()->send();

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

        $coluna = $contexto === self::CONTEXTO_STATUS ? 'status' : 'previsto_status';
        $emUso = ObraEntregaContratual::query()->where($coluna, $slug)->count();

        if ($emUso > 0) {
            Notification::make()
                ->title('Não é possível deletar')
                ->body("Existem {$emUso} entrega(s) usando este status.")
                ->warning()
                ->send();

            return;
        }

        $status->delete();

        if ($contexto === self::CONTEXTO_STATUS) {
            $this->carregarStatusOptions();
        } else {
            $this->carregarPrevistoOptions();
        }

        Notification::make()->title('Status deletado com sucesso')->success()->send();
    }

    public function removerEntrega(int $entregaId): void
    {
        $entrega = ObraEntregaContratual::find($entregaId);

        if (! $entrega) {
            return;
        }

        $entrega->delete();
        $this->limparCacheObra((int) $entrega->obra_id);
        $this->entregasSelecionadas = array_values(array_diff($this->entregasSelecionadas, [$entregaId]));

        Notification::make()
            ->title('Entrega removida')
            ->success()
            ->send();
    }

    public function alternarSelecaoObra(int $obraId): void
    {
        $idsDaObra = $this->entregasDaObra($obraId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $todasSelecionadas = ! empty($idsDaObra)
            && empty(array_diff($idsDaObra, $this->entregasSelecionadas));

        if ($todasSelecionadas) {
            $this->entregasSelecionadas = array_values(array_diff($this->entregasSelecionadas, $idsDaObra));

            return;
        }

        $this->entregasSelecionadas = array_values(array_unique(array_merge($this->entregasSelecionadas, $idsDaObra)));
    }

    public function removerEntregasSelecionadas(): void
    {
        if (empty($this->entregasSelecionadas)) {
            Notification::make()
                ->title('Nenhuma entrega selecionada')
                ->warning()
                ->send();

            return;
        }

        $entregas = ObraEntregaContratual::query()
            ->whereIn('id', $this->entregasSelecionadas)
            ->get(['id', 'obra_id']);

        $obrasAfetadas = $entregas->pluck('obra_id')->unique();
        $total = $entregas->count();

        ObraEntregaContratual::query()
            ->whereIn('id', $entregas->pluck('id'))
            ->delete();

        foreach ($obrasAfetadas as $obraId) {
            $this->limparCacheObra((int) $obraId);
        }

        $this->entregasSelecionadas = [];

        Notification::make()
            ->title($total.' entrega(s) removida(s)')
            ->success()
            ->send();
    }

    public function adicionarObraAction(): Action
    {
        return Action::make('adicionarObra')
            ->label('Adicionar obra')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalHeading('Adicionar obra à Entrega Contratual')
            ->modalSubmitActionLabel('Adicionar')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('obra_id')
                    ->label('Obra')
                    ->options(fn (): array => $this->getObraSelectOptions())
                    ->searchable()
                    ->required(),
            ]))
            ->action(function (array $data): void {
                $obra = Obras::find($data['obra_id'] ?? null);

                if (! $obra) {
                    return;
                }

                $proximoSort = (int) ($obra->entregasContratuais()->max('sort_order') ?? 0) + 1;

                ObraEntregaContratual::create([
                    'obra_id' => $obra->id,
                    'entrega' => 'Nova entrega',
                    'descricao_entrega' => null,
                    'descricao_existente' => null,
                    'status' => 'nao_entregue',
                    'data_entrega' => null,
                    'custo_estimado' => 0,
                    'previsto_em_contrato' => false,
                    'previsto_status' => 'previsto_nao',
                    'sort_order' => $proximoSort,
                ]);

                if (! in_array($obra->id, $this->obrasExpandidas, true)) {
                    $this->obrasExpandidas[] = $obra->id;
                }

                Notification::make()
                    ->title('Obra adicionada')
                    ->body('Uma entrega inicial foi criada. Edite os campos pela tabela.')
                    ->success()
                    ->send();
            });
    }

    public function colarTabelaAction(): Action
    {
        return Action::make('colarTabela')
            ->label('Colar tabela')
            ->icon('heroicon-m-clipboard-document-list')
            ->color('gray')
            ->modalHeading('Colar tabela de entregas')
            ->modalDescription('Cole aqui uma tabela copiada do Excel. A primeira linha pode ser cabeçalho; se não houver, a ordem esperada das colunas é: Tipo, Entrega, Descrição da entrega, Descrição do existente, Status, Data de entrega, Observações. As entregas serão criadas como N/A em "Previsto em contrato?" e com custos zerados.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Importar entregas')
            ->fillForm(fn (): array => [
                'obra_id' => $this->getObraSelectDefaultId(),
                'tabela_colada' => '',
            ])
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('obra_id')
                    ->label('Obra destino')
                    ->options(fn (): array => $this->getObraSelectOptions())
                    ->searchable()
                    ->required(),

                Textarea::make('tabela_colada')
                    ->label('Tabela colada')
                    ->rows(12)
                    ->required()
                    ->placeholder("Tipo\tEntrega\tDescrição da entrega\tDescrição do existente\tStatus\tData de entrega\tObservações\nInfra de Elétrica\tEnergia Elétrica\t150 KVA's trifásico...\t\tENTREGUE\t08/05/26\tSF providenciar aumento de carga")
                    ->helperText('Aceita TAB, linhas separadas por quebra de linha e cabeçalhos opcionais.')
                    ->columnSpanFull(),
            ]))
            ->action(function (array $data): void {
                $obraId = (int) ($data['obra_id'] ?? 0);
                $texto = trim((string) ($data['tabela_colada'] ?? ''));

                if ($obraId <= 0 || $texto === '') {
                    Notification::make()
                        ->title('Informe a obra e cole a tabela.')
                        ->warning()
                        ->send();

                    return;
                }

                $obra = Obras::find($obraId);

                if (! $obra) {
                    Notification::make()
                        ->title('Obra não encontrada.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $resultado = $this->importarEntregasColadas($obra, $texto);
                } catch (\Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('Não foi possível importar a tabela.')
                        ->danger()
                        ->body('Verifique o formato colado e tente novamente.')
                        ->send();

                    return;
                }

                if ($resultado['criados'] === 0) {
                    Notification::make()
                        ->title('Nenhuma entrega foi importada.')
                        ->warning()
                        ->body('Verifique se o conteúdo colado tem linhas válidas.')
                        ->send();

                    return;
                }

                $this->resetPage();
                if (! in_array($obra->id, $this->obrasExpandidas, true)) {
                    $this->obrasExpandidas[] = $obra->id;
                }

                Notification::make()
                    ->title($resultado['criados'].' entrega(s) importada(s)')
                    ->success()
                    ->body($resultado['ignoradas'] > 0
                        ? $resultado['ignoradas'].' linha(s) vazia(s) ou inválida(s) foram ignoradas.'
                        : 'As entregas foram criadas a partir da tabela colada.')
                    ->send();
            });
    }


    private function importarEntregasColadas(Obras $obra, string $texto): array
    {
        return DB::transaction(function () use ($obra, $texto): array {
            $linhas = $this->parseTabelaColada($texto);

            if ($linhas === []) {
                return ['criados' => 0, 'ignoradas' => 0];
            }

            $primeiraLinha = $linhas[0];
            $mapaCabecalho = $this->detectarCabecalhoTabelaColada($primeiraLinha);
            $temCabecalho = $mapaCabecalho !== [];
            $dadosLinhas = $temCabecalho ? array_slice($linhas, 1) : $linhas;
            $proximoSort = (int) ($obra->entregasContratuais()->max('sort_order') ?? 0) + 1;
            $criados = 0;
            $ignoradas = 0;

            foreach ($dadosLinhas as $campos) {
                $dados = $temCabecalho
                    ? $this->mapearLinhaPeloCabecalho($campos, $mapaCabecalho)
                    : $this->mapearLinhaPorPosicao($campos);

                if (! $this->linhaImportadaTemConteudo($dados)) {
                    $ignoradas++;
                    continue;
                }

                $tipo = trim((string) ($dados['tipo'] ?? ''));
                $entrega = trim((string) ($dados['entrega'] ?? ''));
                $descricaoEntrega = trim((string) ($dados['descricao_entrega'] ?? ''));
                $descricaoExistente = trim((string) ($dados['descricao_existente'] ?? ''));
                $observacoes = trim((string) ($dados['observacoes'] ?? ''));
                $status = $this->normalizarStatusEntrega($dados['status'] ?? null);
                $dataEntrega = $this->normalizarDataEntrega($dados['data_entrega'] ?? null);

                if ($status === null) {
                    $status = $dataEntrega !== null ? 'entregue' : 'nao_entregue';
                }

                if ($status === 'nao_entregue') {
                    $dataEntrega = null;
                }

                ObraEntregaContratual::create([
                    'obra_id' => $obra->id,
                    'tipo' => $tipo !== '' ? $tipo : null,
                    'entrega' => $entrega !== '' ? $entrega : 'Nova entrega',
                    'descricao_entrega' => $descricaoEntrega !== '' ? $descricaoEntrega : null,
                    'descricao_existente' => $descricaoExistente !== '' ? $descricaoExistente : null,
                    'status' => $status,
                    'data_entrega' => $dataEntrega,
                    'observacoes' => $observacoes !== '' ? $observacoes : null,
                    'custo_estimado' => 0,
                    'custo_contrato' => 0,
                    'custo_sem_contrato' => 0,
                    'previsto_em_contrato' => false,
                    'previsto_status' => 'previsto_na',
                    'sort_order' => $proximoSort,
                ]);

                $proximoSort++;
                $criados++;
            }

            return ['criados' => $criados, 'ignoradas' => $ignoradas];
        });
    }

    private function parseTabelaColada(string $texto): array
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/u', '', trim($texto)) ?? trim($texto);

        if ($texto === '') {
            return [];
        }

        $delimitador = $this->detectarDelimitadorTabelaColada($texto);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return [];
        }

        fwrite($handle, $texto);
        rewind($handle);

        $linhas = [];

        while (($campos = fgetcsv($handle, 0, $delimitador, '"', "\\")) !== false) {
            if ($campos === [null] || $campos === false) {
                continue;
            }

            $campos = array_map(
                static fn ($valor): string => trim((string) $valor),
                $campos,
            );

            if ($campos === [] || collect($campos)->filter(fn (string $valor): bool => $valor !== '')->isEmpty()) {
                continue;
            }

            $linhas[] = $campos;
        }

        fclose($handle);

        return $linhas;
    }

    private function detectarDelimitadorTabelaColada(string $texto): string
    {
        $amostra = substr($texto, 0, 4096);
        $contagens = [
            "\t" => substr_count($amostra, "\t"),
            ';' => substr_count($amostra, ';'),
            '|' => substr_count($amostra, '|'),
        ];

        arsort($contagens);

        $delimitador = array_key_first($contagens);

        return is_string($delimitador) && $contagens[$delimitador] > 0 ? $delimitador : "\t";
    }

    private function splitTabelaColadaLinha(string $linha): array
    {
        $linha = preg_replace('/^\xEF\xBB\xBF/u', '', trim($linha)) ?? trim($linha);

        if ($linha === '') {
            return [];
        }

        if (str_contains($linha, "\t")) {
            return array_values(array_map('trim', explode("\t", $linha)));
        }

        if (str_contains($linha, ';')) {
            return array_values(array_map('trim', str_getcsv($linha, ';')));
        }

        if (str_contains($linha, '|')) {
            return array_values(array_map('trim', str_getcsv($linha, '|')));
        }

        if (preg_match('/\s{2,}/', $linha) === 1) {
            return array_values(array_filter(array_map('trim', preg_split('/\s{2,}/', $linha) ?: []), fn (string $valor): bool => $valor !== ''));
        }

        return [$linha];
    }

    private function detectarCabecalhoTabelaColada(array $campos): array
    {
        $mapa = [];

        foreach ($campos as $indice => $campo) {
            $normalizado = $this->normalizarChaveTabelaColada($campo);

            $coluna = match (true) {
                $normalizado === 'tipo',
                $normalizado === 'categoria',
                $normalizado === 'codigo' => 'tipo',
                $normalizado === 'entrega',
                $normalizado === 'nomeentrega',
                $normalizado === 'entregacontratual',
                $normalizado === 'sigla' => 'entrega',
                $normalizado === 'descricaoentrega',
                $normalizado === 'descricaodaentrega',
                $normalizado === 'descricao',
                $normalizado === 'unidade' => 'descricao_entrega',
                $normalizado === 'descricaoexistente',
                $normalizado === 'descricaodoexistente' => 'descricao_existente',
                $normalizado === 'status' => 'status',
                $normalizado === 'dataentrega',
                $normalizado === 'datadeentrega' => 'data_entrega',
                $normalizado === 'observacoes',
                $normalizado === 'observacao',
                $normalizado === 'obs' => 'observacoes',
                default => null,
            };

            if ($coluna !== null) {
                $mapa[$indice] = $coluna;
            }
        }

        return count($mapa) >= 2 ? $mapa : [];
    }

    private function mapearLinhaPeloCabecalho(array $campos, array $mapaCabecalho): array
    {
        $dados = [
            'tipo' => null,
            'entrega' => null,
            'descricao_entrega' => null,
            'descricao_existente' => null,
            'status' => null,
            'data_entrega' => null,
            'observacoes' => null,
        ];

        foreach ($mapaCabecalho as $indice => $coluna) {
            $dados[$coluna] = $campos[$indice] ?? null;
        }

        return $dados;
    }

    private function mapearLinhaPorPosicao(array $campos): array
    {
        $colunas = [
            'tipo',
            'entrega',
            'descricao_entrega',
            'descricao_existente',
            'status',
            'data_entrega',
            'observacoes',
        ];

        $dados = [];
        foreach ($colunas as $indice => $coluna) {
            $dados[$coluna] = $campos[$indice] ?? null;
        }

        return $dados;
    }

    private function linhaImportadaTemConteudo(array $dados): bool
    {
        foreach ($dados as $valor) {
            if (filled($valor)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarChaveTabelaColada(string $texto): string
    {
        $texto = Str::ascii(mb_strtolower(trim($texto)));

        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }

    private function normalizarStatusEntrega(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $normalizado = $this->normalizarChaveTabelaColada((string) $valor);

        if ($normalizado === 'entregue') {
            return 'entregue';
        }

        if (str_contains($normalizado, 'parcial')) {
            return 'entregue_parcial';
        }

        if (
            str_contains($normalizado, 'naoentregue')
            || str_contains($normalizado, 'naoentrega')
            || str_contains($normalizado, 'naoseraentregue')
            || str_contains($normalizado, 'pendente')
            || str_contains($normalizado, 'aguardando')
        ) {
            return 'nao_entregue';
        }

        return null;
    }

    private function normalizarDataEntrega(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return Carbon::instance($valor)->format('Y-m-d');
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        if (is_numeric($texto) && preg_match('/^\d+$/', $texto) === 1) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((int) $texto))->format('Y-m-d');
            } catch (\Throwable) {
                // segue para tentativa de parse textual
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/Y H:i', 'd/m/Y H:i:s'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto)->format('Y-m-d');
            } catch (\Throwable) {
                // tenta o próximo formato
            }
        }

        try {
            return Carbon::parse($texto)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
}

