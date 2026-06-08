<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Exports\ListProjetoExport;
use App\Filament\Resources\ProjetoResource;
use App\Models\Etapa;
use App\Models\Projeto;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListProjetos extends ListRecords
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('exportar_excel')
                ->label('Exportar Projetos')
                ->color('green')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $tabSelecionada = $this->activeTab ?? 'Todos';

                    return Excel::download(
                        new ListProjetoExport($tabSelecionada),
                        'projetos_smart_'.now()->format('Y-m-d').'.xlsx'
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        $etapas = Etapa::pluck('id', 'nome'); // ['Nome da Etapa' => id]
        $user = auth()->user();

        // Papéis com acesso total a todos os projetos
        $fullAccessRoles = ['super_admin', 'PMO', 'Planejamento Estratégico', 'Comercial', 'Inteligência Global', 'gestor_obra'];

        // Base para contar badges (já filtrada por responsável se NÃO tiver acesso total)
        $baseQuery = Projeto::query();
        if (! $user->hasAnyRole($fullAccessRoles)) {
            $baseQuery->where('user_id', $user->id);
        }

        // Helper para criar uma tab por etapa
        $makeTab = function (string $nomeEtapa) use ($etapas, $user, $fullAccessRoles, $baseQuery) {
            $etapaId = $etapas[$nomeEtapa] ?? null;

            return Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($etapaId, $user, $fullAccessRoles) {
                    if ($etapaId) {
                        // Se sua relação for belongsToMany, troque a linha abaixo por:
                        // $query->whereHas('etapas', fn ($q) => $q->where('etapas.id', $etapaId));
                        $query->whereHas('etapas', fn ($q) => $q->where('etapa_id', $etapaId));
                    }

                    if (! $user->hasAnyRole($fullAccessRoles)) {
                        $query->where('user_id', $user->id);
                    }

                    $query->orderByDesc('created_at');
                })
                ->badge(
                    (clone $baseQuery)
                        ->when(
                            $etapaId,
                            fn ($q) =>
                            // Se for belongsToMany, troque para whereHas('etapas', fn($q) => $q->where('etapas.id', $etapaId))
                            $q->whereHas('etapas', fn ($qq) => $qq->where('etapa_id', $etapaId))
                        )
                        ->count()
                );
        };

        return [
            // Todos vêem esta aba; para perfis sem acesso total, contará e listará apenas os próprios
            'Todos' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user, $fullAccessRoles) {
                    if (! $user->hasAnyRole($fullAccessRoles)) {
                        $query->where('user_id', $user->id);
                    }
                    $query->orderByDesc('created_at');
                })
                ->badge((clone $baseQuery)->count()),

            'Prospecção' => $makeTab('Prospecção'),
            'Reunião de comitê' => $makeTab('Reunião de comitê'),
            'Viabilidade' => $makeTab('Viabilidade'),
            'Briefing e Layout' => $makeTab('Briefing e Layout'),
            'Ordem de investimento' => $makeTab('Ordem de investimento'),
            'Contrato' => $makeTab('Contrato'),
            'Projetos de obra' => $makeTab('Projetos de obra'),
            'Orçamentos e equalização' => $makeTab('Orçamentos e equalização'),
            'Inicio de obra' => $makeTab('Inicio de obra'),
            'Inauguração' => $makeTab('Inauguração'),
            'Passagem para operações' => $makeTab('Passagem para operações'),
        ];
    }
}
