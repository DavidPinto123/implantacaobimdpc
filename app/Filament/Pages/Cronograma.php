<?php

namespace App\Filament\Pages;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\ModoAncoraCronograma;
use App\Enums\MotivoAlteracaoObra;
use App\Enums\StatusCronograma;
use App\Enums\TipoDiasTemplate;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseDependencia;
use App\Models\CronogramaFaseHistorico;
use App\Models\CronogramaFaseItem;
use App\Models\CronogramaFaseItemDependencia;
use App\Models\CronogramaTemplate;
use App\Models\Estado;
use App\Models\GrupoAtividades;
use App\Models\GrupoAtividadesItem;
use App\Models\Projeto;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Services\CronogramaService;
use App\Services\CronogramaTemplateService;
use App\Support\CronogramaLimites;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

class Cronograma extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.cronograma';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Planejamento';

    protected static ?string $title = 'Planejamento';

    public function getHeading(): string
    {
        return '';
    }

    #[Url(as: 'projeto', except: null)]
    public ?int $projetoSelecionado = null;

    public ?int $editingFaseId = null;

    public string $filtroStatus = '';

    public string $filtroStatusObra = '';

    public string $filtroEstado = '';

    public string $filtroRegional = '';

    public string $busca = '';

    public string $buscaFase = '';

    public string $filtroStatusFase = '';

    public string $filtroPeriodo = '';

    public string $filtroTemplate = '';

    public ?string $editDataPrevistaInicio = null;

    public ?string $editDataPrevistaFim = null;

    public ?string $editStatus = null;

    public int $editPercentual = 0;

    public string $editObservacoes = '';

    public string $editMotivoDatas = '';

    public bool $mostrarMotivoDatas = false;

    public string $motivoLoteDatas = '';

    public bool $mostrarHistorico = false;

    public ?int $historicoFaseId = null;

    /**
     * Quando setado, o modal de histórico passa a operar para esse projeto
     * (sobrepondo o $projetoSelecionado). Permite abrir o histórico de um
     * projeto a partir da tela macro sem entrar nele.
     * Null = projeto selecionado atual; 0 = modo global (todos os projetos).
     */
    public ?int $historicoProjetoId = null;

    /** Modal de confirmação para reaplicar variante pareada após troca de modo de âncora. */
    public bool $mostrarConfirmacaoReaplicar = false;
    public ?int $varianteSugeridaId = null;
    public string $varianteSugeridaNome = '';

    public bool $mostrarVersoes = false;

    public ?string $versaoSelecionada = null;

    public bool $mostrarComentarios = false;

    public ?int $comentarioFaseId = null;

    public string $novoComentario = '';

    public ?int $novoComentarioFaseId = null;

    public bool $editFaseVisivel = true;

    public ?string $editFaseValue = null;

    /**
     * Modal de confirmação de finalização: quando o usuário muda status para
     * um dos status finais (Concluído, Realizado, Finalizado, Assinado, Pronto),
     * o sistema exige que ele preencha a data de execução (e fim, se a fase
     * tiver duração) antes de gravar.
     */
    public ?int $confirmacaoStatusFaseId = null;

    public ?string $confirmacaoStatusValue = null;

    public ?string $confirmacaoStatusLabel = null;

    public ?string $confirmacaoFaseLabel = null;

    public bool $confirmacaoFaseMarco = false;

    public ?string $confirmacaoDataRealInicio = null;

    public ?string $confirmacaoDataRealFim = null;

    /**
     * Quando true, o modal de confirmação pede apenas a data de início
     * (usado em transições para status intermediários como SOLICITADO,
     * EM_ANDAMENTO, AGENDADO, PENDENCIA_*, em que só o início é conhecido).
     */
    public bool $confirmacaoApenasInicio = false;

    /**
     * Guarda a duração original (em dias corridos) da fase sendo editada,
     * para detectar ambiguidade quando só uma das datas é alterada.
     */
    public ?int $editFaseDuracaoOriginal = null;

    /**
     * Quando true, o modal exibe um banner de confirmação perguntando se o
     * usuário quer mover a fase inteira preservando a duração ou alterar
     * exatamente as datas digitadas.
     */
    public bool $editConfirmacaoShift = false;

    // Edição de regra (override local por obra)
    public ?int $editRegraDuracaoDias = null;

    public ?string $editRegraTipoDias = null;

    public bool $editRegraCustomizada = false;

    public bool $editRegraElastica = false;

    /**
     * Repeater de dependências efetivas da fase em edição.
     *
     * @var array<int, array{alvo: string, gatilho: string, gap_dias: int}>
     */
    public array $editDependencias = [];

    public int $paginaAtual = 1;

    public int $porPagina = 50;

    public string $visualizacao = 'barras';

    public int $renderKey = 0;

    public bool $mostrarOcultas = false;

    public bool $mostrarEditorFases = false;

    public bool $mostrarModalConflitoDep = false;

    // Grupos de atividades
    public bool $modalSelecionarGrupo = false;
    public ?int $faseAlvoGrupo = null;
    public array $gruposDisponiveis = [];

    public string $acaoPendenteFase = 'ocultar';

    public ?int $faseParaNaoAplicarId = null;

    /**
     * Cada entrada: fase_id, fase_nome, dep_id (DB ou null), is_ovr, ovr_dep_idx, substituir_por (enum value ou '').
     *
     * @var list<array{fase_id:int,fase_nome:string,dep_id:int|null,is_ovr:bool,ovr_dep_idx:int|null,substituir_por:string}>
     */
    public array $fasesConflitantes = [];

    public string $faseParaNaoAplicarEnum = '';

    public ?int $templateSelecionadoParaAplicar = null;

    public ?string $templateDataAncora = null;

    public ?string $templateAncoraLabel = null;

    public ?string $templateAncoraCampo = null;

    /**
     * Modal de justificativa de alteração de Data de Posse.
     * Aparece quando aplicarTemplate vai alterar projeto.data_posse.
     */
    public bool $mostrarModalMotivoPosse = false;

    public ?string $motivoPosseCodigo = null;

    public ?string $motivoPosseHistorico = null;

    /**
     * Overrides por fase dentro do modal "Editar template nessa obra".
     * Indexado por id da fase:
     * ['duracao' => int, 'tipo_dias' => string, 'deps' => [['depende_de_fase','gatilho','gap_dias']]]
     *
     * @var array<int, array{duracao: int, tipo_dias: string, deps: array<int, array{depende_de_fase:string, gatilho:string, gap_dias:int}>}>
     */
    public array $overridesObraFases = [];

    public bool $mostrarModalDatas = false;

    public bool $mostrarConfirmacaoSalvarDatas = false;

    public bool $mostrarModalNovoPlanejamento = false;

    public string $novoPlanejamentoNome = '';

    public bool $mostrarModalNovaFase = false;

    /**
     * Edição em lote das datas de todas as fases da obra selecionada.
     * Indexado por id da fase: ['prev_i','prev_f','real_i','real_f'].
     *
     * @var array<int, array{prev_i: ?string, prev_f: ?string, real_i: ?string, real_f: ?string}>
     */
    public array $edicaoLoteDatas = [];

    public array $datasOriginaisLote = [];

    public function getViewData(): array
    {
        if ($this->projetoSelecionado) {
            return $this->getViewDataIndividual();
        }

        return $this->getViewDataMacro();
    }

    private function getViewDataMacro(): array
    {
        $cronogramaService = new CronogramaService;

        $query = Projeto::with(['cronogramaFases.template', 'cronogramaFases.templateFase', 'estado', 'obras']);

        if ($this->filtroEstado) {
            $query->where('estado_id', $this->filtroEstado);
        }

        if ($this->filtroRegional) {
            $regional = $this->filtroRegional;
            $query->where('regional', $regional);
        }

        if ($this->filtroStatus) {
            $status = $this->filtroStatus;
            $query->whereHas('cronogramaFases', fn ($q) => $q->where('status', $status));
        }

        if ($this->filtroStatusObra) {
            $query->where('status', $this->filtroStatusObra);
        }

        if ($this->busca) {
            $busca = $this->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%");
            });
        }

        if ($this->filtroPeriodo) {
            [$periodoInicio, $periodoFim] = $this->resolverPeriodo($this->filtroPeriodo);
            $query->whereHas('cronogramaFases', function ($q) use ($periodoInicio, $periodoFim) {
                $q->where(function ($sub) use ($periodoInicio, $periodoFim) {
                    $sub->whereBetween('data_prevista_inicio', [$periodoInicio, $periodoFim])
                        ->orWhereBetween('data_prevista_fim', [$periodoInicio, $periodoFim])
                        ->orWhere(function ($inner) use ($periodoInicio, $periodoFim) {
                            $inner->where('data_prevista_inicio', '<=', $periodoInicio)
                                ->where('data_prevista_fim', '>=', $periodoFim);
                        });
                });
            });
        }

        if ($this->filtroTemplate === 'com_template') {
            $query->whereHas('cronogramaFases', fn ($q) => $q->whereNotNull('cronograma_template_id'));
        } elseif ($this->filtroTemplate === 'sem_template') {
            $query->whereDoesntHave('cronogramaFases', fn ($q) => $q->whereNotNull('cronograma_template_id'));
        }

        $total = $query->count();
        $projetos = $query->orderBy('nome')
            ->skip(($this->paginaAtual - 1) * $this->porPagina)
            ->take($this->porPagina)
            ->get();

        foreach ($projetos as $projeto) {
            if ($projeto->cronogramaFases->isEmpty() && ! $projeto->sem_fases_auto) {
                $cronogramaService->criarFasesParaProjeto($projeto);
                $projeto->load(['cronogramaFases.template', 'cronogramaFases.templateFase']);
            }
            $projeto->setRelation(
                'cronogramaFases',
                $projeto->cronogramaFases->filter(fn (CronogramaFase $f) => $f->isVisivel())->values()
            );
        }

        $timeline = $this->calcularTimeline($projetos->flatMap->cronogramaFases);

        return [
            'projetos' => $projetos,
            'timeline' => $timeline,
            'totalProjetos' => $total,
            'totalPaginas' => (int) ceil($total / $this->porPagina),
            'statusOptions' => StatusCronograma::cases(),
            'statusProjetoOptions' => Projeto::distinct()->pluck('status')->filter()->sort()->values(),
            'estadosDisponiveis' => Estado::whereHas('projetos')->orderBy('uf')->get(),
            'modoIndividual' => false,
        ];
    }

    private function getViewDataIndividual(): array
    {
        $projeto = Projeto::with([
            'cronogramaFases.template',
            'cronogramaFases.templateFase',
            'cronogramaFases.dependencias.dependeDeItem.fase',
            'cronogramaFases.templateFase.dependencias.dependeDeItem.templateFase',
            'cronogramaFases.comentarios.usuario',
            'cronogramaFases.itens.children.responsaveis',
            'cronogramaFases.itens.children.revisor',
            'cronogramaFases.itens.children.dependencias',
            'cronogramaFases.itens.responsaveis',
            'cronogramaFases.itens.revisor',
            'cronogramaFases.itens.dependeDeFase',
            'cronogramaFases.itens.dependeDeItem',
            'cronogramaFases.itens.dependencias.dependeDeFase',
            'cronogramaFases.itens.dependencias.dependeDeItem',
            'responsavel',
            'responsavelArq',
            'responsavelCom',
            'responsavelEng',
            'respPmo',
            'estado',
            'obras.construtoras',
        ])->find($this->projetoSelecionado);

        if (! $projeto) {
            $this->projetoSelecionado = null;

            return $this->getViewDataMacro();
        }

        if ($projeto->cronogramaFases->isEmpty() && ! $projeto->sem_fases_auto) {
            (new CronogramaService)->criarFasesParaProjeto($projeto);
            $projeto->load(['cronogramaFases.template', 'cronogramaFases.templateFase', 'cronogramaFases.dependencias.dependeDeItem.fase', 'cronogramaFases.templateFase.dependencias.dependeDeItem.templateFase', 'cronogramaFases.itens.children.responsaveis', 'cronogramaFases.itens.children.revisor', 'cronogramaFases.itens.children.dependencias', 'cronogramaFases.itens.responsaveis', 'cronogramaFases.itens.revisor', 'cronogramaFases.itens.dependeDeFase', 'cronogramaFases.itens.dependeDeItem', 'cronogramaFases.itens.dependencias.dependeDeFase', 'cronogramaFases.itens.dependencias.dependeDeItem']);
        }

        $fases = $this->mostrarOcultas
            ? $projeto->cronogramaFases
            : $projeto->cronogramaFases->filter(fn (CronogramaFase $f) => $f->isVisivel())->values();

        if ($this->versaoSelecionada) {
            $fases = $this->reconstruirFasesNaVersao($fases, $this->versaoSelecionada);
        }

        $fases = $this->aplicarBufferLoteEmFases($fases);

        if ($this->buscaFase !== '') {
            $busca = mb_strtolower($this->buscaFase);
            $fases = $fases->filter(fn (CronogramaFase $f) => str_contains(mb_strtolower($f->label_exibicao), $busca))->values();
        }

        if ($this->filtroStatusFase !== '') {
            $fases = $fases->filter(fn (CronogramaFase $f) => $f->status->value === $this->filtroStatusFase)->values();
        }

        $timeline = $this->calcularTimeline($fases);
        $totalFases = $fases->count();
        $fasesConcluidas = $fases->where('status.value', 'concluido')->count();
        $fasesAtrasadas = $fases->where('status.value', 'atrasado')->count();
        $fasesEmAndamento = $fases->where('status.value', 'em_andamento')->count();
        $percentualGeral = $totalFases > 0 ? (int) round($fases->avg('percentual_conclusao')) : 0;

        $inicios = $fases->pluck('data_prevista_inicio')->filter();
        $fins = $fases->pluck('data_prevista_fim')->filter();
        $duracaoTotalDias = ($inicios->isNotEmpty() && $fins->isNotEmpty())
            ? ((int) $inicios->min()->diffInDays($fins->max())) + 1
            : 0;

        $projetosDisponiveis = Projeto::with('estado')
            ->select('id', 'nome', 'codigo', 'estado_id')
            ->orderBy('nome')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'label' => ($p->nome ?? 'Projeto #'.$p->id)
                    .($p->codigo ? ' ('.$p->codigo.')' : '')
                    .($p->estado ? ' - '.$p->estado->uf : ''),
            ]);

        $fasesParaEditor = $projeto->cronogramaFases->sortBy('ordem')->values();

        return [
            'projeto' => $projeto,
            'fases' => $fases,
            'fasesParaEditor' => $fasesParaEditor,
            'timeline' => $timeline,
            'statusOptions' => StatusCronograma::cases(),
            'modoIndividual' => true,
            'totalFases' => $totalFases,
            'fasesConcluidas' => $fasesConcluidas,
            'fasesAtrasadas' => $fasesAtrasadas,
            'fasesEmAndamento' => $fasesEmAndamento,
            'percentualGeral' => $percentualGeral,
            'duracaoTotalDias' => $duracaoTotalDias,
            'projetosDisponiveis' => $projetosDisponiveis,
            'templatesDisponiveis' => CronogramaTemplate::ativos()->orderBy('nome')->get(),
            'versaoAtiva' => $this->versaoSelecionada,
            'templateAplicadoNoProjeto' => $fases->first(fn ($f) => (bool) $f->cronograma_template_id)?->template ?? null,
            'fasesDisponiveisEnum' => FaseCronograma::cases(),
            'gatilhoOptionsEnum' => GatilhoTemplateFase::cases(),
            'tipoDiasOptionsEnum' => TipoDiasTemplate::cases(),
            'modoAncoraAtual' => $projeto->modo_ancora ?? ModoAncoraCronograma::POSSE,
            'limitesResumo' => CronogramaLimites::avaliar($projeto),
            'motivosPosseOptions' => MotivoAlteracaoObra::paraSelect(),
            'usuarios' => \App\Models\User::select('id', 'name')->orderBy('name')->get(),
        ];
    }

    public function abrirEditorFases(): void
    {
        if ($this->mostrarModalDatas) {
            $this->fecharModalDatas();
        }

        $this->templateSelecionadoParaAplicar = null;
        $this->templateDataAncora = null;
        $this->templateAncoraLabel = null;
        $this->templateAncoraCampo = null;

        if ($this->projetoSelecionado) {
            $atual = CronogramaFase::where('projeto_id', $this->projetoSelecionado)
                ->whereNotNull('cronograma_template_id')
                ->value('cronograma_template_id');
            if ($atual) {
                $this->templateSelecionadoParaAplicar = (int) $atual;
                $this->carregarAncoraDoTemplate();
                $this->carregarOverridesObraFases();
            } else {
                $this->overridesObraFases = [];
            }
        }

        $this->mostrarEditorFases = true;
    }

    public function fecharEditorFases(): void
    {
        $this->mostrarEditorFases = false;
        // Força re-render do cr-card para o layout reaproveitar o espaço
        // liberado pelo painel — sem isso, larguras calculadas via Alpine
        // ficam desalinhadas e a tabela aparece com colunas vazias.
        $this->renderKey++;
    }

    public function marcarFaseNaoSeAplica(int $faseId): void
    {
        $this->acaoPendenteFase = 'ocultar';
        $this->verificarDepsFase($faseId);
    }

    public function excluirFaseProjeto(int $faseId): void
    {
        $this->acaoPendenteFase = 'excluir';
        $this->verificarDepsFase($faseId);
    }

    private function verificarDepsFase(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase || ! $fase->projeto_id) {
            return;
        }

        $enumValue = $fase->fase instanceof FaseCronograma ? $fase->fase->value : (string) $fase->fase;

        // Conflitos no banco
        $conflitantesDb = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->where('id', '!=', $faseId)
            ->whereHas('dependencias', fn ($q) => $q->where('depende_de_fase', $enumValue))
            ->with(['dependencias' => fn ($q) => $q->where('depende_de_fase', $enumValue)])
            ->get();

        $conflitantes = [];
        $idsJaAdicionados = [];

        foreach ($conflitantesDb as $cf) {
            $dep = $cf->dependencias->first();
            if (! $dep) {
                continue;
            }
            $conflitantes[] = [
                'fase_id' => $cf->id,
                'fase_nome' => $cf->titulo_personalizado ?? $cf->fase?->label() ?? '—',
                'dep_id' => $dep->id,
                'is_ovr' => false,
                'ovr_dep_idx' => null,
                'substituir_por' => '',
                'gatilho' => $dep->gatilho instanceof GatilhoTemplateFase ? $dep->gatilho->value : (string) ($dep->gatilho ?? ''),
                'gap_dias' => (int) ($dep->gap_dias ?? 0),
            ];
            $idsJaAdicionados[] = $cf->id;
        }

        // Conflitos apenas em memória (overrides não salvos ainda)
        foreach ($this->overridesObraFases as $oFaseId => $override) {
            if ((int) $oFaseId === $faseId || in_array((int) $oFaseId, $idsJaAdicionados, true)) {
                continue;
            }
            foreach ($override['deps'] ?? [] as $idx => $dep) {
                if (($dep['alvo'] ?? '') === 'fase:'.$enumValue) {
                    $cf = CronogramaFase::find($oFaseId);
                    if ($cf) {
                        $conflitantes[] = [
                            'fase_id' => (int) $oFaseId,
                            'fase_nome' => $cf->titulo_personalizado ?? $cf->fase?->label() ?? '—',
                            'dep_id' => null,
                            'is_ovr' => true,
                            'ovr_dep_idx' => $idx,
                            'substituir_por' => '',
                            'gatilho' => $dep['gatilho'] ?? '',
                            'gap_dias' => (int) ($dep['gap_dias'] ?? 0),
                        ];
                    }
                    break;
                }
            }
        }

        $this->faseParaNaoAplicarId = $faseId;
        $this->faseParaNaoAplicarEnum = $enumValue;

        if (! empty($conflitantes)) {
            $this->fasesConflitantes = $conflitantes;
            $this->mostrarModalConflitoDep = true;

            return;
        }

        $this->executarAcaoFase($fase);
    }

    private function executarAcaoFase(CronogramaFase $fase): void
    {
        if ($this->acaoPendenteFase === 'excluir') {
            $fase->dependencias()->delete();
            $fase->itens()->delete();
            $fase->delete();
        } else {
            $fase->updateQuietly(['visivel' => false]);
        }
        $this->renderKey++;
    }

    public function confirmarNaoSeAplicaRemoveDeps(): void
    {
        if (! $this->faseParaNaoAplicarId) {
            return;
        }

        $fase = CronogramaFase::find($this->faseParaNaoAplicarId);
        if (! $fase) {
            return;
        }

        foreach ($this->fasesConflitantes as $conf) {
            $novaDep = $conf['substituir_por'] ?? '';
            $gatilho = $conf['gatilho'] ?? '';
            $gapDias = (int) ($conf['gap_dias'] ?? 0);

            // Dep salva no banco
            if ($conf['dep_id']) {
                $dep = CronogramaFaseDependencia::find($conf['dep_id']);
                if ($dep) {
                    if ($novaDep !== '') {
                        $dep->update([
                            'depende_de_fase' => $novaDep,
                            'gatilho' => $gatilho ?: null,
                            'gap_dias' => $gapDias,
                        ]);
                    } else {
                        $dep->delete();
                    }
                }
            }

            // Dep apenas em memória (override ainda não salvo)
            if ($conf['is_ovr'] && $conf['ovr_dep_idx'] !== null) {
                $oFaseId = $conf['fase_id'];
                if (isset($this->overridesObraFases[$oFaseId]['deps'][$conf['ovr_dep_idx']])) {
                    if ($novaDep !== '') {
                        $this->overridesObraFases[$oFaseId]['deps'][$conf['ovr_dep_idx']] = array_merge(
                            $this->overridesObraFases[$oFaseId]['deps'][$conf['ovr_dep_idx']],
                            ['alvo' => 'fase:'.$novaDep, 'gatilho' => $gatilho, 'gap_dias' => $gapDias]
                        );
                    } else {
                        unset($this->overridesObraFases[$oFaseId]['deps'][$conf['ovr_dep_idx']]);
                        $this->overridesObraFases[$oFaseId]['deps'] = array_values(
                            $this->overridesObraFases[$oFaseId]['deps']
                        );
                    }
                }
            }
        }

        $this->executarAcaoFase($fase);

        $this->mostrarModalConflitoDep = false;
        $this->faseParaNaoAplicarId = null;
        $this->faseParaNaoAplicarEnum = '';
        $this->fasesConflitantes = [];
    }

    public function cancelarNaoSeAplica(): void
    {
        $this->mostrarModalConflitoDep = false;
        $this->faseParaNaoAplicarId = null;
        $this->faseParaNaoAplicarEnum = '';
        $this->fasesConflitantes = [];
    }

    public function desmarcarNaoSeAplica(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $fase->updateQuietly(['visivel' => null]);
        $this->renderKey++;
    }

    public function moverFaseAcima(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $anterior = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->where('ordem', '<', $fase->ordem)
            ->orderByDesc('ordem')
            ->first();

        if (! $anterior) {
            return;
        }

        [$fase->ordem, $anterior->ordem] = [$anterior->ordem, $fase->ordem];
        $fase->save();
        $anterior->save();
        $this->renderKey++;
    }

    public function moverFaseAbaixo(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $proxima = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->where('ordem', '>', $fase->ordem)
            ->orderBy('ordem')
            ->first();

        if (! $proxima) {
            return;
        }

        [$fase->ordem, $proxima->ordem] = [$proxima->ordem, $fase->ordem];
        $fase->save();
        $proxima->save();
        $this->renderKey++;
    }

    public function moverFaseParaPosicao(int $faseId, int $alvoId): void
    {
        if ($faseId === $alvoId) {
            return;
        }

        $fase = CronogramaFase::find($faseId);
        $alvo = CronogramaFase::find($alvoId);

        if (! $fase || ! $alvo || $fase->projeto_id !== $alvo->projeto_id) {
            return;
        }

        $todas = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->orderBy('ordem')
            ->get();

        $semArrastada = $todas->reject(fn ($f) => $f->id === $faseId)->values();

        $alvoIdx = $semArrastada->search(fn ($f) => $f->id === $alvoId);

        if ($alvoIdx === false) {
            return;
        }

        $reordenada = $semArrastada->slice(0, $alvoIdx)
            ->push($fase)
            ->merge($semArrastada->slice($alvoIdx))
            ->values();

        foreach ($reordenada as $i => $f) {
            $nova = $i + 1;
            if ((int) $f->ordem !== $nova) {
                CronogramaFase::where('id', $f->id)->update(['ordem' => $nova]);
            }
        }

        $this->renderKey++;
    }

    private function carregarOverridesObraFases(): void
    {
        $this->overridesObraFases = [];
        if (! $this->projetoSelecionado) {
            return;
        }

        $fases = CronogramaFase::with(['templateFase', 'dependencias'])
            ->where('projeto_id', $this->projetoSelecionado)
            ->orderBy('ordem')
            ->get();

        foreach ($fases as $fase) {
            $regra = $fase->regraEfetiva();
            $this->overridesObraFases[$fase->id] = [
                'duracao' => (int) $regra->duracao_dias,
                'tipo_dias' => $regra->tipo_dias?->value ?? 'corridos',
                'deps' => $regra->dependencias->map(fn ($d) => [
                    'alvo' => $this->dependenciaParaAlvo($d),
                    'gatilho' => $d->gatilho instanceof GatilhoTemplateFase
                        ? $d->gatilho->value
                        : (string) $d->gatilho,
                    'gap_dias' => (int) $d->gap_dias,
                ])->values()->all(),
            ];
        }
    }

    public function adicionarDepFaseObra(int $faseId): void
    {
        if (! isset($this->overridesObraFases[$faseId])) {
            return;
        }
        $this->overridesObraFases[$faseId]['deps'][] = [
            'alvo' => '',
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 1,
        ];
    }

    public function removerDepFaseObra(int $faseId, int $idx): void
    {
        if (! isset($this->overridesObraFases[$faseId]['deps'][$idx])) {
            return;
        }
        unset($this->overridesObraFases[$faseId]['deps'][$idx]);
        $this->overridesObraFases[$faseId]['deps'] = array_values($this->overridesObraFases[$faseId]['deps']);
    }

    public function salvarOverridesObraFases(): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $fases = CronogramaFase::with(['templateFase.dependencias', 'dependencias'])
            ->where('projeto_id', $this->projetoSelecionado)
            ->get();

        foreach ($fases as $fase) {
            if (! isset($this->overridesObraFases[$fase->id])) {
                continue;
            }
            $o = $this->overridesObraFases[$fase->id];

            // valida deps
            foreach ($o['deps'] as $dep) {
                if (($dep['alvo'] ?? '') === 'fase:'.$fase->fase->value) {
                    Notification::make()->title('Dependência inválida')
                        ->body('Uma fase não pode depender de si mesma: '.$fase->fase->label())
                        ->danger()->send();

                    return;
                }
            }
            $vals = array_filter(array_column($o['deps'], 'alvo'));
            if (count($vals) !== count(array_unique($vals))) {
                Notification::make()->title('Dependência duplicada')
                    ->body('Fase '.$fase->fase->label().' tem dependências duplicadas.')
                    ->danger()->send();

                return;
            }

            $tpl = $fase->templateFase;
            $novaDur = max(0, (int) ($o['duracao'] ?? 0));
            $novoTipo = $o['tipo_dias'] ?? 'corridos';

            $update = [];
            if ($tpl) {
                $igualDur = $novaDur === (int) $tpl->duracao_dias;
                $igualTipo = $novoTipo === $tpl->tipo_dias?->value;
                $update['regra_duracao_dias'] = $igualDur ? null : $novaDur;
                $update['regra_tipo_dias'] = $igualTipo ? null : $novoTipo;
                $update['regra_customizada'] = ! ($igualDur && $igualTipo);
            } else {
                $update['regra_duracao_dias'] = $novaDur;
                $update['regra_tipo_dias'] = $novoTipo;
                $update['regra_customizada'] = true;
            }

            $fase->update($update);

            // Sincroniza dependências locais: se idênticas às do template, limpa; senão substitui.
            $tplDeps = $tpl
                ? $tpl->dependencias->map(fn ($d) => [
                    'alvo' => $this->dependenciaParaAlvo($d),
                    'gatilho' => $d->gatilho instanceof GatilhoTemplateFase ? $d->gatilho->value : (string) $d->gatilho,
                    'gap_dias' => (int) $d->gap_dias,
                ])->values()->all()
                : [];

            $novasDeps = array_values(array_filter($o['deps'], fn ($d) => ! empty($d['alvo'])));

            $fase->dependencias()->delete();
            if ($this->normalizarDependencias($novasDeps) !== $this->normalizarDependencias($tplDeps)) {
                foreach ($novasDeps as $dep) {
                    [$depFase, $depItemId] = $this->resolverAlvo($dep['alvo'] ?? '');
                    $fase->dependencias()->create([
                        'depende_de_fase' => $depFase,
                        'depende_de_item_id' => $depItemId,
                        'gatilho' => $dep['gatilho'] ?: GatilhoTemplateFase::FIM_ANTERIOR->value,
                        'gap_dias' => (int) ($dep['gap_dias'] ?? 0),
                    ]);
                }
                if (! $fase->regra_customizada) {
                    $fase->update(['regra_customizada' => true]);
                }
            }
        }

        // Recalcula datas previstas usando as regras efetivas (incluindo overrides salvos)
        $faseAncora = CronogramaFase::where('projeto_id', $this->projetoSelecionado)
            ->whereNotNull('cronograma_template_id')
            ->first();
        if ($faseAncora) {
            try {
                (new CronogramaTemplateService)->recalcularFaseEDependentes($faseAncora);
            } catch (\Throwable $e) {
                Notification::make()->title('Aviso ao recalcular datas')->body($e->getMessage())->warning()->send();
            }
        }

        $this->renderKey++;
        Notification::make()->title('Overrides da obra salvos e datas recalculadas')->success()->send();
    }

    public function updatedTemplateSelecionadoParaAplicar($value): void
    {
        $this->carregarAncoraDoTemplate();
        $this->carregarOverridesObraFases();
    }

    private function carregarAncoraDoTemplate(): void
    {
        if (! $this->templateSelecionadoParaAplicar || ! $this->projetoSelecionado) {
            $this->templateDataAncora = null;
            $this->templateAncoraLabel = null;
            $this->templateAncoraCampo = null;

            return;
        }

        $template = CronogramaTemplate::find($this->templateSelecionadoParaAplicar);
        $projeto = Projeto::with('obras')->find($this->projetoSelecionado);
        if (! $template || ! $projeto) {
            return;
        }

        $this->templateAncoraCampo = $template->ancora_campo;
        $this->templateAncoraLabel = $this->labelAncora($template->ancora_campo);

        $valor = (new CronogramaTemplateService)->resolverAncora($template, $projeto);
        $this->templateDataAncora = $valor?->format('Y-m-d');
    }

    private function labelAncora(string $campo): string
    {
        return match ($campo) {
            'projeto.data_ass_contrato' => 'Data de assinatura do contrato',
            'projeto.inauguracao' => 'Data de inauguração',
            'projeto.data_posse' => 'Data de posse',
            'inicio' => 'Início da obra',
            'fim' => 'Fim da obra',
            default => $campo,
        };
    }

    public function abrirModalDatas(): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        if ($this->mostrarEditorFases) {
            $this->fecharEditorFases();
        }

        $fases = CronogramaFase::with('templateFase')
            ->where('projeto_id', $this->projetoSelecionado)
            ->orderBy('ordem')
            ->get();

        $this->edicaoLoteDatas = [];
        $this->datasOriginaisLote = [];
        foreach ($fases as $fase) {
            $regra = $fase->regraEfetiva();
            $prevI = $fase->data_prevista_inicio?->format('Y-m-d');
            $prevF = $fase->data_prevista_fim?->format('Y-m-d');
            $this->edicaoLoteDatas[$fase->id] = [
                'prev_i' => $prevI,
                'prev_f' => $prevF,
                'duracao' => (int) $regra->duracao_dias,
                'travado' => CronogramaTemplateService::bloqueadoRecalculo($fase->status),
            ];
            $this->datasOriginaisLote[$fase->id] = [
                'prev_i' => $prevI,
                'prev_f' => $prevF,
            ];
        }

        $this->mostrarModalDatas = true;
    }

    public function fecharModalDatas(): void
    {
        $this->mostrarModalDatas = false;
        $this->mostrarConfirmacaoSalvarDatas = false;
        $this->edicaoLoteDatas = [];
        $this->datasOriginaisLote = [];
        $this->motivoLoteDatas = '';
        // Força re-render do cr-card para o layout reaproveitar o espaço
        // liberado pelo painel.
        $this->renderKey++;
    }

    public function abrirConfirmacaoSalvarDatas(): void
    {
        if (! $this->mostrarModalDatas || empty($this->edicaoLoteDatas)) {
            return;
        }

        $this->mostrarConfirmacaoSalvarDatas = true;
    }

    public function fecharConfirmacaoSalvarDatas(): void
    {
        $this->mostrarConfirmacaoSalvarDatas = false;
    }

    /**
     * Detecta se a fase POSSE está entre as fases alteradas no lote (data
     * prevista mudou em relação ao snapshot original). Usado para exigir
     * motivo padronizado no modal de confirmação.
     */
    public function posseAlteradaNoLote(): bool
    {
        if (empty($this->edicaoLoteDatas)) {
            return false;
        }

        $fases = CronogramaFase::where('projeto_id', $this->projetoSelecionado)
            ->where('fase', FaseCronograma::POSSE->value)
            ->get();

        foreach ($fases as $fase) {
            $buf = $this->edicaoLoteDatas[$fase->id] ?? null;
            $orig = $this->datasOriginaisLote[$fase->id] ?? null;
            if (! $buf || ! $orig) {
                continue;
            }
            if (($buf['prev_i'] ?? null) !== ($orig['prev_i'] ?? null)) {
                return true;
            }
            if (($buf['prev_f'] ?? null) !== ($orig['prev_f'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    public function confirmarSalvarDatasEmLote(): void
    {
        if (blank(trim($this->motivoLoteDatas ?? ''))) {
            Notification::make()
                ->title('Motivo obrigatório')
                ->body('Informe o motivo da alteração antes de salvar.')
                ->warning()
                ->send();

            return;
        }

        if ($this->posseAlteradaNoLote() && blank($this->motivoPosseCodigo)) {
            Notification::make()
                ->title('Classificação da Data de Posse obrigatória')
                ->body('Selecione o motivo padronizado para a alteração da Data de Posse.')
                ->warning()
                ->send();

            return;
        }

        if ($this->posseAlteradaNoLote()) {
            $this->motivoPosseHistorico = $this->motivoLoteDatas;
        }

        $this->mostrarConfirmacaoSalvarDatas = false;
        $this->salvarDatasEmLote(true);
    }

    /**
     * Aplica o buffer de edição em lote (datas previstas) na coleção de fases
     * em memória, sem persistir. Usado no render para mostrar prévia em
     * tempo real enquanto a sidebar "Alterar datas" estiver aberta.
     */
    private function aplicarBufferLoteEmFases($fases)
    {
        if (! $this->mostrarModalDatas || empty($this->edicaoLoteDatas)) {
            return $fases;
        }

        foreach ($fases as $fase) {
            $buf = $this->edicaoLoteDatas[$fase->id] ?? null;
            if (! $buf) {
                continue;
            }

            $fase->data_prevista_inicio = ! empty($buf['prev_i']) ? Carbon::parse($buf['prev_i']) : null;
            $fase->data_prevista_fim = ! empty($buf['prev_f']) ? Carbon::parse($buf['prev_f']) : null;
        }

        return $fases;
    }

    public function updatedEdicaoLoteDatas($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            return;
        }

        [$faseId, $field] = $parts;
        if (! in_array($field, ['prev_i', 'prev_f', 'duracao'])) {
            return;
        }

        $this->recalcularCascataLote((int) $faseId, $field);
    }

    public function recalcularCascataLote(int $faseIdAlterada, string $campo = 'prev_i'): void
    {
        if (! $this->projetoSelecionado || empty($this->edicaoLoteDatas)) {
            return;
        }

        $fases = CronogramaFase::with(['dependencias', 'templateFase.dependencias'])
            ->where('projeto_id', $this->projetoSelecionado)
            ->orderBy('ordem')
            ->get();

        if ($fases->isEmpty()) {
            return;
        }

        $faseAlterada = $fases->firstWhere('id', $faseIdAlterada);
        if (! $faseAlterada) {
            return;
        }

        $dados = $this->edicaoLoteDatas[$faseIdAlterada];

        $effectiveStart = $dados['prev_i'] ?? null;
        $effectiveFim = null;

        if (! $effectiveStart) {
            return;
        }

        $fasesObra = $fases->keyBy(fn ($f) => $f->fase->value);
        $service = new CronogramaTemplateService;
        [$duracoes, $tipoDias, $deps, $elasticas] = $service->extrairRegrasEfetivas($fasesObra);

        foreach ($fases as $fase) {
            $v = $fase->fase->value;
            if (isset($this->edicaoLoteDatas[$fase->id]['duracao'])) {
                $duracoes[$v] = max(0, (int) $this->edicaoLoteDatas[$fase->id]['duracao']);
            }
        }

        $novaDataFim = $effectiveFim ? CarbonImmutable::parse($effectiveFim) : null;

        try {
            $resolvidas = $service->calcularCascataBidirecional(
                $faseAlterada->fase->value,
                CarbonImmutable::parse($effectiveStart),
                $duracoes,
                $tipoDias,
                $deps,
                $novaDataFim,
                $elasticas,
            );
        } catch (\Throwable) {
            return;
        }

        foreach ($fases as $fase) {
            $chave = $fase->fase->value;
            if (! isset($resolvidas[$chave])) {
                continue;
            }

            if (! empty($this->edicaoLoteDatas[$fase->id]['travado'])) {
                continue;
            }

            $this->edicaoLoteDatas[$fase->id]['prev_i'] = $resolvidas[$chave]['inicio']->format('Y-m-d');
            $this->edicaoLoteDatas[$fase->id]['prev_f'] = $resolvidas[$chave]['fim']->format('Y-m-d');
        }
    }

    public function salvarDatasEmLote(bool $fechar = false): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $afetadas = 0;
        foreach ($this->edicaoLoteDatas as $faseId => $datas) {
            $fase = CronogramaFase::with('templateFase')->find($faseId);
            if (! $fase || $fase->projeto_id !== $this->projetoSelecionado) {
                continue;
            }

            $inicioAntes = $fase->data_prevista_inicio?->toDateString();
            $fimAntes = $fase->data_prevista_fim?->toDateString();

            // Calcula data final a partir de início + duração quando o fim não foi preenchido
            $prevI = $datas['prev_i'] ?: null;
            $prevF = $datas['prev_f'] ?: null;
            $novaDur = isset($datas['duracao']) ? max(0, (int) $datas['duracao']) : null;
            if ($prevI && ! $prevF && $novaDur > 0) {
                $prevF = Carbon::parse($prevI)->addDays($novaDur - 1)->toDateString();
            }

            $update = [
                'data_prevista_inicio' => $prevI,
                'data_prevista_fim' => $prevF,
            ];
            if ($novaDur !== null) {
                $tpl = $fase->templateFase;
                if ($tpl && $novaDur !== (int) $tpl->duracao_dias) {
                    $update['regra_duracao_dias'] = $novaDur;
                    $update['regra_customizada'] = true;
                } elseif ($tpl && $novaDur === (int) $tpl->duracao_dias) {
                    $update['regra_duracao_dias'] = null;
                    if (! $fase->regra_tipo_dias && $fase->dependencias()->count() === 0) {
                        $update['regra_customizada'] = false;
                    }
                } elseif (! $tpl) {
                    $update['regra_duracao_dias'] = $novaDur;
                }
            }

            $fase->update($update);

            $motivo = $this->motivoLoteDatas ?: 'Edição em lote';
            if ($inicioAntes !== $prevI) {
                CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_inicio', $inicioAntes, $prevI, $motivo, auth()->id());
            }
            if ($fimAntes !== $prevF) {
                CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_fim', $fimAntes, $prevF, $motivo, auth()->id());
            }

            // Propaga variação de datas para subitems sem datas manuais
            $delta = $this->calcularDeltaDias($inicioAntes, $prevI);
            if ($delta !== null && $delta !== 0) {
                $this->propagarShiftSubitensDaFase($fase->id, $delta);
            }

            $afetadas++;
        }

        $projeto = Projeto::find($this->projetoSelecionado);
        if ($projeto) {
            $dataPosseAntes = $projeto->data_posse?->toDateString();

            (new CronogramaTemplateService)->sincronizarDatasComProjeto($projeto);

            $projeto->refresh();
            $dataPosseDepois = $projeto->data_posse?->toDateString();

            if ($dataPosseAntes !== $dataPosseDepois && $this->motivoPossePreenchido()) {
                CronogramaFaseHistorico::create([
                    'projeto_id' => $projeto->id,
                    'cronograma_fase_id' => null,
                    'campo_alterado' => 'projeto.data_posse',
                    'valor_anterior' => $dataPosseAntes,
                    'valor_novo' => $dataPosseDepois,
                    'motivo' => sprintf(
                        'Data de posse alterada de %s para %s',
                        $dataPosseAntes ? Carbon::parse($dataPosseAntes)->format('d/m/Y') : '—',
                        $dataPosseDepois ? Carbon::parse($dataPosseDepois)->format('d/m/Y') : '—',
                    ),
                    'motivo_codigo' => $this->motivoPosseCodigo,
                    'motivo_historico' => $this->motivoPosseHistorico,
                    'usuario_id' => auth()->id(),
                    'automatico' => false,
                ]);
            }
        }

        $this->limparModalMotivoPosse();
        $this->renderKey++;

        if ($fechar) {
            $this->fecharModalDatas();
        }

        Notification::make()
            ->title('Datas atualizadas')
            ->body("{$afetadas} fase(s) atualizadas.")
            ->success()
            ->send();
    }

    public function resetarFaseLote(int $faseId): void
    {
        $fase = CronogramaFase::with('templateFase')->find($faseId);
        if (! $fase || $fase->projeto_id !== $this->projetoSelecionado || ! $fase->templateFase) {
            return;
        }

        $fase->update([
            'regra_duracao_dias' => null,
            'regra_tipo_dias' => null,
            'regra_customizada' => false,
        ]);
        $fase->dependencias()->delete();
        $fase->refresh()->load('templateFase');

        $regra = $fase->regraEfetiva();
        $this->edicaoLoteDatas[$faseId]['duracao'] = (int) $regra->duracao_dias;

        Notification::make()->title('Fase restaurada ao padrão do template')->success()->send();
    }

    public function zerarDatasObra(): void
    {
        if (! auth()->user()?->can('ZerarDatas:Cronograma')) {
            Notification::make()->title('Sem permissão')->danger()->send();

            return;
        }

        if (! $this->projetoSelecionado) {
            return;
        }

        $fasesParaZerar = CronogramaFase::where('projeto_id', $this->projetoSelecionado)->get();

        foreach ($fasesParaZerar as $faseZerar) {
            $inicioAntes = $faseZerar->data_prevista_inicio?->toDateString();
            $fimAntes = $faseZerar->data_prevista_fim?->toDateString();
            $realInicioAntes = $faseZerar->data_realizada_inicio?->toDateString();
            $realFimAntes = $faseZerar->data_realizada_fim?->toDateString();

            if ($inicioAntes) {
                CronogramaService::registrarHistoricoDatas($faseZerar, 'data_prevista_inicio', $inicioAntes, null, 'Zeragem de datas', auth()->id(), true);
            }
            if ($fimAntes) {
                CronogramaService::registrarHistoricoDatas($faseZerar, 'data_prevista_fim', $fimAntes, null, 'Zeragem de datas', auth()->id(), true);
            }
            if ($realInicioAntes) {
                CronogramaService::registrarHistoricoDatas($faseZerar, 'data_realizada_inicio', $realInicioAntes, null, 'Zeragem de datas', auth()->id(), true);
            }
            if ($realFimAntes) {
                CronogramaService::registrarHistoricoDatas($faseZerar, 'data_realizada_fim', $realFimAntes, null, 'Zeragem de datas', auth()->id(), true);
            }
        }

        $afetadas = CronogramaFase::where('projeto_id', $this->projetoSelecionado)
            ->update([
                'data_prevista_inicio' => null,
                'data_prevista_fim' => null,
                'data_realizada_inicio' => null,
                'data_realizada_fim' => null,
                'status' => 'nao_iniciado',
                'percentual_conclusao' => 0,
            ]);

        $projeto = Projeto::with('obras')->find($this->projetoSelecionado);

        if ($projeto) {
            $projeto->updateQuietly([
                // Datas planejadas
                'inauguracao' => null,
                'data_posse' => null,
                'imp_inicio' => null,
                'imp_fim' => null,
                'cad_plan_inicio' => null,
                'cad_plan_fim' => null,
                'vis_plan_inicio' => null,
                'vis_plan_fim' => null,
                'brief_plan_lay_inicio' => null,
                'brief_plan_lay_fim' => null,
                'proj_plan_ini' => null,
                'proj_plan_fim' => null,
                'orca_planejado_ini' => null,
                'orca_planejado_fim' => null,
                'ordem_planej_ini' => null,
                'ordem_planej_fim' => null,
                'legal_plan_ini' => null,
                'legal_plan_fim' => null,
                // Datas realizadas
                'cad_rea_inicio' => null,
                'cad_rea_fim' => null,
                'vis_rea_inicio' => null,
                'vis_rea_fim' => null,
                'brief_real_lay_inicio' => null,
                'brief_real_lay_fim' => null,
                'proj_real_ini' => null,
                'proj_real_fim' => null,
                'orca_real_ini' => null,
                'orca_real_fim' => null,
                'ordem_realizado' => null,
                'ordem_realizado_fim' => null,
                'legal_realizado_ini' => null,
                'legal_realizado_fim' => null,
            ]);

            $obra = $projeto->obras->first();
            if ($obra) {
                $obra->updateQuietly([
                    'inicio' => null,
                    'fim' => null,
                    'inicio_real' => null,
                    'inicio_imp' => null,
                    'fim_imp' => null,
                    'entrada_ponto' => null,
                ]);
            }
        }

        // Mantém o modal aberto e o template selecionado para que o usuário
        // possa informar a nova data-âncora e reaplicar sem precisar reabrir.
        $this->templateDataAncora = null;
        $this->templateAncoraLabel = null;
        $this->renderKey++;
        Notification::make()
            ->title('Datas zeradas')
            ->body("{$afetadas} fase(s) tiveram suas datas previstas limpas, incluindo datas do projeto e obra.")
            ->success()
            ->send();
    }

    /**
     * Alterna o modo de âncora do cronograma do projeto selecionado.
     * - POSSE: mudanças em data_posse recalculam o cronograma (planejamento inicial)
     * - OBRAS: mudanças em data_posse não recalculam (operação corrente)
     */
    public function definirModoAncora(string $modo): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $modoEnum = ModoAncoraCronograma::tryFrom($modo);
        if (! $modoEnum) {
            Notification::make()
                ->title('Modo de âncora inválido')
                ->danger()
                ->send();

            return;
        }

        $projeto = Projeto::find($this->projetoSelecionado);
        if (! $projeto) {
            return;
        }

        if ($projeto->modo_ancora === $modoEnum) {
            return;
        }

        $projeto->update(['modo_ancora' => $modoEnum->value]);
        $this->renderKey++;

        // Se o template aplicado tem variante pareada, oferece reaplicar a do novo modo.
        $templateAtual = $projeto->cronogramaFases()
            ->whereNotNull('cronograma_template_id')
            ->with('template.pareado')
            ->first()
            ?->template;

        if ($templateAtual && $templateAtual->temPar()) {
            $variante = $templateAtual->variantePara($modoEnum);
            if ($variante && $variante->id !== $templateAtual->id) {
                $this->varianteSugeridaId = $variante->id;
                $this->varianteSugeridaNome = $variante->nome;
                $this->mostrarConfirmacaoReaplicar = true;

                return;
            }
        }

        Notification::make()
            ->title('Modo de âncora atualizado')
            ->body($modoEnum->descricao())
            ->success()
            ->send();
    }

    /**
     * Reaplica a variante pareada do template após troca de modo de âncora.
     * Mantém o status/datas realizadas; sobrescreve previstos com base na
     * nova âncora e nas regras da variante.
     */
    public function confirmarReaplicarVariante(): void
    {
        if (! $this->projetoSelecionado || ! $this->varianteSugeridaId) {
            $this->mostrarConfirmacaoReaplicar = false;

            return;
        }

        $projeto = Projeto::with('obras')->find($this->projetoSelecionado);
        $variante = CronogramaTemplate::with('fases')->find($this->varianteSugeridaId);

        if (! $projeto || ! $variante) {
            $this->cancelarReaplicarVariante();

            return;
        }

        try {
            (new CronogramaTemplateService)->aplicar($variante, $projeto);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Falha ao reaplicar variante')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->cancelarReaplicarVariante();

            return;
        }

        Notification::make()
            ->title('Variante reaplicada')
            ->body('Cronograma reconfigurado conforme a variante ' . strtoupper($projeto->modo_ancora->value) . ' do template.')
            ->success()
            ->send();

        $this->cancelarReaplicarVariante();
        $this->renderKey++;
    }

    public function cancelarReaplicarVariante(): void
    {
        $this->mostrarConfirmacaoReaplicar = false;
        $this->varianteSugeridaId = null;
        $this->varianteSugeridaNome = '';
    }

    public function aplicarTemplate(): void
    {
        if (! $this->projetoSelecionado || ! $this->templateSelecionadoParaAplicar) {
            return;
        }

        if (! $this->templateDataAncora) {
            Notification::make()
                ->title('Informe a data-âncora')
                ->body('Preencha a data antes de aplicar o template.')
                ->warning()
                ->send();

            return;
        }

        $projeto = Projeto::with('obras')->find($this->projetoSelecionado);
        $template = CronogramaTemplate::with('fases', 'pareado.fases')->find($this->templateSelecionadoParaAplicar);

        if (! $projeto || ! $template) {
            Notification::make()->title('Projeto ou template não encontrados')->danger()->send();

            return;
        }

        // Se o template tem variante pareada, escolhe automaticamente a que bate com o modo do projeto.
        $modoProjeto = $projeto->modo_ancora ?? ModoAncoraCronograma::POSSE;
        if ($template->temPar()) {
            $variante = $template->variantePara($modoProjeto);
            if ($variante && $variante->id !== $template->id) {
                $template = CronogramaTemplate::with('fases')->find($variante->id);
                Notification::make()
                    ->title('Variante ' . strtoupper($modoProjeto->value) . ' selecionada')
                    ->body('O projeto está em modo ' . strtoupper($modoProjeto->value) . '; aplicando a variante correspondente do template.')
                    ->info()
                    ->send();
            }
        }

        if ($this->ancoraVaiAlterarPosse($projeto, $template) && ! $this->motivoPossePreenchido()) {
            $this->mostrarModalMotivoPosse = true;

            return;
        }

        $this->gravarDataAncora($projeto, $template->ancora_campo, $this->templateDataAncora);
        $projeto->refresh()->load('obras');

        $dataAncoraOverride = (is_null($template->ancora_campo) || $template->ancora_campo === 'manual')
            ? \Carbon\CarbonImmutable::parse($this->templateDataAncora)
            : null;

        try {
            (new CronogramaTemplateService)->aplicar($template, $projeto, $dataAncoraOverride);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Falha ao aplicar template')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->limparModalMotivoPosse();
        $this->fecharEditorFases();
        $this->renderKey++;
        Notification::make()->title('Template aplicado às datas previstas')->success()->send();
    }

    private function ancoraVaiAlterarPosse(Projeto $projeto, CronogramaTemplate $template): bool
    {
        if ($template->ancora_campo !== 'projeto.data_posse') {
            return false;
        }

        $atual = $projeto->data_posse?->toDateString();
        $novo = $this->templateDataAncora;

        return $atual !== $novo;
    }

    private function motivoPossePreenchido(): bool
    {
        return ! blank($this->motivoPosseCodigo);
    }

    public function confirmarMotivoPosse(): void
    {
        if (! $this->motivoPossePreenchido()) {
            Notification::make()
                ->title('Selecione o motivo padronizado')
                ->warning()
                ->send();

            return;
        }

        $this->mostrarModalMotivoPosse = false;

        $this->aplicarTemplate();
    }

    public function cancelarMotivoPosse(): void
    {
        $this->limparModalMotivoPosse();
    }

    private function limparModalMotivoPosse(): void
    {
        $this->mostrarModalMotivoPosse = false;
        $this->motivoPosseCodigo = null;
        $this->motivoPosseHistorico = null;
    }

    private function gravarDataAncora(Projeto $projeto, ?string $campo, string $valor): void
    {
        if (is_null($campo) || $campo === 'manual') {
            return;
        }

        if (str_starts_with($campo, 'projeto.')) {
            $atributo = substr($campo, strlen('projeto.'));

            if ($atributo === 'data_posse' && $this->motivoPossePreenchido()) {
                $projeto->motivo_alteracao_posse_codigo = $this->motivoPosseCodigo;
                $projeto->motivo_alteracao_posse_historico = $this->motivoPosseHistorico;
            }

            $projeto->{$atributo} = $valor;
            $projeto->save();

            return;
        }

        if (in_array($campo, ['inicio', 'fim'])) {
            $obra = $projeto->obras->first();
            if ($obra) {
                $obra->{$campo} = $valor;
                $obra->save();
            }

            return;
        }

        $projeto->{$campo} = $valor;
        $projeto->save();
    }

    private function calcularTimeline(Collection $fases): array
    {
        $limiteMinimo = now()->subYears(5);
        $limiteMaximo = now()->addYears(5);

        $datas = $fases->flatMap(fn ($f) => [
            $f->data_prevista_inicio,
            $f->data_prevista_fim,
        ])->filter()->filter(fn ($d) => $d->gte($limiteMinimo) && $d->lte($limiteMaximo));

        if ($datas->isEmpty()) {
            $inicio = now()->startOfMonth();
            $fim = now()->addMonths(12)->endOfMonth();
        } else {
            $inicio = $datas->min()->copy()->startOfMonth();
            $fim = $datas->max()->copy()->endOfMonth();
        }

        if ($inicio->gt(now())) {
            $inicio = now()->startOfMonth();
        }
        if ($fim->lt(now())) {
            $fim = now()->addMonths(3)->endOfMonth();
        }

        $totalDias = $inicio->diffInDays($fim);
        $diasHoje = $inicio->diffInDays(now());

        $meses = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fim)) {
            $meses[] = [
                'label' => $cursor->translatedFormat('M/y'),
                'dias' => $cursor->daysInMonth,
                'offset' => $inicio->diffInDays($cursor),
            ];
            $cursor->addMonth()->startOfMonth();
        }

        // Lista dia a dia (usada pelo gantt calendário)
        $dias = [];
        $hojeStr = now()->toDateString();
        $cursor = $inicio->copy();
        while ($cursor->lte($fim)) {
            $dias[] = [
                'data' => $cursor->toDateString(),
                'dia' => $cursor->day,
                'dow' => ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'][$cursor->dayOfWeek],
                'isWeekend' => $cursor->isWeekend(),
                'isHoje' => $cursor->toDateString() === $hojeStr,
                'mesLabel' => $cursor->translatedFormat('M/y'),
            ];
            $cursor = $cursor->copy()->addDay();
        }

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'totalDias' => max($totalDias, 1),
            'diasHoje' => $diasHoje,
            'meses' => $meses,
            'dias' => $dias,
        ];
    }

    public function selecionarProjeto(int $id): void
    {
        $this->projetoSelecionado = $id;
        $this->visualizacao = 'barras';
    }

    public function voltarParaMacro(): void
    {
        $this->projetoSelecionado = null;
        $this->editingFaseId = null;
    }

    public function toggleVersoes(): void
    {
        $this->mostrarVersoes = ! $this->mostrarVersoes;
        if (! $this->mostrarVersoes) {
            $this->versaoSelecionada = null;
        }
    }

    public function selecionarVersao(?string $timestamp): void
    {
        $this->versaoSelecionada = $timestamp;
    }

    public function voltarVersaoAtual(): void
    {
        $this->versaoSelecionada = null;
    }

    /**
     * Restaura todas as datas previstas das fases do projeto para o estado
     * que estavam no timestamp informado. Conforme reunião 09/05 — funciona
     * como um "Control+V" do cronograma.
     *
     * Cada fase que muda gera um novo CronogramaFaseHistorico com motivo
     * "Restauração de versão de DD/MM/AAAA HH:MM" — preservando a auditoria
     * (não sobrescreve histórico, adiciona nova entrada).
     */
    public function restaurarVersao(string $timestamp): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $fases = CronogramaFase::where('projeto_id', $this->projetoSelecionado)->get();
        $fasesReconstruidas = $this->reconstruirFasesNaVersao($fases, $timestamp);

        $reconstruidasPorId = $fasesReconstruidas->keyBy('id');
        $faseLabel = Carbon::parse($timestamp)->format('d/m/Y H:i');
        $motivo = "Restauração de versão de {$faseLabel}";
        $totalAlteradas = 0;

        foreach ($fases as $fase) {
            $reconstruida = $reconstruidasPorId->get($fase->id);
            if (! $reconstruida) {
                continue;
            }

            // Pula fases bloqueadas (concluídas, cadeado pós-contrato, etc).
            if (CronogramaTemplateService::bloqueadoRecalculo($fase->status)) {
                continue;
            }

            $alteradas = [];
            foreach (['data_prevista_inicio', 'data_prevista_fim'] as $campo) {
                $valorAtual = $fase->{$campo}?->toDateString();
                $valorRestaurar = $reconstruida->{$campo}?->toDateString();
                if ($valorAtual === $valorRestaurar) {
                    continue;
                }
                $fase->{$campo} = $valorRestaurar;
                $alteradas[$campo] = [$valorAtual, $valorRestaurar];
            }

            if (empty($alteradas)) {
                continue;
            }

            $fase->saveQuietly();

            foreach ($alteradas as $campo => [$antes, $depois]) {
                CronogramaService::registrarHistoricoDatas(
                    $fase,
                    $campo,
                    $antes,
                    $depois,
                    $motivo,
                    auth()->id(),
                    automatico: false,
                );
            }

            $totalAlteradas++;
        }

        $this->versaoSelecionada = null;
        $this->renderKey++;

        Notification::make()
            ->title("Versão de {$faseLabel} restaurada")
            ->body("{$totalAlteradas} fase(s) tiveram suas datas previstas revertidas.")
            ->success()
            ->send();
    }

    public function reconstruirFasesNaVersao(Collection $fases, string $timestamp): Collection
    {
        $alteracoesPosteriores = CronogramaFaseHistorico::where('created_at', '>', $timestamp)
            ->whereIn('cronograma_fase_id', $fases->pluck('id'))
            ->whereIn('campo_alterado', ['data_prevista_inicio', 'data_prevista_fim'])
            ->orderBy('created_at', 'desc')
            ->get();

        $fasesClone = $fases->map(fn ($f) => clone $f);

        foreach ($alteracoesPosteriores as $alt) {
            $fase = $fasesClone->firstWhere('id', $alt->cronograma_fase_id);
            if (! $fase) {
                continue;
            }

            $campo = $alt->campo_alterado;
            $fase->{$campo} = $alt->valor_anterior ? Carbon::parse($alt->valor_anterior) : null;
        }

        return $fasesClone;
    }

    public function abrirHistorico(?int $faseId = null): void
    {
        $this->historicoFaseId = $faseId;
        $this->historicoProjetoId = null;
        $this->mostrarHistorico = true;
    }

    public function abrirHistoricoProjeto(int $projetoId): void
    {
        $this->historicoProjetoId = $projetoId;
        $this->historicoFaseId = null;
        $this->mostrarHistorico = true;
    }

    public function abrirHistoricoGlobal(): void
    {
        $this->historicoProjetoId = 0;
        $this->historicoFaseId = null;
        $this->mostrarHistorico = true;
    }

    public function fecharHistorico(): void
    {
        $this->mostrarHistorico = false;
        $this->historicoFaseId = null;
        $this->historicoProjetoId = null;
    }

    public function abrirComentarios(int $faseId): void
    {
        $this->comentarioFaseId = $faseId;
        $this->novoComentarioFaseId = $faseId;
        $this->novoComentario = '';
        $this->mostrarComentarios = true;
    }

    public function abrirComentariosGlobal(): void
    {
        $this->comentarioFaseId = null;
        $this->novoComentarioFaseId = null;
        $this->novoComentario = '';
        $this->mostrarComentarios = true;
    }

    public function fecharComentarios(): void
    {
        $this->mostrarComentarios = false;
        $this->comentarioFaseId = null;
        $this->novoComentarioFaseId = null;
        $this->novoComentario = '';
    }

    public function salvarComentario(): void
    {
        $faseId = $this->novoComentarioFaseId ?? $this->comentarioFaseId;

        if (! $faseId || trim($this->novoComentario) === '') {
            if (! $faseId) {
                Notification::make()->title('Selecione uma fase')->warning()->send();
            }

            return;
        }

        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $fase->comentarios()->create([
            'usuario_id' => auth()->id(),
            'conteudo' => trim($this->novoComentario),
        ]);

        $this->novoComentario = '';

        Notification::make()->title('Comentário adicionado')->success()->send();
    }

    // ------------------------------------------------------------------
    // Subitens (checklist sim/não) da fase — usado principalmente nas
    // fases de Recebimento de Projetos (Arquitetura/Complementares).
    // ------------------------------------------------------------------

    /**
     * Títulos em edição para novos subitens raiz, indexados por fase.
     *
     * @var array<int, string>
     */
    public array $novoSubitemTitulos = [];

    /**
     * Compatibilidade com telas Livewire já carregadas antes de o campo passar
     * a ser indexado por fase.
     */
    public string $novoSubitemTitulo = '';

    public ?int $novoSubitemFaseId = null;

    public string $novoFilhoTitulo = '';

    public ?int $expandindoFilhosDeItemId = null;

    /**
     * IDs das fases com checklist expandido inline.
     *
     * @var array<int, int>
     */
    public array $fasesExpandidas = [];

    public function alternarExpansaoFase(int $faseId): void
    {
        if (in_array($faseId, $this->fasesExpandidas, true)) {
            $this->fasesExpandidas = array_values(array_diff($this->fasesExpandidas, [$faseId]));
        } else {
            $this->fasesExpandidas[] = $faseId;
        }
    }

    public function expandirFaseEFocarInput(int $faseId): void
    {
        if (! in_array($faseId, $this->fasesExpandidas, true)) {
            $this->fasesExpandidas[] = $faseId;
        }
        // O foco no input é feito via JS depois do re-render (dispatch para o Alpine)
        $this->dispatch('focarInputNovaAtividade', faseId: $faseId);
    }

    /**
     * Atualiza o tri-estado (Sim/Não/Risco) dos subitens de Liberação de Posse.
     * O observer de CronogramaFaseItem sincroniza `recebido` automaticamente
     * (SIM=true; NAO/RISCO=false) para manter o cálculo de % conclusão consistente.
     */
    public function alterarStatusLiberacao(int $itemId, string $status): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $novoStatus = \App\Enums\StatusLiberacaoPosse::tryFrom($status);
        if (! $novoStatus) {
            return;
        }

        // Toggle: clicar no mesmo status remove a marcação (volta para null/pendente)
        if ($item->status_liberacao === $novoStatus) {
            $item->status_liberacao = null;
            $item->recebido = false;
        } else {
            $item->status_liberacao = $novoStatus;
        }
        $item->save();

        $this->renderKey++;
    }

    public function alternarRecebidoSubitem(int $itemId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $novoRecebido = ! $item->recebido;

        if ($novoRecebido && ! $this->subitemDependenciasConcluidas($item)) {
            return;
        }

        if (
            ! $novoRecebido
            && CronogramaFaseItemDependencia::where('depende_de_item_id', $item->id)
                ->whereHas('item', fn ($query) => $query->where('recebido', true))
                ->exists()
        ) {
            Notification::make()
                ->title('Há subitens dependentes concluídos')
                ->body('Desmarque primeiro os subitens que dependem deste item.')
                ->warning()
                ->send();

            return;
        }

        $item->recebido = $novoRecebido;
        $item->save();

        $this->renderKey++;
    }

    public function adicionarSubitem(int $faseId): void
    {
        $titulo = trim((string) ($this->novoSubitemTitulos[$faseId] ?? $this->novoSubitemTitulo));
        if ($titulo === '') {
            Notification::make()->title('Informe um título para o subitem')->warning()->send();

            return;
        }

        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $ordem = (int) ($fase->itens()->whereNull('parent_id')->max('ordem') ?? -1) + 1;

        CronogramaFaseItem::create([
            'cronograma_fase_id' => $faseId,
            'titulo' => $titulo,
            'recebido' => false,
            'ordem' => $ordem,
            'origem' => 'manual',
        ]);

        unset($this->novoSubitemTitulos[$faseId]);
        $this->novoSubitemTitulo = '';
        $this->novoSubitemFaseId = null;
        $this->renderKey++;
    }

    public function removerSubitem(int $itemId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->delete();

        $this->renderKey++;
    }

    public function adicionarFilhoItem(int $parentId): void
    {
        $titulo = trim($this->novoFilhoTitulo);
        if ($titulo === '') {
            Notification::make()->title('Informe um título para o sub-item')->warning()->send();

            return;
        }

        $parent = CronogramaFaseItem::find($parentId);
        if (! $parent) {
            return;
        }

        $profundidadeParent = 0;
        $ancestral = $parent;
        while ($ancestral->parent_id !== null) {
            $profundidadeParent++;
            $ancestral = $ancestral->parent;
            if (! $ancestral || $profundidadeParent > 10) {
                break;
            }
        }
        if ($profundidadeParent >= 2) {
            Notification::make()
                ->title('Profundidade máxima atingida')
                ->body('Subitens podem ter no máximo 4 níveis (L1 = fase principal).')
                ->warning()
                ->send();

            return;
        }

        $ordem = (int) ($parent->children()->max('ordem') ?? -1) + 1;

        CronogramaFaseItem::create([
            'cronograma_fase_id' => $parent->cronograma_fase_id,
            'parent_id' => $parentId,
            'titulo' => $titulo,
            'recebido' => false,
            'ordem' => $ordem,
            'origem' => 'manual',
        ]);

        $this->novoFilhoTitulo = '';
        $this->expandindoFilhosDeItemId = null;
        $this->renderKey++;
    }

    public function alternarAdicionarFilho(int $itemId): void
    {
        $this->expandindoFilhosDeItemId = ($this->expandindoFilhosDeItemId === $itemId) ? null : $itemId;
        $this->novoFilhoTitulo = '';
    }

    public function adicionarDependenciaSubitem(int $itemId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->dependencias()->create([
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 1,
        ]);

        $this->renderKey++;
    }

    public function removerDependenciaSubitem(int $dependenciaId): void
    {
        $dependencia = CronogramaFaseItemDependencia::find($dependenciaId);
        if (! $dependencia) {
            return;
        }

        $item = $dependencia->item;
        $dependencia->delete();

        if ($item) {
            $this->recalcularDatasSubitemPorDependencias($item);
        }

        $this->renderKey++;
    }

    public function salvarAlvoDependenciaSubitem(int $dependenciaId, ?string $alvo): void
    {
        $dependencia = CronogramaFaseItemDependencia::with('item.fase')->find($dependenciaId);
        if (! $dependencia || ! $dependencia->item) {
            return;
        }

        $alvo = blank($alvo) ? null : (string) $alvo;
        if ($alvo === null) {
            $dependencia->update([
                'depende_de_fase_id' => null,
                'depende_de_item_id' => null,
            ]);
            $this->recalcularDatasSubitemPorDependencias($dependencia->item);
            $this->renderKey++;

            return;
        }

        [$tipo, $alvoId] = array_pad(explode(':', $alvo, 2), 2, null);
        $alvoId = (int) $alvoId;

        if ($tipo === 'fase') {
            $this->salvarAlvoDependenciaSubitemFase($dependencia, $alvoId);

            return;
        }

        if ($tipo === 'item') {
            $this->salvarAlvoDependenciaSubitemItem($dependencia, $alvoId);

            return;
        }

        Notification::make()->title('Dependência inválida')->warning()->send();
    }

    public function salvarGatilhoDependenciaSubitem(int $dependenciaId, string $gatilho): void
    {
        $dependencia = CronogramaFaseItemDependencia::with('item')->find($dependenciaId);
        if (! $dependencia || ! GatilhoTemplateFase::tryFrom($gatilho)) {
            return;
        }

        $dependencia->gatilho = $gatilho;
        $dependencia->save();

        if ($dependencia->item) {
            $this->recalcularDatasSubitemPorDependencias($dependencia->item);
        }

        $this->renderKey++;
    }

    public function salvarGapDependenciaSubitem(int $dependenciaId, int|string|null $gap): void
    {
        $dependencia = CronogramaFaseItemDependencia::with('item')->find($dependenciaId);
        if (! $dependencia) {
            return;
        }

        $dependencia->gap_dias = (int) ($gap ?? 0);
        $dependencia->save();

        if ($dependencia->item) {
            $this->recalcularDatasSubitemPorDependencias($dependencia->item);
        }

        $this->renderKey++;
    }

    public function salvarDependenciaSubitem(int $itemId, ?string $dependencia): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $dependencia = blank($dependencia) ? null : (string) $dependencia;

        if ($dependencia === null) {
            $item->depende_de_item_id = null;
            $item->depende_de_fase_id = null;
            $item->save();
            $this->renderKey++;

            return;
        }

        if (is_numeric($dependencia)) {
            $dependencia = 'item:'.$dependencia;
        }

        [$tipo, $dependenciaId] = array_pad(explode(':', $dependencia, 2), 2, null);
        $dependenciaId = (int) $dependenciaId;

        if ($tipo === 'fase') {
            $this->salvarDependenciaSubitemFase($item, $dependenciaId);

            return;
        }

        if ($tipo !== 'item') {
            Notification::make()->title('Dependência inválida')->warning()->send();

            return;
        }

        if ($dependenciaId === $item->id) {
            Notification::make()->title('Um subitem não pode depender dele mesmo')->warning()->send();

            return;
        }

        $faseItem = CronogramaFase::find($item->cronograma_fase_id);
        $dependenciaItem = CronogramaFaseItem::whereKey($dependenciaId)
            ->whereHas('fase', fn ($query) => $query->where('projeto_id', $faseItem?->projeto_id))
            ->first();

        if (! $dependenciaItem) {
            Notification::make()->title('Dependência inválida para esta obra')->warning()->send();

            return;
        }

        if ($this->subitemDependencyCreatesCycle($item->id, $dependenciaItem->id)) {
            Notification::make()->title('Dependência circular não permitida')->warning()->send();

            return;
        }

        if ($item->recebido && ! $dependenciaItem->recebido) {
            Notification::make()
                ->title('Dependência pendente')
                ->body('Um subitem já recebido só pode depender de outro subitem recebido.')
                ->warning()
                ->send();

            return;
        }

        $item->depende_de_item_id = $dependenciaItem->id;
        $item->depende_de_fase_id = null;
        $item->save();
        $this->renderKey++;
    }

    private function salvarDependenciaSubitemFase(CronogramaFaseItem $item, int $faseId): void
    {
        if ($faseId === $item->cronograma_fase_id) {
            Notification::make()
                ->title('Dependência circular não permitida')
                ->body('Um subitem não pode depender da própria fase.')
                ->warning()
                ->send();

            return;
        }

        $faseItem = CronogramaFase::find($item->cronograma_fase_id);
        $dependenciaFase = CronogramaFase::whereKey($faseId)
            ->where('projeto_id', $faseItem?->projeto_id)
            ->first();

        if (! $dependenciaFase) {
            Notification::make()->title('Dependência inválida para esta obra')->warning()->send();

            return;
        }

        if ($item->recebido && ! $this->faseConcluidaParaDependencia($dependenciaFase)) {
            Notification::make()
                ->title('Dependência pendente')
                ->body('Um subitem já recebido só pode depender de uma fase concluída.')
                ->warning()
                ->send();

            return;
        }

        $item->depende_de_fase_id = $dependenciaFase->id;
        $item->depende_de_item_id = null;
        $item->save();
        $this->renderKey++;
    }

    private function faseConcluidaParaDependencia(?CronogramaFase $fase): bool
    {
        if (! $fase) {
            return false;
        }

        $statusFinais = [
            StatusCronograma::CONCLUIDO,
            StatusCronograma::REALIZADO,
            StatusCronograma::ASSINADO,
            StatusCronograma::FINALIZADO,
            StatusCronograma::PRONTO,
        ];

        return in_array($fase->status, $statusFinais, true)
            || $fase->percentual_conclusao >= 100
            || filled($fase->data_realizada_fim);
    }

    private function subitemDependencyCreatesCycle(int $itemId, int $dependenciaId): bool
    {
        $visitados = [];
        $atualId = $dependenciaId;

        while ($atualId) {
            if ($atualId === $itemId || isset($visitados[$atualId])) {
                return true;
            }

            $visitados[$atualId] = true;
            $atualId = (int) (CronogramaFaseItemDependencia::where('cronograma_fase_item_id', $atualId)
                ->whereNotNull('depende_de_item_id')
                ->value('depende_de_item_id') ?? 0);
        }

        return false;
    }

    private function salvarAlvoDependenciaSubitemFase(CronogramaFaseItemDependencia $dependencia, int $faseId): void
    {
        $item = $dependencia->item;
        if (! $item) {
            return;
        }

        if ($faseId === $item->cronograma_fase_id) {
            Notification::make()
                ->title('Dependência circular não permitida')
                ->body('Um subitem não pode depender da própria fase.')
                ->warning()
                ->send();

            return;
        }

        $faseItem = CronogramaFase::find($item->cronograma_fase_id);
        $fase = CronogramaFase::whereKey($faseId)
            ->where('projeto_id', $faseItem?->projeto_id)
            ->first();

        if (! $fase) {
            Notification::make()->title('Dependência inválida para esta obra')->warning()->send();

            return;
        }

        $dependencia->update([
            'depende_de_fase_id' => $fase->id,
            'depende_de_item_id' => null,
        ]);

        $this->recalcularDatasSubitemPorDependencias($item);
        $this->renderKey++;
    }

    private function salvarAlvoDependenciaSubitemItem(CronogramaFaseItemDependencia $dependencia, int $itemDependenciaId): void
    {
        $item = $dependencia->item;
        if (! $item) {
            return;
        }

        if ($itemDependenciaId === $item->id) {
            Notification::make()->title('Um subitem não pode depender dele mesmo')->warning()->send();

            return;
        }

        $faseItem = CronogramaFase::find($item->cronograma_fase_id);
        $itemDependencia = CronogramaFaseItem::whereKey($itemDependenciaId)
            ->whereHas('fase', fn ($query) => $query->where('projeto_id', $faseItem?->projeto_id))
            ->first();

        if (! $itemDependencia) {
            Notification::make()->title('Dependência inválida para esta obra')->warning()->send();

            return;
        }

        if ($this->subitemDependencyCreatesCycle($item->id, $itemDependencia->id)) {
            Notification::make()->title('Dependência circular não permitida')->warning()->send();

            return;
        }

        $dependencia->update([
            'depende_de_fase_id' => null,
            'depende_de_item_id' => $itemDependencia->id,
        ]);

        $this->recalcularDatasSubitemPorDependencias($item);
        $this->renderKey++;
    }

    private function subitemDependenciasConcluidas(CronogramaFaseItem $item): bool
    {
        foreach ($item->dependencias()->with(['dependeDeFase', 'dependeDeItem'])->get() as $dependencia) {
            if ($dependencia->depende_de_fase_id && ! $this->faseConcluidaParaDependencia($dependencia->dependeDeFase)) {
                Notification::make()
                    ->title('Dependência pendente')
                    ->body('Conclua as fases dependentes antes de marcar este subitem como recebido.')
                    ->warning()
                    ->send();

                return false;
            }

            if ($dependencia->depende_de_item_id && ! $dependencia->dependeDeItem?->recebido) {
                Notification::make()
                    ->title('Dependência pendente')
                    ->body('Conclua os subitens dependentes antes de marcar este subitem como recebido.')
                    ->warning()
                    ->send();

                return false;
            }
        }

        return true;
    }

    private function recalcularDatasSubitemPorDependencias(CronogramaFaseItem $item): void
    {
        $dependencias = $item->dependencias()->with(['dependeDeFase', 'dependeDeItem'])->get();
        if ($dependencias->isEmpty()) {
            return;
        }

        $candidatos = [];
        $duracao = ($item->data_prevista_inicio && $item->data_prevista_fim)
            ? max(1, (int) $item->data_prevista_inicio->diffInDays($item->data_prevista_fim) + 1)
            : 1;

        foreach ($dependencias as $dependencia) {
            $datas = null;

            if ($dependencia->depende_de_fase_id && $dependencia->dependeDeFase) {
                $datas = [
                    'inicio' => $dependencia->dependeDeFase->data_prevista_inicio,
                    'fim' => $dependencia->dependeDeFase->data_prevista_fim,
                ];
            } elseif ($dependencia->depende_de_item_id && $dependencia->dependeDeItem) {
                $datas = [
                    'inicio' => $dependencia->dependeDeItem->data_prevista_inicio,
                    'fim' => $dependencia->dependeDeItem->data_prevista_fim,
                ];
            }

            if (! $datas || ! $datas['inicio']) {
                continue;
            }

            $gatilho = $dependencia->gatilho instanceof GatilhoTemplateFase
                ? $dependencia->gatilho
                : GatilhoTemplateFase::tryFrom((string) $dependencia->gatilho) ?? GatilhoTemplateFase::FIM_ANTERIOR;

            $fimDependencia = $datas['fim'] ?? $datas['inicio'];

            $candidatos[] = match ($gatilho) {
                GatilhoTemplateFase::INICIO_ANTERIOR => $datas['inicio']->copy()->addDays((int) $dependencia->gap_dias),
                GatilhoTemplateFase::FIM_ANTERIOR => $fimDependencia->copy()->addDays((int) $dependencia->gap_dias + 1),
                GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA => $fimDependencia->copy()->addDays((int) $dependencia->gap_dias),
                GatilhoTemplateFase::FIM_JUNTO => $fimDependencia->copy()->addDays((int) $dependencia->gap_dias - ($duracao - 1)),
                GatilhoTemplateFase::FIM_ANTES_INICIO => $datas['inicio']->copy()->addDays((int) $dependencia->gap_dias - ($duracao - 1)),
            };
        }

        if (empty($candidatos)) {
            return;
        }

        $inicio = collect($candidatos)->sort()->last();
        $item->data_prevista_inicio = $inicio;
        $item->data_prevista_fim = $inicio->copy()->addDays($duracao - 1);
        $item->save();

        // Atualiza o item pai (e ascendentes) após a cascata de dependência
        if ($item->parent_id) {
            $this->recalcularDatasItemPai($item->parent_id);
        } else {
            $this->recalcularDatasFaseDeItens($item->cronograma_fase_id);
        }

        // Cascateia para os próprios dependentes deste item
        $this->recalcularDependentesSubitem($item);
    }

    private function recalcularDependentesSubitem(CronogramaFaseItem $item): void
    {
        CronogramaFaseItemDependencia::with('item')
            ->where('depende_de_item_id', $item->id)
            ->get()
            ->each(function (CronogramaFaseItemDependencia $dependencia): void {
                if ($dependencia->item) {
                    $this->recalcularDatasSubitemPorDependencias($dependencia->item);
                }
            });
    }

    /**
     * Edição inline de datas de um subitem (previstas ou realizadas).
     *
     * @param  string  $campo  data_prevista_inicio|data_prevista_fim|data_realizada_inicio|data_realizada_fim
     */
    public function salvarDataInlineSubitem(int $itemId, string $campo, ?string $valor): void
    {
        $camposPermitidos = [
            'data_prevista_inicio',
            'data_prevista_fim',
            'data_realizada_inicio',
            'data_realizada_fim',
        ];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->{$campo} = blank($valor) ? null : $valor;

        // Marcar como data definida manualmente para preservar ao reajustar planejamento
        if (in_array($campo, ['data_prevista_inicio', 'data_prevista_fim'], true)) {
            $item->data_prevista_manual = true;
        }

        // Ao alterar data de início, recalcula data_fim pela duração (se definida)
        if ($campo === 'data_prevista_inicio' && ! blank($valor) && $item->duracao_dias > 0) {
            $item->data_prevista_fim = \Carbon\Carbon::parse($valor)->addDays($item->duracao_dias - 1);
        }

        $item->save();

        // Atualiza datas do item pai (e ascendentes) como min/max dos filhos,
        // chegando até a fase. Se já é item raiz, atualiza a fase diretamente.
        if ($item->parent_id) {
            $this->recalcularDatasItemPai($item->parent_id);
        } else {
            $this->recalcularDatasFaseDeItens($item->cronograma_fase_id);
        }

        $this->recalcularDependentesSubitem($item);
        if (in_array($campo, ['data_prevista_inicio', 'data_prevista_fim'], true)) {
            $this->sincronizarDatasTaskDeSubitem($item->fresh());
        }
        $this->renderKey++;
    }

    public function salvarObservacoesSubitem(int $itemId, ?string $valor): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->observacoes = blank($valor) ? null : $valor;
        $item->save();

        $this->renderKey++;
    }

    public function salvarTituloSubitem(int $itemId, string $valor): void
    {
        $valor = trim($valor);
        if ($valor === '') {
            return;
        }

        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->titulo = $valor;
        $item->save();

        $this->renderKey++;
    }

    // ------------------------------------------------------------------
    // Criação rápida de planejamento (projeto apenas com nome).
    // ------------------------------------------------------------------

    public function criarNovoPlanejamento(): void
    {
        $this->validate(
            ['novoPlanejamentoNome' => 'required|min:2'],
            ['novoPlanejamentoNome.required' => 'Informe o nome do planejamento.',
             'novoPlanejamentoNome.min'      => 'O nome precisa ter ao menos 2 caracteres.']
        );

        $projeto = \App\Models\Projeto::create([
            'nome'          => trim($this->novoPlanejamentoNome),
            'user_id'       => auth()->id(),
            'sem_fases_auto' => true,
        ]);

        $this->mostrarModalNovoPlanejamento = false;
        $this->novoPlanejamentoNome = '';

        $this->redirect(static::getUrl(['projeto' => $projeto->id]));
    }

    // Fases ad-hoc (personalizadas por projeto) e visibilidade local.
    // ------------------------------------------------------------------

    public string $novaFasePersonalizadaTitulo = '';

    public function adicionarFasePersonalizada(): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $titulo = trim($this->novaFasePersonalizadaTitulo);
        if ($titulo === '') {
            Notification::make()->title('Informe um título para a fase')->warning()->send();

            return;
        }

        $projeto = Projeto::find($this->projetoSelecionado);
        if (! $projeto) {
            return;
        }

        $ordem = (int) (CronogramaFase::where('projeto_id', $projeto->id)->max('ordem') ?? 0) + 1;

        CronogramaFase::create([
            'projeto_id' => $projeto->id,
            'fase' => FaseCronograma::PERSONALIZADA->value,
            'titulo_personalizado' => $titulo,
            'ordem' => $ordem,
            'marco' => false,
            'status' => StatusCronograma::NAO_INICIADO->value,
            'percentual_conclusao' => 0,
            'visivel' => true,
        ]);

        $this->novaFasePersonalizadaTitulo = '';
        $this->renderKey++;
        Notification::make()->title('Fase adicionada')->success()->send();
    }

    public function adicionarFasePersonalizadaEFecharModal(): void
    {
        if (trim($this->novaFasePersonalizadaTitulo) === '') {
            $this->addError('novaFasePersonalizadaTitulo', 'Informe o nome da fase.');

            return;
        }

        $this->adicionarFasePersonalizada();
        $this->mostrarModalNovaFase = false;
    }

    public function alternarVisibilidadeFase(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $fase->visivel = ! $fase->isVisivel();
        $fase->save();

        $this->renderKey++;
    }

    private const STATUS_FINAIS = [
        StatusCronograma::CONCLUIDO,
        StatusCronograma::REALIZADO,
        StatusCronograma::ASSINADO,
        StatusCronograma::FINALIZADO,
        StatusCronograma::PRONTO,
    ];

    /**
     * Status que NÃO disparam modal — apenas gravam direto sem pedir data
     * do usuário. Usado para transições "de volta ao início" (limpa datas).
     */
    private const STATUS_SEM_MODAL = [
        StatusCronograma::NAO_INICIADO,
        StatusCronograma::NA,
        StatusCronograma::INDEFINIDO,
        StatusCronograma::NAO_REALIZADO,
    ];

    public function alterarStatusFase(int $faseId, string $novoStatus): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $statusEnum = StatusCronograma::tryFrom($novoStatus);
        if (! $statusEnum) {
            return;
        }

        $permitidos = $fase->fase->statusDisponiveis();
        if (! in_array($statusEnum, $permitidos, true)) {
            return;
        }

        // Status que não precisam de data (voltam o estado ao inicial) — grava direto.
        if (in_array($statusEnum, self::STATUS_SEM_MODAL, true)) {
            $fase->status = $statusEnum;
            $fase->save();
            $this->renderKey++;

            return;
        }

        // Demais status (finais ou intermediários) exigem confirmação com data informada
        // pelo usuário — evita que o observer use `now()` automaticamente.
        $this->confirmacaoStatusFaseId = $faseId;
        $this->confirmacaoStatusValue = $statusEnum->value;
        $this->confirmacaoStatusLabel = $statusEnum->label();
        $this->confirmacaoFaseLabel = $fase->fase->label();
        $this->confirmacaoFaseMarco = (bool) $fase->marco || ($fase->data_prevista_inicio && $fase->data_prevista_fim && $fase->data_prevista_inicio->equalTo($fase->data_prevista_fim));
        $this->confirmacaoApenasInicio = ! in_array($statusEnum, self::STATUS_FINAIS, true);

        $hoje = now()->format('Y-m-d');
        $this->confirmacaoDataRealInicio = $fase->data_realizada_inicio?->format('Y-m-d')
            ?? $fase->data_prevista_inicio?->format('Y-m-d')
            ?? $hoje;
        $this->confirmacaoDataRealFim = $this->confirmacaoApenasInicio
            ? null
            : ($fase->data_realizada_fim?->format('Y-m-d') ?? $hoje);
    }

    public function confirmarFinalizacaoStatus(): void
    {
        if (! $this->confirmacaoStatusFaseId || ! $this->confirmacaoStatusValue) {
            return;
        }

        $fase = CronogramaFase::find($this->confirmacaoStatusFaseId);
        if (! $fase) {
            $this->cancelarFinalizacaoStatus();

            return;
        }

        $statusEnum = StatusCronograma::tryFrom($this->confirmacaoStatusValue);
        if (! $statusEnum) {
            $this->cancelarFinalizacaoStatus();

            return;
        }

        // Status intermediário (apenas início): grava data_realizada_inicio e limpa fim.
        if ($this->confirmacaoApenasInicio) {
            if (! $this->confirmacaoDataRealInicio) {
                Notification::make()
                    ->title('Data de início obrigatória')
                    ->body('Informe a data em que o status passou a valer.')
                    ->warning()
                    ->send();

                return;
            }

            $fase->data_realizada_inicio = $this->confirmacaoDataRealInicio;
            $fase->data_realizada_fim = null;
            $fase->status = $statusEnum;
            $fase->save();

            $this->cancelarFinalizacaoStatus();
            $this->renderKey++;

            return;
        }

        // Status final: exige data de conclusão.
        if (! in_array($statusEnum, self::STATUS_FINAIS, true)) {
            $this->cancelarFinalizacaoStatus();

            return;
        }

        if (! $this->confirmacaoDataRealFim) {
            Notification::make()
                ->title('Data de execução obrigatória')
                ->body('Informe a data em que a fase foi concluída.')
                ->warning()
                ->send();

            return;
        }

        $dataFim = $this->confirmacaoDataRealFim;
        $dataInicio = $this->confirmacaoFaseMarco
            ? $dataFim
            : ($this->confirmacaoDataRealInicio ?: $dataFim);

        if (! $this->confirmacaoFaseMarco && $dataInicio > $dataFim) {
            Notification::make()
                ->title('Datas inválidas')
                ->body('A data de início real não pode ser posterior à data de fim.')
                ->danger()
                ->send();

            return;
        }

        $fase->data_realizada_inicio = $dataInicio;
        $fase->data_realizada_fim = $dataFim;
        $fase->status = $statusEnum;
        $fase->save();

        $this->cancelarFinalizacaoStatus();
        $this->renderKey++;
    }

    public function cancelarFinalizacaoStatus(): void
    {
        $this->confirmacaoStatusFaseId = null;
        $this->confirmacaoStatusValue = null;
        $this->confirmacaoStatusLabel = null;
        $this->confirmacaoFaseLabel = null;
        $this->confirmacaoFaseMarco = false;
        $this->confirmacaoDataRealInicio = null;
        $this->confirmacaoDataRealFim = null;
        $this->confirmacaoApenasInicio = false;
    }

    public function abrirEdicaoFase(int $faseId): void
    {
        $fase = CronogramaFase::with('templateFase')->find($faseId);
        if (! $fase) {
            return;
        }

        $this->editingFaseId = $faseId;
        $this->editFaseVisivel = $fase->isVisivel();
        $this->editFaseValue = $fase->fase->value;
        $this->editConfirmacaoShift = false;
        $this->editFaseDuracaoOriginal = ($fase->data_prevista_inicio && $fase->data_prevista_fim)
            ? (int) $fase->data_prevista_inicio->diffInDays($fase->data_prevista_fim) + 1
            : null;
        $this->editDataPrevistaInicio = $fase->data_prevista_inicio?->format('Y-m-d');
        $this->editDataPrevistaFim = $fase->data_prevista_fim?->format('Y-m-d');
        $this->editStatus = $fase->status->value;
        $this->editPercentual = $fase->percentual_conclusao;
        $this->editObservacoes = $fase->observacoes ?? '';
        $this->editMotivoDatas = '';
        $this->mostrarMotivoDatas = false;

        $regra = $fase->regraEfetiva();
        $this->editRegraDuracaoDias = $regra->duracao_dias;
        $this->editRegraTipoDias = $regra->tipo_dias instanceof TipoDiasTemplate
            ? $regra->tipo_dias->value
            : (string) ($regra->tipo_dias ?? 'corridos');
        $this->editRegraCustomizada = $fase->regra_customizada;
        $this->editRegraElastica = (bool) $regra->elastica;

        $this->editDependencias = $regra->dependencias
            ->map(fn ($d) => [
                'alvo' => $this->dependenciaParaAlvo($d),
                'gatilho' => $d->gatilho instanceof GatilhoTemplateFase
                    ? $d->gatilho->value
                    : (string) $d->gatilho,
                'gap_dias' => (int) $d->gap_dias,
            ])
            ->values()
            ->all();
    }

    public function adicionarDependenciaObra(): void
    {
        $this->editDependencias[] = [
            'alvo' => '',
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 1,
        ];
    }

    public function removerDependenciaObra(int $index): void
    {
        unset($this->editDependencias[$index]);
        $this->editDependencias = array_values($this->editDependencias);
    }

    /**
     * Salva edição inline do nome do projeto diretamente na página do cronograma.
     * Permite editar o nome sem sair do contexto, respeitando a Policy de Projeto.
     */
    public function salvarNomeProjeto(?string $valor): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $projeto = Projeto::find($this->projetoSelecionado);
        if (! $projeto) {
            return;
        }

        if (! auth()->user()->can('update', $projeto)) {
            Notification::make()->title('Sem permissão para editar o projeto')->danger()->send();

            return;
        }

        $valor = is_string($valor) ? trim($valor) : null;
        if (blank($valor)) {
            Notification::make()->title('Nome do projeto não pode ficar vazio')->warning()->send();

            return;
        }

        if ($projeto->nome === $valor) {
            return;
        }

        $projeto->nome = $valor;
        $projeto->save();

        $this->renderKey++;

        Notification::make()->title('Nome do projeto atualizado')->success()->send();
    }

    /**
     * Salva edição inline de uma célula de data (prevista ou realizada)
     * sem abrir o modal completo. Usado quando o usuário clica direto na
     * célula do cronograma e altera a data.
     *
     * Para datas PREVISTAS, preserva a duração original da fase (ajusta o
     * par início/fim) e propaga o shift para as fases dependentes via
     * `shiftComponent`. Para datas REALIZADAS, apenas grava o valor.
     *
     * @param  string  $campo  Um de: data_prevista_inicio, data_prevista_fim,
     *                         data_realizada_inicio, data_realizada_fim
     */
    public function salvarDataInline(int $faseId, string $campo, ?string $valor): void
    {
        $camposPermitidos = [
            'data_prevista_inicio',
            'data_prevista_fim',
            'data_realizada_inicio',
            'data_realizada_fim',
        ];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $valor = blank($valor) ? null : $valor;
        $valorAntes = $fase->{$campo}?->toDateString();

        if ($valorAntes === $valor) {
            return;
        }

        // ----- Data REALIZADA: grava direto, sem propagar cascata. -----
        if (in_array($campo, ['data_realizada_inicio', 'data_realizada_fim'], true)) {
            if ($campo === 'data_realizada_fim' && $valor && $fase->data_realizada_inicio && $valor < $fase->data_realizada_inicio->toDateString()) {
                Notification::make()->title('Data realizada de fim anterior ao início')->danger()->send();

                return;
            }
            if ($campo === 'data_realizada_inicio' && $valor && $fase->data_realizada_fim && $valor > $fase->data_realizada_fim->toDateString()) {
                Notification::make()->title('Data realizada de início posterior ao fim')->danger()->send();

                return;
            }

            $fase->{$campo} = $valor;
            $fase->save();

            CronogramaService::registrarHistoricoDatas($fase, $campo, $valorAntes, $valor, 'Edição inline', auth()->id());

            // Inferir status com base nas datas realizadas
            $disponiveis = $fase->fase?->statusDisponiveis() ?? [];
            $novoStatus = null;

            if ($campo === 'data_realizada_fim') {
                if ($valor && ! in_array($fase->status, self::STATUS_FINAIS, true)) {
                    $novoStatus = collect($disponiveis)
                        ->first(fn ($s) => in_array($s, self::STATUS_FINAIS, true));
                } elseif (! $valor && in_array($fase->status, self::STATUS_FINAIS, true)) {
                    $novoStatus = $fase->data_realizada_inicio
                        ? collect($disponiveis)->first(fn ($s) => $s === StatusCronograma::EM_ANDAMENTO)
                        : collect($disponiveis)->first(fn ($s) => $s === StatusCronograma::NAO_INICIADO);
                }
            } elseif ($campo === 'data_realizada_inicio' && $valor
                && ! $fase->data_realizada_fim
                && in_array($fase->status, [StatusCronograma::NAO_INICIADO, StatusCronograma::ATRASADO], true)
            ) {
                $novoStatus = collect($disponiveis)
                    ->first(fn ($s) => $s === StatusCronograma::EM_ANDAMENTO);
            }

            if ($novoStatus) {
                $fase->status = $novoStatus;
                $fase->save();
            }

            $this->renderKey++;

            return;
        }

        // ----- Data PREVISTA: preserva duração + propaga shift nas dependentes. -----
        if (blank($valor)) {
            // Limpeza de data prevista — grava direto; sem dados suficientes para shift.
            $fase->{$campo} = null;
            $fase->save();

            CronogramaService::registrarHistoricoDatas($fase, $campo, $valorAntes, null, 'Edição inline', auth()->id());

            $this->renderKey++;

            return;
        }

        $inicioAtual = $fase->data_prevista_inicio;
        $fimAtual = $fase->data_prevista_fim;
        $duracao = ($inicioAtual && $fimAtual)
            ? (int) $inicioAtual->diffInDays($fimAtual)
            : null;

        if ($campo === 'data_prevista_fim' && $valor && $inicioAtual && $valor < $inicioAtual->toDateString()) {
            Notification::make()->title('Data de fim anterior ao início')->danger()->send();

            return;
        }
        if ($campo === 'data_prevista_inicio' && $valor && $fimAtual && $valor > $fimAtual->toDateString() && $duracao === null) {
            Notification::make()->title('Data de início posterior ao fim')->danger()->send();

            return;
        }

        $novoInicio = $inicioAtual?->toDateString();
        $novoFim = $fimAtual?->toDateString();

        if ($campo === 'data_prevista_inicio') {
            $novoInicio = $valor;
            // Preserva duração ajustando o fim automaticamente.
            if ($duracao !== null) {
                $novoFim = Carbon::parse($valor)->addDays($duracao)->toDateString();
            } elseif ($fimAtual === null) {
                $novoFim = $valor;
            }
        } else { // data_prevista_fim
            $novoFim = $valor;
            if ($duracao !== null) {
                $novoInicio = Carbon::parse($valor)->subDays($duracao)->toDateString();
            } elseif ($inicioAtual === null) {
                $novoInicio = $valor;
            }
        }

        $inicioAntes = $inicioAtual?->toDateString();
        $fimAntes = $fimAtual?->toDateString();

        $fase->data_prevista_inicio = $novoInicio;
        $fase->data_prevista_fim = $novoFim;
        $fase->save();

        if ($inicioAntes !== $novoInicio) {
            CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_inicio', $inicioAntes, $novoInicio, 'Edição inline', auth()->id());
        }
        if ($fimAntes !== $novoFim) {
            CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_fim', $fimAntes, $novoFim, 'Edição inline', auth()->id());
        }

        // Propaga shift para a componente conectada (dependentes e antecessoras
        // transitivas), preservando duração e gaps das outras fases.
        $deltaInicio = $this->calcularDeltaDias($inicioAntes, $novoInicio);
        $deltaFim = $this->calcularDeltaDias($fimAntes, $novoFim);

        $service = new CronogramaTemplateService;
        if ($deltaInicio !== null && $deltaFim !== null && $deltaInicio === $deltaFim && $deltaInicio !== 0) {
            // Propaga shift para subitems da fase diretamente editada
            $this->propagarShiftSubitensDaFase($fase->id, $deltaInicio);
            $fase->refresh();
            $service->shiftComponent($fase, $deltaInicio);
        }

        $this->renderKey++;
    }

    public function salvarFase(): void
    {
        $fase = CronogramaFase::with('templateFase')->find($this->editingFaseId);
        if (! $fase) {
            return;
        }

        $dataPrevistaInicioAntes = $fase->data_prevista_inicio?->toDateString();
        $dataPrevistaFimAntes = $fase->data_prevista_fim?->toDateString();

        $tpl = $fase->templateFase;
        $temTemplate = $tpl !== null;

        $overrideElastica = null;
        if ($temTemplate) {
            if ((bool) $this->editRegraElastica !== (bool) $tpl->regra_elastica) {
                $overrideElastica = $this->editRegraElastica;
            }
        }

        $overrides = [
            'regra_duracao_dias' => $this->editRegraElastica ? null : $this->editRegraDuracaoDias,
            'regra_tipo_dias' => $this->editRegraTipoDias ?: null,
            'regra_elastica' => $overrideElastica,
        ];

        if ($temTemplate) {
            $mesmaDuracao = $this->editRegraElastica
                || (int) $this->editRegraDuracaoDias === (int) $tpl->duracao_dias;
            $iguaisAoTemplate =
                $overrideElastica === null
                && (bool) $this->editRegraElastica === (bool) $tpl->regra_elastica
                && $mesmaDuracao
                && ($this->editRegraTipoDias ?: null) === $tpl->tipo_dias?->value;

            if ($iguaisAoTemplate) {
                $overrides = [
                    'regra_duracao_dias' => null,
                    'regra_tipo_dias' => null,
                    'regra_elastica' => null,
                ];
                $regraCustomizada = false;
            } else {
                $regraCustomizada = true;
            }
        } else {
            $regraCustomizada = false;
            $overrides = [
                'regra_duracao_dias' => $this->editRegraElastica ? null : $this->editRegraDuracaoDias,
                'regra_tipo_dias' => $this->editRegraTipoDias ?: null,
                'regra_elastica' => $this->editRegraElastica,
            ];
        }

        // Validações de integridade das dependências.
        foreach ($this->editDependencias as $dep) {
            $alvo = $dep['alvo'] ?? '';
            if ($alvo === 'fase:'.$fase->fase->value) {
                Notification::make()
                    ->title('Dependência inválida')
                    ->body('Uma fase não pode depender de si mesma.')
                    ->danger()->send();

                return;
            }
        }
        $alvos = array_filter(array_column($this->editDependencias, 'alvo'));
        if (count($alvos) !== count(array_unique($alvos))) {
            Notification::make()
                ->title('Dependência duplicada')
                ->body('O mesmo alvo aparece mais de uma vez nas dependências.')
                ->danger()->send();

            return;
        }

        // Detecta se as dependências divergem das herdadas do template.
        $depsAtuais = $this->normalizarDependencias($fase->regraEfetiva()->dependencias->all());
        $depsNovas = $this->normalizarDependencias($this->editDependencias);
        $depsMudaram = $depsAtuais !== $depsNovas;

        $depsIguaisAoTemplate = false;
        if ($temTemplate) {
            $depsTemplate = $this->normalizarDependencias($tpl->dependencias->all());
            $depsIguaisAoTemplate = $depsNovas === $depsTemplate;
        }

        $regraMudou = $temTemplate && (
            (int) ($this->editRegraElastica ? 0 : $this->editRegraDuracaoDias) !== (int) $tpl->duracao_dias
            || ($this->editRegraTipoDias ?: null) !== $tpl->tipo_dias?->value
            || (bool) $this->editRegraElastica !== (bool) $tpl->regra_elastica
            || $regraCustomizada !== (bool) $fase->regra_customizada
            || $depsMudaram
        );

        // ------------------------------------------------------------------
        // Verifica se datas previstas mudaram — se sim, exige motivo.
        // ------------------------------------------------------------------
        $inicioMudouCheck = $dataPrevistaInicioAntes !== ($this->editDataPrevistaInicio ?: null);
        $fimMudouCheck = $dataPrevistaFimAntes !== ($this->editDataPrevistaFim ?: null);

        if (($inicioMudouCheck || $fimMudouCheck) && ! $this->mostrarMotivoDatas) {
            $this->mostrarMotivoDatas = true;

            return;
        }

        if (($inicioMudouCheck || $fimMudouCheck) && trim($this->editMotivoDatas) === '') {
            Notification::make()
                ->title('Motivo obrigatório')
                ->body('Informe o motivo da alteração de datas antes de salvar.')
                ->warning()
                ->send();

            return;
        }

        // ------------------------------------------------------------------
        // Detecta ambiguidade de datas ANTES de persistir:
        // Se só uma das datas foi alterada (e a regra não mudou), precisamos
        // perguntar ao usuário se ele quer preservar a duração (shift limpo)
        // ou alterar as datas exatamente como digitadas (muda a duração).
        // ------------------------------------------------------------------
        if (! $regraMudou && $this->editFaseDuracaoOriginal !== null) {
            $inicioMudou = $dataPrevistaInicioAntes !== ($this->editDataPrevistaInicio ?: null);
            $fimMudou = $dataPrevistaFimAntes !== ($this->editDataPrevistaFim ?: null);
            $apenasUmaDataMudou = ($inicioMudou xor $fimMudou)
                && $this->editDataPrevistaInicio
                && $this->editDataPrevistaFim;

            if ($apenasUmaDataMudou && ! $this->editConfirmacaoShift) {
                // Só ativa o banner se a duração resultante do que foi digitado
                // for de fato diferente da original — senão é um shift de 0 dias e
                // não há o que perguntar.
                $novaDur = Carbon::parse($this->editDataPrevistaInicio)
                    ->diffInDays(Carbon::parse($this->editDataPrevistaFim)) + 1;
                if ($novaDur !== $this->editFaseDuracaoOriginal) {
                    $this->editConfirmacaoShift = true;

                    return;
                }
            }
        }

        $this->persistirFaseEPropagar(
            $fase,
            $overrides,
            $regraCustomizada,
            $regraMudou,
            $depsMudaram,
            $depsIguaisAoTemplate,
            $dataPrevistaInicioAntes,
            $dataPrevistaFimAntes,
        );
    }

    /**
     * Usuário escolheu preservar a duração ao ver o banner de confirmação:
     * ajusta o campo que ficou pendente (inicio ou fim) para que a duração
     * original seja mantida, marca a confirmação e retoma o save.
     */
    public function confirmarSalvarShift(): void
    {
        if ($this->editFaseDuracaoOriginal === null) {
            $this->editConfirmacaoShift = false;

            return;
        }

        // Recarrega a fase para descobrir qual data foi alterada.
        $fase = CronogramaFase::with('templateFase')->find($this->editingFaseId);
        if (! $fase) {
            return;
        }

        $inicioAntes = $fase->data_prevista_inicio?->toDateString();
        $fimAntes = $fase->data_prevista_fim?->toDateString();

        // Se o usuário só mexeu no início, ajusta o fim em função da duração.
        // Se só mexeu no fim, ajusta o início.
        if ($this->editDataPrevistaInicio !== $inicioAntes) {
            $this->editDataPrevistaFim = Carbon::parse($this->editDataPrevistaInicio)
                ->addDays($this->editFaseDuracaoOriginal - 1)
                ->toDateString();
        } elseif ($this->editDataPrevistaFim !== $fimAntes) {
            $this->editDataPrevistaInicio = Carbon::parse($this->editDataPrevistaFim)
                ->subDays($this->editFaseDuracaoOriginal - 1)
                ->toDateString();
        }

        $this->editConfirmacaoShift = false;
        $this->salvarFase();
    }

    /**
     * Usuário escolheu manter as datas exatas no banner de confirmação:
     * a fase vai ter a duração alterada, tratada como "mudou a regra".
     */
    public function confirmarSalvarExato(): void
    {
        $this->editConfirmacaoShift = false;
        // Força que o salvarFase a seguir trate como regra mudando, pulando
        // a detecção de ambiguidade (editFaseDuracaoOriginal = null).
        $this->editFaseDuracaoOriginal = null;
        $this->salvarFase();
    }

    public function cancelarConfirmacaoShift(): void
    {
        $this->editConfirmacaoShift = false;
    }

    /**
     * Persiste a fase (overrides + datas + deps) e dispara o recálculo
     * adequado com base no modo detectado.
     *
     * Modos possíveis:
     * - Rule changed (regraMudou): full hybrid recalc via recalcularFaseEDependentes.
     * - Shift bidirecional (delta uniforme em ambas as datas): shiftComponent.
     * - Duração mudou sem regra (usuário escolheu "exato" no banner): full hybrid.
     * - Nenhuma data mudou: só grava metadados e sai.
     */
    private function persistirFaseEPropagar(
        CronogramaFase $fase,
        array $overrides,
        bool $regraCustomizada,
        bool $regraMudou,
        bool $depsMudaram,
        bool $depsIguaisAoTemplate,
        ?string $dataPrevistaInicioAntes,
        ?string $dataPrevistaFimAntes,
    ): void {
        $fase->update(array_merge($overrides, [
            'data_prevista_inicio' => $this->editDataPrevistaInicio ?: null,
            'data_prevista_fim' => $this->editDataPrevistaFim ?: null,
            'status' => $this->editStatus,
            'percentual_conclusao' => $this->editPercentual,
            'observacoes' => $this->editObservacoes ?: null,
            'regra_customizada' => $regraCustomizada || ($depsMudaram && ! $depsIguaisAoTemplate),
        ]));

        $novoInicio = $this->editDataPrevistaInicio ?: null;
        $novoFim = $this->editDataPrevistaFim ?: null;

        if ($dataPrevistaInicioAntes !== $novoInicio) {
            CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_inicio', $dataPrevistaInicioAntes, $novoInicio, $this->editMotivoDatas ?: null, auth()->id());
        }
        if ($dataPrevistaFimAntes !== $novoFim) {
            CronogramaService::registrarHistoricoDatas($fase, 'data_prevista_fim', $dataPrevistaFimAntes, $novoFim, $this->editMotivoDatas ?: null, auth()->id());
        }

        // Sincroniza as dependências locais da obra:
        if ($depsMudaram) {
            $fase->dependencias()->delete();
            if (! $depsIguaisAoTemplate) {
                foreach ($this->editDependencias as $dep) {
                    $alvo = $dep['alvo'] ?? '';
                    if ($alvo === '') {
                        continue;
                    }
                    [$depFase, $depItemId] = $this->resolverAlvo($alvo);
                    $fase->dependencias()->create([
                        'depende_de_fase' => $depFase,
                        'depende_de_item_id' => $depItemId,
                        'gatilho' => $dep['gatilho'] ?? GatilhoTemplateFase::FIM_ANTERIOR->value,
                        'gap_dias' => (int) ($dep['gap_dias'] ?? 1),
                        'regra_customizada' => true,
                    ]);
                }
            }
        }

        $fase->refresh();
        $service = new CronogramaTemplateService;

        $deltaInicio = $this->calcularDeltaDias($dataPrevistaInicioAntes, $this->editDataPrevistaInicio ?: null);
        $deltaFim = $this->calcularDeltaDias($dataPrevistaFimAntes, $this->editDataPrevistaFim ?: null);

        if ($regraMudou) {
            // Mudança de duração/tipo/deps → recálculo híbrido completo.
            $service->recalcularFaseEDependentes($fase);
            $fase->refresh();
            // Propaga a variação de datas resultante do recálculo para os subitems
            $deltaAposRecalc = $this->calcularDeltaDias($dataPrevistaInicioAntes, $fase->data_prevista_inicio?->toDateString());
            if ($deltaAposRecalc !== null && $deltaAposRecalc !== 0) {
                $this->propagarShiftSubitensDaFase($fase->id, $deltaAposRecalc);
            }
            $this->renderKey++;
        } elseif ($deltaInicio !== null && $deltaFim !== null && $deltaInicio === $deltaFim && $deltaInicio !== 0) {
            // Shift limpo: mesma quantidade de dias em inicio e fim → shiftComponent.
            $this->propagarShiftSubitensDaFase($fase->id, $deltaInicio);
            $service->shiftComponent($fase, $deltaInicio);
            $this->renderKey++;
        } elseif ($deltaInicio !== null && $deltaFim !== null && $deltaInicio !== $deltaFim) {
            // Inicio e fim mudaram com deltas diferentes → duração mudou.
            // Trata como regra mudando e recalcula híbrido.
            $service->recalcularFaseEDependentes($fase);
            $fase->refresh();
            $deltaAposRecalc = $this->calcularDeltaDias($dataPrevistaInicioAntes, $fase->data_prevista_inicio?->toDateString());
            if ($deltaAposRecalc !== null && $deltaAposRecalc !== 0) {
                $this->propagarShiftSubitensDaFase($fase->id, $deltaAposRecalc);
            }
            $this->renderKey++;
        }
        // Caso contrário (datas inalteradas): só gravou metadados, sem recálculo.

        $this->editingFaseId = null;
        Notification::make()->title('Fase atualizada')->success()->send();
    }

    /**
     * Recalcula as datas previstas de um item pai com base nas datas dos filhos:
     * início = mínimo dos inícios dos filhos, fim = máximo dos fins dos filhos.
     * Sobe recursivamente até a raiz e, ao chegar na raiz, atualiza a FASE.
     */
    private function recalcularDatasItemPai(int $parentId): void
    {
        $pai = CronogramaFaseItem::find($parentId);
        if (! $pai) {
            return;
        }

        $filhos = CronogramaFaseItem::where('parent_id', $parentId)
            ->whereNotNull('data_prevista_inicio')
            ->whereNotNull('data_prevista_fim')
            ->get();

        if ($filhos->isEmpty()) {
            return;
        }

        $pai->data_prevista_inicio = $filhos->min('data_prevista_inicio');
        $pai->data_prevista_fim    = $filhos->max('data_prevista_fim');
        $pai->saveQuietly();

        if ($pai->parent_id) {
            $this->recalcularDatasItemPai($pai->parent_id);
        } else {
            // Chegou à raiz: atualiza a fase com o agregado de todos os itens raiz
            $this->recalcularDatasFaseDeItens($pai->cronograma_fase_id);
        }
    }

    /**
     * Atualiza as datas previstas da FASE como min/max dos itens raiz que têm datas.
     * Chamado após alterações nos subitems para manter a fase como resumo.
     */
    private function recalcularDatasFaseDeItens(int $faseId): void
    {
        $itens = CronogramaFaseItem::where('cronograma_fase_id', $faseId)
            ->whereNull('parent_id')
            ->whereNotNull('data_prevista_inicio')
            ->whereNotNull('data_prevista_fim')
            ->get();

        if ($itens->isEmpty()) {
            return;
        }

        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $fase->data_prevista_inicio = $itens->min('data_prevista_inicio');
        $fase->data_prevista_fim    = $itens->max('data_prevista_fim');
        $fase->saveQuietly();
    }

    /**
     * Desloca todas as datas previstas dos subitems de uma fase que NÃO foram
     * definidas manualmente (data_prevista_manual = false). Preserva subitems
     * com datas manuais intactos.
     */
    private function propagarShiftSubitensDaFase(int $faseId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        CronogramaFaseItem::where('cronograma_fase_id', $faseId)
            ->where('data_prevista_manual', false)
            ->whereNotNull('data_prevista_inicio')
            ->whereNotNull('data_prevista_fim')
            ->each(function (CronogramaFaseItem $item) use ($delta) {
                $item->data_prevista_inicio = $item->data_prevista_inicio->copy()->addDays($delta);
                $item->data_prevista_fim = $item->data_prevista_fim->copy()->addDays($delta);
                $item->saveQuietly();
            });
    }

    /**
     * Retorna a diferença em dias corridos entre duas strings de data no
     * formato Y-m-d. Retorna null se qualquer uma das datas for nula.
     */
    private function calcularDeltaDias(?string $antes, ?string $depois): ?int
    {
        if ($antes === null || $depois === null) {
            return null;
        }
        $a = Carbon::parse($antes)->startOfDay();
        $d = Carbon::parse($depois)->startOfDay();

        return (int) round(($d->getTimestamp() - $a->getTimestamp()) / 86400);
    }

    public function resetarRegraFase(): void
    {
        $fase = CronogramaFase::with('templateFase')->find($this->editingFaseId);
        if (! $fase || ! $fase->templateFase) {
            return;
        }

        $fase->update([
            'regra_duracao_dias' => null,
            'regra_tipo_dias' => null,
            'regra_elastica' => null,
            'regra_customizada' => false,
        ]);

        // Remove qualquer override local de dependências — volta a herdar do template.
        $fase->dependencias()->delete();

        $this->abrirEdicaoFase($this->editingFaseId);
        $this->renderKey++;
        Notification::make()->title('Regra restaurada do template')->success()->send();
    }

    /**
     * Normaliza uma lista de dependências (vinda de array ou de Collection de
     * Eloquent) num formato comparável por `===`: array de tuplas ordenadas
     * [alvo, gatilho, gap_dias] onde alvo = 'fase:VALUE' ou 'item:ID'.
     */
    private function normalizarDependencias(iterable $deps): array
    {
        $out = [];
        foreach ($deps as $d) {
            if (is_array($d)) {
                $de = (string) ($d['alvo'] ?? '');
                $gat = (string) ($d['gatilho'] ?? 'fim_anterior');
                $gap = (int) ($d['gap_dias'] ?? 1);
            } else {
                $de = $this->dependenciaParaAlvo($d);
                $gat = $d->gatilho instanceof GatilhoTemplateFase
                    ? $d->gatilho->value
                    : (string) $d->gatilho;
                $gap = (int) $d->gap_dias;
            }
            if ($de === '') {
                continue;
            }
            $out[] = [$de, $gat, $gap];
        }
        sort($out);

        return $out;
    }

    private function dependenciaParaAlvo(object $d): string
    {
        if (! empty($d->depende_de_item_id)) {
            return 'item:'.$d->depende_de_item_id;
        }
        $faseVal = $d->depende_de_fase instanceof FaseCronograma
            ? $d->depende_de_fase->value
            : (string) ($d->depende_de_fase ?? '');

        return $faseVal !== '' ? 'fase:'.$faseVal : '';
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    private function resolverAlvo(string $alvo): array
    {
        if (str_starts_with($alvo, 'item:')) {
            return [null, (int) substr($alvo, 5)];
        }
        $value = str_starts_with($alvo, 'fase:') ? substr($alvo, 5) : $alvo;

        return [$value ?: null, null];
    }

    public function fecharEdicao(): void
    {
        $this->editingFaseId = null;
    }

    /**
     * Alterna a visibilidade de uma fase na obra. Bloqueia se houver dependentes visíveis
     * apontando para esta fase — o usuário precisa ajustar as dependências antes.
     */
    public function toggleVisibilidadeFase(int $faseId): void
    {
        $fase = CronogramaFase::with('templateFase')->find($faseId);
        if (! $fase) {
            return;
        }

        $estadoAtual = $fase->isVisivel();

        if ($estadoAtual) {
            $service = new CronogramaTemplateService;
            $dependentes = $service->dependentesVisiveis($fase->fase, $fase->projeto_id);

            if ($dependentes->isNotEmpty()) {
                $nomes = $dependentes->map(fn (CronogramaFase $d) => $d->fase->label())->join(', ');
                Notification::make()
                    ->title('Não é possível ocultar esta fase')
                    ->body("As fases seguintes dependem dela: {$nomes}. Ajuste as dependências antes de ocultar.")
                    ->warning()
                    ->send();

                return;
            }

            $fase->update(['visivel' => false]);
            Notification::make()->title('Fase ocultada')->success()->send();
        } else {
            $fase->update(['visivel' => true]);
            Notification::make()->title('Fase exibida')->success()->send();
        }

        $this->editingFaseId = null;
        $this->renderKey++;
    }

    public function recalcularTudo(): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $projeto = Projeto::with('cronogramaFases')->find($this->projetoSelecionado);
        if ($projeto) {
            foreach ($projeto->cronogramaFases as $fase) {
                if ($fase->status === StatusCronograma::CONCLUIDO && $fase->percentual_conclusao < 100) {
                    $fase->percentual_conclusao = 100;
                    $fase->saveQuietly();
                }
            }
            Notification::make()->title('Percentuais sincronizados')->success()->send();
        }
    }

    public function irParaPagina(int $pagina): void
    {
        $this->paginaAtual = max(1, $pagina);
    }

    public function updatedFiltroStatus(): void
    {
        $this->paginaAtual = 1;
    }

    public function updatedFiltroStatusObra(): void
    {
        $this->paginaAtual = 1;
    }

    public function updatedFiltroEstado(): void
    {
        $this->paginaAtual = 1;
    }

    public function updatedFiltroRegional(): void
    {
        $this->paginaAtual = 1;
    }

    public function updatedFiltroPeriodo(): void
    {
        $this->paginaAtual = 1;
    }

    public function updatedFiltroTemplate(): void
    {
        $this->paginaAtual = 1;
    }

    public function limparFiltrosMacro(): void
    {
        $this->busca = '';
        $this->filtroEstado = '';
        $this->filtroStatusObra = '';
        $this->filtroStatus = '';
        $this->filtroPeriodo = '';
        $this->filtroTemplate = '';
        $this->paginaAtual = 1;
    }

    private function resolverPeriodo(string $periodo): array
    {
        return match ($periodo) {
            'ultimo_mes' => [now()->subMonth()->startOfDay(), now()->endOfDay()],
            'ultimos_3_meses' => [now()->subMonths(3)->startOfDay(), now()->endOfDay()],
            'ultimos_6_meses' => [now()->subMonths(6)->startOfDay(), now()->endOfDay()],
            'proximo_mes' => [now()->startOfDay(), now()->addMonth()->endOfDay()],
            'proximos_3_meses' => [now()->startOfDay(), now()->addMonths(3)->endOfDay()],
            'proximos_6_meses' => [now()->startOfDay(), now()->addMonths(6)->endOfDay()],
            default => [now()->subYear()->startOfDay(), now()->addYear()->endOfDay()],
        };
    }

    public function updatedBusca(): void
    {
        $this->paginaAtual = 1;
    }

    public function adicionarResponsavelSubitem(int $itemId, int $userId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->responsaveis()->syncWithoutDetaching([$userId]);
        $this->criarTarefaDeSubitem($item, $userId, ehRevisor: false);
        $this->renderKey++;
    }

    public function removerResponsavelSubitem(int $itemId, int $userId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->responsaveis()->detach($userId);
        $this->renderKey++;
    }

    public function salvarDuracaoSubitem(int $itemId, ?int $valor): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->duracao_dias = ($valor !== null && $valor > 0) ? $valor : null;

        // Quando o usuário define duração, calcula a data final sempre (tem precedência sobre qualquer flag).
        // Também reseta a flag manual — ao usar duração em vez de data fixa, o item volta a seguir
        // o planejamento da fase em shifts futuros.
        if ($item->duracao_dias && $item->data_prevista_inicio) {
            $item->data_prevista_fim = $item->data_prevista_inicio->copy()->addDays($item->duracao_dias - 1);
            $item->data_prevista_manual = false;
        }

        $item->save();

        // Atualiza datas do item pai (e ascendentes) como min/max dos filhos,
        // chegando até a fase. Se já é item raiz, atualiza a fase diretamente.
        if ($item->parent_id) {
            $this->recalcularDatasItemPai($item->parent_id);
        } else {
            $this->recalcularDatasFaseDeItens($item->cronograma_fase_id);
        }

        $this->sincronizarDatasTaskDeSubitem($item);
        $this->renderKey++;
    }

    // ─── Valor por subitem → soma na fase ───────────────────────────────────

    private function parseBrFloat(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $s = (string) $raw;
        if (str_contains($s, ',')) {
            $s = str_replace(['.', ' '], '', $s);
            $s = str_replace(',', '.', $s);
        }
        $v = round((float) trim($s), 2);

        return $v > 0 ? $v : null;
    }

    public function salvarValorSubitem(int $itemId, mixed $valorRaw): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->valor = $this->parseBrFloat($valorRaw);
        $item->save();

        $this->recalcularValorFase($item->cronograma_fase_id);
        $this->renderKey++;
    }

    private function recalcularValorFase(int $faseId): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $soma = CronogramaFaseItem::where('cronograma_fase_id', $faseId)
            ->whereNull('parent_id')
            ->whereNotNull('valor')
            ->sum('valor');

        $fase->valor = $soma > 0 ? round($soma, 2) : null;
        $fase->save();
    }

    // ─── Revisor de subitem ───────────────────────────────────────────────────

    public function salvarRevisorSubitem(int $itemId, ?int $userId): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->revisor_id = $userId;
        $item->save();

        if ($userId) {
            $this->criarTarefaDeSubitem($item, $userId, ehRevisor: true);
        }

        $this->renderKey++;
    }

    // ─── Helper: cria tarefa ao atribuir responsável ou revisor ─────────────

    private function criarTarefaDeSubitem(CronogramaFaseItem $item, int $userId, bool $ehRevisor = false): void
    {
        // Não duplicar: se já existe tarefa para este item + usuário + tipo, ignora
        $jaExiste = Task::where('cronograma_fase_item_id', $item->id)
            ->where('assigned_to', $userId)
            ->where('eh_revisor', $ehRevisor)
            ->exists();

        if ($jaExiste) {
            return;
        }

        $categoria = TaskCategory::firstOrCreate(
            ['name' => 'Planejamento BIM'],
            ['name' => 'Planejamento BIM']
        );

        $titulo = $ehRevisor ? '[Revisão] ' . $item->titulo : $item->titulo;

        $inicio = $item->data_prevista_inicio?->toDateString() ?? today()->toDateString();

        $prazo = $item->duracao_dias;
        if (! $prazo && $item->data_prevista_inicio && $item->data_prevista_fim) {
            $prazo = $item->data_prevista_inicio->diffInDays($item->data_prevista_fim) + 1;
        }
        $prazo = $prazo ?: 1;

        try {
            Task::create([
                'title'                   => $titulo,
                'task_category_id'        => $categoria->id,
                'assigned_to'             => $userId,
                'created_by'              => auth()->id() ?? $userId,
                'inicio'                  => $inicio,
                'prazo'                   => $prazo,
                'status'                  => 'pendente',
                'projeto_id'              => $this->projetoSelecionado,
                'cronograma_fase_item_id' => $item->id,
                'eh_revisor'              => $ehRevisor,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('criarTarefaDeSubitem falhou: ' . $e->getMessage());
        }
    }

    private function sincronizarDatasTaskDeSubitem(CronogramaFaseItem $item): void
    {
        $inicio = $item->data_prevista_inicio?->toDateString();
        $prazo  = $item->duracao_dias;

        if (! $prazo && $item->data_prevista_inicio && $item->data_prevista_fim) {
            $prazo = $item->data_prevista_inicio->diffInDays($item->data_prevista_fim) + 1;
        }

        if (! $inicio || ! $prazo) {
            return;
        }

        // Itera individualmente para disparar o hook `updating` do modelo,
        // que recalcula `termino_programado` respeitando `dias_corridos` de cada tarefa.
        Task::where('cronograma_fase_item_id', $item->id)
            ->where(fn ($q) => $q->whereNull('data_entrega')->orWhere('status', '!=', 'concluida'))
            ->get()
            ->each(function (Task $task) use ($inicio, $prazo) {
                $task->inicio = $inicio;
                $task->prazo  = $prazo;
                $task->save();
            });
    }

    public function duplicarProjeto(int $projetoId): void
    {
        $original = Projeto::with([
            'cronogramaFases.itens.children.children',
            'cronogramaFases.itens.responsaveis',
            'cronogramaFases.itens.children.responsaveis',
            'cronogramaFases.itens.children.children.responsaveis',
        ])->find($projetoId);

        if (! $original) {
            return;
        }

        $novoProjeto = $original->replicate(['codigo']);
        $novoProjeto->nome = 'Cópia de ' . $original->nome;
        $novoProjeto->codigo = null;
        $novoProjeto->save();

        foreach ($original->cronogramaFases as $fase) {
            $novaFase = $fase->replicate();
            $novaFase->projeto_id = $novoProjeto->id;
            $novaFase->save();

            $this->duplicarItens($fase->itens->whereNull('parent_id'), $novaFase->id, null);
        }

        Notification::make()
            ->title('Projeto duplicado com sucesso')
            ->body('Cópia de "' . $original->nome . '" criada.')
            ->success()
            ->send();

        $this->renderKey++;
    }

    private function duplicarItens($itens, int $faseId, ?int $parentId): void
    {
        foreach ($itens->sortBy('ordem') as $item) {
            $novoItem = $item->replicate();
            $novoItem->cronograma_fase_id = $faseId;
            $novoItem->parent_id = $parentId;
            $novoItem->save();

            foreach ($item->responsaveis as $resp) {
                $novoItem->responsaveis()->attach($resp->id);
            }

            if ($item->children->isNotEmpty()) {
                $this->duplicarItens($item->children, $faseId, $novoItem->id);
            }
        }
    }

    public function sincronizarTarefasDoProjetoAtual(): void
    {
        if (! $this->projetoSelecionado) {
            return;
        }

        $faseIds = CronogramaFase::where('projeto_id', $this->projetoSelecionado)->pluck('id');

        $itens = CronogramaFaseItem::whereIn('cronograma_fase_id', $faseIds)
            ->with('responsaveis')
            ->get();

        $criadas = 0;

        foreach ($itens as $item) {
            foreach ($item->responsaveis as $responsavel) {
                $jaExiste = Task::where('cronograma_fase_item_id', $item->id)
                    ->where('assigned_to', $responsavel->id)
                    ->where('eh_revisor', false)
                    ->exists();
                if (! $jaExiste) {
                    $this->criarTarefaDeSubitem($item, $responsavel->id, ehRevisor: false);
                    $criadas++;
                }
            }

            if ($item->revisor_id) {
                $jaExiste = Task::where('cronograma_fase_item_id', $item->id)
                    ->where('assigned_to', $item->revisor_id)
                    ->where('eh_revisor', true)
                    ->exists();
                if (! $jaExiste) {
                    $this->criarTarefaDeSubitem($item, $item->revisor_id, ehRevisor: true);
                    $criadas++;
                }
            }

            // Sincroniza datas das tarefas já existentes (e recém-criadas)
            $this->sincronizarDatasTaskDeSubitem($item);
        }

        // Recalcular datas dos itens pai (bottom-up): processa os itens que são pais
        // de outros itens, recursão interna garante propagação até a fase.
        $parentIds = CronogramaFaseItem::whereIn('cronograma_fase_id', $faseIds)
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id')
            ->unique();

        foreach ($parentIds as $parentId) {
            $this->recalcularDatasItemPai($parentId);
        }

        foreach ($faseIds as $faseId) {
            $this->recalcularDatasFaseDeItens($faseId);
        }

        Notification::make()
            ->title($criadas > 0 ? "{$criadas} tarefa(s) criada(s) e datas sincronizadas" : 'Datas e hierarquia sincronizadas')
            ->success()
            ->send();

        $this->renderKey++;
    }

    // ─── Ações em lote sobre subitens ────────────────────────────────────────

    public function excluirSubitemsEmLote(array $ids): void
    {
        CronogramaFaseItem::whereIn('id', $ids)->get()->each->delete();
        $this->renderKey++;
    }

    public function marcarConcluidsEmLote(array $ids): void
    {
        CronogramaFaseItem::whereIn('id', $ids)->update(['recebido' => true]);
        $this->renderKey++;
    }

    public function alterarDatasSubitemsEmLote(array $ids, ?string $inicio, ?string $fim): void
    {
        $dados = [];
        if ($inicio) {
            $dados['data_prevista_inicio'] = $inicio;
        }
        if ($fim) {
            $dados['data_prevista_fim'] = $fim;
        }
        if (! empty($dados)) {
            CronogramaFaseItem::whereIn('id', $ids)->update($dados);
            $this->renderKey++;
        }
    }

    public function atribuirResponsavelSubitemsEmLote(array $ids, int $userId): void
    {
        foreach ($ids as $itemId) {
            $item = CronogramaFaseItem::find($itemId);
            if (! $item) {
                continue;
            }
            if (! $item->responsaveis->contains('id', $userId)) {
                $item->responsaveis()->attach($userId);
            }
            $this->criarTarefaDeSubitem($item, $userId, ehRevisor: false);
        }
        $this->renderKey++;
    }

    public function atribuirRevisorSubitemsEmLote(array $ids, int $userId): void
    {
        foreach ($ids as $itemId) {
            $item = CronogramaFaseItem::find($itemId);
            if (! $item) {
                continue;
            }
            $item->revisor_id = $userId;
            $item->save();
            $this->criarTarefaDeSubitem($item, $userId, ehRevisor: true);
        }
        $this->renderKey++;
    }

    public function atribuirDependenciaSubitemsEmLote(array $ids, string $alvo, string $gatilho, int $gap): void
    {
        [$tipo, $alvoIdStr] = array_pad(explode(':', $alvo, 2), 2, null);
        $alvoId = (int) $alvoIdStr;

        if (! in_array($tipo, ['fase', 'item'], true) || $alvoId <= 0) {
            return;
        }

        $gatilhoEnum = GatilhoTemplateFase::tryFrom($gatilho);
        if (! $gatilhoEnum) {
            return;
        }

        $itens = CronogramaFaseItem::whereIn('id', $ids)->get();

        foreach ($itens as $item) {
            if ($tipo === 'item' && $alvoId === $item->id) {
                continue;
            }
            if ($tipo === 'fase' && $alvoId === $item->cronograma_fase_id) {
                continue;
            }

            $dep = $item->dependencias()->create([
                'gatilho'  => $gatilhoEnum->value,
                'gap_dias' => $gap,
            ]);

            if ($tipo === 'fase') {
                $dep->update(['depende_de_fase_id' => $alvoId, 'depende_de_item_id' => null]);
            } else {
                $dep->update(['depende_de_fase_id' => null, 'depende_de_item_id' => $alvoId]);
            }

            $this->recalcularDatasSubitemPorDependencias($item);
        }

        $this->renderKey++;
    }

    public function editarSubitemsEmLote(
        array $ids,
        ?string $inicio,
        ?string $fim,
        ?int $duracao,
        ?int $responsavelId,
        ?int $revisorId,
        ?string $depAlvo,
        string $depGatilho,
        int $depGap
    ): void {
        // Datas e duração
        if ($inicio || $fim || ($duracao > 0)) {
            if ($duracao > 0) {
                // Duração definida: salva duracao_dias e recalcula data_fim em cada item
                $itensParaDuracao = CronogramaFaseItem::whereIn('id', $ids)->get();
                foreach ($itensParaDuracao as $item) {
                    $item->duracao_dias = $duracao;
                    if ($inicio) {
                        $item->data_prevista_inicio = $inicio;
                    }
                    $dataBase = $item->data_prevista_inicio;
                    if ($dataBase) {
                        $item->data_prevista_fim = $dataBase->copy()->addDays($duracao - 1);
                        $item->data_prevista_manual = false;
                    }
                    $item->save();
                }
            } else {
                $dados = [];
                if ($inicio) {
                    $dados['data_prevista_inicio'] = $inicio;
                }
                if ($fim) {
                    $dados['data_prevista_fim'] = $fim;
                }
                CronogramaFaseItem::whereIn('id', $ids)->update($dados);
            }
        }

        if ($responsavelId) {
            foreach ($ids as $itemId) {
                $item = CronogramaFaseItem::find($itemId);
                if (! $item) {
                    continue;
                }
                if (! $item->responsaveis->contains('id', $responsavelId)) {
                    $item->responsaveis()->attach($responsavelId);
                }
                $this->criarTarefaDeSubitem($item, $responsavelId, ehRevisor: false);
            }
        }

        if ($revisorId) {
            foreach ($ids as $itemId) {
                $item = CronogramaFaseItem::find($itemId);
                if (! $item) {
                    continue;
                }
                $item->revisor_id = $revisorId;
                $item->save();
                $this->criarTarefaDeSubitem($item, $revisorId, ehRevisor: true);
            }
        }

        if ($depAlvo) {
            [$tipo, $alvoIdStr] = array_pad(explode(':', $depAlvo, 2), 2, null);
            $alvoId = (int) $alvoIdStr;
            $gatilhoEnum = GatilhoTemplateFase::tryFrom($depGatilho);

            if (in_array($tipo, ['fase', 'item'], true) && $alvoId > 0 && $gatilhoEnum) {
                $itens = CronogramaFaseItem::whereIn('id', $ids)->get();
                foreach ($itens as $item) {
                    if ($tipo === 'item' && $alvoId === $item->id) {
                        continue;
                    }
                    if ($tipo === 'fase' && $alvoId === $item->cronograma_fase_id) {
                        continue;
                    }
                    $dep = $item->dependencias()->create([
                        'gatilho'  => $gatilhoEnum->value,
                        'gap_dias' => $depGap,
                    ]);
                    if ($tipo === 'fase') {
                        $dep->update(['depende_de_fase_id' => $alvoId, 'depende_de_item_id' => null]);
                    } else {
                        $dep->update(['depende_de_fase_id' => null, 'depende_de_item_id' => $alvoId]);
                    }
                    $this->recalcularDatasSubitemPorDependencias($item);
                }
            }
        }

        // Sincronizar datas das tasks vinculadas para todos os itens editados
        foreach ($ids as $itemId) {
            $item = CronogramaFaseItem::find($itemId);
            if ($item) {
                $this->sincronizarDatasTaskDeSubitem($item);
            }
        }

        $this->renderKey++;
    }

    // ─── Drag-drop de fases (reordenar) ──────────────────────────────────────

    public function moverFase(int $faseId, int $novaOrdem): void
    {
        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $projetoId = $fase->projeto_id;
        $oldOrdem  = (int) $fase->ordem;

        if ($oldOrdem === $novaOrdem) {
            return;
        }

        if ($novaOrdem > $oldOrdem) {
            // Movendo para baixo: itens intermediários sobem
            CronogramaFase::where('projeto_id', $projetoId)
                ->where('id', '!=', $faseId)
                ->whereBetween('ordem', [$oldOrdem + 1, $novaOrdem])
                ->decrement('ordem');
        } else {
            // Movendo para cima: itens intermediários descem
            CronogramaFase::where('projeto_id', $projetoId)
                ->where('id', '!=', $faseId)
                ->whereBetween('ordem', [$novaOrdem, $oldOrdem - 1])
                ->increment('ordem');
        }

        $fase->ordem = $novaOrdem;
        $fase->save();

        $this->renderKey++;
    }

    // ─── Drag-drop de subitens (reordenar / mover entre fases) ───────────────

    public function moverSubitem(int $itemId, int $faseOrigemId, int $faseDestinoId, int $novaOrdem): void
    {
        $item = CronogramaFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $mesmaFase = ($faseOrigemId === $faseDestinoId);
        $oldOrdem  = $item->ordem;

        if ($mesmaFase) {
            // Reordenação dentro da mesma fase
            if ($novaOrdem === $oldOrdem) {
                return;
            }
            if ($novaOrdem > $oldOrdem) {
                CronogramaFaseItem::where('cronograma_fase_id', $faseOrigemId)
                    ->whereNull('parent_id')
                    ->where('id', '!=', $itemId)
                    ->whereBetween('ordem', [$oldOrdem + 1, $novaOrdem])
                    ->decrement('ordem');
            } else {
                CronogramaFaseItem::where('cronograma_fase_id', $faseOrigemId)
                    ->whereNull('parent_id')
                    ->where('id', '!=', $itemId)
                    ->whereBetween('ordem', [$novaOrdem, $oldOrdem - 1])
                    ->increment('ordem');
            }
        } else {
            // Mover para outra fase: fechar gap na fase de origem
            CronogramaFaseItem::where('cronograma_fase_id', $faseOrigemId)
                ->whereNull('parent_id')
                ->where('id', '!=', $itemId)
                ->where('ordem', '>', $oldOrdem)
                ->decrement('ordem');

            if ($novaOrdem === 9999) {
                // Append ao final da fase destino
                $novaOrdem = (CronogramaFaseItem::where('cronograma_fase_id', $faseDestinoId)
                    ->whereNull('parent_id')
                    ->max('ordem') ?? -1) + 1;
            } else {
                // Inserir antes do item alvo: abrir espaço
                CronogramaFaseItem::where('cronograma_fase_id', $faseDestinoId)
                    ->whereNull('parent_id')
                    ->where('id', '!=', $itemId)
                    ->where('ordem', '>=', $novaOrdem)
                    ->increment('ordem');
            }

            $item->cronograma_fase_id = $faseDestinoId;
            $item->parent_id          = null;
        }

        $item->ordem = $novaOrdem;
        $item->save();

        $this->renderKey++;
    }

    // ─── Grupos de atividades ─────────────────────────────────────────────────

    public function criarGrupoAtividades(string $nome, array $ids): void
    {
        $nome = trim($nome);
        if (! $nome || empty($ids)) {
            return;
        }

        $grupo = GrupoAtividades::create([
            'nome'      => $nome,
            'criado_por' => auth()->id(),
        ]);

        $itens = CronogramaFaseItem::whereIn('id', $ids)
            ->whereNull('parent_id')
            ->orderBy('ordem')
            ->get();

        foreach ($itens as $ordem => $item) {
            $grupoItem = GrupoAtividadesItem::create([
                'grupo_id'    => $grupo->id,
                'titulo'      => $item->titulo,
                'descricao'   => $item->descricao,
                'ordem'       => $ordem,
                'duracao_dias' => $item->duracao_dias,
            ]);

            foreach ($item->children->sortBy('ordem') as $childOrdem => $filho) {
                GrupoAtividadesItem::create([
                    'grupo_id'    => $grupo->id,
                    'parent_id'   => $grupoItem->id,
                    'titulo'      => $filho->titulo,
                    'descricao'   => $filho->descricao,
                    'ordem'       => $childOrdem,
                    'duracao_dias' => $filho->duracao_dias,
                ]);
            }
        }

        Notification::make()->title("Grupo \"{$nome}\" criado com " . $itens->count() . ' atividade(s)')->success()->send();
    }

    public function abrirSelecionarGrupo(int $faseId): void
    {
        $this->faseAlvoGrupo = $faseId;
        $this->gruposDisponiveis = GrupoAtividades::with('itensRaiz.children')
            ->orderBy('nome')
            ->get()
            ->map(fn ($g) => [
                'id'          => $g->id,
                'nome'        => $g->nome,
                'descricao'   => $g->descricao,
                'total_itens' => $g->todosItens()->count(),
                'itens'       => $g->itensRaiz->map(fn ($i) => [
                    'titulo' => $i->titulo,
                    'filhos' => $i->children->pluck('titulo')->toArray(),
                ])->toArray(),
            ])
            ->toArray();
        $this->modalSelecionarGrupo = true;
    }

    public function fecharSelecionarGrupo(): void
    {
        $this->modalSelecionarGrupo = false;
        $this->faseAlvoGrupo        = null;
        $this->gruposDisponiveis    = [];
    }

    public function inserirGrupoNaFase(int $grupoId): void
    {
        if (! $this->faseAlvoGrupo) {
            return;
        }

        $grupo = GrupoAtividades::with('itensRaiz.children')->find($grupoId);
        if (! $grupo) {
            return;
        }

        $maxOrdem = CronogramaFaseItem::where('cronograma_fase_id', $this->faseAlvoGrupo)
            ->whereNull('parent_id')
            ->max('ordem') ?? -1;

        foreach ($grupo->itensRaiz as $i => $gItem) {
            $item = CronogramaFaseItem::create([
                'cronograma_fase_id' => $this->faseAlvoGrupo,
                'titulo'             => $gItem->titulo,
                'descricao'          => $gItem->descricao,
                'ordem'              => $maxOrdem + 1 + $i,
                'duracao_dias'       => $gItem->duracao_dias,
                'origem'             => 'grupo',
            ]);

            foreach ($gItem->children->sortBy('ordem') as $j => $gFilho) {
                CronogramaFaseItem::create([
                    'cronograma_fase_id' => $this->faseAlvoGrupo,
                    'parent_id'          => $item->id,
                    'titulo'             => $gFilho->titulo,
                    'descricao'          => $gFilho->descricao,
                    'ordem'              => $j,
                    'duracao_dias'       => $gFilho->duracao_dias,
                    'origem'             => 'grupo',
                ]);
            }
        }

        // Garante que a fase fique expandida
        if (! in_array($this->faseAlvoGrupo, $this->fasesExpandidas, true)) {
            $this->fasesExpandidas[] = $this->faseAlvoGrupo;
        }

        $this->fecharSelecionarGrupo();
        $this->renderKey++;
    }

    public function excluirGrupoAtividades(int $grupoId): void
    {
        GrupoAtividades::find($grupoId)?->delete();
        $this->gruposDisponiveis = array_values(
            array_filter($this->gruposDisponiveis, fn ($g) => $g['id'] !== $grupoId)
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:Cronograma');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('View:Cronograma');
    }
}
