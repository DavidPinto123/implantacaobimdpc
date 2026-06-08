<x-filament::page wire:init="loadAgenda">
    <div class="ag-shell">
        <div class="ag-topbar">
            <div>
                <p class="ag-kicker">Agenda operacional</p>
                <h1 class="ag-title">Agenda Geral</h1>
                <p class="ag-subtitle">Somente VT agendada e relatórios fotográficos das unidades em uma única visão.</p>
            </div>

            <div class="ag-top-actions">
                <button type="button" class="ag-btn ag-btn-ghost" wire:click="irParaHoje">Hoje</button>
                <button type="button" class="ag-btn ag-btn-ghost" wire:click="mesAnterior">←</button>
                <button type="button" class="ag-btn ag-btn-ghost" wire:click="proximoMes">→</button>
                <button type="button" class="ag-btn @if($viewMode === 'month') ag-btn-active @endif" wire:click="setViewMode('month')">Mês</button>
                <button type="button" class="ag-btn @if($viewMode === 'week') ag-btn-active @endif" wire:click="setViewMode('week')">Semana</button>
                <button type="button" class="ag-btn @if($viewMode === 'day') ag-btn-active @endif" wire:click="setViewMode('day')">Dia</button>
                <button type="button" class="ag-btn ag-btn-ghost ag-btn-icon" wire:click="openSettingsModal" title="Configurações da agenda">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="ag-icon" />
                </button>
                <button type="button" class="ag-btn ag-btn-ghost ag-btn-icon" wire:click="toggleInvitesTab" title="Meus convites">
                    <x-filament::icon icon="heroicon-o-inbox" class="ag-icon" />
                    @if(!empty($myPendingInvites))
                        <span class="ag-badge-count">{{ count($myPendingInvites) }}</span>
                    @endif
                </button>
                <button type="button" class="ag-btn ag-btn-primary" wire:click="openCreateEventModal">+ Novo evento</button>
            </div>
        </div>

        @if($agendaLoaded)
        <div class="ag-layout">
            <aside class="ag-sidebar">
                <div class="ag-panel">
                    <div class="ag-panel-head">
                        <span class="ag-panel-title">{{ \Carbon\Carbon::parse($mesReferencia)->translatedFormat('F \d\e Y') }}</span>
                    </div>
                    <div class="ag-mini-calendar">
                        @php
                            $mini = \Carbon\Carbon::parse($mesReferencia)->startOfMonth();
                            $miniStart = $mini->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                            $miniEnd = $mini->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
                            $cursor = $miniStart->copy();
                        @endphp
                        <div class="ag-mini-weekdays">
                            <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span>
                        </div>
                        <div class="ag-mini-grid">
                            @while($cursor->lte($miniEnd))
                                @php
                                    $date = $cursor->format('Y-m-d');
                                    $isCurrentMonth = $cursor->format('Y-m') === \Carbon\Carbon::parse($mesReferencia)->format('Y-m');
                                @endphp
                                <button type="button"
                                        class="ag-mini-day {{ $date === $selectedDate ? 'is-selected' : '' }} {{ $date === now()->format('Y-m-d') ? 'is-today' : '' }} {{ $isCurrentMonth ? '' : 'is-other-month' }}"
                                        wire:click="selecionarDataMini('{{ $date }}')">
                                    <span>{{ $cursor->format('j') }}</span>
                                </button>
                                @php $cursor->addDay(); @endphp
                            @endwhile
                        </div>
                    </div>
                </div>

                <div class="ag-panel">
                    <div class="ag-panel-head">
                        <span class="ag-panel-title">Filtros</span>
                    </div>
                    <div class="ag-filters">
                        <label>
                            <span>Origem</span>
                            <select wire:model.live="filters.origin">
                                <option value="all">Todas</option>
                                <option value="vt">VT</option>
                                <option value="unidade">Unidade</option>
                                <option value="manual">Manual</option>
                            </select>
                        </label>

                        <label>
                            <span>Tipo</span>
                            <select wire:model.live="filters.type">
                                <option value="all">Todos</option>
                                @foreach($tiposFiltro as $tipo)
                                    <option value="{{ $tipo['slug'] }}">{{ $tipo['nome'] }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span>Responsável</span>
                            <select wire:model.live="filters.responsible_user_id">
                                <option value="">Todos</option>
                                @foreach($responsibleOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span>Status</span>
                            <select wire:model.live="filters.status">
                                <option value="all">Todos</option>
                                <option value="agendado">Agendado</option>
                                <option value="previsto">Previsto</option>
                                <option value="confirmado">Confirmado</option>
                                <option value="realizado">Realizado</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                                <option value="em_andamento">Em andamento</option>
                            </select>
                        </label>

                        <label>
                            <span>Busca</span>
                            <input type="text" wire:model.live.debounce.400ms="filters.search" placeholder="VT, unidade, relatorio...">
                        </label>
                    </div>
                </div>

                <div class="ag-panel">
                    <div class="ag-panel-head">
                        <span class="ag-panel-title">Legenda</span>
                    </div>
                    <div class="ag-legend">
                        @forelse($tiposFiltro as $tipo)
                            <div><i style="background:{{ $tipo['cor'] }}"></i> {{ $tipo['nome'] }}</div>
                        @empty
                            <div class="ag-empty-small" style="padding:0;">Nenhum tipo cadastrado ainda.</div>
                        @endforelse
                    </div>
                </div>
            </aside>

            <main class="ag-main">
                <div class="ag-panel ag-main-panel">
                    <div class="ag-panel-head ag-panel-head-row">
                        <div>
                            <span class="ag-panel-title">
                                @if($viewMode === 'month')
                                    {{ \Carbon\Carbon::parse($mesReferencia)->translatedFormat('F Y') }}
                                @elseif($viewMode === 'week')
                                    Semana de {{ \Carbon\Carbon::parse($selectedDate)->startOfWeek(\Carbon\Carbon::MONDAY)->format('d/m/Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                                @endif
                            </span>
                            <p class="ag-panel-hint">{{ count($events) }} marcos carregados</p>
                        </div>
                        @if($selectedDate)
                            <span class="ag-date-pill">Selecionado: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                        @endif
                    </div>

                    <div class="ag-main-scroll">
                    @if($viewMode === 'month')
                        <div class="ag-weekdays">
                            <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span>
                        </div>
                        <div class="ag-month-grid">
                            @foreach($calendarWeeks as $week)
                                @php $multiCount = $week['multi_day_count'] ?? 0; @endphp
                                <div class="ag-week-row" style="--multi-count: {{ $multiCount }};">
                                    @if(!empty($week['multi_day_bars']))
                                        <div class="ag-multi-day-row">
                                            @foreach($week['multi_day_bars'] as $barIndex => $bar)
                                                <div class="ag-multi-day-bar {{ $bar['continues_left'] ? 'continues-left' : '' }} {{ $bar['continues_right'] ? 'continues-right' : '' }}"
                                                     style="grid-column: {{ $bar['startCol'] }} / span {{ $bar['span'] }}; grid-row: {{ $barIndex + 1 }}; background: {{ $bar['color'] }}; border-color: {{ $bar['color'] }};"
                                                     wire:click="selectEvent('{{ $bar['uid'] }}')"
                                                     title="{{ $bar['title'] }}">
                                                    <span>{{ $bar['title'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="ag-week-cells">
                                        @foreach($week['cells'] as $cell)
                                            @if(!$cell)
                                                <div class="ag-day-cell is-empty"></div>
                                            @else
                                                <div role="button" tabindex="0"
                                                        class="ag-day-cell {{ $cell['is_today'] ? 'is-today' : '' }} {{ $cell['is_selected'] ? 'is-selected' : '' }}"
                                                        wire:click="selecionarData('{{ $cell['date'] }}')">
                                                    <div class="ag-day-head">
                                                        <strong>{{ $cell['day'] }}</strong>
                                                        @if(($cell['total'] ?? 0) > 0)
                                                            <span class="ag-count">{{ $cell['total'] }}</span>
                                                        @endif
                                                    </div>
                                                    @if($multiCount > 0)
                                                        <div class="ag-multi-day-spacer" style="height: calc({{ $multiCount }} * 1.4rem);"></div>
                                                    @endif
                                                    <div class="ag-day-events">
                                                        @foreach($cell['items'] as $event)
                                                            @php $userColor = $event['responsible_user_cor'] ?? '#64748b'; @endphp
                                                            <div class="ag-event-chip" style="border-color: {{ $userColor }}55; background: {{ $userColor }}22;">
                                                                <span class="ag-tipo-pin" style="background: {{ $event['color'] }}" title="{{ $event['event_type_label'] ?? '' }}"></span>
                                                                <button type="button" class="ag-event-link" wire:click.stop="selectEvent('{{ $event['uid'] }}')">
                                                                    <strong>{{ \Illuminate\Support\Str::limit($event['title'], 26) }}</strong>
                                                                    <small>
                                                                        {{ $event['time_label'] }}
                                                                        @if(!empty($event['responsible_name']))
                                                                            · {{ \Illuminate\Support\Str::limit($event['responsible_name'], 18) }}
                                                                        @endif
                                                                    </small>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                        @if(($cell['hidden_total'] ?? 0) > 0)
                                                            <span class="ag-more-events">+{{ $cell['hidden_total'] }} itens</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($viewMode === 'week')
                        <div class="ag-week-grid">
                            @foreach($weekDays as $day)
                                <section class="ag-week-day {{ $day['is_selected'] ? 'is-selected' : '' }} {{ $day['is_today'] ? 'is-today' : '' }}">
                                    <div class="ag-week-day-head">
                                        <button type="button" wire:click="selecionarData('{{ $day['date'] }}')">
                                            <strong>{{ $day['day_name'] }}</strong>
                                            <span>{{ $day['day_number'] }}/{{ $day['month_label'] }}</span>
                                        </button>
                                    </div>
                                    <div class="ag-week-day-body">
                                        @forelse($day['items'] as $event)
                                            @php $userColor = $event['responsible_user_cor'] ?? '#64748b'; @endphp
                                            <div class="ag-week-event" style="border-left-color: {{ $event['color'] }}; background: {{ $userColor }}22;">
                                                <span class="ag-tipo-pin" style="background: {{ $event['color'] }}" title="{{ $event['event_type_label'] ?? '' }}"></span>
                                                <button type="button" class="ag-event-link" wire:click.stop="selectEvent('{{ $event['uid'] }}')">
                                                    <strong>{{ $event['title'] }}</strong>
                                                    <small>
                                                        {{ $event['range_label'] }}
                                                        @if(!empty($event['responsible_name']))
                                                            · {{ $event['responsible_name'] }}
                                                        @endif
                                                    </small>
                                                </button>
                                            </div>
                                        @empty
                                            <p class="ag-empty-small">Sem eventos.</p>
                                        @endforelse
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <div class="ag-day-view">
                            @if(!empty($dayAllDayEvents))
                                <div class="ag-day-all-day">
                                    <div class="ag-panel-head" style="padding:0 0 .55rem;">
                                        <span class="ag-panel-title">Dia inteiro</span>
                                    </div>
                                    <div class="ag-all-day-list">
                                        @foreach($dayAllDayEvents as $event)
                                            @php $userColor = $event['responsible_user_cor'] ?? '#64748b'; @endphp
                                            <button type="button" class="ag-all-day-event" style="border-left-color: {{ $event['color'] }}; background: {{ $userColor }}22;" wire:click.stop="selectEvent('{{ $event['uid'] }}')">
                                                <div class="ag-all-day-event-head">
                                                    <span class="ag-tipo-pin" style="background: {{ $event['color'] }}" title="{{ $event['event_type_label'] ?? '' }}"></span>
                                                    <strong>{{ $event['title'] }}</strong>
                                                </div>
                                                <small>
                                                    {{ $event['origin_label'] }} · {{ $event['range_label'] }}
                                                    @if(!empty($event['responsible_name']))
                                                        · {{ $event['responsible_name'] }}
                                                    @endif
                                                </small>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="ag-day-timeline">
                                @foreach($dayHours as $hour)
                                    <div class="ag-hour-row">
                                        <div class="ag-hour-label">{{ $hour['hour'] }}</div>
                                        <div class="ag-hour-events">
                                            @forelse($hour['items'] as $event)
                                                @php $userColor = $event['responsible_user_cor'] ?? '#64748b'; @endphp
                                                <div class="ag-hour-event" style="border-left-color: {{ $event['color'] }}; background: {{ $userColor }}22;">
                                                    <span class="ag-tipo-pin" style="background: {{ $event['color'] }}" title="{{ $event['event_type_label'] ?? '' }}"></span>
                                                    <button type="button" class="ag-event-link" wire:click.stop="selectEvent('{{ $event['uid'] }}')">
                                                        <strong>{{ $event['title'] }}</strong>
                                                        <small>
                                                            {{ $event['range_label'] }}
                                                            @if(!empty($event['responsible_name']))
                                                                · {{ $event['responsible_name'] }}
                                                            @endif
                                                        </small>
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="ag-hour-empty"></div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    </div>
                </div>
            </main>

            <aside class="ag-details">
                @if($showMyInvitesTab)
                    <div class="ag-panel ag-invites-panel">
                        <div class="ag-panel-head">
                            <span class="ag-panel-title">Meus Convites</span>
                            <button type="button" class="ag-close" wire:click="toggleInvitesTab" style="padding: 0.35rem 0.5rem; margin: -0.35rem -0.5rem;">×</button>
                        </div>

                        <div class="ag-invites-scroll">
                            @if(!empty($myPendingInvites))
                                <div class="ag-invites-section">
                                    <h3 class="ag-invites-section-title">Aguardando Resposta</h3>
                                    <div class="ag-invites-list">
                                        @foreach($myPendingInvites as $invite)
                                            <div class="ag-invite-card ag-invite-card--pending">
                                                <div class="ag-invite-header">
                                                    <strong>{{ $invite['title'] }}</strong>
                                                    <small class="ag-invite-responsible">{{ $invite['responsible_name'] ?? 'Sem responsável' }}</small>
                                                </div>
                                                <small class="ag-invite-datetime">
                                                    @if($invite['all_day'])
                                                        Dia inteiro · {{ $invite['starts_at'] }}
                                                    @else
                                                        {{ $invite['starts_at'] }}
                                                    @endif
                                                </small>
                                                <div class="ag-invite-actions">
                                                    <button type="button" class="ag-invite-btn ag-invite-btn--reject" wire:click="rejectMyInvite({{ $invite['id'] }})" title="Rejeitar">✕</button>
                                                    <button type="button" class="ag-invite-btn ag-invite-btn--accept" wire:click="acceptMyInvite({{ $invite['id'] }})" title="Aceitar">✓</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($myAcceptedInvites))
                                <div class="ag-invites-section">
                                    <h3 class="ag-invites-section-title">Aceitos</h3>
                                    <div class="ag-invites-list">
                                        @foreach($myAcceptedInvites as $invite)
                                            <div class="ag-invite-card ag-invite-card--accepted">
                                                <div class="ag-invite-header">
                                                    <strong>{{ $invite['title'] }}</strong>
                                                    <small class="ag-invite-responsible">{{ $invite['responsible_name'] ?? 'Sem responsável' }}</small>
                                                </div>
                                                <small class="ag-invite-datetime">
                                                    @if($invite['all_day'])
                                                        Dia inteiro · {{ $invite['starts_at'] }}
                                                    @else
                                                        {{ $invite['starts_at'] }}
                                                    @endif
                                                </small>
                                                <span class="ag-invite-status ag-invite-status--accepted">✓ Aceito</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($myRejectedInvites))
                                <div class="ag-invites-section">
                                    <h3 class="ag-invites-section-title">Rejeitados</h3>
                                    <div class="ag-invites-list">
                                        @foreach($myRejectedInvites as $invite)
                                            <div class="ag-invite-card ag-invite-card--rejected">
                                                <div class="ag-invite-header">
                                                    <strong>{{ $invite['title'] }}</strong>
                                                    <small class="ag-invite-responsible">{{ $invite['responsible_name'] ?? 'Sem responsável' }}</small>
                                                </div>
                                                <small class="ag-invite-datetime">
                                                    @if($invite['all_day'])
                                                        Dia inteiro · {{ $invite['starts_at'] }}
                                                    @else
                                                        {{ $invite['starts_at'] }}
                                                    @endif
                                                </small>
                                                <span class="ag-invite-status ag-invite-status--rejected">✕ Rejeitado</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(empty($myPendingInvites) && empty($myAcceptedInvites) && empty($myRejectedInvites))
                                <p class="ag-empty-small" style="padding: 1rem; text-align: center;">Você não possui convites.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="ag-panel ag-detail-panel">
                    <div class="ag-panel-head">
                        <span class="ag-panel-title">Detalhes</span>
                    </div>

                    <div class="ag-detail-scroll">
                    @if(!empty($selectedEvent))
                        <div class="ag-detail-card">
                            <div class="ag-detail-badge" style="background: {{ $selectedEvent['color'] }}14; color: {{ $selectedEvent['color'] }}">
                                {{ $selectedEvent['origin_label'] }} · {{ $selectedEvent['event_type_label'] }}
                            </div>
                            <h2>{{ $selectedEvent['title'] }}</h2>
                            <p class="ag-detail-range">{{ $selectedEvent['range_label'] }}</p>

                            <div class="ag-detail-list">
                                <div><span>Responsável</span><strong>{{ $selectedEvent['responsible_name'] ?? '—' }}</strong></div>
                                <div><span>Status</span><strong>{{ $selectedEvent['status_label'] }}</strong></div>
                                <div><span>Origem</span><strong>{{ $selectedEvent['origin_label'] }}</strong></div>
                                <div><span>Tipo</span><strong>{{ $selectedEvent['event_type_label'] }}</strong></div>
                                @if(!empty($selectedEvent['location']))
                                    <div><span>Local</span><strong>{{ $selectedEvent['location'] }}</strong></div>
                                @endif
                                @if(!empty($selectedEvent['entity_label']))
                                    <div><span>Vínculo</span><strong>{{ $selectedEvent['entity_label'] }}</strong></div>
                                @endif

                                @if(isset($selectedEvent['pais']) || isset($selectedEvent['estado']) || isset($selectedEvent['cidade']))
                                    @if(!empty($selectedEvent['pais']))
                                        <div><span>País</span><strong>{{ $selectedEvent['pais'] }}</strong></div>
                                    @endif
                                    @if(!empty($selectedEvent['estado']))
                                        <div><span>Estado</span><strong>{{ $selectedEvent['estado'] }}</strong></div>
                                    @endif
                                    @if(!empty($selectedEvent['cidade']))
                                        <div><span>Cidade</span><strong>{{ $selectedEvent['cidade'] }}</strong></div>
                                    @endif
                                @endif

                                @php
                                    $descricaoEvento = !empty($selectedEvent['description']) ? $selectedEvent['description'] : 'Sem descrição';
                                @endphp
                                <div><span>Descrição</span><strong>{{ $descricaoEvento }}</strong></div>
                                @if(!empty($selectedEvent['responsible_setor_nome']))
                                    <div>
                                        <span>Setor</span>
                                        <strong style="display: flex; align-items: center; gap: .3rem;">
                                            <span class="ag-user-pin" style="background: {{ $selectedEvent['responsible_user_cor'] ?? '#64748b' }}; flex-shrink: 0;"></span>
                                            {{ $selectedEvent['responsible_setor_nome'] }}
                                        </strong>
                                    </div>
                                @endif
                            </div>

                            @if(!empty($selectedEvent['anexos']))
                                <div class="ag-anexos-section">
                                    <p class="ag-anexos-label">Anexos</p>
                                    <div class="ag-anexos-list">
                                        @foreach($selectedEvent['anexos'] as $anexo)
                                            <button type="button" class="ag-anexo-item ag-anexo-item--button" wire:click="abrirAnexoPreview({{ $anexo['id'] }})">
                                                <span class="ag-anexo-icon">{{ $anexo['is_pdf'] ? '📄' : ($anexo['is_image'] ? '🖼️' : '📎') }}</span>
                                                <span class="ag-anexo-nome">{{ $anexo['nome'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="ag-detail-actions">
                                @if(!empty($selectedEvent['entity_url']))
                                    <a href="{{ $selectedEvent['entity_url'] }}" class="ag-btn ag-btn-soft" target="_blank">Abrir origem</a>
                                @endif
                                @if(($selectedEvent['origin'] ?? null) === 'manual' && ($selectedEvent['can_manage'] ?? false))
                                    <button type="button" class="ag-btn ag-btn-soft" wire:click="openEditEventModal({{ $selectedEvent['manual_event_id'] }})">Editar</button>
                                    <button type="button" class="ag-btn ag-btn-danger" wire:click="deleteEvent({{ $selectedEvent['manual_event_id'] }})" wire:confirm="Excluir este evento?">Excluir</button>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="ag-empty-detail">Selecione um evento no calendário para ver os detalhes.</p>
                    @endif

                    @php
                        $event = null;
                        if (!empty($selectedEvent['manual_event_id'])) {
                            $event = \App\Models\AgendaEvent::find($selectedEvent['manual_event_id']);
                        } elseif (!empty($selectedEvent['relatorio_fotografico_id'])) {
                            $event = \App\Models\AgendaEvent::where('relatorio_fotografico_id', $selectedEvent['relatorio_fotografico_id'])->first();
                        } elseif (!empty($selectedEvent['relatorio_visita_tecnica_id'])) {
                            $event = \App\Models\AgendaEvent::where('relatorio_visita_tecnica_id', $selectedEvent['relatorio_visita_tecnica_id'])->first();
                        }
                    @endphp
                    @if(!empty($selectedEvent) && $event)
                        <div class="ag-panel-head ag-panel-head-spaced">
                            <span class="ag-panel-title">Participantes</span>
                            @php
                                $participantCount = $event->participants()->count();
                            @endphp
                            <span class="ag-count">{{ $participantCount }}</span>
                        </div>
                        <div class="ag-list">
                            @if($event && $event->participants()->exists())
                                @foreach($event->participants()->get() as $participant)
                                    <div class="ag-list-item" style="cursor: default;">
                                        <span class="ag-list-dot" style="background: {{ $participant->cor_agenda ?? '#64748b' }}"></span>
                                        <div>
                                            <strong>{{ $participant->name }}</strong>
                                            <small>
                                                @if($participant->pivot->status === 'accepted')
                                                    <span style="color: #10b981;">✓ Aceito</span>
                                                @elseif($participant->pivot->status === 'rejected')
                                                    <span style="color: #ef4444;">✕ Rejeitado</span>
                                                @else
                                                    <span style="color: #f59e0b;">⏳ Pendente</span>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="ag-empty-small">Sem participantes.</p>
                            @endif
                        </div>
                    @else
                        <div class="ag-panel-head ag-panel-head-spaced">
                            <span class="ag-panel-title">Próximos itens</span>
                            <span class="ag-count">{{ count($events) }}</span>
                        </div>
                        <div class="ag-list">
                            @forelse(array_slice($events, 0, 8) as $event)
                                <button type="button" class="ag-list-item" wire:click="selectEvent('{{ $event['uid'] }}')">
                                    <span class="ag-list-dot" style="background: {{ $event['color'] }}"></span>
                                    <div>
                                        <strong>{{ $event['title'] }}</strong>
                                        <small>{{ $event['range_label'] }} · {{ $event['origin_label'] }}</small>
                                    </div>
                                </button>
                            @empty
                                <p class="ag-empty-small">Sem eventos no período filtrado.</p>
                            @endforelse
                        </div>
                    @endif
                    </div>
                </div>
                @endif
            </aside>
        </div>
        @else
            <div class="ag-panel" style="margin-top: 1.5rem; padding: 2rem;">
                <p class="ag-panel-title">Carregando agenda...</p>
                <p class="ag-panel-hint">Buscando VT agendada e relatórios fotográficos das unidades.</p>
            </div>
        @endif

        @if($showEventModal)
            <div class="ag-modal-overlay" wire:click.self="closeEventModal">
                <div class="ag-modal">
                    <div class="ag-modal-head">
                        <h3>{{ $editingEventId ? 'Editar evento' : 'Novo evento' }}</h3>
                        <button type="button" class="ag-close" wire:click="closeEventModal">×</button>
                    </div>

                    <div class="ag-modal-grid">
                        <label>
                            <span>Título</span>
                            <input type="text" wire:model="eventForm.title" placeholder="Ex: Reunião kickoff">
                        </label>
                        <label>
                            <span>Tipo</span>
                            <select wire:model="eventForm.event_type">
                                @if(empty($tiposCriacao))
                                    <option value="">Nenhum tipo disponível</option>
                                @else
                                    @foreach($tiposCriacao as $tipo)
                                        <option value="{{ $tipo['slug'] }}">{{ $tipo['nome'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </label>
                        <label>
                            <span>Início</span>
                            <input type="datetime-local"
                                   wire:model="eventForm.starts_at"
                                   @if($eventForm['all_day'] ?? false) disabled @endif
                                   id="starts_at">
                        </label>
                        <label>
                            <span>Fim</span>
                            <input type="datetime-local"
                                   wire:model="eventForm.ends_at"
                                   @if($eventForm['all_day'] ?? false) disabled @endif
                                   id="ends_at">
                        </label>
                        <label class="ag-check">
                            <input type="checkbox" wire:model.live="eventForm.all_day" id="all_day">
                            <span>Dia inteiro</span>
                        </label>
                        <label>
                            <span>Status</span>
                            <select wire:model="eventForm.status">
                                <option value="agendado">Agendado</option>
                                <option value="previsto">Previsto</option>
                                <option value="confirmado">Confirmado</option>
                                <option value="realizado">Realizado</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                                <option value="em_andamento">Em andamento</option>
                            </select>
                        </label>
                        <label>
                            <span>Responsável</span>
                            <select wire:model="eventForm.responsible_user_id" disabled>
                                <option value="">Nenhum</option>
                                @foreach($responsibleOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ag-unidade-search-wrapper">
                            <span>Unidade</span>
                            <input type="text" wire:model.live="unidadeSearch" placeholder="Buscar unidade..." class="ag-unidade-search">
                            @if(!empty($unidadeSearch))
                                <div class="ag-unidade-dropdown">
                                    @forelse($filteredUnidadeOptions as $id => $label)
                                        <button type="button"
                                                class="ag-unidade-option {{ $eventForm['obra_id'] == $id ? 'is-selected' : '' }}"
                                                wire:click="selectUnidade('{{ $id }}')">
                                            {{ $label }}
                                        </button>
                                    @empty
                                        <div class="ag-unidade-empty">Nenhuma unidade encontrada</div>
                                    @endforelse
                                </div>
                            @else
                                @if(!empty($eventForm['obra_id']))
                                    <div class="ag-unidade-selected">
                                        {{ $unidadeOptions[$eventForm['obra_id']] ?? 'Unidade selecionada' }}
                                    </div>
                                @endif
                            @endif
                        </label>

                        @if(!empty($unidadeData))
                            <div class="ag-span-full ag-unidade-info">
                                <p class="ag-unidade-info-label">Dados da Unidade</p>
                                <div class="ag-unidade-info-grid">
                                    @if(!empty($unidadeData['pais']))
                                        <div class="ag-unidade-info-item">
                                            <span>País</span>
                                            <strong>{{ $unidadeData['pais'] }}</strong>
                                        </div>
                                    @endif
                                    @if(!empty($unidadeData['uf']))
                                        <div class="ag-unidade-info-item">
                                            <span>Estado</span>
                                            <strong>{{ $unidadeData['uf'] }}</strong>
                                        </div>
                                    @endif
                                    @if(!empty($unidadeData['cidade']))
                                        <div class="ag-unidade-info-item">
                                            <span>Cidade</span>
                                            <strong>{{ $unidadeData['cidade'] }}</strong>
                                        </div>
                                    @endif
                                    @if(!empty($unidadeData['endereco']))
                                        <div class="ag-unidade-info-item ag-unidade-info-item--full">
                                            <span>Endereço</span>
                                            <strong>{{ $unidadeData['endereco'] }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($showActivitiesPrompt && !empty($eventForm['obra_id']))
                            <div class="ag-activity-prompt ag-span-full">
                                <p>Deseja atrelar uma atividade da unidade a este evento?</p>
                                <div class="ag-activity-buttons">
                                    <button type="button" class="ag-btn ag-btn-sm" wire:click="declineActivity">Não</button>
                                    <button type="button" class="ag-btn ag-btn-sm ag-btn-primary" wire:click="acceptActivity">Sim</button>
                                </div>
                            </div>
                            @if($linkActivity && !empty($activityOptions))
                                <label class="ag-span-full">
                                    <span>Atividade</span>
                                    <select wire:model.live="selectedActivity">
                                        <option value="">Selecione...</option>
                                        @foreach($activityOptions as $value => $meta)
                                            <option value="{{ $value }}">{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @if($activityHint)
                                        <p class="ag-activity-hint">{{ $activityHint }}</p>
                                    @endif
                                </label>

                                @if($selectedActivity === 'relatorio_fotografico')
                                    <label class="ag-span-full">
                                        <span>Agendar Relatório</span>
                                        <input type="date"
                                               wire:model="relatorioFotograficoAgendadoEm"
                                               placeholder="Selecione a data">
                                    </label>
                                @endif
                            @endif
                        @endif
                        <label class="ag-span-full">
                            <span>Adicionar pessoas</span>
                            <div class="ag-participants-wrapper">
                                <input type="text" wire:model.live="participantSearch" placeholder="Digitar nome..." class="ag-participant-search">
                                <div class="ag-participants-dropdown">
                                    @php
                                        $participantList = $filteredParticipants;
                                        $displayLimit = $showAllParticipants ? count($participantList) : 5;
                                        $displayList = array_slice($participantList, 0, $displayLimit, true);
                                        $hasMore = count($participantList) > 5;
                                    @endphp
                                    @forelse($displayList as $id => $name)
                                        <button type="button"
                                                class="ag-participant-option {{ in_array($id, $participantIds) ? 'is-selected' : '' }}"
                                                wire:click="toggleParticipant({{ $id }})">
                                            <span class="ag-participant-checkbox {{ in_array($id, $participantIds) ? 'checked' : '' }}"></span>
                                            <span>{{ $name }}</span>
                                        </button>
                                    @empty
                                        <div class="ag-participant-empty">Nenhuma pessoa encontrada</div>
                                    @endforelse
                                    @if($hasMore && !$showAllParticipants)
                                        <button type="button" class="ag-participant-more" wire:click="$set('showAllParticipants', true)">
                                            Ver mais {{ count($participantList) - 5 }} pessoas
                                        </button>
                                    @elseif($hasMore && $showAllParticipants)
                                        <button type="button" class="ag-participant-more" wire:click="$set('showAllParticipants', false)">
                                            Ver menos
                                        </button>
                                    @endif
                                </div>
                                @if(!empty($participantIds))
                                    <div class="ag-selected-participants">
                                        @php
                                            $visibleCount = 5;
                                            $totalCount = count($participantIds);
                                            $visibleIds = array_slice($participantIds, 0, $visibleCount);
                                            $hiddenCount = max(0, $totalCount - $visibleCount);
                                        @endphp
                                        @foreach($visibleIds as $id)
                                            @if(isset($responsibleOptions[$id]))
                                                <span class="ag-participant-tag">
                                                    {{ $responsibleOptions[$id] }}
                                                    <button type="button" wire:click="toggleParticipant({{ $id }})">×</button>
                                                </span>
                                            @endif
                                        @endforeach
                                        @if($hiddenCount > 0)
                                            <span class="ag-participant-count">+{{ $hiddenCount }}</span>
                                        @endif
                                    </div>
                                    <p class="ag-activity-hint" style="margin-top: 0.5rem;">
                                        💡 Estes participantes receberão uma notificação para aceitar ou rejeitar o convite.
                                    </p>
                                @endif
                            </div>
                        </label>
                        <label class="ag-span-full">
                            <span>Local</span>
                            <input type="text" wire:model="eventForm.location" placeholder="Ex: Sala de reunião">
                        </label>
                        <label class="ag-span-full">
                            <span>Descrição</span>
                            <textarea wire:model="eventForm.description" rows="4" placeholder="Detalhes do evento"></textarea>
                        </label>

                        <div class="ag-span-full ag-anexos-field">
                            <span class="ag-anexos-label">Anexos</span>

                            <label class="ag-anexos-dropzone">
                                <input type="file"
                                       wire:model="anexosUploadInput"
                                       multiple
                                       accept="application/pdf,image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                       class="ag-anexos-input-hidden">
                                <div class="ag-anexos-dropzone-content" wire:loading.remove wire:target="anexosUploadInput">
                                    <span class="ag-anexos-dropzone-icon">📎</span>
                                    <span class="ag-anexos-dropzone-text">
                                        <strong>Clique para selecionar arquivos</strong>
                                        <small>PDF ou imagens · até 10MB cada · vários arquivos</small>
                                    </span>
                                </div>
                                <div class="ag-anexos-dropzone-content" wire:loading wire:target="anexosUploadInput">
                                    <span class="ag-anexos-dropzone-icon">⏳</span>
                                    <span class="ag-anexos-dropzone-text">
                                        <strong>Enviando arquivos...</strong>
                                    </span>
                                </div>
                            </label>

                            @if(!empty($anexosExistentes) || !empty($novosAnexos))
                                <div class="ag-anexos-list">
                                    @foreach($anexosExistentes as $anexo)
                                        <div class="ag-anexo-item">
                                            <span class="ag-anexo-icon">{{ $anexo['is_pdf'] ? '📄' : ($anexo['is_image'] ? '🖼️' : '📎') }}</span>
                                            <button type="button" class="ag-anexo-nome ag-anexo-nome--button" wire:click="abrirAnexoPreview({{ $anexo['id'] }})">{{ $anexo['nome'] }}</button>
                                            <span class="ag-anexo-tag ag-anexo-tag--saved">Salvo</span>
                                            <button type="button" class="ag-anexo-remove" wire:click="removerAnexo({{ $anexo['id'] }})" wire:confirm="Remover este anexo?" title="Remover">×</button>
                                        </div>
                                    @endforeach
                                    @foreach($novosAnexos as $index => $arquivo)
                                        @if(is_object($arquivo) && method_exists($arquivo, 'getClientOriginalName'))
                                            <div class="ag-anexo-item ag-anexo-item--pending">
                                                <span class="ag-anexo-icon">📎</span>
                                                <span class="ag-anexo-nome">{{ $arquivo->getClientOriginalName() }}</span>
                                                <span class="ag-anexo-tag ag-anexo-tag--pending">Pendente</span>
                                                <button type="button" class="ag-anexo-remove" wire:click="removerNovoAnexo({{ $index }})" title="Remover">×</button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @error('anexosUploadInput.*')
                                <span class="ag-anexo-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="ag-modal-actions">
                        <button type="button" class="ag-btn ag-btn-ghost" wire:click="closeEventModal">Cancelar</button>
                        <button type="button" class="ag-btn ag-btn-primary" wire:click="saveEvent">Salvar</button>
                    </div>
                </div>
            </div>
        @endif

        @if($showSettingsModal)
            <div class="ag-modal-overlay" wire:click.self="closeSettingsModal">
                <div class="ag-modal">
                    <div class="ag-modal-head">
                        <h3>Configurações da Agenda</h3>
                        <button type="button" class="ag-close" wire:click="closeSettingsModal">×</button>
                    </div>

                    <div class="ag-settings-tabs">
                        <button type="button"
                                class="ag-settings-tab {{ $settingsTab === 'cores' ? 'is-active' : '' }}"
                                wire:click="setSettingsTab('cores')">
                            Cores dos usuários
                        </button>
                        @if($this->podeGerenciarTipos)
                            <button type="button"
                                    class="ag-settings-tab {{ $settingsTab === 'tipos' ? 'is-active' : '' }}"
                                    wire:click="setSettingsTab('tipos')">
                                Tipos de evento
                            </button>
                        @endif
                    </div>

                    @if($settingsTab === 'cores')
                        <div class="ag-settings-body">
                            <p class="ag-settings-hint">Defina uma cor para cada usuário visível na sua agenda. Essa cor aparece como background dos eventos onde a pessoa é responsável.</p>

                            @if(empty($userPalette))
                                <p class="ag-empty-small">Nenhum usuário disponível.</p>
                            @else
                                <div class="ag-settings-list">
                                    @foreach($userPalette as $index => $entry)
                                        <div class="ag-settings-row">
                                            <span class="ag-user-pin ag-user-pin--lg" style="background: {{ $entry['cor'] }}"></span>
                                            <div class="ag-settings-info">
                                                <span class="ag-settings-nome">{{ $entry['nome'] }}</span>
                                                @if(!empty($entry['setor']))
                                                    <span class="ag-settings-setor">{{ $entry['setor'] }}</span>
                                                @endif
                                            </div>
                                            <input type="color" wire:model.live="userPalette.{{ $index }}.cor" class="ag-color-input">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="ag-modal-actions">
                            <button type="button" class="ag-btn ag-btn-ghost" wire:click="closeSettingsModal">Cancelar</button>
                            <button type="button" class="ag-btn ag-btn-primary" wire:click="saveUserCores">Salvar cores</button>
                        </div>
                    @elseif($settingsTab === 'tipos' && $this->podeGerenciarTipos)
                        <div class="ag-settings-body">
                            <p class="ag-settings-hint">Cadastre tipos de evento para o seu setor. Os Gestores do mesmo setor poderão selecionar esses tipos ao criar eventos manuais.</p>

                            <div class="ag-tipo-form">
                                <div class="ag-tipo-form-row">
                                    <input type="text"
                                           wire:model="novoTipoNome"
                                           placeholder="Nome do tipo (ex.: Reunião externa)"
                                           class="ag-tipo-input">
                                    <input type="color"
                                           wire:model="novoTipoCor"
                                           class="ag-color-input"
                                           title="Cor do tipo">
                                    <button type="button" class="ag-btn ag-btn-primary ag-btn-sm" wire:click="adicionarTipo">+ Adicionar</button>
                                </div>
                            </div>

                            @if(empty($tiposGestao))
                                <p class="ag-empty-small">Nenhum tipo cadastrado ainda. Crie o primeiro acima.</p>
                            @else
                                <div class="ag-settings-list">
                                    @foreach($tiposGestao as $index => $tipo)
                                        <div class="ag-settings-row">
                                            <span class="ag-user-pin ag-user-pin--lg" style="background: {{ $tipo['cor'] }}"></span>
                                            <input type="text"
                                                   wire:model.lazy="tiposGestao.{{ $index }}.nome"
                                                   wire:change="atualizarTipo({{ $tipo['id'] }})"
                                                   class="ag-tipo-input ag-tipo-input--inline">
                                            <input type="color"
                                                   wire:model.live="tiposGestao.{{ $index }}.cor"
                                                   wire:change="atualizarTipo({{ $tipo['id'] }})"
                                                   class="ag-color-input">
                                            <button type="button"
                                                    class="ag-anexo-remove"
                                                    wire:click="removerTipo({{ $tipo['id'] }})"
                                                    wire:confirm="Remover este tipo?"
                                                    title="Remover">×</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="ag-modal-actions">
                            <button type="button" class="ag-btn ag-btn-ghost" wire:click="closeSettingsModal">Fechar</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($showAnexoPreview && $anexoPreview)
            <div class="ag-modal-overlay" wire:click.self="fecharAnexoPreview" wire:keydown.escape.window="fecharAnexoPreview">
                <div class="ag-preview-modal">
                    <div class="ag-modal-head">
                        <h3>{{ $anexoPreview['nome'] }}</h3>
                        <div class="ag-preview-head-actions">
                            <a href="{{ $anexoPreview['url'] }}" target="_blank" rel="noopener" class="ag-btn ag-btn-ghost ag-btn-sm" title="Abrir em nova aba">Abrir</a>
                            <a href="{{ $anexoPreview['url'] }}" download="{{ $anexoPreview['nome'] }}" class="ag-btn ag-btn-ghost ag-btn-sm" title="Baixar">Baixar</a>
                            <button type="button" class="ag-close" wire:click="fecharAnexoPreview">×</button>
                        </div>
                    </div>
                    <div class="ag-preview-body">
                        @if($anexoPreview['is_image'])
                            <img src="{{ $anexoPreview['url'] }}" alt="{{ $anexoPreview['nome'] }}" class="ag-preview-img">
                        @elseif($anexoPreview['is_pdf'])
                            <iframe src="{{ $anexoPreview['url'] }}" title="{{ $anexoPreview['nome'] }}" class="ag-preview-iframe"></iframe>
                        @else
                            <div class="ag-preview-fallback">
                                <p>Pré-visualização não disponível para este formato.</p>
                                <a href="{{ $anexoPreview['url'] }}" target="_blank" rel="noopener" class="ag-btn ag-btn-primary">Abrir em nova aba</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .ag-shell {
            --vo-bg: #ffffff;
            --vo-bg-subtle: #f8fafc;
            --vo-text: #111827;
            --vo-text-secondary: #374151;
            --vo-text-muted: #6b7280;
            --vo-text-faint: #9ca3af;
            --vo-border: #e5e7eb;
            --vo-border-light: #f3f4f6;
            --vo-shadow: 0 10px 30px rgba(15, 23, 42, .05);
            --vo-accent: #fbbf24;
            display:flex;
            flex-direction:column;
            gap:1rem;
        }
        .dark .ag-shell {
            --vo-bg: #111111;
            --vo-bg-subtle: #171717;
            --vo-text: #f9fafb;
            --vo-text-secondary: #e5e7eb;
            --vo-text-muted: #cbd5e1;
            --vo-text-faint: #94a3b8;
            --vo-border: #2a2a2a;
            --vo-border-light: #1f1f1f;
            --vo-shadow: 0 18px 42px rgba(0, 0, 0, .35);
            --vo-accent: #fbbf24;
        }
        .ag-topbar {
            display:flex; justify-content:space-between; gap:1rem; align-items:flex-end;
            padding:.9rem 1rem; border:1px solid var(--vo-border); border-radius:.75rem;
            background: var(--vo-bg);
            backdrop-filter: blur(8px);
        }
        .dark .ag-topbar {
            background: var(--vo-bg);
        }
        .ag-kicker { margin:0 0 .2rem; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:var(--vo-text-faint); }
        .ag-title { margin:0; font-size:1.55rem; font-weight:800; color:var(--vo-text); }
        .ag-subtitle { margin:.25rem 0 0; color:var(--vo-text-muted); font-size:.9rem; }
        .ag-top-actions { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; }
        .ag-btn {
            border:1px solid var(--vo-border); background:var(--vo-bg); color:var(--vo-text-secondary);
            border-radius:.65rem; padding:.6rem .82rem; font-size:.82rem; font-weight:700; cursor:pointer;
            text-decoration:none; display:inline-flex; align-items:center; justify-content:center;
        }
        .ag-btn:hover { border-color: var(--vo-text-muted); }
        .ag-btn-ghost { background:var(--vo-bg-subtle); }
        .ag-btn-icon { padding:.55rem; aspect-ratio:1; position: relative; }
        .ag-icon { width:1.05rem; height:1.05rem; display:block; }
        .ag-btn-primary { background: var(--vo-accent); color:#111; border-color: #d39b00; }
        .ag-btn-active { background:#111827; color:#fff; border-color:#111827; }
        .dark .ag-btn-active { background:#fbbf24; color:#111; border-color:#fbbf24; }
        .ag-btn-soft { background: #f8fafc; }
        .dark .ag-btn-soft { background:#2E3339; color:#F3F4F6; }
        .ag-btn-danger { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
        .ag-layout {
            display:grid;
            grid-template-columns: 238px minmax(0, 1fr) 300px;
            gap:1rem;
            align-items:start;
            min-height:0;
        }
        .ag-sidebar, .ag-details {
            display:flex;
            flex-direction:column;
            gap:.8rem;
            min-height:0;
            position:static;
            max-height:none;
            overflow:visible;
            z-index:10;
        }
        .ag-main {
            min-height:0;
        }
        .ag-panel {
            background:var(--vo-bg); border:1px solid var(--vo-border); border-radius:.75rem; box-shadow:var(--vo-shadow);
            overflow:hidden;
        }
        .ag-panel-head {
            padding:.75rem .85rem .45rem;
            display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        }
        .ag-panel-head-row { padding-bottom:.8rem; }
        .ag-panel-head-spaced { border-top:1px solid var(--vo-border-light); margin-top:.25rem; padding-top:.65rem; }
        .ag-panel-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--vo-text-muted); }
        .ag-panel-hint { margin:.2rem 0 0; font-size:.78rem; color:var(--vo-text-faint); }
        .ag-date-pill, .ag-count {
            border-radius:999px; padding:.2rem .55rem; font-size:.72rem; font-weight:800;
            background:#FEF3C7; color:#92400E;
        }
        .dark .ag-date-pill, .dark .ag-count { background: rgba(245, 158, 11, .18); color:#FCD34D; }
        .ag-mini-calendar { padding:0 .85rem .85rem; }
        .ag-mini-weekdays, .ag-weekdays {
            display:grid; grid-template-columns:repeat(7,1fr); gap:.25rem; margin-bottom:.35rem;
        }
        .ag-mini-weekdays span, .ag-weekdays span {
            text-align:center; font-size:.65rem; font-weight:800; color:var(--vo-text-faint); text-transform:uppercase;
        }
        .ag-mini-grid {
            display:grid; grid-template-columns:repeat(7,1fr); gap:.28rem;
        }
        .ag-month-grid {
            display:flex; flex-direction:column; gap:.28rem;
            padding:0 .85rem .85rem;
        }
        .ag-week-row {
            position:relative;
        }
        .ag-multi-day-row {
            position:absolute;
            top:1.95rem;
            left:0;
            right:0;
            display:grid;
            grid-template-columns:repeat(7,1fr);
            grid-auto-rows:1.35rem;
            gap:.15rem 0;
            pointer-events:none;
            z-index:5;
            box-sizing:border-box;
        }
        .ag-multi-day-spacer {
            flex-shrink:0;
            pointer-events:none;
        }
        .ag-multi-day-bar {
            padding:.1rem .55rem;
            margin:0 1px;
            border-radius:.3rem;
            border:none;
            font-size:.7rem;
            font-weight:600;
            color:#fff;
            cursor:pointer;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            display:flex;
            align-items:center;
            height:1.2rem;
            min-width:0;
            pointer-events:auto;
            transition:filter .15s;
            box-sizing:border-box;
        }
        .ag-multi-day-bar:hover {
            filter:brightness(1.08);
        }
        .ag-multi-day-bar.continues-left {
            border-top-left-radius:0;
            border-bottom-left-radius:0;
            margin-left:0;
        }
        .ag-multi-day-bar.continues-right {
            border-top-right-radius:0;
            border-bottom-right-radius:0;
            margin-right:0;
        }
        .ag-multi-day-bar span {
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            color:#fff;
            text-shadow:0 1px 1px rgba(0,0,0,.15);
        }
        .ag-week-cells {
            display:grid; grid-template-columns:repeat(7,1fr); gap:.28rem;
        }
        .ag-day-cell, .ag-week-day, .ag-hour-row {
            border:1px solid var(--vo-border); border-radius:.85rem; background:var(--vo-bg); overflow:hidden;
        }
        .ag-mini-day {
            width:28px; height:28px;
            display:flex; align-items:center; justify-content:center;
            margin:0 auto;
            border:none; background:transparent;
            cursor:pointer; color:var(--vo-text-secondary);
            border-radius:50%;
            transition:background-color .15s, color .15s;
        }
        .ag-mini-day:hover { background:var(--vo-bg-subtle); }
        .ag-mini-day span { font-size:.72rem; line-height:1; font-weight:500; }
        .ag-mini-day.is-other-month { color:var(--vo-text-faint); opacity:.5; }
        .ag-mini-day.is-selected { background:rgba(245,158,11,.18); color:#92400e; }
        .ag-mini-day.is-selected span { font-weight:700; }
        .ag-mini-day.is-today { background:#f59e0b !important; color:#fff !important; }
        .ag-mini-day.is-today span { font-weight:700; color:#fff; }
        .ag-day-cell.is-selected, .ag-week-day.is-selected { border-color:#f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.18) inset; }
        .ag-day-cell.is-today, .ag-week-day.is-today { border-color:#f59e0b; }
        .dark .ag-mini-day small { background:rgba(245,158,11,.2); color:#fcd34d; }
        .ag-filters { display:grid; grid-template-columns:1fr; gap:.55rem; padding:0 .85rem .85rem; }
        .ag-filters label, .ag-modal-grid label {
            display:flex; flex-direction:column; gap:.25rem; font-size:.72rem; color:var(--vo-text-muted); font-weight:700;
        }
        .ag-filters span, .ag-modal-grid span { text-transform:uppercase; letter-spacing:.04em; font-size:.62rem; }
        .ag-filters input, .ag-filters select, .ag-modal-grid input, .ag-modal-grid select, .ag-modal-grid textarea {
            border:1px solid var(--vo-border); border-radius:.7rem; background:var(--vo-bg-subtle); color:var(--vo-text);
            padding:.6rem .75rem; font-size:.8rem; font-family:inherit; width:100%;
        }
        .ag-modal-grid select:disabled {
            opacity:0.6; cursor:not-allowed; background-color:var(--vo-bg-subtle);
        }
        .ag-filters select {
            appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat:no-repeat;
            background-position:right .75rem center;
            background-size:1.2em;
            padding-right:2.5rem;
        }
        .dark .ag-filters select {
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e5e7eb' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }
        .ag-filters input:focus, .ag-filters select:focus, .ag-modal-grid input:focus, .ag-modal-grid select:focus, .ag-modal-grid textarea:focus {
            outline:none;
            border-color: var(--vo-border);
            box-shadow: 0 0 0 1px rgba(0, 0, 0, .08);
        }
        .ag-legend { display:grid; gap:.4rem; padding:0 .85rem .85rem; font-size:.74rem; color:var(--vo-text-secondary); }
        .ag-legend div { display:flex; align-items:center; gap:.5rem; }
        .ag-legend i { width:10px; height:10px; border-radius:999px; display:inline-block; }
        .ag-main-panel {
            min-height:0;
            display:flex;
            flex-direction:column;
        }
        .ag-main-scroll {
            min-height:0;
            overflow:visible;
            flex:1 1 auto;
        }
        .ag-day-cell {
            min-height: 100px; padding:.34rem; display:flex; flex-direction:column; gap:.22rem; cursor:pointer;
        }
        .ag-day-cell.is-empty { border-style:dashed; opacity:.45; cursor:default; }
        .ag-day-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
        .ag-day-head strong { font-size:.78rem; color:var(--vo-text); }
        .ag-day-events { display:flex; flex-direction:column; gap:.26rem; min-height:0; }
        .ag-event-chip, .ag-week-event, .ag-hour-event {
            border-left:3px solid transparent; border-radius:.5rem; background:rgba(148,163,184,.08); padding:.3rem .4rem;
        }
        .ag-event-chip { display:flex; align-items:flex-start; gap:.4rem; text-align:left; min-width:0; }
        .ag-dot, .ag-list-dot {
            width:9px; height:9px; border-radius:999px; flex-shrink:0; margin-top:.2rem;
        }
        .ag-event-link {
            border:0; background:transparent; padding:0; text-align:left; color:inherit; cursor:pointer; width:100%;
            display:flex; flex-direction:column; gap:.1rem;
            min-width:0;
        }
        .ag-event-link strong {
            font-size:.68rem; color:var(--vo-text); line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ag-event-link small {
            font-size:.6rem; color:var(--vo-text-faint); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ag-more-events {
            display:inline-flex; align-self:flex-start; border-radius:999px; background:var(--vo-bg-subtle); border:1px dashed var(--vo-border);
            color:var(--vo-text-muted); font-size:.62rem; font-weight:800; padding:.1rem .38rem;
        }
        .ag-week-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:.5rem; padding:0 1rem 1rem; }
        .ag-week-day { min-height: 460px; display:flex; flex-direction:column; }
        .ag-week-day-head { padding:.7rem; border-bottom:1px solid var(--vo-border-light); }
        .ag-week-day-head button {
            width:100%; border:0; background:transparent; text-align:left; cursor:pointer; display:flex; flex-direction:column; gap:.1rem;
            color:var(--vo-text);
        }
        .ag-week-day-head strong { font-size:.8rem; }
        .ag-week-day-head span { font-size:.7rem; color:var(--vo-text-faint); }
        .ag-week-day-body { padding:.6rem; display:flex; flex-direction:column; gap:.4rem; }
        .ag-empty-small, .ag-empty-detail { margin:0; color:var(--vo-text-faint); font-size:.8rem; }
        .ag-day-view { padding:0 .85rem .85rem; }
        .ag-day-all-day { margin-bottom:.7rem; }
        .ag-all-day-list { display:flex; flex-direction:column; gap:.3rem; }
        .ag-all-day-event {
            border:1px solid var(--vo-border-light); border-left-width:3px; border-radius:.75rem; background:var(--vo-bg-subtle);
            padding:.42rem .55rem; text-align:left; cursor:pointer; display:flex; flex-direction:column; gap:.08rem;
        }
        .ag-all-day-event strong { font-size:.72rem; color:var(--vo-text); }
        .ag-all-day-event small { font-size:.62rem; color:var(--vo-text-faint); }
        .ag-day-timeline { display:flex; flex-direction:column; gap:.28rem; }
        .ag-hour-row { display:grid; grid-template-columns:64px 1fr; min-height:48px; }
        .ag-hour-label {
            padding:.58rem .55rem; border-right:1px solid var(--vo-border-light); color:var(--vo-text-faint); font-size:.68rem; font-weight:800;
        }
        .ag-hour-events { padding:.38rem; display:flex; flex-direction:column; gap:.28rem; }
        .ag-modal-overlay {
            position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:80; display:flex; align-items:center; justify-content:center; padding:1rem;
        }
        .ag-modal {
            width:min(880px, 100%); max-height:92vh; overflow:auto; background:var(--vo-bg); border:1px solid var(--vo-border); border-radius:1rem;
            box-shadow:0 30px 80px rgba(15,23,42,.25);
        }
        .ag-modal-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1rem .75rem; border-bottom:1px solid var(--vo-border-light); }
        .ag-modal-head h3 { margin:0; font-size:1rem; font-weight:800; color:var(--vo-text); }
        .ag-close { border:0; background:var(--vo-bg-subtle); color:var(--vo-text); width:34px; height:34px; border-radius:.75rem; font-size:1.1rem; cursor:pointer; }
        .ag-modal-grid {
            display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem; padding:1rem;
        }
        .ag-span-full { grid-column:1 / -1; }
        .ag-check { flex-direction:row !important; align-items:center; gap:.55rem !important; padding-top:1.4rem; }
        .ag-check input { width:18px; height:18px; }
        .ag-participants-wrapper { position:relative; display:flex; flex-direction:column; gap:.5rem; }
        .ag-participant-search { border:1px solid var(--vo-border); border-radius:.7rem; background:var(--vo-bg-subtle); color:var(--vo-text); padding:.6rem .75rem; font-size:.8rem; font-family:inherit; width:100%; }
        .ag-participant-search:focus { outline:none; border-color:var(--vo-border); box-shadow:0 0 0 1px rgba(0,0,0,.08); }
        .ag-participants-dropdown { position:relative; max-height:none; overflow:hidden; border:1px solid var(--vo-border); border-radius:.7rem; background:var(--vo-bg); }
        .ag-participants-dropdown:not(:has(.ag-participant-more)) { max-height:none; }
        .ag-participants-dropdown:has(.ag-participant-more) { max-height:280px; overflow-y:auto; }
        .ag-participant-option { display:flex; align-items:center; gap:.5rem; width:100%; border:none; background:transparent; color:var(--vo-text-secondary); padding:.5rem .75rem; font-size:.8rem; cursor:pointer; text-align:left; transition:background .15s; }
        .ag-participant-option:hover { background:var(--vo-bg-subtle); }
        .ag-participant-option.is-selected { background:rgba(251,191,36,.1); color:var(--vo-text); font-weight:600; }
        .ag-participant-checkbox { width:16px; height:16px; border:1px solid var(--vo-border); border-radius:.4rem; display:inline-block; background:var(--vo-bg-subtle); flex-shrink:0; position:relative; }
        .ag-participant-checkbox.checked { background:#fbbf24; border-color:#fbbf24; }
        .ag-participant-checkbox.checked::after { content:'✓'; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#111; font-size:.6rem; font-weight:800; }
        .ag-participant-empty { padding:.75rem; text-align:center; color:var(--vo-text-faint); font-size:.75rem; }
        .ag-selected-participants { display:flex; flex-wrap:wrap; gap:.4rem; padding:.5rem 0 0; }
        .ag-participant-tag { display:inline-flex; align-items:center; gap:.3rem; background:rgba(251,191,36,.15); color:var(--vo-text); padding:.3rem .6rem; border-radius:999px; font-size:.75rem; font-weight:600; }
        .ag-participant-tag button { border:none; background:transparent; color:inherit; cursor:pointer; font-size:.9rem; padding:0; margin:0 -0.2rem 0 0; line-height:1; }
        .ag-participant-tag button:hover { opacity:.7; }
        .ag-participant-count { display:inline-flex; align-items:center; justify-content:center; background:rgba(251,191,36,.2); color:var(--vo-text); padding:.3rem .5rem; border-radius:999px; font-size:.75rem; font-weight:600; min-width:2rem; }
        .ag-participant-more { width:100%; padding:.5rem .75rem; border:none; background:transparent; color:var(--vo-text-muted); cursor:pointer; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; transition:background .15s; }
        .ag-participant-more:hover { background:var(--vo-bg-subtle); }
        .ag-activity-prompt { padding:.75rem; border:1px solid #f59e0b; background:rgba(245,158,11,.08); border-radius:.7rem; }
        .ag-activity-prompt p { margin:0 0 .6rem; color:var(--vo-text); font-size:.8rem; font-weight:600; }
        .ag-activity-buttons { display:flex; gap:.4rem; }
        .ag-btn-sm { padding:.4rem .6rem; font-size:.75rem; }
        .dark .ag-activity-prompt { background:rgba(245,158,11,.1); }
        .ag-activity-hint { margin:.5rem 0 0; padding:.5rem .6rem; background:rgba(59,130,246,.08); color:var(--vo-text-secondary); font-size:.75rem; line-height:1.4; border-radius:.5rem; border-left:2px solid #3b82f6; }
        .dark .ag-activity-hint { background:rgba(59,130,246,.1); }
        .ag-unidade-search-wrapper { position:relative; display:flex; flex-direction:column; gap:.3rem; }
        .ag-unidade-search { border:1px solid var(--vo-border); border-radius:.7rem; background:var(--vo-bg-subtle); color:var(--vo-text); padding:.6rem .75rem; font-size:.8rem; font-family:inherit; width:100%; }
        .ag-unidade-search:focus { outline:none; border-color:var(--vo-border); box-shadow:0 0 0 1px rgba(0,0,0,.08); }
        .ag-unidade-dropdown { position:relative; max-height:280px; overflow-y:auto; border:1px solid var(--vo-border); border-radius:.7rem; background:var(--vo-bg); margin-top:-.5rem; padding-top:.3rem; }
        .ag-unidade-option { display:flex; align-items:center; width:100%; border:none; background:transparent; color:var(--vo-text-secondary); padding:.5rem .75rem; font-size:.8rem; cursor:pointer; text-align:left; transition:background .15s; }
        .ag-unidade-option:hover { background:var(--vo-bg-subtle); }
        .ag-unidade-option.is-selected { background:rgba(251,191,36,.1); color:var(--vo-text); font-weight:600; }
        .ag-unidade-empty { padding:.75rem; text-align:center; color:var(--vo-text-faint); font-size:.75rem; }
        .ag-unidade-selected { padding:.6rem .75rem; background:var(--vo-bg-subtle); border:1px solid var(--vo-border); border-radius:.7rem; color:var(--vo-text-secondary); font-size:.8rem; }
        .ag-modal-actions { display:flex; justify-content:flex-end; gap:.5rem; padding:0 1rem 1rem; }
        .ag-color-picker-wrapper {
            display:flex;
            align-items:center;
            gap:.5rem;
            padding:.5rem .6rem;
            border:1px solid var(--vo-border);
            border-radius:.7rem;
            background:var(--vo-bg-subtle);
        }
        .ag-color-presets {
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
            flex:1 1 auto;
        }
        .ag-color-swatch {
            width:1.5rem;
            height:1.5rem;
            border-radius:50%;
            border:2px solid transparent;
            cursor:pointer;
            transition:transform .15s, border-color .15s;
            padding:0;
            outline:none;
        }
        .ag-color-swatch:hover {
            transform:scale(1.15);
        }
        .ag-color-swatch.is-selected {
            border-color:var(--vo-text);
            box-shadow:0 0 0 2px var(--vo-bg);
        }
        .ag-color-input {
            width:2rem !important;
            height:2rem !important;
            border:1px solid var(--vo-border) !important;
            border-radius:.5rem !important;
            padding:.1rem !important;
            cursor:pointer;
            background:var(--vo-bg) !important;
            flex-shrink:0;
        }
        .ag-color-input::-webkit-color-swatch-wrapper { padding:0; }
        .ag-color-input::-webkit-color-swatch { border:none; border-radius:.35rem; }
        .ag-detail-panel {
            display:flex;
            flex-direction:column;
        }
        .ag-detail-scroll {
            min-height:0;
            overflow:visible;
            flex:1 1 auto;
        }
        .ag-detail-card { padding:0 1rem 1rem; display:flex; flex-direction:column; gap:.65rem; }
        .ag-detail-badge { display:inline-flex; align-self:flex-start; padding:.3rem .6rem; border-radius:999px; font-size:.68rem; font-weight:800; }
        .ag-detail-card h2 { margin:0; font-size:1.05rem; font-weight:800; color:var(--vo-text); }
        .ag-detail-range { margin:0; color:var(--vo-text-faint); font-size:.82rem; }
        .ag-detail-list { display:grid; gap:.5rem; }
        .ag-detail-list div { border:1px solid var(--vo-border-light); border-radius:.75rem; padding:.55rem .65rem; background:var(--vo-bg-subtle); }
        .ag-detail-list span { display:block; text-transform:uppercase; font-size:.62rem; letter-spacing:.06em; color:var(--vo-text-faint); margin-bottom:.15rem; }
        .ag-detail-list strong { color:var(--vo-text); font-size:.82rem; overflow-wrap:anywhere; }
        .ag-detail-actions { display:flex; flex-wrap:wrap; gap:.5rem; }
        .ag-list { display:flex; flex-direction:column; gap:.4rem; padding:0 .85rem .85rem; }
        .ag-list-item {
            display:flex; align-items:flex-start; gap:.55rem; border:1px solid var(--vo-border-light); background:var(--vo-bg-subtle);
            border-radius:.7rem; padding:.5rem .55rem; text-align:left; cursor:pointer;
        }
        .ag-list-item strong { display:block; color:var(--vo-text); font-size:.75rem; }
        .ag-list-item small { display:block; color:var(--vo-text-faint); font-size:.64rem; margin-top:.08rem; }
        .ag-empty-detail { padding:0 1rem 1rem; }

        /* Quadradinho — cor do TIPO do evento (dentro dos chips) */
        .ag-tipo-pin {
            display:inline-block;
            width:10px;
            height:10px;
            border-radius:2px;
            flex-shrink:0;
            margin-top:.2rem;
            box-shadow:0 0 0 1px rgba(0,0,0,.08);
        }
        /* Quadradinho — cor do USUÁRIO (modal de configurações e detalhes) */
        .ag-user-pin {
            display:inline-block;
            width:10px;
            height:10px;
            border-radius:2px;
            flex-shrink:0;
            margin-right:.3rem;
            vertical-align:middle;
            box-shadow:0 0 0 1px rgba(0,0,0,.08);
        }
        .ag-user-pin--lg { width:14px; height:14px; border-radius:4px; margin-right:.5rem; }
        .ag-all-day-event-head { display:flex; align-items:center; gap:.35rem; }
        .ag-hour-event, .ag-week-event { display:flex; align-items:flex-start; gap:.4rem; padding-left:.45rem; }

        /* Anexos no painel de detalhes e na lista do modal */
        .ag-anexos-section { padding:0 1rem 1rem; display:flex; flex-direction:column; gap:.4rem; }
        .ag-anexos-label { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--vo-text-muted); margin:0; }
        .ag-anexos-list { display:flex; flex-direction:column; gap:.3rem; }
        .ag-anexo-item {
            display:flex; align-items:center; gap:.5rem;
            padding:.45rem .55rem;
            background:var(--vo-bg-subtle);
            border:1px solid var(--vo-border-light);
            border-radius:.5rem;
            font-size:.78rem;
            color:var(--vo-text);
            text-decoration:none;
        }
        .ag-anexo-item:hover { border-color:var(--vo-text-muted); }
        .ag-anexo-icon { font-size:1rem; flex-shrink:0; }
        .ag-anexo-nome {
            flex:1; min-width:0;
            color:var(--vo-text);
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            text-decoration:none;
        }
        a.ag-anexo-nome:hover { text-decoration:underline; }
        .ag-anexo-item--button {
            border:1px solid var(--vo-border-light);
            cursor:pointer;
            width:100%;
            text-align:left;
            font:inherit;
        }
        .ag-anexo-item--button:hover { border-color:var(--vo-text-muted); }
        .ag-anexo-nome--button {
            background:transparent;
            border:none;
            padding:0;
            cursor:pointer;
            font:inherit;
            text-align:left;
        }
        .ag-anexo-nome--button:hover { text-decoration:underline; }

        /* Modal de preview de anexo */
        .ag-preview-modal {
            width:min(960px, 100%);
            max-height:92vh;
            display:flex;
            flex-direction:column;
            background:var(--vo-bg);
            border:1px solid var(--vo-border);
            border-radius:1rem;
            box-shadow:0 30px 80px rgba(15,23,42,.25);
            overflow:hidden;
        }
        .ag-preview-head-actions { display:flex; align-items:center; gap:.4rem; }
        .ag-preview-body {
            flex:1 1 auto;
            min-height:0;
            display:flex;
            align-items:center;
            justify-content:center;
            background:var(--vo-bg-subtle);
            padding:1rem;
            overflow:auto;
        }
        .ag-preview-img {
            max-width:100%;
            max-height:78vh;
            object-fit:contain;
            border-radius:.5rem;
            background:#fff;
            box-shadow:0 8px 24px rgba(15,23,42,.12);
        }
        .ag-preview-iframe {
            width:100%;
            height:78vh;
            border:none;
            border-radius:.5rem;
            background:#fff;
        }
        .ag-preview-fallback {
            display:flex; flex-direction:column; align-items:center; gap:.75rem;
            padding:2rem; color:var(--vo-text-muted); text-align:center;
        }

        /* Campo de anexos no formulário */
        .ag-anexos-field { display:flex; flex-direction:column; gap:.55rem; }
        .ag-anexos-dropzone {
            position:relative;
            display:flex;
            align-items:center;
            justify-content:center;
            border:2px dashed var(--vo-border);
            border-radius:.85rem;
            background:var(--vo-bg-subtle);
            padding:1rem 1.1rem;
            cursor:pointer;
            transition:border-color .15s, background .15s;
        }
        .ag-anexos-dropzone:hover {
            border-color:var(--vo-accent);
            background:rgba(251,191,36,.06);
        }
        .ag-anexos-input-hidden {
            position:absolute;
            inset:0;
            opacity:0;
            cursor:pointer;
            width:100%;
            height:100%;
        }
        .ag-anexos-dropzone-content {
            display:flex;
            align-items:center;
            gap:.75rem;
            pointer-events:none;
        }
        .ag-anexos-dropzone-icon { font-size:1.5rem; }
        .ag-anexos-dropzone-text { display:flex; flex-direction:column; gap:.1rem; }
        .ag-anexos-dropzone-text strong { font-size:.82rem; color:var(--vo-text); }
        .ag-anexos-dropzone-text small { font-size:.7rem; color:var(--vo-text-muted); }
        .ag-anexo-remove {
            border:none;
            background:transparent;
            color:var(--vo-text-muted);
            cursor:pointer;
            font-size:1.2rem;
            line-height:1;
            padding:0 .35rem;
            border-radius:.35rem;
        }
        .ag-anexo-remove:hover { color:#dc2626; background:rgba(220,38,38,.08); }
        .ag-anexo-tag {
            display:inline-flex;
            align-items:center;
            font-size:.6rem;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.04em;
            padding:.15rem .45rem;
            border-radius:999px;
        }
        .ag-anexo-tag--saved { background:rgba(16,185,129,.12); color:#047857; }
        .ag-anexo-tag--pending { background:rgba(251,191,36,.18); color:#92400e; }
        .ag-anexo-item--pending { background:rgba(251,191,36,.08); border-color:rgba(251,191,36,.3); }
        .ag-anexo-error { color:#dc2626; font-size:.72rem; }

        /* Modal de configurações */
        .ag-settings-body { padding:1rem; display:flex; flex-direction:column; gap:.8rem; }
        .ag-settings-hint { margin:0; font-size:.78rem; color:var(--vo-text-muted); line-height:1.4; }
        .ag-settings-list { display:flex; flex-direction:column; gap:.45rem; }
        .ag-settings-row {
            display:flex; align-items:center; gap:.6rem;
            padding:.55rem .65rem;
            border:1px solid var(--vo-border-light);
            background:var(--vo-bg-subtle);
            border-radius:.65rem;
        }
        .ag-settings-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:.1rem; }
        .ag-settings-nome { font-size:.82rem; font-weight:600; color:var(--vo-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ag-settings-setor { font-size:.68rem; color:var(--vo-text-faint); text-transform:uppercase; letter-spacing:.04em; }

        /* Abas do modal de configurações */
        .ag-settings-tabs {
            display:flex;
            gap:.25rem;
            padding:0 1rem;
            border-bottom:1px solid var(--vo-border-light);
        }
        .ag-settings-tab {
            border:none;
            background:transparent;
            color:var(--vo-text-muted);
            font-size:.8rem;
            font-weight:700;
            padding:.7rem 1rem;
            cursor:pointer;
            position:relative;
            border-bottom:2px solid transparent;
            margin-bottom:-1px;
            transition:color .15s, border-color .15s;
        }
        .ag-settings-tab:hover { color:var(--vo-text); }
        .ag-settings-tab.is-active {
            color:var(--vo-text);
            border-bottom-color:var(--vo-accent);
        }

        /* Formulário de novo tipo */
        .ag-tipo-form {
            padding:.65rem .75rem;
            background:var(--vo-bg-subtle);
            border:1px solid var(--vo-border-light);
            border-radius:.75rem;
        }
        .ag-tipo-form-row {
            display:flex;
            gap:.5rem;
            align-items:center;
            flex-wrap:wrap;
        }
        .ag-tipo-input {
            flex:1;
            min-width:160px;
            border:1px solid var(--vo-border);
            border-radius:.6rem;
            background:var(--vo-bg);
            color:var(--vo-text);
            padding:.5rem .65rem;
            font-size:.82rem;
            font-family:inherit;
        }
        .ag-tipo-input:focus { outline:none; border-color:var(--vo-text-muted); }
        .ag-tipo-input--inline { background:transparent; border-color:transparent; padding:.35rem .5rem; }
        .ag-tipo-input--inline:hover, .ag-tipo-input--inline:focus { background:var(--vo-bg); border-color:var(--vo-border); }

        /* Informações da Unidade */
        .ag-unidade-info {
            padding: .75rem;
            background: var(--vo-bg-subtle);
            border: 1px solid var(--vo-border-light);
            border-radius: .75rem;
        }
        .ag-unidade-info-label {
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--vo-text-muted);
            margin: 0 0 .6rem;
        }
        .ag-unidade-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
        }
        .ag-unidade-info-item {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }
        .ag-unidade-info-item--full {
            grid-column: 1 / -1;
        }
        .ag-unidade-info-item span {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--vo-text-faint);
        }
        .ag-unidade-info-item strong {
            font-size: .8rem;
            color: var(--vo-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Painel de Convites */
        .ag-invites-panel {
            display: flex;
            flex-direction: column;
        }
        .ag-invites-scroll {
            min-height: 0;
            overflow: visible;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: .8rem;
            padding: .85rem;
        }
        .ag-invites-section {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }
        .ag-invites-section-title {
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--vo-text-muted);
            margin: 0 0 .4rem;
            padding-bottom: .3rem;
            border-bottom: 1px solid var(--vo-border-light);
        }
        .ag-invites-list {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }
        .ag-invite-card {
            border: 1px solid var(--vo-border-light);
            border-radius: .65rem;
            padding: .55rem .65rem;
            background: var(--vo-bg-subtle);
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }
        .ag-invite-card--pending {
            border-left: 3px solid #f59e0b;
        }
        .ag-invite-card--accepted {
            border-left: 3px solid #10b981;
        }
        .ag-invite-card--rejected {
            border-left: 3px solid #ef4444;
        }
        .ag-invite-header {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }
        .ag-invite-header strong {
            font-size: .75rem;
            font-weight: 700;
            color: var(--vo-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ag-invite-responsible {
            font-size: .65rem;
            color: var(--vo-text-faint);
        }
        .ag-invite-datetime {
            font-size: .65rem;
            color: var(--vo-text-muted);
        }
        .ag-invite-actions {
            display: flex;
            gap: .3rem;
            margin-top: .2rem;
        }
        .ag-invite-btn {
            flex: 1;
            padding: .35rem .4rem;
            border: none;
            border-radius: .4rem;
            font-size: .7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
            color: white;
        }
        .ag-invite-btn--accept {
            background: #10b981;
        }
        .ag-invite-btn--accept:hover {
            background: #059669;
        }
        .ag-invite-btn--reject {
            background: #ef4444;
        }
        .ag-invite-btn--reject:hover {
            background: #dc2626;
        }
        .ag-invite-status {
            font-size: .6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .2rem .4rem;
            border-radius: 999px;
            display: inline-block;
            width: fit-content;
        }
        .ag-invite-status--accepted {
            background: rgba(16, 185, 129, .15);
            color: #047857;
        }
        .ag-invite-status--rejected {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }
        .ag-badge-count {
            position: absolute;
            top: -0.35rem;
            right: -0.35rem;
            background: #ef4444;
            color: white;
            border-radius: 999px;
            width: 1.2rem;
            height: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 800;
        }

        @media (max-width: 1400px) {
            .ag-layout { grid-template-columns: 230px minmax(0, 1fr); }
            .ag-details { grid-column:1 / -1; }
        }

        @media (max-width: 1100px) {
            .ag-layout { grid-template-columns: 1fr; }
            .ag-sidebar, .ag-details { grid-column:1 / -1; }
            .ag-main-panel, .ag-detail-panel { height:auto; }
            .ag-week-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .ag-topbar { align-items:flex-start; flex-direction:column; }
            .ag-top-actions { width:100%; justify-content:flex-start; }
            .ag-layout { gap:.75rem; }
            .ag-sidebar {
                display:grid;
                grid-template-columns:repeat(2, minmax(0, 1fr));
                gap:.65rem;
            }
            .ag-sidebar .ag-panel {
                min-width:0;
            }
            .ag-sidebar .ag-panel:first-child {
                grid-column:1 / -1;
            }
            .ag-sidebar .ag-panel:nth-child(2) {
                grid-column:1 / -1;
            }
            .ag-sidebar .ag-panel:last-child {
                grid-column:1 / -1;
            }
            .ag-details {
                margin-top:.25rem;
            }
            .ag-panel-head { padding:.6rem .7rem .35rem; }
            .ag-panel-title { font-size:.72rem; letter-spacing:.05em; }
            .ag-mini-calendar { padding:0 .7rem .7rem; }
            .ag-mini-weekdays span { font-size:.58rem; }
            .ag-mini-grid { gap:.2rem; }
            .ag-mini-day { width:26px; height:26px; }
            .ag-mini-day span { font-size:.7rem; line-height:1; }
            .ag-filters { gap:.45rem; padding:0 .7rem .7rem; }
            .ag-filters label { gap:.2rem; }
            .ag-filters input, .ag-filters select { padding:.55rem .65rem; font-size:.78rem; }
            .ag-legend {
                grid-template-columns:repeat(2, minmax(0, 1fr));
                gap:.35rem .5rem;
                padding:0 .7rem .7rem;
            }
            .ag-legend div { font-size:.68rem; }
            .ag-panel-head-spaced { margin-top:.15rem; padding-top:.5rem; }
            .ag-modal-grid { grid-template-columns:1fr; }
            .ag-month-grid { padding:0 .7rem .7rem; gap:.22rem; }
            .ag-week-cells { gap:.22rem; }
            .ag-multi-day-row {
                top:1.7rem;
                gap:.12rem .22rem;
                padding:0 .25rem;
                grid-auto-rows:1.1rem;
            }
            .ag-multi-day-bar {
                font-size:.6rem;
                height:1rem;
                padding:0 .35rem;
            }
            .ag-weekdays { padding:0 .7rem; }
            .ag-day-cell, .ag-week-day { min-height:auto; }
            .ag-day-cell { padding:.28rem; gap:.18rem; }
            .ag-day-events { gap:.18rem; }
            .ag-event-chip { padding:.24rem .34rem; }
            .ag-event-link strong { font-size:.64rem; }
            .ag-event-link small { font-size:.58rem; }
            .ag-hour-row { grid-template-columns:58px 1fr; }
            .ag-sidebar, .ag-details { padding-right:0; }
            .ag-details { grid-column:1 / -1; }
        }
        input[type="datetime-local"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: var(--vo-bg-subtle);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allDayCheckbox = document.getElementById('all_day');
            const startsAtInput = document.getElementById('starts_at');
            const endsAtInput = document.getElementById('ends_at');

            function toggleTimeInputs() {
                const isAllDay = allDayCheckbox.checked;
                startsAtInput.disabled = isAllDay;
                endsAtInput.disabled = isAllDay;
            }

            allDayCheckbox.addEventListener('change', toggleTimeInputs);

            endsAtInput.addEventListener('change', function() {
                const startsAt = startsAtInput.value;
                const endsAt = endsAtInput.value;

                if (startsAt && endsAt && !allDayCheckbox.checked) {
                    const start = new Date(startsAt);
                    const end = new Date(endsAt);

                    if (end < start) {
                        alert('A data de fim não pode ser anterior à data de início.');
                        endsAtInput.value = startsAt;
                    }
                }
            });

            startsAtInput.addEventListener('change', function() {
                const startsAt = startsAtInput.value;
                const endsAt = endsAtInput.value;

                if (startsAt && endsAt && !allDayCheckbox.checked) {
                    const start = new Date(startsAt);
                    const end = new Date(endsAt);

                    if (end < start) {
                        endsAtInput.value = startsAt;
                    }
                }
            });
        });

        window.addEventListener('livewire:updated', function() {
            const allDayCheckbox = document.getElementById('all_day');
            const startsAtInput = document.getElementById('starts_at');
            const endsAtInput = document.getElementById('ends_at');

            if (allDayCheckbox && startsAtInput && endsAtInput) {
                const isAllDay = allDayCheckbox.checked;
                startsAtInput.disabled = isAllDay;
                endsAtInput.disabled = isAllDay;
            }
        });
    </script>
</x-filament::page>
