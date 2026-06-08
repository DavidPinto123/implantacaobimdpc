<?php

namespace App\Observers;

use App\Enums\TipoUnidade;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Models\User;
use App\Services\NumeroAsRetrofitService;
use Filament\Notifications\Notification;

class ControleNotaFiscalItemObserver
{
    public function created(ControleNotaFiscalItem $item): void
    {
        $this->syncAutorizacaoServico($item);
    }

    public function updated(ControleNotaFiscalItem $item): void
    {
        $this->syncAutorizacaoServico($item);
    }

    protected function syncAutorizacaoServico(ControleNotaFiscalItem $item): void
    {
        $controle = $item->controleNotaFiscal;

        if (! $controle) {
            return;
        }

        if (blank($item->as_escopo_id)) {
            return;
        }

        if (blank($controle->obra_id)) {
            return;
        }

        if ($controle->tipo_unidade !== TipoUnidade::RETROFIT->value) {
            return;
        }

        $obra = Obras::find($controle->obra_id);
        if (! $obra) {
            return;
        }

        $construtora = Construtora::query()
            ->where('nome', $item->empresa)
            ->first();

        $construtoraId = $construtora?->id;

        $numeroAsRetrofitService = new NumeroAsRetrofitService;
        $numeroAs = $numeroAsRetrofitService->gerar($obra, $controle, $item->as_escopo_id, $item->empresa);

        $query = AutorizacaoServico::query();

        $autorizacaoDireta = $item->autorizacaoServico;
        $vinculadoDiretamente = $autorizacaoDireta instanceof AutorizacaoServico;

        if ($vinculadoDiretamente) {
            $query
                ->whereKey($autorizacaoDireta->id)
                ->where('obra_id', $controle->obra_id)
                ->where('as_escopo_id', $item->as_escopo_id);
        } else {
            if (blank($item->liberado_para_fornecedor_at)) {
                return;
            }

            $query
                ->where('obra_id', $controle->obra_id)
                ->where('as_escopo_id', $item->as_escopo_id);

            if (filled($item->numero_complemento)) {
                $query->where('numero_complemento', $item->numero_complemento);
            } else {
                $query->where(function ($builder): void {
                    $builder
                        ->whereNull('numero_complemento')
                        ->orWhere('numero_complemento', '');
                });
            }
        }

        $updateData = [
            'numero_as' => $numeroAs,
            'controle_nota_fiscal_item_id' => $item->id,
        ];

        if (filled($construtoraId)) {
            $updateData['construtora_id'] = $construtoraId;
        }

        $atualizados = $query->update($updateData);

        if ($atualizados === 0 || blank($item->liberado_para_fornecedor_at)) {
            return;
        }

        if ($vinculadoDiretamente) {
            return;
        }

        $liberacaoAcabouDeAcontecer = $item->wasRecentlyCreated
            || $item->wasChanged('liberado_para_fornecedor_at');

        if (! $liberacaoAcabouDeAcontecer) {
            return;
        }

        if ($construtoraId) {
            User::query()
                ->where('is_active', true)
                ->where('construtoras_id', $construtoraId)
                ->get()
                ->each(function (User $usuario) use ($controle, $item): void {
                    Notification::make()
                        ->title('Nova AS gerada')
                        ->body(
                            'Foi gerado o escopo '.($item->escopo ?: '-').
                            ' na unidade '.($controle->obra?->unidade ?? '-').'.'
                        )
                        ->icon('heroicon-o-check-circle')
                        ->success()
                        ->sendToDatabase($usuario);
                });
        }
    }
}
