<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjetoResource;
use App\Mail\EnviarPdfMail;
use App\Models\Projeto;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class DashboardComercial extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Gestão comercial';

    protected static ?string $title = 'Gestão comercial';

    protected static ?string $slug = 'dashboard-comercial';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard-comercial';

    public array $kpis = [];

    public array $metaSemanal = [];

    public function mount(): void
    {
        $this->loadData();
    }

    protected function baseQuery(): Builder
    {
        $query = Projeto::query();

        if (! $this->canViewAllPoints()) {
            $query->where('resp_com', Auth::id());
        }

        return $query;
    }

    public function loadData(): void
    {
        $userId = Auth::id();
        $scope = $this->canViewAllPoints() ? 'all' : 'own';

        $this->kpis = Cache::remember("dashboard-comercial-kpis-user-{$userId}-{$scope}", now()->addMinutes(5), function () {
            $base = $this->baseQuery();

            $total = (clone $base)->count();
            $aprovados = (clone $base)->where('status_comite', 'aprovado')->count();
            $emValidacao = (clone $base)
                ->whereIn('status_comite', ['em_validacao', 'em validação'])
                ->count();
            $imovelPronto = (clone $base)->where('imovel_pronto', true)->count();
            $multiusuario = (clone $base)->where('locacao', 'Multiusuário')->count();
            $shell30 = (clone $base)
                ->whereNotNull('data_entrega_shell')
                ->whereBetween('data_entrega_shell', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()])
                ->count();

            return [
                'total' => $total,
                'aprovados' => $aprovados,
                'em_validacao' => $emValidacao,
                'imovel_pronto' => $imovelPronto,
                'multiusuario' => $multiusuario,
                'shell_30' => $shell30,
            ];
        });

        $this->metaSemanal = Cache::remember("dashboard-comercial-meta-semanal-user-{$userId}-{$scope}", now()->addMinutes(5), function () {
            if (! $this->canSeeMetaSemanal()) {
                return [];
            }

            $metaPorUsuario = 10;
            $inicioSemana = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $fimSemana = now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            $statusComite = $this->baseQuery()
                ->selectRaw("COALESCE(status_comite, 'sem_status') as status_comite, COUNT(*) as total")
                ->groupByRaw("COALESCE(status_comite, 'sem_status')")
                ->pluck('total', 'status_comite');

            $emValidacao = (int) (
                ($statusComite['em_validacao'] ?? 0)
                + ($statusComite['em validacao'] ?? 0)
                + ($statusComite['em validação'] ?? 0)
            );
            $aprovados = (int) ($statusComite['aprovado'] ?? 0);
            $reprovados = (int) ($statusComite['reprovado'] ?? 0);

            $percentual = (int) round(min(100, ($aprovados / max(1, $metaPorUsuario)) * 100));

            return [
                'meta' => $metaPorUsuario,
                'inicio' => $inicioSemana->format('d/m'),
                'fim' => $fimSemana->format('d/m'),
                'percentual' => $percentual,
                'aprovados' => $aprovados,
                'em_validacao' => $emValidacao,
                'reprovados' => $reprovados,
            ];
        });
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    private function countCamposNaoPreenchidos(Projeto $record): int
    {
        return count($this->camposNaoPreenchidos($record));
    }

    private function countArquivosNaoEnviados(Projeto $record): int
    {
        $campos = [
            'anexo_matricula_iptu',
            'anexo_habite_se',
            'anexo_avcb',
            'anexo_projeto',
            'anexo_convencao_condominio',
            'anexo_regime_interno',
            'anexo_normas_gerais',
            'anexo_outros_documentos',
        ];

        $faltantes = [];

        foreach ($campos as $campo) {
            if (
                in_array($campo, ['evtl_recebido_em', 'anexo_evtl'], true)
                && ! in_array($record->evtl_status, ['sim', 'existente'], true)
            ) {
                continue;
            }

            if ($this->campoEstaVazio($record, $campo)) {
                $faltantes[] = $campo;
            }
        }

        return count($faltantes);
    }

    private function camposNaoPreenchidos(Projeto $record): array
    {
        $campos = [
            'status_comite',
            'codigo',
            'nome',
            'marca',
            'numero_loja',
            'pontos_atencao',
            'pin_google',
            'cep',
            'numero',
            'complemento',
            'rua',
            'bairro',
            'pais_id',
            'estado_id',
            'cidade_id',
            'area_academia',
            'n_pisos',
            'configuracao_academia',
            'pe_direito',
            'n_vagas_livres',
            'tipo_de_loja',
            'empreendimento',
            'locacao',
            'tipo_imovel',
            'modelo_entrega_p',
            'data_entrega_shell',
            'imovel_pronto',
            'relocation',
            'aluguel_cto',
            'carencia',
            'multa_contrato',
            'obs_aluguel',
            'nome_contato',
            'contato',
            'cad_status',
            'vis_status',
            'legal_status_consulta_prev',
            'evtl_status',
            'evtl_recebido_em',
            'dados_engenharia',
            'anexo_evtl',
            'anexo_matricula_iptu',
            'anexo_habite_se',
            'anexo_avcb',
            'anexo_projeto',
            'anexo_convencao_condominio',
            'anexo_regime_interno',
            'anexo_normas_gerais',
            'anexo_outros_documentos',
        ];

        $faltantes = [];

        foreach ($campos as $campo) {
            if (
                in_array($campo, ['evtl_recebido_em', 'anexo_evtl'], true)
                && ! in_array($record->evtl_status, ['sim', 'existente'], true)
            ) {
                continue;
            }

            if ($this->campoEstaVazio($record, $campo)) {
                $faltantes[] = $campo;
            }
        }

        return $faltantes;
    }

    private function campoEstaVazio(Projeto $record, string $campo): bool
    {
        $valor = $record->{$campo} ?? null;

        return match (true) {
            is_array($valor) => empty(array_filter($valor, fn ($item): bool => filled($item))),
            is_bool($valor) => false,
            default => blank($valor),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('novoPonto')
                ->label('Novo ponto')
                ->icon('heroicon-o-plus')
                ->url('/admin/cadastrar-ponto'),
            /*
            Action::make('verProjetos')
                ->label('Ver projetos')
                ->icon('heroicon-o-rectangle-stack')
                ->url('/admin/projetos'),
            */
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->baseQuery()
                    ->with(['cidade:id,nome', 'estado:id,nome'])
                    ->latest()
            )
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('campos_pendentes')
                    ->label('Campos pendentes')
                    ->state(fn (Projeto $record): int => $this->countCamposNaoPreenchidos($record))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('arquivos_pendentes')
                    ->label('Arquivos pendentes')
                    ->state(fn (Projeto $record): int => $this->countArquivosNaoEnviados($record))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('relatorioVisitaTecnica.agendado_em')
                    ->label('Data de agendamento da VT')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('responsavelCom.name')
                    ->label('Responsável')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Ponto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status_comite')
                    ->label('Status Comitê')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'em_validacao' => 'Em validação',
                        'aprovado' => 'Aprovado',
                        'reprovado' => 'Reprovado',
                        default => (string) ($state ?: '—'),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'aprovado' => 'success',
                        'em_validacao', 'em validação' => 'warning',
                        'reprovado' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('empreendimento')
                    ->label('Empreendimento')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('locacao')
                    ->label('Locação')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('imovel_pronto')
                    ->label('Imóvel pronto')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cidade.nome')
                    ->label('Cidade')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('cidades', 'projetos.cidade_id', '=', 'cidades.id')
                            ->orderBy('cidades.nome', $direction)
                            ->select('projetos.*');
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estado.nome')
                    ->label('UF')
                    ->toggleable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('estados', 'projetos.estado_id', '=', 'estados.id')
                            ->orderBy('estados.uf', $direction)
                            ->select('projetos.*');
                    }),

                Tables\Columns\TextColumn::make('data_entrega_shell')
                    ->label('Entrega shell')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('marca')
                    ->label('Marca')
                    ->options(fn () => $this->baseQuery()
                        ->whereNotNull('marca')
                        ->where('marca', '!=', '')
                        ->distinct()
                        ->orderBy('marca')
                        ->pluck('marca', 'marca')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('status_comite')
                    ->label('Status comitê')
                    ->options([
                        'aprovado' => 'Aprovado',
                        'em_validacao' => 'Em validação',
                        'em validação' => 'Em validação',
                        'reprovado' => 'Reprovado',
                    ]),

                Tables\Filters\SelectFilter::make('empreendimento')
                    ->label('Empreendimento')
                    ->options(fn () => $this->baseQuery()
                        ->whereNotNull('empreendimento')
                        ->where('empreendimento', '!=', '')
                        ->distinct()
                        ->orderBy('empreendimento')
                        ->pluck('empreendimento', 'empreendimento')
                        ->toArray()),

                Tables\Filters\TernaryFilter::make('imovel_pronto')
                    ->label('Imóvel pronto'),

                Tables\Filters\Filter::make('shell_30')
                    ->label('Shell em 30 dias')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('data_entrega_shell')
                        ->whereBetween('data_entrega_shell', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()])),
            ])
            ->actions([
                Action::make('ver_vt_pdf')
                    ->label('')
                    ->tooltip('Visualizar VT')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => filled($record->relatorioVisitaTecnica?->pdf_path))
                    ->url(function ($record) {
                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                        return $disk->temporaryUrl(
                            $record->relatorioVisitaTecnica->pdf_path,
                            now()->addMinutes(10)
                        );
                    }, shouldOpenInNewTab: true),

                Action::make('enviar_email')
                    ->label(' ')
                    ->tooltip('Enviar por email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->form([
                        Select::make('para')
                            ->label('Para')
                            ->placeholder('Digite para buscar usuários')
                            ->options(fn (): array => $this->emailOptionsVisitaTecnica())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->required()
                            ->rules(['required', 'array'])
                            ->nestedRecursiveRules(['email'])
                            ->validationMessages([
                                'email' => 'Um ou mais e-mails são inválidos.',
                            ])
                            ->suffixAction(
                                Action::make('limparEmails')
                                    ->label('Limpar')
                                    ->icon('heroicon-o-x-mark')
                                    ->color('danger')
                                    ->action(function (Set $set) {
                                        $set('para', []);
                                    })
                            ),

                        Select::make('cc')
                            ->label('CC')
                            ->placeholder('Digite para buscar usuários')
                            ->options(fn (): array => $this->emailOptionsVisitaTecnica())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->rules(['nullable', 'array'])
                            ->nestedRecursiveRules(['email'])
                            ->validationMessages([
                                'email' => 'Um ou mais e-mails são inválidos.',
                            ]),

                        Select::make('cco')
                            ->label('CCO')
                            ->placeholder('Digite para buscar usuários')
                            ->options(fn (): array => $this->emailOptionsVisitaTecnica())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->rules(['nullable', 'array'])
                            ->nestedRecursiveRules(['email'])
                            ->validationMessages([
                                'email' => 'Um ou mais e-mails são inválidos.',
                            ]),

                        Hidden::make('assunto')->required(),
                        Hidden::make('mensagem')->required(),
                    ])
                    ->fillForm(function (Projeto $record) {
                        $cidade = $record->cidade?->nome ?? '';
                        $uf = $record->estado?->sigla ?? '';
                        $cidadeUf = trim(collect([$cidade, $uf])->filter()->implode('/'));

                        $enderecoCompleto = $record->endereco;
                        if (blank($enderecoCompleto)) {
                            $logradouro = collect([
                                $record->rua,
                                $record->numero,
                            ])->filter()->implode(', ');

                            $enderecoCompleto = collect([
                                $logradouro ?: null,
                                $record->complemento ?: null,
                                $record->bairro ?: null,
                                $cidadeUf ?: null,
                            ])->filter()->implode(' - ');
                        }

                        $simNao = fn ($valor) => match (mb_strtolower((string) $valor)) {
                            'sim', '1', 'true' => 'sim',
                            'nao', 'não', '0', 'false' => 'não',
                            default => $valor ?: 'não informado',
                        };

                        $imovelPronto = $record->imovel_pronto ? 'sim' : 'não';
                        $relocation = $record->relocation ? 'sim' : 'não';

                        $dataShell = $record->data_entrega_shell
                            ? Carbon::parse($record->data_entrega_shell)->format('m/y')
                            : 'não informado';

                        $aluguelFormatado = filled($record->aluguel_cto)
                            ? 'R$ '.number_format((float) $record->aluguel_cto, 2, ',', '.')
                            : 'não informado';

                        $carenciaFormatada = filled($record->carencia)
                            ? e($record->carencia)
                            : 'não informado';

                        $multaFormatada = filled($record->multa_contrato)
                            ? e($record->multa_contrato)
                            : 'não informado';

                        $pontosAtencaoHtml = filled($record->pontos_atencao)
                            ? $record->pontos_atencao
                            : '<span style="color:#444;">Sem observações</span>';

                        /** @var FilesystemAdapter $diskAnexos */
                        $diskAnexos = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                        $camposDeAnexo = [
                            'anexo_matricula_iptu' => 'Matrícula + IPTU',
                            'anexo_habite_se' => 'Habite-se',
                            'anexo_avcb' => 'AVCB',
                            'anexo_projeto' => 'Projeto',
                            'anexo_convencao_condominio' => 'Convenção do condomínio',
                            'anexo_regime_interno' => 'Regime interno',
                            'anexo_normas_gerais' => 'Normas gerais',
                            'anexo_outros_documentos' => 'Outros documentos',
                        ];

                        $anexosAgrupados = [];

                        foreach ($camposDeAnexo as $campo => $labelCampo) {
                            $arquivos = $record->{$campo} ?? [];

                            if (is_string($arquivos) && filled($arquivos)) {
                                $arquivos = [$arquivos];
                            }

                            if (! is_array($arquivos) || empty($arquivos)) {
                                continue;
                            }

                            foreach ($arquivos as $anexo) {
                                if (! is_string($anexo) || blank($anexo)) {
                                    continue;
                                }

                                if (! $diskAnexos->exists($anexo)) {
                                    continue;
                                }

                                try {
                                    $url = $diskAnexos->temporaryUrl($anexo, now()->addDays(7));
                                } catch (\Throwable $e) {
                                    $url = $diskAnexos->url($anexo);
                                }

                                $indice = count($anexosAgrupados[$labelCampo] ?? []) + 1;
                                $extensao = strtolower(pathinfo($anexo, PATHINFO_EXTENSION));

                                $nomeAmigavel = $labelCampo.' '.$indice;

                                if (filled($extensao)) {
                                    $nomeAmigavel .= '.'.$extensao;
                                }

                                $anexosAgrupados[$labelCampo][] = [
                                    'nome' => basename($anexo),
                                    'nome_amigavel' => $nomeAmigavel,
                                    'url' => $url,
                                ];
                            }
                        }

                        $blocoDocumentacao = '';

                        if (! empty($anexosAgrupados)) {
                            $blocoDocumentacao .= '<ul style="margin:8px 0 12px 18px; padding:0;">';

                            foreach ($anexosAgrupados as $categoria => $arquivos) {
                                if (empty($arquivos)) {
                                    continue;
                                }

                                $quantidade = count($arquivos);
                                $labelQuantidade = $quantidade === 1 ? 'arquivo' : 'arquivos';

                                $blocoDocumentacao .=
                                    '<li style="margin:0 0 10px 0;">'.
                                    '<span style="font-size:14px; font-weight:600; color:#222;">'.
                                    e($categoria).' ('.$quantidade.' '.$labelQuantidade.')'.
                                    '</span>'.
                                    '<ul style="margin:6px 0 0 18px; padding:0;">';

                                foreach ($arquivos as $arquivo) {
                                    $blocoDocumentacao .=
                                        '<li style="margin:0 0 4px 0; font-size:13px; line-height:1.5;">'.
                                        '<a href="'.e($arquivo['url']).'" target="_blank" style="color:#0b57d0; text-decoration:none;">'.
                                        e($arquivo['nome_amigavel']).
                                        '</a>'.
                                        '</li>';
                                }

                                $blocoDocumentacao .=
                                    '</ul>'.
                                    '</li>';
                            }

                            $blocoDocumentacao .= '</ul>';
                        } else {
                            $blocoDocumentacao = '<span style="font-size:13px; color:#444;">Nenhum documento anexado.</span><br><br>';
                        }

                        $mensagem =
                            'Prezados,<br><br>'.

                            'Segue abaixo as informações e documentos do imóvel para andamento:<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">📌 1. Informações do Ponto</div>'.

                            '<strong>Status Comitê:</strong> '.e($record->status_comite ?: 'não informado').'<br>'.
                            '<strong>Código <span style="color:#888; font-weight:normal;">(planejamento/ ATA comitê)</span> :</strong> '.e($record->codigo ?: 'não informado').'<br>'.
                            '<strong>Nome comercial do Ponto <span style="color:#888; font-weight:normal;">(nome aprovado em comitê/ diretoria)</span> :</strong> '.e($record->nome ?: 'não informado').'<br>'.
                            '<strong>Marca <span style="color:#888; font-weight:normal;">(DPC)</span> :</strong> '.e($record->marca ?: 'não informado').'<br>'.
                            '<strong>Endereço <span style="color:#888; font-weight:normal;">(Rua, número, cidade e UF)</span> :</strong> '.e($enderecoCompleto ?: 'não informado').'<br>'.
                            '<strong>Nº Loja / LUC:</strong> '.e($record->numero_loja ?: 'não informado').'<br>'.
                            '<strong>Google Maps:</strong> '.e($record->pin_google ?: 'não informado').'<br>'.
                            '<strong>Área contratada <span style="color:#888; font-weight:normal;">(m²)</span> :</strong> '.e($record->area_academia ? number_format((float) $record->area_academia, 2, ',', '.').' m²' : 'não informado').'<br>'.
                            '<strong>Número de pisos <span style="color:#888; font-weight:normal;">(quantidade de pavimentos)</span> :</strong> '.e($record->n_pisos ?: 'não informado').'<br>'.
                            '<strong>Configuração da academia <span style="color:#888; font-weight:normal;">(térreo/ subsolo/ mezanino)</span> :</strong> '.e($record->configuracao_academia ?: 'não informado').'<br>'.
                            '<strong>Pé-direito <span style="color:#888; font-weight:normal;">(m)</span> :</strong> '.e($record->pe_direito ? number_format((float) $record->pe_direito, 2, ',', '.').' m' : 'não informado').'<br>'.
                            '<strong>Quantidade de vagas de estacionamento:</strong> '.e($record->n_vagas_livres ?: 'não informado').'<br>'.
                            '<strong>Tipo de vagas <span style="color:#888; font-weight:normal;">(exclusivas/ compartilhadas/ horas livres)</span> :</strong> '.e($record->tipo_de_loja ?: 'não informado').'<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">📌 2. Pontos de atenção</div>'.

                            '<div style="margin:6px 0 16px 0; line-height:1.6;">'.$pontosAtencaoHtml.'</div>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">💰 3. Informações Comerciais</div>'.

                            '<strong>Valor do aluguel <span style="color:#888; font-weight:normal;">(R$)</span> :</strong> '.$aluguelFormatado.'<br>'.
                            '<strong>Observações:</strong> '.e($record->obs_aluguel ?: '-').'<br>'.
                            '<strong>Carência <span style="color:#888; font-weight:normal;">(meses)</span> :</strong> '.$carenciaFormatada.'<br>'.
                            '<strong>Multa contratual <span style="color:#888; font-weight:normal;">(meses)</span> :</strong> '.$multaFormatada.'<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">🏢 4. Características do Imóvel</div>'.

                            '<strong>Empreendimento <span style="color:#888; font-weight:normal;">(Shopping/ Rua/ Supermercado/ Mall/ Edifício Comercial)</span> :</strong> '.e($record->empreendimento ?: 'não informado').'<br>'.
                            '<strong>Tipo de locação <span style="color:#888; font-weight:normal;">(Monousuário/ Multiusuário)</span> :</strong> '.e($record->locacao ?: 'não informado').'<br>'.
                            '<strong>Tipo de imóvel <span style="color:#888; font-weight:normal;">(BTS/ Padrão/ Construção DPC)</span> :</strong> '.e($record->tipo_imovel ?: 'não informado').'<br>'.
                            '<strong>Entregas do PP <span style="color:#888; font-weight:normal;">(água/ energia/ elevador/ outros)</span> :</strong> '.e($record->modelo_entrega_p ?: 'não informado').'<br>'.
                            '<strong>Data de entrega do shell/ Previsão de posse <span style="color:#888; font-weight:normal;">(dia/mês/ano)</span> :</strong> '.e($dataShell).'<br>'.
                            '<strong>Imóvel pronto <span style="color:#888; font-weight:normal;">(sim/ não)</span> :</strong> '.e($imovelPronto).'<br>'.
                            '<strong>Contato do proprietário/ corretor <span style="color:#888; font-weight:normal;">(nome, telefone e e-mail)</span> :</strong> '.e($record->contato_corretor ?: 'não informado').'<br>'.
                            '<strong>Relocation <span style="color:#888; font-weight:normal;">(se aplicável, informar)</span> :</strong> '.e($relocation).'<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">⚙️ 5. Necessidades</div>'.

                            '<strong>Cadastral <span style="color:#888; font-weight:normal;">(sim/ não)</span> :</strong> '.e($simNao($record->cad_status)).'<br>'.
                            '<strong>Visita Técnica <span style="color:#888; font-weight:normal;">(sim/ não)</span> :</strong> '.e($simNao($record->vis_status)).'<br>'.
                            '<strong>Necessidade específica da engenharia:</strong> '.e($record->dados_engenharia ?: 'Sem observações').'<br>'.
                            '<strong>Consulta Prévia <span style="color:#888; font-weight:normal;">(sim/ não)</span> :</strong> '.e($simNao($record->legal_status_consulta_prev)).'<br>'.
                            '<strong>EVTL <span style="color:#888; font-weight:normal;">(sim/ não)</span> :</strong> '.e($simNao($record->status)).'<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">📐 6. Projetos</div>'.

                            'Projetos em DWG: '.e($record->link_docs ?: 'não informado').'<br><br>'.

                            '<div style="font-size:16px; font-weight:700; color:#111; margin-bottom:10px;">📄 7. Documentação</div>'.
                            $blocoDocumentacao.
                            '<br>'.

                            'Em caso de dúvidas, fico à disposição.<br><br>'.
                            'Atenciosamente,<br>';

                        return [
                            'para' => [
                                'comercial.imobiliario@smartfit.com',
                            ],
                            'cc' => array_values(array_filter([
                                Filament::auth()->user()?->email,
                            ])),
                            'cco' => [],
                            'assunto' => 'Comercial | Entrada de novo ponto '.($record->nome ?? 'Sem nome').' - '.($record->codigo ?? 'Sem código'),
                            'mensagem' => $mensagem,
                        ];
                    })
                    ->action(function (Projeto $record, array $data) {
                        try {
                            $usuario = Filament::auth()->user();

                            $fotoPerfilBinaria = null;
                            $fotoPerfilMime = null;
                            $fotoPerfilNome = null;

                            /** @var FilesystemAdapter $diskFoto */
                            $diskFoto = Storage::disk((string) config('filesystems.media_disk', 'r2'));
                            $caminhoFotoPadrao = public_path('images/logo_smart_white.png');

                            if ($usuario?->foto_perfil && $diskFoto->exists($usuario->foto_perfil)) {
                                $fotoPerfilBinaria = $diskFoto->get($usuario->foto_perfil);
                                $fotoPerfilMime = $diskFoto->mimeType($usuario->foto_perfil) ?: 'image/png';
                                $fotoPerfilNome = basename($usuario->foto_perfil);
                            } elseif (file_exists($caminhoFotoPadrao)) {
                                $fotoPerfilBinaria = file_get_contents($caminhoFotoPadrao);
                                $fotoPerfilMime = mime_content_type($caminhoFotoPadrao) ?: 'image/png';
                                $fotoPerfilNome = basename($caminhoFotoPadrao);
                            }

                            $mail = new EnviarPdfMail(
                                assunto: $data['assunto'],
                                mensagemEmail: $data['mensagem'],
                                pdfBinary: '',
                                nomeArquivo: '',
                                fotoPerfilBinaria: $fotoPerfilBinaria,
                                fotoPerfilMime: $fotoPerfilMime,
                                fotoPerfilNome: $fotoPerfilNome,
                                anexosLinks: [],
                                nomeRemetente: $usuario?->name,
                                emailRemetente: $usuario?->email,
                                cargoRemetente: 'Expansão / Comercial',
                                empresaRemetente: 'DPC',
                            );

                            Mail::to($data['para'] ?? [])
                                ->cc($data['cc'] ?? [])
                                ->bcc($data['cco'] ?? [])
                                ->send($mail);

                            Notification::make()
                                ->title('E-mail enviado com sucesso')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao enviar e-mail')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('editar')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Projeto $record): string => ProjetoResource::getUrl('editar-ponto', ['record' => $record->getKey()])),

                Action::make('ver')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Projeto $record): string => ProjetoResource::getUrl('visualizar-ponto', ['record' => $record->getKey()])),
            ])->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->recordUrl(fn (Projeto $record): string => ProjetoResource::getUrl('editar-ponto', ['record' => $record->getKey()]))
            ->emptyStateHeading('Nenhum ponto encontrado')
            ->emptyStateDescription('Ainda não existem pontos cadastrados para sua gestão.')
            ->emptyStateIcon('heroicon-o-map-pin');
    }

    protected function hasSetorComercial(User $user): bool
    {
        return $user->setores()->whereRaw('LOWER(setor) = ?', ['comercial'])->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function emailOptionsVisitaTecnica(): array
    {
        $emailOptions = [
            'comercial.imobiliario@smartfit.com' => 'Comercial Imobiliário <comercial.imobiliario@smartfit.com>',
        ];

        User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['name', 'email'])
            ->each(function (User $user) use (&$emailOptions): void {
                $email = mb_strtolower(trim((string) $user->email));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return;
                }

                $emailOptions[$email] = "{$user->name} <{$email}>";
            });

        return $emailOptions;
    }

    protected function canViewAllPoints(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('Gestor') && $this->hasSetorComercial($user);
    }

    protected function canSeeMetaSemanal(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('Comercial') && $this->hasSetorComercial($user);
    }

    protected static function isAllowedCommercialUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $hasSetorComercial = $user->setores()->whereRaw('LOWER(setor) = ?', ['comercial'])->exists();
        if (! $hasSetorComercial) {
            return false;
        }

        return $user->hasAnyRole(['Gestor', 'Comercial']);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->can('View:DashboardComercial')) {
            return false;
        }

        return static::isAllowedCommercialUser($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
