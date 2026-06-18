<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappSubscricao;
use App\Services\PosObra\WhatsAppService;
use Illuminate\Console\Command;

class EnviarResumoAtrasos extends Command
{
    protected $signature = 'whatsapp:resumo-atrasos {--user= : E-mail ou ID do usuário destinatário}';

    protected $description = 'Envia resumo consolidado de TODAS as tarefas atrasadas (com profissional responsável)';

    public function handle(): int
    {
        $template = config('services.whatsapp.templates.resumo_atrasos');

        if (! $template) {
            $this->warn('Template resumo_atrasos não configurado em config/services.php.');

            return self::FAILURE;
        }

        // Todas as tarefas atrasadas, independente do profissional
        $tarefas = Task::where('termino_programado', '<', now()->startOfDay())
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->with(['responsavel', 'projeto'])
            ->orderBy('termino_programado')
            ->get();

        if ($tarefas->isEmpty()) {
            $this->info('Nenhuma tarefa atrasada encontrada.');

            return self::SUCCESS;
        }

        // Montar texto do resumo
        $linhas = [];
        foreach ($tarefas->take(15) as $tarefa) {
            $dias = (int) now()->startOfDay()->diffInDays($tarefa->termino_programado->startOfDay());
            $resp = $tarefa->responsavel?->name ?? 'Sem responsável';
            $proj = $tarefa->projeto?->nome ? " [{$tarefa->projeto->nome}]" : '';
            $linhas[] = "• {$tarefa->title}{$proj} - {$resp} ({$dias}d)";
        }

        if ($tarefas->count() > 15) {
            $linhas[] = '+ ' . ($tarefas->count() - 15) . ' tarefa(s) a mais';
        }

        $resumo = implode(' | ', $linhas);

        if (mb_strlen($resumo) > 900) {
            $resumo = mb_substr($resumo, 0, 897) . '...';
        }

        // Determinar destinatários
        $query = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_active', true);

        if ($filtro = $this->option('user')) {
            $query->where(fn ($q) => $q->where('email', $filtro)->orWhere('id', $filtro));
        } else {
            $assinantes = WhatsappSubscricao::where('template_key', 'resumo_atrasos')->pluck('user_id');
            if ($assinantes->isNotEmpty()) {
                $query->whereIn('id', $assinantes);
            }
        }

        $usuarios = $query->get();
        $enviados = 0;

        foreach ($usuarios as $usuario) {
            $tel = WhatsAppService::formatarTelefone($usuario->phone);
            if (! $tel) {
                continue;
            }

            SendWhatsAppNotificationJob::dispatch($tel, $template, [$usuario->name, $resumo]);
            $enviados++;
        }

        $this->info("Resumo de atrasos: {$tarefas->count()} tarefa(s) → {$enviados} mensagem(ns) enfileirada(s).");

        return self::SUCCESS;
    }
}
