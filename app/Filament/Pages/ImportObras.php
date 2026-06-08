<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessObraImportJob;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\ImportacaoLog;
use App\Models\ImportacaoStaging;
use App\Models\ImportacaoTemplate;
use App\Models\Obras;
use App\Models\Projeto;
use App\Services\SpreadsheetParserService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use UnitEnum;

class ImportObras extends Page
{
    use HasPageShield;
    use WithFileUploads;

    protected string $view = 'filament.pages.import-obras';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationLabel = 'Importar Planilha';

    protected static ?string $title = 'Importar Planilha';

    protected static ?int $navigationSort = 99;

    public int $currentStep = 1;

    // Step 0 — Tipo de planilha
    public string $tipoPlanilha = 'engenharia';

    // Step 1
    public $arquivo;

    public ?string $arquivoPath = null;

    public ?string $arquivoNome = null;

    // Step 2
    public array $abas = [];

    public ?string $abaSelecionada = null;

    public int $headerRow = 1;

    public array $columnMap = [];

    // Step 3
    public array $headers = [];

    public array $preview = [];

    public array $mapping = [];

    public array $camposDisponiveis = [];

    public array $camposCalculados = [];

    public array $fieldLabels = [];

    public function getFieldLabel(string $campo): string
    {
        return $this->fieldLabels[$campo] ?? $campo;
    }

    public array $previewSistema = [];

    public array $previewPlanilha = [];

    public array $templates = [];

    public ?string $nomeTemplate = null;

    // Step 4 — Mapeamento de valores (enums/status)
    public array $enumMappedFields = [];

    public array $spreadsheetValues = [];

    public array $valueMapping = [];

    public array $systemEnumOptions = [];

    public array $novoValorEnum = [];

    // Step 5 — Validacao
    public array $resumoValidacao = [];

    public array $projetosFaltantes = [];

    public array $projetosAprovados = [];

    public ?int $etapaPadrao = null;

    public array $etapasDisponiveis = [];

    public array $estadosDisponiveis = [];

    public array $statusEtapaMapping = [];

    public array $statusValoresUnicos = [];

    public string $novaEtapaNome = '';

    // Step 5.5 — Conflitos
    public array $conflitos = [];

    public array $resolucoes = [];

    public int $totalConflitos = 0;

    public int $obrasComConflito = 0;

    // Step 6 — Resultado
    public ?int $importacaoLogId = null;

    public array $resultado = [];

    // Step 6 — Staging review
    public array $stagingResumo = [];

    public array $stagingRows = [];

    public int $stagingPage = 1;

    public string $stagingFiltro = 'todos';

    // Step 6 — Resolucao de erros
    public array $errosAgrupados = [];

    public array $projetosCorrecao = [];

    // Historico
    public array $importacoesAnteriores = [];

    public function mount(): void
    {
        $this->loadTemplates();
        $this->loadImportacoesAnteriores();
        $parser = app(SpreadsheetParserService::class);
        $this->camposDisponiveis = $parser->getAvailableFields($this->tipoPlanilha);
        $this->camposCalculados = $parser->getComputedFields();
        $this->fieldLabels = $parser->getFieldLabels();
        $this->etapasDisponiveis = Etapa::orderBy('id')->pluck('nome', 'id')->toArray();
        $this->etapaPadrao = 9;
        $this->estadosDisponiveis = Estado::orderBy('nome')
            ->get()
            ->map(fn ($e) => ['id' => $e->id, 'label' => $e->uf ? "{$e->uf} - {$e->nome}" : $e->nome, 'pais_id' => $e->pais_id, 'pais_nome' => $e->pais?->nome ?? ''])
            ->toArray();
    }

    public function loadImportacoesAnteriores(): void
    {
        $this->importacoesAnteriores = ImportacaoLog::where('modulo', 'obras')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ImportacaoLog $log) => [
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

    public function loadTemplates(): void
    {
        $this->templates = ImportacaoTemplate::where('modulo', 'obras')
            ->orderBy('nome')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'nome' => $t->nome])
            ->toArray();
    }

    public function updatedArquivo(): void
    {
        $this->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:10240'],
        ]);

        $nome = Str::slug(pathinfo($this->arquivo->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = $this->arquivo->getClientOriginalExtension();
        $path = 'importacoes/'.$nome.'-'.uniqid().'.'.$ext;

        Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($path, file_get_contents($this->arquivo->getRealPath()));

        $this->arquivoPath = $path;
        $this->arquivoNome = $this->arquivo->getClientOriginalName();
    }

    public function avancarParaAbas(): void
    {
        if (! $this->arquivoPath) {
            Notification::make()->title('Selecione um arquivo.')->warning()->send();

            return;
        }

        $parser = app(SpreadsheetParserService::class);
        $fullPath = $this->downloadToTemp();

        $this->abas = $parser->getSheetNames($fullPath);
        $this->abaSelecionada = $this->abas[0] ?? null;
        $this->currentStep = 2;
    }

    public function avancarParaMapeamento(): void
    {
        if (! $this->abaSelecionada) {
            Notification::make()->title('Selecione uma aba.')->warning()->send();

            return;
        }

        $parser = app(SpreadsheetParserService::class);
        $fullPath = $this->downloadToTemp();

        $analysis = $parser->analyzeSheet($fullPath, $this->abaSelecionada, 5);
        $this->headerRow = $analysis['headerRow'];
        $this->headers = $analysis['headers'];
        $this->preview = $analysis['preview'];
        $this->previewPlanilha = $analysis['sampleValues'] ?? [];
        $this->columnMap = $analysis['columnMap'];
        $this->mapping = $parser->suggestMapping($this->headers, $this->tipoPlanilha);
        $this->camposDisponiveis = $parser->getAvailableFields($this->tipoPlanilha);
        $this->fieldLabels = $parser->getFieldLabels();
        $this->carregarPreviewSistema();
        $this->currentStep = 3;
    }

    public function carregarTemplate(int $templateId): void
    {
        $template = ImportacaoTemplate::find($templateId);
        if (! $template) {
            return;
        }

        $savedMapping = $template->mapeamento;
        foreach ($this->headers as $header) {
            if (isset($savedMapping[$header])) {
                $this->mapping[$header] = $savedMapping[$header];
            }
        }

        Notification::make()->title("Template \"{$template->nome}\" carregado.")->success()->send();
    }

    public function salvarTemplate(): void
    {
        if (empty($this->nomeTemplate)) {
            Notification::make()->title('Informe um nome para o template.')->warning()->send();

            return;
        }

        ImportacaoTemplate::create([
            'nome' => $this->nomeTemplate,
            'modulo' => 'obras',
            'mapeamento' => $this->mapping,
            'user_id' => Auth::id(),
        ]);

        $this->nomeTemplate = null;
        $this->loadTemplates();
        Notification::make()->title('Template salvo.')->success()->send();
    }

    public function avancarParaValores(): void
    {
        $activeMapping = array_filter($this->mapping, fn ($v) => is_string($v) && $v !== '');
        if (empty($activeMapping)) {
            Notification::make()->title('Mapeie pelo menos uma coluna.')->warning()->send();

            return;
        }

        $this->detectEnumFields();
        $this->currentStep = 4;
    }

    public function detectEnumFields(): void
    {
        $parser = app(SpreadsheetParserService::class);
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

            $this->enumMappedFields[] = [
                'header' => $header,
                'field' => $dbField,
            ];
            $enumHeaders[] = $header;
            $headerToField[$header] = $dbField;
            $this->systemEnumOptions[$dbField] = $parser->getEnumOptionsForField($dbField);
        }

        if (empty($enumHeaders)) {
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
            $filtered = array_filter($uniqueValues, fn ($v, $k) => $k !== '' && $k !== null, ARRAY_FILTER_USE_BOTH);
            $this->spreadsheetValues[$dbField] = $filtered;

            foreach (array_keys($filtered) as $spreadsheetVal) {
                $this->valueMapping[$dbField][$spreadsheetVal] =
                    $enumColumns[$dbField][$spreadsheetVal] ?? '';
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

        if (! in_array($novoValor, $this->systemEnumOptions[$field] ?? [])) {
            $this->systemEnumOptions[$field][] = $novoValor;
        }

        $this->valueMapping[$field][$spreadsheetVal] = $novoValor;
        unset($this->novoValorEnum[$field][$spreadsheetVal]);

        Notification::make()->title("Valor \"{$novoValor}\" adicionado.")->success()->send();
    }

    public function avancarParaValidacao(): void
    {
        $parser = app(SpreadsheetParserService::class);
        $fullPath = $this->downloadToTemp();
        $rows = $parser->parseRows($fullPath, $this->abaSelecionada, $this->mapping, $this->headerRow, $this->valueMapping, $this->columnMap);

        $novos = 0;
        $atualizacoes = 0;
        $erros = [];
        $codigosSemProjeto = [];

        foreach ($rows as $index => $row) {
            if (! empty($row['codigo'])) {
                $exists = Obras::where('codigo', $row['codigo'])->exists();
                if ($exists) {
                    $atualizacoes++;
                } else {
                    $novos++;
                    if (! Projeto::where('codigo', $row['codigo'])->exists()) {
                        $codigosSemProjeto[$row['codigo']] = $row;
                    }
                }
            } else {
                $novos++;
            }
        }

        $this->projetosFaltantes = [];
        $this->projetosAprovados = [];
        $statusCount = [];

        foreach ($rows as $row) {
            $statusObra = $row['status'] ?? '';
            if ($statusObra !== '') {
                $statusCount[$statusObra] = ($statusCount[$statusObra] ?? 0) + 1;
            }
        }

        foreach ($codigosSemProjeto as $codigo => $row) {
            $estado = null;
            $cidade = null;
            $paisNome = '';

            if (! empty($row['uf'])) {
                $ufValue = strtoupper(trim($row['uf']));
                $estado = Estado::where('uf', $ufValue)->first();

                if (! $estado) {
                    $ufNomeMap = [
                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
                    ];
                    if (isset($ufNomeMap[$ufValue])) {
                        $estado = Estado::where('nome', $ufNomeMap[$ufValue])->first();
                    }
                    if (! $estado) {
                        $estado = Estado::whereRaw('LOWER(nome) = ?', [mb_strtolower($ufValue)])->first();
                    }
                }

                if ($estado) {
                    $paisNome = $estado->pais?->nome ?? '';
                    if (! empty($row['cidade'])) {
                        $cidade = Cidade::where('estado_id', $estado->id)
                            ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($row['cidade']))])
                            ->first();
                    }
                }
            }

            $statusObra = $row['status'] ?? '';

            $this->projetosFaltantes[] = [
                'codigo' => $codigo,
                'nome' => $row['unidade'] ?? $row['nova_sigla'] ?? $codigo,
                'sigla' => $row['nova_sigla'] ?? $row['sigla'] ?? $codigo,
                'marca' => $row['marca'] ?? null,
                'status' => $statusObra,
                'cidade_nome' => $row['cidade'] ?? '',
                'uf' => $row['uf'] ?? '',
                'estado_id' => $estado?->id,
                'cidade_id' => $cidade?->id,
                'pais_id' => $estado?->pais_id,
                'pais_nome' => $paisNome,
                'resolvido' => $estado !== null,
            ];

            $this->projetosAprovados[] = $estado !== null;
        }

        $this->statusValoresUnicos = $statusCount;

        $etapasLower = collect($this->etapasDisponiveis)
            ->mapWithKeys(fn ($nome, $id) => [mb_strtolower($nome) => $id]);

        $aliasEtapa = [
            'obras' => 'inicio de obra',
            'inaugurada' => 'inauguração',
            'stand-by' => 'stand-by',
        ];

        $this->statusEtapaMapping = [];
        foreach (array_keys($statusCount) as $status) {
            $lower = mb_strtolower($status);
            $etapaId = $etapasLower->get($lower)
                ?? $etapasLower->get($aliasEtapa[$lower] ?? '')
                ?? '';
            $this->statusEtapaMapping[$status] = $etapaId;
        }

        $this->resumoValidacao = [
            'total' => $rows->count(),
            'novos' => $novos,
            'atualizacoes' => $atualizacoes,
            'erros' => count($erros),
            'detalhes' => array_slice($erros, 0, 50),
            'projetos_faltantes' => count($this->projetosFaltantes),
        ];

        $this->detectarConflitos($rows);

        $this->currentStep = 5;
    }

    public function updateMapping(string $header, string $value): void
    {
        $this->mapping[$header] = $value;
    }

    public function carregarPreviewSistema(): void
    {
        $this->previewSistema = [];

        $camposMapeados = array_filter(array_unique(array_values($this->mapping)));
        if (empty($camposMapeados)) {
            return;
        }

        $parser = app(SpreadsheetParserService::class);
        $projetoFieldMap = $parser->getProjetoFieldMap();

        $amostra = Obras::with('projeto')->inRandomOrder()->limit(50)->get();
        if ($amostra->isEmpty()) {
            return;
        }

        foreach ($camposMapeados as $campo) {
            if ($campo === '__calculado__') {
                continue;
            }

            if (isset($projetoFieldMap[$campo])) {
                $dbField = $projetoFieldMap[$campo];
                $valores = $amostra
                    ->map(fn ($obra) => $obra->projeto?->{$dbField})
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->map(fn ($v) => $v instanceof Carbon ? $v->format('d/m/Y') : (string) $v)
                    ->unique()
                    ->take(3)
                    ->values()
                    ->toArray();
            } else {
                $valores = $amostra->pluck($campo)
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->map(fn ($v) => $v instanceof Carbon ? $v->format('d/m/Y') : (string) $v)
                    ->unique()
                    ->take(3)
                    ->values()
                    ->toArray();
            }

            if (! empty($valores)) {
                $this->previewSistema[$campo] = $valores;
            }
        }
    }

    public function detectarConflitos($rows): void
    {
        $this->conflitos = [];
        $this->resolucoes = [];
        $this->totalConflitos = 0;
        $this->obrasComConflito = 0;

        $parser = app(SpreadsheetParserService::class);
        $dateColumns = $parser->getDateColumns();
        $projetoFieldMap = $parser->getProjetoFieldMap();

        foreach ($rows as $row) {
            $codigo = $row['codigo'] ?? null;
            if (! $codigo) {
                continue;
            }

            $existing = Obras::with('projeto')->where('codigo', $codigo)->first();
            if (! $existing) {
                continue;
            }

            $obraConflitos = [];
            foreach ($row as $campo => $valorPlanilha) {
                if (in_array($campo, ['projeto_id', 'codigo'])) {
                    continue;
                }

                if ($valorPlanilha === null || $valorPlanilha === '') {
                    continue;
                }

                if (isset($projetoFieldMap[$campo])) {
                    $dbField = $projetoFieldMap[$campo];
                    $valorBanco = $existing->projeto?->{$dbField};
                } else {
                    $valorBanco = $existing->getAttributes()[$campo] ?? null;
                }

                if ($valorBanco === null || $valorBanco === '') {
                    continue;
                }

                $valorBancoStr = $valorBanco instanceof Carbon
                    ? $valorBanco->format('Y-m-d')
                    : (string) $valorBanco;
                $valorPlanilhaStr = (string) $valorPlanilha;

                if (in_array($campo, $dateColumns)) {
                    $valorBancoStr = $this->normalizarData($valorBancoStr);
                    $valorPlanilhaStr = $this->normalizarData($valorPlanilhaStr);
                }

                if ($valorBancoStr === $valorPlanilhaStr) {
                    continue;
                }

                if (is_numeric($valorBancoStr) && is_numeric($valorPlanilhaStr) && (float) $valorBancoStr === (float) $valorPlanilhaStr) {
                    continue;
                }

                $obraConflitos[] = [
                    'campo' => $campo,
                    'valor_banco' => $valorBanco instanceof Carbon
                        ? $valorBanco->format('d/m/Y')
                        : (string) $valorBanco,
                    'valor_planilha' => in_array($campo, $dateColumns) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valorPlanilhaStr)
                        ? Carbon::parse($valorPlanilhaStr)->format('d/m/Y')
                        : (string) $valorPlanilha,
                ];
            }

            if (! empty($obraConflitos)) {
                $this->conflitos[$codigo] = [
                    'unidade' => $existing->unidade ?? $existing->sigla ?? $codigo,
                    'campos' => $obraConflitos,
                ];

                foreach ($obraConflitos as $conflito) {
                    $this->resolucoes[$codigo][$conflito['campo']] = 'planilha';
                }

                $this->totalConflitos += count($obraConflitos);
                $this->obrasComConflito++;
            }
        }
    }

    public function resolverTodosConflitos(string $decisao): void
    {
        foreach ($this->resolucoes as $codigo => $campos) {
            foreach ($campos as $campo => $valor) {
                $this->resolucoes[$codigo][$campo] = $decisao;
            }
        }
    }

    public function criarEtapa(): void
    {
        $nome = trim($this->novaEtapaNome);
        if ($nome === '') {
            Notification::make()->title('Informe o nome da etapa.')->warning()->send();

            return;
        }

        $existe = Etapa::whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])->first();
        if ($existe) {
            Notification::make()->title('Etapa ja existe.')->warning()->send();

            return;
        }

        $etapa = Etapa::create(['nome' => $nome]);
        $this->etapasDisponiveis[$etapa->id] = $etapa->nome;
        $this->novaEtapaNome = '';

        Notification::make()->title("Etapa \"{$etapa->nome}\" criada.")->success()->send();
    }

    public function avancarParaConflitos(): void
    {
        if (empty($this->conflitos)) {
            $this->executarImportacao();

            return;
        }

        $this->currentStep = 55;
    }

    private function normalizarData(string $valor): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return $valor;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2})$#', $valor, $m)) {
            $ano = (int) $m[3];
            $ano = $ano > 50 ? 1900 + $ano : 2000 + $ano;

            return sprintf('%04d-%02d-%02d', $ano, (int) $m[2], (int) $m[1]);
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $valor, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return $valor;
    }

    public function executarImportacao(): void
    {
        $activeMapping = array_filter($this->mapping, fn ($v) => is_string($v) && $v !== '');

        $projetosParaCriar = [];
        foreach ($this->projetosFaltantes as $i => $projeto) {
            if (! empty($this->projetosAprovados[$i]) && $projeto['resolvido']) {
                $status = $projeto['status'] ?? '';
                $etapaId = ! empty($this->statusEtapaMapping[$status])
                    ? (int) $this->statusEtapaMapping[$status]
                    : ($this->etapaPadrao ? (int) $this->etapaPadrao : null);
                $projeto['etapa_id'] = $etapaId;
                $projetosParaCriar[] = $projeto;
            }
        }

        $semEtapa = collect($projetosParaCriar)->filter(fn ($p) => empty($p['etapa_id']));
        if ($semEtapa->isNotEmpty()) {
            Notification::make()
                ->title('Existem projetos sem etapa definida.')
                ->body('Mapeie todos os status a uma etapa ou defina uma etapa padrao.')
                ->warning()
                ->send();

            return;
        }

        $resolucoesFiltradas = [];
        foreach ($this->resolucoes as $codigo => $campos) {
            $temManter = false;
            foreach ($campos as $campo => $decisao) {
                if ($decisao === 'manter') {
                    $temManter = true;
                    break;
                }
            }
            if ($temManter) {
                $resolucoesFiltradas[$codigo] = $campos;
            }
        }

        $obrasIgnoradas = [];
        foreach ($this->resolucoes as $codigo => $campos) {
            if (in_array('ignorar', $campos)) {
                $obrasIgnoradas[] = $codigo;
            }
        }

        $log = ImportacaoLog::create([
            'arquivo_original' => $this->arquivoNome,
            'arquivo_path' => $this->arquivoPath,
            'modulo' => 'obras',
            'status' => 'pendente',
            'total_linhas' => $this->resumoValidacao['total'] ?? 0,
            'mapeamento_usado' => [
                'columns' => $activeMapping,
                'values' => $this->valueMapping,
                'headerRow' => $this->headerRow,
                'sheet' => $this->abaSelecionada,
                'columnMap' => $this->columnMap,
                'projetos_criar' => $projetosParaCriar,
                'resolucoes' => $resolucoesFiltradas,
                'obras_ignoradas' => $obrasIgnoradas,
                'tipo_planilha' => $this->tipoPlanilha,
            ],
            'user_id' => Auth::id(),
        ]);

        $this->importacaoLogId = $log->id;

        ProcessObraImportJob::dispatch($log->id, Auth::id());

        Notification::make()
            ->title('Importacao iniciada')
            ->body('O processamento esta sendo feito em segundo plano. Voce sera notificado ao concluir.')
            ->success()
            ->send();

        $this->currentStep = 6;
        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->resultado = [
            'status' => 'processando',
            'mensagem' => 'A importacao esta sendo processada em segundo plano.',
        ];
    }

    public function verificarStatus(): void
    {
        if (! $this->importacaoLogId) {
            return;
        }

        $log = ImportacaoLog::find($this->importacaoLogId);
        if (! $log) {
            return;
        }

        $processados = $log->linhas_criadas + $log->linhas_atualizadas + $log->linhas_erro;
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

        if ($log->status === 'staged') {
            $this->carregarStagingResumo();
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

        $this->stagingResumo = [
            'criar' => $counts['criar'] ?? 0,
            'atualizar' => $counts['atualizar'] ?? 0,
            'erro' => $counts['erro'] ?? 0,
            'ignorar' => $counts['ignorar'] ?? 0,
            'total' => array_sum($counts),
        ];

        $this->carregarStagingRows();

        if (($counts['erro'] ?? 0) > 0 && empty($this->errosAgrupados)) {
            $this->carregarErrosStagingResolver();
        }
    }

    public function carregarStagingRows(): void
    {
        $query = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId);

        if ($this->stagingFiltro !== 'todos') {
            $query->where('acao', $this->stagingFiltro);
        }

        $this->stagingRows = $query->orderBy('linha_planilha')
            ->limit(50)
            ->offset(($this->stagingPage - 1) * 50)
            ->get()
            ->map(fn (ImportacaoStaging $s) => [
                'id' => $s->id,
                'linha' => $s->linha_planilha,
                'codigo' => $s->codigo,
                'acao' => $s->acao,
                'dados' => $s->dados,
                'erro' => $s->erro,
                'unidade' => $s->dados['unidade'] ?? $s->dados['obra']['unidade'] ?? $s->dados['nova_sigla'] ?? $s->dados['obra']['nova_sigla'] ?? '-',
            ])
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
        $log = ImportacaoLog::find($this->importacaoLogId);
        if (! $log || $log->status !== 'staged') {
            Notification::make()->title('Importacao nao esta no estado correto.')->danger()->send();

            return;
        }

        $log->update(['status' => 'confirmando']);

        $criadas = 0;
        $atualizadas = 0;
        $erros = [];

        try {
            DB::transaction(function () use ($log, &$criadas, &$atualizadas, &$erros) {
                $stagingRows = ImportacaoStaging::where('importacao_log_id', $log->id)
                    ->whereIn('acao', ['criar', 'atualizar'])
                    ->orderBy('linha_planilha')
                    ->get();

                foreach ($stagingRows as $staging) {
                    try {
                        $obraData = $staging->dados['obra'] ?? $staging->dados;
                        $projetoData = $staging->dados['projeto'] ?? [];

                        if ($staging->acao === 'criar') {
                            $obra = Obras::create($obraData);
                            $obra->users()->attach($log->user_id);
                            if (! empty($projetoData) && $obra->projeto) {
                                $obra->projeto->update($projetoData);
                            }
                            $criadas++;
                        } elseif ($staging->acao === 'atualizar') {
                            $existing = Obras::find($staging->obra_existente_id);
                            if ($existing) {
                                $existing->update($obraData);
                                if (! empty($projetoData) && $existing->projeto) {
                                    $existing->projeto->update($projetoData);
                                }
                                $existing->users()->syncWithoutDetaching([$log->user_id]);
                                $atualizadas++;
                            }
                        }
                    } catch (\Exception $e) {
                        $erros[] = [
                            'linha' => $staging->linha_planilha,
                            'msg' => Str::limit($e->getMessage(), 200),
                            'tipo' => 'outro',
                        ];
                        $staging->update(['acao' => 'erro', 'erro' => [
                            'msg' => Str::limit($e->getMessage(), 200),
                            'tipo' => 'outro',
                        ]]);
                    }
                }
            });
        } catch (\Exception $e) {
            $log->update([
                'status' => 'staged',
                'erros' => array_merge($log->erros ?? [], $erros),
            ]);

            Notification::make()
                ->title('Erro ao confirmar importacao')
                ->body(Str::limit($e->getMessage(), 200))
                ->danger()
                ->send();

            $this->carregarStagingResumo();

            return;
        }

        ImportacaoStaging::where('importacao_log_id', $log->id)->delete();

        $log->update([
            'status' => 'concluido',
            'linhas_criadas' => $criadas,
            'linhas_atualizadas' => $atualizadas,
            'linhas_erro' => count($log->erros ?? []),
            'finalizado_em' => now(),
        ]);

        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->resultado = [
            'status' => 'concluido',
            'total' => $log->total_linhas,
            'criados' => $criadas,
            'atualizados' => $atualizadas,
            'erros' => count($log->erros ?? []),
        ];

        if (! empty($log->erros)) {
            $this->carregarErrosResolver();
        }

        Notification::make()
            ->title('Importacao confirmada')
            ->body("{$criadas} criados, {$atualizadas} atualizados.")
            ->success()
            ->send();
    }

    public function descartarImportacao(): void
    {
        $log = ImportacaoLog::find($this->importacaoLogId);
        if (! $log) {
            return;
        }

        ImportacaoStaging::where('importacao_log_id', $log->id)->delete();
        $log->update(['status' => 'descartado', 'finalizado_em' => now()]);

        Notification::make()->title('Importacao descartada.')->info()->send();
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
            'outro' => array_values(array_filter($erros, fn ($e) => ! in_array($e['tipo'] ?? '', ['projeto_nao_criado', 'projeto_nao_encontrado']))),
        ];

        $codigosUnicos = [];
        foreach ($this->errosAgrupados['projeto_nao_encontrado'] as $erro) {
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

            $this->projetosCorrecao[] = [
                'codigo' => $codigo,
                'nome' => $dados['unidade'] ?? $dados['nova_sigla'] ?? $codigo,
                'sigla' => $dados['nova_sigla'] ?? $dados['sigla'] ?? $codigo,
                'marca' => $dados['marca'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'estado_id' => $estadoId,
                'cidade_nome' => $dados['cidade'] ?? '',
                'cidade_id' => null,
                'pais_id' => $paisId,
                'etapa_id' => $this->etapaPadrao ?? null,
                'status' => $dados['status'] ?? '',
                'criado' => Projeto::where('codigo', $codigo)->exists(),
            ];
        }
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
            'outro' => array_values(array_filter($erros, fn ($e) => ! in_array($e['tipo'] ?? '', ['projeto_nao_criado', 'projeto_nao_encontrado']))),
        ];

        $codigosUnicos = [];
        foreach ($this->errosAgrupados['projeto_nao_encontrado'] as $erro) {
            $codigo = $erro['codigo'] ?? '';
            if ($codigo !== '' && $codigo !== '-' && ! isset($codigosUnicos[$codigo])) {
                $codigosUnicos[$codigo] = $erro;
            }
        }

        $this->projetosCorrecao = [];
        foreach ($this->errosAgrupados['projeto_nao_criado'] as $erro) {
            $dados = $erro['dados_projeto'] ?? [];
            $codigo = $dados['codigo'] ?? $erro['codigo'] ?? '';
            unset($codigosUnicos[$codigo]);

            $this->projetosCorrecao[] = [
                'codigo' => $codigo,
                'nome' => $dados['nome'] ?? '',
                'sigla' => $dados['sigla'] ?? '',
                'marca' => $dados['marca'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'estado_id' => $dados['estado_id'] ?? null,
                'cidade_nome' => $dados['cidade_nome'] ?? '',
                'cidade_id' => $dados['cidade_id'] ?? null,
                'pais_id' => $dados['pais_id'] ?? null,
                'etapa_id' => $dados['etapa_id'] ?? ($this->etapaPadrao ?? null),
                'status' => $dados['status'] ?? '',
                'criado' => Projeto::where('codigo', $codigo)->exists(),
            ];
        }

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

            $this->projetosCorrecao[] = [
                'codigo' => $codigo,
                'nome' => $dados['unidade'] ?? $dados['nova_sigla'] ?? $codigo,
                'sigla' => $dados['nova_sigla'] ?? $dados['sigla'] ?? $codigo,
                'marca' => $dados['marca'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'estado_id' => $estadoId,
                'cidade_nome' => $dados['cidade'] ?? '',
                'cidade_id' => null,
                'pais_id' => $paisId,
                'etapa_id' => $this->etapaPadrao ?? null,
                'status' => $dados['status'] ?? '',
                'criado' => Projeto::where('codigo', $codigo)->exists(),
            ];
        }
    }

    public function criarProjetoCorrecao(int $index): void
    {
        if (! isset($this->projetosCorrecao[$index])) {
            return;
        }

        $dados = $this->projetosCorrecao[$index];
        $codigo = $dados['codigo'];

        if (Projeto::where('codigo', $codigo)->exists()) {
            $this->projetosCorrecao[$index]['criado'] = true;
            Notification::make()->title("Projeto {$codigo} ja existe.")->info()->send();

            return;
        }

        $estadoId = $dados['estado_id'];
        $cidadeNome = trim($dados['cidade_nome']);
        $cidadeId = $dados['cidade_id'];
        $paisId = $dados['pais_id'];
        $etapaId = $dados['etapa_id'];

        if (! $estadoId) {
            Notification::make()->title('Estado e obrigatorio.')->warning()->send();

            return;
        }

        if (! $cidadeId && $cidadeNome && $estadoId) {
            $cidade = Cidade::firstOrCreate(
                ['estado_id' => $estadoId, 'nome' => $cidadeNome],
            );
            $cidadeId = $cidade->id;
        }

        if (! $cidadeId) {
            Notification::make()->title('Cidade e obrigatoria.')->warning()->send();

            return;
        }

        if (! $paisId) {
            $estado = Estado::find($estadoId);
            $paisId = $estado?->pais_id;
        }

        Projeto::create([
            'codigo' => $codigo,
            'nome' => $dados['nome'],
            'sigla' => $dados['sigla'],
            'marca' => $dados['marca'] ?: null,
            'user_id' => Auth::id(),
            'etapa_id' => $etapaId,
            'estado_id' => $estadoId,
            'cidade_id' => $cidadeId,
            'pais_id' => $paisId,
        ]);

        $this->projetosCorrecao[$index]['criado'] = true;
        Notification::make()->title("Projeto {$codigo} criado.")->success()->send();
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

    public function atualizarEstadoCorrecao(int $index, ?int $estadoId): void
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

    public function reimportarComErros(): void
    {
        $dadosRetry = [];

        $erroRows = ImportacaoStaging::where('importacao_log_id', $this->importacaoLogId)
            ->where('acao', 'erro')
            ->get();

        foreach ($erroRows as $staging) {
            if (! empty($staging->dados)) {
                $dadosRetry[] = $staging->dados;
            }
        }

        if (empty($dadosRetry)) {
            foreach ($this->errosAgrupados['projeto_nao_encontrado'] ?? [] as $erro) {
                if (! empty($erro['dados'])) {
                    $dadosRetry[] = $erro['dados'];
                }
            }
        }

        if (empty($dadosRetry)) {
            Notification::make()->title('Nenhuma linha para reimportar.')->info()->send();

            return;
        }

        $log = ImportacaoLog::find($this->importacaoLogId);

        ImportacaoStaging::where('importacao_log_id', $log->id)->where('acao', 'erro')->delete();

        $retryLog = ImportacaoLog::create([
            'arquivo_original' => ($log->arquivo_original ?? 'retry').' (retry)',
            'arquivo_path' => $log->arquivo_path ?? '',
            'modulo' => 'obras',
            'status' => 'pendente',
            'total_linhas' => count($dadosRetry),
            'mapeamento_usado' => [
                'retry_dados' => $dadosRetry,
                'retry_de' => $log->id,
            ],
            'user_id' => Auth::id(),
        ]);

        ProcessObraImportJob::dispatch($retryLog->id, Auth::id());

        $this->importacaoLogId = $retryLog->id;
        $this->errosAgrupados = [];
        $this->projetosCorrecao = [];
        $this->stagingResumo = [];
        $this->stagingRows = [];
        $this->resultado = [
            'status' => 'processando',
            'mensagem' => 'Reimportacao em andamento...',
        ];

        Notification::make()
            ->title('Reimportacao iniciada')
            ->body(count($dadosRetry).' linhas sendo reprocessadas.')
            ->success()
            ->send();
    }

    public function atualizarEstadoProjeto(int $index, ?int $estadoId): void
    {
        if (! isset($this->projetosFaltantes[$index])) {
            return;
        }

        if (! $estadoId) {
            $this->projetosFaltantes[$index]['estado_id'] = null;
            $this->projetosFaltantes[$index]['pais_id'] = null;
            $this->projetosFaltantes[$index]['pais_nome'] = '';
            $this->projetosFaltantes[$index]['cidade_id'] = null;
            $this->projetosFaltantes[$index]['resolvido'] = false;
            $this->projetosAprovados[$index] = false;

            return;
        }

        $estado = Estado::with('pais')->find($estadoId);
        if (! $estado) {
            return;
        }

        $this->projetosFaltantes[$index]['estado_id'] = $estado->id;
        $this->projetosFaltantes[$index]['pais_id'] = $estado->pais_id;
        $this->projetosFaltantes[$index]['pais_nome'] = $estado->pais?->nome ?? '';
        $this->projetosFaltantes[$index]['uf'] = $estado->uf ?? $estado->nome;
        $this->projetosFaltantes[$index]['resolvido'] = true;

        $cidadeNome = $this->projetosFaltantes[$index]['cidade_nome'] ?? '';
        if ($cidadeNome) {
            $cidade = Cidade::where('estado_id', $estado->id)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($cidadeNome))])
                ->first();
            $this->projetosFaltantes[$index]['cidade_id'] = $cidade?->id;
        }

        $this->projetosAprovados[$index] = true;
    }

    public function novaImportacao(): void
    {
        $this->reset([
            'currentStep', 'arquivo', 'arquivoPath', 'arquivoNome',
            'abas', 'abaSelecionada', 'headers', 'preview', 'previewPlanilha', 'mapping', 'columnMap',
            'resumoValidacao', 'importacaoLogId', 'resultado', 'nomeTemplate',
            'enumMappedFields', 'spreadsheetValues', 'valueMapping', 'systemEnumOptions',
            'projetosFaltantes', 'projetosAprovados',
            'statusEtapaMapping', 'statusValoresUnicos',
            'errosAgrupados', 'projetosCorrecao',
            'conflitos', 'resolucoes', 'totalConflitos', 'obrasComConflito',
            'tipoPlanilha', 'previewSistema',
            'stagingResumo', 'stagingRows', 'stagingPage', 'stagingFiltro',
        ]);
        $this->currentStep = 1;
        $this->etapaPadrao = 9;
        $this->tipoPlanilha = 'engenharia';
        $this->loadImportacoesAnteriores();
    }

    public function voltarStep(): void
    {
        if ($this->currentStep === 55) {
            $this->currentStep = 5;
        } elseif ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    private function downloadToTemp(): string
    {
        $ext = pathinfo($this->arquivoPath, PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'import_').'.'.$ext;
        file_put_contents($tempPath, Storage::disk((string) config('filesystems.media_disk', 'r2'))->get($this->arquivoPath));

        return $tempPath;
    }
}
