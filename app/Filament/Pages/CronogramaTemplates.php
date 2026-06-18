<?php

namespace App\Filament\Pages;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\ModoAncoraCronograma;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use App\Models\CronogramaTemplateFaseItem;
use App\Models\CronogramaTemplateFaseItemDependencia;
use App\Services\CronogramaTemplateService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

class CronogramaTemplates extends Page
{
    use WithFileUploads;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected string $view = 'filament.pages.cronograma-templates';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Templates de Planejamento';

    protected static ?string $title = 'Templates de Planejamento';

    public function getHeading(): string
    {
        return '';
    }

    public function mount(): void
    {
        // Se a URL traz ?tpl=ID, restaura o template selecionado (suporta F5 sem perder contexto).
        if ($this->templateSelecionadoId) {
            $idQuery = $this->templateSelecionadoId;
            $this->templateSelecionadoId = null;
            $this->selecionarTemplate($idQuery);
        }
    }

    #[Url(as: 'tpl', except: null, history: true)]
    public ?int $templateSelecionadoId = null;

    // Form state: template
    public string $tplNome = '';
    public string $tplTipoObra = '';
    public string $tplAncoraCampo = '';
    public string $tplModoAncora = 'posse';
    public ?int $tplPareadoId = null;
    public bool $tplAtivo = true;
    public string $tplObservacoes = '';

    /**
     * Drawer "Editar fases": estado de edição em buffer + Gantt simulado em tempo real.
     * As edições inline atualizam a simulação imediatamente, mas só persistem no DB
     * ao clicar em "Salvar". Banner "Alterações não salvas" sinaliza dirty state.
     */
    public bool $mostrarEditorFases = false;

    /**
     * Snapshot de edições não-persistidas, indexado por template_fase_id (int) ou
     * por chave temporária "novo:{uniqid}" (string) para fases adicionadas no buffer.
     *
     * Estrutura por fase:
     *  ['fase' => string, 'duracao' => int, 'tipo_dias' => string, 'visivel' => bool,
     *   'is_ancora' => bool, 'regra_elastica' => bool, 'observacoes' => string,
     *   'ordem' => int, 'deps' => [['alvo' => 'fase:X|item:Y', 'gatilho' => str, 'gap' => int], ...]]
     *
     * @var array<int|string, array<string,mixed>>
     */
    public array $bufferTemplate = [];

    /**
     * IDs de fases marcadas para deleção ao salvar (apenas fases que existem no DB).
     *
     * @var array<int, int>
     */
    public array $bufferFasesRemovidas = [];

    /**
     * Resultado da última simulação rodada com o buffer aplicado:
     *  [fase_value => ['inicio' => 'YYYY-MM-DD', 'fim' => 'YYYY-MM-DD']]
     *
     * @var array<string, array{inicio: string, fim: string}>
     */
    public array $bufferSimulacao = [];

    public ?string $bufferErroSimulacao = null;

    public string $bufferSimulacaoAncora = '';

    public bool $bufferDirty = false;

    /**
     * Buffers transitórios para entrada de subitens no drawer (por fase).
     *
     * @var array<int|string, string>
     */
    public array $novoSubitemTitulos = [];

    public string $novoFilhoTitulo = '';

    public ?int $expandindoFilhosDeItemId = null;

    /** Seletor de "adicionar nova fase" do catálogo (enum). */
    public string $bufferNovaFaseEnum = '';

    /** Input de título de fase personalizada (nome livre). */
    public string $bufferNovaFasePersonalizadaTitulo = '';

    public bool $mostrarConfirmacaoDescarte = false;

    /**
     * Modal de conflito ao ocultar/remover fase com dependentes.
     * @var array<int, array{chave: int|string, fase_nome: string, dep_idx: int, substituir_por: string, gatilho: string, gap_dias: int}>
     */
    public array $fasesConflitantesBuffer = [];

    public bool $mostrarModalConflitoDepBuffer = false;

    /** Chave (id ou "novo:...") da fase em vias de ser ocultada/removida. */
    public int|string|null $faseConflitoChave = null;

    /** Valor enum (string) da fase em vias de ser ocultada/removida. */
    public string $faseConflitoEnum = '';

    /** 'ocultar' ou 'remover'. */
    public string $acaoConflitoFase = '';

    public bool $mostrarImportacao = false;

    public bool $mostrarArquivados = false;

    public $arquivoImportacao = null;

    public function getViewData(): array
    {
        if ($this->templateSelecionadoId) {
            return $this->getViewDataIndividual();
        }

        return $this->getViewDataMacro();
    }

    private function getViewDataMacro(): array
    {
        $query = CronogramaTemplate::withCount('fases')
            ->with(['fases.dependencias', 'pareado:id,nome,modo_ancora'])
            ->orderBy('tipo_obra')
            ->orderBy('nome');

        if ($this->mostrarArquivados) {
            $query->withTrashed();
        }

        $templates = $query->get();

        $duracoes = $templates->mapWithKeys(fn (CronogramaTemplate $t) => [
            $t->id => $this->calcularDuracaoTotal($t),
        ]);

        return [
            'modoIndividual' => false,
            'templates' => $templates,
            'duracoes' => $duracoes,
        ];
    }

    private function getViewDataIndividual(): array
    {
        $template = CronogramaTemplate::with([
            'fases.dependencias.dependeDeItem',
            'fases.itens.parent',
            'fases.itens.dependencias.dependeDeTemplateFase',
            'fases.itens.dependencias.dependeDeItem',
        ])->find($this->templateSelecionadoId);

        if (! $template) {
            $this->templateSelecionadoId = null;

            return $this->getViewDataMacro();
        }

        // Ordena pela coluna `ordem` (drag-drop) com fallback no enum.
        $fasesOrdenadas = $template->fases
            ->sortBy([
                ['ordem', 'asc'],
                fn (CronogramaTemplateFase $f) => $f->fase->ordem(),
            ])
            ->values();

        $duracaoTotalDias = $this->calcularDuracaoTotal($template);

        // Fases no enum que ainda NÃO estão no template (para o seletor "adicionar fase" no drawer).
        $usadas = $fasesOrdenadas->pluck('fase')->map->value->all();
        $fasesAdicionaveis = collect(FaseCronograma::cases())
            ->reject(fn (FaseCronograma $c) => in_array($c->value, $usadas, true))
            ->values()
            ->all();

        // Candidatos a pareamento: mesmo tipo_obra, modo de âncora oposto e ainda sem par.
        $modoOpostoTpl = $template->modo_ancora === ModoAncoraCronograma::POSSE
            ? ModoAncoraCronograma::OBRAS
            : ModoAncoraCronograma::POSSE;

        $candidatosPareamento = CronogramaTemplate::where('id', '!=', $template->id)
            ->when($template->tipo_obra, fn ($q) => $q->where('tipo_obra', $template->tipo_obra->value))
            ->where('modo_ancora', $modoOpostoTpl->value)
            ->whereNull('template_pareado_id')
            ->orderBy('nome')
            ->get(['id', 'nome', 'modo_ancora']);

        return [
            'modoIndividual' => true,
            'template' => $template,
            'fases' => $fasesOrdenadas,
            'duracaoTotalDias' => $duracaoTotalDias,
            'fasesDisponiveis' => FaseCronograma::cases(),
            'fasesAdicionaveis' => $fasesAdicionaveis,
            'tipoObraOptions' => TipoObraCronograma::cases(),
            'tipoDiasOptions' => TipoDiasTemplate::cases(),
            'gatilhoOptions' => GatilhoTemplateFase::cases(),
            'ancoraOptions' => $this->ancoraOptions(),
            'candidatosPareamento' => $candidatosPareamento,
        ];
    }

    /**
     * Calcula a duração total do template em dias, usando o serviço de cálculo híbrido
     * com uma data âncora fictícia. Retorna 0 se o template não tiver fase âncora ou
     * se ocorrer qualquer erro no cálculo.
     */
    private function calcularDuracaoTotal(CronogramaTemplate $template): int
    {
        try {
            $datas = (new CronogramaTemplateService)->simular($template);

            if (empty($datas)) {
                return 0;
            }

            $inicios = array_map(fn ($d) => $d['inicio'], $datas);
            $fins = array_map(fn ($d) => $d['fim'], $datas);

            $inicio = min($inicios);
            $fim = max($fins);

            // Span em dias offset (mesma convenção da planilha PMO: end - start
            // sem o "+1 inclusive"). Reflete a duração real do cronograma.
            return (int) $inicio->diffInDays($fim);
        } catch (\Throwable $e) {
            return 0;
        }
    }


    private function ancoraOptions(): array
    {
        return [
            'projeto.data_ass_contrato' => 'Projeto → Data Assinatura Contrato',
            'projeto.inauguracao' => 'Projeto → Inauguração',
            'projeto.data_posse' => 'Projeto → Data Posse',
            'inicio' => 'Obra → Início',
            'fim' => 'Obra → Fim',
        ];
    }

    public function selecionarTemplate(int $id): void
    {
        $this->templateSelecionadoId = $id;
        $this->limparBuffers();

        $tpl = CronogramaTemplate::find($id);
        if ($tpl) {
            $this->tplNome = $tpl->nome;
            $this->tplTipoObra = $tpl->tipo_obra?->value ?? '';
            $this->tplAncoraCampo = $tpl->ancora_campo ?? '';
            $this->tplModoAncora = $tpl->modo_ancora?->value ?? 'posse';
            $this->tplPareadoId = $tpl->template_pareado_id;
            $this->tplAtivo = (bool) $tpl->ativo;
            $this->tplObservacoes = $tpl->observacoes ?? '';

            // Drawer abre automaticamente + simulação inicial.
            $this->abrirEditorFases();
        }
    }

    public function voltarParaMacro(): void
    {
        $this->templateSelecionadoId = null;
        $this->mostrarEditorFases = false;
        $this->limparBuffers();
    }

    private function limparBuffers(): void
    {
        $this->bufferTemplate = [];
        $this->bufferFasesRemovidas = [];
        $this->bufferSimulacao = [];
        $this->bufferErroSimulacao = null;
        $this->bufferDirty = false;
        $this->novoSubitemTitulos = [];
        $this->novoFilhoTitulo = '';
        $this->expandindoFilhosDeItemId = null;
        $this->bufferNovaFaseEnum = '';
        $this->mostrarConfirmacaoDescarte = false;
        $this->fasesConflitantesBuffer = [];
        $this->mostrarModalConflitoDepBuffer = false;
        $this->faseConflitoChave = null;
        $this->faseConflitoEnum = '';
        $this->acaoConflitoFase = '';
    }

    public function salvarTemplate(): void
    {
        $tpl = CronogramaTemplate::find($this->templateSelecionadoId);
        if (! $tpl) {
            return;
        }

        $tpl->update([
            'nome' => $this->tplNome,
            'tipo_obra' => $this->tplTipoObra ?: null,
            'ancora_campo' => $this->tplAncoraCampo ?: null,
            'modo_ancora' => $this->tplModoAncora ?: null,
            'ativo' => $this->tplAtivo,
            'observacoes' => $this->tplObservacoes ?: null,
        ]);

        Notification::make()->title('Template atualizado')->success()->send();
    }

    /**
     * Cria um template "irmão" como variante do par (modo oposto ao atual).
     * Copia fases/dependências/itens do template atual como ponto de partida
     * e amarra os dois via `template_pareado_id`. Depois leva o usuário para
     * editar a nova variante.
     */
    public function criarVariantePareada(): void
    {
        $atual = CronogramaTemplate::with('fases.dependencias', 'fases.itens.dependencias')
            ->find($this->templateSelecionadoId);

        if (! $atual) {
            return;
        }

        if ($atual->temPar()) {
            Notification::make()->title('Este template já tem variante pareada')->warning()->send();

            return;
        }

        $modoAtual = $atual->modo_ancora ?? ModoAncoraCronograma::POSSE;
        $modoNovo = $modoAtual === ModoAncoraCronograma::POSSE
            ? ModoAncoraCronograma::OBRAS
            : ModoAncoraCronograma::POSSE;

        $ancoraNova = $modoNovo === ModoAncoraCronograma::OBRAS
            ? 'projeto.data_inicio_obra'
            : 'projeto.data_posse';

        $variante = $atual->replicate(['template_pareado_id']);
        $variante->nome = $atual->nome . ' — ' . strtoupper($modoNovo->value);
        $variante->modo_ancora = $modoNovo->value;
        $variante->ancora_campo = $ancoraNova;
        $variante->template_pareado_id = $atual->id;
        $variante->save();

        // Replica fases + deps + itens.
        $mapaFases = [];
        foreach ($atual->fases as $fase) {
            $faseNova = $fase->replicate();
            $faseNova->cronograma_template_id = $variante->id;
            // Se a fase original era âncora, mantém apenas se for a âncora correta do novo modo.
            // Caso contrário, deixa o usuário ajustar via editor.
            $faseNova->save();
            $mapaFases[$fase->id] = $faseNova;

            foreach ($fase->dependencias as $dep) {
                $depNova = $dep->replicate();
                $depNova->cronograma_template_fase_id = $faseNova->id;
                $depNova->save();
            }
        }

        // Replica itens com mapeamento de fase_id e parent_id.
        foreach ($atual->fases as $fase) {
            $faseNova = $mapaFases[$fase->id];
            $mapaItens = [];
            foreach ($fase->itens as $item) {
                $itemNovo = $item->replicate();
                $itemNovo->cronograma_template_fase_id = $faseNova->id;
                $itemNovo->parent_id = $item->parent_id ? ($mapaItens[$item->parent_id] ?? null) : null;
                $itemNovo->save();
                $mapaItens[$item->id] = $itemNovo->id;
            }
        }

        // Linka o original ao novo.
        $atual->update(['template_pareado_id' => $variante->id]);

        Notification::make()
            ->title('Variante criada')
            ->body('Variante ' . strtoupper($modoNovo->value) . ' criada a partir do template atual. Ajuste as fases conforme necessário.')
            ->success()
            ->send();

        $this->selecionarTemplate($variante->id);
    }

    /**
     * Pareia o template atual com outro template existente (manualmente).
     * Os dois templates devem ter modos de âncora opostos e o outro não pode
     * já estar pareado com algum terceiro.
     */
    public function parearComTemplate(int $outroId): void
    {
        $atual = CronogramaTemplate::find($this->templateSelecionadoId);
        $outro = CronogramaTemplate::find($outroId);

        if (! $atual || ! $outro) {
            return;
        }

        if ($atual->id === $outro->id) {
            Notification::make()->title('Não é possível parear um template consigo mesmo')->danger()->send();

            return;
        }

        if ($atual->modo_ancora === $outro->modo_ancora) {
            Notification::make()
                ->title('Modos de âncora idênticos')
                ->body('O template pareado precisa ter modo oposto (POSSE ↔ OBRAS).')
                ->danger()
                ->send();

            return;
        }

        if ($outro->template_pareado_id && $outro->template_pareado_id !== $atual->id) {
            Notification::make()
                ->title('Template já tem outro par')
                ->body('Desfaça o pareamento atual de "' . $outro->nome . '" antes.')
                ->danger()
                ->send();

            return;
        }

        // Limpa par antigo, se houver.
        if ($atual->template_pareado_id && $atual->template_pareado_id !== $outro->id) {
            CronogramaTemplate::where('id', $atual->template_pareado_id)
                ->update(['template_pareado_id' => null]);
        }

        $atual->update(['template_pareado_id' => $outro->id]);
        $outro->update(['template_pareado_id' => $atual->id]);

        Notification::make()
            ->title('Templates pareados')
            ->body('Agora você pode alternar entre as variantes POSSE/OBRAS no editor.')
            ->success()
            ->send();

        $this->selecionarTemplate($atual->id);
    }

    public function desfazerPareamento(): void
    {
        $atual = CronogramaTemplate::find($this->templateSelecionadoId);
        if (! $atual || ! $atual->template_pareado_id) {
            return;
        }

        $parId = $atual->template_pareado_id;
        $atual->update(['template_pareado_id' => null]);
        CronogramaTemplate::where('id', $parId)->update(['template_pareado_id' => null]);

        Notification::make()->title('Pareamento desfeito')->success()->send();
        $this->selecionarTemplate($atual->id);
    }

    /**
     * Alterna para o template pareado (POSSE ↔ OBRAS).
     * Se já estamos no modo solicitado, não faz nada.
     */
    public function irParaVariante(string $modo): void
    {
        $atual = CronogramaTemplate::find($this->templateSelecionadoId);
        if (! $atual) {
            return;
        }

        $modoEnum = ModoAncoraCronograma::tryFrom($modo);
        if (! $modoEnum || $atual->modo_ancora === $modoEnum) {
            return;
        }

        $variante = $atual->variantePara($modoEnum);
        if (! $variante) {
            Notification::make()
                ->title('Variante não existe ainda')
                ->body('Crie a variante ' . strtoupper($modo) . ' antes de alternar.')
                ->warning()
                ->send();

            return;
        }

        $this->selecionarTemplate($variante->id);
    }

    public function novoTemplate(): void
    {
        $tpl = CronogramaTemplate::create([
            'nome' => 'Novo template',
            'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
            'ancora_campo' => 'projeto.data_posse',
            'modo_ancora' => ModoAncoraCronograma::POSSE->value,
            'ativo' => true,
        ]);

        $this->selecionarTemplate($tpl->id);
    }

    public function duplicarTemplate(): void
    {
        $tpl = CronogramaTemplate::with('fases.dependencias')->find($this->templateSelecionadoId);
        if (! $tpl) {
            return;
        }

        $novo = $tpl->replicate();
        $novo->nome = $tpl->nome.' (cópia)';
        $novo->save();

        foreach ($tpl->fases as $fase) {
            $faseNova = $fase->replicate();
            $faseNova->cronograma_template_id = $novo->id;
            $faseNova->save();

            foreach ($fase->dependencias as $dep) {
                $depNova = $dep->replicate();
                $depNova->cronograma_template_fase_id = $faseNova->id;
                $depNova->save();
            }
        }

        $this->selecionarTemplate($novo->id);
        Notification::make()->title('Template duplicado')->success()->send();
    }

    /**
     * Exporta o template selecionado como arquivo JSON para download.
     * O formato e auto-contido: nao depende de IDs internos, apenas dos
     * values de enum (fase, tipo_obra, etc.) e pode ser re-importado em
     * qualquer ambiente.
     */
    public function exportarTemplate(): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tpl = CronogramaTemplate::with('fases.dependencias')->find($this->templateSelecionadoId);
        if (! $tpl) {
            return null;
        }

        $payload = [
            'versao' => 1,
            'tipo' => 'cronograma_template',
            'exportado_em' => now()->toIso8601String(),
            'template' => [
                'nome' => $tpl->nome,
                'tipo_obra' => $tpl->tipo_obra?->value,
                'ancora_campo' => $tpl->ancora_campo,
                'ativo' => (bool) $tpl->ativo,
                'observacoes' => $tpl->observacoes,
                'fases' => $tpl->fases
                    ->sortBy(fn ($f) => $f->fase->ordem())
                    ->values()
                    ->map(fn (CronogramaTemplateFase $f) => [
                        'fase' => $f->fase->value,
                        'duracao_dias' => (int) $f->duracao_dias,
                        'tipo_dias' => $f->tipo_dias->value,
                        'visivel' => (bool) $f->visivel,
                        'is_ancora' => (bool) $f->is_ancora,
                        'regra_elastica' => (bool) $f->regra_elastica,
                        'observacoes' => $f->observacoes,
                        'dependencias' => $f->dependencias->map(fn ($d) => [
                            'depende_de_fase' => $d->depende_de_fase instanceof FaseCronograma
                                ? $d->depende_de_fase->value
                                : (string) $d->depende_de_fase,
                            'gatilho' => $d->gatilho instanceof GatilhoTemplateFase
                                ? $d->gatilho->value
                                : (string) $d->gatilho,
                            'gap_dias' => (int) $d->gap_dias,
                        ])->values()->all(),
                    ])
                    ->all(),
            ],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $slug = \Illuminate\Support\Str::slug($tpl->nome);
        $filename = 'template-cronograma-'.$slug.'-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function abrirImportacao(): void
    {
        $this->mostrarImportacao = true;
        $this->arquivoImportacao = null;
    }

    public function fecharImportacao(): void
    {
        $this->mostrarImportacao = false;
        $this->arquivoImportacao = null;
    }

    /**
     * Importa um template a partir do JSON enviado no upload.
     * Cria um novo template (com nome sufixado se ja existir um com o mesmo nome),
     * e recria todas as fases + dependencias.
     */
    public function importarTemplate(): void
    {
        if (! $this->arquivoImportacao) {
            Notification::make()->title('Selecione um arquivo')->warning()->send();

            return;
        }

        try {
            $conteudo = file_get_contents($this->arquivoImportacao->getRealPath());
            $payload = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Arquivo inválido')
                ->body('Não foi possível ler o JSON: '.$e->getMessage())
                ->danger()->send();

            return;
        }

        if (($payload['tipo'] ?? null) !== 'cronograma_template' || ! isset($payload['template'])) {
            Notification::make()
                ->title('Formato não reconhecido')
                ->body('O arquivo não é um template de cronograma válido.')
                ->danger()->send();

            return;
        }

        $data = $payload['template'];

        // Validação mínima dos enums
        if (! TipoObraCronograma::tryFrom($data['tipo_obra'] ?? '')) {
            Notification::make()
                ->title('Tipo de obra inválido')
                ->body("O tipo '{$data['tipo_obra']}' não existe.")
                ->danger()->send();

            return;
        }

        foreach ($data['fases'] ?? [] as $f) {
            if (! FaseCronograma::tryFrom($f['fase'] ?? '')) {
                Notification::make()
                    ->title('Fase inválida')
                    ->body("A fase '{$f['fase']}' não é reconhecida pelo sistema.")
                    ->danger()->send();

                return;
            }
        }

        // Gera um nome único caso já exista um template com o mesmo nome.
        $nomeOriginal = (string) ($data['nome'] ?? 'Template importado');
        $nomeFinal = $nomeOriginal;
        $sufixo = 1;
        while (CronogramaTemplate::where('nome', $nomeFinal)->exists()) {
            $sufixo++;
            $nomeFinal = $nomeOriginal." (importado {$sufixo})";
        }

        try {
            \DB::transaction(function () use ($data, $nomeFinal) {
                $tpl = CronogramaTemplate::create([
                    'nome' => $nomeFinal,
                    'tipo_obra' => $data['tipo_obra'],
                    'ancora_campo' => $data['ancora_campo'] ?? 'projeto.data_posse',
                    'ativo' => (bool) ($data['ativo'] ?? true),
                    'observacoes' => $data['observacoes'] ?? null,
                ]);

                $fasesCriadas = [];
                foreach ($data['fases'] ?? [] as $f) {
                    $fase = CronogramaTemplateFase::create([
                        'cronograma_template_id' => $tpl->id,
                        'fase' => $f['fase'],
                        'ordem' => FaseCronograma::from($f['fase'])->ordem(),
                        'duracao_dias' => max(0, (int) ($f['duracao_dias'] ?? 0)),
                        'tipo_dias' => $f['tipo_dias'] ?? TipoDiasTemplate::CORRIDOS->value,
                        'visivel' => (bool) ($f['visivel'] ?? true),
                        'is_ancora' => (bool) ($f['is_ancora'] ?? false),
                        'regra_elastica' => (bool) ($f['regra_elastica'] ?? false),
                        'observacoes' => $f['observacoes'] ?? null,
                    ]);
                    $fasesCriadas[$f['fase']] = $fase;
                }

                // Cria dependências num segundo passe, quando todas as fases já existem.
                foreach ($data['fases'] ?? [] as $f) {
                    $fase = $fasesCriadas[$f['fase']] ?? null;
                    if (! $fase) {
                        continue;
                    }
                    foreach ($f['dependencias'] ?? [] as $dep) {
                        $depValue = $dep['depende_de_fase'] ?? null;
                        if (! $depValue || ! isset($fasesCriadas[$depValue])) {
                            continue;
                        }
                        CronogramaTemplateFaseDependencia::create([
                            'cronograma_template_fase_id' => $fase->id,
                            'depende_de_fase' => $depValue,
                            'gatilho' => $dep['gatilho'] ?? GatilhoTemplateFase::FIM_ANTERIOR->value,
                            'gap_dias' => (int) ($dep['gap_dias'] ?? 1),
                        ]);
                    }
                }

                $this->templateSelecionadoId = $tpl->id;
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao importar')
                ->body($e->getMessage())
                ->danger()->send();

            return;
        }

        $this->fecharImportacao();
        if ($this->templateSelecionadoId) {
            $this->selecionarTemplate($this->templateSelecionadoId);
        }
        Notification::make()
            ->title('Template importado com sucesso')
            ->body("Criado como '{$nomeFinal}'.")
            ->success()->send();
    }

    public function excluirTemplate(): void
    {
        $tpl = CronogramaTemplate::find($this->templateSelecionadoId);
        if (! $tpl) {
            return;
        }

        $tpl->delete();
        $this->voltarParaMacro();
        Notification::make()->title('Template arquivado')->success()->send();
    }

    public function duplicarTemplatePorId(int $id): void
    {
        $tpl = CronogramaTemplate::with([
            'fases.dependencias',
            'fases.itens.responsaveis',
        ])->find($id);
        if (! $tpl) {
            return;
        }

        $novo = $tpl->replicate();
        $novo->nome = $tpl->nome.' (cópia)';
        $novo->save();

        $faseIdMap = [];
        $itemIdMap = [];

        foreach ($tpl->fases as $fase) {
            $faseNova = $fase->replicate();
            $faseNova->cronograma_template_id = $novo->id;
            $faseNova->save();
            $faseIdMap[$fase->id] = $faseNova->id;

            $itensRaiz = $fase->itens->whereNull('parent_id');
            $this->copiarItensTemplateParaTemplate($itensRaiz, $faseNova->id, null, $fase->itens, $itemIdMap);
        }

        foreach ($tpl->fases as $fase) {
            foreach ($fase->dependencias as $dep) {
                $depNova = $dep->replicate();
                $depNova->cronograma_template_fase_id = $faseIdMap[$fase->id];
                if (isset($faseIdMap[$dep->depende_de_template_fase_id])) {
                    $depNova->depende_de_template_fase_id = $faseIdMap[$dep->depende_de_template_fase_id];
                }
                $depNova->save();
            }
        }

        foreach ($itemIdMap as $origId => $novoId) {
            $origItem = CronogramaTemplateFaseItem::find($origId);
            if ($origItem?->depende_de_item_id && isset($itemIdMap[$origItem->depende_de_item_id])) {
                CronogramaTemplateFaseItem::where('id', $novoId)
                    ->update(['depende_de_item_id' => $itemIdMap[$origItem->depende_de_item_id]]);
            }
        }

        Notification::make()->title('Template duplicado')->success()->send();
    }

    public function excluirTemplatePorId(int $id): void
    {
        $tpl = CronogramaTemplate::find($id);
        if (! $tpl) {
            return;
        }
        $tpl->delete();
        Notification::make()->title('Template arquivado')->success()->send();
    }

    public function restaurarTemplatePorId(int $id): void
    {
        $tpl = CronogramaTemplate::withTrashed()->find($id);
        if (! $tpl) {
            return;
        }
        $tpl->restore();
        Notification::make()->title('Template restaurado')->success()->send();
    }

    public function restaurarTemplate(): void
    {
        $tpl = CronogramaTemplate::withTrashed()->find($this->templateSelecionadoId);
        if (! $tpl) {
            return;
        }
        $tpl->restore();
        Notification::make()->title('Template restaurado')->success()->send();
    }

    private function copiarItensTemplateParaTemplate($itens, int $faseId, ?int $parentId, $todosItens, array &$itemIdMap): void
    {
        foreach ($itens->sortBy('ordem') as $item) {
            $novoItem = $item->replicate();
            $novoItem->cronograma_template_fase_id = $faseId;
            $novoItem->parent_id = $parentId;
            $novoItem->depende_de_item_id = null;
            $novoItem->save();

            $itemIdMap[$item->id] = $novoItem->id;

            if ($item->responsaveis->isNotEmpty()) {
                $novoItem->responsaveis()->sync($item->responsaveis->pluck('id'));
            }

            $filhos = $todosItens->where('parent_id', $item->id);
            if ($filhos->isNotEmpty()) {
                $this->copiarItensTemplateParaTemplate($filhos, $faseId, $novoItem->id, $todosItens, $itemIdMap);
            }
        }
    }

    // =====================================================================
    // Drawer "Editar fases": buffer + simulação reativa
    // =====================================================================

    /**
     * Abre o drawer e popula o bufferTemplate a partir do template carregado.
     * Define data-âncora padrão (hoje + 90d) e roda simulação inicial.
     */
    public function abrirEditorFases(): void
    {
        if (! $this->templateSelecionadoId) {
            return;
        }

        $template = CronogramaTemplate::with('fases.dependencias.dependeDeItem.templateFase')
            ->find($this->templateSelecionadoId);

        if (! $template) {
            return;
        }

        if ($this->bufferSimulacaoAncora === '') {
            $this->bufferSimulacaoAncora = \Carbon\CarbonImmutable::today()->addDays(90)->toDateString();
        }

        $this->bufferTemplate = [];
        $this->bufferFasesRemovidas = [];
        $this->bufferDirty = false;

        foreach ($template->fases as $fase) {
            $this->bufferTemplate[$fase->id] = $this->snapshotFase($fase);
        }

        $this->mostrarEditorFases = true;
        $this->simularComBuffer();
    }

    /**
     * Snapshot de uma fase do DB no formato do buffer.
     */
    private function snapshotFase(CronogramaTemplateFase $fase): array
    {
        $deps = $fase->dependencias->map(function ($d) {
            if ($d->depende_de_item_id) {
                $alvo = 'item:'.$d->depende_de_item_id;
            } else {
                $value = $d->depende_de_fase instanceof FaseCronograma
                    ? $d->depende_de_fase->value
                    : (string) $d->depende_de_fase;
                $alvo = $value ? 'fase:'.$value : '';
            }

            return [
                'alvo' => $alvo,
                'gatilho' => $d->gatilho instanceof GatilhoTemplateFase
                    ? $d->gatilho->value
                    : (string) $d->gatilho,
                'gap' => (int) $d->gap_dias,
            ];
        })->values()->all();

        return [
            'fase' => $fase->fase->value,
            'titulo_personalizado' => (string) ($fase->titulo_personalizado ?? ''),
            'duracao' => (int) $fase->duracao_dias,
            'tipo_dias' => $fase->tipo_dias instanceof TipoDiasTemplate
                ? $fase->tipo_dias->value
                : (string) $fase->tipo_dias,
            'visivel' => (bool) $fase->visivel,
            'is_ancora' => (bool) $fase->is_ancora,
            'regra_elastica' => (bool) $fase->regra_elastica,
            'observacoes' => (string) ($fase->observacoes ?? ''),
            'ordem' => (int) ($fase->ordem ?? $fase->fase->ordem()),
            'deps' => $deps,
        ];
    }

    /**
     * Fecha o drawer. Se houver edições pendentes, abre modal de confirmação
     * antes de descartar (a menos que $confirmado=true).
     */
    public function fecharEditorFases(bool $confirmado = false): void
    {
        if ($this->bufferDirty && ! $confirmado) {
            $this->mostrarConfirmacaoDescarte = true;

            return;
        }

        $this->mostrarEditorFases = false;
        $this->limparBuffers();
    }

    public function descartarBuffer(): void
    {
        $this->mostrarConfirmacaoDescarte = false;
        // Re-popula a partir do DB, mantém drawer aberto.
        $this->abrirEditorFases();
        Notification::make()->title('Alterações descartadas')->success()->send();
    }

    public function cancelarDescarteBuffer(): void
    {
        $this->mostrarConfirmacaoDescarte = false;
    }

    /**
     * Persiste todo o buffer no banco em transação. Roda validação de ciclo via
     * CronogramaTemplateService::simular() antes de comitar.
     */
    public function salvarBuffer(): void
    {
        if (! $this->templateSelecionadoId || empty($this->bufferTemplate)) {
            return;
        }

        // Validação: cada bloco de deps não pode ter alvos duplicados nem self-ref.
        foreach ($this->bufferTemplate as $faseId => $dados) {
            $alvos = array_filter(array_column($dados['deps'] ?? [], 'alvo'));
            if (count($alvos) !== count(array_unique($alvos))) {
                Notification::make()
                    ->title('Dependência duplicada')
                    ->body('Há alvos repetidos na fase '.($dados['fase'] ?? '').'.')
                    ->danger()->send();

                return;
            }
            $selfAlvo = 'fase:'.($dados['fase'] ?? '');
            foreach ($alvos as $a) {
                if ($a === $selfAlvo) {
                    Notification::make()
                        ->title('Dependência inválida')
                        ->body('Uma fase não pode depender de si mesma ('.$dados['fase'].').')
                        ->danger()->send();

                    return;
                }
            }
        }

        // Garante mutex de is_ancora: apenas uma fase do template é âncora.
        $ancorasNoBuffer = array_filter($this->bufferTemplate, fn ($d) => ! empty($d['is_ancora']));
        if (count($ancorasNoBuffer) > 1) {
            Notification::make()
                ->title('Mais de uma fase âncora')
                ->body('Apenas uma fase do template pode ser âncora. Marque apenas uma.')
                ->danger()->send();

            return;
        }

        try {
            \DB::transaction(function () {
                // Deleta fases removidas (e dependências em cascata pelo banco).
                if (! empty($this->bufferFasesRemovidas)) {
                    CronogramaTemplateFase::whereIn('id', $this->bufferFasesRemovidas)->delete();
                }

                $idMap = []; // chaves "novo:{uniqid}" → ids reais após insert

                foreach ($this->bufferTemplate as $chave => $dados) {
                    $titulo = trim((string) ($dados['titulo_personalizado'] ?? ''));
                    $atributos = [
                        'titulo_personalizado' => $titulo !== '' ? $titulo : null,
                        'duracao_dias' => $dados['regra_elastica'] ? 0 : max(0, (int) $dados['duracao']),
                        'tipo_dias' => $dados['tipo_dias'],
                        'visivel' => (bool) $dados['visivel'],
                        'is_ancora' => (bool) $dados['is_ancora'],
                        'regra_elastica' => (bool) $dados['regra_elastica'],
                        'observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                        'ordem' => (int) $dados['ordem'],
                    ];

                    if (is_string($chave) && str_starts_with($chave, 'novo:')) {
                        // INSERT: nova fase adicionada no buffer
                        $faseModel = CronogramaTemplateFase::create($atributos + [
                            'cronograma_template_id' => $this->templateSelecionadoId,
                            'fase' => $dados['fase'],
                        ]);
                        $idMap[$chave] = $faseModel->id;
                    } else {
                        CronogramaTemplateFase::where('id', $chave)->update($atributos);
                    }
                }

                // Sincroniza dependências (delete all + recreate por fase).
                foreach ($this->bufferTemplate as $chave => $dados) {
                    $faseId = $idMap[$chave] ?? (int) $chave;
                    CronogramaTemplateFaseDependencia::where('cronograma_template_fase_id', $faseId)->delete();

                    foreach ($dados['deps'] ?? [] as $dep) {
                        $alvo = $dep['alvo'] ?? '';
                        if ($alvo === '') {
                            continue;
                        }
                        [$tipo, $valor] = array_pad(explode(':', $alvo, 2), 2, '');
                        CronogramaTemplateFaseDependencia::create([
                            'cronograma_template_fase_id' => $faseId,
                            'depende_de_fase' => $tipo === 'fase' ? $valor : null,
                            'depende_de_item_id' => $tipo === 'item' ? (int) $valor : null,
                            'gatilho' => $dep['gatilho'] ?? GatilhoTemplateFase::FIM_ANTERIOR->value,
                            'gap_dias' => (int) ($dep['gap'] ?? 0),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao salvar')
                ->body($e->getMessage())
                ->danger()->send();

            return;
        }

        // Validação pós-save: rodar simulação real para detectar ciclos.
        try {
            $tpl = CronogramaTemplate::with('fases.dependencias')->find($this->templateSelecionadoId);
            (new CronogramaTemplateService)->simular($tpl);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Grafo de dependências inválido após salvar')
                ->body($e->getMessage())
                ->danger()->send();
        }

        // Re-abre o drawer com snapshot atualizado.
        $this->abrirEditorFases();
        Notification::make()->title('Template salvo')->success()->send();
    }

    /**
     * Recalcula a simulação a partir do buffer (não toca DB). Atualizado a cada edição.
     */
    private function simularComBuffer(): void
    {
        $this->bufferErroSimulacao = null;
        $this->bufferSimulacao = [];

        if (empty($this->bufferTemplate)) {
            return;
        }

        $duracoes = [];
        $tipoDias = [];
        $deps = [];
        $elasticas = [];
        $ancoraValue = null;

        foreach ($this->bufferTemplate as $dados) {
            $value = $dados['fase'] ?? null;
            if (! $value) {
                continue;
            }

            $duracoes[$value] = $dados['regra_elastica'] ? 0 : max(0, (int) $dados['duracao']);
            $tipoDias[$value] = TipoDiasTemplate::tryFrom((string) $dados['tipo_dias']) ?? TipoDiasTemplate::CORRIDOS;
            $elasticas[$value] = (bool) $dados['regra_elastica'];

            if (! empty($dados['is_ancora'])) {
                $ancoraValue = $value;
            }

            $deps[$value] = [];
            foreach ($dados['deps'] ?? [] as $d) {
                $alvo = $d['alvo'] ?? '';
                if ($alvo === '' || ! str_starts_with($alvo, 'fase:')) {
                    continue;
                }
                $depValue = substr($alvo, 5);
                $deps[$value][] = (object) [
                    'de' => $depValue,
                    'gatilho' => GatilhoTemplateFase::tryFrom((string) ($d['gatilho'] ?? 'fim_anterior'))
                        ?? GatilhoTemplateFase::FIM_ANTERIOR,
                    'gap' => (int) ($d['gap'] ?? 0),
                ];
            }
        }

        if (! $ancoraValue) {
            $this->bufferErroSimulacao = 'Nenhuma fase âncora definida.';

            return;
        }

        try {
            $datas = (new CronogramaTemplateService)->calcularDatasFromMaps(
                $ancoraValue,
                \Carbon\CarbonImmutable::parse($this->bufferSimulacaoAncora ?: \Carbon\CarbonImmutable::today()->addDays(90)->toDateString()),
                $duracoes,
                $tipoDias,
                $deps,
                null,
                $elasticas,
            );

            foreach ($datas as $value => $d) {
                $this->bufferSimulacao[$value] = [
                    'inicio' => $d['inicio']->toDateString(),
                    'fim' => $d['fim']->toDateString(),
                ];
            }
        } catch (\Throwable $e) {
            $this->bufferErroSimulacao = $e->getMessage();
        }
    }

    // =====================================================================
    // Handlers de edição inline (escrevem no buffer + flag dirty + re-simulam)
    // =====================================================================

    public function atualizarBufferDuracao(int|string $faseId, int|string|null $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        $this->bufferTemplate[$faseId]['duracao'] = max(0, (int) ($valor ?? 0));
        $this->marcarDirtyERessimular();
    }

    public function atualizarBufferTipoDias(int|string $faseId, string $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        if (! in_array($valor, ['corridos', 'uteis'], true)) return;
        $this->bufferTemplate[$faseId]['tipo_dias'] = $valor;
        $this->marcarDirtyERessimular();
    }

    public function atualizarBufferElastica(int|string $faseId, bool $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        $this->bufferTemplate[$faseId]['regra_elastica'] = $valor;
        if ($valor) {
            $this->bufferTemplate[$faseId]['duracao'] = 0;
        }
        $this->marcarDirtyERessimular();
    }

    public function atualizarBufferVisivel(int|string $faseId, bool $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;

        // Marcar como visível: aplica direto, sem checagem.
        if ($valor) {
            $this->bufferTemplate[$faseId]['visivel'] = true;
            $this->marcarDirtyERessimular();

            return;
        }

        // Marcar como oculta: se outras fases dependem dela, abre modal pra
        // o usuário reconfigurar/remover essas dependências antes (mesmo
        // padrão do "marcar não se aplica" da Page Cronograma da obra).
        if ($this->verificarDepsBuffer($faseId, 'ocultar')) {
            return; // modal aberto; aguarda confirmação
        }

        $this->bufferTemplate[$faseId]['visivel'] = false;
        $this->marcarDirtyERessimular();
    }

    public function atualizarBufferAncora(int|string $faseId, bool $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;

        // Mutex: apenas uma fase pode ser âncora por template.
        if ($valor) {
            foreach ($this->bufferTemplate as $k => $_) {
                $this->bufferTemplate[$k]['is_ancora'] = ($k === $faseId || $k == $faseId);
            }
        } else {
            $this->bufferTemplate[$faseId]['is_ancora'] = false;
        }
        $this->marcarDirtyERessimular();
    }

    public function atualizarBufferTituloPersonalizado(int|string $faseId, string $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        $this->bufferTemplate[$faseId]['titulo_personalizado'] = trim($valor);
        $this->bufferDirty = true;
    }

    public function atualizarBufferObservacoes(int|string $faseId, string $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        $this->bufferTemplate[$faseId]['observacoes'] = $valor;
        $this->bufferDirty = true;
    }

    /**
     * Recebe lista de IDs de fase na nova ordem (do drag-drop) e atualiza buffer.
     *
     * @param array<int,int|string> $idsNaOrdem
     */
    public function atualizarBufferOrdem(array $idsNaOrdem): void
    {
        $ordem = 0;
        foreach ($idsNaOrdem as $id) {
            if (isset($this->bufferTemplate[$id])) {
                $this->bufferTemplate[$id]['ordem'] = $ordem++;
            }
        }
        $this->marcarDirtyERessimular();
    }

    public function bufferAdicionarDep(int|string $faseId): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;
        $this->bufferTemplate[$faseId]['deps'][] = [
            'alvo' => '',
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap' => 0,
        ];
        $this->bufferDirty = true;
    }

    public function bufferRemoverDep(int|string $faseId, int $idx): void
    {
        if (! isset($this->bufferTemplate[$faseId]['deps'][$idx])) return;
        unset($this->bufferTemplate[$faseId]['deps'][$idx]);
        $this->bufferTemplate[$faseId]['deps'] = array_values($this->bufferTemplate[$faseId]['deps']);
        $this->marcarDirtyERessimular();
    }

    public function bufferAtualizarDep(int|string $faseId, int $idx, string $campo, $valor): void
    {
        if (! isset($this->bufferTemplate[$faseId]['deps'][$idx])) return;
        if (! in_array($campo, ['alvo', 'gatilho', 'gap'], true)) return;
        $this->bufferTemplate[$faseId]['deps'][$idx][$campo] = $campo === 'gap' ? (int) $valor : (string) $valor;
        $this->marcarDirtyERessimular();
    }

    /**
     * Adiciona uma fase nova ao buffer (não persiste ainda; chave temporária "novo:{uniqid}").
     */
    public function bufferAdicionarFase(): void
    {
        $valorEnum = $this->bufferNovaFaseEnum;
        if (! $valorEnum || ! FaseCronograma::tryFrom($valorEnum)) {
            return;
        }
        // Evita duplicar fase já existente.
        foreach ($this->bufferTemplate as $d) {
            if (($d['fase'] ?? null) === $valorEnum) {
                Notification::make()->title('Fase já existe no template')->warning()->send();
                $this->bufferNovaFaseEnum = '';

                return;
            }
        }

        $this->inserirFaseNoBuffer($valorEnum, '');
        $this->bufferNovaFaseEnum = '';
    }

    /**
     * Adiciona uma fase personalizada (nome livre) ao buffer.
     * Reusa o case PERSONALIZADA do enum + grava o nome em titulo_personalizado.
     * Limite atual: uma fase personalizada por template (constraint da tabela).
     */
    public function bufferAdicionarFasePersonalizada(): void
    {
        $titulo = trim($this->bufferNovaFasePersonalizadaTitulo);
        if ($titulo === '') {
            Notification::make()->title('Informe um título para a fase personalizada')->warning()->send();

            return;
        }

        // Limite: enum tem case único PERSONALIZADA, e a tabela tem unique
        // (template_id, fase). Logo só cabe uma fase personalizada por template.
        foreach ($this->bufferTemplate as $d) {
            if (($d['fase'] ?? null) === FaseCronograma::PERSONALIZADA->value) {
                Notification::make()
                    ->title('Já existe uma fase personalizada')
                    ->body('O template aceita apenas uma fase personalizada (limite atual).')
                    ->warning()->send();

                return;
            }
        }

        $this->inserirFaseNoBuffer(FaseCronograma::PERSONALIZADA->value, $titulo);
        $this->bufferNovaFasePersonalizadaTitulo = '';
    }

    private function inserirFaseNoBuffer(string $faseValue, string $tituloPersonalizado): void
    {
        $chave = 'novo:'.uniqid();
        $maxOrdem = collect($this->bufferTemplate)->max('ordem') ?? -1;

        $this->bufferTemplate[$chave] = [
            'fase' => $faseValue,
            'titulo_personalizado' => $tituloPersonalizado,
            'duracao' => 0,
            'tipo_dias' => TipoDiasTemplate::CORRIDOS->value,
            'visivel' => true,
            'is_ancora' => false,
            'regra_elastica' => false,
            'observacoes' => '',
            'ordem' => $maxOrdem + 1,
            'deps' => [],
        ];

        $this->marcarDirtyERessimular();
    }

    /**
     * Remove uma fase do buffer. Se for fase persistida (id int), marca para deleção.
     * Se for fase nova (chave "novo:..."), apenas remove do buffer.
     *
     * Se outras fases dependem desta, abre modal pra reconfigurar/remover
     * essas dependências antes (padrão da Page Cronograma da obra).
     */
    public function bufferRemoverFase(int|string $faseId): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;

        if ($this->verificarDepsBuffer($faseId, 'remover')) {
            return; // modal aberto; aguarda confirmação
        }

        $this->executarAcaoConflitoFase($faseId, 'remover');
    }

    /**
     * Verifica se há outras fases no buffer dependendo de $faseId.
     * Se sim, popula $fasesConflitantesBuffer + abre modal e retorna true.
     * Se não, retorna false (chamador segue com a ação direto).
     */
    private function verificarDepsBuffer(int|string $faseId, string $acao): bool
    {
        $faseEnum = $this->bufferTemplate[$faseId]['fase'] ?? '';
        if ($faseEnum === '') return false;

        $alvoSelf = 'fase:'.$faseEnum;
        $conflitantes = [];

        foreach ($this->bufferTemplate as $chave => $bufFase) {
            if ($chave === $faseId || $chave == $faseId) continue;

            foreach ($bufFase['deps'] ?? [] as $idx => $dep) {
                if (($dep['alvo'] ?? '') !== $alvoSelf) continue;

                $faseEnumDep = FaseCronograma::tryFrom((string) ($bufFase['fase'] ?? ''));
                $conflitantes[] = [
                    'chave'           => $chave,
                    'fase_nome'       => $faseEnumDep?->label() ?? (string) $bufFase['fase'],
                    'dep_idx'         => $idx,
                    'substituir_por'  => '',
                    'gatilho'         => $dep['gatilho'] ?? GatilhoTemplateFase::FIM_ANTERIOR->value,
                    'gap_dias'        => (int) ($dep['gap'] ?? 0),
                ];
                break; // uma referência por fase é suficiente pra mostrar no modal
            }
        }

        if (empty($conflitantes)) {
            return false;
        }

        $this->faseConflitoChave = $faseId;
        $this->faseConflitoEnum = $faseEnum;
        $this->acaoConflitoFase = $acao;
        $this->fasesConflitantesBuffer = $conflitantes;
        $this->mostrarModalConflitoDepBuffer = true;

        return true;
    }

    /**
     * Aplica a ação no buffer (oculta ou remove a fase) sem checagem de conflito.
     */
    private function executarAcaoConflitoFase(int|string $faseId, string $acao): void
    {
        if (! isset($this->bufferTemplate[$faseId])) return;

        if ($acao === 'remover') {
            if (is_int($faseId) || ctype_digit((string) $faseId)) {
                $this->bufferFasesRemovidas[] = (int) $faseId;
            }
            unset($this->bufferTemplate[$faseId]);
        } else {
            $this->bufferTemplate[$faseId]['visivel'] = false;
        }

        $this->marcarDirtyERessimular();
    }

    /**
     * O usuário confirmou as substituições no modal de conflito.
     * Aplica cada decisão (substituir alvo / remover dep) e executa a
     * ação pendente (ocultar ou remover) na fase original.
     */
    public function confirmarOcultarReconfigurarDeps(): void
    {
        if ($this->faseConflitoChave === null) return;

        foreach ($this->fasesConflitantesBuffer as $conf) {
            $chave = $conf['chave'];
            $idx = $conf['dep_idx'];
            $novaDep = $conf['substituir_por'] ?? '';

            if (! isset($this->bufferTemplate[$chave]['deps'][$idx])) continue;

            if ($novaDep === '') {
                // Remove a dependência completamente.
                unset($this->bufferTemplate[$chave]['deps'][$idx]);
                $this->bufferTemplate[$chave]['deps'] = array_values(
                    $this->bufferTemplate[$chave]['deps']
                );
            } else {
                $this->bufferTemplate[$chave]['deps'][$idx] = [
                    'alvo' => 'fase:'.$novaDep,
                    'gatilho' => $conf['gatilho'] ?? GatilhoTemplateFase::FIM_ANTERIOR->value,
                    'gap' => (int) ($conf['gap_dias'] ?? 0),
                ];
            }
        }

        $this->executarAcaoConflitoFase($this->faseConflitoChave, $this->acaoConflitoFase);

        $this->mostrarModalConflitoDepBuffer = false;
        $this->faseConflitoChave = null;
        $this->faseConflitoEnum = '';
        $this->acaoConflitoFase = '';
        $this->fasesConflitantesBuffer = [];
    }

    public function cancelarOcultarReconfigurarDeps(): void
    {
        $this->mostrarModalConflitoDepBuffer = false;
        $this->faseConflitoChave = null;
        $this->faseConflitoEnum = '';
        $this->acaoConflitoFase = '';
        $this->fasesConflitantesBuffer = [];
    }

    public function updatedBufferSimulacaoAncora(): void
    {
        $this->simularComBuffer();
    }

    private function marcarDirtyERessimular(): void
    {
        $this->bufferDirty = true;
        $this->simularComBuffer();
    }

    /**
     * Adiciona subitem à fase $faseId. Título vem de $novoSubitemTitulos[$faseId]
     * (igual ao padrão da Page Cronograma da obra).
     *
     * Subitens persistem direto no DB (não vão pro buffer) — consistente com a obra.
     */
    public function adicionarTemplateFaseItem(int $faseId): void
    {
        $titulo = trim((string) ($this->novoSubitemTitulos[$faseId] ?? ''));
        if ($titulo === '') {
            Notification::make()->title('Informe um título para o subitem')->warning()->send();

            return;
        }

        $fase = CronogramaTemplateFase::find($faseId);
        if (! $fase || $fase->cronograma_template_id !== $this->templateSelecionadoId) {
            return;
        }

        $ordem = ((int) (CronogramaTemplateFaseItem::where('cronograma_template_fase_id', $faseId)
            ->max('ordem') ?? -1)) + 1;

        CronogramaTemplateFaseItem::create([
            'cronograma_template_fase_id' => $faseId,
            'titulo' => $titulo,
            'ordem' => $ordem,
        ]);

        unset($this->novoSubitemTitulos[$faseId]);
    }

    /**
     * Adiciona sub-subitem (filho) sob $parentId. Título vem de $novoFilhoTitulo
     * (igual ao padrão da Page Cronograma da obra).
     */
    public function adicionarSubitemTemplateFaseItem(int $parentId): void
    {
        $titulo = trim($this->novoFilhoTitulo);
        if ($titulo === '') {
            Notification::make()->title('Informe um título para o sub-item')->warning()->send();

            return;
        }

        $parent = CronogramaTemplateFaseItem::find($parentId);
        if (! $parent) {
            return;
        }

        $ordem = ((int) (CronogramaTemplateFaseItem::where('parent_id', $parentId)->max('ordem') ?? -1)) + 1;

        CronogramaTemplateFaseItem::create([
            'cronograma_template_fase_id' => $parent->cronograma_template_fase_id,
            'parent_id' => $parentId,
            'titulo' => $titulo,
            'ordem' => $ordem,
        ]);

        $this->novoFilhoTitulo = '';
        $this->expandindoFilhosDeItemId = null;
    }

    public function alternarAdicionarFilho(int $itemId): void
    {
        $this->expandindoFilhosDeItemId = $this->expandindoFilhosDeItemId === $itemId ? null : $itemId;
        $this->novoFilhoTitulo = '';
    }

    public function removerTemplateFaseItem(int $itemId): void
    {
        $item = CronogramaTemplateFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        // Garante que o item pertence ao template selecionado.
        $fase = CronogramaTemplateFase::find($item->cronograma_template_fase_id);
        if (! $fase || $fase->cronograma_template_id !== $this->templateSelecionadoId) {
            return;
        }

        CronogramaTemplateFaseItem::where('parent_id', $itemId)->delete();
        $item->delete();
    }

    public function salvarTituloTemplateFaseItem(int $itemId, string $titulo): void
    {
        $titulo = trim($titulo);
        if ($titulo === '') {
            return;
        }

        $item = CronogramaTemplateFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $fase = CronogramaTemplateFase::find($item->cronograma_template_fase_id);
        if (! $fase || $fase->cronograma_template_id !== $this->templateSelecionadoId) {
            return;
        }

        $item->update(['titulo' => $titulo]);
    }

    public function adicionarDependenciaTemplateFaseItem(int $itemId): void
    {
        $item = CronogramaTemplateFaseItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->dependencias()->create([
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 1,
        ]);
    }

    public function removerDependenciaTemplateFaseItem(int $dependenciaId): void
    {
        CronogramaTemplateFaseItemDependencia::whereKey($dependenciaId)->delete();
    }

    public function salvarAlvoDependenciaTemplateFaseItem(int $dependenciaId, ?string $alvo): void
    {
        $dependencia = CronogramaTemplateFaseItemDependencia::with('item.templateFase')->find($dependenciaId);
        if (! $dependencia || ! $dependencia->item) {
            return;
        }

        $alvo = blank($alvo) ? null : (string) $alvo;
        if ($alvo === null) {
            $dependencia->update([
                'depende_de_template_fase_id' => null,
                'depende_de_item_id' => null,
            ]);

            return;
        }

        [$tipo, $alvoId] = array_pad(explode(':', $alvo, 2), 2, null);
        $alvoId = (int) $alvoId;

        if ($tipo === 'fase') {
            $this->salvarAlvoDependenciaTemplateItemFase($dependencia, $alvoId);

            return;
        }

        if ($tipo === 'item') {
            $this->salvarAlvoDependenciaTemplateItemItem($dependencia, $alvoId);

            return;
        }

        Notification::make()->title('Dependência inválida')->warning()->send();
    }

    public function salvarGatilhoDependenciaTemplateFaseItem(int $dependenciaId, string $gatilho): void
    {
        $dependencia = CronogramaTemplateFaseItemDependencia::find($dependenciaId);
        if (! $dependencia || ! GatilhoTemplateFase::tryFrom($gatilho)) {
            return;
        }

        $dependencia->gatilho = $gatilho;
        $dependencia->save();
    }

    public function salvarGapDependenciaTemplateFaseItem(int $dependenciaId, int|string|null $gap): void
    {
        $dependencia = CronogramaTemplateFaseItemDependencia::find($dependenciaId);
        if (! $dependencia) {
            return;
        }

        $dependencia->gap_dias = (int) ($gap ?? 0);
        $dependencia->save();
    }

    private function salvarAlvoDependenciaTemplateItemFase(CronogramaTemplateFaseItemDependencia $dependencia, int $faseId): void
    {
        $item = $dependencia->item;
        if (! $item) {
            return;
        }

        if ($faseId === $item->cronograma_template_fase_id) {
            Notification::make()
                ->title('Dependência circular não permitida')
                ->body('Um item não pode depender da própria fase.')
                ->warning()
                ->send();

            return;
        }

        $templateId = $item->templateFase?->cronograma_template_id;
        $fase = CronogramaTemplateFase::whereKey($faseId)
            ->where('cronograma_template_id', $templateId)
            ->first();

        if (! $fase) {
            Notification::make()->title('Dependência inválida para este template')->warning()->send();

            return;
        }

        $dependencia->update([
            'depende_de_template_fase_id' => $fase->id,
            'depende_de_item_id' => null,
        ]);
    }

    private function salvarAlvoDependenciaTemplateItemItem(CronogramaTemplateFaseItemDependencia $dependencia, int $itemDependenciaId): void
    {
        $item = $dependencia->item;
        if (! $item) {
            return;
        }

        if ($itemDependenciaId === $item->id) {
            Notification::make()->title('Um item não pode depender dele mesmo')->warning()->send();

            return;
        }

        $templateId = $item->templateFase?->cronograma_template_id;
        $itemDependencia = CronogramaTemplateFaseItem::whereKey($itemDependenciaId)
            ->whereHas('templateFase', fn ($query) => $query->where('cronograma_template_id', $templateId))
            ->first();

        if (! $itemDependencia) {
            Notification::make()->title('Dependência inválida para este template')->warning()->send();

            return;
        }

        if ($this->templateItemDependencyCreatesCycle($item->id, $itemDependencia->id)) {
            Notification::make()->title('Dependência circular não permitida')->warning()->send();

            return;
        }

        $dependencia->update([
            'depende_de_template_fase_id' => null,
            'depende_de_item_id' => $itemDependencia->id,
        ]);
    }

    public function salvarDependenciaTemplateFaseItem(int $itemId, ?string $dependencia): void
    {
        $item = CronogramaTemplateFaseItem::with('templateFase')->find($itemId);
        if (! $item || $item->templateFase?->cronograma_template_id !== $this->templateSelecionadoId) {
            return;
        }

        $dependencia = blank($dependencia) ? null : (string) $dependencia;

        if ($dependencia === null) {
            $item->depende_de_item_id = null;
            $item->depende_de_template_fase_id = null;
            $item->save();

            return;
        }

        if (is_numeric($dependencia)) {
            $dependencia = 'item:'.$dependencia;
        }

        [$tipo, $dependenciaId] = array_pad(explode(':', $dependencia, 2), 2, null);
        $dependenciaId = (int) $dependenciaId;

        if ($tipo === 'fase') {
            $this->salvarDependenciaTemplateFaseItemFase($item, $dependenciaId);

            return;
        }

        if ($tipo !== 'item') {
            Notification::make()->title('Dependência inválida')->warning()->send();

            return;
        }

        if ($dependenciaId === $item->id) {
            Notification::make()->title('Um item não pode depender dele mesmo')->warning()->send();

            return;
        }

        $templateId = $item->templateFase?->cronograma_template_id;
        $dependenciaItem = CronogramaTemplateFaseItem::whereKey($dependenciaId)
            ->whereHas('templateFase', fn ($query) => $query->where('cronograma_template_id', $templateId))
            ->first();

        if (! $dependenciaItem) {
            Notification::make()->title('Dependência inválida para este template')->warning()->send();

            return;
        }

        if ($this->templateItemDependencyCreatesCycle($item->id, $dependenciaItem->id)) {
            Notification::make()->title('Dependência circular não permitida')->warning()->send();

            return;
        }

        $item->depende_de_item_id = $dependenciaItem->id;
        $item->depende_de_template_fase_id = null;
        $item->save();
    }

    private function salvarDependenciaTemplateFaseItemFase(CronogramaTemplateFaseItem $item, int $faseId): void
    {
        if ($faseId === $item->cronograma_template_fase_id) {
            Notification::make()
                ->title('Dependência circular não permitida')
                ->body('Um item não pode depender da própria fase.')
                ->warning()
                ->send();

            return;
        }

        $templateId = $item->templateFase?->cronograma_template_id;
        $fase = CronogramaTemplateFase::whereKey($faseId)
            ->where('cronograma_template_id', $templateId)
            ->first();

        if (! $fase) {
            Notification::make()->title('Dependência inválida para este template')->warning()->send();

            return;
        }

        $item->depende_de_template_fase_id = $fase->id;
        $item->depende_de_item_id = null;
        $item->save();
    }

    private function templateItemDependencyCreatesCycle(int $itemId, int $dependenciaId): bool
    {
        $visitados = [];
        $atualId = $dependenciaId;

        while ($atualId) {
            if ($atualId === $itemId || isset($visitados[$atualId])) {
                return true;
            }

            $visitados[$atualId] = true;
            $atualId = (int) (CronogramaTemplateFaseItemDependencia::where('cronograma_template_fase_item_id', $atualId)
                ->whereNotNull('depende_de_item_id')
                ->value('depende_de_item_id') ?? 0);
        }

        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:CronogramaTemplates');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('View:CronogramaTemplates');
    }
}
