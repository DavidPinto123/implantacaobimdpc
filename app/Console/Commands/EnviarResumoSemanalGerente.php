<?php

namespace App\Console\Commands;

use App\Mail\ResumoPlanejamentoSemanal;
use App\Models\Projeto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarResumoSemanalGerente extends Command
{
    protected $signature = 'resumo:semanal {--preview : Mostra o resumo no console sem enviar email}';

    protected $description = 'Envia resumo semanal de planejamentos para cada Gerente Geral';

    public function handle(): int
    {
        $hoje = Carbon::today();
        // Semana anterior: segunda a domingo passados
        $semAntIni = $hoje->copy()->startOfWeek()->subWeek();
        $semAntFim = $semAntIni->copy()->endOfWeek();
        // Semana atual: segunda a domingo desta semana
        $semAtualIni = $hoje->copy()->startOfWeek();
        $semAtualFim = $semAtualIni->copy()->endOfWeek();

        $labelAnterior = $semAntIni->format('d/m') . ' a ' . $semAntFim->format('d/m/Y');
        $labelAtual    = $semAtualIni->format('d/m') . ' a ' . $semAtualFim->format('d/m/Y');

        // Busca todos os gerentes que têm planejamentos ativos
        $gerenteIds = Projeto::where('status', '!=', 'cancelado')
            ->whereNotNull('gerente_geral_id')
            ->pluck('gerente_geral_id')
            ->unique();

        $gerentes = User::whereIn('id', $gerenteIds)->get();

        if ($gerentes->isEmpty()) {
            $this->info('Nenhum Gerente Geral com planejamentos ativos encontrado.');
            return self::SUCCESS;
        }

        foreach ($gerentes as $gerente) {
            $projetos = Projeto::where('gerente_geral_id', $gerente->id)
                ->where('status', '!=', 'cancelado')
                ->with(['cronogramaFases.itens.responsaveis'])
                ->get();

            // Semana anterior: itens concluídos (recebido = true) com data_realizada_fim na semana anterior
            $semanaAnterior = $this->coletarItensSemana(
                $projetos,
                $semAntIni,
                $semAntFim,
                concluidos: true
            );

            // Semana atual: itens previstos (data_prevista_fim na semana atual) ou em andamento
            $semanaAtual = $this->coletarItensSemana(
                $projetos,
                $semAtualIni,
                $semAtualFim,
                concluidos: false
            );

            if ($this->option('preview')) {
                $this->line("=== Gerente: {$gerente->name} ({$gerente->email}) ===");
                $this->line("Semana anterior ({$labelAnterior}): " . count($semanaAnterior) . ' itens concluídos');
                $this->line("Semana atual ({$labelAtual}): " . count($semanaAtual) . ' itens previstos');
                foreach ($semanaAnterior as $entry) {
                    $this->line("  [ANT] [{$entry['projeto']}] {$entry['fase']} > {$entry['item']}");
                }
                foreach ($semanaAtual as $entry) {
                    $this->line("  [ATU] [{$entry['projeto']}] {$entry['fase']} > {$entry['item']}");
                }
                continue;
            }

            if (! $gerente->email) {
                $this->warn("Gerente {$gerente->name} sem e-mail cadastrado, pulando.");
                continue;
            }

            Mail::to($gerente->email)->send(new ResumoPlanejamentoSemanal(
                gerente: $gerente,
                projetos: $projetos,
                semanaAnterior: $semanaAnterior,
                semanaAtual: $semanaAtual,
                labelSemanaAnterior: $labelAnterior,
                labelSemanaAtual: $labelAtual,
            ));

            $this->info("Resumo enviado para {$gerente->name} <{$gerente->email}>");
        }

        return self::SUCCESS;
    }

    private function coletarItensSemana($projetos, Carbon $ini, Carbon $fim, bool $concluidos): array
    {
        $resultado = [];

        foreach ($projetos as $projeto) {
            foreach ($projeto->cronogramaFases as $fase) {
                foreach ($fase->itens as $item) {
                    if ($concluidos) {
                        // Itens concluídos na semana anterior
                        if (! $item->recebido || ! $item->data_realizada_fim) continue;
                        $data = Carbon::parse($item->data_realizada_fim);
                        if (! $data->between($ini, $fim)) continue;
                    } else {
                        // Itens previstos para esta semana (pela data_prevista_fim)
                        if ($item->recebido) continue;
                        $dataRef = $item->data_prevista_fim ?? $item->data_prevista_inicio;
                        if (! $dataRef) continue;
                        $data = Carbon::parse($dataRef);
                        if (! $data->between($ini, $fim)) continue;
                    }

                    $responsaveisNomes = $item->responsaveis->pluck('name')->implode(', ');

                    $resultado[] = [
                        'projeto'      => $projeto->nome,
                        'fase'         => $fase->nome,
                        'item'         => $item->titulo ?? "Item #{$item->id}",
                        'responsaveis' => $responsaveisNomes ?: '—',
                        'data'         => $concluidos
                            ? Carbon::parse($item->data_realizada_fim)->format('d/m/Y')
                            : (Carbon::parse($item->data_prevista_fim ?? $item->data_prevista_inicio)->format('d/m/Y')),
                        'status'       => $item->status ?? null,
                        'recebido'     => $item->recebido,
                    ];
                }
            }
        }

        return $resultado;
    }
}
