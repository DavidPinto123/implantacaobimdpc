<?php

namespace App\Services;

use App\Models\Marca;
use App\Models\Projeto;
use App\Models\RelatorioFotografico;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Carbon\Carbon;

class RelatorioFotograficoTaskService
{
    public static function syncPendencias(RelatorioFotografico $relatorio): void
    {
        $entregas = $relatorio->entregas_contratuais ?? [];

        if (! is_array($entregas) || empty($entregas)) {
            return;
        }

        $taskCategoryId = TaskCategory::query()
            ->where('name', 'Entregas Contratuais')
            ->value('id');

        if (! $taskCategoryId) {
            return;
        }

        $projeto = Projeto::find($relatorio->projeto_id);

        if (! $projeto) {
            return;
        }

        $marcaId = null;

        if (! empty($projeto->marca)) {
            $marcaId = Marca::query()
                ->where('nome', $projeto->marca)
                ->value('id');
        }

        if (! $marcaId) {
            return;
        }

        $autorId = $relatorio->autor_id ?? auth()->id();
        $autor = User::find($autorId);

        if (! $autor) {
            return;
        }

        $assignedTo = $autor->id;
        $createdBy = $autor->id;
        $setorId = $autor->setores()->value('setores.id');

        $statusProtegidos = [
            'em_andamento',
            'concluida',
            'cancelada',
        ];

        foreach ($entregas as $item) {
            if (($item['status'] ?? null) !== 'nao_entregue') {
                continue;
            }

            $titulo = $item['titulo'] ?? 'Entrega sem título';
            $dataPrevista = $item['data_prevista'] ?? null;

            if (! $dataPrevista) {
                continue;
            }

            $inicio = now()->toDateString();
            $diasCorridos = true;
            $prazo = self::calcularPrazo($inicio, $dataPrevista, $diasCorridos);

            $taskExistente = Task::query()
                ->where('task_category_id', $taskCategoryId)
                ->where('marca_id', $marcaId)
                ->where('assigned_to', $assignedTo)
                ->where('title', 'Relatório Fotográfico - '.$titulo)
                ->first();

            if ($taskExistente) {
                if (in_array($taskExistente->status, $statusProtegidos, true)) {
                    continue;
                }

                $taskExistente->update([
                    'description' => 'Pendência gerada automaticamente para a entrega contratual "'.$titulo.'" do relatório fotográfico.',
                    'created_by' => $createdBy,
                    'assigned_to' => $assignedTo,
                    'setor_id' => $setorId,
                    'prazo' => $prazo,
                    'dias_corridos' => $diasCorridos,
                    'inicio' => $inicio,
                    'termino_programado' => $dataPrevista,
                    'data_entrega' => $dataPrevista,
                    'status' => 'pendente',
                    'sigla' => $relatorio->sigla ?? null,
                ]);

                continue;
            }

            Task::create([
                'title' => 'Relatório Fotográfico - '.$titulo,
                'description' => 'Pendência gerada automaticamente para a entrega contratual "'.$titulo.'" do relatório fotográfico.',
                'task_category_id' => $taskCategoryId,
                'sigla' => $relatorio->sigla ?? null,
                'marca_id' => $marcaId,
                'created_by' => $createdBy,
                'assigned_to' => $assignedTo,
                'setor_id' => $setorId,
                'prazo' => $prazo,
                'dias_corridos' => $diasCorridos,
                'inicio' => $inicio,
                'termino_programado' => $dataPrevista,
                'data_entrega' => $dataPrevista,
                'status' => 'pendente',
            ]);
        }
    }

    protected static function calcularPrazo(string $inicio, string $termino, bool $diasCorridos = true): int
    {
        $dataInicio = Carbon::parse($inicio)->startOfDay();
        $dataTermino = Carbon::parse($termino)->startOfDay();

        if ($dataTermino->lessThan($dataInicio)) {
            return 0;
        }

        if ($diasCorridos) {
            return $dataInicio->diffInDays($dataTermino);
        }

        $diasUteis = 0;
        $dataAtual = $dataInicio->copy();

        while ($dataAtual->lt($dataTermino)) {
            if (! $dataAtual->isWeekend()) {
                $diasUteis++;
            }

            $dataAtual->addDay();
        }

        return $diasUteis;
    }
}
