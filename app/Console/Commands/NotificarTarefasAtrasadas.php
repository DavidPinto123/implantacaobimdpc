<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Models\User;
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

        // Apenas tarefas que venceram ontem — notifica só no primeiro dia de atraso
        $ontem = now()->subDay()->toDateString();

        $query = Task::whereDate('termino_programado', $ontem)
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->with(['responsavel', 'solicitante']);

        if ($filtro = $this->option('user')) {
            $usuario = User::where('email', $filtro)->orWhere('id', $filtro)->first();
            if ($usuario) {
                $query->where(fn ($q) => $q->where('assigned_to', $usuario->id)->orWhere('created_by', $usuario->id));
            }
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

                $enviados++;
            }
        }

        $this->info("Atrasos: {$tarefas->count()} tarefa(s) vencida(s) ontem → {$enviados} notificação(ões) enfileirada(s).");

        return self::SUCCESS;
    }
}
