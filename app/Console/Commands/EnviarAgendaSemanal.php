<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Task;
use App\Models\User;
use App\Services\PosObra\WhatsAppService;
use Illuminate\Console\Command;

class EnviarAgendaSemanal extends Command
{
    protected $signature = 'whatsapp:agenda-semanal {--user= : E-mail ou ID do usuário (para testar um único destinatário)}';

    protected $description = 'Envia resumo semanal de tarefas via WhatsApp (toda segunda-feira às 9h)';

    private const DIAS_PT = [
        'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua',
        'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sáb', 'Sun' => 'Dom',
    ];

    public function handle(): int
    {
        $template = config('services.whatsapp.templates.agenda_semanal');

        if (! $template) {
            $this->warn('Template agenda_semanal não configurado em config/services.php.');

            return self::FAILURE;
        }

        $hoje     = now()->startOfDay();
        $fimSemana = now()->endOfWeek(); // domingo da semana atual

        $query = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_active', true);

        if ($filtro = $this->option('user')) {
            $query->where(function ($q) use ($filtro) {
                $q->where('email', $filtro)->orWhere('id', $filtro);
            });
        }

        $usuarios = $query->get();

        $enviados = 0;

        foreach ($usuarios as $usuario) {
            $tel = WhatsAppService::formatarTelefone($usuario->phone);
            if (! $tel) {
                continue;
            }

            $tarefasSemana = Task::where(function ($q) use ($usuario) {
                $q->where('assigned_to', $usuario->id)
                    ->orWhere('created_by', $usuario->id);
            })
                ->whereBetween('termino_programado', [$hoje, $fimSemana])
                ->whereNotIn('status', ['concluida', 'cancelada'])
                ->orderBy('termino_programado')
                ->get();

            $tarefasAtrasadas = Task::where(function ($q) use ($usuario) {
                $q->where('assigned_to', $usuario->id)
                    ->orWhere('created_by', $usuario->id);
            })
                ->where('termino_programado', '<', $hoje)
                ->whereNotIn('status', ['concluida', 'cancelada'])
                ->orderBy('termino_programado')
                ->get();

            if ($tarefasSemana->isEmpty() && $tarefasAtrasadas->isEmpty()) {
                continue;
            }

            $linhas = [];

            if ($tarefasSemana->isNotEmpty()) {
                $linhas[] = '📋 *Esta semana:*';
                foreach ($tarefasSemana->take(5) as $t) {
                    $dia = self::DIAS_PT[$t->termino_programado->format('D')] ?? '';
                    $linhas[] = "• {$t->title} ({$dia} {$t->termino_programado->format('d/m')})";
                }
                if ($tarefasSemana->count() > 5) {
                    $linhas[] = '  + ' . ($tarefasSemana->count() - 5) . ' tarefa(s) a mais';
                }
            }

            if ($tarefasAtrasadas->isNotEmpty()) {
                if (! empty($linhas)) {
                    $linhas[] = '';
                }
                $linhas[] = '⚠️ *Em atraso:*';
                foreach ($tarefasAtrasadas->take(5) as $t) {
                    $dias = (int) $t->termino_programado->diffInDays(now());
                    $linhas[] = "• {$t->title} ({$dias}d atrasada)";
                }
                if ($tarefasAtrasadas->count() > 5) {
                    $linhas[] = '  + ' . ($tarefasAtrasadas->count() - 5) . ' tarefa(s) a mais';
                }
            }

            // Meta não permite \n em parâmetros de template — usa separador inline
            $resumo = implode(' | ', array_filter($linhas, fn ($l) => $l !== ''));

            // Limite de segurança para variável de template (~1024 chars)
            if (mb_strlen($resumo) > 900) {
                $resumo = mb_substr($resumo, 0, 897) . '...';
            }

            SendWhatsAppNotificationJob::dispatch($tel, $template, [$usuario->name, $resumo]);
            $enviados++;
        }

        $this->info("Agenda semanal: {$enviados} resumo(s) enfileirado(s) para {$usuarios->count()} usuário(s) com telefone.");

        return self::SUCCESS;
    }
}
