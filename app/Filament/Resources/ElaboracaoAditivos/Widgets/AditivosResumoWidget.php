<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Widgets;

use App\Models\ElaboracaoAditivoItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Reactive;

class AditivosResumoWidget extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $construtoraFiltro = null;

    #[Reactive]
    public ?string $gestorFiltro = null;

    #[Reactive]
    public ?string $obraFiltro = null;

    #[Reactive]
    public ?string $dataDeFiltro = null;

    #[Reactive]
    public ?string $dataAteFiltro = null;

    protected function getStats(): array
    {
        $query = ElaboracaoAditivoItem::query()
            ->with('elaboracaoAditivo');

        $this->applyRoleScope($query);
        $this->applyFilters($query);

        $totalItens = (clone $query)->count();
        $valorTotalItens = (clone $query)->sum('valor_total_geral');

        $totalAditivos = (clone $query)
            ->get()
            ->pluck('elaboracao_aditivo_id')
            ->filter()
            ->unique()
            ->count();

        return [
            Stat::make('Total de aditivos', number_format($totalAditivos, 0, ',', '.')),
            Stat::make('Total de itens', number_format($totalItens, 0, ',', '.')),
            Stat::make('Valor total', 'R$ '.number_format($valorTotalItens, 2, ',', '.')),
        ];
    }

    protected function applyRoleScope(Builder $query): void
    {
        $user = Auth::user();

        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        $podeVerTudo =
            $user->hasRole('super_admin') ||
            $user->hasRole('Coordenador') ||
            $user->setores()->where('setor', 'Obras')->exists();

        if ($podeVerTudo) {
            return;
        }

        if ($user->hasRole('Fornecedor') && $user->construtoras_id) {
            $query->whereHas('elaboracaoAditivo', fn (Builder $q) => $q->where('construtora_id', $user->construtoras_id));

            return;
        }

        $query->whereHas('elaboracaoAditivo', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    protected function applyFilters(Builder $query): void
    {
        $query->whereHas('elaboracaoAditivo', function (Builder $q) {
            $q->when($this->construtoraFiltro, fn (Builder $q) => $q->where('construtora_id', $this->construtoraFiltro))
                ->when($this->gestorFiltro, fn (Builder $q) => $q->where('gestor_id', $this->gestorFiltro))
                ->when($this->obraFiltro, fn (Builder $q) => $q->where('obra_id', $this->obraFiltro))
                ->when($this->dataDeFiltro, fn (Builder $q) => $q->whereDate('data', '>=', $this->dataDeFiltro))
                ->when($this->dataAteFiltro, fn (Builder $q) => $q->whereDate('data', '<=', $this->dataAteFiltro));
        });
    }
}
