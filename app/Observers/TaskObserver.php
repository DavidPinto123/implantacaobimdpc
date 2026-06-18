<?php

namespace App\Observers;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Services\PosObra\WhatsAppService;

class TaskObserver
{
    public function created(Task $task): void
    {
        $template = config('services.whatsapp.templates.nova_tarefa');
        if (! $template) {
            return;
        }

        $prazo = $task->termino_programado
            ? $task->termino_programado->format('d/m/Y')
            : 'sem prazo';

        $destinos = collect();

        if ($task->responsavel?->phone) {
            $destinos->push($task->responsavel);
        }

        foreach ($destinos as $usuario) {
            $tel = WhatsAppService::formatarTelefone($usuario->phone);
            if ($tel) {
                SendWhatsAppNotificationJob::dispatch($tel, $template, [
                    $usuario->name,
                    $task->title,
                    $prazo,
                ]);
            }
        }

        $this->notificarGerenteGeral($task, "Nova tarefa criada: *{$task->title}*. Responsável: " . ($task->responsavel?->name ?? '—'));
    }

    public function updated(Task $task): void
    {
        // ── Sync Planning ────────────────────────────────────────────────
        if ($task->cronograma_fase_item_id) {
            $this->syncPlanejamento($task);
        }

        // ── WhatsApp: só notifica quando status mudou ────────────────────
        if (! $task->wasChanged('status')) {
            return;
        }

        $template = config('services.whatsapp.templates.status_tarefa');
        if (! $template) {
            return;
        }

        $statusLabel = match ($task->status) {
            'pendente'     => 'Não iniciada',
            'em_andamento' => 'Em andamento',
            'concluida'    => 'Concluída',
            'cancelada'    => 'Cancelada',
            default        => ucfirst($task->status),
        };

        $destinos = collect();

        if ($task->responsavel?->phone) {
            $destinos->push($task->responsavel);
        }

        if ($task->solicitante?->phone && $task->solicitante->id !== $task->responsavel?->id) {
            $destinos->push($task->solicitante);
        }

        foreach ($destinos as $usuario) {
            $tel = WhatsAppService::formatarTelefone($usuario->phone);
            if ($tel) {
                SendWhatsAppNotificationJob::dispatch($tel, $template, [
                    $usuario->name,
                    $task->title,
                    $statusLabel,
                ]);
            }
        }

        $this->notificarGerenteGeral($task, "Tarefa *{$task->title}* atualizada para {$statusLabel}");
    }

    private function syncPlanejamento(Task $task): void
    {
        $item = \App\Models\CronogramaFaseItem::find($task->cronograma_fase_item_id);
        if (! $item) {
            return;
        }

        // Campos que replicam diretamente sem disparar o observer de cronograma
        $changes = [];

        if ($task->wasChanged('revisor_id')) {
            $changes['revisor_id'] = $task->revisor_id;
        }

        if ($task->wasChanged('termino_programado') && $task->termino_programado) {
            $changes['data_prevista_fim'] = $task->termino_programado;
        }

        if ($changes) {
            \App\Models\CronogramaFaseItem::withoutObservers(fn () => $item->update($changes));
        }

        // Responsável: mantém o assigned_to no pivot sem remover outros
        if ($task->wasChanged('assigned_to') && $task->assigned_to) {
            $item->responsaveis()->syncWithoutDetaching([$task->assigned_to]);
        }

        // Status → datas realizadas e recebido
        if ($task->wasChanged('status')) {
            if ($task->status === 'em_andamento' && ! $item->data_realizada_inicio) {
                \App\Models\CronogramaFaseItem::withoutObservers(
                    fn () => $item->update(['data_realizada_inicio' => today()])
                );
            } elseif ($task->status === 'concluida' && ! $item->recebido) {
                $item->recebido = true;
                $item->data_realizada_fim ??= today();
                $item->save(); // observer propaga percentual e status da fase
            }
        }
    }

    private function notificarGerenteGeral(Task $task, string $evento): void
    {
        $template = config('services.whatsapp.templates.gerente_notificacao');
        if (! $template) {
            return;
        }

        $gerente = $task->projeto?->gerenteGeral;
        if (! $gerente?->phone) {
            return;
        }

        // Não notifica se o gerente já foi notificado como responsável/solicitante
        if (in_array($gerente->id, array_filter([
            $task->responsavel?->id,
            $task->solicitante?->id,
        ]))) {
            return;
        }

        $tel = WhatsAppService::formatarTelefone($gerente->phone);
        if (! $tel) {
            return;
        }

        $nomeProjeto = $task->projeto?->nome ?? 'Projeto';

        SendWhatsAppNotificationJob::dispatch($tel, $template, [
            $gerente->name,
            $evento,
            $nomeProjeto,
        ]);
    }
}
