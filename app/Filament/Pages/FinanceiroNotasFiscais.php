<?php

namespace App\Filament\Pages;

use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Tables\TableExcel\Actions\ManageColumnsAction;
use App\Filament\Tables\TableExcel\Actions\ManageFiltersAction;
use App\Filament\Tables\TableExcel\Page\Columns\ActionsColumn;
use App\Filament\Tables\TableExcel\Page\Columns\Column;
use App\Filament\Tables\TableExcel\Page\Columns\DateColumn;
use App\Filament\Tables\TableExcel\Page\Columns\PillColumn;
use App\Filament\Tables\TableExcel\Page\Columns\RowAction;
use App\Filament\Tables\TableExcel\Page\Columns\SelectColumn;
use App\Filament\Tables\TableExcel\Page\Columns\TextColumn;
use App\Filament\Tables\TableExcel\Page\Concerns\HasTableExcelPage;
use App\Filament\Tables\TableExcel\Page\Filters\DateRangeFilter;
use App\Filament\Tables\TableExcel\Page\Filters\SelectFilter;
use App\Filament\Tables\TableExcel\Page\TableExcelPage;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalNota;
use App\Models\ControleNotaFiscalNotaBaixa;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\User;
use App\Services\FinanceiroNotasFiscaisPdfService;
use App\Services\FinanceiroNotasFiscaisZipService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FinanceiroNotasFiscais extends Page
{
    use HasPageShield;
    use HasTableExcelPage;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Financeiro';

    protected static ?string $navigationLabel = 'Notas Fiscais';

    protected static ?string $title = 'Notas Fiscais';

    protected static ?string $slug = 'financeiro-notas-fiscais';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.financeiro-notas-fiscais';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('View:FinanceiroNotasFiscais');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        // Default inicial "Pendente" só na primeira carga (sem URL state). Definir
        // via SelectFilter::default() faria o trait reaplicar ['0'] toda vez que o
        // usuário limpasse o filtro no modal — aqui o estado vazio é preservado.
        // if (! array_key_exists('baixado', $this->filtros)) {
        //    $this->filtros['baixado'] = ['0'];
        // }
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function getTableExcelToolbarActions(): array
    {
        return [
            'configurarFiltrosAction',
            'gerenciarColunasAction',
            'exportarPdfAction',
        ];
    }

    public function configurarFiltrosAction(): Action
    {
        return ManageFiltersAction::make(
            $this->getTableExcelPage()->getFilters(),
            'configurarFiltros',
        );
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
            'financeiro.notas_fiscais',
            $columnsOptions,
            'gerenciarColunas',
        );
    }

    public function exportarPdfAction(): Action
    {
        return Action::make('exportarPdf')
            ->label('Exportar Relatório em PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color(Color::hex('#DC2626'))
            ->action(fn () => $this->exportarPdf());
    }

    public function exportarPdf(): ?StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $config = $this->getTableExcelPage();

        $query = $config->buildQuery();
        $this->applySearch($query, $config);
        $this->applyFilters($query, $config);

        try {
            $service = app(FinanceiroNotasFiscaisPdfService::class);
            $pdf = $service->gerar($query, $this->resumoFiltrosAtivos());
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Erro ao gerar PDF')
                ->body('Não foi possível gerar o relatório. Tente novamente ou refine os filtros.')
                ->danger()
                ->send();

            return null;
        }

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'notas-fiscais-'.now()->format('Ymd-His').'.pdf',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function resumoFiltrosAtivos(): array
    {
        $config = $this->getTableExcelPage();
        $resumo = [];

        $busca = trim((string) $this->busca);
        if ($busca !== '') {
            $resumo['Busca'] = $busca;
        }

        foreach ($config->getFilters() as $filter) {
            $value = $this->filtros[$filter->key] ?? null;

            if ($filter->isEmptyValue($value)) {
                continue;
            }

            $texto = $this->descreverValorFiltro($filter, $value);

            if ($texto !== '') {
                $resumo[$filter->label] = $texto;
            }
        }

        return $resumo;
    }

    protected function descreverValorFiltro(mixed $filter, mixed $value): string
    {
        if ($filter instanceof SelectFilter) {
            $options = $filter->resolveOptions();
            $values = array_values(array_filter((array) $value, fn ($v): bool => $v !== null && $v !== ''));
            $labels = array_map(fn ($v): string => (string) ($options[$v] ?? $v), $values);

            return implode(', ', $labels);
        }

        if ($filter instanceof DateRangeFilter && is_array($value)) {
            $from = trim((string) ($value['from'] ?? ''));
            $until = trim((string) ($value['until'] ?? ''));
            $fmt = static fn (string $d): string => ($t = strtotime($d)) ? date('d/m/Y', $t) : $d;

            if ($from !== '' && $until !== '') {
                return $fmt($from).' até '.$fmt($until);
            }

            if ($from !== '') {
                return 'A partir de '.$fmt($from);
            }

            if ($until !== '') {
                return 'Até '.$fmt($until);
            }
        }

        return '';
    }

    protected function tableExcelPage(): TableExcelPage
    {
        return TableExcelPage::make()
            ->query(function () {
                $query = ControleNotaFiscalNota::query()
                    ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
                    ->with([
                        'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra:id,codigo,unidade,projeto_id',
                        'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto:id,resp_eng',
                        'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
                        'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra:id,codigo,unidade,projeto_id',
                        'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto:id,resp_eng',
                        'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
                        'baixadoPor:id,name',
                    ]);

                $this->aplicarOrdenacaoPorPendentes($query);

                return $query
                    ->orderBy('baixado', 'asc')
                    ->orderByDesc('updated_at');
            })
            ->columns($this->buildColumns())
            ->filters($this->buildFilters())
            ->search('busca', 'Buscar código, unidade ou gestor...', ['__custom__'])
            ->perPage(50)
            ->recordKey('id')
            ->tableKey('financeiro.notas_fiscais')
            ->stickyHeader()
            ->stickyActions()
            ->dense()
            ->freezable()
            ->resizable()
            ->emptyState(
                'Nenhuma nota fiscal encontrada',
                'Ajuste a busca ou os filtros para ver resultados.',
            );
    }

    /**
     * @return array<int, Column>
     */
    protected function buildColumns(): array
    {
        $tipoOptions = [
            ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA => 'Mão de obra',
            ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL => 'Material',
            ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE => 'Transporte',
        ];
        $tipoColors = [
            ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA => PillColumn::COLOR_INFO,
            ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL => PillColumn::COLOR_NEUTRAL,
            ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE => PillColumn::COLOR_WARNING,
        ];

        $formatBrl = fn (mixed $value): string => 'R$ '.number_format((float) ($value ?? 0), 2, ',', '.');

        return [
            SelectColumn::make('selecao', '')
                ->align('center')
                ->toggleable(false)
                ->reorderable(false),

            TextColumn::make('numero_nf', 'Nº NF')
                ->align('center')
                ->sortable(),

            TextColumn::make('empresa', 'Razão Social')
                ->sortable(),

            TextColumn::make('cnpj_fornecedor', 'CNPJ')
                ->align('center')
                ->sortable(),

            PillColumn::make('tipo_medicao', 'Tipo')
                ->align('center')
                ->options($tipoOptions)
                ->colors($tipoColors)
                ->defaultColor(PillColumn::COLOR_NEUTRAL)
                ->chevron(false)
                ->sortable(),

            TextColumn::make('valor_acumulado_medido_nf', 'Valor')
                ->align('end')
                ->getStateUsing(fn (ControleNotaFiscalNota $record) => $formatBrl($record->valor_acumulado_medido_nf))
                ->sortable(),

            DateColumn::make('created_at', 'Postagem')
                ->align('center')
                ->sortable(),

            PillColumn::make('baixado', 'Baixa')
                ->align('center')
                ->options([
                    '1' => 'Baixado',
                    '0' => 'Pendente',
                ])
                ->colors([
                    '1' => PillColumn::COLOR_SUCCESS,
                    '0' => PillColumn::COLOR_NEUTRAL,
                ])
                ->defaultColor(PillColumn::COLOR_NEUTRAL)
                ->chevron(false)
                ->getStateUsing(fn (ControleNotaFiscalNota $record): string => $record->baixado ? '1' : '0')
                ->sortable(),

            ActionsColumn::make('anexos', 'Pré-visualização')
                ->align('center')
                ->actions([
                    RowAction::make('anexo_nf', 'Nota fiscal')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (ControleNotaFiscalNota $record): ?string => ControleNotaFiscalNota::getFileUrl($record->arquivo_path)),

                    RowAction::make('anexo_boleto', 'Boleto')
                        ->icon('heroicon-o-banknotes')
                        ->url(fn (ControleNotaFiscalNota $record): ?string => ControleNotaFiscalNota::getFileUrl($record->boleto_path)),
                ]),

            TextColumn::make('baixado_por', 'Baixado por')
                ->align('center')
                ->getStateUsing(fn (ControleNotaFiscalNota $record): ?string => $record->baixadoPor?->name),

            TextColumn::make('baixado_em', 'Baixado em')
                ->align('center')
                ->getStateUsing(fn (ControleNotaFiscalNota $record): ?string => $record->baixado_em?->format('d/m/Y H:i'))
                ->sortable(),

            ActionsColumn::make('historico_baixas_col', 'Histórico de baixas')
                ->align('center')
                ->actions([
                    RowAction::make('historico_baixas', 'Histórico de baixas')
                        ->icon('heroicon-o-clock')
                        ->mountsAction('historicoBaixas'),
                ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function buildFilters(): array
    {
        return [
            SelectFilter::make('baixado', 'Baixa')
                ->options([
                    '0' => 'Pendente',
                    '1' => 'Baixado',
                ])
                ->multiple()
                ->placeholder('Pendentes e baixadas')
                ->group('Nota fiscal')
                ->applyUsing(function (Builder $q, mixed $value): Builder {
                    $vals = array_values(array_filter((array) $value, fn ($v): bool => $v !== null && $v !== ''));

                    if ($vals === [] || count($vals) === 2) {
                        return $q;
                    }

                    return $q->where('baixado', (string) $vals[0] === '1');
                }),

            SelectFilter::make('tipo_medicao', 'Tipo')
                ->options([
                    ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA => 'Mão de obra',
                    ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL => 'Material',
                    ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE => 'Transporte',
                ])
                ->multiple()
                ->placeholder('Todos os tipos')
                ->group('Nota fiscal'),

            SelectFilter::make('cnpj_fornecedor', 'Fornecedor')
                ->options(fn (): array => ControleNotaFiscalNota::query()
                    ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
                    ->whereNotNull('cnpj_fornecedor')
                    ->where('cnpj_fornecedor', '!=', '')
                    ->orderBy('empresa')
                    ->get(['empresa', 'cnpj_fornecedor'])
                    ->unique('cnpj_fornecedor')
                    ->mapWithKeys(fn (ControleNotaFiscalNota $n): array => [
                        (string) $n->cnpj_fornecedor => trim(($n->empresa ?: 'Sem nome').' ('.$n->cnpj_fornecedor.')'),
                    ])
                    ->all())
                ->multiple()
                ->placeholder('Todos os fornecedores')
                ->secondary()
                ->group('Nota fiscal'),

            DateRangeFilter::make('emissao', 'Postagem')
                ->column('created_at')
                ->secondary()
                ->group('Datas'),

            SelectFilter::make('unidade_obra', 'Unidade')
                ->options(fn (): array => Obras::query()
                    ->whereIn('id', $this->obrasComNotasAprovadasIdsQuery())
                    ->whereNotNull('unidade')
                    ->where('unidade', '!=', '')
                    ->orderBy('unidade')
                    ->pluck('unidade', 'unidade')
                    ->all())
                ->multiple()
                ->placeholder('Todas as unidades')
                ->secondary()
                ->group('Obra')
                ->applyUsing(fn (Builder $q, mixed $value): Builder => $this->applyObraFilter($q, $value, 'unidade')),

            SelectFilter::make('gestor', 'Gestor')
                ->options(fn (): array => User::query()
                    ->whereIn('id', Projeto::query()
                        ->select('resp_eng')
                        ->whereNotNull('resp_eng')
                        ->whereIn('id', Obras::query()
                            ->select('projeto_id')
                            ->whereNotNull('projeto_id')
                            ->whereIn('id', $this->obrasComNotasAprovadasIdsQuery())))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->multiple()
                ->placeholder('Todos os gestores')
                ->secondary()
                ->group('Obra')
                ->applyUsing(function (Builder $q, mixed $value): Builder {
                    $vals = array_values(array_filter((array) $value, fn ($v): bool => $v !== null && $v !== ''));
                    if ($vals === []) {
                        return $q;
                    }

                    return $q->where(function (Builder $sub) use ($vals): void {
                        $sub->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto', fn (Builder $projetoQuery): Builder => $projetoQuery->whereIn('resp_eng', $vals))
                            ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto', fn (Builder $projetoQuery): Builder => $projetoQuery->whereIn('resp_eng', $vals));
                    });
                }),
        ];
    }

    /**
     * Ordena os registros de modo que as unidades com mais notas pendentes
     * apareçam primeiro. A unidade de cada nota é resolvida pelo subquery
     * correlacionado em {@see unidadeOrderSubquery()}.
     */
    protected function aplicarOrdenacaoPorPendentes(Builder $query): void
    {
        $unidades = $this->unidadesOrdenadasPorPendentes();
        $unidadeSub = $this->unidadeOrderSubquery();

        if ($unidades === []) {
            $query->orderBy($unidadeSub, 'asc');

            return;
        }

        $whens = [];
        foreach (array_keys($unidades) as $i) {
            $whens[] = 'WHEN ? THEN '.($i + 1);
        }

        $caseSql = 'CASE ('.$unidadeSub->toSql().') '
            .implode(' ', $whens)
            .' ELSE '.(count($unidades) + 1).' END ASC';

        $bindings = array_merge($unidadeSub->getBindings(), $unidades);

        $query->orderByRaw($caseSql, $bindings);
    }

    /**
     * Lista de unidades (com notas aprovadas) ordenadas pela quantidade de
     * notas pendentes em ordem decrescente; empate desempata pelo nome da
     * unidade. Unidades sem notas pendentes ficam no final, mas mantêm ordem
     * alfabética entre si.
     *
     * @return array<int, string>
     */
    protected function unidadesOrdenadasPorPendentes(): array
    {
        $rows = DB::table('controle_nota_fiscal_notas as n')
            ->leftJoin('autorizacao_servicos as as_doc', 'as_doc.id', '=', 'n.autorizacao_servico_id')
            ->leftJoin('controle_nota_fiscal_items as i', 'i.id', '=', 'as_doc.controle_nota_fiscal_item_id')
            ->leftJoin('autorizacao_servico_adicionais as asa_doc', 'asa_doc.id', '=', 'n.autorizacao_servico_adicional_id')
            ->leftJoin('controle_nota_fiscal_auxiliares as a', 'a.id', '=', 'asa_doc.controle_nota_fiscal_auxiliar_id')
            ->leftJoin('controle_nota_fiscals as ci', 'ci.id', '=', 'i.controle_nota_fiscal_id')
            ->leftJoin('controle_nota_fiscals as ca', 'ca.id', '=', 'a.controle_nota_fiscal_id')
            ->leftJoin('obras as oi', 'oi.id', '=', 'ci.obra_id')
            ->leftJoin('obras as oa', 'oa.id', '=', 'ca.obra_id')
            ->where('n.status', StatusControleNotaFiscalNota::APROVADO->value)
            ->selectRaw('COALESCE(oi.unidade, oa.unidade) AS unidade')
            ->selectRaw('SUM(CASE WHEN n.baixado = 0 THEN 1 ELSE 0 END) AS pendentes')
            ->groupBy(DB::raw('COALESCE(oi.unidade, oa.unidade)'))
            ->orderByDesc('pendentes')
            ->orderBy('unidade')
            ->get();

        return $rows
            ->filter(fn ($r): bool => $r->unidade !== null && $r->unidade !== '')
            ->pluck('unidade')
            ->map(fn ($u): string => (string) $u)
            ->all();
    }

    protected function unidadeOrderSubquery(): Builder
    {
        return Obras::query()
            ->select('obras.unidade')
            ->whereIn('obras.id', function ($subObra): void {
                $subObra->select('controle_nota_fiscals.obra_id')
                    ->from('controle_nota_fiscals')
                    ->where(function ($subControle): void {
                        $subControle->whereIn('controle_nota_fiscals.id', function ($subItem): void {
                            $subItem->select('controle_nota_fiscal_items.controle_nota_fiscal_id')
                                ->from('controle_nota_fiscal_items')
                                ->join('autorizacao_servicos', 'autorizacao_servicos.controle_nota_fiscal_item_id', '=', 'controle_nota_fiscal_items.id')
                                ->whereColumn('autorizacao_servicos.id', 'controle_nota_fiscal_notas.autorizacao_servico_id');
                        })->orWhereIn('controle_nota_fiscals.id', function ($subAuxiliar): void {
                            $subAuxiliar->select('controle_nota_fiscal_auxiliares.controle_nota_fiscal_id')
                                ->from('controle_nota_fiscal_auxiliares')
                                ->join('autorizacao_servico_adicionais', 'autorizacao_servico_adicionais.controle_nota_fiscal_auxiliar_id', '=', 'controle_nota_fiscal_auxiliares.id')
                                ->whereColumn('autorizacao_servico_adicionais.id', 'controle_nota_fiscal_notas.autorizacao_servico_adicional_id');
                        });
                    });
            })
            ->limit(1);
    }

    public function fetchRowsHtml(): string
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->getTableExcelViewData();
        $config = $data['config'];
        $registros = $data['registros'];

        $rawColumns = $config->getColumns();
        $ocultas = $this->colunasOcultas ?? [];
        $userOrder = $this->ordemColunas ?? [];

        if (! empty($userOrder)) {
            $fixed = array_values(array_filter($rawColumns, fn ($c) => ! $c->reorderable));
            $reorderable = array_values(array_filter($rawColumns, fn ($c) => $c->reorderable));
            $byKey = [];
            foreach ($reorderable as $c) {
                $byKey[$c->key] = $c;
            }
            $ordered = [];
            foreach ($userOrder as $k) {
                if (isset($byKey[$k])) {
                    $ordered[] = $byKey[$k];
                    unset($byKey[$k]);
                }
            }
            foreach ($byKey as $c) {
                $ordered[] = $c;
            }
            $allColumns = array_merge($fixed, $ordered);
        } else {
            $allColumns = $rawColumns;
        }

        $columns = array_values(array_filter(
            $allColumns,
            fn ($c) => ! in_array($c->key, $ocultas, true),
        ));

        $selIds = array_map('strval', $this->selecionados ?? []);

        return view('filament.pages.partials.financeiro-notas-fiscais-tbody-rows', [
            'registros' => $registros,
            'columns' => $columns,
            'config' => $config,
            'recordKey' => $config->getRecordKey(),
            'bulkEnabled' => $config->isBulkEnabled(),
            'resizable' => $config->isResizable(),
            'frozenCols' => $this->frozenColumns ?? [],
            'widths' => $this->columnWidths ?? [],
            'selIds' => $selIds,
            'notasSelecionadas' => $this->notasSelecionadas,
        ])->render();
    }

    /** @var array<int, int> IDs de notas selecionadas para bulk action de baixa. */
    #[Locked]
    public array $notasSelecionadas = [];

    public function toggleNotaSelecionada(int $id): void
    {
        abort_unless(static::canAccess(), 403);

        $id = (int) $id;
        $current = array_map('intval', $this->notasSelecionadas);

        if (in_array($id, $current, true)) {
            $this->notasSelecionadas = array_values(array_filter($current, fn (int $i): bool => $i !== $id));
        } else {
            $current[] = $id;
            $this->notasSelecionadas = array_values(array_unique($current));
        }

        $this->dispatch('te-refresh-rows');
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function toggleGrupoSelecionado(array $ids): void
    {
        abort_unless(static::canAccess(), 403);

        $ids = array_map('intval', $ids);
        $current = array_map('intval', $this->notasSelecionadas);
        $emComum = array_intersect($current, $ids);

        if (count($emComum) === count($ids) && $ids !== []) {
            // Todos do grupo já selecionados → remove todos
            $this->notasSelecionadas = array_values(array_diff($current, $ids));
        } else {
            // Algum/nenhum selecionado → adiciona todos (união)
            $this->notasSelecionadas = array_values(array_unique(array_merge($current, $ids)));
        }

        $this->dispatch('te-refresh-rows');
    }

    public function limparSelecaoNotas(): void
    {
        $this->notasSelecionadas = [];
        $this->dispatch('te-refresh-rows');
    }

    public function selecionarTodasNotas(): void
    {
        abort_unless(static::canAccess(), 403);

        $config = $this->getTableExcelPage();
        $query = $config->buildQuery();
        $this->applySearch($query, $config);
        $this->applyFilters($query, $config);

        $this->notasSelecionadas = array_values(array_unique(array_map(
            'intval',
            $query->pluck('id')->all(),
        )));

        $this->dispatch('te-refresh-rows');
    }

    public function historicoBaixasAction(): Action
    {
        return Action::make('historicoBaixas')
            ->slideOver()
            ->modalWidth('lg')
            ->modalHeading(function (array $arguments): string {
                $nota = ControleNotaFiscalNota::find($arguments['record'] ?? null);

                $numero = $nota?->numero_nf ?: 'Nota';

                return "Histórico de baixas — NF {$numero}";
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(function (array $arguments) {
                $nota = ControleNotaFiscalNota::query()
                    ->with(['baixas.usuario:id,name'])
                    ->find($arguments['record'] ?? null);

                return view('filament.actions.financeiro-notas-fiscais-historico-baixas-modal', [
                    'nota' => $nota,
                ]);
            });
    }

    public function baixarSelecionadasAction(): Action
    {
        return Action::make('baixarSelecionadas')
            ->label('Baixar selecionadas')
            ->icon('heroicon-o-arrow-down-tray')
            ->size('xs')
            ->color(Color::hex('#FFBA00'))
            ->modalHeading('Baixar notas selecionadas')
            ->modalDescription('Escolha quais notas incluir no ZIP. A organização será em pastas por Unidade e Gestor.')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Baixar ZIP')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Radio::make('escopo')
                    ->hiddenLabel()
                    ->options([
                        'pendentes' => 'Apenas notas com baixa pendente',
                        'todas' => 'Todas as notas selecionadas',
                    ])
                    ->default('pendentes')
                    ->required(),
            ]))
            ->action(fn (array $data) => $this->baixarSelecionadas($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function baixarSelecionadas(array $data = []): ?BinaryFileResponse
    {
        if (! static::canAccess()) {
            abort(403);
        }

        if ($this->notasSelecionadas === []) {
            return null;
        }

        $escopo = (string) ($data['escopo'] ?? 'todas');
        $ids = array_values(array_unique(array_map('intval', $this->notasSelecionadas)));

        $query = ControleNotaFiscalNota::query()
            ->whereIn('id', $ids)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->where(function (Builder $q): void {
                $q->whereNotNull('arquivo_path')->orWhereNotNull('boleto_path');
            })
            ->with([
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra:id,unidade,projeto_id',
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto:id,resp_eng',
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra:id,unidade,projeto_id',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto:id,resp_eng',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
            ]);

        if ($escopo === 'pendentes') {
            $query->where('baixado', false);
        }

        $notas = $query->get();

        if ($notas->isEmpty()) {
            Notification::make()
                ->title($escopo === 'pendentes'
                    ? 'Nenhuma nota pendente com anexos entre as selecionadas'
                    : 'Nenhuma nota selecionada possui anexos disponíveis')
                ->warning()
                ->send();

            return null;
        }

        $sufixo = $escopo === 'pendentes' ? '-pendentes' : '';

        return $this->gerarZipNotasAgrupado($notas, 'anexos-selecionadas-'.$notas->count().$sufixo.'.zip');
    }

    protected function applySearch(Builder $query, TableExcelPage $config): void
    {
        if (! $config->hasSearch()) {
            return;
        }

        $termo = trim((string) $this->busca);

        if ($termo === '') {
            return;
        }

        $like = '%'.$termo.'%';

        $query->where(function (Builder $outer) use ($like): void {
            // Código e Unidade da obra (via item ou auxiliar)
            $outer->where(function (Builder $codigoUnidade) use ($like): void {
                $codigoUnidade->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra', function (Builder $obra) use ($like): void {
                    $obra->where('codigo', 'like', $like)
                        ->orWhere('unidade', 'like', $like);
                })->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra', function (Builder $obra) use ($like): void {
                    $obra->where('codigo', 'like', $like)
                        ->orWhere('unidade', 'like', $like);
                });
            });

            // Gestor (User.name via projeto.responsavelEng)
            $outer->orWhere(function (Builder $gestor) use ($like): void {
                $gestor->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto.responsavelEng', fn (Builder $u) => $u->where('name', 'like', $like))
                    ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto.responsavelEng', fn (Builder $u) => $u->where('name', 'like', $like));
            });
        });
    }

    /**
     * Orquestra a geração do ZIP via Service e aplica os side-effects de UI/DB
     * (Notification, marcar como baixado, refresh de linhas).
     *
     * @param  EloquentCollection<int, ControleNotaFiscalNota>  $notas
     */
    protected function gerarZipNotasAgrupado(EloquentCollection $notas, string $nomeArquivo): ?BinaryFileResponse
    {
        $resultado = app(FinanceiroNotasFiscaisZipService::class)->gerarAgrupado($notas);

        if ($resultado === null) {
            Notification::make()
                ->title('Erro ao criar arquivo ZIP')
                ->danger()
                ->send();

            return null;
        }

        if ($resultado->vazio()) {
            Notification::make()
                ->title('Nenhum anexo encontrado nas notas selecionadas')
                ->warning()
                ->send();

            return null;
        }

        $agora = now();
        $userId = Auth::id();
        $idsComArquivo = $resultado->idsComArquivo;

        $registrosHistorico = array_map(fn (int $notaId): array => [
            'controle_nota_fiscal_nota_id' => $notaId,
            'user_id' => $userId,
            'baixado_em' => $agora,
            'created_at' => $agora,
            'updated_at' => $agora,
        ], $idsComArquivo);

        if ($registrosHistorico !== []) {
            ControleNotaFiscalNotaBaixa::insert($registrosHistorico);
        }

        $marcadas = ControleNotaFiscalNota::query()
            ->whereIn('id', $idsComArquivo)
            ->where('baixado', false)
            ->count();

        ControleNotaFiscalNota::query()
            ->whereIn('id', $idsComArquivo)
            ->update([
                'baixado' => true,
                'baixado_por_id' => $userId,
                'baixado_em' => $agora,
                'updated_at' => DB::raw('updated_at'),
            ]);

        if ($marcadas > 0) {
            Notification::make()
                ->title("Marcadas como baixado: {$marcadas} nota(s)")
                ->success()
                ->send();
        }

        $this->dispatch('te-refresh-rows');

        return response()->download($resultado->caminho, $nomeArquivo)->deleteFileAfterSend();
    }

    protected function obrasComNotasAprovadasIdsQuery(): Builder
    {
        return ControleNotaFiscal::query()
            ->select('obra_id')
            ->whereNotNull('obra_id')
            ->where(function (Builder $q): void {
                $q->whereHas('itens.notasFiscais', fn (Builder $n): Builder => $n->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value))
                    ->orWhereHas('auxiliares.notasFiscais', fn (Builder $n): Builder => $n->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value));
            });
    }

    protected function applyObraFilter(Builder $query, mixed $value, string $coluna): Builder
    {
        $vals = array_values(array_filter((array) $value, fn ($v): bool => $v !== null && $v !== ''));

        if ($vals === []) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($vals, $coluna): void {
            $sub->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra', fn (Builder $obraQuery): Builder => $obraQuery->whereIn($coluna, $vals))
                ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra', fn (Builder $obraQuery): Builder => $obraQuery->whereIn($coluna, $vals));
        });
    }
}
