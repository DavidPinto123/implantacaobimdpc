<?php

namespace App\Filament\Pages;

use App\Enums\AsStatus;
use App\Enums\ModoSaldoFiscal;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Services\ControleNotaFiscal\ControleNotaFiscalSaldoService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class ConstrutoraControlesNotaFiscalPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $navigationLabel = 'Meus controles de NF';

    protected static ?string $title = 'Meus controles de NF';

    protected static ?string $slug = 'construtora-controles-nota-fiscal';

    protected static ?int $navigationSort = 31;

    protected string $view = 'filament.pages.construtora-controles-nota-fiscal-page';

    public ?string $selectedObraId = null;

    public ?string $selectedControleId = null;

    /**
     * @var array<int, string>
     */
    public array $obraOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sheetRows = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $selectedControleResumo = null;

    public ?ControleNotaFiscal $selectedControleRecord = null;

    protected ControleNotaFiscalSaldoService $saldoService;

    protected ?array $eligibleDestinoIds = null;

    public function boot(ControleNotaFiscalSaldoService $saldoService): void
    {
        $this->saldoService = $saldoService;
    }

    public function mount(): void
    {
        $this->refreshData();

        $this->form->fill([
            'selectedObraId' => $this->selectedObraId,
        ]);
    }

    public function updatedSelectedObraId(): void
    {
        $this->refreshData();
    }

    public function selecionarControle(int $controleId): void
    {
        $this->selectedControleId = (string) $controleId;
        $this->refreshData();
    }

    public function limparControle(): void
    {
        $this->selectedControleId = null;
        $this->refreshData();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedObraId')
                    ->label('Unidade')
                    ->options(fn (): array => $this->obraOptions)
                    ->placeholder('Todas as unidades')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live(),
            ])
            ->statePath('');
    }

    protected function refreshData(): void
    {
        $this->obraOptions = $this->buildObraOptions();

        $controles = $this->getFilteredControles()->values();

        if (filled($this->selectedObraId) && $controles->count() === 1) {
            $this->selectedControleId = (string) $controles->first()->id;
        } elseif (
            $this->selectedControleId !== null
            && ! $controles->contains(fn (ControleNotaFiscal $controle): bool => (int) $controle->id === (int) $this->selectedControleId)
        ) {
            $this->selectedControleId = null;
        }

        $controleSelecionado = $this->selectedControleId === null
            ? null
            : $controles->first(fn (ControleNotaFiscal $controle): bool => (int) $controle->id === (int) $this->selectedControleId);

        if (! $controleSelecionado instanceof ControleNotaFiscal) {
            $this->selectedControleRecord = null;
            $this->selectedControleResumo = null;
            $this->sheetRows = [];

            return;
        }

        $this->selectedControleRecord = $controleSelecionado;
        $this->selectedControleResumo = $this->buildSelectedControleResumo($controleSelecionado);
        $this->sheetRows = $this->buildSheetRows($controleSelecionado);
    }

    /**
     * @return array<int, string>
     */
    protected function buildObraOptions(): array
    {
        return $this->getEligibleControlesQuery(withRelations: false)
            ->with('obra:id,codigo,unidade')
            ->get()
            ->pluck('obra')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($obra) => mb_strtolower((string) $obra->unidade))
            ->mapWithKeys(function ($obra): array {
                $unidade = trim((string) ($obra->unidade ?? ''));

                return [$obra->id => ($unidade !== '' ? $unidade : 'Obra #'.$obra->id)];
            })
            ->all();
    }

    /**
     * @return Collection<int, ControleNotaFiscal>
     */
    protected function getFilteredControles(): Collection
    {
        return $this->getEligibleControlesQuery(withRelations: true)
            ->when(
                filled($this->selectedObraId),
                fn (Builder $query): Builder => $query->where('obra_id', (int) $this->selectedObraId)
            )
            ->get();
    }

    protected function getEligibleControlesQuery(bool $withRelations): Builder
    {
        $eligibleDestinoIds = $this->getEligibleDestinoIds();

        if ($eligibleDestinoIds['controle_ids'] === []) {
            return ControleNotaFiscal::query()->whereRaw('1 = 0');
        }

        $query = ControleNotaFiscal::query()
            ->whereKey($eligibleDestinoIds['controle_ids'])
            ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)
            ->with([
                'obra' => fn ($query) => $query
                    ->select('id', 'codigo', 'unidade', 'projeto_id')
                    ->with('projeto:id,sigla,nova_sigla'),
            ])
            ->orderBy('obra_id')
            ->orderByDesc('id');

        if (! $withRelations) {
            return $query;
        }

        return $query->with([
            'itens' => fn ($itemQuery) => $itemQuery
                ->whereIn('id', $eligibleDestinoIds['item_ids'])
                ->orderBy('sort_order')
                ->orderBy('id'),
            'auxiliares' => fn ($auxiliarQuery) => $auxiliarQuery
                ->whereIn('id', $eligibleDestinoIds['auxiliar_ids'])
                ->with('asas')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);
    }

    /**
     * @return array{controle_ids: array<int, int>, item_ids: array<int, int>, auxiliar_ids: array<int, int>}
     */
    protected function getEligibleDestinoIds(): array
    {
        if ($this->eligibleDestinoIds !== null) {
            return $this->eligibleDestinoIds;
        }

        $construtoraId = auth()->user()?->construtoras_id;
        $construtoraNome = $this->getConstrutoraNome();
        $construtoraNomeNormalizado = mb_strtolower($construtoraNome);

        if (! filled($construtoraId) || $construtoraNome === '') {
            return $this->eligibleDestinoIds = [
                'controle_ids' => [],
                'item_ids' => [],
                'auxiliar_ids' => [],
            ];
        }

        $itemIds = AutorizacaoServico::query()
            ->where('construtora_id', $construtoraId)
            ->where('status', AsStatus::ENVIADA->value)
            ->whereHas('controleNotaFiscalItem', fn (Builder $itemQuery): Builder => $itemQuery
                ->whereNotNull('liberado_para_fornecedor_at')
                ->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)))
            ->pluck('controle_nota_fiscal_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $auxiliarIds = Asa::query()
            ->whereIn('status', [AsStatus::APROVADO->value, AsStatus::CRIADA->value, AsStatus::ENVIADA->value])
            ->whereHas('controleNotaFiscalAuxiliar', fn (Builder $auxiliarQuery): Builder => $this->applyReleasedEmpresaMatch($auxiliarQuery, $construtoraNomeNormalizado)
                ->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)))
            ->pluck('controle_nota_fiscal_auxiliar_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $itemIds = collect($itemIds)->unique()->values()->all();
        $auxiliarIds = collect($auxiliarIds)->unique()->values()->all();

        $controleIds = ControleNotaFiscalItem::query()
            ->whereIn('id', $itemIds)
            ->pluck('controle_nota_fiscal_id')
            ->merge(ControleNotaFiscalAuxiliar::query()
                ->whereIn('id', $auxiliarIds)
                ->pluck('controle_nota_fiscal_id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $this->eligibleDestinoIds = [
            'controle_ids' => $controleIds,
            'item_ids' => $itemIds,
            'auxiliar_ids' => $auxiliarIds,
        ];
    }

    protected function getConstrutoraNome(): string
    {
        $user = auth()->user();

        return trim((string) ($user?->construtora?->nome ?? ''));
    }

    protected function applyEmpresaMatch(Builder $query, string $construtoraNomeNormalizado): Builder
    {
        return $query->whereRaw('LOWER(TRIM(empresa)) = ?', [$construtoraNomeNormalizado]);
    }

    protected function applyReleasedEmpresaMatch(Builder $query, string $construtoraNomeNormalizado): Builder
    {
        return $this->applyEmpresaMatch($query, $construtoraNomeNormalizado)
            ->whereNotNull('liberado_para_fornecedor_at');
    }

    protected function applyItemEscopoEnviado(Builder $query): Builder
    {
        return $query
            ->whereHas('autorizacaoServico', fn (Builder $asQuery): Builder => $asQuery->where('status', AsStatus::ENVIADA->value));
    }

    public static function hasConstrutoraContext(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('Fornecedor')
            && filled($user->construtoras_id)
            && filled($user->construtora?->nome);
    }

    protected function buildControleLabel(ControleNotaFiscal $controle): string
    {
        $obraCodigo = trim((string) ($controle->obra?->codigo ?? ''));
        $obraUnidade = trim((string) ($controle->obra?->unidade ?? $controle->unidade ?? ''));
        $sigla = trim((string) ($controle->sigla ?? ''));

        return trim(
            collect([
                $obraCodigo !== '' ? $obraCodigo : null,
                $obraUnidade !== '' ? $obraUnidade : null,
                $sigla !== '' ? 'Sigla '.$sigla : null,
            ])->filter()->implode(' - ')
        ) ?: 'Controle #'.$controle->id;
    }

    /**
     * @return Collection<int, ControleNotaFiscalItem|ControleNotaFiscalAuxiliar>
     */
    protected function getControleLinhas(ControleNotaFiscal $controle): Collection
    {
        return $controle->itens->concat($controle->auxiliares)->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSelectedControleResumo(ControleNotaFiscal $controle): array
    {
        $linhas = $this->getControleLinhas($controle);

        return [
            'id' => $controle->id,
            'label' => $this->buildControleLabel($controle),
            'unidade' => (string) ($controle->obra?->unidade ?? $controle->unidade ?? '-'),
            'sigla' => (string) ($controle->sigla ?? '-'),
            'valor_global_total' => (float) $linhas->sum(fn ($linha): float => (float) ($linha->valor_global_a ?? 0)),
            'saldo_total' => (float) $linhas->sum(fn ($linha): float => $this->saldoComprometidoDaLinha($linha)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSheetRows(ControleNotaFiscal $controle): array
    {
        $rows = $controle->itens
            ->map(fn (ControleNotaFiscalItem $item): array => $this->buildItemRow($item, $controle))
            ->values()
            ->all();

        foreach ($controle->auxiliares as $auxiliar) {
            $rows[] = $this->buildAuxiliarRow($auxiliar, $controle);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildItemRow(ControleNotaFiscalItem $item, ControleNotaFiscal $controle): array
    {
        $notas = $this->notasDaLinha($item);
        $saldo = $this->saldoComprometidoDaLinha($item);
        $valorAcumulado = $this->valorAcumuladoDaLinha($item, $saldo);

        return [
            'id' => $item->id,
            'source' => 'item',
            'as_escopo_id' => $item->as_escopo_id,
            'grupo' => (string) ($item->grupo ?? ''),
            'numero_as' => (string) ($item->numero_as ?? ''),
            'numero_complemento' => (string) ($item->numero_complemento ?? ''),
            'escopo_complementar' => (string) ($item->escopo_complementar ?? ''),
            'escopo' => (string) ($item->escopo ?? ''),
            'empresa' => (string) ($item->empresa ?? ''),
            'percentual_total' => (string) ($item->percentual_total ?? '100'),
            'percentual_faturamento_mao_obra' => (string) ($item->percentual_faturamento_mao_obra ?? '60'),
            'percentual_faturamento_material' => (string) ($item->percentual_faturamento_material ?? '40'),
            'valor_global_a' => (string) $item->valor_global_a,
            'total_medicao_a_menos_b' => (string) $valorAcumulado,
            'valor_acumulado_medido' => (string) $valorAcumulado,
            'saldo' => (string) $saldo,
            'observacoes' => (string) ($item->observacoes ?? ''),
            'importacao_url' => $controle->status === ControleNotaFiscal::STATUS_ENCERRADO
                ? null
                : $this->resolveImportacaoUrlForItem($controle, $item),
            'expanded' => false,
            'notas_mao_obra' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)),
            'notas_material' => $this->mapNotas($notas->whereIn('tipo_medicao', [ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL, ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE])),
            'notas_transporte' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuxiliarRow(ControleNotaFiscalAuxiliar $auxiliar, ControleNotaFiscal $controle): array
    {
        $grupo = ControleNotaFiscalAuxiliar::normalizeGrupo($auxiliar->grupo) ?? (string) ($auxiliar->grupo ?? '');
        $escopo = ControleNotaFiscalAuxiliar::normalizeGrupo($auxiliar->escopo) ?? (string) ($auxiliar->escopo ?: $grupo);
        $notas = $this->notasDaLinha($auxiliar);
        $saldo = $this->saldoComprometidoDaLinha($auxiliar);
        $valorAcumulado = $this->valorAcumuladoDaLinha($auxiliar, $saldo);

        return [
            'id' => $auxiliar->id,
            'source' => 'auxiliar',
            'as_escopo_id' => null,
            'grupo' => $grupo,
            'numero_as' => (string) ($auxiliar->numero_as ?? ''),
            'escopo' => $escopo,
            'empresa' => (string) ($auxiliar->empresa ?? ''),
            'percentual_total' => (string) ($auxiliar->percentual_total ?? '100'),
            'percentual_faturamento_mao_obra' => (string) ($auxiliar->percentual_faturamento_mao_obra ?? '60'),
            'percentual_faturamento_material' => (string) ($auxiliar->percentual_faturamento_material ?? '40'),
            'valor_global_a' => (string) $auxiliar->valor_global_a,
            'total_medicao_a_menos_b' => (string) $valorAcumulado,
            'valor_acumulado_medido' => (string) $valorAcumulado,
            'saldo' => (string) $saldo,
            'observacoes' => (string) ($auxiliar->observacoes ?? ''),
            'importacao_url' => $controle->status === ControleNotaFiscal::STATUS_ENCERRADO
                ? null
                : $this->resolveImportacaoUrlForAuxiliar($controle, $auxiliar),
            'expanded' => false,
            'notas_mao_obra' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)),
            'notas_material' => $this->mapNotas($notas->whereIn('tipo_medicao', [ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL, ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE])),
            'notas_transporte' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE)),
        ];
    }

    protected function saldoComprometidoDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $linha): float
    {
        if ($linha instanceof ControleNotaFiscalItem) {
            $autorizacaoServico = AutorizacaoServico::query()
                ->where('controle_nota_fiscal_item_id', $linha->id)
                ->latest('id')
                ->first();

            if ($autorizacaoServico instanceof AutorizacaoServico) {
                return $this->saldoService->saldoParaAs($autorizacaoServico, ModoSaldoFiscal::Comprometido);
            }
        } else {
            $asa = $linha->asas()
                ->whereIn('status', [AsStatus::APROVADO->value, AsStatus::CRIADA->value, AsStatus::ENVIADA->value])
                ->latest('id')
                ->first();

            if ($asa instanceof Asa) {
                return $this->saldoService->saldoParaAsa($asa, ModoSaldoFiscal::Comprometido);
            }
        }

        $valorBase = (float) ($linha->valor_global_a ?? 0);
        $valorComprometido = (float) $this->notasDaLinha($linha)
            ->whereIn('status', StatusControleNotaFiscalNota::comImpactoNoSaldo())
            ->sum('valor_acumulado_medido_nf');

        return max(round($valorBase - $valorComprometido, 2), 0.0);
    }

    protected function valorAcumuladoDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $linha, float $saldo): float
    {
        return max(round((float) ($linha->valor_global_a ?? 0) - $saldo, 2), 0.0);
    }

    protected function notasDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $linha): Collection
    {
        if ($linha instanceof ControleNotaFiscalItem) {
            return ControleNotaFiscalNota::query()
                ->whereHas('autorizacaoServico', fn (Builder $asQuery): Builder => $asQuery->where('controle_nota_fiscal_item_id', $linha->id))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return ControleNotaFiscalNota::query()
            ->whereHas('asa', fn (Builder $asaQuery): Builder => $asaQuery->where('controle_nota_fiscal_auxiliar_id', $linha->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function resolveImportacaoUrlForItem(ControleNotaFiscal $controle, ControleNotaFiscalItem $item): ?string
    {
        if (! filled($controle->obra_id) || ! filled($item->as_escopo_id)) {
            return null;
        }

        $user = auth()->user();

        if (! $user || ! filled($user->construtoras_id)) {
            return null;
        }

        $authorizationQuery = AutorizacaoServico::query()
            ->where('obra_id', $controle->obra_id)
            ->where('construtora_id', $user->construtoras_id)
            ->where('controle_nota_fiscal_item_id', $item->id);
        $authorizationQuery->where('status', AsStatus::ENVIADA->value);

        if (filled($item->numero_complemento)) {
            $authorizationQuery->where('numero_complemento', $item->numero_complemento);
        }

        $authorization = $authorizationQuery
            ->orderByDesc('id')
            ->first();

        if (! $authorization instanceof AutorizacaoServico) {
            return null;
        }

        return ImportacaoNotaFiscalResource::getUrl('create', [
            'obra_id_lookup' => $controle->obra_id,
            'asa_id_lookup' => $authorization->id,
        ]);
    }

    protected function resolveImportacaoUrlForAuxiliar(ControleNotaFiscal $controle, ControleNotaFiscalAuxiliar $auxiliar): ?string
    {
        if (! filled($controle->obra_id)) {
            return null;
        }

        $user = auth()->user();

        if (! $user || ! filled($user->construtoras_id)) {
            return null;
        }

        $asa = Asa::query()
            ->where('controle_nota_fiscal_auxiliar_id', $auxiliar->id)
            ->whereIn('status', [AsStatus::APROVADO->value, AsStatus::CRIADA->value, AsStatus::ENVIADA->value])
            ->latest('id')
            ->first();

        if (! $asa instanceof Asa) {
            return null;
        }

        return ImportacaoNotaFiscalResource::getUrl('create', [
            'obra_id_lookup' => $controle->obra_id,
            'asa_id_lookup' => 'asa:'.$asa->id,
        ]);
    }

    /**
     * @param  Collection<int, ControleNotaFiscalNota>  $notas
     * @return array<int, array<string, mixed>>
     */
    protected function mapNotas(Collection $notas): array
    {
        return $notas
            ->map(fn (ControleNotaFiscalNota $nota): array => [
                'id' => $nota->id,
                'empresa' => (string) ($nota->empresa ?? ''),
                'numero_nf' => (string) ($nota->numero_nf ?? ''),
                'cnpj_faturamento' => (string) ($nota->cnpj_faturamento ?? ''),
                'valor_acumulado_medido_nf' => (string) $nota->valor_acumulado_medido_nf,
                'emissao' => $nota->emissao?->format('Y-m-d') ?? '',
                'recebimento' => $nota->recebimento?->format('Y-m-d') ?? '',
                'envio' => $nota->envio?->format('Y-m-d') ?? '',
                'status' => (string) ($nota->status ?? ''),
                'arquivo_path' => (string) ($nota->arquivo_path ?? ''),
                'observacoes' => (string) ($nota->observacoes ?? ''),
            ])
            ->values()
            ->all();
    }
}
