<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Pages;

use App\Enums\StatusControleNotaFiscalNota;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditImportacaoNotaFiscal extends EditRecord
{
    protected static string $resource = 'App\\Filament\\Resources\\ImportacaoNotaFiscals\\ImportacaoNotaFiscalResource';

    public function getTitle(): string
    {
        return 'Editar Nota Fiscal Importada';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tipo_nota_fiscal_destino'] = $this->record->isAdicional() ? 'adicional' : 'principal';
        $data['controle_nota_fiscal_id'] = $this->record->itemDerivado()?->controle_nota_fiscal_id
            ?? $this->record->auxiliarDerivado()?->controle_nota_fiscal_id;
        $data['linha_principal_id'] = $this->record->itemDerivado()?->id;
        $data['linha_auxiliar_id'] = $this->record->auxiliarDerivado()?->id;
        $data['envio'] = $this->record->created_at?->toDateString();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->ensureNotaFiscalNaoDuplicada($data);

        return $this->normalizeNotaFiscalData($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function (): bool {
                    $user = Auth::user();

                    return $user instanceof User && $user->can('Delete:ControleNotaFiscalNota');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function ensureNotaFiscalNaoDuplicada(array $data): void
    {
        if (! ControleNotaFiscalNota::duplicateExists(
            $data['numero_nf'] ?? null,
            $data['cnpj_fornecedor'] ?? null,
            $this->record->id,
        )) {
            return;
        }

        throw ValidationException::withMessages([
            'numero_nf' => 'Já existe uma nota fiscal com este número para este CNPJ do fornecedor.',
        ]);
    }

    protected function normalizeNotaFiscalData(array $data): array
    {
        if ($this->record->status !== StatusControleNotaFiscalNota::EM_ANALISE->value) {
            throw ValidationException::withMessages([
                'status' => 'A nota fiscal não pode mais ser editada após a decisão de aprovação ou reprovação.',
            ]);
        }

        $controleNotaFiscalId = $data['controle_nota_fiscal_id'] ?? null;
        $tipoDestino = $data['tipo_nota_fiscal_destino']
            ?? ($this->record->isAdicional() ? 'adicional' : 'principal');
        $user = Auth::user();

        unset($data['controle_nota_fiscal_id'], $data['tipo_nota_fiscal_destino']);

        if ($tipoDestino === 'adicional') {
            $auxiliar = ControleNotaFiscalAuxiliar::query()
                ->whereKey($data['linha_auxiliar_id'] ?? null)
                ->where('controle_nota_fiscal_id', $controleNotaFiscalId)
                ->first();

            if (! $auxiliar) {
                throw ValidationException::withMessages([
                    'linha_auxiliar_id' => 'Selecione um item extra contratual (adicional) válido do controle informado.',
                ]);
            }

            if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
                $construtora = Construtora::query()->find($user->construtoras_id);

                if (! $construtora || trim((string) $auxiliar->empresa) !== trim((string) $construtora->nome)) {
                    throw ValidationException::withMessages([
                        'linha_auxiliar_id' => 'Você não pode importar nota fiscal para outro fornecedor.',
                    ]);
                }
            }

            $asa = Asa::query()
                ->where('controle_nota_fiscal_auxiliar_id', $auxiliar->id)
                ->first();

            if (! $asa instanceof Asa) {
                throw ValidationException::withMessages([
                    'linha_auxiliar_id' => 'Não foi possível localizar a ASA vinculada ao item auxiliar selecionado.',
                ]);
            }

            $data['autorizacao_servico_id'] = null;
            $data['autorizacao_servico_adicional_id'] = $asa->id;

            unset($data['linha_principal_id'], $data['linha_auxiliar_id']);

            return $data;
        }

        $item = ControleNotaFiscalItem::query()
            ->whereKey($data['linha_principal_id'] ?? null)
            ->where('controle_nota_fiscal_id', $controleNotaFiscalId)
            ->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'linha_principal_id' => 'Selecione um item válido da tabela principal para o controle informado.',
            ]);
        }

        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            $construtora = Construtora::query()->find($user->construtoras_id);

            if (! $construtora || trim((string) $item->empresa) !== trim((string) $construtora->nome)) {
                throw ValidationException::withMessages([
                    'linha_principal_id' => 'Você não pode importar nota fiscal para outro fornecedor.',
                ]);
            }
        }

        $autorizacaoServico = AutorizacaoServico::query()
            ->where('controle_nota_fiscal_item_id', $item->id)
            ->first();

        if (! $autorizacaoServico instanceof AutorizacaoServico) {
            throw ValidationException::withMessages([
                'linha_principal_id' => 'Não foi possível localizar a AS vinculada ao item selecionado.',
            ]);
        }

        $data['autorizacao_servico_id'] = $autorizacaoServico->id;
        $data['autorizacao_servico_adicional_id'] = null;

        unset($data['linha_auxiliar_id'], $data['linha_principal_id']);

        return $data;
    }
}
