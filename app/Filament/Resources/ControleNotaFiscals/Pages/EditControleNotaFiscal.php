<?php

namespace App\Filament\Resources\ControleNotaFiscals\Pages;

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EditControleNotaFiscal extends EditRecord
{
    protected static string $resource = ControleNotaFiscalResource::class;

    protected string $view = 'filament.resources.controle-nota-fiscals.pages.view-controle-nota-fiscal';

    public bool $isEditable = false;

    public array $sheetRows = [];

    /**
     * Keep expand/collapse state separate from sheetRows to avoid being overwritten by wire:model updates.
     *
     * @var array<int, bool>
     */
    public array $expandedRows = [];

    public function getTitle(): string
    {
        return 'Controle de notas fiscais';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->isEditable = false;
        $this->sheetRows = $this->buildSheetRows();
        $this->expandedRows = [];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        abort(403);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()) || static::getResource()::canEdit($this->getRecord()), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('encerrarControleNotaFiscal')
                ->label('Encerrar o controle de nota fiscal')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Encerrar controle de nota fiscal')
                ->modalDescription('Depois de encerrado, este controle não aceitará novas notas fiscais, criação/envio de AS ou ASA e aprovação de notas relacionadas.')
                ->visible(fn (): bool => auth()->user()?->can(ControleNotaFiscal::PERMISSION_CLOSE) === true
                    && $this->record->status !== ControleNotaFiscal::STATUS_ENCERRADO)
                ->action(function (): void {
                    $this->record->update([
                        'status' => ControleNotaFiscal::STATUS_ENCERRADO,
                    ]);

                    $this->record->refresh();
                    $this->sheetRows = $this->buildSheetRows();

                    Notification::make()
                        ->title('Controle de nota fiscal encerrado')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function buildSheetRows(): array
    {
        $this->record->load([
            'itens' => fn ($query) => $query
                ->whereExists(function ($subQuery): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('autorizacao_servicos')
                        ->whereColumn('autorizacao_servicos.controle_nota_fiscal_item_id', 'controle_nota_fiscal_items.id')
                        ->where('autorizacao_servicos.status', AsStatus::ENVIADA->value);
                })
                ->with('autorizacaoServico'),
            'auxiliares' => fn ($query) => $query
                ->whereHas('asas', fn (Builder $asaQuery): Builder => $asaQuery->whereIn('status', [
                    AsStatus::APROVADO->value,
                    AsStatus::CRIADA->value,
                    AsStatus::ENVIADA->value,
                ]))
                ->with('asas'),
        ]);

        $rows = $this->record->itens
            ->map(fn (ControleNotaFiscalItem $item): array => $this->buildItemRow($item))
            ->values()
            ->all();

        foreach ($this->record->auxiliares as $auxiliar) {
            $rows[] = $this->buildAuxiliarRow($auxiliar);
        }

        return $rows;
    }

    protected function applyAuxiliarAutorizacaoEnviada(Builder $query): Builder
    {
        return $query->whereExists(function ($subQuery): void {
            $subQuery
                ->selectRaw('1')
                ->from('autorizacao_servicos')
                ->whereColumn('autorizacao_servicos.numero_as', 'controle_nota_fiscal_auxiliares.numero_as')
                ->where('autorizacao_servicos.status', AsStatus::ENVIADA->value);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildItemRow(ControleNotaFiscalItem $item): array
    {
        $numeroAsComplemento = $this->normalizarNumeroAsComplemento($item->numero_as, $item->numero_complemento);
        $notas = $this->notasDaLinha($item);
        $valorAcumulado = $this->valorRealizadoDasNotas($notas);
        $saldo = $this->saldoRealizadoDaLinha($item, $valorAcumulado);

        return [
            'id' => $item->id,
            'source' => 'item',
            'as_escopo_id' => $item->as_escopo_id,
            'grupo' => (string) ($item->grupo ?? ''),
            'numero_as' => $numeroAsComplemento['numero_as'],
            'numero_complemento' => $numeroAsComplemento['numero_complemento'],
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
            'liberado_para_fornecedor_at' => $item->liberado_para_fornecedor_at?->toDateTimeString(),
            'expanded' => false,
            'notas_mao_obra' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)),
            'notas_material' => $this->mapNotas($notas->whereIn('tipo_medicao', [ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL, ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE])),
            'notas_transporte' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuxiliarRow(ControleNotaFiscalAuxiliar $auxiliar): array
    {
        $grupo = ControleNotaFiscalAuxiliar::normalizeGrupo($auxiliar->grupo) ?? (string) ($auxiliar->grupo ?? '');
        $escopo = ControleNotaFiscalAuxiliar::normalizeGrupo($auxiliar->escopo) ?? (string) ($auxiliar->escopo ?: $grupo);
        $numeroAsComplemento = $this->normalizarNumeroAsComplemento($auxiliar->numero_as, null);
        $notas = $this->notasDaLinha($auxiliar);
        $valorAcumulado = $this->valorRealizadoDasNotas($notas);
        $saldo = $this->saldoRealizadoDaLinha($auxiliar, $valorAcumulado);

        return [
            'id' => $auxiliar->id,
            'source' => 'auxiliar',
            'as_escopo_id' => null,
            'grupo' => $grupo,
            'numero_as' => $numeroAsComplemento['numero_as'],
            'numero_complemento' => $numeroAsComplemento['numero_complemento'],
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
            'liberado_para_fornecedor_at' => $auxiliar->liberado_para_fornecedor_at?->toDateTimeString(),
            'expanded' => false,
            'notas_mao_obra' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)),
            'notas_material' => $this->mapNotas($notas->whereIn('tipo_medicao', [ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL, ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE])),
            'notas_transporte' => $this->mapNotas($notas->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE)),
        ];
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

    protected function valorRealizadoDasNotas(Collection $notas): float
    {
        return (float) $notas
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');
    }

    protected function saldoRealizadoDaLinha(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $linha, float $valorAcumulado): float
    {
        return max(round((float) ($linha->valor_global_a ?? 0) - $valorAcumulado, 2), 0.0);
    }

    /**
     * @return array{numero_as: string, numero_complemento: string}
     */
    protected function normalizarNumeroAsComplemento(?string $numeroAs, ?string $numeroComplemento): array
    {
        $numeroAs = trim((string) $numeroAs);
        $numeroComplemento = trim((string) $numeroComplemento);

        if (str_contains($numeroAs, '/')) {
            [$numeroAsBase, $complementoEmbutido] = array_pad(explode('/', $numeroAs, 2), 2, '');
            $numeroAs = trim($numeroAsBase);

            if ($numeroComplemento === '') {
                $numeroComplemento = trim($complementoEmbutido);
            }
        }

        return [
            'numero_as' => $numeroAs,
            'numero_complemento' => $numeroComplemento,
        ];
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
