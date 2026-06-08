<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Resources\Obras\Support\ObrasEditFormSchema;
use App\Filament\Resources\Obras\Tables\ObrasColumnFilters;
use App\Filament\Tables\TableExcel\Actions\ManageColumnsAction;
use App\Filament\Tables\TableExcel\Actions\ManageFiltersAction;
use App\Filament\Tables\TableExcel\Page\Columns\ActionsColumn;
use App\Filament\Tables\TableExcel\Page\Columns\Column;
use App\Filament\Tables\TableExcel\Page\Columns\DateColumn;
use App\Filament\Tables\TableExcel\Page\Columns\PillColumn;
use App\Filament\Tables\TableExcel\Page\Columns\RowAction;
use App\Filament\Tables\TableExcel\Page\Columns\TextColumn;
use App\Filament\Tables\TableExcel\Page\Columns\TextInputColumn;
use App\Filament\Tables\TableExcel\Page\Concerns\HasTableExcelPage;
use App\Filament\Tables\TableExcel\Page\Filters\DateRangeFilter;
use App\Filament\Tables\TableExcel\Page\Filters\Filter;
use App\Filament\Tables\TableExcel\Page\Filters\PeriodFilter;
use App\Filament\Tables\TableExcel\Page\Filters\SelectFilter;
use App\Filament\Tables\TableExcel\Page\TableExcelPage;
use App\Models\ColunaPersonalizada;
use App\Models\Obras;
use App\Models\TablePreset;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Primeiro cliente do modo Page do Table Excel.
 * Coexiste com /admin/obras (modo Preset) e demonstra o visual estilo Cronograma.
 */
class ListaObrasNova extends Page
{
    use HasTableExcelPage;

    protected static string $resource = ObrasResource::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';

    protected string $view = 'filament.pages.lista-obras-nova';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Obras (nova)';

    protected static ?string $title = 'Obras';

    protected static ?string $slug = 'obras-nova';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Obras::class) ?? false;
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected ?Collection $cachedPontosAtencaoDefs = null;

    protected function getHeaderActions(): array
    {
        // Header do Filament fica vazio — as actions são renderizadas dentro
        // do próprio container da tabela via getTableExcelToolbarActions().
        return [];
    }

    /**
     * @return array<int, string> Nomes dos métodos *Action() a renderizar na toolbar da tabela.
     */
    public function getTableExcelToolbarActions(): array
    {
        return [
            'criarObraAction',
            'configurarFiltrosAction',
            'gerenciarColunasAction',
            'criarCampoPontoAtencaoAction',
            'gerenciarColunasPersonalizadasAction',
            'historicoGlobalAction',
        ];
    }

    public function configurarFiltrosAction(): Action
    {
        return ManageFiltersAction::make(
            $this->getTableExcelPage()->getFilters(),
            'configurarFiltros',
        );
    }

    public function criarObraAction(): Action
    {
        return Action::make('criarObra')
            ->label('Criar obra')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->url(fn () => ObrasResource::getUrl('create'))
            ->visible(fn () => auth()->user()?->can('create', Obras::class) ?? false);
    }

    public function gerenciarColunasAction(): Action
    {
        $columnsOptions = collect($this->getTableExcelPage()->getColumns())
            ->filter(fn ($col) => $col->toggleable && $col->key !== 'actions' && ($col->label ?? '') !== '')
            ->mapWithKeys(fn ($col) => [
                $col->key => $col->group ? "{$col->group} · {$col->label}" : $col->label,
            ])
            ->all();

        return ManageColumnsAction::make(
            'obras.nova',
            $columnsOptions,
            'gerenciarColunas',
        );
    }

    public function criarCampoPontoAtencaoAction(): Action
    {
        return Action::make('criarCampoPontoAtencao')
            ->label('Campo Pontos de Atenção')
            ->icon('heroicon-o-plus-circle')
            ->color('warning')
            ->modalHeading('Novo campo de Pontos de Atenção')
            ->modalSubmitActionLabel('Criar campo')
            ->modalCancelActionLabel('Cancelar')
            ->visible(fn () => $this->canManagePontosAtencao())
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome da coluna')
                    ->required()
                    ->maxLength(120),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->required()
                    ->native(false)
                    ->live()
                    ->options([
                        'texto' => 'Texto',
                        'numero' => 'Número',
                        'data' => 'Data',
                        'select' => 'Lista de opções',
                    ]),
                Forms\Components\TextInput::make('opcoes')
                    ->label('Opções da lista')
                    ->placeholder('Ex.: Pendente, Em análise, Concluído')
                    ->visible(fn ($get) => $get('tipo') === 'select'),
            ]))
            ->action(function (array $data): void {
                $tipo = (string) ($data['tipo'] ?? 'texto');
                $opcoes = $tipo === 'select'
                    ? collect(preg_split('/[\r\n,;]+/', (string) ($data['opcoes'] ?? '')) ?: [])
                        ->map(fn ($item) => trim((string) $item))
                        ->filter(fn ($item) => $item !== '')
                        ->unique()
                        ->values()
                        ->all()
                    : null;

                if ($tipo === 'select' && empty($opcoes)) {
                    Notification::make()->title('Informe as opções da lista')->danger()->send();

                    return;
                }

                $nome = trim((string) ($data['nome'] ?? ''));
                $obras = Obras::query()
                    ->whereNotNull('projeto_id')
                    ->get(['id', 'projeto_id']);

                foreach ($obras as $obra) {
                    ColunaPersonalizada::firstOrCreate(
                        [
                            'projeto_id' => $obra->projeto_id,
                            'obra_id' => $obra->id,
                            'nome' => $nome,
                        ],
                        [
                            'tipo' => $tipo,
                            'opcoes' => $opcoes,
                            'valor' => null,
                            'usuario_id' => auth()->id(),
                        ],
                    );
                }

                Cache::forget('obras_pontos_atencao_definitions');
                Cache::forget('obras_select_filters');
                $this->cachedPontosAtencaoDefs = null;
                $this->cachedTableExcelPage = null;

                Notification::make()
                    ->title('Campo criado para todas as obras')
                    ->success()
                    ->send();

                $this->redirect(static::getUrl());
            });
    }

    public function gerenciarColunasPersonalizadasAction(): Action
    {
        return Action::make('gerenciarColunasPersonalizadas')
            ->label('Gerenciar Colunas PA')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading('Gerenciar Colunas Personalizadas')
            ->modalDescription('Selecione uma coluna de Pontos de Atenção para removê-la de todas as obras.')
            ->modalSubmitActionLabel('Excluir coluna')
            ->modalCancelActionLabel('Cancelar')
            ->requiresConfirmation()
            ->visible(fn () => $this->canManagePontosAtencao()
                && ColunaPersonalizada::query()->distinct('nome')->exists())
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Forms\Components\Select::make('coluna_nome')
                    ->label('Coluna personalizada')
                    ->options(function () {
                        return ColunaPersonalizada::query()
                            ->selectRaw('nome, tipo, COUNT(DISTINCT obra_id) as total_obras')
                            ->groupBy('nome', 'tipo')
                            ->orderBy('nome')
                            ->get()
                            ->mapWithKeys(fn ($item) => [
                                $item->nome => sprintf(
                                    '%s (%s • %s obra%s)',
                                    $item->nome,
                                    $this->formatarTipoPontoAtencao((string) $item->tipo),
                                    $item->total_obras,
                                    $item->total_obras === 1 ? '' : 's',
                                ),
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),
            ]))
            ->action(function (array $data): void {
                $nome = trim((string) ($data['coluna_nome'] ?? ''));
                if ($nome === '') {
                    return;
                }

                ColunaPersonalizada::query()->where('nome', $nome)->delete();
                Cache::forget('obras_pontos_atencao_definitions');
                Cache::forget('obras_select_filters');
                $this->cachedPontosAtencaoDefs = null;
                $this->cachedTableExcelPage = null;

                Notification::make()->title('Coluna excluída')->success()->send();

                $this->redirect(static::getUrl());
            });
    }

    protected function formatarTipoPontoAtencao(string $tipo): string
    {
        return match ($tipo) {
            'texto' => 'Texto',
            'numero' => 'Número',
            'data' => 'Data',
            'select' => 'Lista de opções',
            default => Str::ucfirst($tipo),
        };
    }

    protected function canManagePontosAtencao(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Gestor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    public function historicoObraAction(): Action
    {
        return Action::make('historicoObra')
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading(function (array $arguments): string {
                $obra = Obras::with('projeto:id,sigla,nome')->find($arguments['record'] ?? null);

                $titulo = $obra?->projeto?->nome
                    ?? $obra?->projeto?->sigla
                    ?? $obra?->codigo
                    ?? 'Obra';

                return 'Histórico — '.$titulo;
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn (array $arguments) => view('filament.actions.historico-obra-modal', [
                'obraId' => (int) ($arguments['record'] ?? 0),
            ]));
    }

    public function historicoGlobalAction(): Action
    {
        return Action::make('historicoGlobal')
            ->label('Histórico')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading('Histórico — obras filtradas')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.actions.historico-obra-modal', [
                'obraIds' => $this->getFilteredObraIds(),
            ]));
    }

    /**
     * IDs das obras que batem nos filtros/busca atuais (ignora paginação/ordenação).
     *
     * @return array<int, int>
     */
    protected function getFilteredObraIds(): array
    {
        $config = $this->getTableExcelPage();
        $query = $config->buildQuery();

        $this->applySearch($query, $config);
        $this->applyFilters($query, $config);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function editarObraAction(): Action
    {
        return Action::make('editarObra')
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading(function (array $arguments): string {
                $obra = Obras::find($arguments['record'] ?? null);

                return 'Editar: '.($obra?->sigla ?? $obra?->codigo ?? 'Obra');
            })
            ->modalSubmitActionLabel('Salvar')
            ->authorize(function (array $arguments): bool {
                $obra = Obras::find($arguments['record'] ?? null);
                if (! $obra) {
                    return false;
                }

                return auth()->user()?->can('update', $obra) ?? false;
            })
            ->fillForm(function (array $arguments): array {
                $obra = Obras::find($arguments['record'] ?? null);
                if (! $obra) {
                    return [];
                }

                return array_merge(
                    $obra->attributesToArray(),
                    ObrasEditFormSchema::getPontosAtencaoValues($obra),
                );
            })
            ->schema(ObrasEditFormSchema::schema())
            ->action(function (array $data, array $arguments): void {
                $obra = Obras::find($arguments['record'] ?? null);
                if (! $obra) {
                    return;
                }

                ObrasEditFormSchema::saveFromForm($obra, $data);

                Notification::make()
                    ->title('Obra atualizada')
                    ->success()
                    ->send();
            });
    }

    protected function tableExcelPage(): TableExcelPage
    {
        return TableExcelPage::make()
            ->query(fn () => Obras::query()
                ->with([
                    'projeto:id,sigla,nova_sigla,nome,marca,status_contrato,inauguracao,tipo_imovel,empreendimento,locacao,contato_corretor',
                    'colunasPersonalizadas:obra_id,nome,valor',
                ])
                ->orderBy('codigo'))
            ->columns($this->buildColumns())
            ->filters($this->buildFilters())
            ->search('busca', 'Buscar projeto...', ['codigo', 'projeto.nome', 'projeto.sigla'])
            ->perPage(50)
            ->recordKey('id')
            ->tableKey('obras.nova')
            ->bulkDelete(fn (Obras $obra, $user): bool => $user?->can('delete', $obra) ?? false)
            ->stickyHeader()
            ->stickyActions()
            ->dense()
            ->striped()
            ->freezable()
            ->resizable()
            ->emptyState(
                'Nenhuma obra encontrada',
                'Ajuste a busca ou os filtros para ver resultados.',
            );
    }

    /**
     * @return array<int, Column>
     */
    protected function buildColumns(): array
    {
        $statusOptions = [
            'Em processo' => 'Em processo',
            'Obras' => 'Obras',
            'Inaugurada' => 'Inaugurada',
            'Cancelada' => 'Cancelada',
            'Stand-by' => 'Stand-by',
            'Deletar comercial' => 'Deletar comercial',
        ];
        $statusColors = [
            'Em processo' => PillColumn::COLOR_INFO,
            'Obras' => PillColumn::COLOR_WARNING,
            'Inaugurada' => PillColumn::COLOR_SUCCESS,
            'Cancelada' => PillColumn::COLOR_DANGER,
            'Stand-by' => PillColumn::COLOR_NEUTRAL,
            'Deletar comercial' => PillColumn::COLOR_DANGER,
        ];

        $statusContratoOptions = [
            'ASSINADO' => 'ASSINADO',
            'EM ASSINATURA' => 'EM ASSINATURA',
            'MINUTA' => 'MINUTA',
            'NEGOCIAÇÃO' => 'NEGOCIAÇÃO',
        ];
        $statusContratoColors = [
            'ASSINADO' => PillColumn::COLOR_SUCCESS,
            'EM ASSINATURA' => PillColumn::COLOR_INFO,
            'MINUTA' => PillColumn::COLOR_WARNING,
            'NEGOCIAÇÃO' => PillColumn::COLOR_NEUTRAL,
        ];

        $etapaOptions = [
            'CONCLUÍDO' => 'Concluído',
            'EM ANDAMENTO' => 'Em Andamento',
            'N/A' => 'N/A',
            'NÃO INICIADO' => 'Não Iniciado',
            'AGENDADO' => 'Agendado',
            'PENDÊNCIAS' => 'Pendências',
            'NÃO SOLICITADO' => 'Não Solicitado',
            'SOLICITADO' => 'Solicitado',
        ];
        $etapaColors = [
            'CONCLUÍDO' => PillColumn::COLOR_SUCCESS,
            'EM ANDAMENTO' => PillColumn::COLOR_INFO,
            'N/A' => PillColumn::COLOR_NEUTRAL,
            'NÃO INICIADO' => PillColumn::COLOR_NEUTRAL,
            'AGENDADO' => PillColumn::COLOR_INFO,
            // PARALISADO mantido apenas como fallback para registros antigos.
            'PARALISADO' => PillColumn::COLOR_DANGER,
            'PENDÊNCIAS' => PillColumn::COLOR_WARNING,
            'NÃO SOLICITADO' => PillColumn::COLOR_NEUTRAL,
            'SOLICITADO' => PillColumn::COLOR_INFO,
        ];

        $envioOptions = [
            'enviado' => 'Enviado',
            'nao_enviado' => 'Não Enviado',
        ];
        $envioColors = [
            'enviado' => PillColumn::COLOR_SUCCESS,
            'nao_enviado' => PillColumn::COLOR_DANGER,
        ];

        $relFotoOptions = [
            'enviado' => 'Enviado',
            'pendencias' => 'Enviado com Pendências',
            'nao_enviado' => 'Não Enviado',
        ];
        $relFotoColors = [
            'enviado' => PillColumn::COLOR_SUCCESS,
            'pendencias' => PillColumn::COLOR_WARNING,
            'nao_enviado' => PillColumn::COLOR_DANGER,
        ];

        $simNaoOptions = [
            'sim' => 'Sim',
            'nao' => 'Não',
        ];
        $simNaoColors = [
            'sim' => PillColumn::COLOR_SUCCESS,
            'nao' => PillColumn::COLOR_DANGER,
        ];

        $energiaOptions = [
            'Ligada / Rateio' => 'Ligada / Rateio',
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
            'Pendente, responsavel PP' => 'Pendente, resp. PP',
            'GERADOR' => 'GERADOR',
        ];
        $aguaOptions = [
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
            'Pendente, responsavel PP' => 'Pendente, resp. PP',
            'Ligada / Rateio' => 'Ligada / Rateio',
        ];
        $gasOptions = [
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
            'Pendente, responsavel PP' => 'Pendente, resp. PP',
            'Boiler Instalado provisório' => 'Boiler Instalado provisório',
        ];
        $consumoColors = fn (mixed $state): string => match (true) {
            blank($state) => PillColumn::COLOR_NEUTRAL,
            str_contains(strtolower((string) $state), 'ligada') => PillColumn::COLOR_SUCCESS,
            str_contains(strtolower((string) $state), 'pendente') => PillColumn::COLOR_WARNING,
            default => PillColumn::COLOR_INFO,
        };

        $locacaoColors = [
            'Mono usuário' => PillColumn::COLOR_INFO,
            'Multiusuário' => PillColumn::COLOR_SUCCESS,
        ];

        $checklistOptions = [
            'concluido' => 'Concluído',
            'em_andamento' => 'Em andamento',
            'em_atraso' => 'Em atraso',
            'nao_iniciado' => 'Não iniciado',
        ];
        $checklistColors = [
            'concluido' => PillColumn::COLOR_SUCCESS,
            'em_andamento' => PillColumn::COLOR_INFO,
            'em_atraso' => PillColumn::COLOR_DANGER,
            'nao_iniciado' => PillColumn::COLOR_NEUTRAL,
        ];

        $diasSuffix = fn ($state) => filled($state)
            ? ((int) $state).' '.((int) $state === 1 ? 'dia' : 'dias')
            : null;

        $diasPrazoColors = fn (mixed $state): string => match (true) {
            $state === null || $state === '' || $state === '-' => PillColumn::COLOR_NEUTRAL,
            (int) $state < 0 => PillColumn::COLOR_DANGER,
            (int) $state <= 15 => PillColumn::COLOR_WARNING,
            default => PillColumn::COLOR_SUCCESS,
        };

        $mesLabel = fn ($state) => match ((int) $state) {
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            default => $state,
        };

        $authorizeUpdate = fn (Obras $record, $user): bool => $user?->can('update', $record) ?? false;

        $editSelect = fn (string $field, array $options) => function (Obras $record, string $newValue) use ($field, $options): void {
            if (! array_key_exists($newValue, $options)) {
                return;
            }
            $record->update([$field => $newValue]);
        };

        $editText = fn (string $field) => function (Obras $record, string $newValue) use ($field): void {
            $val = trim($newValue);
            $record->update([$field => $val === '' ? null : $val]);
        };

        $editNumber = fn (string $field, ?float $min = null, ?float $max = null) => function (Obras $record, string $newValue) use ($field, $min, $max): void {
            $val = trim($newValue);
            if ($val === '') {
                $record->update([$field => null]);

                return;
            }

            if (! is_numeric($val)) {
                Notification::make()->title('Valor numérico inválido')->danger()->send();

                return;
            }

            $num = (float) $val;
            if ($min !== null && $num < $min) {
                Notification::make()->title("Valor mínimo: {$min}")->danger()->send();

                return;
            }
            if ($max !== null && $num > $max) {
                Notification::make()->title("Valor máximo: {$max}")->danger()->send();

                return;
            }

            $record->update([$field => $num]);
        };

        $editDate = fn (string $field) => function (Obras $record, string $newValue) use ($field): void {
            try {
                $record->update([$field => $this->normalizeTableDateInput($newValue)]);
            } catch (\Throwable) {
                Notification::make()->title('Data inválida')->danger()->send();
            }
        };

        $makeDateInput = fn (string $field, string $label) => TextInputColumn::make($field, $label)
            ->align('center')
            ->type('date')
            ->getStateUsing(fn (Obras $r) => $this->formatTableDateInput($r->$field))
            ->authorizeEditUsing($authorizeUpdate)
            ->onEditUsing($editDate($field));

        $makeTextInput = fn (string $field, string $label, string $align = 'start') => TextInputColumn::make($field, $label)
            ->align($align)
            ->authorizeEditUsing($authorizeUpdate)
            ->onEditUsing($editText($field));

        $makeNumberInput = fn (string $field, string $label, ?float $min = null, ?float $max = null, string $suffix = '') => TextInputColumn::make($field, $label)
            ->align('center')
            ->type('number')
            ->step('any')
            ->getStateUsing(fn (Obras $r) => $r->$field !== null ? (string) $r->$field : null)
            ->authorizeEditUsing($authorizeUpdate)
            ->onEditUsing($editNumber($field, $min, $max));

        $columns = array_merge(
            [
                ActionsColumn::make('actions', '')
                    ->align('center')
                    ->toggleable(false)
                    ->reorderable(false)
                    ->actions([
                        RowAction::make('view', 'Visualizar')
                            ->icon('heroicon-o-eye')
                            ->url(fn (Obras $obra): string => route(
                                'filament.admin.resources.obras.view',
                                ['record' => $obra->id],
                            )),
                        RowAction::make('edit', 'Editar')
                            ->icon('heroicon-o-pencil-square')
                            ->mountsAction('editarObra'),
                        RowAction::make('historico', 'Histórico')
                            ->icon('heroicon-o-clock')
                            ->mountsAction('historicoObra'),
                        RowAction::make('delete', 'Excluir')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->confirm('Tem certeza que deseja excluir esta obra?')
                            ->authorizeUsing(fn (Obras $obra, $user): bool => $user?->can('delete', $obra) ?? false)
                            ->onClickUsing(function (Obras $obra): void {
                                $obra->delete();
                                Notification::make()
                                    ->title('Obra excluída')
                                    ->success()
                                    ->send();
                            }),
                    ]),
            ],
            $this->withGroup('INFORMAÇÕES DO PROJETO', [
                TextColumn::make('codigo', 'CÓDIGO')->align('center')->monospace(),
                TextColumn::make('projeto.sigla', 'SIGLA')->align('center'),
                TextColumn::make('projeto.nova_sigla', 'NOVA SIGLA')->align('center'),
                TextColumn::make('projeto.nome', 'UNIDADE'),
                TextColumn::make('projeto.marca', 'MARCA')->align('center'),
                TextColumn::make('pipe_land', 'PIPE / LAND')->align('center'),
                PillColumn::make('status', 'STATUS')
                    ->align('center')
                    ->options($statusOptions)
                    ->colors($statusColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('status', $statusOptions)),
            ]),
            $this->withGroup('GESTOR', [
                $makeTextInput('engenharia', 'ENGENHARIA'),
                $makeTextInput('comercial', 'COMERCIAL'),
                $makeTextInput('arquitetura', 'ARQUITETURA'),
                $makeDateInput('entrada_ponto', 'ENTRADA DO PONTO'),
                PillColumn::make('projeto.status_contrato', 'STATUS DO CONTRATO')
                    ->align('center')
                    ->options($statusContratoOptions)
                    ->colors($statusContratoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->chevron(false),
                $makeDateInput('data_assinatura_contrato', 'DATA DE ASSINATURA DO CONTRATO'),
            ]),
            $this->withGroup('TOTAL DE DIAS DE PROCESSO', [
                TextColumn::make('entrada_ponto_ate_inauguracao', 'ENTRADA DO PONTO ATÉ INAUGURAÇÃO')
                    ->align('center')
                    ->hiddenByDefault()
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->entrada_ponto_ate_inauguracao)),
                TextColumn::make('assinatura_ate_inauguracao', 'ASSINATURA ATÉ INAUGURAÇÃO')
                    ->align('center')
                    ->hiddenByDefault()
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->assinatura_ate_inauguracao)),
            ]),
            $this->withGroup('VISITA TÉCNICA', [
                PillColumn::make('status_visita', 'STATUS VISITA')
                    ->align('center')
                    ->options($etapaOptions)
                    ->colors($etapaColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('status_visita', $etapaOptions)),
            ]),
            $this->withGroup('PROJETO EXECUTIVO', [
                PillColumn::make('status_proj_exec', 'STATUS PROJ. EXECUTIVO')
                    ->align('center')
                    ->options($etapaOptions)
                    ->colors($etapaColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('status_proj_exec', $etapaOptions)),
            ]),
            $this->withGroup('POSSE', [
                $makeDateInput('status_data_posse', 'DATA DE POSSE'),
                PillColumn::make('relatorio_fotografico', 'RELATÓRIO FOTOGRÁFICO')
                    ->align('center')
                    ->options($relFotoOptions)
                    ->colors($relFotoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('relatorio_fotografico', $relFotoOptions)),
                $makeDateInput('data_envio_relatorio_fotografico', 'DATA ENVIO REL. FOTOGRÁFICO'),
                $makeDateInput('data_atualizacao_comentario', 'DATA ATUALIZAÇÃO COMENTÁRIO'),
                $makeTextInput('comentarios', 'COMENTÁRIOS'),
                PillColumn::make('termo_de_posse', 'TERMO DE POSSE')
                    ->align('center')
                    ->options($simNaoOptions)
                    ->colors($simNaoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('termo_de_posse', $simNaoOptions)),
            ]),
            $this->withGroup('EXECUÇÃO DE OBRAS', [
                $makeDateInput('inicio', 'INÍCIO'),
                $makeDateInput('inicio_real', 'INÍCIO REAL'),
                $makeDateInput('fim', 'FIM'),
                TextColumn::make('prazo_planejado', 'PRAZO PLANEJADO')
                    ->align('center')
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->prazo_planejado)),
                TextColumn::make('prazo_realizado', 'PRAZO REALIZADO')
                    ->align('center')
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->prazo_realizado)),
            ]),
            $this->withGroup('IMPLANTAÇÃO', [
                $makeDateInput('inicio_imp', 'INÍCIO IMPL.'),
                $makeDateInput('fim_imp', 'FIM IMPL.'),
                PillColumn::make('cronograma_implantacao', 'CRONOGRAMA DE IMPLANTAÇÃO')
                    ->align('center')
                    ->options($envioOptions)
                    ->colors($envioColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('cronograma_implantacao', $envioOptions)),
                $makeTextInput('observacao', 'OBSERVAÇÃO'),
                DateColumn::make('projeto.inauguracao', 'INAUGURAÇÃO'),
                TextColumn::make('imp_prazo_planej', 'PRAZO PLANEJADO (IMPL.)')
                    ->align('center')
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->imp_prazo_planej)),
                TextColumn::make('imp_prazo_realiz', 'PRAZO REALIZADO (IMPL.)')
                    ->align('center')
                    ->getStateUsing(fn (Obras $r) => $diasSuffix($r->imp_prazo_realiz)),
                TextColumn::make('mes', 'MÊS')
                    ->align('center')
                    ->getStateUsing(fn (Obras $r) => $r->mes ? $mesLabel($r->mes) : null),
                TextColumn::make('ano', 'ANO')->align('center'),
            ]),
            $this->withGroup('DADOS DO IMÓVEL', [
                TextColumn::make('projeto.tipo_imovel', 'TIPO DO IMÓVEL'),
                TextColumn::make('endereco', 'ENDEREÇO'),
                TextColumn::make('cidade', 'CIDADE'),
                TextColumn::make('uf', 'ESTADO')->align('center')->compact(),
                TextColumn::make('projeto.empreendimento', 'EMPREENDIMENTO'),
                PillColumn::make('projeto.locacao', 'LOCAÇÃO')
                    ->align('center')
                    ->options([
                        'Mono usuário' => 'Mono usuário',
                        'Multiusuário' => 'Multiusuário',
                    ])
                    ->colors($locacaoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL),
                TextColumn::make('projeto.contato_corretor', 'CONTATO DO CORRETOR / PP'),
            ]),
            $this->withGroup('% DE OBRA', [
                PillColumn::make('dias_para_inauguracao', 'DIAS PARA INAUGURAÇÃO')
                    ->align('center')
                    ->chevron(false)
                    ->colors($diasPrazoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->getStateUsing(fn (Obras $r) => $r->dias_para_inauguracao !== null
                        ? ((int) $r->dias_para_inauguracao).' dias'
                        : '-'),
                PillColumn::make('dias_obra_inicio_pmo', 'DIAS DE OBRA (INÍCIO PMO)')
                    ->align('center')
                    ->chevron(false)
                    ->colors($diasPrazoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->getStateUsing(fn (Obras $r) => $r->dias_obra_inicio_pmo !== null
                        ? ((int) $r->dias_obra_inicio_pmo).' dias'
                        : '-'),
                $makeNumberInput('percentual_obra', '% DE OBRA PREVISTO', 0, 100),
                $makeNumberInput('percentual_obra_executado', '% DE OBRA EXECUTADO', 0, 100),
                $makeNumberInput('desvio', 'DESVIO'),
            ]),
            $this->withGroup('ACOMPANHAMENTO DE OBRA', [
                $makeTextInput('itens_criticos', 'ITENS CRÍTICOS'),
                $makeTextInput('descricao_itens_criticos', 'DESCRIÇÃO DOS ITENS CRÍTICOS'),
            ]),
            $this->withGroup('CRONOGRAMA VISI', [
                PillColumn::make('cronograma_visi', 'CRONOGRAMA VISI')
                    ->align('center')
                    ->options($envioOptions)
                    ->colors($envioColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('cronograma_visi', $envioOptions)),
                PillColumn::make('camera_unidade', 'CÂMERA NA UNIDADE')
                    ->align('center')
                    ->options($simNaoOptions)
                    ->colors($simNaoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('camera_unidade', $simNaoOptions)),
                $makeTextInput('ponto_atencao', 'PONTO DE ATENÇÃO'),
            ]),
            $this->withGroup('CONTRATAÇÕES', [
                $makeTextInput('civil', 'Civil'),
                $makeTextInput('hidraulica', 'Hidráulica')->hiddenByDefault(),
                $makeTextInput('eletrica', 'Elétrica')->hiddenByDefault(),
                $makeTextInput('incendio', 'Incêndio')->hiddenByDefault(),
                $makeTextInput('instalacao_ar_condicionado', 'Instalação Ar Condicionado')->hiddenByDefault(),
                $makeTextInput('maquinas_ar_condicionado', 'Máquinas Ar Condicionado')->hiddenByDefault(),
                PillColumn::make('homologados_em_atraso', 'Homologados em Atraso')
                    ->align('center')
                    ->hiddenByDefault()
                    ->options($simNaoOptions)
                    ->colors([
                        'sim' => PillColumn::COLOR_DANGER,
                        'nao' => PillColumn::COLOR_SUCCESS,
                    ])
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('homologados_em_atraso', $simNaoOptions)),
            ]),
            $this->withGroup('CONTAS DE CONSUMO', [
                PillColumn::make('energia', 'ENERGIA')
                    ->align('center')
                    ->options($energiaOptions)
                    ->colors($consumoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('energia', $energiaOptions)),
                $makeDateInput('previsao_ligacao_energia', 'PREVISÃO DE LIGAÇÃO DE ENERGIA'),
                $makeTextInput('gerador_contratual', 'GERADOR CONTRATUAL'),
                PillColumn::make('agua', 'ÁGUA')
                    ->align('center')
                    ->options($aguaOptions)
                    ->colors($consumoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('agua', $aguaOptions)),
                PillColumn::make('gas', 'GÁS')
                    ->align('center')
                    ->options($gasOptions)
                    ->colors($consumoColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('gas', $gasOptions)),
                $makeTextInput('comentario', 'COMENTÁRIO (CONSUMO)'),
            ]),
            $this->withGroup('PÓS OBRA', [
                PillColumn::make('email_solicitacao_cl', 'EMAIL SOLICITAÇÃO DE CL')
                    ->align('center')
                    ->hiddenByDefault()
                    ->options($envioOptions)
                    ->colors($envioColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('email_solicitacao_cl', $envioOptions)),
                PillColumn::make('envio_qrcod', 'ENVIO DE QRCODE')
                    ->align('center')
                    ->hiddenByDefault()
                    ->options($envioOptions)
                    ->colors($envioColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('envio_qrcod', $envioOptions)),
                PillColumn::make('checklist_manutencao', 'CHECKLIST DE MANUTENÇÃO (TRILOGO)')
                    ->align('center')
                    ->hiddenByDefault()
                    ->options($checklistOptions)
                    ->colors($checklistColors)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->authorizeEditUsing($authorizeUpdate)
                    ->onEditUsing($editSelect('checklist_manutencao', $checklistOptions)),
                $makeDateInput('data_check_list', 'DATA DO CHECK LIST')->hiddenByDefault(),
                $makeDateInput('inicio_prev_pendencias', 'INÍCIO PREVISTO PENDÊNCIAS')->hiddenByDefault(),
                $makeDateInput('termino_prev_pendencias', 'TÉRMINO PREVISTO PENDÊNCIAS')->hiddenByDefault(),
                $makeTextInput('elevador', 'ELEVADOR')->hiddenByDefault(),
                $makeTextInput('comentarios_adicionais', 'COMENTÁRIOS (PÓS OBRA)')->hiddenByDefault(),
                $makeTextInput('gestor_pos_obra', 'GESTOR PÓS OBRA')->hiddenByDefault(),
            ]),
            $this->withHidden($this->withGroup('PONTOS DE ATENÇÃO', $this->buildPontosAtencaoColumns())),
            $this->withGroup('AUDITORIA', [
                TextColumn::make('created_at', 'CRIADO EM')
                    ->align('center')
                    ->muted()
                    ->hiddenByDefault()
                    ->getStateUsing(fn (Obras $r) => $r->created_at
                        ? Carbon::parse($r->created_at)->format('d/m/Y H:i')
                        : null),
            ]),
        );

        // Habilita sort em todas as colunas com coluna DB correspondente (exceto actions/PA).
        foreach ($columns as $col) {
            if ($col instanceof ActionsColumn) {
                continue;
            }
            if (str_starts_with($col->key, 'ponto_atencao_')) {
                continue;
            }
            $col->sortable();
        }

        return $columns;
    }

    /**
     * @param  array<int, Column>  $cols
     * @return array<int, Column>
     */
    private function withGroup(string $label, array $cols): array
    {
        foreach ($cols as $col) {
            $col->group($label);
        }

        return $cols;
    }

    /**
     * @param  array<int, Column>  $cols
     * @return array<int, Column>
     */
    private function withHidden(array $cols): array
    {
        foreach ($cols as $col) {
            $col->hiddenByDefault();
        }

        return $cols;
    }

    private function formatTableDateInput(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTableDateInput(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new \InvalidArgumentException('Invalid date.');
    }

    /**
     * @return array<int, Column>
     */
    protected function buildPontosAtencaoColumns(): array
    {
        $definicoes = $this->getPontosAtencaoDefinitions();

        if ($definicoes->isEmpty()) {
            return [];
        }

        $authorize = fn (Obras $r, $u): bool => $u?->can('update', $r) ?? false;
        $valorAtual = fn (Obras $r, string $nome) => $r->colunasPersonalizadas
            ?->first(fn ($item) => (string) $item->nome === (string) $nome)
            ?->valor;

        $cols = [];

        foreach ($definicoes as $nome => $definicao) {
            $tipo = (string) ($definicao->tipo ?? 'texto');
            $key = 'ponto_atencao_'.md5((string) $nome);
            $label = (string) $nome;

            if ($tipo === 'select') {
                $opcoes = collect($definicao->opcoes ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter(fn ($item) => $item !== '')
                    ->values()
                    ->all();
                $optionsMap = collect($opcoes)->mapWithKeys(fn ($v) => [$v => $v])->all();

                $cols[] = PillColumn::make($key, $label)
                    ->align('center')
                    ->options($optionsMap)
                    ->defaultColor(PillColumn::COLOR_NEUTRAL)
                    ->getStateUsing(fn (Obras $r) => $valorAtual($r, $nome))
                    ->authorizeEditUsing($authorize)
                    ->onEditUsing(function (Obras $record, string $newValue) use ($nome, $opcoes): void {
                        if (! in_array($newValue, $opcoes, true)) {
                            return;
                        }
                        $this->upsertColunaPersonalizada($record, $nome, $newValue);
                    });

                continue;
            }

            if ($tipo === 'data') {
                $cols[] = TextInputColumn::make($key, $label)
                    ->align('center')
                    ->type('date')
                    ->getStateUsing(function (Obras $r) use ($nome) {
                        $v = $r->colunasPersonalizadas
                            ?->first(fn ($i) => (string) $i->nome === (string) $nome)
                            ?->valor;

                        return $this->formatTableDateInput($v);
                    })
                    ->authorizeEditUsing($authorize)
                    ->onEditUsing(function (Obras $record, string $newValue) use ($nome): void {
                        try {
                            $this->upsertColunaPersonalizada($record, $nome, $this->normalizeTableDateInput($newValue));
                        } catch (\Throwable) {
                            Notification::make()->title('Data inválida')->danger()->send();
                        }
                    });

                continue;
            }

            if ($tipo === 'numero') {
                $cols[] = TextInputColumn::make($key, $label)
                    ->align('center')
                    ->type('number')
                    ->step('any')
                    ->getStateUsing(fn (Obras $r) => $valorAtual($r, $nome))
                    ->authorizeEditUsing($authorize)
                    ->onEditUsing(function (Obras $record, string $newValue) use ($nome): void {
                        $val = trim($newValue);
                        if ($val === '') {
                            $this->upsertColunaPersonalizada($record, $nome, null);

                            return;
                        }
                        if (! is_numeric($val)) {
                            Notification::make()->title('Valor numérico inválido')->danger()->send();

                            return;
                        }
                        $this->upsertColunaPersonalizada($record, $nome, $val);
                    });

                continue;
            }

            $cols[] = TextInputColumn::make($key, $label)
                ->getStateUsing(fn (Obras $r) => $valorAtual($r, $nome))
                ->authorizeEditUsing($authorize)
                ->onEditUsing(function (Obras $record, string $newValue) use ($nome): void {
                    $val = trim($newValue);
                    $this->upsertColunaPersonalizada($record, $nome, $val === '' ? null : $val);
                });
        }

        return $cols;
    }

    protected function upsertColunaPersonalizada(Obras $obra, string $nome, ?string $valor): void
    {
        $valor = $valor !== null ? substr($valor, 0, 255) : null;

        ColunaPersonalizada::updateOrCreate(
            [
                'obra_id' => $obra->id,
                'nome' => $nome,
            ],
            [
                'projeto_id' => $obra->projeto_id,
                'valor' => $valor,
                'usuario_id' => auth()->id(),
            ],
        );
    }

    protected function getPontosAtencaoDefinitions(): Collection
    {
        if ($this->cachedPontosAtencaoDefs !== null) {
            return $this->cachedPontosAtencaoDefs;
        }

        return $this->cachedPontosAtencaoDefs = Cache::remember('obras_pontos_atencao_definitions', 600, function (): Collection {
            return ColunaPersonalizada::query()
                ->select('nome', 'tipo', 'opcoes')
                ->whereNotNull('nome')
                ->orderBy('nome')
                ->get()
                ->groupBy('nome')
                ->map(fn ($items) => $items->first());
        });
    }

    /**
     * @return array<int, Filter>
     */
    protected function buildFilters(): array
    {
        $opts = fn (string $col) => ObrasColumnFilters::getSelectOptions($col);

        $projetoSelect = fn (string $key, string $label, string $column, string $group = 'DADOS DO IMÓVEL') => SelectFilter::make($key, $label)
            ->options(fn () => $opts($key))
            ->multiple()
            ->placeholder($label)
            ->secondary()
            ->group($group)
            ->applyUsing(function (Builder $q, mixed $value) use ($column): Builder {
                $vals = array_values(array_filter((array) $value, fn ($v) => $v !== null && $v !== ''));
                if ($vals === []) {
                    return $q;
                }

                return $q->whereHas('projeto', fn (Builder $sub) => $sub->whereIn($column, $vals));
            });

        $obraSelect = fn (string $key, string $label, string $group) => SelectFilter::make($key, $label)
            ->options(fn () => $opts($key))
            ->multiple()
            ->placeholder($label)
            ->secondary()
            ->group($group);

        $pontoAtencaoSelect = function (string $nome, array $opcoes): SelectFilter {
            $options = collect($opcoes)
                ->map(fn ($item) => trim((string) $item))
                ->filter(fn ($item) => $item !== '')
                ->values()
                ->mapWithKeys(fn ($item) => [$item => $item])
                ->all();

            return SelectFilter::make('ponto_atencao_'.Str::slug($nome, '_'), $nome)
                ->options($options)
                ->multiple()
                ->placeholder($nome)
                ->secondary()
                ->group('PONTOS DE ATENÇÃO')
                ->applyUsing(function (Builder $q, mixed $value) use ($nome): Builder {
                    $vals = array_values(array_filter((array) $value, fn ($v) => $v !== null && $v !== ''));
                    if ($vals === []) {
                        return $q;
                    }

                    return $q->whereHas(
                        'colunasPersonalizadas',
                        fn (Builder $sub) => $sub->where('nome', $nome)->whereIn('valor', $vals),
                    );
                });
        };

        $pontoAtencaoDateRange = fn (string $nome): DateRangeFilter => DateRangeFilter::make(
            'ponto_atencao_'.Str::slug($nome, '_'),
            $nome,
        )
            ->secondary()
            ->group('PONTOS DE ATENÇÃO')
            ->queryUsing(function (Builder $q, ?string $from, ?string $until) use ($nome): Builder {
                if ($from === null && $until === null) {
                    return $q;
                }

                return $q->whereHas('colunasPersonalizadas', function (Builder $sub) use ($nome, $from, $until): void {
                    $sub->where('nome', $nome)
                        ->when($from, fn (Builder $s, $d) => $s->whereDate('valor', '>=', $d))
                        ->when($until, fn (Builder $s, $d) => $s->whereDate('valor', '<=', $d));
                });
            });

        $pontosAtencaoFilters = [];
        foreach ($this->getPontosAtencaoDefinitions() as $nome => $definicao) {
            $tipo = (string) ($definicao->tipo ?? 'texto');

            if ($tipo === 'select') {
                $pontosAtencaoFilters[] = $pontoAtencaoSelect(
                    (string) $nome,
                    (array) ($definicao->opcoes ?? []),
                );
            } elseif ($tipo === 'data') {
                $pontosAtencaoFilters[] = $pontoAtencaoDateRange((string) $nome);
            }
        }

        return [
            // PRIMARY (sempre visíveis)
            SelectFilter::make('status', 'Status')
                ->options(fn () => $opts('status'))
                ->multiple()
                ->placeholder('Todos os Status')
                ->default(['Em processo', 'Inaugurada', 'Obras'])
                ->group('INFORMAÇÕES DO PROJETO'),

            SelectFilter::make('uf', 'UF')
                ->options(fn () => $opts('uf'))
                ->multiple()
                ->placeholder('Todos os Estados')
                ->group('DADOS DO IMÓVEL'),

            PeriodFilter::make('inicio', 'Período')
                ->column('inicio')
                ->group('EXECUÇÃO DE OBRAS'),

            // SECONDARY — selects sobre campos de obras
            $obraSelect('pipe_land', 'PIPE / LAND', 'INFORMAÇÕES DO PROJETO'),
            $obraSelect('status_visita', 'Status da Visita', 'VISITA TÉCNICA'),
            $obraSelect('status_proj_exec', 'Status do Projeto Executivo', 'PROJETO EXECUTIVO'),
            $obraSelect('relatorio_fotografico', 'Relatório Fotográfico', 'POSSE'),
            $obraSelect('termo_de_posse', 'Termo de Posse', 'POSSE'),
            $obraSelect('cronograma_implantacao', 'Cronograma de Implantação', 'IMPLANTAÇÃO'),
            $obraSelect('mes', 'Mês', 'IMPLANTAÇÃO'),
            $obraSelect('ano', 'Ano', 'IMPLANTAÇÃO'),
            $obraSelect('cronograma_visi', 'Cronograma VISI', 'CRONOGRAMA VISI'),
            $obraSelect('camera_unidade', 'Câmera na Unidade', 'CRONOGRAMA VISI'),
            $obraSelect('homologados_em_atraso', 'Homologados em Atraso', 'CONTRATAÇÕES'),
            $obraSelect('energia', 'Energia', 'CONTAS DE CONSUMO'),
            $obraSelect('agua', 'Água', 'CONTAS DE CONSUMO'),
            $obraSelect('gas', 'Gás', 'CONTAS DE CONSUMO'),
            $obraSelect('email_solicitacao_cl', 'Email Solicitação CL', 'PÓS OBRA'),
            $obraSelect('envio_qrcod', 'Envio de QRCode', 'PÓS OBRA'),
            $obraSelect('checklist_manutencao', 'Checklist de Manutenção', 'PÓS OBRA'),

            // SECONDARY — selects sobre o relacionamento projeto
            $projetoSelect('marca', 'Marca', 'marca', 'INFORMAÇÕES DO PROJETO'),
            $projetoSelect('tipo_imovel', 'Tipo do Imóvel', 'tipo_imovel'),
            $projetoSelect('locacao', 'Locação', 'locacao'),

            // SECONDARY — date ranges nas 15 colunas de data
            ...$this->buildDateRangeFilters(),

            // SECONDARY — Pontos de Atenção (selects e date ranges de colunas personalizadas)
            ...$pontosAtencaoFilters,
        ];
    }

    /**
     * @return array<int, DateRangeFilter>
     */
    protected function buildDateRangeFilters(): array
    {
        $dates = [
            'entrada_ponto' => ['Entrada do Ponto', 'GESTOR'],
            'data_assinatura_contrato' => ['Assinatura do Contrato', 'GESTOR'],
            'status_data_posse' => ['Data de Posse', 'POSSE'],
            'data_envio_relatorio_fotografico' => ['Envio Relatório Fotográfico', 'POSSE'],
            'data_atualizacao_comentario' => ['Atualização do Comentário', 'POSSE'],
            'inicio_real' => ['Início Real', 'EXECUÇÃO DE OBRAS'],
            'fim' => ['Fim', 'EXECUÇÃO DE OBRAS'],
            'inicio_imp' => ['Início Implantação', 'IMPLANTAÇÃO'],
            'fim_imp' => ['Fim Implantação', 'IMPLANTAÇÃO'],
            'previsao_ligacao_energia' => ['Previsão Ligação Energia', 'CONTAS DE CONSUMO'],
            'data_check_list' => ['Data do Check List', 'PÓS OBRA'],
            'inicio_prev_pendencias' => ['Início Prev. Pendências', 'PÓS OBRA'],
            'termino_prev_pendencias' => ['Término Prev. Pendências', 'PÓS OBRA'],
            'created_at' => ['Criado em', 'AUDITORIA'],
        ];

        return collect($dates)
            ->map(fn (array $config, string $col) => DateRangeFilter::make($col, $config[0])
                ->column($col)
                ->secondary()
                ->group($config[1]))
            ->values()
            ->all();
    }

    public function getColumnPresets(): array
    {
        $presets = [
            ['id' => 'preset_visao_geral', 'label' => 'Visão Geral', 'hidden' => []],
        ];

        $globalPresets = Cache::remember(
            'table_presets_global_'.$this->getTableExcelPage()->getTableKey(),
            600,
            fn () => TablePreset::where('table_key', $this->getTableExcelPage()->getTableKey())
                ->where('is_global', true)
                ->get()
                ->map(fn ($preset) => [
                    'id' => 'preset_global_'.$preset->id,
                    'label' => $preset->name,
                    'hidden' => $preset->hidden_columns ?? [],
                    'created_by' => $preset->created_by,
                    'is_global' => true,
                ])
                ->toArray()
        );

        return array_merge($presets, $globalPresets);
    }

    public function applyColumnTab(array $hidden): void
    {
        $this->salvarColunasVisiveis($hidden);
    }

    public function savePresetTab(string $label, array $hidden, bool $assignToAll = false): ?array
    {
        \Log::info('savePresetTab chamado', [
            'label' => $label,
            'assignToAll' => $assignToAll,
            'hiddenCount' => count($hidden),
        ]);

        $result = null;

        if ($assignToAll) {
            try {
                $preset = TablePreset::updateOrCreate(
                    [
                        'table_key' => $this->getTableExcelPage()->getTableKey(),
                        'name' => $label,
                        'is_global' => true,
                    ],
                    [
                        'hidden_columns' => $hidden,
                        'created_by' => auth()->id(),
                    ]
                );

                \Log::info('Preset salvo', ['preset_id' => $preset->id]);

                Cache::forget('table_presets_global_'.$this->getTableExcelPage()->getTableKey());

                $result = ['id' => $preset->id, 'label' => $label, 'hidden' => $hidden];

                Notification::make()
                    ->title('Vista global criada com sucesso')
                    ->body('A vista "'.$label.'" agora está disponível para todos os usuários')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                \Log::error('Erro savePresetTab', ['error' => $e->getMessage()]);
                Notification::make()
                    ->title('Erro ao criar vista global')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
        $this->dispatch('save-preset-tab', label: $label, hidden: $hidden);

        return $result;
    }

    public function deleteGlobalPreset(int $presetId): void
    {
        $preset = TablePreset::find($presetId);

        if (! $preset || ! $preset->is_global || $preset->table_key !== $this->getTableExcelPage()->getTableKey()) {
            return;
        }

        if ($preset->created_by !== auth()->id()) {
            Notification::make()
                ->title('Sem permissão')
                ->body('Apenas o criador da vista pode removê-la.')
                ->danger()
                ->send();

            return;
        }

        $preset->delete();
        Cache::forget('table_presets_global_'.$this->getTableExcelPage()->getTableKey());

        Notification::make()
            ->title('Vista global removida')
            ->success()
            ->send();
    }
}
