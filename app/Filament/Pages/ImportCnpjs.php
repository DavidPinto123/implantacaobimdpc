<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessCnpjImportJob;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\ImportacaoLog;
use App\Models\ImportacaoStaging;
use App\Models\ImportacaoTemplate;
use App\Models\Projeto;
use App\Models\User;
use App\Services\CnpjSpreadsheetParserService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use UnitEnum;

class ImportCnpjs extends Page
{
    use HasPageShield;
    use WithFileUploads;

    protected string $view = 'filament.pages.import-cnpjs';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Importar CNPJs';

    protected static ?string $title = 'Importar CNPJs';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.index';

    public int $currentStep = 1;

    public $arquivo;

    public ?string $arquivoPath = null;

    public ?string $arquivoNome = null;

    public int $headerRow = 1;

    public array $headers = [];

    public array $preview = [];

    public array $mapping = [];

    public array $camposDisponiveis = [];

    public array $fieldLabels = [];

    public array $previewPlanilha = [];

    public array $previewSistema = [];

    public array $resumoValidacao = [];

    public array $linhasCorrigir = [];

    public array $linhasPreparadas = [];

    public array $resultado = [];

    public array $estadosDisponiveis = [];

    public array $templates = [];

    public ?string $nomeTemplate = null;

    public array $importacoesAnteriores = [];

    public ?int $importacaoLogId = null;

    public array $importacaoSelecionada = [];

    public array $atividadeRecente = [];

    public bool $visualizandoHistorico = false;

    public array $stagingResumo = [];

    public array $stagingRows = [];

    public array $stagingRowsReconstruidas = [];

    public string $stagingFiltro = 'todos';

    public int $stagingPage = 1;

    public array $abas = [];

    public string|int|null $abaSelecionada = null;

    public array $enumMappedFields = [];

    public array $spreadsheetValues = [];

    public array $valueMapping = [];

    public array $systemEnumOptions = [];

    public array $novoValorEnum = [];

    public array $conflitos = [];

    public array $resolucoes = [];

    public int $totalConflitos = 0;

    public int $projetosComConflito = 0;

    public array $columnMap = [];

    public array $camposCalculados = [];

    public array $errosAgrupados = [];

    public array $projetosCorrecao = [];

    public array $buscaProjetos = [];

    public array $resultadosBuscaProjetos = [];

    public ?int $linhaCorrecaoModalIndex = null;

    public array $projetoCorrecaoModal = [];

    public string $buscaProjetoModal = '';

    public array $resultadosBuscaProjetoModal = [];

    public ?int $etapaPadrao = null;

    public array $etapasDisponiveis = [];

    public bool $ignorarLinhasComErro = false;

    public static function canAccess(): bool
    {
        return static::canImportCnpjs();
    }

    public function mount(): void
    {
        $this->authorizeImport();
        $this->initializeImportState();
    }

    protected static function canImportCnpjs(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('Create:CadastrarCnpj');
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeImport(): void
    {
        if (static::canImportCnpjs()) {
            return;
        }

        throw new AuthorizationException('Você não tem permissão para importar CNPJs.');
    }

    public function loadImportacoesAnteriores(): void
    {
        $this->importacoesAnteriores = ImportacaoLog::where('modulo', 'cnpjs')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ImportacaoLog $log): array => [
                'id' => $log->id,
                'arquivo' => $log->arquivo_original,
                'status' => $log->status,
                'total' => $log->total_linhas,
                'criados' => $log->linhas_criadas,
                'atualizados' => $log->linhas_atualizadas,
                'erros' => $log->linhas_erro,
                'usuario' => $log->user?->name ?? '-',
                'data' => $log->created_at->format('d/m/Y H:i'),
                'duracao' => $log->iniciado_em && $log->finalizado_em
                    ? $log->iniciado_em->diffForHumans($log->finalizado_em, true)
                    : null,
            ])
            ->toArray();
    }

    public function acompanharImportacao(int $logId): void
    {
        $log = ImportacaoLog::with('user')->find($logId);

        if (! $log instanceof ImportacaoLog) {
            Notification::make()
                ->title('Importação não encontrada.')
                ->danger()
                ->send();

            return;
        }

        $this->visualizandoHistorico = true;
        $this->importacaoLogId = $log->id;
        $this->stagingFiltro = 'todos';
        $this->stagingPage = 1;
        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->stagingRowsReconstruidas = [];
        $this->atividadeRecente = [];
        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->resultado = [];
        $this->syncImportacaoSelecionada($log);
        $this->verificarStatus();
        $this->currentStep = 6;
    }

    public function voltarAoHistorico(): void
    {
        $this->visualizandoHistorico = false;
        $this->currentStep = 1;
        $this->importacaoLogId = null;
        $this->importacaoSelecionada = [];
        $this->atividadeRecente = [];
        $this->resultado = [];
        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->stagingRowsReconstruidas = [];
        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->stagingFiltro = 'todos';
        $this->stagingPage = 1;
        $this->loadImportacoesAnteriores();
    }

    public function loadTemplates(): void
    {
        $this->templates = ImportacaoTemplate::where('modulo', 'cnpjs')
            ->orderBy('nome')
            ->get()
            ->map(fn (ImportacaoTemplate $template): array => [
                'id' => $template->id,
                'nome' => $template->nome,
            ])
            ->toArray();
    }

    public function carregarTemplate(mixed $templateId): void
    {
        if (! filled($templateId)) {
            return;
        }

        $template = ImportacaoTemplate::find($templateId);

        if (! $template instanceof ImportacaoTemplate) {
            return;
        }

        foreach ($this->headers as $header) {
            if (isset($template->mapeamento[$header])) {
                $this->mapping[$header] = $template->mapeamento[$header];
            }
        }

        Notification::make()->title("Template '{$template->nome}' carregado.")->success()->send();
    }

    public function salvarTemplate(): void
    {
        if (blank($this->nomeTemplate)) {
            Notification::make()->title('Informe um nome para o template.')->warning()->send();

            return;
        }

        ImportacaoTemplate::create([
            'nome' => $this->nomeTemplate,
            'modulo' => 'cnpjs',
            'mapeamento' => $this->mapping,
            'user_id' => Auth::id(),
        ]);

        $this->nomeTemplate = null;
        $this->loadTemplates();

        Notification::make()->title('Template salvo.')->success()->send();
    }

    public function updatedArquivo(): void
    {
        $this->authorizeImport();

        $this->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:10240'],
        ]);

        $nome = Str::slug(pathinfo($this->arquivo->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = $this->arquivo->getClientOriginalExtension();
        $path = 'importacoes/cnpjs/'.$nome.'-'.uniqid().'.'.$ext;

        $stream = $this->arquivo->readStream();

        try {
            Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->arquivoPath = $path;
        $this->arquivoNome = $this->arquivo->getClientOriginalName();
    }

    public function avancarParaAbas(): void
    {
        $this->authorizeImport();

        if (! $this->arquivoPath) {
            Notification::make()->title('Selecione um arquivo.')->warning()->send();

            return;
        }

        $parser = $this->getParser();
        $tempPath = $this->downloadToTemp();
        $this->abas = $parser->getSheetNames($tempPath);
        $this->abaSelecionada = $this->abas[0] ?? 0;
        $this->currentStep = 2;
    }

    public function avancarParaMapeamento(): void
    {
        $this->authorizeImport();

        if (! $this->arquivoPath) {
            Notification::make()->title('Selecione um arquivo.')->warning()->send();

            return;
        }

        if (! $this->abaSelecionada) {
            Notification::make()->title('Selecione uma aba.')->warning()->send();

            return;
        }

        $parser = $this->getParser();
        $fullPath = $this->downloadToTemp();
        $analysis = $parser->analyzeSheet($fullPath, $this->abaSelecionada, 5);

        $this->headerRow = $analysis['headerRow'];
        $this->headers = $analysis['headers'];
        $this->preview = $analysis['preview'];
        $this->previewPlanilha = $analysis['sampleValues'];
        $this->columnMap = $analysis['columnMap'];
        $this->mapping = $parser->suggestMapping($this->headers);
        $this->camposDisponiveis = $parser->getAvailableFields();
        $this->fieldLabels = $parser->getFieldLabels();
        $this->carregarPreviewSistema();
        $this->currentStep = 3;
    }

    public function updateMapping(string $header, string $value): void
    {
        $this->mapping[$header] = $value;
    }

    public function avancarParaValores(): void
    {
        $this->authorizeImport();

        $activeMapping = array_filter($this->mapping, fn ($value): bool => is_string($value) && $value !== '');

        if ($activeMapping === []) {
            Notification::make()->title('Mapeie pelo menos uma coluna.')->warning()->send();

            return;
        }

        $this->detectEnumFields();
        $this->currentStep = 4;
    }

    public function detectEnumFields(): void
    {
        $parser = $this->getParser();
        $enumColumns = $parser->getEnumColumns();
        $fullPath = $this->downloadToTemp();

        $this->enumMappedFields = [];
        $this->spreadsheetValues = [];
        $this->valueMapping = [];
        $this->systemEnumOptions = [];

        $enumHeaders = [];
        $headerToField = [];
        foreach ($this->mapping as $header => $dbField) {
            if (empty($dbField) || is_array($dbField) || ! isset($enumColumns[$dbField])) {
                continue;
            }

            $this->enumMappedFields[] = ['header' => $header, 'field' => $dbField];
            $enumHeaders[] = $header;
            $headerToField[$header] = $dbField;
            $this->systemEnumOptions[$dbField] = $parser->getEnumOptionsForField($dbField);
        }

        if ($enumHeaders === []) {
            return;
        }

        $allValues = $parser->detectAllUniqueValues(
            $fullPath,
            $this->abaSelecionada,
            $enumHeaders,
            $this->headerRow,
            50,
            $this->columnMap,
        );

        foreach ($allValues as $header => $uniqueValues) {
            $dbField = $headerToField[$header];
            $filtered = array_filter($uniqueValues, fn ($count, $k) => $k !== '' && $k !== null && $count !== null, ARRAY_FILTER_USE_BOTH);
            $this->spreadsheetValues[$dbField] = $filtered;

            foreach (array_keys($filtered) as $spreadsheetVal) {
                $this->valueMapping[$dbField][$spreadsheetVal] = $enumColumns[$dbField][$spreadsheetVal] ?? '';
            }
        }
    }

    public function adicionarValorEnum(string $field, string $spreadsheetVal): void
    {
        $novoValor = trim($this->novoValorEnum[$field][$spreadsheetVal] ?? '');

        if ($novoValor === '') {
            Notification::make()->title('Informe o novo valor.')->warning()->send();

            return;
        }

        if (! in_array($novoValor, $this->systemEnumOptions[$field] ?? [], true)) {
            $this->systemEnumOptions[$field][] = $novoValor;
        }

        $this->valueMapping[$field][$spreadsheetVal] = $novoValor;
        unset($this->novoValorEnum[$field][$spreadsheetVal]);

        Notification::make()->title("Valor '{$novoValor}' adicionado.")->success()->send();
    }

    public function avancarParaValidacao(): void
    {
        $this->authorizeImport();

        $activeMapping = array_filter($this->mapping, fn ($value): bool => is_string($value) && $value !== '');

        if ($activeMapping === []) {
            Notification::make()->title('Mapeie pelo menos uma coluna.')->warning()->send();

            return;
        }

        $parser = $this->getParser();
        $fullPath = $this->downloadToTemp();
        $parsedRows = $parser->parseRows($fullPath, $this->abaSelecionada, $this->mapping, $this->headerRow, $this->valueMapping, $this->columnMap);

        $this->linhasPreparadas = [];
        $this->linhasCorrigir = [];
        $this->ignorarLinhasComErro = false;

        $atualizacoes = 0;
        $erros = [];

        foreach ($parsedRows as $parsedRow) {
            $validation = $this->validateParsedRow($parsedRow);

            if ($validation['resolved']) {
                $this->linhasPreparadas[] = $validation;
                $atualizacoes++;

                continue;
            }

            $this->linhasCorrigir[] = $validation;
            $erros[] = [
                'linha' => $parsedRow['linha'],
                'msg' => implode(' | ', $validation['errors']),
            ];
        }

        $this->resumoValidacao = [
            'total' => count($parsedRows),
            'novos' => 0,
            'atualizacoes' => $atualizacoes,
            'erros' => count($this->linhasCorrigir),
            'detalhes' => array_slice($erros, 0, 50),
        ];

        $this->projetosCorrecao = $this->buildProjetosCorrecaoFromLinhasCorrigir();
        $this->syncLinhasCorrigirWithExistingProjetosCorrecao();
        $this->detectarConflitos($this->rowsForConflictReview());
        $this->syncBuscaProjetos();

        $this->currentStep = 5;
    }

    public function detectarConflitos(array $rows): void
    {
        $resolucoesAtuais = $this->resolucoes;
        $projetos = Projeto::query()
            ->whereIn(
                'id',
                collect($rows)
                    ->pluck('projeto_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            )
            ->get()
            ->keyBy('id');

        $this->conflitos = [];
        $this->resolucoes = [];
        $this->totalConflitos = 0;
        $this->projetosComConflito = 0;

        foreach ($rows as $row) {
            $projetoId = $row['projeto_id'] ?? null;

            if (! filled($projetoId)) {
                continue;
            }

            $projeto = $projetos->get($projetoId);

            if (! $projeto instanceof Projeto) {
                continue;
            }

            $campos = [];

            foreach ($this->buildProjetoUpdatePayload($row, false) as $campo => $valorPlanilha) {
                $valorBanco = $projeto->{$campo};

                $valorBancoVazio = $valorBanco === null || $valorBanco === '';
                $valorPlanilhaVazio = $valorPlanilha === null || $valorPlanilha === '';

                if ($valorBancoVazio && $valorPlanilhaVazio) {
                    continue;
                }

                if ((string) $valorBanco === (string) $valorPlanilha) {
                    continue;
                }

                $campos[] = [
                    'campo' => $campo,
                    'valor_banco' => $valorBancoVazio ? '—' : (string) $valorBanco,
                    'valor_planilha' => $valorPlanilhaVazio ? '—' : (string) $valorPlanilha,
                ];
            }

            if ($campos !== []) {
                $conflictKey = $this->buildConflictKey($row, $projeto);
                $codigoExibicao = $row['nova_sigla'] ?: ($projeto->nova_sigla ?: ($projeto->codigo ?: (string) $projeto->id));

                $this->conflitos[$conflictKey] = [
                    'codigo' => $codigoExibicao,
                    'unidade' => $projeto->nome ?? $codigoExibicao,
                    'campos' => $campos,
                ];

                foreach ($campos as $conflito) {
                    $this->resolucoes[$conflictKey][$conflito['campo']] = $resolucoesAtuais[$conflictKey][$conflito['campo']] ?? 'planilha';
                }

                $this->totalConflitos += count($campos);
                $this->projetosComConflito++;
            }
        }
    }

    public function resolverTodosConflitos(string $decisao): void
    {
        foreach ($this->resolucoes as $codigo => $campos) {
            foreach (array_keys($campos) as $campo) {
                $this->resolucoes[$codigo][$campo] = $decisao;
            }
        }
    }

    public function updatedResolucoes(mixed $value, string $key): void
    {
        if (! preg_match('/^([^\.]+\:[^\.]+)\.(.+)$/', $key, $matches)) {
            return;
        }

        $conflictKey = $matches[1];
        $field = $matches[2];

        if (! $this->isCnpjConflictField($field)) {
            return;
        }

        $camposRelacionados = collect($this->conflitos[$conflictKey]['campos'] ?? [])
            ->pluck('campo')
            ->filter(fn (string $campo): bool => $this->isCnpjConflictField($campo));

        foreach ($camposRelacionados as $campo) {
            $this->resolucoes[$conflictKey][$campo] = (string) $value;
        }
    }

    public function updatedIgnorarLinhasComErro(): void
    {
        $this->detectarConflitos($this->rowsForConflictReview());
    }

    public function avancarParaConflitos(): void
    {
        $this->authorizeImport();

        if ($this->hasBlockingCorrections()) {
            Notification::make()
                ->title('Existem linhas pendentes de correção.')
                ->body('Corrija ou complete todas as linhas antes de revisar os conflitos.')
                ->warning()
                ->send();

            $this->currentStep = 5;

            return;
        }

        $this->detectarConflitos($this->rowsForConflictReview());

        if ($this->conflitos === []) {
            $this->executarImportacao();

            return;
        }

        $this->currentStep = 55;
    }

    public function atualizarProjetoCorrecao(int $index, mixed $projetoId): void
    {
        if (! isset($this->linhasCorrigir[$index])) {
            return;
        }

        if (! filled($projetoId)) {
            $this->linhasCorrigir[$index]['projeto_id'] = null;
            $this->linhasCorrigir[$index]['projeto_label'] = null;
            $this->linhasCorrigir[$index]['nova_sigla'] = $this->linhasCorrigir[$index]['original_nova_sigla'] ?? null;
            $this->linhasCorrigir[$index]['sigla_antiga'] = $this->linhasCorrigir[$index]['original_sigla_antiga'] ?? null;
            $this->linhasCorrigir[$index]['pais_id'] = $this->linhasCorrigir[$index]['original_pais_id'] ?? null;
            $this->linhasCorrigir[$index]['estado_id'] = $this->linhasCorrigir[$index]['original_estado_id'] ?? null;
            $this->linhasCorrigir[$index]['cidade_id'] = $this->linhasCorrigir[$index]['original_cidade_id'] ?? null;
            $this->linhasCorrigir[$index]['uf'] = $this->linhasCorrigir[$index]['original_uf'] ?? '';
            $this->linhasCorrigir[$index]['cidade_nome'] = $this->linhasCorrigir[$index]['original_cidade_nome'] ?? '';
            $this->linhasCorrigir[$index]['resolved'] = false;

            return;
        }

        $projeto = Projeto::find($projetoId);

        if (! $projeto instanceof Projeto) {
            return;
        }

        $this->linhasCorrigir[$index]['projeto_id'] = $projeto->id;
        $this->linhasCorrigir[$index]['projeto_label'] = $this->formatProjetoLabel($projeto);
        $this->linhasCorrigir[$index]['nova_sigla'] = $this->firstFilledString($this->linhasCorrigir[$index]['original_nova_sigla'] ?? null, $projeto->nova_sigla);
        $this->linhasCorrigir[$index]['sigla_antiga'] = $this->firstFilledString($this->linhasCorrigir[$index]['original_sigla_antiga'] ?? null, $projeto->sigla_antiga);
        $this->linhasCorrigir[$index]['pais_id'] = $this->linhasCorrigir[$index]['original_pais_id'] ?? $projeto->pais_id;
        $this->linhasCorrigir[$index]['estado_id'] = $this->linhasCorrigir[$index]['original_estado_id'] ?? $projeto->estado_id;
        $this->linhasCorrigir[$index]['cidade_id'] = $this->linhasCorrigir[$index]['original_cidade_id'] ?? $projeto->cidade_id;
        $this->linhasCorrigir[$index]['uf'] = $this->firstFilledString($this->linhasCorrigir[$index]['original_uf'] ?? null, $projeto->estado?->uf) ?? '';
        $this->linhasCorrigir[$index]['cidade_nome'] = $this->firstFilledString($this->linhasCorrigir[$index]['original_cidade_nome'] ?? null, $projeto->cidade?->nome) ?? '';
        $this->linhasCorrigir[$index]['resolved'] = $this->isCorrectionResolved($this->linhasCorrigir[$index]);
    }

    public function updatedLinhasCorrigir(mixed $value, string $key): void
    {
        if (! preg_match('/^(\d+)\.(.+)$/', $key, $matches)) {
            return;
        }

        $index = (int) $matches[1];
        $field = $matches[2];

        if (! isset($this->linhasCorrigir[$index])) {
            return;
        }

        if ($field === 'cnpj_formatado') {
            $parser = $this->getParser();
            $this->linhasCorrigir[$index]['cnpj_formatado'] = $parser->normalizeFormattedCnpj((string) $value) ?? trim((string) $value);
        }

        if ($field === 'cidade_nome') {
            $this->linhasCorrigir[$index]['cidade_id'] = null;

            if (! empty($this->linhasCorrigir[$index]['estado_id']) && filled($this->linhasCorrigir[$index]['cidade_nome'] ?? null)) {
                $cidade = Cidade::query()
                    ->where('estado_id', $this->linhasCorrigir[$index]['estado_id'])
                    ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim((string) $this->linhasCorrigir[$index]['cidade_nome']))])
                    ->first();

                $this->linhasCorrigir[$index]['cidade_id'] = $cidade?->id;
            }
        }

        $this->linhasCorrigir[$index]['resolved'] = $this->isCorrectionResolved($this->linhasCorrigir[$index]);
    }

    public function updatedBuscaProjetos(mixed $value, string $key): void
    {
        if (! ctype_digit($key)) {
            return;
        }

        $index = (int) $key;
        $search = trim((string) $value);

        if ($search === '' || mb_strlen($search) < 2) {
            $this->resultadosBuscaProjetos[$index] = [];

            return;
        }

        $this->resultadosBuscaProjetos[$index] = $this->searchProjetos($search);
    }

    public function selecionarProjetoCorrecao(int $index, int $projetoId): void
    {
        $this->atualizarProjetoCorrecao($index, $projetoId);
        $this->buscaProjetos[$index] = $this->linhasCorrigir[$index]['projeto_label'] ?? '';
        $this->resultadosBuscaProjetos[$index] = [];
    }

    public function limparProjetoCorrecao(int $index): void
    {
        $this->atualizarProjetoCorrecao($index, null);
        $this->buscaProjetos[$index] = '';
        $this->resultadosBuscaProjetos[$index] = [];
    }

    public function abrirLinhaCorrecaoModal(int $index): void
    {
        if (! isset($this->linhasCorrigir[$index])) {
            return;
        }

        $linha = $this->linhasCorrigir[$index];
        $codigo = $this->resolveProjetoCorrecaoCodigo($linha)
            ?? $this->resolveProjetoCorrecaoNome($linha)
            ?? '';

        $this->linhaCorrecaoModalIndex = $index;
        $this->projetoCorrecaoModal = [
            'codigo' => $codigo,
            'nome' => $this->resolveProjetoCorrecaoNome($linha) ?? $codigo,
            'sigla' => $codigo,
            'sigla_antiga' => $this->firstFilledString(
                $linha['sigla_antiga'] ?? null,
                $linha['original_sigla_antiga'] ?? null,
            ),
            'marca' => $linha['empresa'] ?? '',
            'uf' => $linha['uf'] ?? '',
            'estado_id' => $linha['estado_id'] ?? null,
            'cidade_nome' => $linha['cidade_nome'] ?? '',
            'cidade_id' => $linha['cidade_id'] ?? null,
            'pais_id' => $linha['pais_id'] ?? null,
            'etapa_id' => $this->etapaPadrao,
            'grupo_chave' => $this->resolveProjetoCorrecaoAgrupamento($linha),
        ];
        $this->buscaProjetoModal = '';
        $this->resultadosBuscaProjetoModal = [];

        $this->dispatch('open-modal', id: 'linha-correcao-modal');
    }

    public function updatedBuscaProjetoModal(mixed $value): void
    {
        $search = trim((string) $value);

        if ($search === '' || mb_strlen($search) < 2) {
            $this->resultadosBuscaProjetoModal = [];

            return;
        }

        $this->resultadosBuscaProjetoModal = $this->searchProjetos($search);
    }

    public function atualizarEstadoProjetoCorrecaoModal(mixed $estadoId): void
    {
        if (! filled($estadoId)) {
            $this->projetoCorrecaoModal['estado_id'] = null;
            $this->projetoCorrecaoModal['pais_id'] = null;

            return;
        }

        $estado = Estado::with('pais')->find($estadoId);

        if (! $estado instanceof Estado) {
            return;
        }

        $this->projetoCorrecaoModal['estado_id'] = $estado->id;
        $this->projetoCorrecaoModal['pais_id'] = $estado->pais_id;
        $this->projetoCorrecaoModal['uf'] = $estado->uf ?? $estado->nome;
    }

    public function criarProjetoDaLinhaCorrecao(): void
    {
        if ($this->projetoCorrecaoModal === []) {
            return;
        }

        $codigo = trim((string) ($this->projetoCorrecaoModal['codigo'] ?? ''));
        $alreadyExists = $codigo !== '' && Projeto::where('codigo', $codigo)->exists();
        $projeto = $this->createProjetoFromSuggestion($this->projetoCorrecaoModal);

        if (! $projeto instanceof Projeto) {
            return;
        }

        $this->associateProjetoToMatchingLinhas(
            $projeto,
            $this->projetoCorrecaoModal['grupo_chave'] ?? null,
        );

        $this->markMatchingProjetosCorrecaoAsCreated(
            $this->projetoCorrecaoModal['grupo_chave'] ?? null,
        );

        $this->syncBuscaProjetos();
        $this->dispatch('close-modal', id: 'linha-correcao-modal');

        Notification::make()
            ->title($alreadyExists ? "Projeto {$projeto->codigo} vinculado." : "Projeto {$projeto->codigo} criado.")
            ->success()
            ->send();
    }

    public function vincularProjetoExistenteDaLinhaCorrecao(int $projetoId): void
    {
        if ($this->projetoCorrecaoModal === []) {
            return;
        }

        $projeto = Projeto::find($projetoId);

        if (! $projeto instanceof Projeto) {
            return;
        }

        $this->associateProjetoToMatchingLinhas(
            $projeto,
            $this->projetoCorrecaoModal['grupo_chave'] ?? null,
        );

        $this->markMatchingProjetosCorrecaoAsCreated(
            $this->projetoCorrecaoModal['grupo_chave'] ?? null,
        );

        $this->syncBuscaProjetos();
        $this->buscaProjetoModal = $this->formatProjetoLabel($projeto);
        $this->resultadosBuscaProjetoModal = [];
        $this->dispatch('close-modal', id: 'linha-correcao-modal');

        Notification::make()->title("Projeto {$projeto->codigo} vinculado.")->success()->send();
    }

    public function atualizarEstadoCorrecao(int $index, mixed $estadoId): void
    {
        if (! isset($this->linhasCorrigir[$index])) {
            return;
        }

        if (! filled($estadoId)) {
            $this->linhasCorrigir[$index]['estado_id'] = null;
            $this->linhasCorrigir[$index]['pais_id'] = null;
            $this->linhasCorrigir[$index]['cidade_id'] = null;
            $this->linhasCorrigir[$index]['resolved'] = $this->isCorrectionResolved($this->linhasCorrigir[$index]);

            return;
        }

        $estado = Estado::with('pais')->find($estadoId);

        if (! $estado instanceof Estado) {
            return;
        }

        $this->linhasCorrigir[$index]['estado_id'] = $estado->id;
        $this->linhasCorrigir[$index]['pais_id'] = $estado->pais_id;
        $this->linhasCorrigir[$index]['uf'] = $estado->uf ?? $estado->nome;

        $cidadeNome = $this->linhasCorrigir[$index]['cidade_nome'] ?? '';
        $cidade = null;
        if ($cidadeNome !== '') {
            $cidade = Cidade::query()
                ->where('estado_id', $estado->id)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim((string) $cidadeNome))])
                ->first();
        }

        $this->linhasCorrigir[$index]['cidade_id'] = $cidade?->id;
        $this->linhasCorrigir[$index]['resolved'] = $this->isCorrectionResolved($this->linhasCorrigir[$index]);
    }

    public function executarImportacao(): void
    {
        $this->authorizeImport();

        if ($this->hasBlockingCorrections()) {
            Notification::make()
                ->title('Existem linhas pendentes de correção.')
                ->body('Corrija ou complete todas as linhas antes de importar.')
                ->warning()
                ->send();

            $this->currentStep = 5;

            return;
        }

        $resolucoesFiltradas = $this->filteredConflictResolutions();
        $projetosIgnorados = $this->ignoredProjects();
        $linhasIgnoradas = $this->ignoredValidationLines();
        $totalLinhas = (int) ($this->resumoValidacao['total'] ?? (count($this->linhasPreparadas) + count($this->linhasCorrigir)));

        $log = ImportacaoLog::create([
            'arquivo_original' => $this->arquivoNome,
            'arquivo_path' => (string) $this->arquivoPath,
            'modulo' => 'cnpjs',
            'status' => 'pendente',
            'total_linhas' => $totalLinhas,
            'mapeamento_usado' => [
                'columns' => array_filter($this->mapping, fn ($value): bool => is_string($value) && $value !== ''),
                'values' => $this->valueMapping,
                'headerRow' => $this->headerRow,
                'sheet' => $this->abaSelecionada,
                'columnMap' => $this->columnMap,
                'row_overrides' => $this->buildRowOverrides(),
                'resolucoes' => $resolucoesFiltradas,
                'projetos_ignorados' => $projetosIgnorados,
                'linhas_ignoradas' => $linhasIgnoradas,
            ],
            'user_id' => Auth::id(),
        ]);

        $this->importacaoLogId = $log->id;
        $this->visualizandoHistorico = false;
        $this->syncImportacaoSelecionada($log);

        ProcessCnpjImportJob::dispatch($log->id, (int) Auth::id());

        Notification::make()
            ->title('Importação iniciada')
            ->body('O processamento está sendo feito em segundo plano. Você será notificado ao concluir.')
            ->success()
            ->send();

        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->resultado = [
            'status' => 'processando',
            'mensagem' => 'A importação está sendo processada em segundo plano.',
        ];
        $this->currentStep = 6;
    }

    public function verificarStatus(): void
    {
        if (! $this->importacaoLogId) {
            return;
        }

        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog) {
            return;
        }

        $this->syncImportacaoSelecionada($log);

        $processados = in_array($log->status, ['pendente', 'processando'], true)
            ? ImportacaoStaging::where('importacao_log_id', $log->id)->count()
            : ($log->linhas_criadas + $log->linhas_atualizadas + $log->linhas_erro);
        $percentual = $log->total_linhas > 0
            ? round(($processados / $log->total_linhas) * 100)
            : 0;

        $this->resultado = [
            'status' => $log->status,
            'total' => $log->total_linhas,
            'criados' => $log->linhas_criadas,
            'atualizados' => $log->linhas_atualizadas,
            'erros' => $log->linhas_erro,
            'processados' => $processados,
            'percentual' => $percentual,
            'detalhes_erros' => $log->erros ?? [],
        ];

        if ($this->shouldLoadStagingDetails($log->status)) {
            $this->carregarStagingResumo();
            $this->carregarAtividadeRecente();
        } else {
            $this->stagingResumo = [];
            $this->stagingRows = [];
            $this->atividadeRecente = [];
        }

        if ($log->status === 'concluido' && ! empty($log->erros) && empty($this->errosAgrupados)) {
            $this->carregarErrosResolver();
        }

    }

    public function carregarStagingResumo(): void
    {
        if (! $this->importacaoLogId) {
            return;
        }

        $counts = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId)
            ->selectRaw('acao, count(*) as total')
            ->groupBy('acao')
            ->pluck('total', 'acao')
            ->toArray();

        if ($counts === []) {
            $counts = collect($this->getStagingRowsForHistoryFallback())
                ->countBy('acao')
                ->toArray();
        }

        $this->stagingResumo = [
            'criar' => $counts['criar'] ?? 0,
            'atualizar' => $counts['atualizar'] ?? 0,
            'atualizado' => $counts['atualizado'] ?? 0,
            'erro' => $counts['erro'] ?? 0,
            'ignorar' => $counts['ignorar'] ?? 0,
            'outros' => array_sum(array_diff_key($counts, array_flip([
                'criar',
                'atualizar',
                'atualizado',
                'erro',
                'ignorar',
            ]))),
            'total' => array_sum($counts),
        ];

        $this->carregarStagingRows();

        if (($counts['erro'] ?? 0) > 0 && empty($this->errosAgrupados)) {
            $this->carregarErrosStagingResolver();
        }
    }

    public function carregarStagingRows(): void
    {
        if (! $this->importacaoLogId) {
            return;
        }

        $query = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId);

        if (! $query->exists()) {
            $rows = collect($this->getStagingRowsForHistoryFallback());

            if ($this->stagingFiltro !== 'todos') {
                $rows = $this->stagingFiltro === 'outros'
                    ? $rows->filter(fn (array $row): bool => ! in_array($row['acao'], ['criar', 'atualizar', 'atualizado', 'erro', 'ignorar'], true))
                    : $rows->where('acao', $this->stagingFiltro);
            }

            $this->stagingRows = $rows
                ->sortBy('linha')
                ->forPage($this->stagingPage, 50)
                ->values()
                ->all();

            return;
        }

        if ($this->stagingFiltro !== 'todos') {
            if ($this->stagingFiltro === 'outros') {
                $query->whereNotIn('acao', ['criar', 'atualizar', 'atualizado', 'erro', 'ignorar']);
            } else {
                $query->where('acao', $this->stagingFiltro);
            }
        }

        $this->stagingRows = $query
            ->orderBy('linha_planilha')
            ->limit(50)
            ->offset(($this->stagingPage - 1) * 50)
            ->get()
            ->map(fn (ImportacaoStaging $staging): array => $this->mapStagingRow($staging))
            ->toArray();
    }

    public function carregarAtividadeRecente(): void
    {
        if (! $this->importacaoLogId) {
            $this->atividadeRecente = [];

            return;
        }

        if (! ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId)->exists()) {
            $this->atividadeRecente = collect($this->getStagingRowsForHistoryFallback())
                ->sortByDesc(fn (array $row): int => (int) ($row['linha'] ?? 0))
                ->take(10)
                ->values()
                ->all();

            return;
        }

        $this->atividadeRecente = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ImportacaoStaging $staging): array => $this->mapStagingRow($staging))
            ->toArray();
    }

    public function updatedStagingFiltro(): void
    {
        $this->stagingPage = 1;
        $this->carregarStagingRows();
    }

    public function stagingPaginaAnterior(): void
    {
        if ($this->stagingPage > 1) {
            $this->stagingPage--;
            $this->carregarStagingRows();
        }
    }

    public function stagingProximaPagina(): void
    {
        $this->stagingPage++;
        $this->carregarStagingRows();
    }

    public function confirmarImportacao(): void
    {
        $this->authorizeImport();

        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog || $log->status !== 'staged') {
            Notification::make()->title('Importação não está no estado correto.')->danger()->send();

            return;
        }

        $log->update(['status' => 'confirmando']);

        $atualizados = 0;
        $erros = [];

        try {
            DB::transaction(function () use ($log, &$atualizados, &$erros): void {
                $stagingRows = ImportacaoStaging::where('importacao_log_id', $log->id)
                    ->where('acao', 'atualizar')
                    ->orderBy('linha_planilha')
                    ->get();

                foreach ($stagingRows as $staging) {
                    try {
                        $dados = $staging->dados;
                        $projeto = Projeto::find($dados['projeto_id'] ?? null);

                        if (! $projeto instanceof Projeto) {
                            throw new \RuntimeException('Projeto não encontrado no momento da confirmação.');
                        }

                        $projeto->update($this->buildProjetoUpdatePayload($dados));
                        $staging->update([
                            'acao' => 'atualizado',
                            'erro' => null,
                        ]);
                        $atualizados++;
                    } catch (\Throwable $exception) {
                        $erro = [
                            'linha' => $staging->linha_planilha,
                            'msg' => Str::limit($exception->getMessage(), 200),
                            'tipo' => 'outro',
                        ];

                        $erros[] = $erro;
                        $staging->update(['acao' => 'erro', 'erro' => $erro]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            $log->update([
                'status' => 'staged',
                'erros' => array_merge($log->erros ?? [], $erros),
            ]);

            Notification::make()
                ->title('Erro ao confirmar importação')
                ->body(Str::limit($exception->getMessage(), 200))
                ->danger()
                ->send();

            $this->carregarStagingResumo();

            return;
        }

        $mergedErrors = array_merge($log->erros ?? [], $erros);

        $log->update([
            'status' => 'concluido',
            'linhas_atualizadas' => $atualizados,
            'linhas_erro' => count($mergedErrors),
            'erros' => $mergedErrors ?: null,
            'finalizado_em' => now(),
        ]);

        $this->resultado = [
            'status' => 'concluido',
            'total' => $log->total_linhas,
            'atualizados' => $atualizados,
            'erros' => count($mergedErrors),
            'detalhes_erros' => $mergedErrors,
        ];
        $this->carregarStagingResumo();
        $this->carregarAtividadeRecente();
        $this->loadImportacoesAnteriores();

        if ($mergedErrors !== []) {
            $this->carregarErrosResolver();
        }

        Notification::make()
            ->title('Importação confirmada')
            ->body("{$atualizados} projetos atualizados.")
            ->success()
            ->send();

    }

    public function descartarImportacao(): void
    {
        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog) {
            return;
        }

        $log->update(['status' => 'descartado', 'finalizado_em' => now()]);

        Notification::make()->title('Importação descartada.')->info()->send();
        $this->novaImportacao();
    }

    private function carregarErrosStagingResolver(): void
    {
        $erroRows = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId)
            ->where('acao', 'erro')
            ->get();

        $erros = $erroRows->map(fn (ImportacaoStaging $s) => [
            'linha' => $s->linha_planilha,
            'msg' => $s->erro['msg'] ?? '',
            'tipo' => $s->erro['tipo'] ?? 'outro',
            'codigo' => $s->codigo,
            'dados' => $s->dados,
        ])->toArray();

        $this->errosAgrupados = [
            'projeto_nao_criado' => array_values(array_filter($erros, fn ($e) => ($e['tipo'] ?? '') === 'projeto_nao_criado')),
            'projeto_nao_encontrado' => array_values(array_filter($erros, fn ($e) => ($e['tipo'] ?? '') === 'projeto_nao_encontrado')),
            'outro' => array_values(array_filter($erros, fn ($e) => ! in_array(($e['tipo'] ?? ''), ['projeto_nao_criado', 'projeto_nao_encontrado'], true))),
        ];

        $this->montarProjetosCorrecao(array_merge(
            $this->errosAgrupados['projeto_nao_criado'] ?? [],
            $this->errosAgrupados['projeto_nao_encontrado'] ?? [],
        ));
    }

    public function carregarErrosResolver(): void
    {
        $log = ImportacaoLog::find($this->importacaoLogId);
        if (! $log || empty($log->erros)) {
            $this->errosAgrupados = [];

            return;
        }

        $erros = $log->erros;
        $this->errosAgrupados = [
            'projeto_nao_criado' => array_values(array_filter($erros, fn ($e) => ($e['tipo'] ?? '') === 'projeto_nao_criado')),
            'projeto_nao_encontrado' => array_values(array_filter($erros, fn ($e) => ($e['tipo'] ?? '') === 'projeto_nao_encontrado')),
            'outro' => array_values(array_filter($erros, fn ($e) => ! in_array(($e['tipo'] ?? ''), ['projeto_nao_criado', 'projeto_nao_encontrado'], true))),
        ];

        $this->montarProjetosCorrecao(array_merge(
            $this->errosAgrupados['projeto_nao_criado'] ?? [],
            $this->errosAgrupados['projeto_nao_encontrado'] ?? [],
        ));
    }

    private function montarProjetosCorrecao(array $errosProjetoNaoEncontrado): void
    {
        $codigosUnicos = [];

        foreach ($errosProjetoNaoEncontrado as $erro) {
            $codigo = $erro['codigo'] ?? '';

            if ($codigo !== '' && $codigo !== '-' && ! isset($codigosUnicos[$codigo])) {
                $codigosUnicos[$codigo] = $erro;
            }
        }

        $this->projetosCorrecao = [];

        foreach ($codigosUnicos as $codigo => $erro) {
            $dados = $erro['dados'] ?? [];
            $estadoId = null;
            $paisId = null;

            if (! empty($dados['uf'])) {
                $estado = Estado::where('uf', strtoupper(trim($dados['uf'])))->first();
                if ($estado) {
                    $estadoId = $estado->id;
                    $paisId = $estado->pais_id;
                }
            }

            $novaSigla = $dados['nova_sigla'] ?? '';
            $this->projetosCorrecao[] = [
                'codigo' => $novaSigla ?: $codigo,
                'nome' => $dados['unidade'] ?? $novaSigla ?: $codigo,
                'sigla' => $novaSigla ?: $codigo,
                'marca' => $dados['empresa'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'estado_id' => $estadoId,
                'cidade_nome' => $dados['cidade_nome'] ?? ($dados['cidade'] ?? ''),
                'cidade_id' => null,
                'pais_id' => $paisId,
                'etapa_id' => $this->etapaPadrao,
                'criado' => Projeto::where('codigo', $novaSigla ?: $codigo)->exists(),
            ];
        }
    }

    public function criarProjetoCorrecao(int $index): void
    {
        if (! isset($this->projetosCorrecao[$index])) {
            return;
        }

        $dados = $this->projetosCorrecao[$index];
        $codigo = trim((string) ($dados['codigo'] ?? ''));
        $alreadyExists = $codigo !== '' && Projeto::where('codigo', $codigo)->exists();
        $projeto = $this->createProjetoFromSuggestion($dados);

        if (! $projeto instanceof Projeto) {
            return;
        }

        $this->markMatchingProjetosCorrecaoAsCreated($dados['grupo_chave'] ?? null);
        $this->associateProjetoToMatchingLinhas($projeto, $dados['grupo_chave'] ?? null);
        $this->syncBuscaProjetos();

        Notification::make()
            ->title($alreadyExists ? "Projeto {$projeto->codigo} vinculado." : "Projeto {$projeto->codigo} criado.")
            ->success()
            ->send();
    }

    public function criarTodosProjetos(): void
    {
        $criados = 0;
        foreach ($this->projetosCorrecao as $i => $proj) {
            if (! $proj['criado'] && ! empty($proj['cidade_nome']) && ! empty($proj['estado_id'])) {
                $this->criarProjetoCorrecao($i);
                if ($this->projetosCorrecao[$i]['criado']) {
                    $criados++;
                }
            }
        }

        if ($criados === 0) {
            Notification::make()->title('Nenhum projeto criado. Verifique os dados.')->warning()->send();
        }
    }

    public function atualizarEstadoProjetoCorrecao(int $index, ?int $estadoId): void
    {
        if (! isset($this->projetosCorrecao[$index])) {
            return;
        }

        if (! $estadoId) {
            $this->projetosCorrecao[$index]['estado_id'] = null;
            $this->projetosCorrecao[$index]['pais_id'] = null;

            return;
        }

        $estado = Estado::with('pais')->find($estadoId);
        if (! $estado) {
            return;
        }

        $this->projetosCorrecao[$index]['estado_id'] = $estado->id;
        $this->projetosCorrecao[$index]['pais_id'] = $estado->pais_id;
        $this->projetosCorrecao[$index]['uf'] = $estado->uf ?? $estado->nome;
    }

    public function reimportarComErros(?string $tipo = null): void
    {
        if (! $this->canRetryProjectErrors($tipo)) {
            Notification::make()
                ->title('Crie os projetos pendentes antes de reimportar.')
                ->warning()
                ->send();

            return;
        }

        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog) {
            return;
        }

        $erroRowsQuery = ImportacaoStaging::where('importacao_log_id', $log->id)
            ->where('acao', 'erro')
            ->orderBy('linha_planilha');

        if ($tipo) {
            $erroRowsQuery->where('erro->tipo', $tipo);
        }

        $erroRows = $erroRowsQuery->get();

        $dadosRetry = $erroRows
            ->map(fn (ImportacaoStaging $staging) => $staging->dados)
            ->filter()
            ->values()
            ->all();

        if ($dadosRetry === [] && ($tipo === null || $tipo === 'projeto_nao_criado')) {
            foreach ($this->errosAgrupados['projeto_nao_criado'] ?? [] as $erro) {
                if (! empty($erro['dados'])) {
                    $dadosRetry[] = $erro['dados'];
                }
            }
        }

        if ($dadosRetry === [] && ($tipo === null || $tipo === 'projeto_nao_encontrado')) {
            foreach ($this->errosAgrupados['projeto_nao_encontrado'] ?? [] as $erro) {
                if (! empty($erro['dados'])) {
                    $dadosRetry[] = $erro['dados'];
                }
            }
        }

        if ($dadosRetry === []) {
            Notification::make()->title('Nenhuma linha para reimportar.')->info()->send();

            return;
        }

        $retryLog = ImportacaoLog::create([
            'arquivo_original' => ($log->arquivo_original ?? 'retry').' (retry)',
            'arquivo_path' => $log->arquivo_path ?? '',
            'modulo' => 'cnpjs',
            'status' => 'pendente',
            'total_linhas' => count($dadosRetry),
            'mapeamento_usado' => [
                'retry_dados' => $dadosRetry,
                'retry_de' => $log->id,
            ],
            'user_id' => Auth::id(),
        ]);

        ProcessCnpjImportJob::dispatch($retryLog->id, (int) Auth::id());

        $this->importacaoLogId = $retryLog->id;
        $this->visualizandoHistorico = false;
        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->atividadeRecente = [];
        $this->syncImportacaoSelecionada($retryLog);
        $this->resultado = [
            'status' => 'processando',
            'mensagem' => 'Reimportação em andamento...',
        ];

        Notification::make()
            ->title('Reimportação iniciada')
            ->body(count($dadosRetry).' linhas sendo reprocessadas.')
            ->success()
            ->send();

        $this->loadImportacoesAnteriores();
    }

    public function novaImportacao(): void
    {
        $this->reset([
            'currentStep',
            'arquivo',
            'arquivoPath',
            'arquivoNome',
            'nomeTemplate',
            'columnMap',
            'camposCalculados',
            'abas',
            'abaSelecionada',
            'headerRow',
            'headers',
            'preview',
            'mapping',
            'previewPlanilha',
            'previewSistema',
            'enumMappedFields',
            'spreadsheetValues',
            'valueMapping',
            'systemEnumOptions',
            'novoValorEnum',
            'resumoValidacao',
            'linhasCorrigir',
            'linhasPreparadas',
            'conflitos',
            'resolucoes',
            'totalConflitos',
            'projetosComConflito',
            'errosAgrupados',
            'projetosCorrecao',
            'resultado',
            'importacaoLogId',
            'importacaoSelecionada',
            'atividadeRecente',
            'visualizandoHistorico',
            'stagingResumo',
            'stagingRows',
            'stagingRowsReconstruidas',
            'stagingFiltro',
            'stagingPage',
            'ignorarLinhasComErro',
        ]);

        $this->currentStep = 1;
        $this->stagingFiltro = 'todos';
        $this->stagingPage = 1;
        $this->initializeImportState();
    }

    public function voltarStep(): void
    {
        if ($this->currentStep === 55) {
            $this->currentStep = 5;
        } elseif ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    private function initializeImportState(): void
    {
        $this->loadTemplates();
        $this->loadImportacoesAnteriores();
        $this->ignorarLinhasComErro = false;

        $parser = $this->getParser();
        $this->camposDisponiveis = $parser->getAvailableFields();
        $this->fieldLabels = $parser->getFieldLabels();
        $this->camposCalculados = [];
        $this->etapasDisponiveis = Etapa::orderBy('id')->pluck('nome', 'id')->toArray();
        $this->etapaPadrao = $this->resolveEtapaPadraoId();

        $this->estadosDisponiveis = Estado::query()
            ->orderBy('nome')
            ->get()
            ->map(fn (Estado $estado): array => [
                'id' => $estado->id,
                'label' => $estado->uf ? "{$estado->uf} - {$estado->nome}" : $estado->nome,
                'pais_id' => $estado->pais_id,
                'pais_nome' => $estado->pais?->nome ?? '',
            ])
            ->toArray();
    }

    private function resolveEtapaPadraoId(): ?int
    {
        $etapasPadrao = Etapa::query()
            ->whereIn('nome', ['Inauguração', 'Inaugurada'])
            ->pluck('id', 'nome');

        return $etapasPadrao->get('Inauguração')
            ?? $etapasPadrao->get('Inaugurada')
            ?? 9;
    }

    private function buildRowOverrides(): array
    {
        return collect($this->linhasCorrigir)
            ->mapWithKeys(function (array $row): array {
                return [
                    $row['linha'] => [
                        'projeto_id' => $row['projeto_id'] ?? null,
                        'projeto_label' => $row['projeto_label'] ?? null,
                        'nova_sigla' => $row['nova_sigla'] ?? null,
                        'sigla_antiga' => $row['sigla_antiga'] ?? null,
                        'cnpj_formatado' => $row['cnpj_formatado'] ?? null,
                        'status_cnpj' => $row['status_cnpj'] ?? null,
                        'uf' => $row['uf'] ?? null,
                        'cidade_nome' => $row['cidade_nome'] ?? null,
                        'pais_id' => $row['pais_id'] ?? null,
                        'estado_id' => $row['estado_id'] ?? null,
                        'cidade_id' => $row['cidade_id'] ?? null,
                        'empresa' => $row['empresa'] ?? null,
                        'unidade' => $row['unidade'] ?? null,
                    ],
                ];
            })
            ->toArray();
    }

    private function rowsForConflictReview(): array
    {
        return array_merge(
            $this->linhasPreparadas,
            $this->resolvedCorrectionRows(),
        );
    }

    private function resolvedCorrectionRows(): array
    {
        return collect($this->linhasCorrigir)
            ->map(function (array $row): array {
                $row['resolved'] = $this->isCorrectionResolved($row);

                return $row;
            })
            ->filter(fn (array $row): bool => $row['resolved'])
            ->values()
            ->all();
    }

    private function pendingCorrectionRows(): array
    {
        return collect($this->linhasCorrigir)
            ->map(function (array $row): array {
                $row['resolved'] = $this->isCorrectionResolved($row);

                return $row;
            })
            ->filter(fn (array $row): bool => ! $row['resolved'])
            ->values()
            ->all();
    }

    private function ignoredValidationLines(): array
    {
        if (! $this->ignorarLinhasComErro) {
            return [];
        }

        return collect($this->pendingCorrectionRows())
            ->pluck('linha')
            ->filter(fn (mixed $linha): bool => filled($linha))
            ->map(fn (mixed $linha): int => (int) $linha)
            ->values()
            ->all();
    }

    private function filteredConflictResolutions(): array
    {
        $resolucoesFiltradas = [];

        foreach ($this->resolucoes as $codigo => $campos) {
            $temManter = false;

            foreach ($campos as $decisao) {
                if ($decisao === 'manter') {
                    $temManter = true;
                    break;
                }
            }

            if ($temManter) {
                $resolucoesFiltradas[$codigo] = $campos;
            }
        }

        return $resolucoesFiltradas;
    }

    private function ignoredProjects(): array
    {
        $projetosIgnorados = [];

        foreach ($this->resolucoes as $codigo => $campos) {
            if (in_array('ignorar', $campos, true)) {
                $projetosIgnorados[] = $codigo;
            }
        }

        return $projetosIgnorados;
    }

    private function buildProjetosCorrecaoFromLinhasCorrigir(): array
    {
        $parser = $this->getParser();

        return collect($this->linhasCorrigir)
            ->filter(function (array $linha): bool {
                if (! in_array('Projeto não encontrado', $linha['errors'] ?? [], true)) {
                    return false;
                }

                return filled($this->resolveProjetoCorrecaoCodigo($linha))
                    || filled($this->resolveProjetoCorrecaoNome($linha));
            })
            ->filter(fn (array $linha): bool => $parser->shouldClassifyAsProjetoNaoCriado([
                'nova_sigla' => $this->firstFilledString(
                    $linha['original_nova_sigla'] ?? null,
                    $linha['nova_sigla'] ?? null,
                ),
                'unidade' => $this->resolveProjetoCorrecaoNome($linha),
                'cidade_nome' => $linha['cidade_nome'] ?? null,
                'uf' => $linha['uf'] ?? null,
            ]))
            ->groupBy(fn (array $linha): string => $this->resolveProjetoCorrecaoAgrupamento($linha))
            ->map(function ($linhas, string $grupo): array {
                $linha = $linhas->first();
                $codigo = $this->resolveProjetoCorrecaoCodigo($linha)
                    ?? $this->resolveProjetoCorrecaoNome($linha)
                    ?? $grupo;
                $nome = $this->resolveProjetoCorrecaoNome($linha) ?? $codigo;

                return [
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'sigla' => $codigo,
                    'sigla_antiga' => $this->firstFilledString(
                        $linha['sigla_antiga'] ?? null,
                        $linha['original_sigla_antiga'] ?? null,
                    ),
                    'marca' => $linha['empresa'] ?? '',
                    'uf' => $linha['uf'] ?? '',
                    'estado_id' => $linha['estado_id'] ?? null,
                    'cidade_nome' => $linha['cidade_nome'] ?? '',
                    'cidade_id' => $linha['cidade_id'] ?? null,
                    'pais_id' => $linha['pais_id'] ?? null,
                    'etapa_id' => $this->etapaPadrao,
                    'criado' => Projeto::where('codigo', $codigo)->exists(),
                    'grupo_chave' => $grupo,
                    'linhas_afetadas' => $linhas->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveProjetoCorrecaoCodigo(array $linha): ?string
    {
        return $this->firstMeaningfulString(
            $linha['original_nova_sigla'] ?? null,
            $linha['original_sigla_antiga'] ?? null,
            $linha['nova_sigla'] ?? null,
            $linha['sigla_antiga'] ?? null,
            $linha['unidade'] ?? null,
        );
    }

    private function resolveProjetoCorrecaoNome(array $linha): ?string
    {
        return $this->firstMeaningfulString(
            $linha['unidade'] ?? null,
            $linha['original_nova_sigla'] ?? null,
            $linha['original_sigla_antiga'] ?? null,
            $linha['nova_sigla'] ?? null,
            $linha['sigla_antiga'] ?? null,
        );
    }

    private function resolveProjetoCorrecaoAgrupamento(array $linha): string
    {
        $parts = [
            $this->normalizeProjetoCorrecaoParte($linha['original_nova_sigla'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['original_sigla_antiga'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['nova_sigla'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['sigla_antiga'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['unidade'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['empresa'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['uf'] ?? null),
            $this->normalizeProjetoCorrecaoParte($linha['cidade_nome'] ?? null),
        ];

        return implode('|', $parts);
    }

    private function normalizeProjetoCorrecaoParte(mixed $value): string
    {
        $normalized = trim((string) $value);

        if (! $this->isMeaningfulProjectIdentifier($normalized)) {
            return '—';
        }

        return mb_strtolower($normalized);
    }

    private function searchProjetos(string $search): array
    {
        $like = '%'.$search.'%';

        return Projeto::query()
            ->where(function ($query) use ($like): void {
                $query
                    ->where('nome', 'like', $like)
                    ->orWhere('codigo', 'like', $like)
                    ->orWhere('nova_sigla', 'like', $like)
                    ->orWhere('sigla_antiga', 'like', $like)
                    ->orWhere('marca', 'like', $like);
            })
            ->orderBy('nome')
            ->limit(15)
            ->get()
            ->map(fn (Projeto $projeto): array => [
                'id' => $projeto->id,
                'label' => $this->formatProjetoLabel($projeto),
            ])
            ->toArray();
    }

    private function createProjetoFromSuggestion(array $dados): ?Projeto
    {
        $codigo = trim((string) ($dados['codigo'] ?? ''));

        if ($codigo === '') {
            Notification::make()->title('Código do projeto é obrigatório.')->warning()->send();

            return null;
        }

        if (Projeto::where('codigo', $codigo)->exists()) {
            Notification::make()->title("Projeto {$codigo} já existe.")->info()->send();

            return Projeto::where('codigo', $codigo)->first();
        }

        $estadoId = $dados['estado_id'] ?? null;
        $cidadeNome = trim((string) ($dados['cidade_nome'] ?? ''));
        $cidadeId = $dados['cidade_id'] ?? null;
        $paisId = $dados['pais_id'] ?? null;
        $etapaId = $dados['etapa_id'] ?? null;

        if (! $estadoId) {
            Notification::make()->title('Estado é obrigatório.')->warning()->send();

            return null;
        }

        if (! $cidadeId && $cidadeNome !== '' && $estadoId) {
            $cidade = Cidade::firstOrCreate([
                'estado_id' => $estadoId,
                'nome' => $cidadeNome,
            ]);

            $cidadeId = $cidade->id;
        }

        if (! $cidadeId) {
            Notification::make()->title('Cidade é obrigatória.')->warning()->send();

            return null;
        }

        if (! $paisId) {
            $estado = Estado::find($estadoId);
            $paisId = $estado?->pais_id;
        }

        Projeto::create([
            'codigo' => $codigo,
            'nome' => $dados['nome'] ?? $codigo,
            'sigla' => ($dados['sigla'] ?? '') ?: $codigo,
            'nova_sigla' => ($dados['sigla'] ?? '') ?: $codigo,
            'sigla_antiga' => ($dados['sigla_antiga'] ?? '') ?: null,
            'marca' => ($dados['marca'] ?? '') ?: null,
            'user_id' => Auth::id(),
            'etapa_id' => $etapaId,
            'estado_id' => $estadoId,
            'cidade_id' => $cidadeId,
            'pais_id' => $paisId,
        ]);

        return Projeto::where('codigo', $codigo)->first();
    }

    private function associateProjetoToMatchingLinhas(Projeto $projeto, ?string $grupoChave): void
    {
        foreach ($this->linhasCorrigir as $linhaIndex => $linha) {
            $linhaGrupoChave = $this->resolveProjetoCorrecaoAgrupamento($linha);

            if ($grupoChave !== null && $linhaGrupoChave !== $grupoChave) {
                continue;
            }

            $this->linhasCorrigir[$linhaIndex]['projeto_id'] = $projeto->id;
            $this->linhasCorrigir[$linhaIndex]['projeto_label'] = $this->formatProjetoLabel($projeto);
            $this->linhasCorrigir[$linhaIndex]['resolved'] = $this->isCorrectionResolved($this->linhasCorrigir[$linhaIndex]);
        }
    }

    private function markMatchingProjetosCorrecaoAsCreated(?string $grupoChave): void
    {
        foreach ($this->projetosCorrecao as $index => $projetoCorrecao) {
            if (($projetoCorrecao['grupo_chave'] ?? null) !== $grupoChave) {
                continue;
            }

            $this->projetosCorrecao[$index]['criado'] = true;
        }
    }

    private function syncLinhasCorrigirWithExistingProjetosCorrecao(): void
    {
        foreach ($this->projetosCorrecao as $projetoCorrecao) {
            $codigo = trim((string) ($projetoCorrecao['codigo'] ?? ''));

            if ($codigo === '') {
                continue;
            }

            $projeto = Projeto::query()
                ->where('codigo', $codigo)
                ->first();

            if (! $projeto instanceof Projeto) {
                continue;
            }

            $this->associateProjetoToMatchingLinhas(
                $projeto,
                $projetoCorrecao['grupo_chave'] ?? null,
            );
        }
    }

    private function syncBuscaProjetos(): void
    {
        $this->buscaProjetos = collect($this->linhasCorrigir)
            ->mapWithKeys(function (array $linha, int $index): array {
                return [
                    $index => $linha['projeto_label']
                        ?? $this->firstFilledString(
                            $linha['unidade'] ?? null,
                            $linha['nova_sigla'] ?? null,
                            $linha['sigla_antiga'] ?? null,
                        )
                        ?? '',
                ];
            })
            ->toArray();

        $this->resultadosBuscaProjetos = [];
    }

    private function hasPendingCorrections(): bool
    {
        return $this->pendingCorrectionRows() !== [];
    }

    private function hasBlockingCorrections(): bool
    {
        return $this->hasPendingCorrections() && ! $this->ignorarLinhasComErro;
    }

    private function isCnpjConflictField(string $field): bool
    {
        return in_array($field, ['status_cnpj', 'cnpj', 'cnpj_provisorio'], true);
    }

    public function canRetryProjectErrors(?string $tipo = null): bool
    {
        if ($this->projetosCorrecao === []) {
            return true;
        }

        if ($tipo === null) {
            return collect($this->projetosCorrecao)->every(fn (array $projeto): bool => (bool) ($projeto['criado'] ?? false));
        }

        $codigosRelacionados = collect($this->errosAgrupados[$tipo] ?? [])
            ->pluck('codigo')
            ->filter()
            ->unique();

        if ($codigosRelacionados->isEmpty()) {
            return true;
        }

        return collect($this->projetosCorrecao)
            ->filter(fn (array $projeto): bool => $codigosRelacionados->contains($projeto['codigo'] ?? null))
            ->every(fn (array $projeto): bool => (bool) ($projeto['criado'] ?? false));
    }

    private function buildConflictKey(array $row, ?Projeto $projeto = null): string
    {
        $projetoId = $row['projeto_id'] ?? $projeto?->id;

        if (filled($projetoId)) {
            return 'projeto:'.$projetoId;
        }

        $fallback = $this->firstFilledString(
            $row['nova_sigla'] ?? null,
            $row['sigla_antiga'] ?? null,
            $row['projeto_label'] ?? null,
        ) ?? 'sem-chave';

        return 'codigo:'.$fallback;
    }

    private function firstFilledString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function firstMeaningfulString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);

            if ($this->isMeaningfulProjectIdentifier($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function isMeaningfulProjectIdentifier(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return ! in_array(trim($value), ['', '0', '-', '—'], true);
    }

    private function validateParsedRow(array $row): array
    {
        $errors = [];
        $resolvedProjeto = $this->resolveProjeto($row);
        $estado = $this->resolveEstado($row['uf'] ?? null);
        $cidade = $estado instanceof Estado ? $this->resolveCidade($row['cidade'] ?? null, $estado->id) : null;
        $rawStatus = trim((string) ($row['status_cnpj'] ?? ''));
        $normalizedStatus = $this->valueMapping['status_cnpj'][$rawStatus] ?? $this->normalizeStatus($rawStatus);
        $formattedCnpj = $this->formatCnpj($this->normalizeDigits($row['cnpj'] ?? null));

        if (! $resolvedProjeto instanceof Projeto) {
            $errors[] = 'Projeto não encontrado';
        }

        if ($normalizedStatus === null) {
            $errors[] = 'Status do CNPJ inválido';
        }

        if ($formattedCnpj === null || ! $this->isValidCnpj($formattedCnpj)) {
            $errors[] = 'CNPJ inválido';
        }

        if ($formattedCnpj !== null && $resolvedProjeto instanceof Projeto) {
            $duplicateProjeto = Projeto::query()
                ->where('id', '!=', $resolvedProjeto->id)
                ->where(function ($query) use ($formattedCnpj): void {
                    $query
                        ->where('cnpj', $formattedCnpj)
                        ->orWhere('cnpj_provisorio', $formattedCnpj);
                })
                ->first();

            if ($duplicateProjeto instanceof Projeto) {
                $errors[] = 'CNPJ já vinculado a outro projeto';
            }
        }

        $rowData = [
            'linha' => $row['linha'],
            'projeto_id' => $resolvedProjeto?->id,
            'projeto_label' => $resolvedProjeto ? $this->formatProjetoLabel($resolvedProjeto) : null,
            'nova_sigla' => $this->firstFilledString($row['nova_sigla'] ?? null, $resolvedProjeto?->nova_sigla),
            'sigla_antiga' => $this->firstFilledString($row['sigla_antiga'] ?? null, $resolvedProjeto?->sigla_antiga),
            'cnpj_formatado' => $formattedCnpj,
            'status_cnpj' => $normalizedStatus,
            'uf' => $row['uf'] ?? '',
            'cidade_nome' => $row['cidade'] ?? '',
            'empresa' => $row['empresa'] ?? '',
            'unidade' => $row['unidade'] ?? '',
            'pais_id' => $estado?->pais_id ?? $resolvedProjeto?->pais_id,
            'estado_id' => $estado?->id ?? $resolvedProjeto?->estado_id,
            'cidade_id' => $cidade?->id ?? $resolvedProjeto?->cidade_id,
            'original_nova_sigla' => $row['nova_sigla'] ?? null,
            'original_sigla_antiga' => $row['sigla_antiga'] ?? null,
            'original_uf' => $row['uf'] ?? '',
            'original_cidade_nome' => $row['cidade'] ?? '',
            'original_pais_id' => $estado?->pais_id,
            'original_estado_id' => $estado?->id,
            'original_cidade_id' => $cidade?->id,
            'errors' => $errors,
        ];

        $rowData['resolved'] = $this->isCorrectionResolved($rowData);

        if ($rowData['resolved'] && $errors !== []) {
            $rowData['errors'] = [];
        }

        return $rowData;
    }

    private function isCorrectionResolved(array $row): bool
    {
        return filled($row['projeto_id'] ?? null)
            && filled($row['status_cnpj'] ?? null)
            && filled($row['cnpj_formatado'] ?? null)
            && filled($row['estado_id'] ?? null);
    }

    private function resolveProjeto(array $row): ?Projeto
    {
        $parser = $this->getParser();

        return $parser->resolveProjeto($row);
    }

    private function findUniqueProjeto(string $column, ?string $value): ?Projeto
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0' || $value === '-') {
            return null;
        }

        $projetos = Projeto::query()->where($column, $value)->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function findByNomeAndMarca(?string $nome, ?string $marca): ?Projeto
    {
        $nome = trim((string) $nome);
        $marca = trim((string) $marca);

        if ($nome === '' || $nome === '0' || $nome === '-') {
            return null;
        }

        $query = Projeto::query()->where('nome', $nome);

        if ($marca !== '' && $marca !== '0' && $marca !== '-') {
            $query->where('marca', $marca);
        }

        $projetos = $query->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function resolveEstado(?string $uf): ?Estado
    {
        $uf = trim((string) $uf);

        if ($uf === '') {
            return null;
        }

        $ufUpper = mb_strtoupper($uf);

        return Estado::query()
            ->where('uf', $ufUpper)
            ->orWhereRaw('LOWER(nome) = ?', [mb_strtolower($uf)])
            ->first();
    }

    private function resolveCidade(?string $cidade, int $estadoId): ?Cidade
    {
        $cidade = trim((string) $cidade);

        if ($cidade === '') {
            return null;
        }

        return Cidade::query()
            ->where('estado_id', $estadoId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($cidade)])
            ->first();
    }

    private function normalizeStatus(?string $status): ?string
    {
        $normalized = mb_strtoupper(trim((string) $status));

        return match ($normalized) {
            'CNPJ DEFINITIVO', 'DEFINITIVO' => 'definitivo',
            'CNPJ PROVISORIO', 'CNPJ PROVISÓRIO', 'PROVISORIO', 'PROVISÓRIO' => 'provisorio',
            default => null,
        };
    }

    private function normalizeDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    private function formatCnpj(string $cnpj): ?string
    {
        if (strlen($cnpj) !== 14) {
            return null;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2),
        );
    }

    private function isValidCnpj(string $cnpj): bool
    {
        $digits = preg_replace('/\D/', '', $cnpj) ?? '';

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $calculateDigit = static function (string $base, array $weights): int {
            $sum = 0;

            foreach ($weights as $index => $weight) {
                $sum += ((int) $base[$index]) * $weight;
            }

            $remainder = $sum % 11;

            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        $firstDigit = $calculateDigit(substr($digits, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $calculateDigit(substr($digits, 0, 12).$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $digits[12] === (string) $firstDigit && $digits[13] === (string) $secondDigit;
    }

    private function buildProjetoUpdatePayload(array $dados, bool $persistirCidade = true): array
    {
        $cidadeId = $dados['cidade_id'] ?? null;
        $cidadeNome = trim((string) ($dados['cidade_nome'] ?? ''));
        $estadoId = $dados['estado_id'] ?? null;

        if ($persistirCidade && ! $cidadeId && $cidadeNome !== '' && $estadoId) {
            $cidade = Cidade::firstOrCreate([
                'estado_id' => $estadoId,
                'nome' => $cidadeNome,
            ]);

            $cidadeId = $cidade->id;
        }

        return [
            'nova_sigla' => ($dados['nova_sigla'] ?? '') ?: null,
            'sigla_antiga' => ($dados['sigla_antiga'] ?? '') ?: null,
            'status_cnpj' => ($dados['status_cnpj'] ?? '') ?: null,
            'pais_id' => $dados['pais_id'] ?? null,
            'estado_id' => $estadoId,
            'cidade_id' => $cidadeId,
            'cnpj' => ($dados['status_cnpj'] ?? null) === 'definitivo' ? (($dados['cnpj_formatado'] ?? '') ?: null) : null,
            'cnpj_provisorio' => ($dados['status_cnpj'] ?? null) === 'provisorio' ? (($dados['cnpj_formatado'] ?? '') ?: null) : null,
        ];
    }

    private function carregarPreviewSistema(): void
    {
        $this->previewSistema = [];

        $camposMapeados = array_filter(array_unique(array_values($this->mapping)));

        if ($camposMapeados === []) {
            return;
        }

        $amostra = Projeto::query()->inRandomOrder()->limit(20)->get();

        foreach ($camposMapeados as $campo) {
            $valores = match ($campo) {
                'empresa' => $amostra->pluck('marca'),
                'unidade' => $amostra->pluck('nome'),
                'uf' => $amostra->map(fn (Projeto $projeto) => $projeto->estado?->uf),
                'cidade' => $amostra->map(fn (Projeto $projeto) => $projeto->cidade?->nome),
                default => $amostra->pluck($campo),
            };

            $this->previewSistema[$campo] = $valores
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->take(3)
                ->values()
                ->toArray();
        }
    }

    private function syncImportacaoSelecionada(ImportacaoLog $log): void
    {
        $this->importacaoSelecionada = [
            'id' => $log->id,
            'arquivo' => $log->arquivo_original,
            'status' => $log->status,
            'usuario' => $log->user?->name ?? '-',
            'data' => $log->created_at?->format('d/m/Y H:i'),
            'iniciado_em' => $log->iniciado_em?->format('d/m/Y H:i:s'),
            'finalizado_em' => $log->finalizado_em?->format('d/m/Y H:i:s'),
            'duracao' => $log->iniciado_em && $log->finalizado_em
                ? $log->iniciado_em->diffForHumans($log->finalizado_em, true)
                : null,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     linha: int,
     *     codigo: string|null,
     *     acao: string,
     *     dados: array,
     *     erro: array|null,
     *     unidade: string,
     *     processado_em: string|null
     * }
     */
    private function mapStagingRow(ImportacaoStaging $staging): array
    {
        return [
            'id' => $staging->id,
            'linha' => $staging->linha_planilha,
            'codigo' => $staging->codigo,
            'acao' => $staging->acao,
            'dados' => $staging->dados ?? [],
            'erro' => $staging->erro,
            'unidade' => $staging->dados['unidade'] ?? $staging->dados['nova_sigla'] ?? '-',
            'processado_em' => ($staging->updated_at ?? $staging->created_at)?->format('H:i:s'),
        ];
    }

    private function shouldLoadStagingDetails(?string $status): bool
    {
        return in_array($status, ['pendente', 'processando', 'staged', 'concluido', 'erro', 'descartado'], true);
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     linha: int,
     *     codigo: string|null,
     *     acao: string,
     *     dados: array,
     *     erro: array|null,
     *     unidade: string,
     *     processado_em: string|null
     * }>
     */
    private function getStagingRowsForHistoryFallback(): array
    {
        if ($this->stagingRowsReconstruidas !== [] || ! $this->visualizandoHistorico || ! $this->importacaoLogId) {
            return $this->stagingRowsReconstruidas;
        }

        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog) {
            return [];
        }

        $config = $log->mapeamento_usado ?? [];

        try {
            $rows = isset($config['retry_dados'])
                ? $this->getParser()->prepareRetryRows($config['retry_dados'], $config['row_overrides'] ?? [])
                : $this->rebuildRowsFromSourceFile($config);
        } catch (\Throwable) {
            $this->stagingRowsReconstruidas = [];

            return [];
        }

        $projetosIgnorados = $config['projetos_ignorados'] ?? [];
        $linhasIgnoradas = collect($config['linhas_ignoradas'] ?? [])
            ->map(fn (mixed $linha): int => (int) $linha)
            ->all();
        $errorsByLine = collect($log->erros ?? [])
            ->filter(fn (mixed $erro): bool => is_array($erro) && isset($erro['linha']))
            ->keyBy(fn (array $erro): int => (int) $erro['linha']);

        $this->stagingRowsReconstruidas = $rows
            ->map(function (array $row, int $index) use ($errorsByLine, $linhasIgnoradas, $log, $projetosIgnorados): array {
                $linha = (int) ($row['linha'] ?? ($index + 2));
                $codigo = filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? null);
                $erro = $errorsByLine->get($linha);
                $acao = $this->resolveFallbackAction($row, $erro, $log->status, $projetosIgnorados, $linhasIgnoradas);

                return [
                    'id' => -1 * ($index + 1),
                    'linha' => $linha,
                    'codigo' => $codigo,
                    'acao' => $acao,
                    'dados' => $row,
                    'erro' => $erro,
                    'unidade' => $row['unidade'] ?? $row['nova_sigla'] ?? '-',
                    'processado_em' => $log->finalizado_em?->format('H:i:s'),
                ];
            })
            ->values()
            ->all();

        return $this->stagingRowsReconstruidas;
    }

    private function rebuildRowsFromSourceFile(array $config): Collection
    {
        $tempPath = $this->downloadHistorySourceToTemp();

        try {
            $rows = $this->getParser()->prepareRows(
                $tempPath,
                $config['sheet'] ?? 0,
                $config['columns'] ?? $config,
                $config['headerRow'] ?? null,
                $config['values'] ?? [],
                $config['columnMap'] ?? [],
                $config['row_overrides'] ?? [],
            );

            return collect($this->getParser()->applyConflictResolutions(
                $rows->all(),
                $config['resolucoes'] ?? [],
            ));
        } finally {
            @unlink($tempPath);
        }
    }

    private function downloadHistorySourceToTemp(): string
    {
        $log = ImportacaoLog::find($this->importacaoLogId);

        if (! $log instanceof ImportacaoLog || blank($log->arquivo_path)) {
            throw new \RuntimeException('Arquivo original da importação não está disponível.');
        }

        $ext = pathinfo((string) $log->arquivo_path, PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'import_cnpj_history_').'.'.$ext;
        file_put_contents($tempPath, Storage::disk((string) config('filesystems.media_disk', 'r2'))->get((string) $log->arquivo_path));

        return $tempPath;
    }

    private function resolveFallbackAction(array $row, ?array $erro, string $status, array $projetosIgnorados, array $linhasIgnoradas): string
    {
        if (in_array((int) ($row['linha'] ?? 0), $linhasIgnoradas, true)) {
            return 'ignorar';
        }

        if ($erro !== null) {
            return 'erro';
        }

        $conflictKey = filled($row['projeto_id'] ?? null)
            ? 'projeto:'.$row['projeto_id']
            : 'codigo:'.(filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? 'sem-chave'));

        if (in_array($conflictKey, $projetosIgnorados, true)) {
            return 'ignorar';
        }

        if (blank($row['projeto_id'] ?? null)) {
            return 'erro';
        }

        return $status === 'concluido' ? 'atualizado' : 'atualizar';
    }

    private function formatProjetoLabel(Projeto $projeto): string
    {
        return trim(collect([
            $projeto->nome,
            $projeto->codigo,
            $projeto->nova_sigla,
        ])->filter()->implode(' • '));
    }

    private function downloadToTemp(): string
    {
        $ext = pathinfo((string) $this->arquivoPath, PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'import_cnpj_').'.'.$ext;
        file_put_contents($tempPath, Storage::disk((string) config('filesystems.media_disk', 'r2'))->get((string) $this->arquivoPath));

        return $tempPath;
    }

    private function getParser(): CnpjSpreadsheetParserService
    {
        return app(CnpjSpreadsheetParserService::class);
    }
}
