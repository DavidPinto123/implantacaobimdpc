<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Pages;

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\Banco;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use App\Services\ControleNotaFiscal\ControleNotaFiscalNotaService;
use App\Support\Cnpj;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateImportacaoNotaFiscal extends CreateRecord
{
    protected static string $resource = 'App\\Filament\\Resources\\ImportacaoNotaFiscals\\ImportacaoNotaFiscalResource';

    protected string $view = 'filament.resources.importacao-nota-fiscals.pages.create-importacao-nota-fiscal';

    public ?string $saldoInsuficienteMensagem = null;

    public function mount(): void
    {
        parent::mount();

        $this->prefillLookupFieldsFromRequest();
    }

    public function getTitle(): string
    {
        return 'Importar Nota Fiscal';
    }

    public function getBreadcrumbs(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            if (ConstrutoraControlesNotaFiscalPage::canAccess()) {
                return [
                    ConstrutoraControlesNotaFiscalPage::getUrl() => 'Importação De Notas Fiscais',
                    '#' => 'Criar',
                ];
            }
        }

        return parent::getBreadcrumbs();
    }

    protected function prefillLookupFieldsFromRequest(): void
    {
        $obraId = request()->query('obra_id_lookup');
        $asaId = request()->query('asa_id_lookup');

        if (! filled($obraId) && ! filled($asaId)) {
            return;
        }

        $this->form->fill(array_filter([
            'obra_id_lookup' => filled($obraId) ? (string) $obraId : null,
            'asa_id_lookup' => filled($asaId) ? (string) $asaId : null,
        ], fn (mixed $value): bool => $value !== null));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? StatusControleNotaFiscalNota::EM_ANALISE->value;
        $data['envio'] = now()->toDateString();

        $this->validarCnpjsDistintos($data);

        $tipoPagamento = $data['instrucoes_pagamento'] ?? null;

        if ($tipoPagamento === 'boleto_bancario') {
            if (blank($data['boleto_path'] ?? null)) {
                throw ValidationException::withMessages([
                    'data.boleto_path' => 'Informe o arquivo do boleto quando selecionar a opção "Boleto Bancário".',
                ]);
            }
        } else {
            unset($data['boleto_path'], $data['data_vencimento_boleto']);
        }

        if (in_array($tipoPagamento, ['transferencia', 'dados_bancarios'], true)) {
            $this->validarDadosTransferencia($data);
            $data = $this->preencherBancoTransferencia($data);
        } else {
            unset($data['banco'], $data['banco_codigo'], $data['agencia'], $data['conta_corrente']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function normalizeNotaFiscalData(array $data): array
    {
        $user = Auth::user();

        $data['importado_por_id'] = $user instanceof User ? $user->id : null;

        // Para Construtora: `asa:<id>` representa uma ASA vinculada diretamente ao auxiliar fiscal.
        if (
            $user instanceof User
            && $user->hasRole('Fornecedor')
            && filled($user->construtoras_id)
            && $this->isAsaLookup($data['asa_id_lookup'] ?? null)
        ) {
            $obraId = $data['obra_id_lookup'] ?? null;
            $asa = $this->resolveAsa($data['asa_id_lookup'] ?? null);

            if (! filled($obraId)) {
                throw ValidationException::withMessages([
                    'obra_id_lookup' => 'Selecione uma obra válida para importar a nota fiscal.',
                ]);
            }

            $auxiliar = $asa?->controleNotaFiscalAuxiliar;

            if (! $asa instanceof Asa || ! $auxiliar instanceof ControleNotaFiscalAuxiliar) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Selecione uma ASA válida para importar a nota fiscal.',
                ]);
            }

            if ((int) ($auxiliar->controleNotaFiscal?->obra_id ?? 0) !== (int) $obraId) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Não foi possível localizar o escopo desta ASA dentro do controle selecionado.',
                ]);
            }

            $construtora = Construtora::query()->find($user->construtoras_id);

            if (! $construtora || trim((string) $auxiliar->empresa) !== trim((string) $construtora->nome)) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Você não pode importar nota fiscal para outro fornecedor.',
                ]);
            }

            unset(
                $data['obra_id_lookup'],
                $data['asa_id_lookup'],
                $data['controle_nota_fiscal_id'],
                $data['tipo_nota_fiscal_destino']
            );

            $this->ensureControleNotaFiscalAberto($auxiliar->controleNotaFiscal);

            $data['autorizacao_servico_id'] = null;
            $data['autorizacao_servico_adicional_id'] = $asa->id;

            return $data;
        }

        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            $obraId = $data['obra_id_lookup'] ?? null;
            $autorizacaoId = $data['asa_id_lookup'] ?? null;

            if (! filled($obraId)) {
                throw ValidationException::withMessages([
                    'obra_id_lookup' => 'Selecione uma obra vÃ¡lida para importar a nota fiscal.',
                ]);
            }

            if (! filled($autorizacaoId)) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Selecione um nÃºmero de AS/ASA vÃ¡lido para importar a nota fiscal.',
                ]);
            }

            $autorizacao = AutorizacaoServico::query()
                ->with('asEscopo')
                ->find($autorizacaoId);

            if (! $autorizacao instanceof AutorizacaoServico) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Selecione um nÃºmero de AS/ASA vÃ¡lido para importar a nota fiscal.',
                ]);
            }

            $this->ensureAutorizacaoServicoEnviada($autorizacao);

            if ((int) $autorizacao->obra_id !== (int) $obraId) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'A AS selecionada nÃ£o pertence Ã  obra informada.',
                ]);
            }

            if (filled($autorizacao->construtora_id) && (int) $autorizacao->construtora_id !== (int) $user->construtoras_id) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'VocÃª nÃ£o pode importar nota fiscal para uma AS de outro fornecedor.',
                ]);
            }

            $item = $this->resolveItemForAutorizacaoServico($autorizacao, $obraId);

            if ($item instanceof ControleNotaFiscalItem) {
                $this->ensureControleNotaFiscalAberto($item->controleNotaFiscal);

                unset(
                    $data['obra_id_lookup'],
                    $data['asa_id_lookup'],
                    $data['controle_nota_fiscal_id'],
                    $data['tipo_nota_fiscal_destino']
                );

                $data['autorizacao_servico_id'] = $autorizacao->id;
                $data['autorizacao_servico_adicional_id'] = null;

                return $data;
            }

            throw ValidationException::withMessages([
                'asa_id_lookup' => 'Não foi possível localizar a AS vinculada diretamente ao item do controle selecionado.',
            ]);
        }

        // Para admin (não-Construtora): tentar resolver AutorizacaoServico primeiro
        $obraId = $data['obra_id_lookup'] ?? null;
        $autorizacaoId = $data['asa_id_lookup'] ?? null;

        if (filled($obraId) && filled($autorizacaoId)) {
            $autorizacao = AutorizacaoServico::query()
                ->with('asEscopo')
                ->find($autorizacaoId);

            if ($autorizacao instanceof AutorizacaoServico && (int) $autorizacao->obra_id === (int) $obraId) {
                $this->ensureAutorizacaoServicoEnviada($autorizacao);

                $item = $this->resolveItemForAutorizacaoServico($autorizacao, $obraId);

                if ($item instanceof ControleNotaFiscalItem) {
                    $this->ensureControleNotaFiscalAberto($item->controleNotaFiscal);

                    unset(
                        $data['obra_id_lookup'],
                        $data['asa_id_lookup'],
                        $data['controle_nota_fiscal_id'],
                        $data['tipo_nota_fiscal_destino']
                    );

                    $data['autorizacao_servico_id'] = $autorizacao->id;
                    $data['autorizacao_servico_adicional_id'] = null;

                    return $data;
                }

                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Não foi possível localizar a AS vinculada diretamente ao item do controle.',
                ]);
            }
        }

        $asa = $this->resolveAsa($data['asa_id_lookup'] ?? null);

        unset(
            $data['obra_id_lookup'],
            $data['asa_id_lookup'],
            $data['controle_nota_fiscal_id'],
            $data['tipo_nota_fiscal_destino']
        );

        if (! $asa instanceof Asa) {
            throw ValidationException::withMessages([
                'asa_id_lookup' => 'Selecione uma ASA válida para importar a nota fiscal.',
            ]);
        }

        $controle = $this->resolveControleNotaFiscalForAsa($asa);

        if (! $controle instanceof ControleNotaFiscal) {
            throw ValidationException::withMessages([
                'asa_id_lookup' => 'A ASA selecionada não possui dados suficientes para vincular a nota fiscal.',
            ]);
        }

        $this->ensureControleNotaFiscalAberto($controle);

        $destino = $this->resolveControleNotaFiscalDestinoParaAsa($asa);

        if ($destino === 'principal') {
            throw ValidationException::withMessages([
                'asa_id_lookup' => 'A ASA selecionada precisa estar vinculada diretamente a um item auxiliar do controle.',
            ]);
        }

        $auxiliar = $this->resolveAuxiliarForAsa($controle, $asa);

        if (
            $user instanceof User
            && $user->hasRole('Fornecedor')
            && filled($user->construtoras_id)
        ) {
            $construtora = Construtora::query()->find($user->construtoras_id);

            if (! $construtora || trim((string) $auxiliar->empresa) !== trim((string) $construtora->nome)) {
                throw ValidationException::withMessages([
                    'asa_id_lookup' => 'Você não pode importar nota fiscal para outro fornecedor.',
                ]);
            }
        }

        $data['autorizacao_servico_id'] = null;
        $data['autorizacao_servico_adicional_id'] = $asa->id;

        return $data;
    }

    protected function ensureAutorizacaoServicoEnviada(AutorizacaoServico $autorizacao): void
    {
        if ($autorizacao->status === AsStatus::ENVIADA) {
            return;
        }

        throw ValidationException::withMessages([
            'asa_id_lookup' => 'Selecione uma AS enviada para importar a nota fiscal.',
        ]);
    }

    protected function ensureControleNotaFiscalAberto(?ControleNotaFiscal $controle): void
    {
        if ($controle?->status !== ControleNotaFiscal::STATUS_ENCERRADO) {
            return;
        }

        throw ValidationException::withMessages([
            'asa_id_lookup' => 'Este controle de nota fiscal está encerrado e não aceita novas notas fiscais.',
        ]);
    }

    protected function resolveAsa(mixed $asaId): ?Asa
    {
        $asaId = $this->normalizeAsaLookupId($asaId);

        if (! filled($asaId)) {
            return null;
        }

        return Asa::query()
            ->with([
                'controleNotaFiscalAuxiliar.controleNotaFiscal.obra',
            ])
            ->find($asaId);
    }

    protected function isAsaLookup(mixed $asaId): bool
    {
        return is_string($asaId) && str_starts_with($asaId, 'asa:');
    }

    protected function normalizeAsaLookupId(mixed $asaId): mixed
    {
        if (! $this->isAsaLookup($asaId)) {
            return $asaId;
        }

        return substr((string) $asaId, 4);
    }

    protected function resolveControleNotaFiscalForAsa(Asa $asa): ?ControleNotaFiscal
    {
        $controleDireto = $asa->controleNotaFiscalAuxiliar?->controleNotaFiscal;

        if ($controleDireto instanceof ControleNotaFiscal) {
            return $controleDireto->fresh(['obra']);
        }

        return null;
    }

    protected function resolveControleNotaFiscalDestinoParaAsa(Asa $asa): string
    {
        if ($asa->controle_nota_fiscal_destino === 'principal') {
            return 'principal';
        }

        return 'adicional';
    }

    protected function resolveAuxiliarForAsa(ControleNotaFiscal $controle, Asa $asa): ControleNotaFiscalAuxiliar
    {
        if (
            $asa->controle_nota_fiscal_auxiliar_id
            && $asa->controleNotaFiscalAuxiliar?->controle_nota_fiscal_id === $controle->id
        ) {
            return $asa->controleNotaFiscalAuxiliar;
        }

        throw ValidationException::withMessages([
            'asa_id_lookup' => 'A ASA selecionada precisa estar vinculada diretamente ao item auxiliar do controle.',
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var ControleNotaFiscalNota $nota */
        $nota = DB::transaction(function () use ($data): ControleNotaFiscalNota {
            $normalizedData = $this->normalizeNotaFiscalData($data);
            $user = Auth::user();

            if (! $user instanceof User) {
                throw ValidationException::withMessages([
                    'importado_por_id' => 'Usuário autenticado inválido para importar nota fiscal.',
                ]);
            }

            if (filled($normalizedData['autorizacao_servico_id'] ?? null)) {
                $autorizacaoServico = AutorizacaoServico::query()
                    ->find($normalizedData['autorizacao_servico_id']);

                if (! $autorizacaoServico instanceof AutorizacaoServico) {
                    throw ValidationException::withMessages([
                        'autorizacao_servico_id' => 'Não foi possível localizar a AS selecionada.',
                    ]);
                }

                return app(ControleNotaFiscalNotaService::class)->importarParaAs($normalizedData, $autorizacaoServico, $user);
            }

            if (filled($normalizedData['autorizacao_servico_adicional_id'] ?? null)) {
                $asa = Asa::query()
                    ->find($normalizedData['autorizacao_servico_adicional_id']);

                if (! $asa instanceof Asa) {
                    throw ValidationException::withMessages([
                        'autorizacao_servico_adicional_id' => 'Não foi possível localizar a ASA selecionada.',
                    ]);
                }

                return app(ControleNotaFiscalNotaService::class)->importarParaAsa($normalizedData, $asa, $user);
            }

            throw ValidationException::withMessages([
                'destino' => 'Selecione uma AS ou ASA válida para importar a nota fiscal.',
            ]);
        });

        $this->finalizeUploadedFilePath($nota);
        $this->recalculateRelatedSaldo($nota);

        return $nota;
    }

    protected function recalculateRelatedSaldo(ControleNotaFiscalNota $nota): void
    {
        if ($nota->autorizacao_servico_id) {
            $item = $nota->autorizacaoServico?->controleNotaFiscalItem;

            if ($item instanceof ControleNotaFiscalItem) {
                $this->persistSaldoForTarget($item);
            }

            return;
        }

        if ($nota->autorizacao_servico_adicional_id) {
            $auxiliar = $nota->asa?->controleNotaFiscalAuxiliar;

            if ($auxiliar instanceof ControleNotaFiscalAuxiliar) {
                $this->persistSaldoForTarget($auxiliar);
            }

            return;
        }

    }

    protected function persistSaldoForTarget(ControleNotaFiscalItem|ControleNotaFiscalAuxiliar $target): void
    {
        $notasQuery = ControleNotaFiscalNota::query()
            ->where(function (Builder $query) use ($target): void {
                if ($target instanceof ControleNotaFiscalItem) {
                    $autorizacaoServicoIds = AutorizacaoServico::query()
                        ->where('controle_nota_fiscal_item_id', $target->id)
                        ->pluck('id');

                    $query->whereIn('autorizacao_servico_id', $autorizacaoServicoIds);

                    return;
                }

                $asaIds = Asa::query()
                    ->where('controle_nota_fiscal_auxiliar_id', $target->id)
                    ->pluck('id');

                $query->whereIn('autorizacao_servico_adicional_id', $asaIds);
            });

        $acumuladoDireto = (float) (clone $notasQuery)
            ->where('tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');

        $acumuladoIndireto = (float) (clone $notasQuery)
            ->tipoMaterialBucket()
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');

        $valorAcumuladoMedido = $acumuladoDireto + $acumuladoIndireto;
        $totalMedicao = $valorAcumuladoMedido;

        $target->updateQuietly([
            'total_medicao_a_menos_b' => $totalMedicao,
            'valor_acumulado_medido' => $valorAcumuladoMedido,
            'saldo' => (float) $target->valor_global_a - $valorAcumuladoMedido,
        ]);
    }

    protected function finalizeUploadedFilePath(ControleNotaFiscalNota $nota): void
    {
        $targetId = $nota->autorizacao_servico_adicional_id
            ?? $nota->autorizacao_servico_id;

        if (! $targetId) {
            return;
        }

        $fieldsToMove = ['arquivo_path', 'boleto_path'];

        foreach ($fieldsToMove as $field) {
            $currentPath = $nota->{$field};

            if (! is_string($currentPath) || ! str_contains($currentPath, '/temp/')) {
                continue;
            }

            $targetPath = str_replace('/temp/', '/'.$targetId.'/', $currentPath);

            if ($targetPath === $currentPath) {
                continue;
            }

            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

            if ($disk->exists($currentPath)) {
                $disk->move($currentPath, $targetPath);
                $nota->updateQuietly([$field => $targetPath]);
            }
        }
    }

    public function create(bool $another = false): void
    {
        if ($this->notaFiscalJaImportada()) {
            Notification::make()
                ->title('Nota fiscal já importada')
                ->body('Já existe uma nota fiscal com este número para este CNPJ do fornecedor.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $alertaSaldo = $this->verificarAlertaSaldo();

        if ($alertaSaldo !== null) {
            $this->saldoInsuficienteMensagem = $alertaSaldo;
            $this->mountAction('saldoInsuficiente');

            throw ValidationException::withMessages([
                'data.valor_acumulado_medido_nf' => $alertaSaldo,
            ]);
        }

        parent::create($another);
    }

    public function saldoInsuficienteAction(): Action
    {
        return Action::make('saldoInsuficiente')
            ->modalHeading('Saldo insuficiente')
            ->modalDescription(fn (): ?string => $this->saldoInsuficienteMensagem)
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('danger')
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action): Action => $action
                ->label('Entendi')
                ->color('primary')
                ->extraAttributes(['style' => 'margin-inline: auto !important; width: max-content !important;']))
            ->modalFooterActionsAlignment(Alignment::Start)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false);
    }

    protected function notaFiscalJaImportada(): bool
    {
        $formData = $this->form->getState();
        $numeroNf = $formData['numero_nf'] ?? null;
        $cnpjFornecedor = $formData['cnpj_fornecedor'] ?? null;

        return ControleNotaFiscalNota::duplicateExists($numeroNf, $cnpjFornecedor);
    }

    protected function verificarAlertaSaldo(): ?string
    {
        $formData = $this->form->getState();
        $valorNf = (float) ($formData['valor_acumulado_medido_nf'] ?? 0);
        $tipoMedicao = $formData['tipo_medicao'] ?? null;
        $obraId = $formData['obra_id_lookup'] ?? null;
        $asaId = $formData['asa_id_lookup'] ?? null;

        if ($valorNf <= 0 || ! $tipoMedicao || ! $asaId) {
            return null;
        }

        $item = $this->resolveItemForBalanceValidation($asaId, $obraId);

        if (! $item instanceof ControleNotaFiscalItem) {
            return null;
        }

        $valorGlobal = (float) $item->valor_global_a;

        // Calcular saldo geral dinamicamente: valor_global_a - soma de todos os acumulados
        $statusComImpactoNoSaldo = StatusControleNotaFiscalNota::comImpactoNoSaldo();

        $acumuladoTotal = (float) $item->notasFiscais()
            ->whereIn('controle_nota_fiscal_notas.status', $statusComImpactoNoSaldo)
            ->sum('valor_acumulado_medido_nf');
        $saldoGeral = $valorGlobal - $acumuladoTotal;

        if ($tipoMedicao === ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA) {
            // Limite para mão de obra = valor_global_a * percentual
            $limiteMaoObra = $valorGlobal * ((float) $item->percentual_faturamento_mao_obra / 100);
            // Acumulado de mão de obra já importado
            $acumuladoMaoObra = (float) $item->notasFiscais()
                ->where('controle_nota_fiscal_notas.tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA)
                ->whereIn('controle_nota_fiscal_notas.status', $statusComImpactoNoSaldo)
                ->sum('valor_acumulado_medido_nf');
            // Saldo disponível para mão de obra = limite - acumulado
            $saldo = $limiteMaoObra - $acumuladoMaoObra;
            $tipo = 'mão de obra';
        } elseif (in_array($tipoMedicao, ControleNotaFiscalNota::tiposMaterialBucket(), true)) {
            // Limite para material/transporte = valor_global_a * percentual de indireto
            $limiteMaterial = $valorGlobal * ((float) $item->percentual_faturamento_material / 100);
            // Acumulado indireto considera material + transporte no mesmo bucket
            $acumuladoMaterial = (float) $item->notasFiscais()
                ->tipoMaterialBucket()
                ->whereIn('controle_nota_fiscal_notas.status', $statusComImpactoNoSaldo)
                ->sum('valor_acumulado_medido_nf');
            // Saldo disponível para bucket indireto = limite - acumulado
            $saldo = $limiteMaterial - $acumuladoMaterial;
            $tipo = 'material/transporte';
        } else {
            // Tipos não mapeados usam saldo geral do item
            $saldo = $saldoGeral;
            $tipo = (string) $tipoMedicao;
        }

        if ($valorNf > $saldo) {
            $saldoFormatado = number_format($saldo, 2, ',', '.');
            $valorFormatado = number_format($valorNf, 2, ',', '.');

            return "O valor da nota (R$ {$valorFormatado}) excede o saldo disponível de {$tipo} (R$ {$saldoFormatado}).";
        }

        return null;
    }

    protected function validarCnpjsDistintos(array $data): void
    {
        $cnpjFornecedor = Cnpj::normalize($data['cnpj_fornecedor'] ?? null);
        $cnpjFaturamento = Cnpj::normalize($data['cnpj_faturamento'] ?? null);

        if ($cnpjFornecedor !== '' && $cnpjFornecedor === $cnpjFaturamento) {
            throw ValidationException::withMessages([
                'data.cnpj_faturamento' => 'O CNPJ do destinatário/remetente não pode ser igual ao CNPJ do emissor da nota.',
            ]);
        }
    }

    protected function validarDadosTransferencia(array $data): void
    {
        $errors = [];

        foreach (['banco_codigo' => 'Banco', 'agencia' => 'Agência', 'conta_corrente' => 'Conta Corrente'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                $errors["data.{$field}"] = "Informe {$label} para pagamento por transferência.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function preencherBancoTransferencia(array $data): array
    {
        $banco = Banco::query()
            ->where('ativo', true)
            ->where('codigo', $data['banco_codigo'] ?? null)
            ->first();

        if (! $banco instanceof Banco) {
            throw ValidationException::withMessages([
                'data.banco_codigo' => 'Selecione um banco válido para pagamento por transferência.',
            ]);
        }

        $data['banco'] = trim($banco->codigo.' - '.$banco->nome_reduzido);

        return $data;
    }

    protected function resolveItemForBalanceValidation(mixed $asaId, mixed $obraId): ?ControleNotaFiscalItem
    {
        $autorizacao = AutorizacaoServico::query()
            ->with('asEscopo')
            ->find($asaId);

        if ($autorizacao instanceof AutorizacaoServico && (int) $autorizacao->obra_id === (int) $obraId) {
            $item = $this->resolveItemForAutorizacaoServico($autorizacao, $obraId);

            if ($item instanceof ControleNotaFiscalItem) {
                return $item;
            }
        }

        return null;
    }

    protected function resolveItemForAutorizacaoServico(AutorizacaoServico $autorizacao, mixed $obraId): ?ControleNotaFiscalItem
    {
        if ((int) $autorizacao->obra_id !== (int) $obraId) {
            return null;
        }

        return $autorizacao->controleNotaFiscalItem()
            ->whereHas('controleNotaFiscal', fn (Builder $query): Builder => $query
                ->where('obra_id', $autorizacao->obra_id)
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value))
            ->whereNotNull('liberado_para_fornecedor_at')
            ->first();
    }
}
