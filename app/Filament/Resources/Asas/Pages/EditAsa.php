<?php

namespace App\Filament\Resources\Asas\Pages;

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\User;
use App\Services\AsaService;
use App\Support\AsaAccess;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Renderless;
use Throwable;

class EditAsa extends EditRecord
{
    protected static string $resource = AsaResource::class;

    protected string $view = 'filament.resources.asas.pages.edit-asa';

    public bool $isAutosaving = false;

    public bool $pendingAutosave = false;

    public ?string $lastSavedStateHash = null;

    public ?string $contratoAntesDaNegociacaoShell = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->lastSavedStateHash = $this->generateDraftHash(
            $this->getDraftData(false),
        );
    }

    #[Renderless]
    public function autoSaveCurrentState(): void
    {
        if ($this->isAutosaving) {
            $this->pendingAutosave = true;

            return;
        }

        $this->isAutosaving = true;

        try {
            $record = $this->getRecord();

            if (! $record instanceof Asa) {
                return;
            }

            $data = $this->getDraftData(true);
            $currentHash = $this->generateDraftHash($data);

            if ($currentHash !== $this->lastSavedStateHash) {
                $record->update($this->mutateFormDataBeforeSave($data));

                /** @var AsaService $asaService */
                $asaService = app(AsaService::class);
                $asaService->normalizeMediaPaths($record);

                $this->lastSavedStateHash = $currentHash;
            }

            $this->dispatch('draft-autosaved');
        } catch (Throwable $exception) {
            logger()->error('Erro no autosave da ASA', [
                'record_id' => $this->record->id ?? null,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('draft-autosave-error');
        } finally {
            $this->isAutosaving = false;

            if ($this->pendingAutosave) {
                $this->pendingAutosave = false;
                $this->autoSaveCurrentState();
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['justificativa'] = $this->normalizeRichEditorContent($data['justificativa'] ?? null);
        $data['shell_cabe_como_negociacao'] = $this->normalizeShellCabeComoNegociacaoFormState($data['shell_cabe_como_negociacao'] ?? null);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User && AsaAccess::shouldRestrictEditingToDesconto($user)) {
            $data = array_intersect_key($data, array_flip([
                'desconto',
            ]));
        }

        if (array_key_exists('shell_cabe_como_negociacao', $data)) {
            $data['shell_cabe_como_negociacao'] = (bool) $data['shell_cabe_como_negociacao'];
        }

        $record = $this->getRecord();
        $valorBruto = (float) ($data['valor_bruto'] ?? $record?->valor_bruto ?? 0);
        $desconto = (float) ($data['desconto'] ?? $record?->desconto ?? 0);

        $data['valor_total'] = max($valorBruto - $desconto, 0);

        return $data;
    }

    private function normalizeShellCabeComoNegociacaoFormState(mixed $state): ?int
    {
        if ($state === null || $state === '') {
            return null;
        }

        return (int) (bool) $state;
    }

    private function normalizeRichEditorContent(mixed $content): ?string
    {
        if ($content === null) {
            return null;
        }

        if (! is_string($content)) {
            return (new HtmlString('<p>'.e((string) $content).'</p>'))->toHtml();
        }

        $trimmedContent = trim($content);

        if ($trimmedContent === '') {
            return $content;
        }

        if (str_starts_with($trimmedContent, '<')) {
            return $content;
        }

        if (is_numeric($trimmedContent)) {
            return (new HtmlString('<p>'.e($trimmedContent).'</p>'))->toHtml();
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    protected function persistDraftUploads(): void
    {
        foreach ($this->form->getFlatFields(withHidden: true) as $field) {
            if ($field instanceof BaseFileUpload) {
                $field->saveUploadedFiles();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDraftData(bool $persistUploads = true): array
    {
        if ($persistUploads) {
            $this->persistDraftUploads();
        }

        try {
            $data = $this->form->getRawState();
        } catch (Throwable) {
            $data = data_get($this, 'data', []);
        }

        if (! is_array($data)) {
            return [];
        }

        $fillable = array_flip((new Asa)->getFillable());
        $autosaveData = array_intersect_key($data, $fillable);

        foreach (['valor_bruto', 'desconto', 'valor_total'] as $moneyField) {
            if (array_key_exists($moneyField, $autosaveData)) {
                $autosaveData[$moneyField] = MoneyInput::parse($autosaveData[$moneyField]);
            }
        }

        $record = $this->getRecord();

        if (! array_key_exists('objeto', $autosaveData) || blank($autosaveData['objeto'] ?? null)) {
            $autosaveData['objeto'] = (string) ($autosaveData['descricao'] ?? $record?->objeto ?? '');
        }

        return $autosaveData;
    }

    protected function normalizeForHash(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeForHash($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForHash($item);
        }

        return $value;
    }

    protected function generateDraftHash(array $data): string
    {
        return md5(json_encode($this->normalizeForHash($data)));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aprovar')
                ->label('Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->canApprove())
                ->action(fn () => $this->approveFlow()),

            Action::make('reprovar')
                ->label('Reprovar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->canReject())
                ->form([
                    Textarea::make('justificativa_reprovacao')
                        ->label('Justificativa da reprovação')
                        ->required()
                        ->rows(4)
                        ->maxLength(3000),
                ])
                ->action(fn (array $data) => $this->rejectFlow($data['justificativa_reprovacao'])),

            $this->selecionarControleNotaFiscalAction(),
            $this->registrarNegociacaoShellAction(),
        ];
    }

    public function registrarNegociacaoShellAction(): Action
    {
        return Action::make('registrarNegociacaoShell')
            ->label('Negociação Shell')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('warning')
            ->visible(fn (): bool => $this->getRecord()?->contrato === 'Shell')
            ->extraAttributes(['class' => 'hidden'])
            ->modalHeading('Negociação Shell')
            ->modalDescription('Confirme se a alteração cabe como negociação e informe a justificativa.')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->fillForm(function (): array {
                $record = $this->getRecord();

                return [
                    'shell_cabe_como_negociacao' => filled($this->contratoAntesDaNegociacaoShell)
                        ? null
                        : $this->normalizeShellCabeComoNegociacaoFormState($record?->shell_cabe_como_negociacao),
                    'shell_justificativa_negociacao' => $record?->shell_justificativa_negociacao,
                ];
            })
            ->schema([
                ToggleButtons::make('shell_cabe_como_negociacao')
                    ->label('Cabe negociação com proprietário')
                    ->options([
                        1 => 'Sim',
                        0 => 'Não',
                    ])
                    ->colors([
                        1 => 'success',
                        0 => 'danger',
                    ])
                    ->inline()
                    ->required(),
                Textarea::make('shell_justificativa_negociacao')
                    ->label('Justifique')
                    ->required()
                    ->rows(4)
                    ->maxLength(3000),
            ])
            ->beforeFormValidated(function (array $mountedActions): void {
                $data = $mountedActions[0]?->getRawData() ?? [];
                $cabeNegociacao = $data['shell_cabe_como_negociacao'] ?? null;

                if ($cabeNegociacao === null || $cabeNegociacao === '' || blank($data['shell_justificativa_negociacao'] ?? null)) {
                    $this->restaurarContratoAnteriorDaNegociacaoShell();
                }
            })
            ->action(function (array $data): void {
                $this->getRecord()->update([
                    'contrato' => 'Shell',
                    'shell_cabe_como_negociacao' => (bool) ($data['shell_cabe_como_negociacao'] ?? false),
                    'shell_justificativa_negociacao' => filled($data['shell_justificativa_negociacao'] ?? null)
                        ? trim((string) $data['shell_justificativa_negociacao'])
                        : null,
                ]);

                $this->contratoAntesDaNegociacaoShell = null;
                $this->fillForm();
                $this->forceRender();
            });
    }

    public function registrarContratoAnteriorDaNegociacaoShell(?string $contrato): void
    {
        if (blank($contrato) || $contrato === 'Shell') {
            return;
        }

        $this->contratoAntesDaNegociacaoShell = $contrato;
    }

    protected function restaurarContratoAnteriorDaNegociacaoShell(): void
    {
        if (blank($this->contratoAntesDaNegociacaoShell)) {
            return;
        }

        $record = $this->getRecord();
        $record->update([
            'contrato' => $this->contratoAntesDaNegociacaoShell,
            'shell_cabe_como_negociacao' => false,
            'shell_justificativa_negociacao' => null,
        ]);

        $this->contratoAntesDaNegociacaoShell = null;
        $this->fillForm();
        $this->lastSavedStateHash = $this->generateDraftHash($this->getDraftData(false));
    }

    public function selecionarControleNotaFiscalAction(): Action
    {
        return Action::make('selecionarControleNotaFiscal')
            ->label('Selecionar controle')
            ->icon('heroicon-o-link')
            ->color('warning')
            ->visible(fn (): bool => $this->shouldExposeControleNotaFiscalResolutionActions() && $this->canUpdateControleNotaFiscal())
            ->modalHeading('Selecionar Controle de Notas Fiscais')
            ->modalDescription('Escolha o controle que deve receber a linha adicional desta ASA.')
            ->schema([
                Select::make('controle_nota_fiscal_id')
                    ->label('Controle de notas fiscais')
                    ->options(fn (): array => $this->getControleNotaFiscalSelectionOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data): void {
                abort_unless($this->canUpdateControleNotaFiscal(), 403);

                $controle = $this->getControleNotaFiscalSelectionQuery()
                    ->whereKey($data['controle_nota_fiscal_id'] ?? null)
                    ->first();

                if (! $controle instanceof ControleNotaFiscal) {
                    Notification::make()
                        ->title('Controle não encontrado')
                        ->body('Selecione um controle válido para vincular a ASA.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->syncAsaAuxiliarRow($controle, $this->getRecord());
            });
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function canApprove(): bool
    {
        $record = $this->getRecord();
        $user = Auth::user();

        if (! $record || ! $user) {
            return false;
        }

        $status = $this->normalizeStatus($record->status);

        if ($this->isGestorObras($user) && $status === 'solicitado') {
            return true;
        }

        return false;
    }

    protected function canReject(): bool
    {
        return $this->canApprove();
    }

    protected function approveFlow(array $data = []): void
    {
        abort_unless($this->canApprove(), 403);

        $user = Auth::user();
        $record = $this->getRecord();
        $status = $this->normalizeStatus($record->status);
        $aditivo = $record->elaboracaoAditivo;

        if ($this->isGestorObras($user) && $status === 'solicitado') {
            $origemSolicitacao = $data['origem_solicitacao'] ?? $record->contrato;
            $solicitante = $this->resolveSolicitanteFromAditivoAuthor($record);

            if (blank($origemSolicitacao) || blank($solicitante)) {
                Notification::make()
                    ->title('Preencha os campos obrigatórios')
                    ->body($this->missingApprovalFieldsMessage($origemSolicitacao, $solicitante))
                    ->danger()
                    ->send();

                return;
            }

            $asaService = app(AsaService::class);

            $record->update([
                'contrato' => $origemSolicitacao,
                'solicitante' => $solicitante,
                'controle_nota_fiscal_destino' => 'adicional',
                'numero_asa' => $asaService->gerarNumeroAsaParaAsa($record, $origemSolicitacao, $solicitante),
                'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
            ]);

            if ($aditivo) {
                $aditivo->update([
                    'status_fluxo' => 'em_aprovacao_orcamento',
                    'aprovado_gestor_por_id' => $user->id,
                    'aprovado_gestor_em' => now(),
                    'justificativa_reprovacao_gestor' => null,
                ]);
            }

            $controle = $this->findControleNotaFiscalForAsa($record);

            if ($controle instanceof ControleNotaFiscal) {
                $this->syncAsaAuxiliarRow($controle, $record, false);
            }

            $this->notifyOrcamentistaAprovacaoPendente($record);

            Notification::make()
                ->title('ASA aprovada pelo gestor e enviada para o orçamento.')
                ->success()
                ->send();

            return;
        }

    }

    protected function findControleNotaFiscalForAsa(Asa $asa): ?ControleNotaFiscal
    {
        $aditivo = $asa->elaboracaoAditivo;
        if (! $aditivo?->obra_id) {
            $this->notifyControleNotaFiscalResolutionRequired(
                $asa,
                'Controle de ampliação não localizado',
                'A ASA foi aprovada, mas não foi possível identificar a unidade para localizar o controle de notas de ampliação.'
            );

            return null;
        }

        $controles = ControleNotaFiscal::query()
            ->where('obra_id', $aditivo->obra_id)
            ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->orderByDesc('id')
            ->get();

        if ($controles->isEmpty()) {
            $this->notifyControleNotaFiscalResolutionRequired(
                $asa,
                'Controle de ampliação não localizado',
                'A ASA foi aprovada, mas não foi possível localizar automaticamente o controle de notas de ampliação da unidade. Selecione o controle existente.'
            );

            return null;
        }

        if ($controles->count() > 1) {
            $this->notifyControleNotaFiscalResolutionRequired(
                $asa,
                'Múltiplos controles encontrados',
                'A ASA foi aprovada, mas há mais de um controle de ampliação para a unidade. Selecione o controle correto.',
                'danger'
            );

            return null;
        }

        return $controles->first();
    }

    protected function syncAsaAuxiliarRow(ControleNotaFiscal $controle, Asa $asa, bool $notify = true): void
    {
        $asa->loadMissing('elaboracaoAditivo.construtora');

        $grupo = $this->resolveAuxiliarGrupoParaAsa($asa);
        $numeroAs = (string) $asa->codigo_as_emitida;

        $auxiliar = $controle->auxiliares()
            ->when(
                filled($numeroAs),
                fn (Builder $query): Builder => $query
                    ->where('numero_as', $numeroAs)
                    ->whereIn('grupo', $this->resolveAuxiliarGrupoCandidates($grupo)),
                fn (Builder $query): Builder => $query->whereIn('grupo', $this->resolveAuxiliarGrupoCandidates($grupo)),
            )
            ->first();

        if (! $auxiliar instanceof ControleNotaFiscalAuxiliar && filled($asa->contrato)) {
            $auxiliar = $controle->auxiliares()
                ->whereIn('grupo', $this->resolveAuxiliarGrupoCandidates($grupo))
                ->first();
        }

        if (! $auxiliar instanceof ControleNotaFiscalAuxiliar && filled($asa->descricao)) {
            $auxiliar = $controle->auxiliares()
                ->where('grupo', $grupo)
                ->where('escopo', $asa->descricao)
                ->first();
        }

        $sortOrder = $auxiliar?->sort_order
            ?? ((int) $controle->auxiliares()->max('sort_order') + 1);
        $escopoPadrao = $this->resolveEscopoPadraoParaAsa($asa, $grupo);

        if (! $auxiliar instanceof ControleNotaFiscalAuxiliar) {
            $auxiliar = new ControleNotaFiscalAuxiliar([
                'controle_nota_fiscal_id' => $controle->id,
            ]);
        }

        $valorGlobal = round((float) ($asa->valor_total ?? 0), 2);
        $valorAcumulado = (float) $asa->notasFiscais()
            ->where('status', 'aprovado')
            ->sum('valor_acumulado_medido_nf');
        $saldo = max(round($valorGlobal - $valorAcumulado, 2), 0.0);
        $percentuaisFaturamento = app(AsaService::class)->percentuaisFaturamentoPorAditivo($asa);

        $auxiliar->fill([
            'grupo' => $grupo,
            'numero_as' => $asa->codigo_as_emitida,
            'escopo' => $asa->descricao,
            'empresa' => $auxiliar->empresa ?: $asa->elaboracaoAditivo?->construtora?->nome,
            'percentual_total' => $auxiliar->percentual_total ?? 100,
            'percentual_faturamento_mao_obra' => $percentuaisFaturamento['mao_obra'] ?? $auxiliar->percentual_faturamento_mao_obra ?? $escopoPadrao?->percentual_faturamento_mao_obra_default ?? 60,
            'percentual_faturamento_material' => $percentuaisFaturamento['material'] ?? $auxiliar->percentual_faturamento_material ?? $escopoPadrao?->percentual_faturamento_material_default ?? 40,
            'valor_global_a' => $valorGlobal,
            'total_medicao_a_menos_b' => $saldo,
            'valor_acumulado_medido' => $valorAcumulado,
            'saldo' => $saldo,
            'observacoes' => $auxiliar->observacoes,
            'sort_order' => $sortOrder,
        ]);

        $auxiliar->save();

        if ((int) $asa->controle_nota_fiscal_auxiliar_id !== (int) $auxiliar->id) {
            $asa->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();
        }

        if (! $notify) {
            return;
        }

        Notification::make()
            ->title('Linha adicional vinculada ao controle')
            ->body('A ASA aprovada foi vinculada à tabela de itens extra contratuais do controle existente.')
            ->success()
            ->actions([
                Action::make('visualizarControle')
                    ->label('Visualizar controle')
                    ->url(ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]))
                    ->markAsRead(),
            ])
            ->send();
    }

    protected function notifyOrcamentistaAprovacaoPendente(Asa $asa): void
    {
        $asa->loadMissing('projeto.responsavel', 'elaboracaoAditivo.obra');

        $orcamentista = $asa->projeto?->responsavel;

        if (! $orcamentista instanceof User) {
            return;
        }

        $unidade = $asa->elaboracaoAditivo?->obra?->unidade
            ?? $asa->projeto?->nome
            ?? '-';
        $mensagem = 'A ASA '.$asa->numero_asa.' da unidade '.$unidade.' foi aprovada pelo gestor e aguarda aprovação do orçamentista.';

        Notification::make()
            ->title('ASA aguardando aprovação do orçamento')
            ->body($mensagem)
            ->icon('heroicon-o-document-check')
            ->iconColor('warning')
            ->actions([
                Action::make('abrir')
                    ->label('Aprovar ASA')
                    ->url(AutorizacaoServicoResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($orcamentista);

        if (filled($orcamentista->email)) {
            Mail::to($orcamentista->email)
                ->send(new EnviarPdfMail(
                    assunto: 'ASA aguardando aprovação do orçamento '.$asa->numero_asa,
                    mensagemEmail: '<p>'.e($mensagem).'</p>',
                    pdfBinary: '',
                    nomeArquivo: '',
                ));
        }
    }

    protected function resolveEscopoPadraoParaAsa(Asa $asa, string $grupo): ?AsEscopo
    {
        $grupos = $this->resolveAuxiliarGrupoCandidates($grupo);

        $query = AsEscopo::query()
            ->globais()
            ->where('is_active', true)
            ->whereIn('grupo', $grupos);

        if (filled($asa->descricao)) {
            $escopoPorDescricao = (clone $query)
                ->where('escopo', $asa->descricao)
                ->first();

            if ($escopoPorDescricao instanceof AsEscopo) {
                return $escopoPorDescricao;
            }
        }

        return $query
            ->orderBy('numero_as')
            ->first();
    }

    protected function resolveAuxiliarGrupoParaAsa(Asa $asa): string
    {
        $grupoOrigem = trim((string) ($asa->contrato ?? ''));
        $grupoNormalizado = ControleNotaFiscalAuxiliar::normalizeGrupo($grupoOrigem);

        return $grupoNormalizado ?: $grupoOrigem;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveAuxiliarGrupoCandidates(?string $grupo): array
    {
        $grupoOriginal = trim((string) $grupo);
        $grupoNormalizado = trim((string) ControleNotaFiscalAuxiliar::normalizeGrupo($grupoOriginal));

        if ($grupoNormalizado !== '' && $grupoNormalizado !== $grupoOriginal) {
            return array_values(array_unique([$grupoOriginal, $grupoNormalizado]));
        }

        if ($grupoOriginal !== '') {
            return [$grupoOriginal];
        }

        return [];
    }

    protected function shouldExposeControleNotaFiscalResolutionActions(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Asa
            && $this->normalizeStatus($record->status) === 'aprovado'
            && filled($record->elaboracao_aditivo_id)
            && ! $this->hasResolvedControleNotaFiscalForAsa($record);
    }

    protected function canUpdateControleNotaFiscal(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->can('Update:ControleNotaFiscal');
    }

    protected function getControleNotaFiscalSelectionQuery(): Builder
    {
        $record = $this->getRecord();
        $aditivo = $record->elaboracaoAditivo;

        return ControleNotaFiscalResource::getEloquentQuery()
            ->where('obra_id', $aditivo?->obra_id)
            ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->with(['obra:id,projeto_id,unidade', 'obra.projeto:id,sigla'])
            ->orderByDesc('id');
    }

    protected function getControleNotaFiscalSelectionOptions(): array
    {
        return $this->getControleNotaFiscalSelectionQuery()
            ->get(['id', 'obra_id', 'status', 'unidade', 'sigla'])
            ->mapWithKeys(function (ControleNotaFiscal $controle): array {
                $unidade = $controle->obra?->unidade ?? $controle->unidade ?? '-';
                $sigla = $controle->sigla ?? $controle->obra?->sigla ?? '-';

                return [
                    $controle->id => sprintf(
                        '#%d · %s / %s · %s',
                        $controle->id,
                        $unidade,
                        $sigla,
                        $controle->status ?? 'sem status',
                    ),
                ];
            })
            ->all();
    }

    protected function hasResolvedControleNotaFiscalForAsa(Asa $asa): bool
    {
        return filled($asa->controle_nota_fiscal_auxiliar_id);
    }

    protected function notifyControleNotaFiscalResolutionRequired(Asa $asa, string $title, string $body, string $status = 'warning'): void
    {
        $urlBase = static::getResource()::getUrl('edit', ['record' => $asa]);

        Notification::make()
            ->title($title)
            ->body($body)
            ->{$status}()
            ->persistent()
            ->actions(array_values(array_filter([
                $this->canUpdateControleNotaFiscal()
                    ? Action::make('selecionarControle')
                        ->label('Selecionar controle')
                        ->url($urlBase.'?action=selecionarControleNotaFiscal')
                        ->markAsRead()
                    : null,
            ])))
            ->send();
    }

    protected function rejectFlow(string $justificativa): void
    {
        abort_unless($this->canReject(), 403);

        $user = Auth::user();
        $record = $this->getRecord();
        $status = $this->normalizeStatus($record->status);
        $aditivo = $record->elaboracaoAditivo;

        if ($this->isGestorObras($user) && $status === 'solicitado') {
            $record->update([
                'status' => AsStatus::REPROVADO_GESTOR,
                'data_aprovacao' => null,
            ]);

            if ($aditivo) {
                $aditivo->update([
                    'status_fluxo' => 'reprovado_gestor',
                    'justificativa_reprovacao_gestor' => $justificativa,
                ]);
            }

            Notification::make()
                ->title('ASA reprovada pelo gestor.')
                ->success()
                ->send();

            return;
        }

    }

    protected function isGestorObras($user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['Gestor', 'gestor'])) {
            return true;
        }

        return $user->hasRole('Colaborador')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    protected function isOrcamento($user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['coordenador_orcamento', 'colaborador_orcamento'])) {
            return true;
        }

        return $user->setores()
            ->whereRaw('LOWER(setor) in (?, ?, ?, ?)', ['orçamento', 'orcamento', 'orçamentos', 'orcamentos'])
            ->exists();
    }

    protected function normalizeStatus(AsStatus|string|null $status): string
    {
        if ($status instanceof AsStatus) {
            return $status->value;
        }

        return trim(mb_strtolower((string) $status));
    }

    protected function resolveSolicitanteFromAditivoAuthor(Asa $asa): string
    {
        $asa->loadMissing('elaboracaoAditivo.user');

        $aditivo = $asa->elaboracaoAditivo;
        $nomeAutor = trim((string) ($aditivo?->user?->name ?? ''));

        if ($nomeAutor !== '') {
            return $nomeAutor;
        }

        if (filled($aditivo?->user_id)) {
            return 'Usuario #'.$aditivo->user_id;
        }

        $solicitanteAtual = trim((string) ($asa->solicitante ?? ''));

        if ($solicitanteAtual !== '') {
            return $solicitanteAtual;
        }

        return 'Autor do aditivo #'.($aditivo?->id ?? $asa->elaboracao_aditivo_id ?? $asa->id);
    }

    protected function missingApprovalFieldsMessage(mixed $origemSolicitacao, mixed $solicitante): string
    {
        $messages = [];

        if (blank($origemSolicitacao)) {
            $messages[] = 'Informe a origem da solicitação da ASA antes de aprovar.';
        }

        if (blank($solicitante)) {
            $messages[] = 'Não foi possível identificar o usuário autor do aditivo para preencher o solicitante da ASA.';
        }

        return implode(' ', $messages);
    }
}
