<?php

namespace App\Observers;

use App\Enums\StatusCronograma;
use App\Enums\StatusLiberacaoPosse;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;

class CronogramaFaseItemObserver
{
    /**
     * Sincroniza `recebido` (boolean) com `status_liberacao` (tri-estado) antes de salvar.
     * Quando status_liberacao=SIM o item é considerado recebido/concluído; quando
     * NAO ou RISCO, recebido=false. Isso mantém o cálculo de % conclusão consistente
     * para fases que usam o tri-estado (Liberação de Posse).
     */
    public function saving(CronogramaFaseItem $item): void
    {
        if ($item->isDirty('status_liberacao') && $item->status_liberacao instanceof StatusLiberacaoPosse) {
            $item->recebido = $item->status_liberacao->concluido();
        }

        if ($item->isDirty('recebido')) {
            if ($item->recebido) {
                $item->data_realizada_fim ??= today();
            } else {
                // desmarcado manualmente — limpa a data para não ficar inconsistente
                $item->data_realizada_fim = null;
            }
        }
    }

    public function saved(CronogramaFaseItem $item): void
    {
        // Ao trocar ou remover revisor, deleta a task do revisor anterior
        if ($item->wasChanged('revisor_id')) {
            $revisorAnterior = $item->getOriginal('revisor_id');
            if ($revisorAnterior && $revisorAnterior !== $item->revisor_id) {
                \App\Models\Task::where('cronograma_fase_item_id', $item->id)
                    ->where('assigned_to', $revisorAnterior)
                    ->where('eh_revisor', true)
                    ->delete();
            }
        }

        $this->propagarParaItemPai($item);
        $this->recalcularFasePai($item->cronograma_fase_id);
    }

    public function deleted(CronogramaFaseItem $item): void
    {
        // Remove Tasks vinculadas a este item
        \App\Models\Task::where('cronograma_fase_item_id', $item->id)->delete();

        $this->propagarParaItemPai($item);
        $this->recalcularFasePai($item->cronograma_fase_id);
    }

    /**
     * Propaga o estado recebido para cima na árvore de subitens.
     * Um item pai é marcado como recebido quando TODOS os seus filhos estão recebidos.
     * Usa updateQuietly para não re-disparar o observer e sobe iterativamente até a raiz.
     */
    private function propagarParaItemPai(CronogramaFaseItem $item): void
    {
        $atual = $item;

        while ($atual->parent_id) {
            $pai = CronogramaFaseItem::find($atual->parent_id);
            if (! $pai) {
                break;
            }

            $filhos = $pai->children()->get();
            $todosRecebidos = $filhos->isNotEmpty() && $filhos->every(fn ($f) => (bool) $f->recebido);

            if ($pai->recebido === $todosRecebidos) {
                break;
            }

            $updateData = ['recebido' => $todosRecebidos];
            if ($todosRecebidos && ! $pai->data_realizada_fim) {
                $updateData['data_realizada_fim'] = today();
            } elseif (! $todosRecebidos) {
                $updateData['data_realizada_fim'] = null;
            }
            $pai->updateQuietly($updateData);
            $atual = $pai;
        }
    }

    /**
     * Recalcula percentual_conclusao da fase pai em função dos subitens
     * marcados como recebidos. Se 100% recebidos e fase não concluída →
     * marca CONCLUIDO. Se abaixo de 100% e fase estava em status final
     * (auto-concluída por itens) → reverte para EM_ANDAMENTO.
     */
    private function recalcularFasePai(?int $faseId): void
    {
        if (! $faseId) {
            return;
        }

        $fase = CronogramaFase::find($faseId);
        if (! $fase) {
            return;
        }

        $itens = $fase->itens()->get();
        if ($itens->isEmpty()) {
            $update = ['percentual_conclusao' => 0];

            if (in_array($fase->status, $this->statusFinais(), true)) {
                $update['status'] = StatusCronograma::EM_ANDAMENTO;
            }

            $fase->updateQuietly($update);

            return;
        }

        $total       = $itens->count();
        $recebidos   = $itens->where('recebido', true)->count();
        $emAndamento = $itens->where('recebido', false)->whereNotNull('data_realizada_inicio')->count();
        $percentual  = (int) round(($recebidos * 100 + $emAndamento * 50) / $total);

        $update = ['percentual_conclusao' => $percentual];

        $statusFinais = $this->statusFinais();

        if ($percentual === 100 && ! in_array($fase->status, $statusFinais, true)) {
            $update['status'] = StatusCronograma::CONCLUIDO;
        } elseif ($percentual < 100 && in_array($fase->status, $statusFinais, true)) {
            $update['status'] = StatusCronograma::EM_ANDAMENTO;
        }

        $fase->updateQuietly($update);
    }

    /**
     * @return array<int, StatusCronograma>
     */
    private function statusFinais(): array
    {
        return [
            StatusCronograma::CONCLUIDO,
            StatusCronograma::REALIZADO,
            StatusCronograma::ASSINADO,
            StatusCronograma::FINALIZADO,
            StatusCronograma::PRONTO,
        ];
    }
}
