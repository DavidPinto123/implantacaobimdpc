<?php

namespace App\Observers;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Services\PosObra\WhatsAppService;

class TaskObserver
{
    public function updated(Task $task): void
    {
        if (! $task->wasChanged('status')) {
            return;
        }

        $template = config('services.whatsapp.templates.status_tarefa');
        if (! $template) {
            return;
        }

        $statusLabel = match ($task->status) {
            'pendente'     => 'Pendente',
            'em_andamento' => 'Em andamento',
            'concluida'    => 'Concluída',
            'cancelada'    => 'Cancelada',
            default        => ucfirst($task->status),
        };

        $destinos = collect();

        if ($task->responsavel?->phone) {
            $destinos->push($task->responsavel);
        }

        // Notifica criador se for diferente do responsável
        if ($task->solicitante?->phone && $task->solicitante->id !== $task->responsavel?->id) {
            $destinos->push($task->solicitante);
        }

        foreach ($destinos as $usuario) {
            $tel = WhatsAppService::formatarTelefone($usuario->phone);
            if (! $tel) {
                continue;
            }

            SendWhatsAppNotificationJob::dispatch($tel, $template, [
                $usuario->name,
                $task->title,
                $statusLabel,
            ]);
        }
    }
}
