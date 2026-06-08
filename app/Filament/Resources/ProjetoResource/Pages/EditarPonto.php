<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Pages\DashboardComercial;
use App\Filament\Resources\ProjetoResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Marca;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditarPonto extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ProjetoResource::class;

    protected string $view = 'filament.resources.projeto-resource.pages.editar-ponto';

    public ?array $data = [];

    protected $listeners = [
        'confirmar-salvamento-mesmo-assim' => 'confirmarSalvamentoMesmoAssim',
    ];

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::check();
    }

    public function mount(int|string $record): void
    {
        $this->record = Projeto::findOrFail($record);

        if (ProjetoResource::isCommercialUser()) {
            abort_unless((int) $this->record->resp_com === (int) Auth::id(), 403);
        }
        $taskCategoryId = TaskCategory::where('name', 'Visita Técnica')->value('id');

        $taskVisitaTecnica = null;
        $responsaveisIds = [];

        if ($taskCategoryId) {
            $taskVisitaTecnica = Task::query()
                ->where('projeto_id', $this->record->id)
                ->where('task_category_id', $taskCategoryId)
                ->first();

            if ($taskVisitaTecnica) {
                $responsaveisIds = $taskVisitaTecnica->responsaveis()->pluck('users.id')->toArray();
            }
        }

        if (empty($responsaveisIds) && ($this->record->vis_status ?? null) === 'sim') {
            $responsaveisIds = User::query()
                ->whereIn('email', [
                    'talita.carmona@bioritmo.com.br',
                    'talita.soares@smartfit.com',
                ])
                ->pluck('id')
                ->toArray();
        }

        $this->form->fill([
            'id' => $this->record->id,
            'etapas' => $this->record->etapas()->pluck('etapas.id')->all(),
            'status_comite' => $this->record->status_comite,
            'codigo' => $this->record->codigo,
            'nome' => $this->record->nome,
            'marca' => $this->record->marca,
            'numero_loja' => $this->record->numero_loja,
            'pin_google' => $this->record->pin_google,
            'link_docs' => $this->record->link_docs,
            'cep' => $this->record->cep,
            'numero' => $this->record->numero,
            'complemento' => $this->record->complemento,
            'rua' => $this->record->rua,
            'bairro' => $this->record->bairro,
            'pais_id' => $this->record->pais_id,
            'estado_id' => $this->record->estado_id,
            'cidade_id' => $this->record->cidade_id,
            'contato_corretor' => $this->record->contato_corretor,
            'area_academia' => $this->record->area_academia,
            'n_pisos' => $this->record->n_pisos,
            'configuracao_academia' => $this->record->configuracao_academia,
            'pe_direito' => $this->record->pe_direito,
            'n_vagas_livres' => $this->record->n_vagas_livres,
            'tipo_de_loja' => $this->record->tipo_de_loja,
            'empreendimento' => $this->record->empreendimento,
            'locacao' => $this->record->locacao,
            'tipo_imovel' => $this->record->tipo_imovel,
            'modelo_entrega_p' => $this->record->modelo_entrega_p,
            'data_entrega_shell' => $this->record->data_entrega_shell,
            'imovel_pronto' => $this->record->imovel_pronto,
            'relocation' => $this->record->relocation,
            'aluguel_cto' => $this->record->aluguel_cto,
            'carencia' => $this->record->carencia,
            'multa_contrato' => $this->record->multa_contrato,
            'obs_aluguel' => $this->record->obs_aluguel,
            'nome_contato' => $this->record->nome_contato,
            'contato' => $this->record->contato,
            'cad_status' => $this->record->cad_status,
            'vis_status' => $this->record->vis_status,
            'visita_tecnica_user_ids' => $responsaveisIds,
            'legal_status_consulta_prev' => $this->record->legal_status_consulta_prev,
            'evtl_status' => $this->record->evtl_status,
            'evtl_recebido_em' => $this->record->evtl_recebido_em,
            'anexo_evtl' => $this->record->anexo_evtl ?? [],
            'dados_engenharia' => $this->record->dados_engenharia,
            'pontos_atencao' => $this->record->pontos_atencao,
            'anexo_matricula_iptu' => $this->record->anexo_matricula_iptu ?? [],
            'anexo_habite_se' => $this->record->anexo_habite_se ?? [],
            'anexo_avcb' => $this->record->anexo_avcb ?? [],
            'anexo_projeto' => $this->record->anexo_projeto ?? [],
            'anexo_convencao_condominio' => $this->record->anexo_convencao_condominio ?? [],
            'anexo_regime_interno' => $this->record->anexo_regime_interno ?? [],
            'anexo_normas_gerais' => $this->record->anexo_normas_gerais ?? [],
            'anexo_outros_documentos' => $this->record->anexo_outros_documentos ?? [],
        ]);
    }

    protected static function baseDirectory(string $numero): string
    {
        return 'arquivos-pt/'.$numero;
    }

    protected static function midiaDirectory(Get $get): string
    {
        return static::baseDirectory((string) ($get('id') ?: 'temp')).'/midia';
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.pages.dashboard-comercial') => 'Dashboard Comercial',
            '#' => 'Editar ponto',
        ];
    }

    public function getTitle(): string
    {
        return 'Editar ponto';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components($this->getFormComponents(false));
    }

    protected function getFormComponents(bool $disabled = false): array
    {
        return [
            Section::make('Identificação do ponto')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('etapas')
                            ->label('Etapa do projeto')
                            ->multiple()
                            ->preload()
                            ->options(fn (): array => Etapa::query()
                                ->where('nome', 'Prospecção')
                                ->pluck('nome', 'id')
                                ->toArray())
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('Ao cadastrar um ponto, a etapa inicial é sempre Prospecção.'),

                        Select::make('status_comite')
                            ->label('Status do comitê')
                            ->native(false)
                            ->options([
                                'em_validacao' => 'Em validação',
                                'aprovado' => 'Aprovado',
                                'reprovado' => 'Reprovado',
                            ])
                            ->disabled($disabled),

                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->validationMessages([
                                'required' => 'Informe o código.',
                            ])
                            ->readOnly($disabled),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('nome')
                            ->label('Nome comercial do ponto')
                            ->required()
                            ->validationMessages([
                                'required' => 'Informe o nome.',
                            ])
                            ->readOnly($disabled),

                        Select::make('marca')
                            ->label('Marca')
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Marca::query()
                                ->orderBy('nome')
                                ->pluck('nome', 'nome')
                                ->toArray())
                            ->disabled($disabled),

                        TextInput::make('numero_loja')
                            ->label('Nº da Loja / LUC')
                            ->readOnly($disabled),
                    ]),

                    Grid::make(2)->schema([

                        RichEditor::make('pontos_atencao')
                            ->label('Pontos de atenção')
                            ->extraInputAttributes([
                                'style' => 'min-height: 7.5rem;',
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ])
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->helperText('Esse texto irá complementar o corpo do e-mail.')
                            ->placeholder('Descreva observações importantes, riscos, restrições ou cuidados específicos do ponto.')
                            ->disabled($disabled),

                        TextInput::make('pin_google')
                            ->label('Link do Google Maps')
                            ->prefixIcon(Heroicon::MapPin)
                            ->placeholder('Cole aqui o link do Google Maps')
                            ->helperText('Use o link compartilhado do Google Maps para localizar o ponto.')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (filled($state) && ! str_starts_with($state, 'http://') && ! str_starts_with($state, 'https://')) {
                                    $set('pin_google', 'https://'.ltrim($state, '/'));
                                }
                            })
                            ->live(onBlur: true)
                            ->url()
                            ->rule('regex:/^(https?:\/\/)?(www\.)?(google\.com\/maps|maps\.app\.goo\.gl|goo\.gl\/maps)\/.+$/i')
                            ->validationMessages([
                                'url' => 'Informe uma URL válida.',
                                'regex' => 'Informe um link válido do Google Maps.',
                            ])
                            ->readOnly($disabled)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                    ]),
                ]),

            Section::make('Localização do Ponto')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->helperText('Informe o CEP para atualizar as informações de localização.')
                            ->afterStateUpdated(function ($state, callable $set) use ($disabled) {
                                if ($disabled) {
                                    return;
                                }

                                if (! preg_match('/^\d{5}-\d{3}$/', (string) $state)) {
                                    $set('rua', null);
                                    $set('bairro', null);
                                    $set('pais_id', null);
                                    $set('estado_id', null);
                                    $set('cidade_id', null);

                                    Notification::make()
                                        ->title('CEP inválido')
                                        ->body('Digite um CEP no formato 00000-000.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $cepLimpo = str_replace('-', '', $state);
                                $url = "https://viacep.com.br/ws/{$cepLimpo}/json/";

                                try {
                                    $response = @file_get_contents($url);

                                    if (! $response) {
                                        $set('rua', null);
                                        $set('bairro', null);
                                        $set('pais_id', null);
                                        $set('estado_id', null);
                                        $set('cidade_id', null);

                                        Notification::make()
                                            ->title('Não foi possível consultar o CEP')
                                            ->body('O serviço de CEP não respondeu no momento. Tente novamente em instantes.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $data = json_decode($response, true);

                                    if (! empty($data['erro'])) {
                                        $set('rua', null);
                                        $set('bairro', null);
                                        $set('pais_id', null);
                                        $set('estado_id', null);
                                        $set('cidade_id', null);

                                        Notification::make()
                                            ->title('CEP não encontrado')
                                            ->body('Verifique o número informado e tente novamente.')
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $set('rua', $data['logradouro'] ?? '');
                                    $set('bairro', $data['bairro'] ?? '');

                                    $paisId = Pais::query()
                                        ->where('nome', 'Brasil')
                                        ->value('id');

                                    if (! $paisId) {
                                        $set('pais_id', null);
                                        $set('estado_id', null);
                                        $set('cidade_id', null);

                                        Notification::make()
                                            ->title('Configuração incompleta')
                                            ->body('O país Brasil não foi encontrado no cadastro.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $set('pais_id', $paisId);

                                    $estadoId = Estado::query()
                                        ->where('pais_id', $paisId)
                                        ->where('uf', $data['uf'] ?? '')
                                        ->value('id');

                                    if (! $estadoId) {
                                        $set('estado_id', null);
                                        $set('cidade_id', null);

                                        Notification::make()
                                            ->title('Estado não encontrado')
                                            ->body('O estado retornado pelo CEP não está cadastrado no sistema.')
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $set('estado_id', $estadoId);

                                    $cidadeNome = trim((string) ($data['localidade'] ?? ''));

                                    $cidadeId = Cidade::query()
                                        ->where('estado_id', $estadoId)
                                        ->where(function ($query) use ($cidadeNome) {
                                            $query->where('nome', $cidadeNome)
                                                ->orWhere('nome', 'like', $cidadeNome)
                                                ->orWhere('nome', 'like', "%{$cidadeNome}%");
                                        })
                                        ->value('id');

                                    $set('cidade_id', $cidadeId ?: null);

                                    if (! $cidadeId) {
                                        Notification::make()
                                            ->title('Cidade não encontrada')
                                            ->body('A cidade retornada pelo CEP não está cadastrada no sistema.')
                                            ->warning()
                                            ->send();
                                    }
                                } catch (\Throwable $e) {
                                    $set('rua', null);
                                    $set('bairro', null);
                                    $set('pais_id', null);
                                    $set('estado_id', null);
                                    $set('cidade_id', null);

                                    Notification::make()
                                        ->title('Erro ao consultar o CEP')
                                        ->body('Ocorreu um problema ao buscar o endereço. Tente novamente.')
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->disabled($disabled),

                        TextInput::make('numero')
                            ->label('Número')
                            ->reactive()
                            ->maxLength(20)
                            ->readOnly($disabled),

                        TextInput::make('complemento')
                            ->label('Complemento')
                            ->readOnly($disabled),

                        TextInput::make('rua')
                            ->label('Rua')
                            ->readOnly($disabled),
                    ]),

                    Grid::make(4)->schema([
                        TextInput::make('bairro')
                            ->label('Bairro')
                            ->readOnly($disabled),

                        Select::make('pais_id')
                            ->label('País')
                            ->native(false)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('estado_id', null);
                                $set('cidade_id', null);
                            })
                            ->options(fn (): array => Pais::query()
                                ->orderBy('nome')
                                ->pluck('nome', 'id')
                                ->toArray())
                            ->disabled($disabled),

                        Select::make('estado_id')
                            ->label('Estado')
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get): bool => blank($get('pais_id')))
                            ->afterStateUpdated(function (callable $set): void {
                                $set('cidade_id', null);
                            })
                            ->options(function (callable $get): array {
                                $paisId = $get('pais_id');

                                if (! $paisId) {
                                    return [];
                                }

                                return Estado::query()
                                    ->where('pais_id', $paisId)
                                    ->orderBy('nome')
                                    ->get()
                                    ->mapWithKeys(fn ($estado) => [
                                        $estado->id => "{$estado->uf} - {$estado->nome}",
                                    ])
                                    ->toArray();
                            })
                            ->disabled($disabled),

                        Select::make('cidade_id')
                            ->label('Cidade')
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get): bool => blank($get('estado_id')))
                            ->options(function (callable $get): array {
                                $estadoId = $get('estado_id');

                                if (! $estadoId) {
                                    return [];
                                }

                                return Cidade::query()
                                    ->where('estado_id', $estadoId)
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                                    ->toArray();
                            })
                            ->disabled($disabled),
                    ]),
                ]),

            Section::make('Características do imóvel')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('area_academia')
                            ->label('Área contratada')
                            ->numeric()
                            ->required()
                            ->validationMessages([
                                'required' => 'Informe a area.',
                            ])
                            ->suffix('m²')
                            ->readOnly($disabled),

                        TextInput::make('n_pisos')
                            ->label('Número de pisos')
                            ->numeric()
                            ->readOnly($disabled),

                        TextInput::make('configuracao_academia')
                            ->label('Configuração da academia')
                            ->readOnly($disabled),

                        TextInput::make('pe_direito')
                            ->label('PD')
                            ->numeric()
                            ->suffix('m')
                            ->readOnly($disabled),
                    ]),

                    Grid::make(4)->schema([
                        TextInput::make('n_vagas_livres')
                            ->label('Quantidade de vagas de estacionamento')
                            ->readOnly($disabled),

                        Select::make('tipo_de_loja')
                            ->label('Vagas')
                            ->native(false)
                            ->options([
                                'exclusivas' => 'Exclusivas',
                                'compartilhadas' => 'Compartilhadas',
                                'horas_livres' => 'Horas livres',
                            ])
                            ->disabled($disabled),

                        Select::make('empreendimento')
                            ->label('Empreendimento')
                            ->native(false)
                            ->options([
                                'Shopping' => 'Shopping',
                                'Rua' => 'Rua',
                                'Supermercado' => 'Supermercado',
                                'Mall' => 'Mall',
                                'Edifício Comercial' => 'Edifício Comercial',
                            ])
                            ->disabled($disabled),

                        Select::make('locacao')
                            ->label('Tipo de locação')
                            ->native(false)
                            ->options([
                                'Mono usuário' => 'Mono usuário',
                                'Multiusuário' => 'Multiusuário',
                            ])
                            ->disabled($disabled),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('tipo_imovel')
                            ->label('Tipo de imóvel')
                            ->native(false)
                            ->options([
                                'bts' => 'BTS',
                                'padrao' => 'Padrão',
                                'construcao_smart_fit' => 'Construção DPC',
                            ])
                            ->disabled($disabled),

                        TextInput::make('modelo_entrega_p')
                            ->label('Entregas do PP')
                            ->readOnly($disabled),

                        DatePicker::make('data_entrega_shell')
                            ->label('Data de entrega do shell/ previsão de posse')
                            ->disabled($disabled),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('imovel_pronto')
                            ->label('Imóvel pronto')
                            ->onColor('success')
                            ->offColor('danger')
                            ->disabled($disabled),

                        Toggle::make('relocation')
                            ->label('Relocation')
                            ->onColor('success')
                            ->offColor('danger')
                            ->disabled($disabled),
                    ]),
                ]),

            Section::make('Comercial')
                ->schema([
                    Grid::make(3)->schema([
                        MoneyInput::make('aluguel_cto', 'Valor do aluguel')
                            ->disabled($disabled),

                        TextInput::make('carencia')
                            ->label('Carência')
                            ->readOnly($disabled),

                        TextInput::make('multa_contrato')
                            ->label('Multa contratual')
                            ->readOnly($disabled),
                    ]),

                    Textarea::make('obs_aluguel')
                        ->label('Observações aluguel')
                        ->rows(4)
                        ->readOnly($disabled),
                ]),

            Section::make('Contato')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nome_contato')
                            ->label('Nome do contato')
                            ->required(fn (Get $get): bool => ($get('cad_status') ?? null) === 'sim')
                            ->readOnly($disabled),

                        TextInput::make('contato')
                            ->label('Telefone / e-mail')
                            ->required(fn (Get $get): bool => ($get('cad_status') ?? null) === 'sim')
                            ->readOnly($disabled),
                    ]),
                ]),

            Section::make('Necessidades')
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('cad_status')
                            ->label('Cadastral')
                            ->native(false)
                            ->live()
                            ->options([
                                'sim' => 'Sim',
                                'nao' => 'Não',
                            ])
                            ->disabled($disabled),

                        Select::make('vis_status')
                            ->label('Visita Técnica')
                            ->native(false)
                            ->options([
                                'sim' => 'Sim',
                                'nao' => 'Não',
                            ])
                            ->disabled($disabled),

                        Select::make('legal_status_consulta_prev')
                            ->label('Consulta Prévia')
                            ->native(false)
                            ->options([
                                'sim' => 'Sim',
                                'nao' => 'Não',
                            ])
                            ->disabled($disabled),

                        Select::make('evtl_status')
                            ->label('EVTL')
                            ->native(false)
                            ->live()
                            ->options([
                                'sim' => 'Sim',
                                'nao' => 'Não',
                                'existente' => 'Existente',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! in_array($state, ['sim', 'existente'], true)) {
                                    $set('evtl_recebido_em', null);
                                    $set('anexo_evtl', []);
                                }
                            })
                            ->disabled($disabled),

                        DatePicker::make('evtl_recebido_em')
                            ->label('EVTL recebido em')
                            ->visible(fn (callable $get) => in_array(($get('evtl_status') ?? null), ['sim', 'existente'], true))
                            ->disabled($disabled),

                        FileUpload::make('anexo_evtl')
                            ->label('Anexo EVTL')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->visible(fn (callable $get) => in_array(($get('evtl_status') ?? null), ['sim', 'existente'], true))
                            ->disabled($disabled),
                    ]),

                    Textarea::make('dados_engenharia')
                        ->label('Necessidade específica da engenharia')
                        ->rows(4)
                        ->readOnly($disabled),
                ]),

            Section::make('Projetos e documentação')
                ->schema([
                    Grid::make(2)->schema([
                        FileUpload::make('anexo_matricula_iptu')
                            ->label('Matrícula + IPTU')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)
                            ->directory(fn (Get $get) => static::midiaDirectory($get))->disk((string) config('filesystems.media_disk', 'r2'))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->helperText('Envie matrícula e IPTU.')
                            ->disabled($disabled),

                        FileUpload::make('anexo_habite_se')
                            ->label('Habite-se')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),

                        FileUpload::make('anexo_avcb')
                            ->label('AVCB')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),

                        FileUpload::make('anexo_projeto')
                            ->label('Projeto')
                            ->hintIcon(Heroicon::InformationCircle, 'Formatos permitidos: PDF, DWG, RVT e ZIP. Tamanho máximo: 700 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)
                            ->directory(fn (Get $get) => static::midiaDirectory($get))->disk((string) config('filesystems.media_disk', 'r2'))
                            ->acceptedFileTypes([
                                'application/pdf',
                                '.dwg',
                                '.rvt',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->maxSize(716800)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->helperText('Pode incluir PDF, DWG, RVT e ZIP.')
                            ->disabled($disabled),

                        FileUpload::make('anexo_convencao_condominio')
                            ->label('Convenção do condomínio')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),

                        FileUpload::make('anexo_regime_interno')
                            ->label('Regime Interno')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),

                        FileUpload::make('anexo_normas_gerais')
                            ->label('Normas Gerais')
                            ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'application/pdf',
                            ])
                            ->maxSize(50000)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),

                        FileUpload::make('anexo_outros_documentos')
                            ->label('Outros documentos')
                            ->hintIcon(Heroicon::InformationCircle, 'Formatos permitidos: imagens, PDF, Word, Excel, PowerPoint, TXT, DWG, RVT e ZIP. Tamanho máximo: 300 MB por arquivo.')
                            ->multiple()
                            ->reorderable()
                            ->openable(false)
                            ->downloadable()
                            ->previewable(false)->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn (Get $get) => static::midiaDirectory($get))
                            ->acceptedFileTypes([
                                'image/*',
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                                '.dwg',
                                '.rvt',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->maxFiles(20)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                            })
                            ->disabled($disabled),
                    ]),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            /*
            Action::make('salvar')
                ->label('Salvar alterações')
                ->submit('save')
                ->url(fn() => \App\Filament\Pages\DashboardComercial::getUrl()),
            */

            Action::make('visualizar')
                ->label('Visualizar')
                ->icon('heroicon-o-eye')
                ->url(fn () => ProjetoResource::getUrl('visualizar-ponto', ['record' => $this->record->getKey()])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return DashboardComercial::getUrl();
    }

    protected function getCamposPendentes(array $data): array
    {
        $campos = [
            // Identificação do ponto
            'status_comite' => 'Status do comitê',
            'codigo' => 'Código',
            'nome' => 'Nome comercial do ponto',
            'marca' => 'Marca',
            'numero_loja' => 'Nº da Loja / LUC',
            'pontos_atencao' => 'Pontos de atenção',
            'pin_google' => 'Link do Google Maps',

            // Localização do ponto
            'cep' => 'CEP',
            'numero' => 'Número',
            'complemento' => 'Complemento',
            'rua' => 'Rua',
            'bairro' => 'Bairro',
            'pais_id' => 'País',
            'estado_id' => 'Estado',
            'cidade_id' => 'Cidade',

            // Características do imóvel
            'area_academia' => 'Área contratada',
            'n_pisos' => 'Número de pisos',
            'configuracao_academia' => 'Configuração da academia',
            'pe_direito' => 'PD',
            'n_vagas_livres' => 'Quantidade de vagas de estacionamento',
            'tipo_de_loja' => 'Vagas',
            'empreendimento' => 'Empreendimento',
            'locacao' => 'Tipo de locação',
            'tipo_imovel' => 'Tipo de imóvel',
            'modelo_entrega_p' => 'Entregas do PP',
            'data_entrega_shell' => 'Data de entrega do shell / previsão de posse',
            'imovel_pronto' => 'Imóvel pronto',
            'relocation' => 'Relocation',

            // Comercial
            'aluguel_cto' => 'Valor do aluguel',
            'carencia' => 'Carência',
            'multa_contrato' => 'Multa contratual',
            'obs_aluguel' => 'Observações aluguel',

            // Contato
            'nome_contato' => 'Nome do contato',
            'contato' => 'Telefone / e-mail',

            // Necessidades
            'cad_status' => 'Cadastral',
            'vis_status' => 'Visita Técnica',
            'legal_status_consulta_prev' => 'Consulta Prévia',
            'evtl_status' => 'EVTL',
            'evtl_recebido_em' => 'EVTL recebido em',
            'anexo_evtl' => 'Anexo EVTL',
            'dados_engenharia' => 'Necessidade específica da engenharia',

            // Projetos e documentação
            'anexo_matricula_iptu' => 'Matrícula + IPTU',
            'anexo_habite_se' => 'Habite-se',
            'anexo_avcb' => 'AVCB',
            'anexo_projeto' => 'Projeto',
            'anexo_convencao_condominio' => 'Convenção do condomínio',
            'anexo_regime_interno' => 'Regime Interno',
            'anexo_normas_gerais' => 'Normas Gerais',
            'anexo_outros_documentos' => 'Outros documentos',
        ];

        $faltantes = [];

        foreach ($campos as $campo => $label) {
            if (
                in_array($campo, ['evtl_recebido_em', 'anexo_evtl'], true) &&
                ! in_array($data['evtl_status'] ?? null, ['sim', 'existente'], true)
            ) {
                continue;
            }

            if (in_array($campo, ['nome_contato', 'contato'], true) && (($data['cad_status'] ?? null) !== 'sim')) {
                continue;
            }

            $valor = $data[$campo] ?? null;

            $vazio = match (true) {
                is_array($valor) => empty(array_filter($valor, fn ($item) => filled($item))),
                is_bool($valor) => false,
                default => blank($valor),
            };

            if ($vazio) {
                $faltantes[] = $label;
            }
        }

        return $faltantes;
    }

    protected function montarResumoCamposPendentes(array $faltantes, int $limite = 6): string
    {
        if (empty($faltantes)) {
            return '';
        }

        $iniciais = array_slice($faltantes, 0, $limite);
        $restantes = count($faltantes) - count($iniciais);

        $html = 'Alguns campos ainda não foram preenchidos:<br><br>';
        $html .= '• '.implode('<br>• ', array_map('e', $iniciais));

        if ($restantes > 0) {
            $html .= '<br><br><span style="opacity:.85;">e mais '.$restantes.' campo'.($restantes > 1 ? 's' : '').', entre outros.</span>';
        }

        return $html;
    }

    public function confirmarSalvamentoMesmoAssim(): void
    {
        $this->save(true);
    }

    protected function persistSave(array $data): void
    {

        $cidade = Cidade::find($data['cidade_id'] ?? null);
        $estado = Estado::find($data['estado_id'] ?? null);

        $cidadeUf = trim(collect([
            $cidade?->nome,
            $estado?->sigla,
        ])->filter()->implode('/'));

        $logradouro = collect([
            $data['rua'] ?? null,
            $data['numero'] ?? null,
        ])->filter()->implode(', ');

        $enderecoCompleto = collect([
            $logradouro ?: null,
            $data['complemento'] ?? null,
            $data['bairro'] ?? null,
            $cidadeUf ?: null,
        ])->filter()->implode(' - ');

        $this->record->update([
            'status_comite' => $data['status_comite'] ?? null,
            'codigo' => $data['codigo'] ?? null,
            'nome' => $data['nome'] ?? null,
            'marca' => $data['marca'] ?? null,
            'numero_loja' => $data['numero_loja'] ?? null,
            'pin_google' => $data['pin_google'] ?? null,
            'link_docs' => $data['link_docs'] ?? null,
            'cep' => $data['cep'] ?? null,
            'rua' => $data['rua'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade_id' => $data['cidade_id'] ?? null,
            'estado_id' => $data['estado_id'] ?? null,
            'pais_id' => $data['pais_id'] ?? null,
            'endereco' => $enderecoCompleto,
            'contato_corretor' => $data['contato_corretor'] ?? null,
            'area_academia' => $data['area_academia'] ?? null,
            'n_pisos' => $data['n_pisos'] ?? null,
            'configuracao_academia' => $data['configuracao_academia'] ?? null,
            'pe_direito' => $data['pe_direito'] ?? null,
            'n_vagas_livres' => $data['n_vagas_livres'] ?? null,
            'tipo_de_loja' => $data['tipo_de_loja'] ?? null,
            'empreendimento' => $data['empreendimento'] ?? null,
            'locacao' => $data['locacao'] ?? null,
            'tipo_imovel' => $data['tipo_imovel'] ?? null,
            'modelo_entrega_p' => $data['modelo_entrega_p'] ?? null,
            'data_entrega_shell' => $data['data_entrega_shell'] ?? null,
            'imovel_pronto' => $data['imovel_pronto'] ?? false,
            'relocation' => $data['relocation'] ?? false,
            'aluguel_cto' => MoneyInput::parse($data['aluguel_cto'] ?? null),
            'carencia' => $data['carencia'] ?? null,
            'multa_contrato' => $data['multa_contrato'] ?? null,
            'obs_aluguel' => $data['obs_aluguel'] ?? null,
            'nome_contato' => $data['nome_contato'] ?? null,
            'contato' => $data['contato'] ?? null,
            'cad_status' => $data['cad_status'] ?? null,
            'vis_status' => $data['vis_status'] ?? null,
            'legal_status_consulta_prev' => $data['legal_status_consulta_prev'] ?? null,
            'evtl_status' => $data['evtl_status'] ?? null,
            'evtl_recebido_em' => $data['evtl_recebido_em'] ?? null,
            'dados_engenharia' => $data['dados_engenharia'] ?? null,
            'pontos_atencao' => $data['pontos_atencao'] ?? null,

            'anexo_evtl' => $data['anexo_evtl'] ?? [],
            'anexo_matricula_iptu' => $data['anexo_matricula_iptu'] ?? [],
            'anexo_habite_se' => $data['anexo_habite_se'] ?? [],
            'anexo_avcb' => $data['anexo_avcb'] ?? [],
            'anexo_projeto' => $data['anexo_projeto'] ?? [],
            'anexo_convencao_condominio' => $data['anexo_convencao_condominio'] ?? [],
            'anexo_regime_interno' => $data['anexo_regime_interno'] ?? [],
            'anexo_normas_gerais' => $data['anexo_normas_gerais'] ?? [],
            'anexo_outros_documentos' => $data['anexo_outros_documentos'] ?? [],
        ]);

        if (! empty($data['etapas']) && is_array($data['etapas'])) {
            $this->record->etapas()->sync($data['etapas']);
        }

        $taskCategoryId = TaskCategory::where('name', 'Visita Técnica')->value('id');

        if (! $taskCategoryId) {
            Notification::make()
                ->title('Categoria "Visita Técnica" não encontrada')
                ->danger()
                ->send();

            return;
        }

        $taskVisitaTecnica = Task::query()
            ->where('projeto_id', $this->record->id)
            ->where('task_category_id', $taskCategoryId)
            ->first();

        if (($data['vis_status'] ?? null) === 'sim') {
            $responsaveisIds = $data['visita_tecnica_user_ids'] ?? User::query()
                ->whereIn('email', [
                    'talita.carmona@bioritmo.com.br',
                    'talita.soares@smartfit.com',
                ])
                ->pluck('id')
                ->toArray();

            if (empty($responsaveisIds)) {
                Notification::make()
                    ->title('Nenhum responsável padrão encontrado')
                    ->danger()
                    ->send();

                return;
            }

            $setorId = Setor::query()
                ->where('setor', 'Obras')
                ->value('id');

            if (! $setorId) {
                Notification::make()
                    ->title('Setor "Obras" não encontrado')
                    ->danger()
                    ->send();

                return;
            }

            $marcaNome = blank($data['marca'] ?? null) ? 'A DEFINIR' : $data['marca'];

            $marca = Marca::firstOrCreate(
                ['nome' => $marcaNome],
                ['nome' => $marcaNome]
            );

            $nomeProjeto = $this->record->nome ?? 'Novo ponto';
            $enderecoProjeto = $this->record->endereco ?: 'Endereço não informado';

            $dadosTask = [
                'projeto_id' => $this->record->id,
                'title' => 'Realizar visita técnica - '.$nomeProjeto,
                'description' => 'Solicitação feita para realizar a visita técnica do ponto.'
                    ."\n\nProjeto ID: {$this->record->id}"
                    ."\nNome do ponto: {$nomeProjeto}"
                    ."\nEndereço: {$enderecoProjeto}",
                'task_category_id' => $taskCategoryId,
                'sigla' => null,
                'marca_id' => $marca->id,
                'created_by' => Auth::id(),
                'setor_id' => $setorId,
                'prazo' => null,
                'dias_corridos' => 0,
                'inicio' => $taskVisitaTecnica?->inicio ?? now()->toDateString(),
                'termino_programado' => null,
                'status' => 'pendente',
            ];

            if ($taskVisitaTecnica) {
                if ($taskVisitaTecnica->status !== 'em_andamento') {
                    Notification::make()
                        ->title('A tarefa de visita técnica não pode ser atualizada')
                        ->body('A tarefa já existe e só pode ser alterada quando estiver com status "em andamento".')
                        ->warning()
                        ->send();

                    return;
                }

                $taskVisitaTecnica->update($dadosTask);
                $taskVisitaTecnica->responsaveis()->sync($responsaveisIds);
            } else {
                $taskVisitaTecnica = Task::create($dadosTask);
                $taskVisitaTecnica->responsaveis()->sync($responsaveisIds);
            }
        } else {
            if ($taskVisitaTecnica) {
                if ($taskVisitaTecnica->status !== 'pendente') {
                    Notification::make()
                        ->title('A tarefa de visita técnica não pode ser cancelada')
                        ->body('A tarefa existente só pode ser cancelada quando estiver com status "pendente".')
                        ->warning()
                        ->send();

                    return;
                }

                $taskVisitaTecnica->update([
                    'status' => 'cancelada',
                ]);

                $taskVisitaTecnica->responsaveis()->sync([]);
            }
        }

        Notification::make()
            ->title('Ponto atualizado com sucesso.')
            ->success()
            ->send();

        $this->redirect('/admin/dashboard-comercial');
    }

    public function save(bool $forcar = false): void
    {
        $data = $this->form->getState();

        if (! $forcar) {
            $faltantes = $this->getCamposPendentes($data);

            if (! empty($faltantes)) {
                Notification::make()
                    ->title('Existem campos pendentes')
                    ->body($this->montarResumoCamposPendentes($faltantes))
                    ->warning()
                    ->persistent()
                    ->actions([
                        Action::make('continuar')
                            ->label('Salvar mesmo assim')
                            ->button()
                            ->dispatch('confirmar-salvamento-mesmo-assim'),

                        Action::make('fechar')
                            ->label('Fechar')
                            ->close(),
                    ])
                    ->send();

                return;
            }
        }

        $this->persistSave($data);
    }
}
