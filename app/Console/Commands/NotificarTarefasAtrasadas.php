<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappTaskContext;
use App\Services\PosObra\WhatsAppService;
use Illuminate\Console\Command;

class NotificarTarefasAtrasadas extends Command
{
    protected $signature = 'whatsapp:notificar-atrasos {--user= : E-mail ou ID do usuário (para testar um único destinatário)}';

    protected $description = 'Envia WhatsApp para tarefas que venceram ontem (evita spam de re-notificação)';

    public function handle(): int
    {
        $template = config('services.whatsapp.templates.tarefa_atrasada');

        if (! $template) {
            $this->warn('Template tarefa_atrasada não configurado em config/services.php.');

            return self::FAILURE;
        }

        $filtro  = $this->option('user');
        $manual  = (bool) $filtro;

        $query = Task::whereNotIn('status', ['concluida', 'cancelada'])
            ->with(['responsavel', 'solicitante']);

        if ($manual) {
            // Envio manual/teste: todas as tarefas atrasadas do usuário
            $usuario = User::where('email', $filtro)->orWhere('id', $filtro)->first();
            if (! $usuario) {
                $this->warn("Usuário '{$filtro}' não encontrado.");
                return self::FAILURE;
            }
            $query->where('termino_programado', '<', now()->startOfDay())
                ->where(fn ($q) => $q->where('assigned_to', $usuario->id)->orWhere('created_by', $usuario->id));
        } else {
            // Envio automático agendado: apenas tarefas que venceram ontem (evita spam)
            $query->whereDate('termino_programado', now()->subDay()->toDateString());
        }

        $tarefas = $query->get();

        $enviados = 0;

        foreach ($tarefas as $tarefa) {
            $prazo = $tarefa->termino_programado?->format('d/m/Y') ?? '-';

            $destinos = collect();

            if ($tarefa->responsavel?->phone) {
                $destinos->push($tarefa->responsavel);
            }

            if ($tarefa->solicitante?->phone && $tarefa->solicitante->id !== $tarefa->responsavel?->id) {
                $destinos->push($tarefa->solicitante);
            }

            foreach ($destinos as $usuario) {
                $tel = WhatsAppService::formatarTelefone($usuario->phone);
                if (! $tel) {
                    continue;
                }

                SendWhatsAppNotificationJob::dispatch($tel, $template, [
                    $usuario->name,
                    $tarefa->title,
                    $prazo,
                ]);

                // Salva contexto para capturar resposta como comentário na tarefa
                WhatsappTaskContext::updateOrCreate(
                    ['phone' => $tel, 'task_id' => $tarefa->id],
                    ['task_title' => $tarefa->title, 'expires_at' => now()->addHours(72), 'replied_at' => null]
                );

                $enviados++;
            }
        }

        $this->info("Atrasos: {$tarefas->count()} tarefa(s) vencida(s) ontem → {$enviados} notificação(ões) enfileirada(s).");

        return self::SUCCESS;
    }
}
