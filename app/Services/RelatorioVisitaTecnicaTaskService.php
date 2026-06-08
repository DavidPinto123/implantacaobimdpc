<?php

namespace App\Services;

use App\Models\Marca;
use App\Models\Projeto;
use App\Models\RelatorioVisitaTecnica;
use App\Models\Setor;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;

class RelatorioVisitaTecnicaTaskService
{
    public static function syncVisitaConsultorEnergia(RelatorioVisitaTecnica $relatorio): void
    {
        if ((int) ($relatorio->necessario_visita_consultor_energia ?? 0) !== 1) {
            return;
        }

        $taskCategoryId = TaskCategory::query()
            ->where('name', 'Complementares')
            ->value('id');

        if (! $taskCategoryId) {
            return;
        }

        $setorId = Setor::query()
            ->whereRaw('LOWER(setor) = ?', ['complementares'])
            ->value('id');

        if (! $setorId) {
            return;
        }

        $assignedTo = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Consultor'))
            ->whereHas('setores', fn ($query) => $query->where('setores.id', $setorId))
            ->value('id');

        if (! $assignedTo) {
            return;
        }

        $marcaId = self::resolverMarcaId($relatorio);

        if (! $marcaId) {
            return;
        }

        $createdBy = $relatorio->autor_id ?? auth()->id() ?? $assignedTo;

        if (! $createdBy) {
            return;
        }

        $identificadorRelatorio = $relatorio->numero_relatorio_vt
            ?? $relatorio->unidade
            ?? ('#'.$relatorio->id);

        $nomeRelatorio = $relatorio->unidade ?? $relatorio->numero_relatorio_vt;

        $title = 'Visita do consultor de energia - Relatório '.$identificadorRelatorio;
        $description = 'Pendência gerada automaticamente a partir do relatório de visita técnica '.$nomeRelatorio.', pois foi sinalizada a necessidade de visita do consultor de energia.';

        $taskExistente = Task::query()
            ->where('task_category_id', $taskCategoryId)
            ->where('marca_id', $marcaId)
            ->where('assigned_to', $assignedTo)
            ->where('title', $title)
            ->first();

        $payload = [
            'description' => $description,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'setor_id' => $setorId,
            'prazo' => null,
            'dias_corridos' => 0,
            'inicio' => now()->toDateString(),
            'termino_programado' => null,
            'data_entrega' => null,
            'status' => 'pendente',
            'sigla' => $relatorio->sigla ?? null,
        ];

        if ($taskExistente) {
            $statusProtegidos = [
                'em_andamento',
                'concluida',
                'cancelada',
            ];

            if (in_array($taskExistente->status, $statusProtegidos, true)) {
                return;
            }

            $taskExistente->update($payload);

            return;
        }

        Task::create([
            'title' => $title,
            'description' => $description,
            'task_category_id' => $taskCategoryId,
            'sigla' => $relatorio->sigla ?? null,
            'marca_id' => $marcaId,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'setor_id' => $setorId,
            'prazo' => null,
            'dias_corridos' => 0,
            'inicio' => now()->toDateString(),
            'termino_programado' => null,
            'data_entrega' => null,
            'status' => 'pendente',
        ]);
    }

    protected static function resolverMarcaId(RelatorioVisitaTecnica $relatorio): ?int
    {
        if (! empty($relatorio->marca_id)) {
            return (int) $relatorio->marca_id;
        }

        if (! empty($relatorio->projeto_id)) {
            $projeto = Projeto::find($relatorio->projeto_id);

            if ($projeto && ! empty($projeto->marca_id)) {
                return (int) $projeto->marca_id;
            }

            if ($projeto && ! empty($projeto->marca)) {
                return Marca::query()
                    ->where('nome', $projeto->marca)
                    ->value('id');
            }
        }

        if (! empty($relatorio->marca)) {
            return Marca::query()
                ->where('nome', $relatorio->marca)
                ->value('id');
        }

        return null;
    }
}
