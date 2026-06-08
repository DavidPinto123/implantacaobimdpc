<?php

namespace App\Filament\Pages\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Models\Obras;
use App\Models\PosObra\Pendencia;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;

class KanbanPendencias extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static UnitEnum|string|null $navigationGroup = 'Pós Obra';

    protected static ?string $navigationLabel = 'Kanban';

    protected static ?string $title = 'Kanban de Pendências';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.pos-obra.kanban-pendencias';

    public string $obraFiltro = '';

    public array $colunas = [];

    public array $obrasOpcoes = [];

    private const COLUNAS_CONFIG = [
        ['key' => 'REGISTRADA',            'label' => 'Registrada',         'color' => '#FBBA00'],
        ['key' => 'NOTIFICADA_PRESTADORA', 'label' => 'Notificada',         'color' => '#6366F1'],
        ['key' => 'PENDENTE_COM_PRAZO',    'label' => 'Com Prazo',          'color' => '#3B82F6'],
        ['key' => 'EM_EXECUCAO',           'label' => 'Em Execução',        'color' => '#F59E0B'],
        ['key' => 'AGUARDANDO_APROVACAO',  'label' => 'Aguard. Aprovação',  'color' => '#8B5CF6'],
        ['key' => 'CONCLUIDA',             'label' => 'Concluída',          'color' => '#22C55E'],
        ['key' => 'AS_ORCAMENTOS',         'label' => 'As Orçamentos',      'color' => '#06B6D4'],
        ['key' => 'GARANTIA_SOLICITADA',   'label' => 'Garantia Sol.',      'color' => '#EC4899'],
        ['key' => 'PROJ_COMPLEMENTAR',     'label' => 'Proj. Complementar', 'color' => '#7C3AED'],
        ['key' => 'CANCELADA',             'label' => 'Cancelada',          'color' => '#6B7280'],
    ];

    public function mount(): void
    {
        $this->obrasOpcoes = Obras::whereHas('projeto', fn ($q) => $q->whereNotNull('sigla'))
            ->with('projeto:id,sigla')
            ->get()
            ->sortBy('sigla')
            ->pluck('sigla', 'id')
            ->toArray();

        $this->loadData();
    }

    public function loadData(): void
    {
        $pendencias = Pendencia::query()
            ->with(['obra:id,projeto_id', 'obra.projeto:id,sigla', 'disciplina:id,label'])
            ->when($this->obraFiltro, fn ($q) => $q->where('obras_id', $this->obraFiltro))
            ->get(['id', 'codigo', 'urgencia', 'status', 'descricao', 'obras_id', 'disciplina_config_id', 'data_termino']);

        $grouped = $pendencias->groupBy(
            fn ($p) => $p->status instanceof StatusPendencia ? $p->status->value : (string) $p->status
        );

        $this->colunas = collect(self::COLUNAS_CONFIG)->map(function ($col) use ($grouped) {
            $items = $grouped->get($col['key'], collect());

            return [
                'key' => $col['key'],
                'label' => $col['label'],
                'color' => $col['color'],
                'count' => $items->count(),
                'cards' => $items->map(fn ($p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'urgencia' => $p->urgencia instanceof UrgenciaPendencia ? $p->urgencia->value : (string) $p->urgencia,
                    'urgencia_label' => $p->urgencia instanceof UrgenciaPendencia ? $p->urgencia->label() : (string) $p->urgencia,
                    'descricao' => Str::limit($p->descricao ?? '', 100),
                    'disciplina' => $p->disciplina?->label,
                    'obra' => $p->obra?->sigla,
                    'atrasada' => $p->estaAtrasada(),
                    'url' => PendenciaResource::getUrl('view', ['record' => $p->id]),
                ])->values()->toArray(),
            ];
        })->toArray();
    }

    public function updatedObraFiltro(): void
    {
        $this->loadData();
    }

    public function getListUrl(): string
    {
        return PendenciaResource::getUrl('index');
    }
}
