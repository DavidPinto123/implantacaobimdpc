<?php

namespace App\Filament\Pages;

use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HistoricoProjetos extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'PMO';

    protected static ?string $navigationLabel = 'Histórico de Projetos';

    protected static ?string $title = 'Histórico de Projetos';

    protected static ?string $slug = 'historico-projetos';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.historico-projetos';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:HistoricoProjetos') ?? false;
    }

    public ?int $filtroProjetoId = null;

    public ?string $filtroCampo = null;

    public ?int $filtroUsuarioId = null;

    public ?string $filtroDataInicio = null;

    public ?string $filtroDataFim = null;

    public int $limit = 200;

    public function mount(): void
    {
        $this->filtroProjetoId = request()->integer('projeto') ?: null;
    }

    public function limparFiltros(): void
    {
        $this->filtroProjetoId = null;
        $this->filtroCampo = null;
        $this->filtroUsuarioId = null;
        $this->filtroDataInicio = null;
        $this->filtroDataFim = null;
    }

    public function projetosDisponiveis(): array
    {
        $projetoIds = CronogramaFaseHistorico::query()
            ->select('projeto_id')
            ->whereNotNull('projeto_id')
            ->distinct()
            ->pluck('projeto_id')
            ->merge(
                CronogramaFaseHistorico::query()
                    ->join('cronograma_fases', 'cronograma_fase_historicos.cronograma_fase_id', '=', 'cronograma_fases.id')
                    ->select('cronograma_fases.projeto_id')
                    ->distinct()
                    ->pluck('projeto_id')
            )
            ->unique()
            ->filter()
            ->values();

        return Projeto::query()
            ->whereIn('id', $projetoIds)
            ->orderBy('nome')
            ->limit(500)
            ->get(['id', 'codigo', 'nome'])
            ->mapWithKeys(fn ($p) => [$p->id => trim(($p->codigo ? "[{$p->codigo}] " : '').$p->nome)])
            ->toArray();
    }

    public function usuariosDisponiveis(): array
    {
        return User::query()
            ->whereIn('id', CronogramaFaseHistorico::query()->select('usuario_id')->whereNotNull('usuario_id')->distinct())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getViewData(): array
    {
        $query = CronogramaFaseHistorico::query()
            ->with(['cronogramaFase.projeto:id,codigo,nome', 'projeto:id,codigo,nome', 'usuario:id,name']);

        if ($this->filtroProjetoId) {
            $query->where(function (Builder $q) {
                $q->where('projeto_id', $this->filtroProjetoId)
                    ->orWhereHas('cronogramaFase', fn ($q2) => $q2->where('projeto_id', $this->filtroProjetoId));
            });
        }

        if ($this->filtroCampo) {
            $query->where('campo_alterado', $this->filtroCampo);
        }

        if ($this->filtroUsuarioId) {
            $query->where('usuario_id', $this->filtroUsuarioId);
        }

        if ($this->filtroDataInicio) {
            $query->where('created_at', '>=', Carbon::parse($this->filtroDataInicio)->startOfDay());
        }

        if ($this->filtroDataFim) {
            $query->where('created_at', '<=', Carbon::parse($this->filtroDataFim)->endOfDay());
        }

        $registros = $query->latest()->take($this->limit)->get();

        $lotes = $registros->groupBy(function ($h) {
            return $h->created_at->format('Y-m-d H:i:s').'|'.($h->motivo ?? '').'|'.($h->usuario_id ?? 0).'|'.($h->cronogramaFase?->projeto_id ?? $h->projeto_id ?? 0);
        });

        return [
            'registros' => $registros,
            'lotes' => $lotes,
            'projetos' => $this->projetosDisponiveis(),
            'usuarios' => $this->usuariosDisponiveis(),
            'totalRegistros' => $registros->count(),
        ];
    }
}
