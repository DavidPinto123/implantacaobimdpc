<?php

namespace App\Services;

use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Resources\ProjetoResource;
use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Models\AgendaEvent;
use App\Models\AgendaTipoEvento;
use App\Models\AgendaUsuarioCor;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\RelatorioFotografico;
use App\Models\RelatorioVisitaTecnica;
use App\Models\Setor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgendaGeralService
{
    /** @var array<int, array{id:int,nome:string}>|null Cache do setor primário por user_id */
    protected ?array $userSetorCache = null;

    /** @var array<int, string>|null Mapa target_user_id => cor (paleta do usuário visualizador) */
    protected ?array $userCoresCache = null;

    /** @var array<string, array{nome:string,cor:string}>|null Cache de tipos visíveis por slug */
    protected ?array $tiposCache = null;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collectEvents(Carbon $rangeStart, Carbon $rangeEnd, array $filters = [], ?User $viewer = null): Collection
    {
        $viewer ??= auth()->user();
        $visibleIds = $viewer ? $viewer->agendaUsuariosVisiveisIds() : null;

        $this->primeUserSetorCache($visibleIds);
        $this->primeUserCoresCache($viewer, $visibleIds);
        $this->primeTiposCache($viewer);

        $events = collect();

        $events = $events->merge($this->collectVisitaTecnicaEvents($rangeStart, $rangeEnd));
        $events = $events->merge($this->collectRelatorioFotograficoEvents($rangeStart, $rangeEnd));
        $events = $events->merge($this->collectObraEvents($rangeStart, $rangeEnd));
        $events = $events->merge($this->collectManualEvents($rangeStart, $rangeEnd));

        // Aplica filtro hierárquico (role + setor) — null significa "ver tudo"
        if (is_array($visibleIds)) {
            $manualEventIdsAsParticipant = $this->manualEventIdsWhereUserIsParticipant($visibleIds);

            $events = $events->filter(function (array $event) use ($visibleIds, $manualEventIdsAsParticipant): bool {
                $responsibleId = (int) ($event['responsible_user_id'] ?? 0);
                if ($responsibleId !== 0 && in_array($responsibleId, $visibleIds, true)) {
                    return true;
                }

                // Eventos manuais sem responsável definido entram se o usuário visível for participante
                if (($event['origin'] ?? null) === 'manual'
                    && ! empty($event['manual_event_id'])
                    && in_array((int) $event['manual_event_id'], $manualEventIdsAsParticipant, true)) {
                    return true;
                }

                return false;
            });
        }

        // Anexa indicador visual: cor por responsável (configurada pelo viewer) + setor para exibição
        $events = $events->map(function (array $event): array {
            $responsibleId = (int) ($event['responsible_user_id'] ?? 0);
            $setor = $responsibleId !== 0 ? ($this->userSetorCache[$responsibleId] ?? null) : null;

            $event['responsible_setor_id'] = $setor['id'] ?? null;
            $event['responsible_setor_nome'] = $setor['nome'] ?? null;
            $event['responsible_user_cor'] = $responsibleId !== 0
                ? ($this->userCoresCache[$responsibleId] ?? null)
                : null;

            return $event;
        });

        $events = $events->filter(function (array $event) use ($filters): bool {
            if (! empty($filters['origin']) && $filters['origin'] !== 'all' && ($event['origin'] ?? null) !== $filters['origin']) {
                return false;
            }

            if (! empty($filters['type']) && $filters['type'] !== 'all' && ($event['event_type'] ?? null) !== $filters['type']) {
                return false;
            }

            if (! empty($filters['responsible_user_id']) && (int) ($event['responsible_user_id'] ?? 0) !== (int) $filters['responsible_user_id']) {
                return false;
            }

            if (! empty($filters['status']) && $filters['status'] !== 'all' && ($event['status'] ?? null) !== $filters['status']) {
                return false;
            }

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', array_filter([
                    $event['title'] ?? '',
                    $event['description'] ?? '',
                    $event['entity_label'] ?? '',
                    $event['origin_label'] ?? '',
                    $event['responsible_name'] ?? '',
                    $event['location'] ?? '',
                ])));

                if (! Str::contains($haystack, Str::lower($search))) {
                    return false;
                }
            }

            return true;
        });

        return $events
            ->sortBy(function (array $event): string {
                $startsAt = Carbon::parse($event['starts_at']);
                $isAllDay = (bool) ($event['all_day'] ?? false);

                return $startsAt->format('Y-m-d H:i')
                    .'|'.($isAllDay ? '0' : '1')
                    .'|'.Str::lower((string) ($event['title'] ?? ''));
            })
            ->values();
    }

    /**
     * Paleta padrão por nome de setor — fallback quando o usuário não configurou cores.
     *
     * @return array<string, string>
     */
    public function defaultSetorPalette(): array
    {
        return [
            'Obras' => '#ea580c',
            'Orçamentos' => '#2563eb',
            'Orcamentos' => '#2563eb',
            'Projetos' => '#7c3aed',
            'Legalização' => '#059669',
            'Legalizacao' => '#059669',
            'Comercial' => '#db2777',
            'PMO' => '#0ea5e9',
            'Engenharia' => '#16a34a',
        ];
    }

    /**
     * Retorna a paleta de cores que o usuário definiu para cada usuário visível na sua agenda.
     *
     * @return array<int, array{id:int,nome:string,setor:?string,cor:string}>
     */
    public function userPaletteForViewer(?User $viewer): array
    {
        if (! $viewer) {
            return [];
        }

        $visibleIds = $viewer->agendaUsuariosVisiveisIds();

        $usersQuery = User::query()->with(['setores:id,setor'])->orderBy('name');

        if (is_array($visibleIds)) {
            $usersQuery->whereIn('id', $visibleIds);
        }

        $users = $usersQuery->get(['id', 'name']);

        $defaults = $this->defaultSetorPalette();
        $custom = AgendaUsuarioCor::query()
            ->where('user_id', $viewer->id)
            ->pluck('cor', 'target_user_id')
            ->all();

        return $users->map(function (User $u) use ($custom, $defaults): array {
            $setor = $u->setores->first();
            $setorNome = $setor?->setor;
            $fallback = $setorNome && isset($defaults[$setorNome]) ? $defaults[$setorNome] : '#64748b';

            return [
                'id' => (int) $u->id,
                'nome' => (string) $u->name,
                'setor' => $setorNome,
                'cor' => $custom[$u->id] ?? $fallback,
            ];
        })->all();
    }

    public function saveUserCor(User $viewer, int $targetUserId, string $cor): void
    {
        AgendaUsuarioCor::updateOrCreate(
            ['user_id' => $viewer->id, 'target_user_id' => $targetUserId],
            ['cor' => $cor],
        );
    }

    /**
     * @param array<int, int>|null $visibleIds
     */
    protected function primeUserSetorCache(?array $visibleIds): void
    {
        $query = User::query()->with(['setores:id,setor']);

        if (is_array($visibleIds) && ! empty($visibleIds)) {
            $query->whereIn('id', $visibleIds);
        }

        $this->userSetorCache = $query->get(['id'])
            ->mapWithKeys(function (User $user): array {
                $setor = $user->setores->first();

                return [
                    $user->id => $setor
                        ? ['id' => (int) $setor->id, 'nome' => (string) $setor->setor]
                        : null,
                ];
            })
            ->filter()
            ->all();
    }

    /**
     * Monta a cor por user_id para o viewer atual.
     * Fallback: cor padrão do primeiro setor do usuário, ou cinza.
     *
     * @param array<int, int>|null $visibleIds
     */
    protected function primeUserCoresCache(?User $viewer, ?array $visibleIds): void
    {
        if (! $viewer) {
            $this->userCoresCache = [];

            return;
        }

        $defaults = $this->defaultSetorPalette();
        $custom = AgendaUsuarioCor::query()
            ->where('user_id', $viewer->id)
            ->pluck('cor', 'target_user_id')
            ->all();

        $cache = [];
        foreach (($this->userSetorCache ?? []) as $userId => $setor) {
            $setorNome = $setor['nome'] ?? null;
            $fallback = $setorNome && isset($defaults[$setorNome]) ? $defaults[$setorNome] : '#64748b';
            $cache[(int) $userId] = $custom[$userId] ?? $fallback;
        }

        // Aplica também cores customizadas para usuários que não tem setor (caem no fallback cinza)
        foreach ($custom as $targetId => $cor) {
            if (! isset($cache[(int) $targetId])) {
                $cache[(int) $targetId] = $cor;
            }
        }

        $this->userCoresCache = $cache;
    }

    /**
     * Carrega no cache os tipos de evento dos setores que o viewer enxerga.
     * Chave: slug → ['nome' => ..., 'cor' => ...].
     */
    protected function primeTiposCache(?User $viewer): void
    {
        $query = AgendaTipoEvento::query();

        if ($viewer) {
            $setorIds = $viewer->setoresVisiveisIds();
            if (is_array($setorIds)) {
                if (empty($setorIds)) {
                    $this->tiposCache = [];
                    return;
                }
                $query->whereIn('setor_id', $setorIds);
            }
        }

        $cache = [];
        foreach ($query->get(['slug', 'nome', 'cor']) as $tipo) {
            // Em caso de duplicidade de slug entre setores, o primeiro encontrado vence
            if (! isset($cache[$tipo->slug])) {
                $cache[$tipo->slug] = ['nome' => $tipo->nome, 'cor' => $tipo->cor];
            }
        }

        $this->tiposCache = $cache;
    }

    /**
     * Lista de tipos para o filtro do sidebar (todos os setores visíveis ao viewer).
     * Sempre inclui no topo os tipos automáticos do sistema (VT e Relatório fotográfico de posse),
     * para que os eventos gerados automaticamente possam ser filtrados.
     *
     * @return array<int, array{slug:string,nome:string,cor:string}>
     */
    public function tiposEventoForViewer(?User $viewer): array
    {
        $this->primeTiposCache($viewer);

        $automaticos = [
            ['slug' => 'vt', 'nome' => 'Visita técnica', 'cor' => '#f59e0b'],
            ['slug' => 'relatorio_fotografico', 'nome' => 'Relatório fotográfico de posse', 'cor' => '#7c3aed'],
        ];

        $custom = collect($this->tiposCache ?? [])
            ->reject(fn (array $meta, string $slug) => in_array($slug, ['vt', 'relatorio_fotografico'], true))
            ->map(fn (array $meta, string $slug): array => [
                'slug' => $slug,
                'nome' => $meta['nome'],
                'cor' => $meta['cor'],
            ])
            ->sortBy('nome')
            ->values()
            ->all();

        return array_merge($automaticos, $custom);
    }

    /**
     * Lista de tipos disponíveis para o usuário criar/editar eventos manuais.
     * Restringe ao(s) setor(es) do próprio usuário.
     *
     * @return array<int, array{slug:string,nome:string,cor:string}>
     */
    public function tiposEventoForCreator(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $setorIds = $user->setores()->pluck('setores.id')->all();

        if (empty($setorIds)) {
            return [];
        }

        return AgendaTipoEvento::query()
            ->whereIn('setor_id', $setorIds)
            ->orderBy('nome')
            ->get(['slug', 'nome', 'cor'])
            ->map(fn (AgendaTipoEvento $t): array => [
                'slug' => $t->slug,
                'nome' => $t->nome,
                'cor' => $t->cor,
            ])
            ->all();
    }

    /**
     * Tipos cadastrados pelo Coordenador no seu setor (para a aba de gestão).
     *
     * @return array<int, array{id:int,slug:string,nome:string,cor:string}>
     */
    public function tiposEventoForCoordenador(?User $coordenador): array
    {
        if (! $coordenador || ! $coordenador->podeGerenciarTiposAgenda()) {
            return [];
        }

        $setor = $coordenador->primeiroSetor();
        if (! $setor) {
            return [];
        }

        return AgendaTipoEvento::query()
            ->where('setor_id', $setor->id)
            ->orderBy('nome')
            ->get(['id', 'slug', 'nome', 'cor'])
            ->map(fn (AgendaTipoEvento $t): array => [
                'id' => (int) $t->id,
                'slug' => $t->slug,
                'nome' => $t->nome,
                'cor' => $t->cor,
            ])
            ->all();
    }

    public function criarTipoEvento(User $coordenador, string $nome, string $cor): AgendaTipoEvento
    {
        if (! $coordenador->podeGerenciarTiposAgenda()) {
            throw new \RuntimeException('Apenas Coordenadores podem criar tipos de evento.');
        }

        $setor = $coordenador->primeiroSetor();
        if (! $setor) {
            throw new \RuntimeException('Coordenador sem setor vinculado.');
        }

        $nome = trim($nome);
        if ($nome === '') {
            throw new \RuntimeException('O nome do tipo é obrigatório.');
        }

        $slug = $this->gerarSlugUnico($setor->id, $nome);

        return AgendaTipoEvento::create([
            'setor_id' => $setor->id,
            'slug' => $slug,
            'nome' => $nome,
            'cor' => $cor ?: '#64748b',
            'created_by' => $coordenador->id,
        ]);
    }

    public function atualizarTipoEvento(User $coordenador, int $tipoId, ?string $nome, ?string $cor): AgendaTipoEvento
    {
        if (! $coordenador->podeGerenciarTiposAgenda()) {
            throw new \RuntimeException('Apenas Coordenadores podem editar tipos de evento.');
        }

        $tipo = AgendaTipoEvento::query()->findOrFail($tipoId);

        if ($tipo->setor_id !== $coordenador->primeiroSetor()?->id) {
            throw new \RuntimeException('Você não pode editar tipos de outro setor.');
        }

        if (filled($nome) && trim($nome) !== '') {
            $tipo->nome = trim($nome);
        }

        if (filled($cor)) {
            $tipo->cor = $cor;
        }

        $tipo->save();

        return $tipo;
    }

    /**
     * @return array{ok:bool,message:?string}
     */
    public function removerTipoEvento(User $coordenador, int $tipoId): array
    {
        if (! $coordenador->podeGerenciarTiposAgenda()) {
            return ['ok' => false, 'message' => 'Apenas Coordenadores podem remover tipos de evento.'];
        }

        $tipo = AgendaTipoEvento::query()->find($tipoId);
        if (! $tipo) {
            return ['ok' => false, 'message' => 'Tipo não encontrado.'];
        }

        if ($tipo->setor_id !== $coordenador->primeiroSetor()?->id) {
            return ['ok' => false, 'message' => 'Você não pode remover tipos de outro setor.'];
        }

        $emUso = AgendaEvent::query()->where('event_type', $tipo->slug)->exists();
        if ($emUso) {
            return ['ok' => false, 'message' => 'Este tipo está vinculado a eventos existentes e não pode ser removido.'];
        }

        $tipo->delete();

        return ['ok' => true, 'message' => null];
    }

    protected function gerarSlugUnico(int $setorId, string $nome): string
    {
        $base = Str::slug($nome, '_');
        if ($base === '') {
            $base = 'tipo';
        }

        $slug = $base;
        $i = 2;
        while (AgendaTipoEvento::query()->where('setor_id', $setorId)->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, int>
     */
    protected function manualEventIdsWhereUserIsParticipant(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return AgendaEvent::query()
            ->whereHas('participants', fn ($q) => $q->whereIn('users.id', $userIds))
            ->pluck('id')
            ->all();
    }

    public function saveManualEvent(array $data, ?AgendaEvent $event = null): AgendaEvent
    {
        $payload = [
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'starts_at' => Carbon::parse((string) $data['starts_at']),
            'ends_at' => blank($data['ends_at'] ?? null) ? null : Carbon::parse((string) $data['ends_at']),
            'all_day' => (bool) ($data['all_day'] ?? false),
            'origin' => 'manual',
            'event_type' => (string) ($data['event_type'] ?? 'geral'),
            'status' => (string) ($data['status'] ?? 'agendado'),
            'color' => $this->nullIfBlank($data['color'] ?? null),
            'location' => $this->nullIfBlank($data['location'] ?? null),
            'responsible_user_id' => $this->nullableInt($data['responsible_user_id'] ?? null),
            'projeto_id' => $this->nullableInt($data['projeto_id'] ?? null),
            'obra_id' => $this->nullableInt($data['obra_id'] ?? null),
            'relatorio_visita_tecnica_id' => $this->nullableInt($data['relatorio_visita_tecnica_id'] ?? null),
            'updated_by' => auth()->id(),
        ];

        if ($payload['all_day']) {
            $payload['starts_at'] = Carbon::parse((string) $data['starts_at'])->startOfDay();
            $payload['ends_at'] = blank($data['ends_at'] ?? null)
                ? Carbon::parse((string) $data['starts_at'])->startOfDay()->addDay()
                : Carbon::parse((string) $data['ends_at']);
        }

        if ($event) {
            $event->update($payload);

            return $event;
        }

        $payload['created_by'] = auth()->id();

        return AgendaEvent::create($payload);
    }

    public function deleteManualEvent(AgendaEvent $event): void
    {
        $event->delete();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectManualEvents(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        return AgendaEvent::query()
            ->with([
                'responsibleUser:id,name',
                'projeto:id,nome,codigo,pais_id,estado_id,cidade_id',
                'projeto.pais:id,nome',
                'projeto.estado:id,nome',
                'projeto.cidade:id,nome',
                'obra:id,codigo,unidade,projeto_id',
                'obra.projeto:id,nome,codigo,pais_id,estado_id,cidade_id',
                'obra.projeto.pais:id,nome',
                'obra.projeto.estado:id,nome',
                'obra.projeto.cidade:id,nome',
                'relatorioVisitaTecnica:id,projeto_id,agendado_em,numero_relatorio_vt',
            ])
            ->where(function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('starts_at', [$rangeStart, $rangeEnd->copy()->endOfDay()])
                    ->orWhere(function (Builder $nested) use ($rangeStart, $rangeEnd): void {
                        $nested->whereNotNull('ends_at')
                            ->whereBetween('ends_at', [$rangeStart, $rangeEnd->copy()->endOfDay()]);
                    })
                    ->orWhere(function (Builder $nested) use ($rangeStart, $rangeEnd): void {
                        $nested->where('starts_at', '<=', $rangeStart)
                            ->where(function (Builder $inner) use ($rangeEnd): void {
                                $inner->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $rangeEnd->copy()->startOfDay());
                            });
                    });
            })
            ->get()
            ->map(fn (AgendaEvent $event): array => $this->mapManualEvent($event))
            ->filter(fn (array $event) => $this->eventIntersectsRange($event, $rangeStart, $rangeEnd));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectProjetoEvents(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $projects = $this->projectQueryForRange($rangeStart, $rangeEnd)
            ->with(['responsavelCom:id,name', 'responsavelEng:id,name', 'respPmo:id,name', 'responsavel:id,name'])
            ->select([
                'id',
                'nome',
                'codigo',
                'status',
                'status_comite',
                'user_id',
                'data_ass_contrato',
                'cad_plan_inicio',
                'cad_plan_fim',
                'cad_rea_inicio',
                'cad_rea_fim',
                'vis_plan_inicio',
                'vis_plan_fim',
                'vis_rea_inicio',
                'vis_rea_fim',
                'brief_plan',
                'brief_plan_lay_inicio',
                'brief_plan_lay_fim',
                'brief_real',
                'brief_real_lay_inicio',
                'brief_real_lay_fim',
                'ordem_planej_ini',
                'ordem_planej_fim',
                'ordem_realizado',
                'ordem_realizado_fim',
                'proj_planej_reuniao_start',
                'proj_real_reuniao_start',
                'proj_plan_ini',
                'proj_plan_fim',
                'proj_real_ini',
                'proj_real_fim',
                'orca_reuniao_kickoff',
                'orca_planejado_ini',
                'orca_planejado_fim',
                'orca_real_ini',
                'orca_real_fim',
                'legal_plan_ini',
                'legal_plan_fim',
                'legal_realizado_ini',
                'legal_realizado_fim',
                'data_posse',
                'entrega_projeto',
                'inicio_obra',
                'entrega_obra',
                'inauguracao',
                'data_entrega_shell',
            ])
            ->get();

        $definitions = $this->projectDefinitions();

        return $projects->flatMap(function (Projeto $project) use ($definitions, $rangeStart, $rangeEnd): Collection {
            $events = collect();

            foreach ($definitions as $definition) {
                $field = $definition['field'];
                $value = $project->{$field} ?? null;

                if (blank($value)) {
                    continue;
                }

                $start = Carbon::parse($value);
                $end = ! empty($definition['end_field']) && filled($project->{$definition['end_field']} ?? null)
                    ? Carbon::parse($project->{$definition['end_field']})
                    : null;
                if ($end && ($definition['all_day'] ?? false)) {
                    $end = $end->copy()->addDay();
                }

                $event = $this->buildEvent([
                    'uid' => 'projeto-'.$project->id.'-'.$field,
                    'title' => $definition['title'],
                    'description' => $project->nome ?: 'Projeto sem nome',
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'all_day' => (bool) ($definition['all_day'] ?? true),
                    'origin' => 'projeto',
                    'origin_label' => 'Projeto',
                    'event_type' => $definition['event_type'],
                    'status' => $definition['status'] ?? 'previsto',
                    'color' => $definition['color'],
                    'location' => $project->cidade?->nome ?? null,
                    'responsible_user_id' => $project->responsavelCom?->id
                        ?? $project->responsavelEng?->id
                        ?? $project->respPmo?->id
                        ?? $project->responsavel?->id,
                    'responsible_name' => $project->responsavelCom?->name
                        ?? $project->responsavelEng?->name
                        ?? $project->respPmo?->name
                        ?? $project->responsavel?->name,
                    'entity_label' => $project->codigo ? "{$project->codigo} · {$project->nome}" : $project->nome,
                    'entity_url' => ProjetoResource::getUrl('visualizar-ponto', ['record' => $project->id]),
                    'entity_edit_url' => ProjetoResource::getUrl('editar-ponto', ['record' => $project->id]),
                    'project_id' => $project->id,
                    'obra_id' => null,
                    'relatorio_visita_tecnica_id' => null,
                    'source_id' => $project->id,
                ]);

                if ($this->eventIntersectsRange($event, $rangeStart, $rangeEnd)) {
                    $events->push($event);
                }
            }

            return $events;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectObraEvents(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $obras = $this->obraQueryForRange($rangeStart, $rangeEnd)
            ->with(['projeto:id,nome,codigo,user_id,resp_com,resp_eng,resp_pmo', 'projeto.responsavelCom:id,name', 'projeto.responsavelEng:id,name', 'projeto.respPmo:id,name'])
            ->get();

        $definitions = $this->obraDefinitions();

        $manualEventObraIds = AgendaEvent::whereNotNull('obra_id')
            ->pluck('obra_id')
            ->toArray();

        $manualEventRfIds = AgendaEvent::whereNotNull('relatorio_fotografico_id')
            ->pluck('obra_id')
            ->toArray();

        return $obras->flatMap(function (Obras $obra) use ($definitions, $rangeStart, $rangeEnd, $manualEventObraIds, $manualEventRfIds): Collection {
            $events = collect();

            foreach ($definitions as $definition) {
                $field = $definition['field'];
                $value = $obra->{$field} ?? null;

                if (blank($value)) {
                    continue;
                }

                if ($field === 'data_agendamento_vt' && in_array($obra->id, $manualEventObraIds)) {
                    continue;
                }

                if ($field === 'data_agendamento_rf' && in_array($obra->id, $manualEventRfIds)) {
                    continue;
                }

                $start = Carbon::parse($value);
                $end = ! empty($definition['end_field']) && filled($obra->{$definition['end_field']} ?? null)
                    ? Carbon::parse($obra->{$definition['end_field']})
                    : null;
                if ($end && ($definition['all_day'] ?? false)) {
                    $end = $end->copy()->addDay();
                }

                $responsible = $obra->projeto?->responsavelCom?->name
                    ?? $obra->projeto?->responsavelEng?->name
                    ?? $obra->projeto?->respPmo?->name;
                $responsibleId = $obra->projeto?->responsavelCom?->id
                    ?? $obra->projeto?->responsavelEng?->id
                    ?? $obra->projeto?->respPmo?->id;

                $event = $this->buildEvent([
                    'uid' => 'obra-'.$obra->id.'-'.$field,
                    'title' => $definition['title'],
                    'description' => $obra->unidade ?: ($obra->projeto?->nome ?? 'Unidade'),
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'all_day' => (bool) ($definition['all_day'] ?? true),
                    'origin' => 'unidade',
                    'origin_label' => 'Unidade',
                    'event_type' => $definition['event_type'],
                    'status' => $definition['status'] ?? 'realizado',
                    'color' => $definition['color'],
                    'location' => $obra->cidade ?: null,
                    'responsible_user_id' => $responsibleId,
                    'responsible_name' => $responsible,
                    'entity_label' => $obra->codigo ? "{$obra->codigo} · {$obra->unidade}" : ($obra->unidade ?? 'Unidade'),
                    'entity_url' => ObrasResource::getUrl('view', ['record' => $obra->id]),
                    'entity_edit_url' => ObrasResource::getUrl('edit', ['record' => $obra->id]),
                    'project_id' => $obra->projeto_id,
                    'obra_id' => $obra->id,
                    'relatorio_visita_tecnica_id' => null,
                    'source_id' => $obra->id,
                ]);

                if ($this->eventIntersectsRange($event, $rangeStart, $rangeEnd)) {
                    $events->push($event);
                }
            }

            return $events;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectVisitaTecnicaEvents(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $vt = $this->visitaTecnicaQueryForRange($rangeStart, $rangeEnd)
            ->with(['projeto:id,nome,codigo,user_id,resp_com', 'projeto.responsavelCom:id,name'])
            ->get();

        $manualEventVtIds = AgendaEvent::whereNotNull('relatorio_visita_tecnica_id')
            ->pluck('relatorio_visita_tecnica_id')
            ->toArray();

        return $vt->filter(fn (RelatorioVisitaTecnica $record) => !in_array($record->id, $manualEventVtIds))
            ->flatMap(function (RelatorioVisitaTecnica $record) use ($rangeStart, $rangeEnd): Collection {
            $events = collect();

            $eventsData = [
                [
                    'field' => 'agendado_em',
                    'title' => 'VT agendada',
                    'event_type' => 'vt',
                    'status' => 'agendado',
                    'color' => '#f59e0b',
                ],
            ];

            foreach ($eventsData as $definition) {
                $value = $record->{$definition['field']} ?? null;
                if (blank($value)) {
                    continue;
                }

                $start = Carbon::parse($value);

                $event = $this->buildEvent([
                    'uid' => 'vt-'.$record->id.'-'.$definition['field'],
                    'title' => $definition['title'],
                    'description' => $record->projeto?->nome ?: 'Visita técnica',
                    'starts_at' => $start,
                    'ends_at' => null,
                    'all_day' => false,
                    'origin' => 'vt',
                    'origin_label' => 'VT',
                    'event_type' => $definition['event_type'],
                    'status' => $definition['status'],
                    'color' => $definition['color'],
                    'location' => $record->unidade_relatorio ?: null,
                    'responsible_user_id' => $record->projeto?->responsavelCom?->id,
                    'responsible_name' => $record->projeto?->responsavelCom?->name,
                    'entity_label' => $record->numero_relatorio_vt ? "{$record->numero_relatorio_vt} · ".($record->projeto?->codigo ?? $record->projeto?->nome ?? 'VT') : ($record->projeto?->nome ?? 'VT'),
                    'entity_url' => RelatorioVisitaTecnicaResource::getUrl('view', ['record' => $record->id]),
                    'entity_edit_url' => RelatorioVisitaTecnicaResource::getUrl('edit', ['record' => $record->id]),
                    'project_id' => $record->projeto_id,
                    'obra_id' => null,
                    'relatorio_visita_tecnica_id' => $record->id,
                    'source_id' => $record->id,
                ]);

                if ($this->eventIntersectsRange($event, $rangeStart, $rangeEnd)) {
                    $events->push($event);
                }
            }

            return $events;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectRelatorioFotograficoEvents(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $rf = RelatorioFotografico::whereNotNull('agendado_em')
            ->with(['projeto:id,nome,codigo,pais_id,estado_id,cidade_id', 'projeto.pais:id,nome', 'projeto.estado:id,nome', 'projeto.cidade:id,nome', 'autor:id,name'])
            ->whereBetween('agendado_em', [
                $rangeStart->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->get();

        $manualEventRfIds = AgendaEvent::whereNotNull('relatorio_fotografico_id')
            ->pluck('relatorio_fotografico_id')
            ->toArray();

        return $rf->filter(fn (RelatorioFotografico $record) => !in_array($record->id, $manualEventRfIds))
            ->flatMap(function (RelatorioFotografico $record) use ($rangeStart, $rangeEnd): Collection {
            $events = collect();

            $eventsData = [
                [
                    'field' => 'agendado_em',
                    'title' => 'Relatório fotográfico de posse',
                    'event_type' => 'relatorio_fotografico',
                    'status' => 'agendado',
                    'color' => '#7c3aed',
                ],
            ];

            foreach ($eventsData as $definition) {
                $value = $record->{$definition['field']} ?? null;
                if (blank($value)) {
                    continue;
                }

                $start = Carbon::parse($value);

                $event = $this->buildEvent([
                    'uid' => 'rf-'.$record->id.'-'.$definition['field'],
                    'title' => $definition['title'],
                    'description' => null,
                    'starts_at' => $start,
                    'ends_at' => null,
                    'all_day' => false,
                    'origin' => 'relatorio_fotografico',
                    'origin_label' => 'Relatório fotográfico',
                    'event_type' => $definition['event_type'],
                    'status' => $definition['status'],
                    'color' => $definition['color'],
                    'location' => null,
                    'responsible_user_id' => $record->autor_id,
                    'responsible_name' => $record->autor?->name,
                    'entity_label' => $record->projeto?->nome ?? 'Relatório fotográfico',
                    'entity_url' => RelatorioFotograficoResource::getUrl('view', ['record' => $record->id]),
                    'entity_edit_url' => RelatorioFotograficoResource::getUrl('edit', ['record' => $record->id]),
                    'project_id' => $record->projeto_id,
                    'obra_id' => null,
                    'relatorio_fotografico_id' => $record->id,
                    'source_id' => $record->id,
                    'pais' => $record->projeto?->pais?->nome ?? null,
                    'estado' => $record->projeto?->estado?->nome ?? null,
                    'cidade' => $record->projeto?->cidade?->nome ?? null,
                ]);

                if ($this->eventIntersectsRange($event, $rangeStart, $rangeEnd)) {
                    $events->push($event);
                }
            }

            return $events;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function projectDefinitions(): array
    {
        return [
            ['field' => 'data_ass_contrato', 'title' => 'Contrato assinado', 'event_type' => 'contrato', 'color' => '#16a34a', 'all_day' => true],
            ['field' => 'proj_planej_reuniao_start', 'title' => 'Reunião de projeto planejada', 'event_type' => 'reuniao', 'color' => '#2563eb', 'all_day' => true],
            ['field' => 'proj_real_reuniao_start', 'title' => 'Reunião de projeto realizada', 'event_type' => 'reuniao', 'color' => '#1d4ed8', 'all_day' => true],
            ['field' => 'orca_reuniao_kickoff', 'title' => 'Kickoff de orçamento', 'event_type' => 'reuniao', 'color' => '#7c3aed', 'all_day' => true],
            ['field' => 'data_posse', 'title' => 'Posse', 'event_type' => 'posse', 'color' => '#db2777', 'all_day' => true],
            ['field' => 'entrega_projeto', 'title' => 'Entrega do projeto', 'event_type' => 'entrega', 'color' => '#0f766e', 'all_day' => true],
            ['field' => 'inicio_obra', 'title' => 'Início de obra', 'event_type' => 'obra', 'color' => '#ea580c', 'all_day' => true],
            ['field' => 'entrega_obra', 'title' => 'Entrega da obra', 'event_type' => 'obra', 'color' => '#b45309', 'all_day' => true],
            ['field' => 'inauguracao', 'title' => 'Inauguração', 'event_type' => 'inauguracao', 'color' => '#dc2626', 'all_day' => true],
            ['field' => 'data_entrega_shell', 'title' => 'Entrega do shell', 'event_type' => 'shell', 'color' => '#6366f1', 'all_day' => true],
            ['field' => 'cad_plan_inicio', 'end_field' => 'cad_plan_fim', 'title' => 'Cadastro planejado', 'event_type' => 'cadastro', 'color' => '#0ea5e9', 'all_day' => true],
            ['field' => 'cad_rea_inicio', 'end_field' => 'cad_rea_fim', 'title' => 'Cadastro realizado', 'event_type' => 'cadastro', 'color' => '#0284c7', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'vis_plan_inicio', 'end_field' => 'vis_plan_fim', 'title' => 'Visita técnica planejada', 'event_type' => 'vt', 'color' => '#f59e0b', 'all_day' => true],
            ['field' => 'vis_rea_inicio', 'end_field' => 'vis_rea_fim', 'title' => 'Visita técnica realizada', 'event_type' => 'vt', 'color' => '#d97706', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'brief_plan_lay_inicio', 'end_field' => 'brief_plan_lay_fim', 'title' => 'Briefing e layout planejados', 'event_type' => 'briefing', 'color' => '#14b8a6', 'all_day' => true],
            ['field' => 'brief_real_lay_inicio', 'end_field' => 'brief_real_lay_fim', 'title' => 'Briefing e layout realizados', 'event_type' => 'briefing', 'color' => '#0f766e', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'ordem_planej_ini', 'end_field' => 'ordem_planej_fim', 'title' => 'Ordem de investimento planejada', 'event_type' => 'ordem', 'color' => '#8b5cf6', 'all_day' => true],
            ['field' => 'ordem_realizado', 'end_field' => 'ordem_realizado_fim', 'title' => 'Ordem de investimento realizada', 'event_type' => 'ordem', 'color' => '#7c3aed', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'proj_plan_ini', 'end_field' => 'proj_plan_fim', 'title' => 'Projeto executivo planejado', 'event_type' => 'projeto_executivo', 'color' => '#4f46e5', 'all_day' => true],
            ['field' => 'proj_real_ini', 'end_field' => 'proj_real_fim', 'title' => 'Projeto executivo realizado', 'event_type' => 'projeto_executivo', 'color' => '#4338ca', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'orca_planejado_ini', 'end_field' => 'orca_planejado_fim', 'title' => 'Orçamentos planejados', 'event_type' => 'orcamento', 'color' => '#db2777', 'all_day' => true],
            ['field' => 'orca_real_ini', 'end_field' => 'orca_real_fim', 'title' => 'Orçamentos realizados', 'event_type' => 'orcamento', 'color' => '#be185d', 'all_day' => true, 'status' => 'realizado'],
            ['field' => 'legal_plan_ini', 'end_field' => 'legal_plan_fim', 'title' => 'Legalização planejada', 'event_type' => 'legalizacao', 'color' => '#059669', 'all_day' => true],
            ['field' => 'legal_realizado_ini', 'end_field' => 'legal_realizado_fim', 'title' => 'Legalização realizada', 'event_type' => 'legalizacao', 'color' => '#047857', 'all_day' => true, 'status' => 'realizado'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function obraDefinitions(): array
    {
        return [
            ['field' => 'data_agendamento_vt', 'title' => 'VT agendada', 'event_type' => 'vt', 'color' => '#d97706', 'all_day' => true],
            ['field' => 'data_agendamento_rf', 'title' => 'Relatório fotográfico de posse', 'event_type' => 'relatorio_fotografico', 'color' => '#7c3aed', 'all_day' => true, 'status' => 'agendado'],
        ];
    }

    protected function projectQueryForRange(Carbon $rangeStart, Carbon $rangeEnd): Builder
    {
        $query = Projeto::query();

        $query->where(function (Builder $builder) use ($rangeStart, $rangeEnd): void {
            foreach ($this->projectDefinitions() as $definition) {
                $field = $definition['field'];
                $endField = $definition['end_field'] ?? null;

                $builder->orWhere(function (Builder $nested) use ($field, $endField, $rangeStart, $rangeEnd): void {
                    if ($endField) {
                        $nested->whereNotNull($field)
                            ->whereNotNull($endField)
                            ->whereDate($field, '<=', $rangeEnd->toDateString())
                            ->whereDate($endField, '>=', $rangeStart->toDateString());

                        return;
                    }

                    $nested->whereBetween($field, [
                        $rangeStart->toDateString(),
                        $rangeEnd->toDateString(),
                    ]);
                });
            }
        });

        return $query;
    }

    protected function obraQueryForRange(Carbon $rangeStart, Carbon $rangeEnd): Builder
    {
        $query = Obras::query();

        $query->where(function (Builder $builder) use ($rangeStart, $rangeEnd): void {
            foreach ($this->obraDefinitions() as $definition) {
                $field = $definition['field'];
                $endField = $definition['end_field'] ?? null;

                $builder->orWhere(function (Builder $nested) use ($field, $endField, $rangeStart, $rangeEnd): void {
                    if ($endField) {
                        $nested->whereNotNull($field)
                            ->whereNotNull($endField)
                            ->whereDate($field, '<=', $rangeEnd->toDateString())
                            ->whereDate($endField, '>=', $rangeStart->toDateString());

                        return;
                    }

                    $nested->whereBetween($field, [
                        $rangeStart->toDateString(),
                        $rangeEnd->toDateString(),
                    ]);
                });
            }
        });

        return $query;
    }

    protected function visitaTecnicaQueryForRange(Carbon $rangeStart, Carbon $rangeEnd): Builder
    {
        return RelatorioVisitaTecnica::query()
            ->where(function (Builder $builder) use ($rangeStart, $rangeEnd): void {
                $builder->whereBetween('agendado_em', [$rangeStart, $rangeEnd->copy()->endOfDay()]);
            });
    }

    protected function buildEvent(array $data): array
    {
        $startsAt = $data['starts_at'] instanceof Carbon ? $data['starts_at'] : Carbon::parse((string) $data['starts_at']);
        $endsAt = isset($data['ends_at']) && $data['ends_at']
            ? ($data['ends_at'] instanceof Carbon ? $data['ends_at'] : Carbon::parse((string) $data['ends_at']))
            : null;
        $allDay = (bool) ($data['all_day'] ?? false);

        if ($allDay && ! $endsAt) {
            $endsAt = $startsAt->copy()->addDay();
        }

        $responsibleId = $this->nullableInt($data['responsible_user_id'] ?? null);
        $responsibleName = $this->nullIfBlank($data['responsible_name'] ?? null);

        return [
            'uid' => (string) ($data['uid'] ?? Str::uuid()),
            'title' => (string) ($data['title'] ?? ''),
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt?->toDateTimeString(),
            'all_day' => $allDay,
            'date_key' => $startsAt->toDateString(),
            'time_label' => $allDay ? 'Dia inteiro' : $startsAt->format('H:i'),
            'range_label' => $this->rangeLabel($startsAt, $endsAt, $allDay),
            'origin' => (string) ($data['origin'] ?? 'manual'),
            'origin_label' => (string) ($data['origin_label'] ?? ucfirst((string) ($data['origin'] ?? 'manual'))),
            'event_type' => (string) ($data['event_type'] ?? 'geral'),
            'event_type_label' => $this->typeLabel((string) ($data['event_type'] ?? 'geral')),
            'status' => (string) ($data['status'] ?? 'agendado'),
            'status_label' => $this->statusLabel((string) ($data['status'] ?? 'agendado')),
            'color' => (string) ($data['color'] ?? '#64748b'),
            'location' => $this->nullIfBlank($data['location'] ?? null),
            'responsible_user_id' => $responsibleId,
            'responsible_name' => $responsibleName,
            'entity_label' => $this->nullIfBlank($data['entity_label'] ?? null),
            'entity_url' => $this->nullIfBlank($data['entity_url'] ?? null),
            'entity_edit_url' => $this->nullIfBlank($data['entity_edit_url'] ?? null),
            'project_id' => $this->nullableInt($data['project_id'] ?? null),
            'obra_id' => $this->nullableInt($data['obra_id'] ?? null),
            'relatorio_visita_tecnica_id' => $this->nullableInt($data['relatorio_visita_tecnica_id'] ?? null),
            'relatorio_fotografico_id' => $this->nullableInt($data['relatorio_fotografico_id'] ?? null),
            'source_id' => $this->nullableInt($data['source_id'] ?? null),
            'pais' => $this->nullIfBlank($data['pais'] ?? null),
            'estado' => $this->nullIfBlank($data['estado'] ?? null),
            'cidade' => $this->nullIfBlank($data['cidade'] ?? null),
            'display_dates' => $this->buildDisplayDates($startsAt, $endsAt, $allDay),
        ];
    }

    protected function mapManualEvent(AgendaEvent $event): array
    {
        $responsibleName = $event->responsibleUser?->name;
        $typeColor = $this->defaultColorForType($event->event_type);

        // Puxar localização do projeto ou via obra->projeto
        $pais = null;
        $estado = null;
        $cidade = null;

        if ($event->projeto) {
            $pais = $event->projeto->pais?->nome;
            $estado = $event->projeto->estado?->nome;
            $cidade = $event->projeto->cidade?->nome;
        } elseif ($event->obra?->projeto) {
            $pais = $event->obra->projeto->pais?->nome;
            $estado = $event->obra->projeto->estado?->nome;
            $cidade = $event->obra->projeto->cidade?->nome;
        }

        return $this->buildEvent([
            'uid' => 'manual-'.$event->id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'all_day' => $event->all_day,
            'origin' => 'manual',
            'origin_label' => 'Manual',
            'event_type' => $event->event_type,
            'status' => $event->status,
            'color' => $event->color ?: $typeColor,
            'dot_color' => $typeColor,
            'location' => $event->location,
            'responsible_user_id' => $event->responsible_user_id,
            'responsible_name' => $responsibleName,
            'entity_label' => $this->manualEntityLabel($event),
            'entity_url' => $this->manualEntityUrl($event),
            'entity_edit_url' => $this->manualEntityUrl($event),
            'project_id' => $event->projeto_id,
            'obra_id' => $event->obra_id,
            'relatorio_visita_tecnica_id' => $event->relatorio_visita_tecnica_id,
            'source_id' => $event->id,
            'pais' => $pais,
            'estado' => $estado,
            'cidade' => $cidade,
        ]) + [
            'manual_event_id' => $event->id,
            'can_edit' => true,
            'can_delete' => true,
        ];
    }

    protected function defaultColorForType(?string $type): string
    {
        if ($type && isset($this->tiposCache[$type]['cor'])) {
            return $this->tiposCache[$type]['cor'];
        }

        return match ($type) {
            'vt' => '#f59e0b',
            'reuniao' => '#2563eb',
            'contrato' => '#16a34a',
            'obra' => '#ea580c',
            'implantacao' => '#0ea5e9',
            'checklist' => '#14b8a6',
            'entrega' => '#0f766e',
            'posse' => '#db2777',
            'inauguracao' => '#dc2626',
            'ponto' => '#8b5cf6',
            'pendencia' => '#ef4444',
            'relatorio_fotografico' => '#7c3aed',
            default => '#64748b',
        };
    }

    protected function manualEntityLabel(AgendaEvent $event): ?string
    {
        if ($event->projeto) {
            return $event->projeto->codigo
                ? "{$event->projeto->codigo} · {$event->projeto->nome}"
                : $event->projeto->nome;
        }

        if ($event->obra) {
            return $event->obra->codigo
                ? "{$event->obra->codigo} · {$event->obra->unidade}"
                : $event->obra->unidade;
        }

        if ($event->relatorioVisitaTecnica) {
            return $event->relatorioVisitaTecnica->numero_relatorio_vt
                ? "{$event->relatorioVisitaTecnica->numero_relatorio_vt}"
                : 'Visita técnica';
        }

        return 'Evento manual';
    }

    protected function manualEntityUrl(AgendaEvent $event): ?string
    {
        if ($event->projeto_id) {
            return ProjetoResource::getUrl('visualizar-ponto', ['record' => $event->projeto_id]);
        }

        if ($event->obra_id) {
            return ObrasResource::getUrl('view', ['record' => $event->obra_id]);
        }

        if ($event->relatorio_visita_tecnica_id) {
            return RelatorioVisitaTecnicaResource::getUrl('view', ['record' => $event->relatorio_visita_tecnica_id]);
        }

        return null;
    }

    protected function eventIntersectsRange(array $event, Carbon $rangeStart, Carbon $rangeEnd): bool
    {
        $start = Carbon::parse((string) $event['starts_at']);
        $end = ! empty($event['ends_at']) ? Carbon::parse((string) $event['ends_at']) : $start->copy()->addSecond();

        return $start->lte($rangeEnd) && $end->gte($rangeStart);
    }

    protected function rangeLabel(Carbon $startsAt, ?Carbon $endsAt, bool $allDay): string
    {
        if ($allDay) {
            if ($endsAt && $startsAt->diffInDays($endsAt) > 1) {
                return $startsAt->format('d/m').' - '.$endsAt->copy()->subDay()->format('d/m');
            }

            return $startsAt->format('d/m/Y');
        }

        if (! $endsAt) {
            return $startsAt->format('d/m/Y H:i');
        }

        return $startsAt->format('d/m/Y H:i').' - '.$endsAt->format('d/m/Y H:i');
    }

    protected function typeLabel(string $type): string
    {
        if ($type !== '' && isset($this->tiposCache[$type]['nome'])) {
            return $this->tiposCache[$type]['nome'];
        }

        return match ($type) {
            'vt' => 'Visita técnica',
            'reuniao' => 'Reunião',
            'contrato' => 'Contrato',
            'posse' => 'Posse',
            'entrega' => 'Entrega',
            'obra' => 'Obra',
            'shell' => 'Shell',
            'cadastro' => 'Cadastro',
            'briefing' => 'Briefing',
            'ordem' => 'Ordem de investimento',
            'projeto_executivo' => 'Projeto executivo',
            'orcamento' => 'Orçamento',
            'legalizacao' => 'Legalização',
            'implantacao' => 'Implantação',
            'checklist' => 'Checklist',
            'fachada' => 'Fachada',
            'energia' => 'Energia',
            'ponto' => 'Ponto',
            'pendencia' => 'Pendência',
            'relatorio_fotografico' => 'Relatório fotográfico de posse',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'agendado' => 'Agendado',
            'confirmado' => 'Confirmado',
            'previsto' => 'Previsto',
            'realizado' => 'Realizado',
            'concluido' => 'Concluído',
            'cancelado' => 'Cancelado',
            'em_andamento' => 'Em andamento',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected function nullIfBlank(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim((string) $value);
    }

    protected function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    /**
     * @return array<int, string>
     */
    protected function buildDisplayDates(Carbon $startsAt, ?Carbon $endsAt, bool $allDay): array
    {
        if (! $endsAt || ! $endsAt->gt($startsAt)) {
            return [$startsAt->toDateString()];
        }

        $dates = [];
        $cursor = $startsAt->copy()->startOfDay();
        $lastDate = $endsAt->copy()->subSecond()->startOfDay();

        while ($cursor->lte($lastDate)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates !== [] ? $dates : [$startsAt->toDateString()];
    }

    public function checkActivityExists(int $obraId, string $activity): array
    {
        $obra = Obras::find($obraId);
        if (!$obra) {
            return ['exists' => false, 'hint' => ''];
        }

        $projetoId = $obra->projeto_id;

        if ($activity === 'vt') {
            $vt = RelatorioVisitaTecnica::where('projeto_id', $projetoId)->latest()->first();
            if ($vt) {
                $agendadoEm = optional($vt->agendado_em)->format('d/m/Y H:i') ?? 'sem data';
                return [
                    'exists' => true,
                    'hint' => "Já existe uma VT para este projeto (agendada em {$agendadoEm}). O campo será atualizado com a nova data.",
                ];
            }
            return [
                'exists' => false,
                'hint' => 'Não existe VT para este projeto. Um novo relatório de visita técnica será criado.',
            ];
        }

        if ($activity === 'relatorio_fotografico') {
            $rf = RelatorioFotografico::where('projeto_id', $projetoId)->latest()->first();
            if ($rf) {
                return [
                    'exists' => true,
                    'hint' => 'Já existe um relatório fotográfico para este projeto. A data de envio na unidade será atualizada.',
                ];
            }
            return [
                'exists' => false,
                'hint' => 'Não existe relatório fotográfico para este projeto. Um novo será criado.',
            ];
        }

        return ['exists' => false, 'hint' => ''];
    }

    public function linkActivityToObra(int $obraId, string $activity, Carbon $startsAt, int $createdBy): ?int
    {
        $obra = Obras::find($obraId);
        if (!$obra) {
            return null;
        }

        $projetoId = $obra->projeto_id;

        if ($activity === 'vt') {
            $vt = RelatorioVisitaTecnica::where('projeto_id', $projetoId)->latest()->first();
            if ($vt) {
                $vt->update(['agendado_em' => $startsAt]);
            } else {
                $vt = RelatorioVisitaTecnica::create([
                    'projeto_id' => $projetoId,
                    'agendado_em' => $startsAt,
                ]);
            }
            $obra->update(['data_agendamento_vt' => $startsAt->toDateString()]);
            return $vt->id;
        }

        if ($activity === 'relatorio_fotografico') {
            $rf = RelatorioFotografico::where('projeto_id', $projetoId)->latest()->first();
            if (!$rf) {
                $rf = RelatorioFotografico::create([
                    'projeto_id' => $projetoId,
                    'autor_id' => $createdBy,
                    'status' => 'Rascunho',
                ]);
            }
            $obra->update(['data_agendamento_rf' => $startsAt]);
            return $rf->id;
        }

        return null;
    }
}
