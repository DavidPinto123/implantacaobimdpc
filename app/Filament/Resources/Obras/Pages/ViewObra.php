<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Enums\TipoUnidade;
use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Filament\Resources\Obras\ObrasResource;
use App\Models\ColunaPersonalizada;
use App\Models\Construtora;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\Midia;
use App\Models\ObraDocumento;
use App\Models\ObraEntregaContratual;
use App\Models\ObraRecebimento;
use App\Models\Obras;
use App\Models\RelatorioFotografico;
use App\Models\RelatorioVisitaTecnica;
use App\Models\Status;
use App\Models\User;
use App\Services\ConstructinService;
use App\Services\ObraDocumentoSyncService;
use App\Services\RelatorioFotograficoPdfService;
use App\Support\ImageUploadHelper;
use App\Support\ImageVariantUrl;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ViewObra extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = ObrasResource::class;

    protected string $view = 'filament.pages.obras.view-obra';

    /**
     * @deprecated Use ObraRecebimento::ITENS_PADRAO. Mantido por compatibilidade.
     */
    public const ITENS_PADRAO_RECEBIMENTO = ObraRecebimento::ITENS_PADRAO;

    public const DOCUMENTOS_PADRAO_NOMES = [
        'Relatório de Visita Técnica',
        'Relatório Fotográfico de posse do imóvel',
        'CNPJ (definitivo)',
        'Reunião kickoff',
        'Email Início de obra',
        'Ata Reunião de Start de Obra',
        'Seguro de obra',
        'ART de Execução Civil',
        'ART de Execução Elétrica',
        'ART de Execução Hidráulica',
        'ART de Execução Incêndio',
        'ART de Execução Gás',
        'ART de Execução Ar cond.',
        'ART de Execução Elevador',
        'ART de Execução Estrutural',
        'Checklist para PRÉ Implantação',
        'Cronograma de Implantação',
        'Email Convocação Checklist MANUTENÇÃO',
        'Checklist de Manutenção',
        'Manual da Obra',
        'QRCOD',
        'Passagem Contas de Consumo',
        'Relatório de finalização do Checklist Manutenção',
    ];

    public const DOCUMENTOS_CATEGORIAS = [
        'Relatório de Visita Técnica' => 'automatico',
        'Relatório Fotográfico de posse do imóvel' => 'automatico',
        'CNPJ (definitivo)' => 'automatico',

        'ART de Execução Civil' => 'construtora',
        'ART de Execução Elétrica' => 'construtora',
        'ART de Execução Hidráulica' => 'construtora',
        'ART de Execução Incêndio' => 'construtora',
        'ART de Execução Gás' => 'construtora',
        'ART de Execução Ar cond.' => 'construtora',
        'ART de Execução Elevador' => 'construtora',
        'ART de Execução Estrutural' => 'construtora',

        'Reunião kickoff' => 'manual',
        'Email Início de obra' => 'manual',
        'Ata Reunião de Start de Obra' => 'manual',
        'Seguro de obra' => 'manual',
        'Checklist para PRÉ Implantação' => 'manual',
        'Cronograma de Implantação' => 'manual',
        'Email Convocação Checklist MANUTENÇÃO' => 'manual',
        'Checklist de Manutenção' => 'manual',
        'Manual da Obra' => 'manual',
        'QRCOD' => 'manual',
        'Passagem Contas de Consumo' => 'manual',
        'Relatório de finalização do Checklist Manutenção' => 'manual',
    ];

    public array $constructinFotos = [];

    public array $constructinVisi = [];

    public array $constructinProgress = [
        'percentual_obra' => null,
        'percentual_obra_executado' => null,
        'referencia' => null,
    ];

    // Modal — Contas de Consumo
    public bool $modalConsumoOpen = false;

    public string $consumoEnergia = '';

    public string $consumoAgua = '';

    public string $consumoGas = '';

    public string $consumoEnergiaObservacoes = '';

    public string $consumoAguaObservacoes = '';

    public string $consumoGasObservacoes = '';

    public array $consumoUploadEnergia = [];

    public array $consumoUploadAgua = [];

    public array $consumoUploadGas = [];

    public array $consumoUploadEnergiaBuffer = [];

    public array $consumoUploadAguaBuffer = [];

    public array $consumoUploadGasBuffer = [];

    public int $consumoUploadVersion = 1;

    public array $consumoDocumentosEnergia = [];

    public array $consumoDocumentosAgua = [];

    public array $consumoDocumentosGas = [];

    // Modal — Detalhe do Item de Controle de Medição (somente leitura)
    public bool $modalDetalheItemOpen = false;

    public array $detalheItemDados = [];

    // Modal — Anexos de um documento (visualização rápida no card resumo)
    public bool $modalAnexosDocOpen = false;

    public ?int $modalAnexosDocId = null;

    // Modal — Documentos
    public bool $modalDocumentosOpen = false;

    public string $novoDocNome = '';

    public string $novoDocStatus = 'pendente';

    public array $documentosUpload = [];

    public int $documentosUploadVersion = 1;

    /**
     * Upload temporário por documento (chave = doc_id). Recebe arquivos do <input type="file">.
     * A cada change, os arquivos são movidos para $documentosUploadBufferPorDoc e o input é zerado.
     *
     * @var array<int, mixed>
     */
    public array $documentosUploadPorDoc = [];

    /**
     * Buffer acumulado de arquivos pendentes de envio por documento (permite anexar em vários
     * batches sem perder a seleção anterior).
     *
     * @var array<int, list<TemporaryUploadedFile>>
     */
    public array $documentosUploadBufferPorDoc = [];

    /**
     * Versão do wire:key dos inputs de upload, usada para forçar reset após processar.
     */
    public int $documentosUploadInputVersion = 1;

    public ?int $novoDocConstrutoraId = null;

    public bool $novoDocAtribuirFornecedor = false;

    /**
     * Buffer de atribuição de fornecedor aos documentos persistidos (commit ao clicar em Salvar).
     * Chave = id do documento, valor = construtora_id escolhida (ou null).
     *
     * @var array<int, int|null>
     */
    public array $documentosConstrutoraEdit = [];

    /**
     * Buffer de atribuição de fornecedor aos documentos VIRTUAIS (ainda não persistidos).
     * Chave = nome canônico do documento, valor = construtora_id escolhida (ou null).
     * Ao salvar, registros são criados para os itens com fornecedor atribuído.
     *
     * @var array<string, int|null>
     */
    public array $documentosVirtuaisConstrutoraEdit = [];

    /**
     * Nomes de documentos virtuais cujo select de atribuição foi revelado via "+ Atribuir".
     *
     * @var array<int, string>
     */
    public array $documentosVirtuaisAtribuirAbertos = [];

    /**
     * IDs dos documentos manuais cujo select de atribuição foi revelado via
     * botão "Atribuir a um fornecedor?".
     *
     * @var array<int, int>
     */
    public array $documentosAtribuirAbertos = [];

    // Modal — Recebimentos
    public bool $modalRecebimentosOpen = false;

    public string $novoRecNome = '';

    public string $novoRecStatus = 'pendente';

    public ?int $novoRecConstrutoraId = null;

    /**
     * Estado do modal de Recebimentos (buffers — só persistem em salvarRecebimentos()).
     * Chave = nome do item padrão, valor = construtora_id escolhida (ou null).
     *
     * @var array<string, int|null>
     */
    public array $recebimentosPadraoSelecao = [];

    /**
     * Buffer de alterações em recebimentos já criados.
     * Chave = id do recebimento, valor = construtora_id escolhida (ou null).
     *
     * @var array<int, int|null>
     */
    public array $recebimentosConstrutoraEdit = [];

    // Modal — Pontos de Atenção
    public bool $modalPontosAtencaoOpen = false;

    public string $novaColunaPersonalizadaNome = '';

    public string $novaColunaPersonalizadaTipo = 'texto';

    public string $novaColunaPersonalizadaOpcoes = '';

    public array $colunasPersonalizadasValores = [];

    // Modal — Fachada
    public bool $modalFachadaOpen = false;

    public ?string $fachadaDataInstalacao = null;

    public string $fachadaStatus = '';

    public string $fachadaObservacao = '';

    // Modal — Fotos
    public bool $modalFotosOpen = false;

    public ?array $fotosFormData = ['fotosUpload' => []];

    public string $fotoCategoriaSelecionada = 'obra';

    public string $novaFotoCategoria = '';

    public array $fotoCategorias = [];

    public array $galeriaCompleta = [];

    public array $rdosData = [];

    public bool $rdosLoaded = false;

    /** @var array<int, array<string, mixed>> */
    public array $entregaContratualData = [];

    public bool $entregaContratualLoaded = false;

    /** @var array<int, int> Timestamp para forçar recriação de inputs */
    public array $entregaContratualRefresh = [];

    /** @var array<int, array<string, mixed>> */
    public array $pedidosRetrofitData = [];

    public bool $pedidosRetrofitLoaded = false;

    public array $rdosDetalhes = [];

    public array $rdosDetalhesCarregando = [];

    public bool $modalRdoOpen = false;

    public ?int $modalRdoId = null;

    public ?array $modalRdoData = null;

    public int $rdosPage = 1;

    public int $rdosPerPage = 10;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->sincronizarDocumentosAutomaticos();
        $this->sincronizarDocumentosContratados();
        $this->refreshDocumentosRelation();
        $this->refreshRecebimentosRelation();

        $service = new ConstructinService;
        $projectId = $this->record->constructin_project_id;

        if (! $projectId && $this->record->projeto_id) {
            $novaSigla = $this->record->projeto?->nova_sigla;
            if (filled($novaSigla)) {
                try {
                    $projectId = $service->findProjectByNovaSigla($novaSigla);
                    if ($projectId) {
                        $this->record->update(['constructin_project_id' => $projectId]);
                        $this->record->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Auto-mapping Constructin falhou', [
                        'obra_id' => $this->record->id,
                        'nova_sigla' => $novaSigla,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($projectId) {
            $this->constructinFotos = $this->prepareConstructinFotos($service->getImages($projectId));
            $this->constructinVisi = $service->getSCurve($projectId);
            $this->constructinProgress = $service->getProgressSnapshot($projectId);
        }

        if ($projectId && ! filled($this->record->link)) {
            $visiLink = "https://visi.constructin.com.br/#/project/{$projectId}";
            $this->record->update(['link' => $visiLink]);
            $this->record->refresh();
        }

        $this->buildGaleriaCompleta();
        $this->fotoCategorias = $this->getCategorias();
    }

    // ── Contas de Consumo ────────────────────────────────────────────────────

    public function abrirModalConsumo(): void
    {
        $this->consumoEnergia = $this->record->energia ?? '';
        $this->consumoAgua = $this->record->agua ?? '';
        $this->consumoGas = $this->record->gas ?? '';
        $this->consumoEnergiaObservacoes = $this->record->energia_observacoes ?? '';
        $this->consumoAguaObservacoes = $this->record->agua_observacoes ?? '';
        $this->consumoGasObservacoes = $this->record->gas_observacoes ?? '';
        $this->consumoUploadEnergia = [];
        $this->consumoUploadAgua = [];
        $this->consumoUploadGas = [];
        $this->consumoUploadEnergiaBuffer = [];
        $this->consumoUploadAguaBuffer = [];
        $this->consumoUploadGasBuffer = [];

        // Carregar documentos existentes
        $this->consumoDocumentosEnergia = $this->record->documentos
            ->where('nome', 'Conta de Energia')
            ->values()
            ->toArray();
        $this->consumoDocumentosAgua = $this->record->documentos
            ->where('nome', 'Conta de Água')
            ->values()
            ->toArray();
        $this->consumoDocumentosGas = $this->record->documentos
            ->where('nome', 'Conta de Gás')
            ->values()
            ->toArray();

        $this->consumoUploadVersion++;
        $this->modalConsumoOpen = true;
    }

    public function salvarConsumo(): void
    {
        $this->validate([
            'consumoUploadEnergia' => ['array'],
            'consumoUploadEnergia.*' => ['file', 'mimes:pdf', 'max:51200'],
            'consumoUploadAgua' => ['array'],
            'consumoUploadAgua.*' => ['file', 'mimes:pdf', 'max:51200'],
            'consumoUploadGas' => ['array'],
            'consumoUploadGas.*' => ['file', 'mimes:pdf', 'max:51200'],
        ], [
            'consumoUploadEnergia.*.mimes' => 'Apenas arquivos PDF são permitidos para energia.',
            'consumoUploadAgua.*.mimes' => 'Apenas arquivos PDF são permitidos para água.',
            'consumoUploadGas.*.mimes' => 'Apenas arquivos PDF são permitidos para gás.',
            'consumoUploadEnergia.*.max' => 'Cada arquivo de energia deve ter no máximo 50MB.',
            'consumoUploadAgua.*.max' => 'Cada arquivo de água deve ter no máximo 50MB.',
            'consumoUploadGas.*.max' => 'Cada arquivo de gás deve ter no máximo 50MB.',
        ]);

        $this->record->update([
            'energia' => $this->consumoEnergia ?: null,
            'agua' => $this->consumoAgua ?: null,
            'gas' => $this->consumoGas ?: null,
            'energia_observacoes' => $this->consumoEnergiaObservacoes ?: null,
            'agua_observacoes' => $this->consumoAguaObservacoes ?: null,
            'gas_observacoes' => $this->consumoGasObservacoes ?: null,
        ]);

        $this->processarArquivosConsumo('Energia', $this->consumoUploadEnergia);
        $this->processarArquivosConsumo('Água', $this->consumoUploadAgua);
        $this->processarArquivosConsumo('Gás', $this->consumoUploadGas);

        $this->consumoUploadEnergia = [];
        $this->consumoUploadAgua = [];
        $this->consumoUploadGas = [];
        $this->consumoUploadEnergiaBuffer = [];
        $this->consumoUploadAguaBuffer = [];
        $this->consumoUploadGasBuffer = [];
        $this->consumoUploadVersion++;

        $this->modalConsumoOpen = false;
        Notification::make()->title('Contas de Consumo atualizadas')->success()->send();
    }

    public function updatedConsumoUploadEnergiaBuffer(): void
    {
        $this->consumoUploadEnergia = array_merge($this->consumoUploadEnergia, $this->consumoUploadEnergiaBuffer);
        $this->consumoUploadEnergiaBuffer = [];
        $this->consumoUploadVersion++;
    }

    public function updatedConsumoUploadAguaBuffer(): void
    {
        $this->consumoUploadAgua = array_merge($this->consumoUploadAgua, $this->consumoUploadAguaBuffer);
        $this->consumoUploadAguaBuffer = [];
        $this->consumoUploadVersion++;
    }

    public function updatedConsumoUploadGasBuffer(): void
    {
        $this->consumoUploadGas = array_merge($this->consumoUploadGas, $this->consumoUploadGasBuffer);
        $this->consumoUploadGasBuffer = [];
        $this->consumoUploadVersion++;
    }

    public function removerArquivoConsumo(string $tipo, int $index): void
    {
        $property = match ($tipo) {
            'Energia' => 'consumoUploadEnergia',
            'Água' => 'consumoUploadAgua',
            'Gás' => 'consumoUploadGas',
            default => null,
        };

        if (! $property || ! isset($this->{$property}[$index])) {
            return;
        }

        array_splice($this->{$property}, $index, 1);
    }

    public function removerDocumentoConsumo(string $tipo, int $documentoId): void
    {
        $documento = ObraDocumento::find($documentoId);

        if ($documento && $documento->obra_id === $this->record->id) {
            $documento->delete();

            // Recarregar documentos
            $this->record->unsetRelation('documentos');
            $this->abrirModalConsumo();

            Notification::make()
                ->title('Documento removido com sucesso')
                ->success()
                ->send();
        }
    }

    public function visualizarDocumentoConsumo(int $documentoId): void
    {
        $documento = ObraDocumento::find($documentoId);

        if ($documento && $documento->obra_id === $this->record->id) {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
            $url = $disk->temporaryUrl($documento->arquivo_path, now()->addMinutes(30));
            $this->redirect($url, navigate: false);
        }
    }

    protected function processarArquivosConsumo(string $tipo, array $arquivos): void
    {
        foreach ($arquivos as $arquivo) {
            if (! $arquivo) {
                continue;
            }

            $nomeOriginal = $arquivo->getClientOriginalName();
            $path = $arquivo->storeAs("obras/{$this->record->id}/consumo/{$tipo}", Str::uuid()->toString().'.'.$arquivo->getClientOriginalExtension(), (string) config('filesystems.media_disk', 'r2'));

            ObraDocumento::create([
                'obra_id' => $this->record->id,
                'nome' => "Conta de {$tipo}",
                'status' => 'enviado',
                'arquivo_path' => $path,
                'arquivo_nome' => $nomeOriginal,
                'usuario_id' => auth()->id(),
            ]);
        }

        $this->record->unsetRelation('documentos');
    }

    // ── Documentos ────────────────────────────────────────────────────────────

    public function abrirModalDocumentos(): void
    {
        if (! $this->canViewDocumentos()) {
            Notification::make()->title('Sem permissao para visualizar documentos')->danger()->send();

            return;
        }

        // Os 3 automáticos (Visita Técnica, Fotográfico, CNPJ) podem ser criados eagerly
        // quando há fonte para sincronizar — os demais só são criados ao atribuir construtora
        // ou ao anexar arquivo (lista virtual no modal).
        $this->sincronizarDocumentosAutomaticos();
        $this->sincronizarCnpjDefinitivo();

        $this->novoDocNome = '';
        $this->novoDocConstrutoraId = null;
        $this->novoDocAtribuirFornecedor = false;
        $this->documentosAtribuirAbertos = [];
        $this->refreshDocumentosRelation();
        $this->inicializarBufferDocumentos();
        $this->modalDocumentosOpen = true;
    }

    /**
     * Lista de documentos exibidos no modal: persistidos + virtuais (padrão ainda não criado).
     * Documentos virtuais têm id negativo (índice da lista canônica) para uso na UI.
     *
     * @return list<array{id: int, nome: string, persistido: bool, doc: ?ObraDocumento}>
     */
    public function getDocumentosExibidosProperty(): array
    {
        $persistidos = $this->record->documentos->keyBy(fn (ObraDocumento $d): string => mb_strtolower(trim((string) $d->nome)));

        $itens = [];

        // Primeiro: ordem canônica (documentos padrão), persistidos OU virtuais.
        foreach (self::DOCUMENTOS_PADRAO_NOMES as $idx => $nome) {
            $key = mb_strtolower(trim($nome));
            $doc = $persistidos->get($key);

            if ($doc instanceof ObraDocumento) {
                $itens[] = [
                    'id' => (int) $doc->id,
                    'nome' => (string) $doc->nome,
                    'persistido' => true,
                    'doc' => $doc,
                ];
                $persistidos->forget($key);
            } else {
                $itens[] = [
                    'id' => -($idx + 1),
                    'nome' => $nome,
                    'persistido' => false,
                    'doc' => null,
                ];
            }
        }

        // Em seguida: documentos extras persistidos (criados manualmente fora da lista canônica).
        foreach ($persistidos as $doc) {
            $itens[] = [
                'id' => (int) $doc->id,
                'nome' => (string) $doc->nome,
                'persistido' => true,
                'doc' => $doc,
            ];
        }

        return $itens;
    }

    /**
     * Popula $documentosConstrutoraEdit a partir do estado atual do banco,
     * pra os selects do modal refletirem o fornecedor atualmente atribuída.
     */
    protected function inicializarBufferDocumentos(): void
    {
        $this->documentosConstrutoraEdit = [];
        foreach ($this->record->documentos as $doc) {
            $this->documentosConstrutoraEdit[(int) $doc->id] = $doc->construtora_id !== null
                ? (int) $doc->construtora_id
                : null;
        }

        $this->documentosVirtuaisConstrutoraEdit = [];
        $this->documentosVirtuaisAtribuirAbertos = [];

        $persistidosNomes = $this->record->documentos
            ->map(fn (ObraDocumento $d): string => mb_strtolower(trim((string) $d->nome)))
            ->all();

        foreach (self::DOCUMENTOS_PADRAO_NOMES as $nome) {
            if (in_array(mb_strtolower(trim($nome)), $persistidosNomes, true)) {
                continue;
            }

            $this->documentosVirtuaisConstrutoraEdit[$nome] = null;
        }
    }

    public function abrirAtribuicaoFornecedorVirtual(string $nome): void
    {
        if (! $this->canManageDocumentos()) {
            return;
        }

        if (! in_array($nome, $this->documentosVirtuaisAtribuirAbertos, true)) {
            $this->documentosVirtuaisAtribuirAbertos[] = $nome;
        }
    }

    /**
     * Atualiza o documento "CNPJ (definitivo)" com base no projeto vinculado:
     *   - status_cnpj = 'definitivo' → documento fica 'enviado' (verde)
     *   - status_cnpj = 'provisorio' → documento fica 'pendente' (amarelo)
     * Também define arquivo_nome = nome do projeto pra exibir na lista.
     */
    protected function sincronizarCnpjDefinitivo(): void
    {
        $projeto = $this->record->projeto;
        if (! $projeto) {
            return;
        }

        $documento = $this->localizarDocumentoPorNome('CNPJ (definitivo)');
        if (! $documento) {
            return;
        }

        $status = $projeto->status_cnpj === 'definitivo' ? 'enviado' : 'pendente';
        $nomeProjeto = (string) ($projeto->nome ?? '');
        $novoArquivoNome = $nomeProjeto !== '' ? $nomeProjeto : $documento->arquivo_nome;

        $precisaAtualizar = $documento->status !== $status
            || ($novoArquivoNome !== null && $documento->arquivo_nome !== $novoArquivoNome);

        if (! $precisaAtualizar) {
            return;
        }

        $documento->update([
            'status' => $status,
            'arquivo_nome' => $novoArquivoNome,
        ]);
    }

    /**
     * Devolve a categoria classificada de um nome de documento.
     * Documentos cadastrados manualmente pelo gestor e não mapeados viram 'manual'.
     */
    public function categoriaDoDocumento(string $nome): string
    {
        return self::DOCUMENTOS_CATEGORIAS[$nome] ?? 'manual';
    }

    /**
     * Revela o select inline de atribuição de fornecedor para um doc manual.
     */
    public function abrirAtribuicaoFornecedor(int $documentoId): void
    {
        if (! $this->canManageDocumentos()) {
            return;
        }

        if (! in_array($documentoId, $this->documentosAtribuirAbertos, true)) {
            $this->documentosAtribuirAbertos[] = $documentoId;
        }
    }

    /**
     * Persiste as atribuições de fornecedor dos documentos e notifica as
     * construtoras impactadas. Igual ao fluxo de Recebimentos (Salvar em lote).
     */
    public function salvarDocumentos(): void
    {
        if (! $this->canManageDocumentos()) {
            Notification::make()->title('Sem permissao para salvar documentos')->danger()->send();

            return;
        }

        $cache = [];
        $cacheGet = function (int $id) use (&$cache): ?\App\Models\Construtora {
            if (! array_key_exists($id, $cache)) {
                $cache[$id] = Construtora::query()->find($id);
            }

            return $cache[$id] instanceof Construtora ? $cache[$id] : null;
        };

        $atualizados = 0;

        foreach ($this->documentosConstrutoraEdit as $docId => $construtoraId) {
            $doc = ObraDocumento::query()
                ->where('id', (int) $docId)
                ->where('obra_id', $this->record->id)
                ->first();

            if (! $doc) {
                continue;
            }

            // Categoria 'automatico' nunca aceita atribuição manual
            if ($this->categoriaDoDocumento((string) $doc->nome) === 'automatico') {
                continue;
            }

            // ARTs vinculadas a escopos contratados no Controle de Medição não podem ser editadas aqui.
            if ($this->isDocumentoArtTrancado((string) $doc->nome)) {
                continue;
            }

            $anterior = $doc->construtora_id !== null ? (int) $doc->construtora_id : null;
            $novo = filled($construtoraId) ? (int) $construtoraId : null;

            if ($anterior === $novo) {
                continue;
            }

            if ($novo !== null) {
                $construtora = $cacheGet($novo);
                if (! $construtora) {
                    continue;
                }

                $doc->update(['construtora_id' => $construtora->id]);
                $this->notificarConstrutoraDocumento($doc->fresh(), $construtora);
            } else {
                $doc->update(['construtora_id' => null]);
            }

            $atualizados++;
        }

        // Criação de documentos virtuais com fornecedor atribuído.
        $criados = 0;
        foreach ($this->documentosVirtuaisConstrutoraEdit as $nome => $construtoraId) {
            $construtoraId = filled($construtoraId) ? (int) $construtoraId : null;
            if ($construtoraId === null) {
                continue;
            }

            // Garante que ainda não foi criado (idempotência).
            $jaExiste = ObraDocumento::query()
                ->where('obra_id', $this->record->id)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($nome))])
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $construtora = $cacheGet($construtoraId);
            if (! $construtora) {
                continue;
            }

            $doc = ObraDocumento::create([
                'obra_id' => $this->record->id,
                'construtora_id' => $construtora->id,
                'nome' => $nome,
                'status' => 'pendente',
                'usuario_id' => auth()->id(),
            ]);

            $this->notificarConstrutoraDocumento($doc->fresh(), $construtora);
            $criados++;
        }

        $this->refreshDocumentosRelation();
        $this->inicializarBufferDocumentos();
        $this->documentosAtribuirAbertos = [];

        if ($atualizados === 0 && $criados === 0) {
            Notification::make()->title('Nada para salvar')->info()->send();

            return;
        }

        $partes = [];
        if ($criados > 0) {
            $partes[] = $criados === 1
                ? '1 documento criado e atribuído.'
                : $criados.' documentos criados e atribuídos.';
        }
        if ($atualizados > 0) {
            $partes[] = $atualizados === 1
                ? '1 fornecedor atualizado.'
                : $atualizados.' fornecedores atualizados.';
        }

        Notification::make()
            ->title('Documentos salvos')
            ->body(implode(' ', $partes))
            ->success()
            ->send();

        $this->modalDocumentosOpen = false;
    }

    /**
     * Notifica os usuários do fornecedor que um documento foi atribuído a ela.
     */
    protected function notificarConstrutoraDocumento(
        ObraDocumento $documento,
        Construtora $construtora
    ): void {
        $usuarios = $construtora->users()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $obraNome = $this->record->projeto?->nome ?? ('Obra #'.$this->record->id);
        $corpo = sprintf(
            'O documento "%s" da obra "%s" foi atribuído à %s para envio.',
            $documento->nome,
            $obraNome,
            $construtora->nome
        );

        $urlItem = ObraDocumentoResource::getUrl('edit', ['record' => $documento->id]);

        Notification::make()
            ->title('Novo documento atribuído')
            ->body($corpo)
            ->icon('heroicon-o-document-text')
            ->warning()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url($urlItem),
            ])
            ->sendToDatabase($usuarios);
    }

    private function sincronizarDocumentosAutomaticos(): void
    {
        $projetoId = $this->record->projeto_id ?? null;

        if (! $projetoId) {
            return;
        }

        $relatorioVt = RelatorioVisitaTecnica::query()
            ->where('projeto_id', $projetoId)
            ->whereNotNull('pdf_path')
            ->orderByDesc('updated_at')
            ->get()
            ->first(function (RelatorioVisitaTecnica $item) {
                return $this->statusNormalizado($item->status) === 'concluido'
                    && filled($item->pdf_path)
                    && Storage::disk((string) config('filesystems.media_disk', 'r2'))->exists($item->pdf_path);
            });

        $relatorioFotografico = RelatorioFotografico::query()
            ->where('projeto_id', $projetoId)
            ->orderByDesc('updated_at')
            ->get()
            ->first(function (RelatorioFotografico $item) {
                $status = $this->statusNormalizado($item->status);

                return in_array($status, ['concluido', 'aprovado_com_pendencia'], true);
            });

        if (! $relatorioVt && ! $relatorioFotografico) {
            return;
        }

        if ($relatorioVt) {
            $this->vincularPdfAutomaticoDocumento(
                nomeAlvo: 'Relatório de Visita Técnica',
                arquivoPath: $relatorioVt->pdf_path,
                arquivoNome: basename((string) $relatorioVt->pdf_path),
            );
        }

        if ($relatorioFotografico) {
            $path = $this->obterOuGerarPdfRelatorioFotografico($relatorioFotografico);

            if ($path) {
                $this->vincularPdfAutomaticoDocumento(
                    nomeAlvo: 'Relatório Fotográfico de posse do imóvel',
                    arquivoPath: $path,
                    arquivoNome: basename($path),
                );
            }
        }
    }

    /**
     * Garante que documentos vinculados aos escopos contratados no Controle de
     * Medição existam na obra, incluindo os casos especiais com mais de um
     * documento por A.S.
     */
    private function sincronizarDocumentosContratados(): void
    {
        $controleNotaFiscalObra = $this->record->controlesNotaFiscal()
            ->with('itens:id,controle_nota_fiscal_id,as_escopo_id,empresa')
            ->get();

        if ($controleNotaFiscalObra->isEmpty()) {
            return;
        }

        $docSync = app(ObraDocumentoSyncService::class);

        foreach ($controleNotaFiscalObra as $controle) {
            foreach ($controle->itens as $item) {
                if (! filled($item->as_escopo_id)) {
                    continue;
                }

                $docSync->syncCreatedFromEscopo(
                    (int) $this->record->id,
                    (int) $item->as_escopo_id,
                    (string) ($item->empresa ?? '')
                );
            }
        }
    }

    private function vincularPdfAutomaticoDocumento(string $nomeAlvo, string $arquivoPath, string $arquivoNome): void
    {
        $documento = $this->localizarDocumentoPorNome($nomeAlvo);

        // Não cria registro automaticamente: o documento só passa a existir quando o gestor
        // clicar em "+ Atribuir à obra" via atribuirDocumentoAutomatico(). Se ainda não foi
        // atribuído, simplesmente ignora a sincronização.
        if (! $documento) {
            return;
        }

        if ($documento->nome !== $nomeAlvo) {
            $documento->update([
                'nome' => $nomeAlvo,
            ]);

            $documento->refresh();
        }

        $jaSincronizado = $documento->arquivo_path === $arquivoPath
            && $documento->status === 'enviado';

        if ($jaSincronizado) {
            return;
        }

        $documento->update([
            'arquivo_path' => $arquivoPath,
            'arquivo_nome' => $arquivoNome,
            'status' => 'enviado',
            'usuario_id' => auth()->id(),
        ]);
    }

    private function localizarDocumentoPorNome(string $nomeAlvo): ?ObraDocumento
    {
        $alias = [
            'Relatório de Visita Técnica' => [
                'Relatório de Visita Técnica',
                'Relatório de VT',
                'Relatorio de VT',
            ],
            'Relatório Fotográfico de posse do imóvel' => [
                'Relatório Fotográfico de posse do imóvel',
                'Relatório Fotográfico de Posse',
                'Relatorio Fotografico de Posse',
            ],
        ];

        $nomesAceitos = collect($alias[$nomeAlvo] ?? [$nomeAlvo])
            ->map(fn (string $nome): string => $this->textoNormalizado($nome))
            ->all();

        return $this->record->documentos()
            ->get()
            ->first(fn (ObraDocumento $doc) => in_array($this->textoNormalizado((string) $doc->nome), $nomesAceitos, true));
    }

    private function pdfPathRelatorioFotografico(int $id): string
    {
        return RelatorioFotograficoPdfService::pdfStoragePath($id);
    }

    private function obterOuGerarPdfRelatorioFotografico(RelatorioFotografico $relatorio): ?string
    {
        $path = $this->pdfPathRelatorioFotografico($relatorio->id);

        if (Storage::disk((string) config('filesystems.media_disk', 'r2'))->exists($path)) {
            return $path;
        }

        try {
            /** @var RelatorioFotograficoPdfService $pdfService */
            $pdfService = app(RelatorioFotograficoPdfService::class);
            $pdf = $pdfService->makePdf($relatorio);

            Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($path, $pdf->output(), [
                'ContentType' => 'application/pdf',
            ]);

            return Storage::disk((string) config('filesystems.media_disk', 'r2'))->exists($path) ? $path : null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar PDF automático do relatório fotográfico para Obra', [
                'obra_id' => $this->record->id,
                'projeto_id' => $this->record->projeto_id,
                'relatorio_fotografico_id' => $relatorio->id,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function statusNormalizado(?string $status): string
    {
        return str_replace(' ', '_', $this->textoNormalizado((string) $status));
    }

    private function textoNormalizado(string $texto): string
    {
        return Str::of($texto)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }

    public function adicionarDocumento(): void
    {
        if (! $this->canManageDocumentos()) {
            Notification::make()->title('Sem permissao para adicionar documentos')->danger()->send();

            return;
        }

        $nome = trim($this->novoDocNome);

        if ($nome === '') {
            return;
        }

        $construtora = null;
        if (filled($this->novoDocConstrutoraId)) {
            $construtora = Construtora::query()->find($this->novoDocConstrutoraId);

            if (! $construtora instanceof Construtora) {
                Notification::make()
                    ->title('Fornecedor inválido')
                    ->body('Selecione um fornecedor cadastrado.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $documento = ObraDocumento::create([
            'obra_id' => $this->record->id,
            'construtora_id' => $construtora?->id,
            'nome' => $nome,
            'status' => 'pendente',
            'usuario_id' => auth()->id(),
        ]);

        if ($construtora) {
            $this->notificarConstrutoraDocumento($documento, $construtora);
        }

        $this->novoDocNome = '';
        $this->novoDocConstrutoraId = null;
        $this->novoDocAtribuirFornecedor = false;
        $this->refreshDocumentosRelation();
        $this->inicializarBufferDocumentos();
    }

    public function removerDocumento(int $id): void
    {
        if (! $this->canManageDocumentos()) {
            Notification::make()->title('Sem permissao para remover documentos')->danger()->send();

            return;
        }

        $documento = ObraDocumento::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $documento) {
            return;
        }

        if ($this->isDocumentoArtTrancado((string) $documento->nome)) {
            Notification::make()
                ->title('Documento vinculado ao Controle de Medição')
                ->body('Para remover esta ART, exclua primeiro o escopo correspondente no Controle de Medição.')
                ->warning()
                ->send();

            return;
        }

        foreach ($documento->arquivos_paths_resolved as $path) {
            /** @var FilesystemAdapter $mediaDisk */
            $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

            if ($mediaDisk->exists($path)) {
                $mediaDisk->delete($path);
            }
        }

        $documento->delete();
        $this->refreshDocumentosRelation();
    }

    /**
     * Hook do Livewire: a cada vez que o usuário escolhe arquivos no input, acumula no buffer
     * e zera o input para permitir nova seleção (incremental, vários batches).
     */
    public function updatedDocumentosUploadPorDoc(mixed $value, string $key): void
    {
        // $key vem como "{docId}" ou "{docId}.{n}" dependendo do estado do array.
        $partes = explode('.', $key);
        $docId = (int) ($partes[0] ?? 0);

        if ($docId <= 0) {
            return;
        }

        $arquivos = $this->documentosUploadPorDoc[$docId] ?? [];
        if (! is_array($arquivos)) {
            $arquivos = [$arquivos];
        }

        if (! isset($this->documentosUploadBufferPorDoc[$docId]) || ! is_array($this->documentosUploadBufferPorDoc[$docId])) {
            $this->documentosUploadBufferPorDoc[$docId] = [];
        }

        foreach ($arquivos as $arquivo) {
            if ($arquivo) {
                $this->documentosUploadBufferPorDoc[$docId][] = $arquivo;
            }
        }

        $this->documentosUploadPorDoc[$docId] = [];
        $this->documentosUploadInputVersion++;
    }

    public function removerArquivoBuffer(int $documentoId, int $index): void
    {
        $buffer = $this->documentosUploadBufferPorDoc[$documentoId] ?? [];

        if (! is_array($buffer) || ! array_key_exists($index, $buffer)) {
            return;
        }

        unset($buffer[$index]);
        $this->documentosUploadBufferPorDoc[$documentoId] = array_values($buffer);
    }

    /**
     * Processa os arquivos pendentes no buffer para um documento e os anexa de fato.
     */
    public function fazerUploadDocumento(int $documentoId): void
    {
        $documento = ObraDocumento::where('id', $documentoId)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $documento instanceof ObraDocumento) {
            return;
        }

        if (! $this->podeAnexarDocumento($documento)) {
            Notification::make()->title('Sem permissão para anexar arquivos')->danger()->send();

            return;
        }

        $arquivos = $this->documentosUploadBufferPorDoc[$documentoId] ?? [];

        if (! is_array($arquivos) || $arquivos === []) {
            Notification::make()->title('Selecione ao menos um PDF antes de enviar')->warning()->send();

            return;
        }

        $this->validate([
            "documentosUploadBufferPorDoc.{$documentoId}.*" => ['file', 'mimes:pdf', 'max:51200'],
        ], [
            "documentosUploadBufferPorDoc.{$documentoId}.*.mimes" => 'Apenas arquivos PDF são permitidos.',
            "documentosUploadBufferPorDoc.{$documentoId}.*.max" => 'Cada arquivo deve ter no máximo 50MB.',
        ]);

        $disk = (string) config('filesystems.media_disk', 'r2');
        $paths = $documento->arquivos_paths_resolved;
        $nomes = $documento->arquivos_nomes_resolved;
        $adicionados = 0;

        foreach ($arquivos as $arquivo) {
            if (! $arquivo) {
                continue;
            }

            $nomeOriginal = $arquivo->getClientOriginalName();
            $extensao = $arquivo->getClientOriginalExtension() ?: 'pdf';
            $path = $arquivo->storeAs(
                "obra-documentos/{$documento->id}",
                Str::uuid()->toString().'.'.$extensao,
                $disk
            );

            $paths[] = $path;
            $nomes[] = $nomeOriginal;
            $adicionados++;
        }

        if ($adicionados === 0) {
            return;
        }

        $documento->update([
            'arquivos_paths' => $paths,
            'arquivos_nomes' => $nomes,
            'status' => 'enviado',
            'usuario_id' => auth()->id(),
        ]);

        $this->documentosUploadBufferPorDoc[$documentoId] = [];
        $this->documentosUploadPorDoc[$documentoId] = [];
        $this->documentosUploadInputVersion++;
        $this->documentosUploadVersion++;
        $this->refreshDocumentosRelation();

        // Notifica o lado oposto:
        //   - Fornecedor subiu => notifica gestores de Obras
        //   - Gestor subiu => notifica o fornecedor atribuído (se houver)
        $usuario = auth()->user();
        if ($usuario instanceof User && $usuario->hasRole('Fornecedor')) {
            $this->notificarGestoresEnvioDocumento($documento->fresh(), $adicionados);
        } else {
            $construtora = $documento->construtora;
            if ($construtora instanceof Construtora) {
                $this->notificarConstrutoraEnvioGestor($documento->fresh(), $construtora, $adicionados);
            }
        }

        Notification::make()
            ->title($adicionados === 1 ? 'Arquivo anexado' : $adicionados.' arquivos anexados')
            ->success()
            ->send();
    }

    public function removerArquivoDocumento(int $documentoId, int $index): void
    {
        $documento = ObraDocumento::where('id', $documentoId)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $documento instanceof ObraDocumento) {
            return;
        }

        if (! $this->podeAnexarDocumento($documento)) {
            Notification::make()->title('Sem permissão para remover arquivos')->danger()->send();

            return;
        }

        $paths = $documento->arquivos_paths_resolved;
        $nomes = $documento->arquivos_nomes_resolved;

        if (! array_key_exists($index, $paths)) {
            return;
        }

        $alvo = $paths[$index];
        unset($paths[$index], $nomes[$index]);
        $paths = array_values($paths);
        $nomes = array_values($nomes);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
        if (filled($alvo) && $disk->exists($alvo)) {
            $disk->delete($alvo);
        }

        $documento->update([
            'arquivos_paths' => $paths,
            'arquivos_nomes' => $nomes,
            'arquivo_path' => null,
            'arquivo_nome' => null,
            'status' => $paths === [] ? 'pendente' : 'enviado',
        ]);

        $this->refreshDocumentosRelation();

        Notification::make()->title('Arquivo removido')->success()->send();
    }

    public function podeAnexarDocumentoBlade(ObraDocumento $documento): bool
    {
        return $this->podeAnexarDocumento($documento);
    }

    /**
     * Retorna o conjunto de nomes de documentos que estão "trancados"
     * porque o escopo correspondente foi contratado no Controle de Medição da obra.
     * Para esses documentos, o fornecedor é definido pelo Controle de NF e não pode
     * ser alterado nem o documento removido pela tela de Envio de Documentos.
     *
     * @return array<int, string> Lista de nomes (lower-case para comparação).
     */
    public function getDocumentosArtTrancadosProperty(): array
    {
        $mapa = ObraDocumentoSyncService::MAPA_AS_DOCUMENTOS;
        $numerosAs = array_keys($mapa);

        if ($numerosAs === []) {
            return [];
        }

        $obraId = (int) $this->record->id;

        $contratados = ControleNotaFiscalItem::query()
            ->whereHas('controleNotaFiscal', fn (Builder $q) => $q->where('obra_id', $obraId))
            ->whereIn('numero_as', $numerosAs)
            ->pluck('numero_as')
            ->unique()
            ->all();

        $nomes = [];
        foreach ($contratados as $numero) {
            if (isset($mapa[$numero])) {
                foreach ($mapa[$numero] as $nomeDoc) {
                    $nomes[] = mb_strtolower($nomeDoc);
                }
            }
        }

        return array_values(array_unique($nomes));
    }

    public function isDocumentoArtTrancado(string $nome): bool
    {
        return in_array(mb_strtolower(trim($nome)), $this->documentosArtTrancados, true);
    }

    /**
     * Permite anexar/remover arquivos para o gestor (Obras) ou para o fornecedor vinculada à obra.
     */
    private function podeAnexarDocumento(ObraDocumento $documento): bool
    {
        if ($this->canManageDocumentos()) {
            return true;
        }

        $usuario = auth()->user();
        if (! $usuario instanceof User || ! $usuario->hasRole('Fornecedor')) {
            return false;
        }

        // O fornecedor só anexa em documentos atribuídos a ela.
        return $documento->construtora_id !== null
            && (int) $documento->construtora_id === (int) $usuario->construtoras_id;
    }

    /**
     * Notifica todos os gestores de Obras quando o fornecedor envia arquivos.
     */
    private function notificarGestoresEnvioDocumento(ObraDocumento $documento, int $quantidade): void
    {
        $gestores = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'Gestor'))
            ->whereHas('setores', fn (Builder $q) => $q->whereRaw('LOWER(setor) = ?', ['obras']))
            ->get();

        if ($gestores->isEmpty()) {
            return;
        }

        $obraNome = $this->record->projeto?->nome ?? ('Obra #'.$this->record->id);
        $construtoraNome = $documento->construtora?->nome ?? 'Fornecedor';

        $titulo = $quantidade === 1
            ? 'Fornecedor enviou um arquivo'
            : 'Fornecedor enviou '.$quantidade.' arquivos';

        $corpo = sprintf(
            '%s anexou ao documento "%s" da obra "%s".',
            $construtoraNome,
            $documento->nome,
            $obraNome
        );

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-document-arrow-up')
            ->success()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url(ObraDocumentoResource::getUrl('edit', ['record' => $documento->id])),
            ])
            ->sendToDatabase($gestores);
    }

    /**
     * Notifica o fornecedor atribuído quando o gestor anexa arquivos.
     */
    private function notificarConstrutoraEnvioGestor(ObraDocumento $documento, Construtora $construtora, int $quantidade): void
    {
        $usuarios = $construtora->users()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $obraNome = $this->record->projeto?->nome ?? ('Obra #'.$this->record->id);

        $titulo = $quantidade === 1
            ? 'Gestor anexou um arquivo'
            : 'Gestor anexou '.$quantidade.' arquivos';

        $corpo = sprintf(
            'O gestor anexou um arquivo ao documento "%s" da obra "%s".',
            $documento->nome,
            $obraNome
        );

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-document-arrow-up')
            ->success()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url(ObraDocumentoResource::getUrl('edit', ['record' => $documento->id])),
            ])
            ->sendToDatabase($usuarios);
    }

    /**
     * Atribui à obra qualquer documento da lista canônica. Cria o registro com status 'pendente'
     * (sem fornecedor) — depois de atribuído, o fornecedor pode ser definido no card persistido.
     * Para documentos automáticos, dispara sincronização com os resources externos.
     */
    public function atribuirDocumento(string $nome): void
    {
        if (! $this->canManageDocumentos()) {
            Notification::make()->title('Sem permissão para atribuir documentos')->danger()->send();

            return;
        }

        $nome = trim($nome);
        if ($nome === '') {
            return;
        }

        $jaExiste = ObraDocumento::query()
            ->where('obra_id', $this->record->id)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->exists();

        if ($jaExiste) {
            $this->refreshDocumentosRelation();

            return;
        }

        ObraDocumento::create([
            'obra_id' => $this->record->id,
            'nome' => $nome,
            'status' => 'pendente',
            'usuario_id' => auth()->id(),
        ]);

        // Para documentos automáticos: roda sincronização para já promover ao status correto
        // se o resource externo estiver pronto.
        if ($this->categoriaDoDocumento($nome) === 'automatico') {
            $this->sincronizarDocumentosAutomaticos();
            $this->sincronizarCnpjDefinitivo();
        }

        $this->refreshDocumentosRelation();
        $this->inicializarBufferDocumentos();

        Notification::make()->title('Documento atribuído à obra')->success()->send();
    }

    public function abrirModalAnexosDocumento(int $id): void
    {
        $documento = ObraDocumento::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $documento instanceof ObraDocumento) {
            return;
        }

        $this->modalAnexosDocId = $id;
        $this->modalAnexosDocOpen = true;
    }

    public function fecharModalAnexosDocumento(): void
    {
        $this->modalAnexosDocOpen = false;
        $this->modalAnexosDocId = null;
    }

    public function abrirArquivoDocumento(int $id, int $index): void
    {
        $documento = ObraDocumento::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $documento) {
            return;
        }

        $paths = $documento->arquivos_paths_resolved;
        $path = $paths[$index] ?? null;

        if (! filled($path)) {
            return;
        }

        $url = null;
        /** @var FilesystemAdapter $mediaDisk */
        $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

        if (Str::startsWith((string) $path, ['http://', 'https://'])) {
            $url = $path;
        } elseif ($mediaDisk->exists($path)) {
            $url = $mediaDisk->temporaryUrl($path, now()->addMinutes(15));
        }

        if (! filled($url)) {
            Notification::make()->title('Arquivo não encontrado para visualização')->warning()->send();

            return;
        }

        $this->js('window.open('.json_encode($url).', "_blank")');
    }

    private function canManageDocumentos(): bool
    {
        return ObraDocumentoResource::canManageAll(auth()->user());
    }

    private function isConstrutoraVinculadaNaObra(?User $user): bool
    {
        if (! $user instanceof User || blank($user->construtoras_id)) {
            return false;
        }

        return $this->record->construtoras()
            ->where('construtoras.id', $user->construtoras_id)
            ->exists();
    }

    private function canViewDocumentos(): bool
    {
        $user = auth()->user();

        return $this->canManageDocumentos()
            || (ObraDocumentoResource::isConstrutoraTerceiros($user) && $this->isConstrutoraVinculadaNaObra($user));
    }

    public function getPodeGerenciarDocumentosProperty(): bool
    {
        return $this->canManageDocumentos();
    }

    public function getPodeVisualizarDocumentosProperty(): bool
    {
        return $this->canViewDocumentos();
    }

    private function refreshDocumentosRelation(): void
    {
        $this->record->unsetRelation('documentos');
        $this->record->load([
            'documentos' => fn ($query) => $query->with([
                'usuario:id,name',
            ]),
        ]);
    }

    // ── Recebimentos ──────────────────────────────────────────────────────────

    public function abrirModalRecebimentos(): void
    {
        if (! $this->canViewRecebimentos()) {
            Notification::make()->title('Sem permissao para visualizar recebimentos')->danger()->send();

            return;
        }

        $this->refreshRecebimentosRelation();
        $this->inicializarBuffersRecebimentos();
        $this->novoRecNome = '';
        $this->novoRecStatus = 'pendente';
        $this->novoRecConstrutoraId = null;
        $this->modalRecebimentosOpen = true;
    }

    /**
     * Popula os buffers a partir do estado atual do banco.
     * Itens padrão pendentes começam sem construtora; itens já criados
     * começam com o fornecedor atualmente atribuída.
     */
    protected function inicializarBuffersRecebimentos(): void
    {
        $this->recebimentosPadraoSelecao = [];
        foreach ($this->itensPadraoRecebimentoPendentes as $nome) {
            $this->recebimentosPadraoSelecao[$nome] = null;
        }

        $this->recebimentosConstrutoraEdit = [];
        foreach ($this->record->recebimentos as $rec) {
            $this->recebimentosConstrutoraEdit[(int) $rec->id] = $rec->construtora_id !== null
                ? (int) $rec->construtora_id
                : null;
        }
    }

    /**
     * Retorna a lista de itens padrão ainda NÃO criados para esta obra.
     * Cada item aparece no modal com um select de construtora inline.
     *
     * @return array<int, string>
     */
    public function getItensPadraoRecebimentoPendentesProperty(): array
    {
        $jaCriados = $this->record->recebimentos
            ->pluck('nome')
            ->map(fn ($n): string => mb_strtolower(trim((string) $n)))
            ->all();

        return array_values(array_filter(
            self::ITENS_PADRAO_RECEBIMENTO,
            fn (string $nome): bool => ! in_array(mb_strtolower(trim($nome)), $jaCriados, true)
        ));
    }

    /**
     * Opções de construtoras para o select inline. Usa todos os registros do
     * cadastro de Construtoras (ConstrutoraResource), já que a pivot
     * obra_construtora não é preenchida no projeto.
     *
     * @return array<int, string>
     */
    public function getConstrutorasDaObraProperty(): array
    {
        return Construtora::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->all();
    }

    /**
     * Persiste todas as mudanças feitas no modal (itens padrão + edição de criados).
     * Cria os recebimentos novos, atualiza fornecedores dos existentes e envia
     * notificações aos usuários das construtoras impactadas.
     */
    public function salvarRecebimentos(): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para salvar recebimentos')->danger()->send();

            return;
        }

        $construtorasCache = [];
        $cacheGet = function (int $id) use (&$construtorasCache): ?\App\Models\Construtora {
            if (! array_key_exists($id, $construtorasCache)) {
                $construtorasCache[$id] = Construtora::query()->find($id);
            }

            return $construtorasCache[$id] instanceof Construtora
                ? $construtorasCache[$id]
                : null;
        };

        $criados = 0;
        $atualizados = 0;

        // 1) Itens padrão que ganharam construtora nesta sessão → cria recebimento
        foreach ($this->recebimentosPadraoSelecao as $nomeItem => $construtoraId) {
            if (! filled($construtoraId)) {
                continue;
            }

            $construtora = $cacheGet((int) $construtoraId);
            if (! $construtora) {
                continue;
            }

            $jaExiste = $this->record->recebimentos()
                ->whereRaw('LOWER(TRIM(nome)) = ?', [mb_strtolower(trim((string) $nomeItem))])
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $recebimento = ObraRecebimento::create([
                'obra_id' => $this->record->id,
                'construtora_id' => $construtora->id,
                'nome' => $nomeItem,
                'status' => 'pendente',
                'usuario_id' => auth()->id(),
            ]);

            $this->notificarConstrutoraRecebimento($recebimento, $construtora, atribuicao: true);
            $criados++;
        }

        // 2) Edição de construtora em recebimentos já existentes
        foreach ($this->recebimentosConstrutoraEdit as $recebimentoId => $construtoraId) {
            $recebimento = ObraRecebimento::query()
                ->where('id', (int) $recebimentoId)
                ->where('obra_id', $this->record->id)
                ->first();

            if (! $recebimento) {
                continue;
            }

            $anterior = $recebimento->construtora_id !== null ? (int) $recebimento->construtora_id : null;
            $novo = filled($construtoraId) ? (int) $construtoraId : null;

            if ($anterior === $novo) {
                continue;
            }

            if ($novo !== null) {
                $construtora = $cacheGet($novo);
                if (! $construtora) {
                    continue;
                }

                $recebimento->update(['construtora_id' => $construtora->id]);
                $this->notificarConstrutoraRecebimento($recebimento->fresh(), $construtora, atribuicao: false);
            } else {
                $recebimento->update(['construtora_id' => null]);
            }

            $atualizados++;
        }

        $this->refreshRecebimentosRelation();
        $this->inicializarBuffersRecebimentos();

        if ($criados === 0 && $atualizados === 0) {
            Notification::make()->title('Nada para salvar')->info()->send();

            return;
        }

        $partes = [];
        if ($criados > 0) {
            $partes[] = $criados === 1 ? '1 item criado' : $criados.' itens criados';
        }
        if ($atualizados > 0) {
            $partes[] = $atualizados === 1 ? '1 construtora atualizada' : $atualizados.' construtoras atualizadas';
        }

        Notification::make()
            ->title('Recebimentos salvos')
            ->body(implode(' · ', $partes).'.')
            ->success()
            ->send();

        $this->modalRecebimentosOpen = false;
    }

    /**
     * @deprecated Mantido por compatibilidade; prefira salvarRecebimentos().
     */
    public function atribuirConstrutoraItemPadrao(string $nomeItem, int $construtoraId): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para atribuir construtora')->danger()->send();

            return;
        }

        $nomeItem = trim($nomeItem);
        if ($nomeItem === '') {
            return;
        }

        $construtora = Construtora::query()->find($construtoraId);
        if (! $construtora instanceof Construtora) {
            Notification::make()
                ->title('Fornecedor inválido')
                ->body('Selecione um fornecedor cadastrado.')
                ->danger()
                ->send();

            return;
        }

        // Evita duplicata caso o item já tenha sido criado entre cliques
        $jaExiste = $this->record->recebimentos()
            ->whereRaw('LOWER(TRIM(nome)) = ?', [mb_strtolower($nomeItem)])
            ->exists();

        if ($jaExiste) {
            $this->refreshRecebimentosRelation();

            return;
        }

        $recebimento = ObraRecebimento::create([
            'obra_id' => $this->record->id,
            'construtora_id' => $construtora->id,
            'nome' => $nomeItem,
            'status' => 'pendente',
            'usuario_id' => auth()->id(),
        ]);

        $this->notificarConstrutoraRecebimento($recebimento, $construtora, atribuicao: true);
        $this->refreshRecebimentosRelation();

        Notification::make()
            ->title("Item '{$nomeItem}' atribuído a {$construtora->nome}")
            ->success()
            ->send();
    }

    /**
     * Altera o fornecedor responsável de um recebimento existente.
     * Se o fornecedor mudou, notifica os usuários do novo fornecedor.
     */
    public function alterarConstrutoraRecebimento(int $recebimentoId, ?int $construtoraId): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para alterar recebimento')->danger()->send();

            return;
        }

        $recebimento = ObraRecebimento::query()
            ->where('id', $recebimentoId)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $recebimento) {
            return;
        }

        $construtoraAnterior = $recebimento->construtora_id;

        if (filled($construtoraId)) {
            $construtora = Construtora::query()->find($construtoraId);
            if (! $construtora instanceof Construtora) {
                Notification::make()
                    ->title('Fornecedor inválido')
                    ->body('Selecione um fornecedor cadastrado.')
                    ->danger()
                    ->send();

                return;
            }

            $recebimento->update(['construtora_id' => $construtora->id]);

            if ((int) $construtoraAnterior !== (int) $construtora->id) {
                $this->notificarConstrutoraRecebimento($recebimento->fresh(), $construtora, atribuicao: false);
            }
        } else {
            $recebimento->update(['construtora_id' => null]);
        }

        $this->refreshRecebimentosRelation();
    }

    /**
     * Dispara Notification::sendToDatabase para todos os usuários do fornecedor.
     */
    protected function notificarConstrutoraRecebimento(
        ObraRecebimento $recebimento,
        Construtora $construtora,
        bool $atribuicao
    ): void {
        $usuarios = $construtora->users()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $obraNome = $this->record->projeto?->nome ?? ('Obra #'.$this->record->id);
        $titulo = $atribuicao
            ? 'Novo item de recebimento atribuído'
            : 'Item de recebimento reatribuído ao seu fornecedor';
        $corpo = sprintf(
            'O item "%s" na obra "%s" está aguardando entrega pela %s.',
            $recebimento->nome,
            $obraNome,
            $construtora->nome
        );

        $urlItem = ObraRecebimentoResource::getUrl('edit', ['record' => $recebimento->id]);

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-truck')
            ->warning()
            ->actions([
                Action::make('ver_item')
                    ->label('Abrir item')
                    ->url($urlItem),
            ])
            ->sendToDatabase($usuarios);
    }

    public function adicionarRecebimento(): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para adicionar recebimentos')->danger()->send();

            return;
        }

        $nome = trim($this->novoRecNome);

        if ($nome === '') {
            return;
        }

        $construtora = null;
        if (filled($this->novoRecConstrutoraId)) {
            $construtora = Construtora::query()->find($this->novoRecConstrutoraId);

            if (! $construtora instanceof Construtora) {
                Notification::make()
                    ->title('Fornecedor inválido')
                    ->body('Selecione um fornecedor cadastrado.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $recebimento = ObraRecebimento::create([
            'obra_id' => $this->record->id,
            'construtora_id' => $construtora?->id,
            'nome' => $nome,
            'status' => 'pendente',
            'usuario_id' => auth()->id(),
        ]);

        if ($construtora) {
            $this->notificarConstrutoraRecebimento($recebimento, $construtora, atribuicao: true);
        }

        $this->refreshRecebimentosRelation();
        $this->novoRecNome = '';
        $this->novoRecStatus = 'pendente';
        $this->novoRecConstrutoraId = null;
    }

    public function removerRecebimento(int $id): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para remover recebimentos')->danger()->send();

            return;
        }

        ObraRecebimento::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->delete();

        $this->refreshRecebimentosRelation();
    }

    public function alterarStatusRecebimento(int $id, string $status): void
    {
        if (! $this->canManageRecebimentos()) {
            Notification::make()->title('Sem permissao para alterar recebimentos')->danger()->send();

            return;
        }

        if (! in_array($status, ['pendente', 'recebido', 'nao_aplicavel'])) {
            return;
        }

        ObraRecebimento::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->update([
                'status' => $status,
            ]);

        $this->refreshRecebimentosRelation();
    }

    public function abrirArquivoRecebimento(int $id, string $tipo): void
    {
        $recebimento = $this->record->recebimentos()
            ->whereKey($id)
            ->first();

        if (! $recebimento) {
            return;
        }

        $paths = match ($tipo) {
            'foto' => $recebimento->foto_entrega_paths_resolved,
            'nota' => $recebimento->nota_fiscal_paths_resolved,
            default => [],
        };

        if ($paths === []) {
            return;
        }

        $urls = collect($paths)
            ->map(function (string $path): ?string {
                /** @var FilesystemAdapter $mediaDisk */
                $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                if (Str::startsWith($path, ['http://', 'https://'])) {
                    return $path;
                }

                if ($mediaDisk->exists($path)) {
                    return $mediaDisk->temporaryUrl($path, now()->addMinutes(15));
                }

                return null;
            })
            ->filter(fn (?string $url): bool => filled($url))
            ->values();

        if ($urls->isEmpty()) {
            Notification::make()
                ->title('Arquivo não encontrado para visualização')
                ->warning()
                ->send();

            return;
        }

        $urls->each(fn (string $url) => $this->js('window.open('.json_encode($url).', "_blank")'));
    }

    private function canManageRecebimentos(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('Gestor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    private function canViewRecebimentos(): bool
    {
        $user = auth()->user();

        if ($this->canManageRecebimentos()) {
            return true;
        }

        return ObraRecebimentoResource::isConstrutoraTerceiros($user)
            && $this->isConstrutoraVinculadaNaObra($user);
    }

    public function getPodeVisualizarRecebimentosProperty(): bool
    {
        return $this->canViewRecebimentos();
    }

    private function refreshRecebimentosRelation(): void
    {
        $this->record->unsetRelation('recebimentos');
        $this->record->load([
            'recebimentos' => fn ($query) => $query->with([
                'construtora:id,nome',
                'usuario:id,name',
            ]),
        ]);
    }

    // ── Controle de Contratações ──────────────────────────────────────────────

    public function getPodeEditarPontosAtencaoProperty(): bool
    {
        return $this->canManagePontosAtencao();
    }

    private function canManagePontosAtencao(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Gestor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    public function abrirModalPontosAtencao(): void
    {
        if (! $this->canManagePontosAtencao()) {
            Notification::make()->title('Sem permissao para editar Pontos de Atencao')->danger()->send();

            return;
        }

        $this->carregarColunasPersonalizadasParaFormulario();
        $this->modalPontosAtencaoOpen = true;
    }

    public function salvarPontosAtencao(): void
    {
        if (! $this->canManagePontosAtencao()) {
            Notification::make()->title('Sem permissao para editar Pontos de Atencao')->danger()->send();

            return;
        }

        $colunas = ColunaPersonalizada::query()
            ->where('obra_id', $this->record->id)
            ->orderBy('nome')
            ->get();

        foreach ($colunas as $coluna) {
            $raw = $this->colunasPersonalizadasValores[$coluna->id] ?? null;
            $valor = is_string($raw) ? trim($raw) : $raw;

            if ($valor === '' || $valor === null) {
                $valor = null;
            }

            if ($valor !== null) {
                if ($coluna->tipo === 'numero' && ! is_numeric($valor)) {
                    Notification::make()->title("Valor invalido para '{$coluna->nome}'")->danger()->send();

                    return;
                }

                if ($coluna->tipo === 'data' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $valor)) {
                    Notification::make()->title("Data invalida para '{$coluna->nome}'")->danger()->send();

                    return;
                }

                if ($coluna->tipo === 'select') {
                    $opcoes = collect($coluna->opcoes ?? [])
                        ->filter(fn ($item) => filled(trim((string) $item)))
                        ->map(fn ($item) => trim((string) $item))
                        ->values()
                        ->all();

                    if (empty($opcoes) || ! in_array((string) $valor, $opcoes, true)) {
                        Notification::make()->title("Selecione uma opcao valida para '{$coluna->nome}'")->danger()->send();

                        return;
                    }
                }
            }

            $coluna->update([
                'valor' => $valor !== null ? substr((string) $valor, 0, 255) : null,
                'usuario_id' => auth()->id(),
            ]);
        }

        $this->modalPontosAtencaoOpen = false;
        $this->record->unsetRelation('colunasPersonalizadas');
        Notification::make()->title('Pontos de Atencao atualizados')->success()->send();
    }

    public function adicionarColunaPersonalizadaPontoAtencao(): void
    {
        if (! $this->canManagePontosAtencao()) {
            Notification::make()->title('Sem permissao para editar Pontos de Atencao')->danger()->send();

            return;
        }

        $this->validate([
            'novaColunaPersonalizadaNome' => ['required', 'string', 'max:120'],
            'novaColunaPersonalizadaTipo' => ['required', 'in:texto,numero,data,select'],
        ], [
            'novaColunaPersonalizadaNome.required' => 'Informe o nome da coluna.',
            'novaColunaPersonalizadaNome.max' => 'Nome deve ter no maximo 120 caracteres.',
            'novaColunaPersonalizadaTipo.in' => 'Tipo de coluna invalido.',
        ]);

        $nome = trim($this->novaColunaPersonalizadaNome);
        $tipo = $this->novaColunaPersonalizadaTipo;
        $opcoes = $tipo === 'select' ? $this->normalizarOpcoesColunaPersonalizada($this->novaColunaPersonalizadaOpcoes) : null;
        $userId = auth()->id();
        $obras = Obras::query()
            ->whereNotNull('projeto_id')
            ->get(['id', 'projeto_id']);

        if ($tipo === 'select' && empty($opcoes)) {
            Notification::make()->title('Informe as opcoes do select (separadas por virgula)')->danger()->send();

            return;
        }

        foreach ($obras as $obra) {
            ColunaPersonalizada::firstOrCreate(
                [
                    'projeto_id' => $obra->projeto_id,
                    'obra_id' => $obra->id,
                    'nome' => $nome,
                ],
                [
                    'tipo' => $tipo,
                    'opcoes' => $opcoes,
                    'valor' => null,
                    'usuario_id' => $userId,
                ]
            );
        }

        $this->novaColunaPersonalizadaNome = '';
        $this->novaColunaPersonalizadaTipo = 'texto';
        $this->novaColunaPersonalizadaOpcoes = '';
        $this->carregarColunasPersonalizadasParaFormulario();
        $this->record->unsetRelation('colunasPersonalizadas');

        Notification::make()->title('Coluna criada para todas as obras')->success()->send();
    }

    public function removerColunaPersonalizadaPontoAtencao(int $colunaId): void
    {
        if (! $this->canManagePontosAtencao()) {
            Notification::make()->title('Sem permissao para editar Pontos de Atencao')->danger()->send();

            return;
        }

        $coluna = ColunaPersonalizada::query()
            ->where('id', $colunaId)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $coluna) {
            return;
        }

        ColunaPersonalizada::query()
            ->where('nome', $coluna->nome)
            ->delete();

        $this->carregarColunasPersonalizadasParaFormulario();
        $this->record->unsetRelation('colunasPersonalizadas');

        Notification::make()->title('Campo personalizado removido de todas as obras')->success()->send();
    }

    private function carregarColunasPersonalizadasParaFormulario(): void
    {
        $colunas = ColunaPersonalizada::query()
            ->where('obra_id', $this->record->id)
            ->orderBy('nome')
            ->get();

        $this->colunasPersonalizadasValores = [];

        foreach ($colunas as $coluna) {
            $this->colunasPersonalizadasValores[$coluna->id] = $coluna->valor;
        }
    }

    private function normalizarOpcoesColunaPersonalizada(string $opcoesBrutas): array
    {
        return collect(preg_split('/[\r\n,;]+/', $opcoesBrutas) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function abrirModalFachada(): void
    {
        $this->fachadaDataInstalacao = $this->record->fachada_data_instalacao?->format('Y-m-d');
        $this->fachadaStatus = $this->record->fachada_status ?? '';
        $this->fachadaObservacao = $this->record->fachada_observacao ?? '';
        $this->modalFachadaOpen = true;
    }

    public function salvarFachada(): void
    {
        $statusPermitidos = [
            'finalizada',
            'agendada',
            'aguardando_contratacao',
            'em_atraso',
            'com_pendencia',
        ];

        if ($this->fachadaStatus !== '' && ! in_array($this->fachadaStatus, $statusPermitidos, true)) {
            Notification::make()->title('Status de fachada invalido')->danger()->send();

            return;
        }

        $this->record->update([
            'fachada_data_instalacao' => filled($this->fachadaDataInstalacao) ? $this->fachadaDataInstalacao : null,
            'fachada_status' => $this->fachadaStatus !== '' ? $this->fachadaStatus : null,
            'fachada_observacao' => filled(trim($this->fachadaObservacao)) ? trim($this->fachadaObservacao) : null,
        ]);

        $this->modalFachadaOpen = false;
        Notification::make()->title('Fachada atualizada')->success()->send();
    }

    // ── Detalhe do Item do Controle de Medição (somente leitura) ─────────────

    /**
     * Carrega os dados de um ControleNotaFiscalItem (tipo 'item') ou
     * ControleNotaFiscalAuxiliar (tipo 'auxiliar') e abre o modal read-only.
     */
    public function abrirModalDetalheItem(int $id, string $tipo): void
    {
        $registro = match ($tipo) {
            'item' => ControleNotaFiscalItem::query()
                ->with(['controleNotaFiscal.obra:id', 'notas'])
                ->find($id),
            'auxiliar' => ControleNotaFiscalAuxiliar::query()
                ->with(['controleNotaFiscal.obra:id', 'notas'])
                ->find($id),
            default => null,
        };

        if (! $registro) {
            Notification::make()->title('Item não encontrado')->danger()->send();

            return;
        }

        // Garante que o item realmente pertence a esta obra.
        $obraDoRegistro = $registro->controleNotaFiscal?->obra_id;
        if ((int) $obraDoRegistro !== (int) $this->record->id) {
            Notification::make()->title('Item não pertence a esta obra')->danger()->send();

            return;
        }

        $notas = $registro->notas ?? collect();
        $totalNotas = $notas->count();
        $somaNotas = (float) $notas->sum(fn ($n) => (float) ($n->valor_acumulado_medido_nf ?? 0));

        $notasPorTipo = $notas->groupBy('tipo_medicao')->map->count();

        $this->detalheItemDados = [
            'tipo' => $tipo,
            'id' => (int) $registro->id,
            'grupo' => (string) ($registro->grupo ?? ''),
            'numero_as' => (string) ($registro->numero_as ?? ''),
            'numero_complemento' => (string) ($registro->numero_complemento ?? ''),
            'escopo' => (string) ($registro->escopo ?? ''),
            'escopo_complementar' => (string) ($registro->escopo_complementar ?? ''),
            'empresa' => (string) ($registro->empresa ?? ''),
            'valor_global_a' => (float) ($registro->valor_global_a ?? 0),
            'total_medicao_a_menos_b' => (float) ($registro->total_medicao_a_menos_b ?? 0),
            'valor_acumulado_medido' => (float) ($registro->valor_acumulado_medido ?? 0),
            'saldo' => (float) ($registro->saldo ?? 0),
            'percentual_faturamento_mao_obra' => (float) ($registro->percentual_faturamento_mao_obra ?? 0),
            'percentual_faturamento_material' => (float) ($registro->percentual_faturamento_material ?? 0),
            'observacoes' => (string) ($registro->observacoes ?? ''),
            'total_notas' => $totalNotas,
            'soma_notas' => $somaNotas,
            'notas_mao_obra' => (int) ($notasPorTipo['mao_obra'] ?? 0),
            'notas_material' => (int) ($notasPorTipo['material'] ?? 0),
            'notas_transporte' => (int) ($notasPorTipo['transporte'] ?? 0),
        ];

        $this->modalDetalheItemOpen = true;
    }

    // ── Fotos da Galeria ───────────────────────────────────────────────────

    public function getFotosFormSchema(): array
    {
        return [
            FileUpload::make('fotosUpload')
                ->hiddenLabel()
                ->multiple()->disk((string) config('filesystems.media_disk', 'r2'))
                ->visibility('public')
                ->previewable(true)
                ->fetchFileInformation(false)
                ->openable()
                ->downloadable()
                ->panelLayout('grid')
                ->imagePreviewHeight('200')
                ->reorderable()
                ->saveUploadedFileUsing(
                    ImageUploadHelper::callback(
                        fn () => 'obras/'.$this->record->id.'/galeria',
                        (string) config('filesystems.media_disk', 'r2'),
                        'galeria'
                    )
                )
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'video/*',
                ])
                ->maxSize(204800),
        ];
    }

    public function fotosForm(Schema $schema): Schema
    {
        return $schema
            ->schema($this->getFotosFormSchema())
            ->statePath('fotosFormData');
    }

    public function abrirModalFotos(): void
    {
        $this->fotosFormData = ['fotosUpload' => []];
        $this->fotoCategoriaSelecionada = 'obra';
        $this->modalFotosOpen = true;
    }

    public function salvarFotos(): void
    {
        $data = $this->fotosForm->getState();

        if (empty($data['fotosUpload'])) {
            return;
        }

        foreach ($data['fotosUpload'] as $path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $tipo = match (true) {
                in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'imagem',
                in_array($extension, ['mp4', 'mov', 'avi', 'webm']) => 'video',
                default => 'arquivo',
            };

            $this->record->midias()->create([
                'path' => $path,
                'disk' => (string) config('filesystems.media_disk', 'r2'),
                'categoria' => $this->fotoCategoriaSelecionada,
                'tipo' => $tipo,
                'nome_original' => basename($path),
            ]);
        }

        $this->fotosFormData = ['fotosUpload' => []];
        $this->modalFotosOpen = false;
        $this->buildGaleriaCompleta();
        $this->refreshFotoCategorias();
        $this->dispatch('galeria-filter-change', filter: $this->fotoCategoriaSelecionada);
        Notification::make()->title('Fotos adicionadas')->success()->send();
    }

    public function abrirVisi(): void
    {
        if (! filled($this->record->link)) {
            Notification::make()
                ->title('Link VISI não disponível')
                ->body('Esta obra não possui um link VISI configurado.')
                ->warning()
                ->send();

            return;
        }

        $this->js('window.open('.json_encode($this->record->link).', "_blank")');
    }

    public function removerFoto(int $midiaId): void
    {
        $midia = Midia::find($midiaId);

        if (! $midia || $midia->mediavel_id !== $this->record->id || $midia->mediavel_type !== Obras::class) {
            return;
        }

        Storage::disk($midia->disk)->delete($midia->path);
        $midia->delete();

        $this->buildGaleriaCompleta();
        $this->refreshFotoCategorias();
        Notification::make()->title('Foto removida')->success()->send();
    }

    public function getCategorias(): array
    {
        return $this->record->midias()
            ->select('categoria')
            ->distinct()
            ->pluck('categoria')
            ->reject(fn ($cat) => $cat === 'obra')
            ->values()
            ->all();
    }

    private function refreshFotoCategorias(): void
    {
        $dbCats = $this->getCategorias();
        $builtIn = ['obra', 'constructin', 'relatorio_fotografico', 'visita_tecnica', 'projeto'];
        $extras = array_filter(
            array_diff($this->fotoCategorias, $dbCats),
            fn ($cat) => ! in_array($cat, $builtIn)
        );
        $this->fotoCategorias = array_values(array_unique(array_merge($dbCats, $extras)));
    }

    public function criarFotoCategoria(string $nome): void
    {
        $nome = trim($nome);

        if (empty($nome)) {
            return;
        }

        $slug = Str::slug($nome);
        $reservados = ['obra', 'constructin', 'relatorio_fotografico', 'visita_tecnica', 'projeto'];

        if (in_array($slug, $reservados)) {
            Notification::make()->title('Nome reservado pelo sistema')->danger()->send();

            return;
        }

        if (in_array($slug, $this->fotoCategorias)) {
            Notification::make()->title('Categoria já existe')->warning()->send();

            return;
        }

        $this->fotoCategorias[] = $slug;
        $this->dispatch('fotos-updated', fotos: $this->galeriaCompleta, categorias: $this->fotoCategorias);
        Notification::make()->title("Categoria \"{$nome}\" criada")->success()->send();
    }

    public function removerFotoCategoria(string $categoria, string $destino): void
    {
        if ($categoria === $destino) {
            return;
        }

        $this->record->midias()
            ->where('categoria', $categoria)
            ->update(['categoria' => $destino]);

        $this->fotoCategorias = array_values(array_diff($this->fotoCategorias, [$categoria]));
        $this->buildGaleriaCompleta();
        $this->refreshFotoCategorias();
        $this->dispatch('galeria-filter-change', filter: $destino);
        Notification::make()->title("Categoria removida, fotos movidas para \"{$destino}\"")->success()->send();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function definirFotoPerfil(?string $path = null, ?string $url = null): void
    {
        $this->definirFotoDaGaleria('foto_perfil', 'Foto de perfil', $path, $url);
    }

    public function definirFotoCapa(?string $path = null, ?string $url = null): void
    {
        $this->definirFotoDaGaleria('foto_capa', 'Foto de capa', $path, $url);
    }

    private function definirFotoDaGaleria(string $campo, string $label, ?string $path, ?string $url): void
    {
        $pathFinal = $this->resolverPathFotoGaleria($path, $url);

        if (! $pathFinal) {
            Notification::make()->title("Nao foi possivel definir {$label}")->danger()->send();

            return;
        }

        $this->record->update([$campo => $pathFinal]);
        $this->record->refresh();

        Notification::make()->title("{$label} atualizada")->success()->send();
    }

    private function resolverPathFotoGaleria(?string $path, ?string $url): ?string
    {
        if (filled($path) && Storage::disk((string) config('filesystems.media_disk', 'r2'))->exists($path)) {
            return $path;
        }

        if (! filled($url)) {
            return null;
        }

        $urlFinal = $url;
        if (str_contains($url, 'files.constructin.com.br')) {
            $urlFinal = $this->obterUrlConstructinFresca($url) ?? $url;
        }

        try {
            $response = Http::timeout(30)->get($urlFinal);
        } catch (\Throwable $e) {
            Log::warning('Falha ao baixar imagem externa para foto da obra', [
                'obra_id' => $this->record->id,
                'url' => $urlFinal,
                'url_original' => $url,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $mimeType = (string) $response->header('Content-Type', '');
        $body = $response->body();

        if (! filled($body)) {
            return null;
        }

        $extensao = 'jpg';

        if (str_starts_with($mimeType, 'image/')) {
            $extensao = match (strtolower($mimeType)) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };
        } else {
            if (str_starts_with($mimeType, 'application/octet-stream') || str_starts_with($mimeType, 'binary/octet-stream')) {
                $extensao = $this->detectarExtensaoPorAssinatura($body);
            }
        }

        try {
            $targetPath = 'obras/'.$this->record->id.'/fotos/externas/'.Str::uuid().'.'.$extensao;
            Storage::disk((string) config('filesystems.media_disk', 'r2'))->put($targetPath, $body);

            return $targetPath;
        } catch (\Throwable $e) {
            Log::error('Falha ao salvar foto da obra no storage', [
                'obra_id' => $this->record->id,
                'url' => $url,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function detectarExtensaoPorAssinatura(string $data): string
    {
        $hex = bin2hex(substr($data, 0, 12));

        return match (true) {
            str_starts_with($hex, 'ffd8ffe0') || str_starts_with($hex, 'ffd8ffe1') => 'jpg',
            str_starts_with($hex, '89504e47') => 'png',
            str_starts_with($hex, '52494646') && str_contains($hex, '57454250') => 'webp',
            str_starts_with($hex, '474946') => 'gif',
            default => 'jpg',
        };
    }

    private function obterUrlConstructinFresca(?string $urlAntiga): ?string
    {
        if (! $this->record->constructin_project_id) {
            return null;
        }

        try {
            $service = new ConstructinService;
            $fotos = $service->getImages((int) $this->record->constructin_project_id);

            if (empty($fotos)) {
                return null;
            }

            return $fotos[0]['url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao obter URL fresca da Constructin', [
                'obra_id' => $this->record->id,
                'constructin_project_id' => $this->record->constructin_project_id,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function buildGaleriaCompleta(): void
    {
        $fotos = [];
        $projetoId = $this->record->projeto_id;

        foreach ($this->record->midias()->orderBy('ordem')->get() as $midia) {
            $isImagem = $midia->tipo === 'imagem';
            $url = $isImagem
                ? (ImageVariantUrl::forStorage($midia->disk, $midia->path, 1600, 1200, 'contain', 78) ?? $midia->url)
                : $midia->url;

            $fotos[] = [
                'uid' => 'midia_'.$midia->id,
                'url' => $url,
                'thumb_url' => $isImagem
                    ? (ImageVariantUrl::forStorage($midia->disk, $midia->path, 420, 420, 'cover', 70) ?? $url)
                    : null,
                'original_url' => $midia->url,
                'path' => $midia->path,
                'source' => $midia->categoria,
                'midia_id' => $midia->id,
            ];
        }

        foreach ($this->constructinFotos as $foto) {
            $fotos[] = [
                'uid' => 'constructin_'.md5((string) ($foto['original_url'] ?? $foto['url'])),
                'url' => $foto['url'],
                'thumb_url' => $foto['thumb_url'] ?? $foto['url'],
                'original_url' => $foto['original_url'] ?? $foto['url'],
                'path' => null,
                'source' => 'constructin',
            ];
        }

        if ($projetoId) {
            $relatoriosFoto = RelatorioFotografico::where('projeto_id', $projetoId)->get();
            foreach ($relatoriosFoto as $rf) {
                foreach ($rf->fotos ?? [] as $path) {
                    /** @var FilesystemAdapter $mediaDisk */
                    $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                    $fotos[] = [
                        'uid' => 'rf_'.$rf->id.'_'.md5((string) $path),
                        'url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 1600, 1200, 'contain', 78) ?? $mediaDisk->url($path),
                        'thumb_url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 420, 420, 'cover', 70) ?? $mediaDisk->url($path),
                        'original_url' => $mediaDisk->url($path),
                        'path' => $path,
                        'source' => 'relatorio_fotografico',
                    ];
                }
            }

            $camposFotoVt = [
                'foto_entrada_de_energia', 'foto_energia_carga_superior_150', 'foto_energia_provisoria',
                'foto_unica_medicao', 'foto_spda', 'foto_telegonia_dg', 'foto_necessario_estrutura_auxiliar',
                'foto_estrutura_fachada', 'foto_cobertura_vao_1_5', 'foto_cobertura_isolamento',
                'foto_permitidas_furacoes_laje', 'foto_sobrecarga_minima_laje', 'foto_sobrecarga_minima_laje_teto',
                'foto_local_tomada_ar_externo_exaustao', 'foto_alvenaria_periferia_existente',
                'foto_reboco_interno_externo_existente', 'foto_estanqueidade',
                'foto_area_tecnica_externa_existente', 'foto_prever_acustica_condensadores',
                'foto_prever_protecao_condensadores', 'foto_reservatorio_agua_existente',
                'foto_reservatorio_incendio_existente', 'foto_ponto_esgoto_existente_shell',
                'foto_rede_gas_disponivel', 'foto_medidor_agua_instalado_ligado',
                'foto_sistema_incendio_existente', 'foto_planta_demarcacao_area', 'foto_pd_acima_livre',
                'foto_necessario_elevador_plataforma', 'foto_piso_acabamento_polido',
                'foto_necessario_pelicula_fachada', 'foto_prever_marquise', 'foto_prever_porta_enrolar',
                'foto_caixilhos_vidros_existentes', 'foto_prever_impermeabilizacao',
                'foto_necessario_porta_enrolar', 'fotos_gerais',
            ];

            /** @var FilesystemAdapter $mediaDisk */
            $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

            $visitasTecnicas = RelatorioVisitaTecnica::where('projeto_id', $projetoId)->get();
            foreach ($visitasTecnicas as $vt) {
                foreach ($camposFotoVt as $campo) {
                    $paths = $vt->{$campo} ?? [];
                    if (! is_array($paths)) {
                        continue;
                    }
                    foreach ($paths as $path) {
                        if (filled($path)) {
                            $fotos[] = [
                                'uid' => 'vt_'.$vt->id.'_'.$campo.'_'.md5((string) $path),
                                'url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 1600, 1200, 'contain', 78) ?? $mediaDisk->url($path),
                                'thumb_url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 420, 420, 'cover', 70) ?? $mediaDisk->url($path),
                                'original_url' => $mediaDisk->url($path),
                                'path' => $path,
                                'source' => 'visita_tecnica',
                            ];
                        }
                    }
                }
            }

            $projeto = $this->record->projeto;
            if ($projeto?->imagem_ponto) {
                $imgs = is_array($projeto->imagem_ponto) ? $projeto->imagem_ponto : [$projeto->imagem_ponto];
                foreach ($imgs as $img) {
                    $fotos[] = [
                        'uid' => 'projeto_'.md5((string) $img),
                        'url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $img, 1600, 1200, 'contain', 78) ?? $mediaDisk->url($img),
                        'thumb_url' => ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $img, 420, 420, 'cover', 70) ?? $mediaDisk->url($img),
                        'original_url' => $mediaDisk->url($img),
                        'path' => $img,
                        'source' => 'projeto',
                    ];
                }
            }
        }

        $this->galeriaCompleta = collect($fotos)->filter(fn ($f) => filled($f['url']))->values()->all();
        $this->dispatch('fotos-updated', fotos: $this->galeriaCompleta, categorias: $this->fotoCategorias);
    }

    private function prepareConstructinFotos(array $fotos): array
    {
        return collect($fotos)
            ->map(function (array $foto): ?array {
                $originalUrl = $foto['url'] ?? null;

                if (blank($originalUrl)) {
                    return null;
                }

                // URLs do Constructin costumam ser assinadas e expirar rapidamente.
                // Para evitar 404 no endpoint de variantes, usamos o link original.
                return [
                    'url' => (string) $originalUrl,
                    'thumb_url' => (string) $originalUrl,
                    'original_url' => (string) $originalUrl,
                    'date' => $foto['date'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function loadEntregaContratual(): void
    {
        if ($this->entregaContratualLoaded) {
            return;
        }

        $this->entregaContratualData = $this->record
            ->entregasContratuais()
            ->get()
            ->map(fn ($e): array => [
                'id' => $e->id,
                'tipo' => $e->tipo,
                'entrega' => $e->entrega,
                'descricao_entrega' => $e->descricao_entrega,
                'descricao_existente' => $e->descricao_existente,
                'status' => $e->status,
                'data_entrega' => optional($e->data_entrega)->format('Y-m-d'),
                'previsto_em_contrato' => (bool) $e->previsto_em_contrato,
                'previsto_status' => $e->previsto_status
                    ?? ((bool) $e->previsto_em_contrato ? 'previsto_sim' : 'previsto_nao'),
                'custo_contrato' => (float) $e->custo_contrato,
                'custo_sem_contrato' => (float) $e->custo_sem_contrato,
                'custo_estimado' => (float) $e->custo_estimado,
                'observacoes' => $e->observacoes,
            ])
            ->all();

        $this->entregaContratualLoaded = true;
    }

    /**
     * @return array<string, string> slug => nome
     */
    public function getEntregaContratualStatusOptions(): array
    {
        return Status::slugsDoContexto('entrega_contratual_status');
    }

    /**
     * @return array<string, string> slug => cor hex
     */
    public function getEntregaContratualStatusCorMap(): array
    {
        return Status::ativosPorContexto('entrega_contratual_status')
            ->mapWithKeys(fn (Status $s): array => [$s->slug => $s->cor])
            ->all();
    }

    /**
     * @return array<string, string> slug => nome
     */
    public function getEntregaContratualPrevistoOptions(): array
    {
        return Status::slugsDoContexto('entrega_contratual_previsto');
    }

    /**
     * @return array<string, string> slug => cor hex
     */
    public function getEntregaContratualPrevistoCorMap(): array
    {
        return Status::ativosPorContexto('entrega_contratual_previsto')
            ->mapWithKeys(fn (Status $s): array => [$s->slug => $s->cor])
            ->all();
    }

    /**
     * @return array<string, ?string> slug => tipo_custo
     */
    public function getEntregaContratualPrevistoTipoCustoMap(): array
    {
        return Status::ativosPorContexto('entrega_contratual_previsto')
            ->mapWithKeys(fn (Status $s): array => [$s->slug => $s->tipo_custo])
            ->all();
    }

    /**
     * @return array<string, bool> slug => is_protected
     */
    public function getEntregaContratualPrevistoProtectedMap(): array
    {
        return Status::ativosPorContexto('entrega_contratual_previsto')
            ->mapWithKeys(fn (Status $s): array => [$s->slug => (bool) $s->is_protected])
            ->all();
    }

    public function atualizarEntregaContratual(int $id, string $campo, mixed $valor): void
    {
        $camposPermitidos = ['tipo', 'entrega', 'descricao_entrega', 'descricao_existente', 'status', 'data_entrega', 'previsto_status', 'custo_contrato', 'custo_sem_contrato', 'custo_estimado', 'observacoes'];

        if (! in_array($campo, $camposPermitidos, true)) {
            return;
        }

        $entrega = ObraEntregaContratual::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $entrega) {
            return;
        }

        if ($campo === 'status' && ! array_key_exists($valor, $this->getEntregaContratualStatusOptions())) {
            return;
        }

        if ($campo === 'previsto_status' && ! array_key_exists($valor, $this->getEntregaContratualPrevistoOptions())) {
            return;
        }

        if (in_array($campo, ['custo_estimado', 'custo_contrato', 'custo_sem_contrato'], true)) {
            $valor = is_numeric($valor) ? max(0, (float) $valor) : 0;
        }

        if ($campo === 'previsto_status') {
            // A janela de confirmação no front (voMostrarConfirmacaoPrevisto) já cobre o caso
            // em que existe valor a zerar. Aqui só chegamos no caminho direto (sem valor a zerar)
            // ou quando o front errou — nesse caso emitimos aviso e abortamos.
            $tipoCustoMap = $this->getEntregaContratualPrevistoTipoCustoMap();
            $tipoCustoNovo = $tipoCustoMap[$valor] ?? null;
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

            $this->aplicarMudancaPrevistoEntregaContratual($entrega, $valor);

            return;
        }

        if (in_array($campo, ['tipo', 'descricao_entrega', 'descricao_existente', 'observacoes'], true)) {
            $valor = $valor === '' ? null : $valor;
        }

        if ($campo === 'entrega') {
            $valor = $valor === '' || $valor === null ? 'Nova entrega' : $valor;
        }

        if ($campo === 'data_entrega') {
            if ($entrega->status === 'nao_entregue') {
                $valor = null;
            } else {
                $valor = $valor === '' ? null : $valor;
            }
        }

        $dados = [$campo => $valor];

        if ($campo === 'status' && $valor === 'nao_entregue') {
            $dados['data_entrega'] = null;
        }

        $entrega->update($dados);

        foreach ($this->entregaContratualData as &$item) {
            if ((int) $item['id'] === $id) {
                foreach ($dados as $k => $v) {
                    if ($k === 'data_entrega') {
                        $item[$k] = $v ? Carbon::parse($v)->format('Y-m-d') : null;
                    } else {
                        $item[$k] = $v;
                    }
                }
                break;
            }
        }
        unset($item);
    }

    public function confirmarMudancaPrevistoEntregaContratual(int $entregaId, string $novoSlug): void
    {
        $entrega = ObraEntregaContratual::where('id', $entregaId)
            ->where('obra_id', $this->record->id)
            ->first();

        if (! $entrega) {
            return;
        }

        if (! array_key_exists($novoSlug, $this->getEntregaContratualPrevistoOptions())) {
            return;
        }

        $this->aplicarMudancaPrevistoEntregaContratual($entrega, $novoSlug);

        Notification::make()
            ->title('Atualização realizada com sucesso')
            ->success()
            ->send();
    }

    private function aplicarMudancaPrevistoEntregaContratual(ObraEntregaContratual $entrega, string $novoSlug): void
    {
        $tipoCusto = $this->getEntregaContratualPrevistoTipoCustoMap()[$novoSlug] ?? null;

        $dados = [
            'previsto_status' => $novoSlug,
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

        // Recarrega o array da view e força recriação dos inputs Alpine (moeda).
        $this->entregaContratualLoaded = false;
        $this->loadEntregaContratual();
        $this->entregaContratualRefresh[$entrega->id] = now()->timestamp;
        $this->dispatch('$refresh');
    }

    #[On('confirmarMudancaPrevistoEntregaContratual')]
    public function handleConfirmarMudancaPrevistoEntregaContratual(int $entregaId, string $novoSlug): void
    {
        $this->confirmarMudancaPrevistoEntregaContratual($entregaId, $novoSlug);
    }

    public function adicionarEntregaContratual(): void
    {
        $proximoSort = (int) ($this->record->entregasContratuais()->max('sort_order') ?? 0) + 1;

        $nova = ObraEntregaContratual::create([
            'obra_id' => $this->record->id,
            'entrega' => 'Nova entrega',
            'status' => 'nao_entregue',
            'previsto_em_contrato' => false,
            'previsto_status' => 'previsto_nao',
            'custo_contrato' => 0,
            'custo_sem_contrato' => 0,
            'custo_estimado' => 0,
            'sort_order' => $proximoSort,
        ]);

        $this->entregaContratualData[] = [
            'id' => $nova->id,
            'tipo' => null,
            'entrega' => $nova->entrega,
            'descricao_entrega' => null,
            'descricao_existente' => null,
            'status' => $nova->status,
            'data_entrega' => null,
            'previsto_em_contrato' => false,
            'previsto_status' => 'previsto_nao',
            'custo_contrato' => 0.0,
            'custo_sem_contrato' => 0.0,
            'custo_estimado' => 0.0,
            'observacoes' => null,
        ];
    }

    public function removerEntregaContratual(int $id): void
    {
        $entrega = ObraEntregaContratual::where('id', $id)
            ->where('obra_id', $this->record->id)
            ->first();

        if ($entrega) {
            $entrega->delete();
        }

        $this->entregaContratualData = array_values(
            array_filter($this->entregaContratualData, fn ($item) => (int) ($item['id'] ?? 0) !== $id)
        );
    }

    public function loadPedidosRetrofit(): void
    {
        if ($this->pedidosRetrofitLoaded) {
            return;
        }

        $obraId = $this->record->id;

        if (! $obraId) {
            $this->pedidosRetrofitData = [];
            $this->pedidosRetrofitLoaded = true;

            return;
        }

        $controle = $this->record->controlesNotaFiscal()
            ->where('tipo_unidade', TipoUnidade::RETROFIT->value)
            ->whereHas('itens')
            ->with([
                'itens' => function ($query): void {
                    $query->orderBy('sort_order')
                        ->orderBy('id');
                },
                'itens.asEscopo:id,grupo,numero_as,escopo',
            ])
            ->latest('id')
            ->first();

        if (! $controle) {
            $this->pedidosRetrofitData = [];
            $this->pedidosRetrofitLoaded = true;

            return;
        }

        $this->pedidosRetrofitData = [
            'controle_id' => $controle->id,
            'numero' => $controle->id,
            'status' => $this->normalizeRetrofitControleStatus($controle->status),
            'contratacao' => optional($controle->contratacao ?? null)->format('d/m/Y'),
            'observacoes' => $controle->observacoes,
            'pedidos' => $controle->itens
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'grupo' => $item->grupo,
                    'codigo' => trim((string) ($item->numero_as ?? '')),
                    'numero_complemento' => $item->numero_complemento,
                    'escopo' => $item->asEscopo?->escopo ?? $item->escopo,
                    'empresa' => $item->empresa,
                    'status' => $item->status_retrofit,
                    'valor' => (float) ($item->valor_global_a ?? 0),
                    'quantidade' => $item->quantidade,
                    'observacoes' => $item->observacoes,
                ])
                ->values()
                ->all(),
        ];

        $this->pedidosRetrofitLoaded = true;
    }

    private function normalizeRetrofitControleStatus(?string $status): string
    {
        $normalized = trim((string) $status);

        if ($normalized === '') {
            return 'Rascunho';
        }

        return match (mb_strtolower($normalized)) {
            'rascunho' => 'Rascunho',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
            default => ucfirst(mb_strtolower($normalized)),
        };
    }

    public function loadRdos(): void
    {
        if ($this->rdosLoaded) {
            return;
        }

        $projectId = $this->record->constructin_project_id;

        if (! $projectId) {
            $this->rdosData = [];
            $this->rdosLoaded = true;

            return;
        }

        try {
            $service = new ConstructinService;
            $this->rdosData = $service->getRdosList($projectId);
        } catch (\Throwable $e) {
            Log::error('Falha ao carregar RDOs', ['msg' => $e->getMessage()]);
            $this->rdosData = [];
        }

        $this->rdosLoaded = true;

        $this->dispatch('rdos-preload', ids: collect($this->rdosData)
            ->slice(0, $this->rdosPerPage)
            ->pluck('id')
            ->values()
            ->all());
    }

    public function loadRdoDetail(int $rdoId): void
    {
        $projectId = $this->record->constructin_project_id;

        if (! $projectId) {
            return;
        }

        $key = (string) $rdoId;

        if (array_key_exists($key, $this->rdosDetalhes)) {
            return;
        }

        $this->rdosDetalhesCarregando[] = $rdoId;

        try {
            $service = new ConstructinService;
            $detalhe = $service->getRdoDetail($projectId, $rdoId);

            $activities = collect($detalhe['activities'] ?? [])
                ->map(fn ($a) => [
                    'name' => $a['name'] ?? $a['activity'] ?? $a['description'] ?? '—',
                    'percentage' => $a['percentage'] ?? null,
                    'status' => $a['status'] ?? null,
                ])->values()->all();

            $summary = collect([
                'morning_condition' => $detalhe['morning_condition'] ?? null,
                'morning_weather' => $detalhe['morning_weather'] ?? null,
                'afternoon_condition' => $detalhe['afternoon_condition'] ?? null,
                'afternoon_weather' => $detalhe['afternoon_weather'] ?? null,
                'night_condition' => $detalhe['night_condition'] ?? null,
                'night_weather' => $detalhe['night_weather'] ?? null,
                'comments' => $detalhe['comments'] ?? null,
            ])->filter(fn ($value) => filled($value))->all();

            $weather = collect([
                'Manhã' => $this->translateRdoValue(trim((string) ($detalhe['morning_condition'] ?? ''))) ?: null,
                'Manhã Clima' => $this->translateRdoValue(trim((string) ($detalhe['morning_weather'] ?? ''))) ?: null,
                'Tarde' => $this->translateRdoValue(trim((string) ($detalhe['afternoon_condition'] ?? ''))) ?: null,
                'Tarde Clima' => $this->translateRdoValue(trim((string) ($detalhe['afternoon_weather'] ?? ''))) ?: null,
                'Noite' => $this->translateRdoValue(trim((string) ($detalhe['night_condition'] ?? ''))) ?: null,
                'Noite Clima' => $this->translateRdoValue(trim((string) ($detalhe['night_weather'] ?? ''))) ?: null,
            ])->filter(fn ($value) => filled($value))->all();

            $manpower = collect([
                $detalhe['workers'] ?? null,
                $detalhe['mdo'] ?? null,
                $detalhe['mao_de_obra'] ?? null,
                $detalhe['mão_de_obra'] ?? null,
                $detalhe['manpower'] ?? null,
                $detalhe['workforce'] ?? null,
                $detalhe['team'] ?? null,
                $detalhe['labor'] ?? null,
                $detalhe['staff'] ?? null,
                $detalhe['crew'] ?? null,
                $detalhe['people'] ?? null,
            ])->filter(fn ($value) => filled($value))->first();

            $manpower = $this->normalizeRdoWorkforce($manpower);

            $equipment = collect([
                $detalhe['equipments'] ?? null,
                $detalhe['equipment'] ?? null,
                $detalhe['maquinas'] ?? null,
                $detalhe['máquinas'] ?? null,
                $detalhe['machines'] ?? null,
                $detalhe['machinery'] ?? null,
                $detalhe['tools'] ?? null,
                $detalhe['resources'] ?? null,
            ])->filter(fn ($value) => filled($value))->first();

            $audit = collect([
                'data_relatorio' => $this->auditDateValue(
                    $this->firstFilledRdoValue($detalhe, [
                        'report_date',
                        'date',
                        'reportDate',
                    ])
                ),
                'data_criacao' => $this->auditDateValue($this->firstFilledRdoValue($detalhe, [
                    'created_at',
                    'createdAt',
                    'created_date',
                    'createdDate',
                ])),
                'ultima_atualizacao' => $this->auditDateValue($this->firstFilledRdoValue($detalhe, [
                    'updated_at',
                    'updatedAt',
                    'modified_at',
                    'modifiedAt',
                ])),
                'criado_por' => $this->auditCreatedBy($detalhe),
                'aprovado_por' => $this->auditApprovedBy($detalhe),
                'aprovado_em' => $this->auditDateValue($this->firstFilledRdoValue($detalhe, [
                    'approved_at',
                    'approvedAt',
                    'approved_date',
                    'approvedDate',
                ])),
            ])->filter(fn ($value) => filled($value))->all();

            $averagePercentage = data_get($detalhe, 'averagePercentage');
            if ($averagePercentage === null) {
                $percentuais = collect($activities)->pluck('percentage')->filter(fn ($v) => is_numeric($v));
                $averagePercentage = $percentuais->isEmpty() ? null : round($percentuais->avg(), 1);
            }

            $this->rdosDetalhes[$key] = [
                'activities' => $activities,
                'summary' => $summary,
                'weather' => $weather,
                'manpower' => $manpower,
                'equipment' => $equipment,
                'audit' => $audit,
                'averagePercentage' => $averagePercentage,
            ];

            $this->rdosData = collect($this->rdosData)
                ->map(function (array $item) use ($rdoId, $activities, $summary, $weather, $manpower, $equipment, $audit, $averagePercentage) {
                    if ((int) ($item['id'] ?? 0) !== $rdoId) {
                        return $item;
                    }

                    return array_merge($item, [
                        'activities' => $activities,
                        'summary' => $summary,
                        'weather' => $weather,
                        'manpower' => $manpower,
                        'equipment' => $equipment,
                        'audit' => $audit,
                        'averagePercentage' => $averagePercentage,
                    ]);
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::error('Falha ao carregar detalhe do RDO', [
                'msg' => $e->getMessage(),
                'rdo_id' => $rdoId,
            ]);

            $this->rdosDetalhes[$key] = [
                'activities' => [],
                'summary' => [],
                'weather' => [],
                'manpower' => null,
                'equipment' => null,
                'audit' => [],
                'averagePercentage' => null,
            ];
        }

        $this->rdosDetalhesCarregando = array_values(array_filter(
            $this->rdosDetalhesCarregando,
            fn ($id) => (int) $id !== $rdoId
        ));
    }

    public function abrirModalRdo(int $rdoId): void
    {
        $this->loadRdoDetail($rdoId);

        $this->modalRdoId = $rdoId;
        $this->modalRdoData = $this->rdosDetalhes[(string) $rdoId] ?? null;
        $this->modalRdoOpen = true;
    }

    public function fecharModalRdo(): void
    {
        $this->modalRdoOpen = false;
        $this->modalRdoId = null;
        $this->modalRdoData = null;
    }

    public function irParaRdoPage(int $page): void
    {
        $totalPages = max(1, (int) ceil(count($this->rdosData) / max($this->rdosPerPage, 1)));
        $this->rdosPage = max(1, min($page, $totalPages));

        $this->dispatch('rdos-preload', ids: collect($this->rdosData)
            ->forPage($this->rdosPage, $this->rdosPerPage)
            ->pluck('id')
            ->values()
            ->all());
    }

    public function formatRdoValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            $isAssoc = array_keys($value) !== range(0, count($value) - 1);

            if (! $isAssoc) {
                return collect($value)
                    ->map(fn ($item) => $this->formatRdoValue($item))
                    ->filter(fn ($item) => filled($item))
                    ->implode(', ');
            }

            return collect($value)
                ->map(function ($item, $key) {
                    $formatted = $this->formatRdoValue($item);

                    return filled($formatted) ? "{$key}: {$formatted}" : null;
                })
                ->filter(fn ($item) => filled($item))
                ->implode(' | ');
        }

        if ($value instanceof \JsonSerializable) {
            return $this->formatRdoValue($value->jsonSerialize());
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function translateRdoLabel(string $label): string
    {
        $map = [
            'id' => 'ID',
            'name' => 'Nome',
            'quantity' => 'Quantidade',
            'status' => 'Status',
            'type' => 'Tipo',
            'description' => 'Descrição',
            'description_text' => 'Descrição',
            'worker' => 'Trabalhador',
            'workers' => 'Mão de Obra',
            'crew' => 'Equipe',
            'staff' => 'Equipe',
            'team' => 'Equipe',
            'workforce' => 'Equipe',
            'morning_condition' => 'Manhã',
            'morning_weather' => 'Manhã - Clima',
            'afternoon_condition' => 'Tarde',
            'afternoon_weather' => 'Tarde - Clima',
            'night_condition' => 'Noite',
            'night_weather' => 'Noite - Clima',
            'comments' => 'Observações',
        ];

        $key = Str::of($label)->snake()->lower()->value();

        return $map[$key] ?? Str::headline($label);
    }

    public function translateRdoValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $map = [
                'NON_VIABLE' => 'Não viável',
                'VIABLE' => 'Viável',
                'RAIN' => 'Chuva',
                'CLEAR_SKY' => 'Céu limpo',
                'PARTLY_CLOUDY' => 'Parcialmente nublado',
                'CLOUDY' => 'Nublado',
                'SUNNY' => 'Ensolarado',
                'IN_PROGRESS' => 'Em andamento',
                'DIRECT' => 'Direto',
                'DONE' => 'Concluído',
                'COMPLETED' => 'Concluído',
                'PENDING' => 'Pendente',
            ];

            return $map[strtoupper($value)] ?? $value;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => $this->translateRdoValue($item))
                ->all();
        }

        return $value;
    }

    public function normalizeRdoCards(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [[
                'title' => null,
                'fields' => [[
                    'label' => 'Detalhe',
                    'value' => $this->formatRdoValue($this->translateRdoValue($value)),
                ]],
            ]];
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);

        if ($isAssoc) {
            return [[
                'title' => null,
                'fields' => collect($value)
                    ->map(fn ($item, $key) => [
                        'label' => $this->translateRdoLabel((string) $key),
                        'value' => $this->formatRdoValue($this->translateRdoValue($item)),
                    ])
                    ->filter(fn ($row) => filled($row['value']))
                    ->values()
                    ->all(),
            ]];
        }

        return collect($value)
            ->map(function ($item, $index) {
                if (! is_array($item)) {
                    return [
                        'title' => null,
                        'fields' => [[
                            'label' => 'Item '.($index + 1),
                            'value' => $this->formatRdoValue($item),
                        ]],
                    ];
                }

                $title = $item['name']
                    ?? $item['title']
                    ?? $item['worker']
                    ?? data_get($item, 'equipment.name')
                    ?? data_get($item, 'equipment.title')
                    ?? data_get($item, 'equipment.description')
                    ?? data_get($item, 'equipment.description_text')
                    ?? $item['description']
                    ?? $item['description_text']
                    ?? null;

                return [
                    'title' => filled($title) ? $this->formatRdoValue($title) : null,
                    'type' => $this->formatRdoValue($this->translateRdoValue(data_get($item, 'type') ?? data_get($item, 'status'))) ?: 'Direto',
                    'quantity' => $this->formatRdoValue(data_get($item, 'quantity') ?? data_get($item, 'qty') ?? data_get($item, 'amount')),
                ];
            })
            ->filter(fn ($card) => filled($card['title']))
            ->values()
            ->all();
    }

    public function normalizeRdoWorkforce(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->map(function ($item) {
                if (is_array($item)) {
                    $title = $this->workforceLabelFromValue(
                        $item['name']
                            ?? $item['title']
                            ?? $item['worker']
                            ?? data_get($item, 'equipment.name')
                            ?? data_get($item, 'equipment.title')
                            ?? data_get($item, 'equipment.description')
                            ?? data_get($item, 'equipment.description_text')
                            ?? data_get($item, 'description')
                            ?? data_get($item, 'description_text')
                            ?? null
                    );

                    if (blank($title)) {
                        return null;
                    }

                    $type = $this->formatRdoValue($this->translateRdoValue(data_get($item, 'type') ?? data_get($item, 'status')));

                    return [
                        'title' => $this->formatRdoValue($this->translateRdoValue($title)),
                        'type' => filled($type) ? $type : 'Direto',
                        'quantity' => $this->formatRdoValue(data_get($item, 'quantity') ?? data_get($item, 'qty') ?? data_get($item, 'amount')),
                    ];
                }

                return [
                    'title' => $this->workforceLabelFromValue($item),
                    'type' => 'Direto',
                    'quantity' => null,
                ];
            })
            ->filter(fn ($row) => filled($row['title'] ?? null))
            ->values()
            ->all();
    }

    private function workforceLabelFromValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $text = $this->formatRdoValue($this->translateRdoValue($value));

        if (preg_match('/\bname:\s*([^|]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/\btitle:\s*([^|]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        if (str_contains($text, '|')) {
            $segments = collect(explode('|', $text))
                ->map(fn ($segment) => trim($segment))
                ->filter()
                ->values();

            $nameSegment = $segments->first(fn ($segment) => str_starts_with(strtolower($segment), 'name:'));
            if (filled($nameSegment)) {
                return trim((string) preg_replace('/^name:\s*/i', '', $nameSegment));
            }
        }

        return trim($text);
    }

    private function firstFilledRdoValue(array $payload, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function auditDateValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $this->formatRdoValue($value);
        }
    }

    private function auditCreatedBy(array $payload): ?string
    {
        $event = collect($payload['events'] ?? [])
            ->first(fn ($item) => strtoupper((string) data_get($item, 'type')) === 'CREATED');

        $name = trim(collect([
            data_get($event, 'user.name'),
            data_get($event, 'user.surname'),
        ])->filter()->implode(' '));

        if (filled($name)) {
            return $name;
        }

        return $this->firstFilledRdoValue($payload, [
            'created_by.name',
            'created_by.full_name',
            'author.name',
            'author.full_name',
            'user.name',
            'user.full_name',
        ]) ?? $this->firstFilledRdoValue($payload, [
            'created_by',
            'author',
            'user',
        ]);
    }

    private function auditApprovedBy(array $payload): ?string
    {
        $event = collect($payload['events'] ?? [])
            ->first(fn ($item) => strtoupper((string) data_get($item, 'type')) === 'APPROVED');

        $name = trim(collect([
            data_get($event, 'user.name'),
            data_get($event, 'user.surname'),
        ])->filter()->implode(' '));

        if (filled($name)) {
            return $name;
        }

        return $this->firstFilledRdoValue($payload, [
            'approved_by.name',
            'approved_by.full_name',
            'approvedBy.name',
            'approvedBy.full_name',
        ]) ?? $this->firstFilledRdoValue($payload, [
            'approved_by',
            'approvedBy',
        ]);
    }
}
