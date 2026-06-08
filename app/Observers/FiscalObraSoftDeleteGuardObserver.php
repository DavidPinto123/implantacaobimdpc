<?php

namespace App\Observers;

use App\Models\Asa;
use App\Models\AsaItem;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\ControleNotaFiscalNotaBaixa;
use App\Models\Obras;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class FiscalObraSoftDeleteGuardObserver
{
    public function saving(Model $model): void
    {
        $this->bloquearSeObraEstiverSoftDelete($model);
        $this->bloquearSeControleEstiverEncerrado($model);
    }

    public function deleting(Model $model): void
    {
        $this->bloquearSeObraEstiverSoftDelete($model);
        $this->bloquearSeControleEstiverEncerrado($model);
    }

    private function bloquearSeObraEstiverSoftDelete(Model $model): void
    {
        if (! $this->registroPertenceAObraSoftDelete($model)) {
            return;
        }

        throw ValidationException::withMessages([
            'obra' => 'Não é possível alterar registros fiscais de uma obra excluída.',
        ]);
    }

    private function registroPertenceAObraSoftDelete(Model $model): bool
    {
        $controle = $this->controleNotaFiscalDoRegistro($model);

        if ($controle?->obraEstaSoftDelete()) {
            return true;
        }

        if (! $model instanceof AutorizacaoServico) {
            return false;
        }

        return $this->obraEstaSoftDelete($model->obra_id);
    }

    private function bloquearSeControleEstiverEncerrado(Model $model): void
    {
        if ($model instanceof ControleNotaFiscal) {
            return;
        }

        $controle = $this->controleNotaFiscalDoRegistro($model);

        if ($controle?->status !== ControleNotaFiscal::STATUS_ENCERRADO) {
            return;
        }

        throw ValidationException::withMessages([
            'controle_nota_fiscal' => 'Controle de nota fiscal encerrado para a unidade.',
        ]);
    }

    private function controleNotaFiscalDoRegistro(Model $model): ?ControleNotaFiscal
    {
        return match (true) {
            $model instanceof ControleNotaFiscal => $model,
            $model instanceof ControleNotaFiscalItem => $model->controleNotaFiscal()->first(),
            $model instanceof ControleNotaFiscalAuxiliar => $model->controleNotaFiscal()->first(),
            $model instanceof AutorizacaoServico => $model->controleNotaFiscalItem?->controleNotaFiscal,
            $model instanceof Asa => $model->controleNotaFiscalAuxiliar?->controleNotaFiscal,
            $model instanceof AsaItem => $model->asa?->controleNotaFiscalAuxiliar?->controleNotaFiscal,
            $model instanceof ControleNotaFiscalNota => $this->controleNotaFiscalDaNota($model),
            $model instanceof ControleNotaFiscalNotaBaixa => $model->nota ? $this->controleNotaFiscalDaNota($model->nota) : null,
            default => null,
        };
    }

    private function controleNotaFiscalDaNota(ControleNotaFiscalNota $nota): ?ControleNotaFiscal
    {
        return $nota->autorizacaoServico?->controleNotaFiscalItem?->controleNotaFiscal
            ?? $nota->asa?->controleNotaFiscalAuxiliar?->controleNotaFiscal;
    }

    private function obraEstaSoftDelete(?int $obraId): bool
    {
        if (! $obraId) {
            return false;
        }

        return Obras::withTrashed()
            ->whereKey($obraId)
            ->whereNotNull('deleted_at')
            ->exists();
    }
}
