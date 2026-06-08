<?php

namespace App\Filament\Pages;

use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use App\Services\ControleNotaFiscal\ControleNotaFiscalAgenteNotificationService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AprovacaoNotasFiscaisPage extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-badge';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?string $navigationLabel = 'Aprovação de Notas Fiscais';

    protected static ?string $title = 'Aprovação de Notas Fiscais';

    protected static ?string $slug = 'aprovacao-notas-fiscais';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.aprovacao-notas-fiscais-page';

    public ?string $selectedControleId = null;

    public array $notasVisualizadas = [];

    public ?array $selectedControleSummary = null;

    public function mount(): void
    {
        $this->notasVisualizadas = session()->get($this->getNotasVisualizadasSessionKey(), []);

        $controleId = request()->integer('controle');

        if ($controleId > 0) {
            $this->selectControle($controleId, shouldNotify: false);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getControlesPendentesQuery())
            ->queryStringIdentifier('controlesPendentes')
            ->recordAction(null)
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->defaultSort('obra.unidade', 'asc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('obra.codigo')
                    ->label('Código obra')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('obra.unidade')
                    ->label('Unidade')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('linhas_pendentes')
                    ->label('Escopos com pendências')
                    ->state(fn (ControleNotaFiscal $record): int => $this->getControleLinhasPendentes($record))
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('obras_pendentes')
                    ->label('Notas pendentes')
                    ->state(fn (ControleNotaFiscal $record): int => $this->getControleNotasPendentes($record))
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('selecionar')
                    ->label(fn (ControleNotaFiscal $record): string => (int) $this->selectedControleId === (int) $record->id ? 'Selecionado' : 'Selecionar')
                    ->icon('heroicon-o-eye')
                    ->color(fn (ControleNotaFiscal $record): string => (int) $this->selectedControleId === (int) $record->id ? 'success' : 'primary')
                    ->action(fn (ControleNotaFiscal $record): mixed => $this->selectControle((int) $record->id)),
            ])
            ->emptyStateHeading('Nenhum controle com nota pendente.')
            ->emptyStateDescription('Quando houver notas em análise, os controles aparecerão aqui.');
    }

    protected function getNotasVisualizadasSessionKey(): string
    {
        return 'aprovacao_notas_fiscais.notas_visualizadas.'.(Auth::id() ?? 'guest');
    }

    protected function notaFoiVisualizada(int $notaId): bool
    {
        return in_array($notaId, $this->notasVisualizadas, true);
    }

    protected function marcarNotaComoVisualizadaInternamente(int $notaId): void
    {
        if ($this->notaFoiVisualizada($notaId)) {
            return;
        }

        $this->notasVisualizadas[] = $notaId;
        $this->notasVisualizadas = array_values(array_unique(array_map('intval', $this->notasVisualizadas)));

        session()->put($this->getNotasVisualizadasSessionKey(), $this->notasVisualizadas);
    }

    public function marcarNotaComoVisualizada(int $notaId): void
    {
        $notaExiste = ControleNotaFiscalNota::query()
            ->whereKey($notaId)
            ->where('status', StatusControleNotaFiscalNota::EM_ANALISE->value)
            ->exists();

        if (! $notaExiste) {
            return;
        }

        $this->marcarNotaComoVisualizadaInternamente($notaId);
    }

    public function selectControle(int $controleId, bool $shouldNotify = true): void
    {
        $controle = $this->getControleResumoQuery()
            ->find($controleId);

        if (! $controle) {
            Notification::make()
                ->title('Controle não encontrado')
                ->danger()
                ->send();

            return;
        }

        $this->selectedControleId = (string) $controle->id;
        $this->selectedControleSummary = $this->buildControleSummary($controle);

        $this->loadData();

        if ($shouldNotify) {
            Notification::make()
                ->title('Controle selecionado')
                ->success()
                ->send();
        }
    }

    public function limparSelecaoControle(): void
    {
        $this->selectedControleId = null;
        $this->selectedControleSummary = null;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('View:AprovacaoNotasFiscaisPage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function canProcessNotas(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('Update:ControleNotaFiscalNota');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = ControleNotaFiscalNota::query()
            ->where('status', StatusControleNotaFiscalNota::EM_ANALISE->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function loadData(): void
    {
        if (blank($this->selectedControleId)) {
            return;
        }

        $controle = $this->getControleResumoQuery()->find((int) $this->selectedControleId);

        if (! $controle) {
            $this->selectedControleId = null;
            $this->selectedControleSummary = null;

            return;
        }

        $this->selectedControleSummary = $this->buildControleSummary($controle);
    }

    protected function getControleResumoQuery(): Builder
    {
        return ControleNotaFiscal::query()
            ->with([
                'obra' => fn ($query) => $query
                    ->select('id', 'codigo', 'unidade', 'projeto_id')
                    ->with('projeto:id,sigla,nova_sigla'),
                'itens' => fn ($query) => $query
                    ->with('notasFiscais')
                    ->withCount([
                        'notasFiscais as notas_pendentes_count' => fn ($notaQuery) => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value),
                    ]),
                'auxiliares' => fn ($query) => $query
                    ->with('notasFiscais')
                    ->withCount([
                        'notasFiscais as notas_pendentes_count' => fn ($notaQuery) => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value),
                    ]),
            ]);
    }

    protected function buildControleSummary(ControleNotaFiscal $controle): array
    {
        $linhas = $controle->itens->concat($controle->auxiliares);
        $saldoTotal = $linhas->sum(fn ($linha): float => $this->calculateControleLineSaldo($linha));

        return [
            'unidade' => filled($controle->obra?->unidade) ? (string) $controle->obra?->unidade : (filled($controle->unidade) ? (string) $controle->unidade : '-'),
            'sigla' => filled($controle->obra?->sigla) ? (string) $controle->obra?->sigla : (filled($controle->sigla) ? (string) $controle->sigla : '-'),
            'valor_global' => (float) $linhas->sum(fn ($linha): float => (float) ($linha->valor_global_a ?? 0)),
            'saldo_total' => (float) $saldoTotal,
            'linhas_pendentes' => $this->getControleLinhasPendentes($controle),
            'notas_pendentes' => $this->getControleNotasPendentes($controle),
        ];
    }

    protected function calculateControleLineSaldo(mixed $linha): float
    {
        $valorTotal = (float) ($linha->valor_global_a ?? 0);

        $notas = collect($linha->notasFiscais ?? []);
        $notasComImpactoNoSaldo = $notas->whereIn('status', StatusControleNotaFiscalNota::comImpactoNoSaldo());
        $acumuladoDireto = $notasComImpactoNoSaldo
            ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
            ->sum(fn (ControleNotaFiscalNota $nota): float => (float) ($nota->valor_acumulado_medido_nf ?? 0));
        $acumuladoIndireto = $notasComImpactoNoSaldo
            ->whereIn('tipo_medicao', ControleNotaFiscalNota::tiposMaterialBucket())
            ->sum(fn (ControleNotaFiscalNota $nota): float => (float) ($nota->valor_acumulado_medido_nf ?? 0));

        return $valorTotal - ($acumuladoDireto + $acumuladoIndireto);
    }

    public function calculatePendingRowMetrics(array $row): array
    {
        $valorTotal = (float) ($row['valor_global_a'] ?? 0);
        $percentualDireto = (float) ($row['percentual_faturamento_mao_obra'] ?? 60);
        $percentualIndireto = (float) ($row['percentual_faturamento_material'] ?? 40);

        $notasContexto = collect($row['notas_contexto'] ?? []);
        $statusComImpactoNoSaldo = StatusControleNotaFiscalNota::comImpactoNoSaldo();

        $acumuladoDireto = $notasContexto
            ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
            ->whereIn('status', $statusComImpactoNoSaldo)
            ->sum(fn ($nota): float => (float) ($nota->valor_acumulado_medido_nf ?? 0));

        $acumuladoIndireto = $notasContexto
            ->whereIn('tipo_medicao', ControleNotaFiscalNota::tiposMaterialBucket())
            ->whereIn('status', $statusComImpactoNoSaldo)
            ->sum(fn ($nota): float => (float) ($nota->valor_acumulado_medido_nf ?? 0));

        $faturamentoDiretoTotal = $valorTotal * ($percentualDireto / 100);
        $faturamentoIndiretoTotal = $valorTotal * ($percentualIndireto / 100);
        $acumuladoTotal = $acumuladoDireto + $acumuladoIndireto;
        $saldoGeral = $valorTotal - $acumuladoTotal;

        return [
            'faturamento_direto_total' => $faturamentoDiretoTotal,
            'faturamento_indireto_total' => $faturamentoIndiretoTotal,
            'total_medicao_a_menos_b' => $acumuladoTotal,
            'saldo_geral' => $saldoGeral,
            'percentual_saldo_geral' => $valorTotal > 0 ? ($saldoGeral / $valorTotal) * 100 : 0.0,
            'saldo_direto' => $faturamentoDiretoTotal - $acumuladoDireto,
            'saldo_indireto' => $faturamentoIndiretoTotal - $acumuladoIndireto,
        ];
    }

    public function getLinhasPendentesProperty(): Collection
    {
        return $this->buildLinhasPendentes(
            (clone $this->baseNotasQuery())
                ->where('status', StatusControleNotaFiscalNota::EM_ANALISE->value)
                ->orderBy('updated_at')
                ->get()
        );
    }

    protected function baseNotasQuery(): Builder
    {
        $query = ControleNotaFiscalNota::query()
            ->where(function ($builder): void {
                $builder
                    ->whereNotNull('autorizacao_servico_id')
                    ->orWhereNotNull('autorizacao_servico_adicional_id');
            })
            ->with([
                'importadoPor:id,name',
                'decididoPor:id,name',
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra:id,codigo,unidade',
                'autorizacaoServico.notasFiscais',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra:id,codigo,unidade',
                'asa.notasFiscais',
            ]);

        if (blank($this->selectedControleId)) {
            return $query->whereRaw('1 = 0');
        }

        $controleId = (int) $this->selectedControleId;

        $query->where(function (Builder $builder) use ($controleId): void {
            $builder
                ->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal', fn (Builder $itemBuilder): Builder => $itemBuilder->whereKey($controleId))
                ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal', fn (Builder $auxBuilder): Builder => $auxBuilder->whereKey($controleId));
        });

        return $query;
    }

    protected function getControlesPendentesQuery(): Builder
    {
        return ControleNotaFiscal::query()
            ->with('obra:id,codigo,unidade')
            ->with([
                'itens' => fn ($query) => $query->withCount([
                    'notasFiscais as notas_pendentes_count' => fn ($notaQuery) => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value),
                ]),
                'auxiliares' => fn ($query) => $query->withCount([
                    'notasFiscais as notas_pendentes_count' => fn ($notaQuery) => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value),
                ]),
            ])
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('itens.notasFiscais', fn (Builder $notaQuery): Builder => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value))
                    ->orWhereHas('auxiliares.notasFiscais', fn (Builder $notaQuery): Builder => $notaQuery->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::EM_ANALISE->value));
            });
    }

    protected function getControleLinhasPendentes(ControleNotaFiscal $controle): int
    {
        $itens = $controle->itens;
        $auxiliares = $controle->auxiliares;

        return (int) $itens->filter(fn ($item): bool => (int) ($item->notas_pendentes_count ?? 0) > 0)->count()
            + (int) $auxiliares->filter(fn ($auxiliar): bool => (int) ($auxiliar->notas_pendentes_count ?? 0) > 0)->count();
    }

    protected function getControleNotasPendentes(ControleNotaFiscal $controle): int
    {
        return (int) $controle->itens->sum('notas_pendentes_count')
            + (int) $controle->auxiliares->sum('notas_pendentes_count');
    }

    protected function refreshAfterDecision(): void
    {
        $this->loadData();
        $this->resetTable();
    }

    protected function selectedControleHasPendingNotas(): bool
    {
        if (blank($this->selectedControleId)) {
            return false;
        }

        return $this->getControlesPendentesQuery()
            ->whereKey((int) $this->selectedControleId)
            ->exists();
    }

    protected function buildLinhasPendentes(Collection $notasPendentes): Collection
    {
        return $notasPendentes
            ->filter(fn (ControleNotaFiscalNota $nota): bool => $this->itemDaNota($nota) instanceof ControleNotaFiscalItem || $this->auxiliarDaNota($nota) instanceof ControleNotaFiscalAuxiliar)
            ->groupBy(function (ControleNotaFiscalNota $nota): string {
                $item = $this->itemDaNota($nota);
                $auxiliar = $this->auxiliarDaNota($nota);

                return $item instanceof ControleNotaFiscalItem
                    ? 'item:'.$item->id
                    : 'auxiliar:'.$auxiliar?->id;
            })
            ->map(function (Collection $grupoNotas): array {
                /** @var ControleNotaFiscalNota $referencia */
                $referencia = $grupoNotas->first();
                $row = $referencia->isAdicional()
                    ? $this->mapAuxiliarApprovalRow($referencia)
                    : $this->mapItemApprovalRow($referencia);

                $notasDoEscopo = $referencia->isAdicional()
                    ? $this->notasDaLinha($this->auxiliarDaNota($referencia))
                    : $this->notasDaLinha($this->itemDaNota($referencia));

                return [
                    ...$row,
                    'notas_mao_obra' => $grupoNotas
                        ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
                        ->values()
                        ->map(fn (ControleNotaFiscalNota $nota): array => $this->mapApprovalRowNota($nota))
                        ->all(),
                    'notas_material' => $grupoNotas
                        ->whereIn('tipo_medicao', ControleNotaFiscalNota::tiposMaterialBucket())
                        ->values()
                        ->map(fn (ControleNotaFiscalNota $nota): array => $this->mapApprovalRowNota($nota))
                        ->all(),
                    'pendencias' => $grupoNotas->count(),
                    'notas_mao_obra_todas' => $notasDoEscopo
                        ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
                        ->values()
                        ->map(fn (ControleNotaFiscalNota $nota): array => $this->mapApprovalRowNota($nota))
                        ->all(),
                    'notas_material_todas' => $notasDoEscopo
                        ->whereIn('tipo_medicao', ControleNotaFiscalNota::tiposMaterialBucket())
                        ->values()
                        ->map(fn (ControleNotaFiscalNota $nota): array => $this->mapApprovalRowNota($nota))
                        ->all(),
                    'ultima_atualizacao' => $grupoNotas
                        ->max(fn (ControleNotaFiscalNota $nota): int => (int) ($nota->updated_at?->timestamp ?? 0)),
                ];
            })
            ->sortBy([
                fn (array $row): string => (string) ($row['unidade'] ?? ''),
                fn (array $row): string => (string) ($row['grupo'] ?? ''),
                fn (array $row): string => (string) ($row['numero_as'] ?? ''),
                fn (array $row): string => (string) ($row['escopo'] ?? ''),
            ])
            ->values();
    }

    protected function mapItemApprovalRow(ControleNotaFiscalNota $nota): array
    {
        $item = $this->itemDaNota($nota);
        $controle = $item?->controleNotaFiscal;

        return [
            'id' => $item?->id,
            'source' => 'item',
            'unidade' => (string) ($controle?->obra?->unidade ?? '-'),
            'grupo' => (string) ($item?->grupo ?? $item?->asEscopo?->grupo ?? ''),
            'numero_as' => (string) ($item?->numero_as ?? ''),
            'numero_complemento' => (string) ($item?->numero_complemento ?? ''),
            'escopo' => (string) ($item?->escopo ?? $item?->asEscopo?->escopo ?? ''),
            'empresa' => (string) ($item?->empresa ?? '-'),
            'percentual_faturamento_mao_obra' => (float) ($item?->percentual_faturamento_mao_obra ?? 60),
            'percentual_faturamento_material' => (float) ($item?->percentual_faturamento_material ?? 40),
            'valor_global_a' => (float) ($item?->valor_global_a ?? 0),
            'controle_url' => $controle ? ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]) : null,
            'notas_contexto' => $this->notasDaLinha($item),
        ];
    }

    protected function mapAuxiliarApprovalRow(ControleNotaFiscalNota $nota): array
    {
        $auxiliar = $this->auxiliarDaNota($nota);
        $controle = $auxiliar?->controleNotaFiscal;

        return [
            'id' => $auxiliar?->id,
            'source' => 'auxiliar',
            'unidade' => (string) ($controle?->obra?->unidade ?? '-'),
            'grupo' => (string) ($auxiliar?->grupo ?? ''),
            'numero_as' => (string) ($auxiliar?->numero_as ?? ''),
            'numero_complemento' => '',
            'escopo' => (string) ($auxiliar?->escopo ?: $auxiliar?->grupo ?: ''),
            'empresa' => (string) ($auxiliar?->empresa ?? '-'),
            'percentual_faturamento_mao_obra' => (float) ($auxiliar?->percentual_faturamento_mao_obra ?? 60),
            'percentual_faturamento_material' => (float) ($auxiliar?->percentual_faturamento_material ?? 40),
            'valor_global_a' => (float) ($auxiliar?->valor_global_a ?? 0),
            'controle_url' => $controle ? ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]) : null,
            'notas_contexto' => $this->notasDaLinha($auxiliar),
        ];
    }

    protected function itemDaNota(ControleNotaFiscalNota $nota): ?ControleNotaFiscalItem
    {
        return $nota->itemDerivado();
    }

    protected function auxiliarDaNota(ControleNotaFiscalNota $nota): ?ControleNotaFiscalAuxiliar
    {
        return $nota->auxiliarDerivado();
    }

    protected function controleFiscalDaNota(ControleNotaFiscalNota $nota): ?ControleNotaFiscal
    {
        return $this->itemDaNota($nota)?->controleNotaFiscal
            ?? $this->auxiliarDaNota($nota)?->controleNotaFiscal;
    }

    protected function controleFiscalEstaEncerrado(ControleNotaFiscalNota $nota): bool
    {
        return $this->controleFiscalDaNota($nota)?->status === ControleNotaFiscal::STATUS_ENCERRADO;
    }

    protected function notasDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar|null $linha): Collection
    {
        if (! $linha instanceof ControleNotaFiscalItem && ! $linha instanceof ControleNotaFiscalAuxiliar) {
            return collect();
        }

        return $linha->relationLoaded('notasFiscais')
            ? $linha->notasFiscais
            : $linha->notasFiscais()->get();
    }

    protected function mapApprovalRowNota(ControleNotaFiscalNota $nota): array
    {
        return [
            'id' => $nota->id,
            'empresa' => (string) ($nota->empresa ?? '-'),
            'cnpj_fornecedor' => (string) ($nota->cnpj_fornecedor ?? '-'),
            'numero_nf' => (string) ($nota->numero_nf ?? '-'),
            'valor' => (float) ($nota->valor_acumulado_medido_nf ?? 0),
            'emissao' => $nota->emissao?->format('d/m/Y') ?? '-',
            'recebimento' => $nota->recebimento?->format('d/m/Y') ?? '-',
            'envio' => $nota->envio?->format('d/m/Y') ?? '-',
            'status' => (string) ($nota->status ?? ''),
            'status_label' => ControleNotaFiscalNota::getStatusLabel($nota->status),
            'status_tone' => StatusControleNotaFiscalNota::tryFrom((string) $nota->status)?->color() ?? 'neutral',
            'observacoes' => (string) ($nota->observacoes ?? ''),
            'arquivo_url' => ControleNotaFiscalNota::getFileUrl($nota->arquivo_path),
            'boleto_url' => ControleNotaFiscalNota::getFileUrl($nota->boleto_path),
            'foi_visualizada' => $this->notaFoiVisualizada((int) $nota->id),
            'is_pendente' => $nota->status === StatusControleNotaFiscalNota::EM_ANALISE->value,
        ];
    }

    public function aprovar(int $id): void
    {
        if (! $this->canProcessNotas()) {
            Notification::make()
                ->title('Você não tem permissão para aprovar notas fiscais')
                ->danger()
                ->send();

            return;
        }

        $nota = ControleNotaFiscalNota::query()->findOrFail($id);

        if ($nota->status !== StatusControleNotaFiscalNota::EM_ANALISE->value) {
            Notification::make()
                ->title('Esta nota fiscal já foi processada')
                ->warning()
                ->send();

            $this->refreshAfterDecision();

            return;
        }

        if (! $this->notaFoiVisualizada((int) $nota->id)) {
            Notification::make()
                ->title('Visualize a nota fiscal antes de aprovar')
                ->warning()
                ->send();

            return;
        }

        if ($this->controleFiscalEstaEncerrado($nota)) {
            Notification::make()
                ->title('Controle de nota fiscal encerrado')
                ->body('Não é possível aprovar notas fiscais deste controle.')
                ->danger()
                ->send();

            return;
        }

        $nota->update([
            'status' => StatusControleNotaFiscalNota::APROVADO->value,
            'decidido_por_id' => Auth::id(),
            'decidido_em' => now(),
        ]);

        $this->recalcularSaldoRelacionado($nota->refresh());
        app(ControleNotaFiscalAgenteNotificationService::class)->notificarNotaDecidida($nota);

        $this->notasVisualizadas = array_values(array_filter(
            $this->notasVisualizadas,
            fn (int $notaId): bool => $notaId !== (int) $nota->id
        ));
        session()->put($this->getNotasVisualizadasSessionKey(), $this->notasVisualizadas);

        $this->refreshAfterDecision();

        if (! $this->selectedControleHasPendingNotas()) {
            $this->limparSelecaoControle();
        }

        Notification::make()
            ->title('Nota fiscal aprovada')
            ->success()
            ->send();
    }

    public function reprovar(int $id): void
    {
        if (! $this->canProcessNotas()) {
            Notification::make()
                ->title('Você não tem permissão para reprovar notas fiscais')
                ->danger()
                ->send();

            return;
        }

        $nota = ControleNotaFiscalNota::query()->findOrFail($id);

        if ($nota->status !== StatusControleNotaFiscalNota::EM_ANALISE->value) {
            Notification::make()
                ->title('Esta nota fiscal já foi processada')
                ->warning()
                ->send();

            $this->refreshAfterDecision();

            return;
        }

        if ($this->controleFiscalEstaEncerrado($nota)) {
            Notification::make()
                ->title('Controle de nota fiscal encerrado')
                ->body('Não é possível reprovar notas fiscais deste controle.')
                ->danger()
                ->send();

            return;
        }

        $nota->update([
            'status' => StatusControleNotaFiscalNota::REPROVADO->value,
            'decidido_por_id' => Auth::id(),
            'decidido_em' => now(),
        ]);

        $this->recalcularSaldoRelacionado($nota->refresh());
        app(ControleNotaFiscalAgenteNotificationService::class)->notificarNotaDecidida($nota);

        $this->refreshAfterDecision();

        Notification::make()
            ->title('Nota fiscal reprovada')
            ->warning()
            ->send();
    }

    protected function recalcularSaldoRelacionado(ControleNotaFiscalNota $nota): void
    {
        $target = $this->itemDaNota($nota) ?? $this->auxiliarDaNota($nota);

        if (! $target instanceof ControleNotaFiscalItem && ! $target instanceof ControleNotaFiscalAuxiliar) {
            return;
        }

        $valorAcumuladoMedido = (float) $this->notasDaLinha($target)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');

        $target->updateQuietly([
            'total_medicao_a_menos_b' => $valorAcumuladoMedido,
            'valor_acumulado_medido' => $valorAcumuladoMedido,
            'saldo' => (float) $target->valor_global_a - $valorAcumuladoMedido,
        ]);
    }
}
