<?php

namespace App\Http\Controllers;

use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Services\CronogramaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronogramaController extends Controller
{
    public function __construct(
        private CronogramaService $cronogramaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Projeto::with([
            'cronogramaFases' => fn ($q) => $q->visiveis(),
            'cronogramaFases.templateFase',
            'estado',
            'obras',
        ])->whereHas('cronogramaFases');

        if ($request->filled('projeto_id')) {
            $query->where('id', $request->projeto_id);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('cronogramaFases', fn ($q) => $q->where('status', $status));
        }

        if ($request->filled('regional')) {
            $regional = $request->regional;
            $query->where('regional', $regional);
        }

        if ($request->filled('estado')) {
            $query->where('estado_id', $request->estado);
        }

        if ($request->filled('data_inicio')) {
            $query->whereHas('cronogramaFases', fn ($q) => $q->where('data_prevista_inicio', '>=', $request->data_inicio));
        }

        if ($request->filled('data_fim')) {
            $query->whereHas('cronogramaFases', fn ($q) => $q->where('data_prevista_fim', '<=', $request->data_fim));
        }

        $projetos = $query->paginate(50);

        $data = $projetos->map(fn (Projeto $projeto) => [
            'projeto' => [
                'id' => $projeto->id,
                'codigo' => $projeto->codigo,
                'nome' => $projeto->nome,
                'uf' => $projeto->estado?->uf,
                'status' => $projeto->status,
                'percentual_geral' => $this->cronogramaService->calcularPercentualGeral($projeto),
            ],
            'fases' => $projeto->cronogramaFases->map(fn (CronogramaFase $fase) => [
                'id' => $fase->id,
                'fase' => $fase->fase->value,
                'label' => $fase->fase->label(),
                'ordem' => $fase->ordem,
                'marco' => $fase->marco,
                'data_prevista_inicio' => $fase->data_prevista_inicio?->format('Y-m-d'),
                'data_prevista_fim' => $fase->data_prevista_fim?->format('Y-m-d'),
                'status' => $fase->status->value,
                'status_label' => $fase->status->label(),
                'status_color' => $fase->status->color(),
                'percentual_conclusao' => $fase->percentual_conclusao,
                'dias_atraso' => $fase->dias_atraso,
                'observacoes' => $fase->observacoes,
            ]),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $projetos->currentPage(),
                'last_page' => $projetos->lastPage(),
                'per_page' => $projetos->perPage(),
                'total' => $projetos->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'projeto_id' => 'required|exists:projetos,id',
        ]);

        $projeto = Projeto::findOrFail($request->projeto_id);
        $this->cronogramaService->criarFasesParaProjeto($projeto);

        return response()->json([
            'message' => 'Fases criadas com sucesso.',
            'fases' => $projeto->cronogramaFases()->get(),
        ], 201);
    }

    public function update(Request $request, CronogramaFase $cronogramaFase): JsonResponse
    {
        $validated = $request->validate([
            'data_prevista_inicio' => 'nullable|date',
            'data_prevista_fim' => 'nullable|date|after_or_equal:data_prevista_inicio',
            'status' => 'nullable|in:' . implode(',', array_column(\App\Enums\StatusCronograma::cases(), 'value')),
            'percentual_conclusao' => 'nullable|integer|min:0|max:100',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        $cronogramaFase->update($validated);

        return response()->json([
            'message' => 'Fase atualizada com sucesso.',
            'fase' => $cronogramaFase->fresh(),
        ]);
    }

    public function atualizarStatus(Projeto $projeto): JsonResponse
    {
        $projeto->loadMissing('cronogramaFases');

        foreach ($projeto->cronogramaFases as $fase) {
            if ($fase->status === \App\Enums\StatusCronograma::CONCLUIDO && $fase->percentual_conclusao < 100) {
                $fase->percentual_conclusao = 100;
                $fase->saveQuietly();
            }
        }

        return response()->json([
            'message' => 'Percentuais sincronizados com sucesso.',
            'fases' => $projeto->cronogramaFases()->get(),
        ]);
    }
}
