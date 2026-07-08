<?php

namespace App\Filament\Resources;

use App\Filament\Components\Forms\DownloadPdfButton;
use App\Filament\Resources\ProjetoResource\Pages;
use App\Filament\Resources\ProjetoResource\RelationManagers\ProspeccaoRelationManager;
use App\Filament\Tables\Actions\AvancoEtapa;
use App\Filament\Tables\Actions\ReuniaoComiteAction;
use App\Filament\Tables\Actions\ViabilidadeAction;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Marca;
use App\Models\Pais;
use App\Models\Pipe;
use App\Models\Projeto;
use App\Models\Prospeccao;
use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use App\Support\DateCalc;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ProjetoResource extends Resource
{
    protected static ?string $model = Projeto::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Projetos';

    protected static ?string $modelLabel = 'Projeto';

    protected static ?string $slug = 'projetos';

    protected static ?string $breadcrumb = 'Projetos';

    protected static ?string $pluralModelLabel = 'Lista de Projetos';

    protected static ?int $navigationSort = 3;

    protected static string|null|UnitEnum $navigationGroup = 'Outros';

    protected static function squadUserOptions(string $role): array
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', [$role, 'Gestor']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informações do Projeto')
                    ->description('*Campos obrigatórios')
                    ->schema([
                        Section::make('Prospecção')
                            ->schema([
                                Section::make('Informações básicas do Ponto')
                                    ->schema([
                                        TextInput::make('nome')
                                            ->label('Nome do Ponto')
                                            ->required()
                                            ->maxLength(255)
                                            ->validationMessages([
                                                'required' => 'O nome é obrigatório.',
                                            ]),
                                        TextInput::make('codigo')
                                            ->label('Código')
                                            ->required()
                                            ->visible(fn ($record) => $record !== null),
                                        TextInput::make('nova_sigla')
                                            ->maxLength(255),
                                        TextInput::make('sigla')
                                            ->maxLength(255),
                                        TextInput::make('inscricao_estadual')
                                            ->label('Inscrição Estadual')
                                            ->maxLength(255),
                                        Select::make('user_id')
                                            // ->required()
                                            ->label('Responsável')
                                            ->options(
                                                User::whereNotNull('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => auth()->id())
                                            ->disabled()
                                            ->dehydrated(),

                                        Select::make('etapas')
                                            ->label('Etapas do projeto')
                                            ->relationship(
                                                name: 'etapas',
                                                titleAttribute: 'nome',
                                                modifyQueryUsing: function ($query) {
                                                    $order = [
                                                        'Prospecção',
                                                        'Reunião de comitê',
                                                        'Viabilidade',
                                                        'Briefing e Layout',
                                                        'Ordem de investimento',
                                                        'Contrato',
                                                        'Projetos de obra',
                                                        'Orçamentos e equalização',
                                                    ];

                                                    // Usa FIELD para ordenar na sequência definida
                                                    $query->orderByRaw("FIELD(nome, '".implode("','", $order)."')");
                                                }
                                            )
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->reactive()
                                            // ->required()
                                            ->validationMessages([
                                                'required' => 'A etapa é obrigatória.',
                                            ])
                                            ->default(function () {
                                                $user = auth()->user();

                                                if ($user->hasRole('super_admin')) {
                                                    return [];
                                                }

                                                if ($user->hasRole('Comercial')) {
                                                    $etapaProspec = Etapa::where('nome', 'Prospecção')->first();
                                                    if ($etapaProspec) {
                                                        return [$etapaProspec->id];
                                                    }
                                                }

                                                return [];
                                            }),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'Em processo' => 'Em processo',
                                                'Obras' => 'Obras',
                                                'Inaugurada' => 'Inaugurada',
                                                'Cancelada' => 'Cancelada',
                                                'Stand-by' => 'Stand-by',
                                                'Deletar comercial' => 'Deletar comercial',
                                            ])
                                            ->native(false)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('link_matterport')
                                            ->maxLength(255)
                                            ->url(),
                                        TextInput::make('crono_revisado')
                                            ->maxLength(255),

                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Localização do Ponto')
                                    ->schema([
                                        TextInput::make('cep')
                                            ->label('CEP')
                                            ->mask('99999-999')
                                            ->reactive()
                                            ->helperText('Informe o CEP para atualizar as informações de localização')
                                            ->validationMessages([
                                                'required' => 'O CEP é obrigatório.',
                                            ])
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                // Checa CEP válido (pode fazer regex aqui ou só chamar direto)
                                                if (preg_match('/^\d{5}-\d{3}$/', $state)) {
                                                    $cepLimpo = str_replace('-', '', $state);
                                                    $url = "https://viacep.com.br/ws/{$cepLimpo}/json/";

                                                    $response = file_get_contents($url);
                                                    if ($response) {
                                                        $data = json_decode($response, true);

                                                        if (empty($data['erro'])) {
                                                            $set('rua', $data['logradouro'] ?? '');
                                                            $set('bairro', $data['bairro'] ?? '');

                                                            // Pais fixo Brasil
                                                            $paisId = Pais::where('nome', 'Brasil')->value('id');
                                                            if ($paisId) {
                                                                $set('pais_id', $paisId);
                                                            }

                                                            $mapaEstados = [
                                                                'AC' => 'Acre',
                                                                'AL' => 'Alagoas',
                                                                'AP' => 'Amapá',
                                                                'AM' => 'Amazonas',
                                                                'BA' => 'Bahia',
                                                                'CE' => 'Ceará',
                                                                'DF' => 'Distrito Federal',
                                                                'ES' => 'Espírito Santo',
                                                                'GO' => 'Goiás',
                                                                'MA' => 'Maranhão',
                                                                'MT' => 'Mato Grosso',
                                                                'MS' => 'Mato Grosso do Sul',
                                                                'MG' => 'Minas Gerais',
                                                                'PA' => 'Pará',
                                                                'PB' => 'Paraíba',
                                                                'PR' => 'Paraná',
                                                                'PE' => 'Pernambuco',
                                                                'PI' => 'Piauí',
                                                                'RJ' => 'Rio de Janeiro',
                                                                'RN' => 'Rio Grande do Norte',
                                                                'RS' => 'Rio Grande do Sul',
                                                                'RO' => 'Rondônia',
                                                                'RR' => 'Roraima',
                                                                'SC' => 'Santa Catarina',
                                                                'SP' => 'São Paulo',
                                                                'SE' => 'Sergipe',
                                                                'TO' => 'Tocantins',
                                                            ];

                                                            $nomeEstado = $mapaEstados[$data['uf']] ?? null;

                                                            if ($nomeEstado) {
                                                                $estadoId = Estado::where('pais_id', $paisId)
                                                                    ->where('nome', $nomeEstado)
                                                                    ->value('id');

                                                                if ($estadoId) {
                                                                    $set('estado_id', $estadoId);

                                                                    $cidadeId = Cidade::where('estado_id', $estadoId)
                                                                        ->where('nome', $data['localidade'])
                                                                        ->value('id');

                                                                    if ($cidadeId) {
                                                                        $set('cidade_id', $cidadeId);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }),
                                        TextInput::make('telefone')
                                            ->label('Telefone')
                                            ->mask('(99) 99999-9999')
                                            ->maxLength(255),

                                        TextInput::make('numero')
                                            ->label('Número')
                                            ->reactive()
                                            ->numeric()
                                            ->maxLength(20)
                                            ->validationMessages([
                                                'required' => 'O Número é obrigatório.',
                                            ])
                                            ->afterStateUpdated(function ($state, callable $get) {
                                                $cep = $get('cep');

                                                if ($cep) {
                                                    $existing = Projeto::where('cep', $cep)
                                                        ->where('numero', $state)
                                                        ->get();

                                                    if ($existing->isNotEmpty()) {
                                                        $listaProjetos = $existing->map(
                                                            fn ($projeto) => "{$projeto->nome} (Código {$projeto->codigo})"
                                                        )->implode('<br>');

                                                        Notification::make()
                                                            ->title('Endereço já cadastrado')
                                                            ->body("Já existem prospecções neste endereço:\n{$listaProjetos}")
                                                            ->warning()
                                                            ->persistent()
                                                            ->actions([
                                                                Action::make('ver')
                                                                    ->label('Ver Projetos')
                                                                    ->url(route('filament.admin.resources.projetos.index')),
                                                                Action::make('continuar')
                                                                    ->label('Cadastrar mesmo assim')
                                                                    ->close(),
                                                            ])
                                                            ->send();
                                                    }
                                                }
                                            }),

                                        TextInput::make('complemento')
                                            ->label('Complemento')
                                            ->columnSpan(1),

                                        TextInput::make('rua')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('bairro')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated(true),

                                        Select::make('pais_id')
                                            // ->required()
                                            ->relationship('pais', 'nome')
                                            ->reactive()
                                            ->disabled()
                                            ->dehydrated(true),

                                        Select::make('estado_id')
                                            // ->required()
                                            ->relationship('estado', 'nome')
                                            ->reactive()
                                            ->disabled()
                                            ->dehydrated(true),

                                        Select::make('cidade_id')
                                            // ->required()
                                            ->relationship('cidade', 'nome')
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('pin_google')
                                            ->label('Pin do Google Maps')
                                            ->url()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Imagens do Ponto')
                                    ->schema([
                                        FileUpload::make('imagem_ponto')
                                            ->label('Imagem do Ponto')
                                            ->image()
                                            ->multiple()
                                            ->imagePreviewHeight('600')
                                            ->maxSize(60000)
                                            ->directory('pontos')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->openable()
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => $file->hashName())
                                            ->enableReordering()
                                            ->hint('Máx 60MB por imagem')
                                            ->rules(['mimes:jpg,jpeg,png,webp'])
                                            ->validationMessages([
                                                'mimes' => 'O arquivo deve ser uma imagem válida (jpg, jpeg, png ou webp).',
                                                'max' => 'O tamanho do arquivo não pode ultrapassar 60 MB.',
                                            ])
                                            ->columnSpan([
                                                'default' => 1, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Informações do Proprietário do Ponto')
                                    ->schema([
                                        Select::make('tipo_entrada')
                                            ->label('Tipo de Entrada')
                                            ->options([
                                                'Prospecção de Rua' => 'Prospecção de Rua',
                                                'Email' => 'Email',
                                                'Proprietário' => 'Proprietário',
                                                'Indicação Interna' => 'Indicação Interna',
                                            ])
                                            ->native(false)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('nome_contato')
                                            ->placeholder('Digite o nome do contato')
                                            ->maxLength(255)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('contato')
                                            ->label('Telefone do contato')
                                            ->mask('(99) 99999-9999')
                                            ->maxLength(255)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Imóvel')
                                    ->schema([
                                        Toggle::make('relocation')
                                            ->label('Relocation ?')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Toggle::make('imovel_pronto')
                                            ->label('Imóvel pronto ?')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        DatePicker::make('data_entrega_shell')
                                            ->label('Data de Entrega Shell')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Links e Observações')
                                    ->schema([
                                        TextInput::make('link_docs')
                                            ->label('Link Docs')
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('link_construct_in')
                                            ->label('Link Construct In')
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Textarea::make('observacoes_ponto')
                                            ->label('Observações do Ponto')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 2,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Documentos')
                                    ->schema([
                                        FileUpload::make('anexos')
                                            ->label('Anexos')
                                            ->multiple()
                                            ->downloadable()
                                            ->openable()
                                            ->directory('projetos/anexos')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 2,      // no desktop continua 1
                                            ]),
                                        FileUpload::make('anexo_proposta_comercial')
                                            ->label('Proposta Comercial')
                                            ->directory('projetos/propostas')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_proposta_comercial_comentario')
                                            ->label('Comentário da Proposta')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_contrato_assinado')
                                            ->label('Contrato Assinado')
                                            ->directory('projetos/contratos')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_contrato_assinado_comentario')
                                            ->label('Comentário do Contrato')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_pmo_cronograma')
                                            ->label('Cronograma')
                                            ->directory('projetos/cronograma')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('comentario_pmo_cronograma')
                                            ->label('Comentário do cronograma')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_pmo_termo_abertura')
                                            ->label('Termo de Abertura')
                                            ->directory('projetos/termoabertura')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('comentario_pmo_termo_abertura')
                                            ->label('Comentário do Termo de Abertura')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_planejamento_plano')
                                            ->label('Planejamento do Plano')
                                            ->directory('projetos/planejamentoplano')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('planejamento_plano_comentario')
                                            ->label('Comentário do Planejamento do Plano')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_planejamento_estudo')
                                            ->label('Planejamento do Plano de Estudo')
                                            ->directory('projetos/planejamentoestudo')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('planejamento_estudo_comentario')
                                            ->label('Comentário do Planejamento do Estudo')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_consulta_previa')
                                            ->label('Consulta Prévia')
                                            ->directory('projetos/planejamentoestudo')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_consulta_previa_comentario')
                                            ->label('Comentário da consulta Prévia')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_estudoviabilidade')
                                            ->label('Estudo Viabilidade')
                                            ->directory('projetos/planejamentoestudo')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_estudoviabilidade_comentario')
                                            ->label('Comentário do estudo viabilidade')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_visita_tecnica')
                                            ->label('Visita Técnica')
                                            ->directory('projetos/planejamentoestudo')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_visita_tecnica_comentario')
                                            ->label('Comentário da visita Técnica')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        FileUpload::make('anexo_projetos_adicionais')
                                            ->label('Projeto Adicionais')
                                            ->directory('projetos/planejamentoestudo')
                                            ->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->multiple()
                                            ->openable()
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        RichEditor::make('anexo_projetos_adicionais_comentario')
                                            ->label('Comentário dos Projetos Adicionais')
                                            ->maxLength(5000)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(fn () => true),
                            ])
                            ->columns(1)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Status do processo')
                            ->schema([
                                Select::make('marca')
                                    ->label('Marca')
                                    ->options(
                                        Marca::query()
                                            ->whereNotNull('nome')
                                            ->where('nome', '!=', '')
                                            ->distinct()
                                            ->orderBy('nome')
                                            ->pluck('nome', 'nome')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('escopo')
                                    ->label('Escopo')
                                    ->options([
                                        'EXPANSÃO' => 'EXPANSÃO',
                                        'AQUISIÇÃO' => 'AQUISIÇÃO',
                                        'RELOCATION' => 'RELOCATION',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('pipeline')
                                    ->label('Pipe / Land')
                                    ->options(
                                        Pipe::query()
                                            ->whereNotNull('pipeline')
                                            ->where('pipeline', '!=', '')
                                            ->distinct()
                                            ->orderBy('pipeline')
                                            ->pluck('pipeline', 'pipeline')
                                    )
                                    ->searchable()
                                    ->preload(),

                            ])
                            ->columns(3)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Squad')
                            ->schema([
                                Select::make('gerente_geral_id')
                                    ->label('Gerente Geral')
                                    ->options(fn (): array => \App\Models\User::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Selecione o Gerente Geral')
                                    ->columnSpan(['default' => 2, 'lg' => 2]),
                                Select::make('resp_pmo')
                                    ->label('Responsável PMO')
                                    ->options(fn (): array => self::squadUserOptions('PMO'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                Select::make('resp_com')
                                    ->label('Responsável Comercial')
                                    ->options(fn (): array => self::squadUserOptions('Comercial'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                Select::make('resp_arq')
                                    ->label('Responsável Arquitetura')
                                    ->options(fn (): array => self::squadUserOptions('Arquitetura'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                Select::make('resp_eng')
                                    ->label('Responsável Engenharia')
                                    ->options(fn (): array => self::squadUserOptions('Engenharia'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                Select::make('status_comite')
                                    ->label('Status Comitê')
                                    ->options([
                                        '01 - INAUGURADO' => '01 - INAUGURADO',
                                        '02 - ASSINADO' => '02 - ASSINADO',
                                        '03 - APROVADO' => '03 - APROVADO',
                                        '04 - APROVADO COMO LOCALIZAÇÃO' => '04 - APROVADO COMO LOCALIZAÇÃO',
                                        '04 - EM VALIDAÇÃO' => '04 - EM VALIDAÇÃO',
                                        '05 - EM VALIDAÇÃO' => '05 - EM VALIDAÇÃO',
                                        '05 - MINUTA' => '05 - MINUTA',
                                        '06 - EM NEGOCIAÇÃO' => '06 - EM NEGOCIAÇÃO',
                                        '07 - MINUTA' => '07 - MINUTA',
                                        '07 - ON HOLD' => '07 - ON HOLD',
                                        '08 - REPROVADO' => '08 - REPROVADO',
                                        '09 - CAIU' => '09 - CAIU',
                                        '10 - DISTRATADO' => '10 - DISTRATADO',
                                        '11 - A APRESENTAR' => '11 - A APRESENTAR',
                                        '3 - ASSINADO' => '3 - ASSINADO',
                                        'APROVADO' => 'APROVADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                            ])
                            ->columns(4)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Comercial')
                            ->schema([
                                Select::make('status_imovel')
                                    ->label('Status Imóvel')
                                    ->options([
                                        'N/A' => 'N/A',
                                        'OBRA PP' => 'OBRA PP',
                                        'OBRA SF' => 'OBRA SF',
                                        'PRONTO' => 'PRONTO',
                                        'VALIDAÇÃO ENG' => 'VALIDAÇÃO ENG',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                DatePicker::make('prazo_inicio')
                                    ->label('Prazo para início do Projeto')
                                    ->live()  // MUITO importante no v3
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()  // MUITO importante no v3
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                        if (blank($state)) {
                                            $set('cad_plan_inicio', null);
                                            $set('vis_plan_inicio', null);

                                            return;
                                        }
                                        // Normaliza a data independente de vir como string ou DateTime
                                        $d = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        // seta +1 dia no formato que o segundo campo espera
                                        $set('cad_plan_inicio', $d->addDay()->toDateString()); // Y-m-d
                                        $set('vis_plan_inicio', $d->addDay()->toDateString()); // Y-m-d
                                    }),
                                Select::make('status_contrato')
                                    ->label('Status Contrato')
                                    ->options([
                                        'ASSINADO' => 'ASSINADO',
                                        'EM ASSINATURA' => 'EM ASSINATURA',
                                        'MINUTA' => 'MINUTA',
                                        'NEGOCIAÇÃO' => 'NEGOCIAÇÃO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                DatePicker::make('data_ass_contrato')
                                    ->label('Data de assinatura do contrato')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                /*Forms\Components\DatePicker::make('entrega_projeto')
                                    ->label('Prazo para entrega do Projeto')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),*/
                            ])
                            ->columns(4)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Viabilidade')
                            ->schema([
                                Section::make('Cadastral')
                                    ->schema([
                                        DatePicker::make('cad_plan_inicio')
                                            ->label('Planejado Início')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                                // se limpou o início, limpa o fim
                                                if (blank($state)) {
                                                    $set('cad_plan_fim', null);

                                                    return;
                                                }

                                                // pegue o valor bruto do prazo (sem cast!)
                                                $diasRaw = $get('cad_plan_dias');

                                                // se o prazo ainda não foi informado, mantenha o fim nulo
                                                if (blank($diasRaw)) {
                                                    $set('cad_plan_fim', null);

                                                    return;
                                                }

                                                $ini = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                $dias = (int) $diasRaw;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // padrão: fim = início + N dias
                                                // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                                $set('cad_plan_fim', $ini->addDays($dias)->toDateString());
                                            }),
                                        TextInput::make('cad_plan_dias')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || ! is_numeric($state)) {
                                                    $set('cad_plan_fim', null);

                                                    return;
                                                }

                                                $ini = $get('cad_plan_inicio');
                                                if (blank($ini)) {
                                                    $set('cad_plan_fim', null);

                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // aplica o cálculo final
                                                $set('cad_plan_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        /*
                                        TextInput::make('cad_plan_dias')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $ini = $get('cad_plan_inicio');

                                                // se início vazio ou prazo não informado, limpa o fim
                                                if (blank($ini) || blank($state)) {
                                                    $set('cad_plan_fim', null);
                                                    return;
                                                }

                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                $set('cad_plan_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        */
                                        DatePicker::make('cad_plan_fim')
                                            ->label('Planejado Fim')
                                            ->readonly()
                                            // ->extraInputAttributes([
                                            // 'class' => 'bg-black text-white',
                                            // ])
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            // ->extraInputAttributes([
                                            //       'class' => 'input-black dark:input-black-dark', // sua classe
                                            // ])
                                            ->live()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        DatePicker::make('cad_rea_inicio')
                                            ->label('Realizado Início')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                                // se limpou o início, limpa o fim
                                                if (blank($state)) {
                                                    $set('cad_rea_fim', null);

                                                    return;
                                                }

                                                // pegue o valor bruto do prazo (sem cast!)
                                                $diasRaw = $get('cad_prazo');

                                                // se o prazo ainda não foi informado, mantenha o fim nulo
                                                if (blank($diasRaw)) {
                                                    $set('cad_rea_fim', null);

                                                    return;
                                                }

                                                $ini = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                $dias = (int) $diasRaw;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // padrão: fim = início + N dias
                                                // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                                $set('cad_rea_fim', $ini->addDays($dias)->toDateString());
                                            }),

                                        TextInput::make('cad_prazo')
                                            ->label('Prazo')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || ! is_numeric($state)) {
                                                    $set('cad_rea_fim', null);

                                                    return;
                                                }

                                                $ini = $get('cad_rea_inicio');
                                                if (blank($ini)) {
                                                    $set('cad_rea_fim', null);

                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // aplica o cálculo final
                                                $set('cad_rea_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        /*
                                        TextInput::make('cad_prazo')
                                            ->label('Prazo')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $ini = $get('cad_rea_inicio');

                                                // se início vazio ou prazo não informado, limpa o fim
                                                if (blank($ini) || blank($state)) {
                                                    $set('cad_rea_fim', null);
                                                    return;
                                                }

                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                $set('cad_rea_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        */
                                        DatePicker::make('cad_rea_fim')
                                            ->label('Realizado Fim')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Select::make('cad_status')
                                            ->label('Status')
                                            ->options([
                                                'CONCLUÍDO' => 'CONCLUÍDO',
                                                'EM ANDAMENTO' => 'EM ANDAMENTO',
                                                'N/A' => 'N/A',
                                                'NÃO INICIADO' => 'NÃO INICIADO',
                                                'AGENDADO' => 'AGENDADO',
                                                'PENDÊNCIAS' => 'PENDÊNCIAS',
                                                'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                                'SOLICITADO' => 'SOLICITADO',
                                            ])
                                            ->searchable()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Visita Técnica')
                                    ->schema([
                                        DatePicker::make('vis_plan_inicio')
                                            ->label('Planejado Início')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                                // se limpou o início, limpa o fim
                                                if (blank($state)) {
                                                    $set('vis_plan_fim', null);

                                                    return;
                                                }

                                                // pegue o valor bruto do prazo (sem cast!)
                                                $diasRaw = $get('vis_plan_dias');

                                                // se o prazo ainda não foi informado, mantenha o fim nulo
                                                if (blank($diasRaw)) {
                                                    $set('vis_plan_fim', null);

                                                    return;
                                                }

                                                $ini = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                $dias = (int) $diasRaw;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // padrão: fim = início + N dias
                                                // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                                $set('vis_plan_fim', $ini->addDays($dias)->toDateString());
                                            }),
                                        TextInput::make('vis_plan_dias')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || ! is_numeric($state)) {
                                                    $set('vis_plan_fim', null);

                                                    return;
                                                }

                                                $ini = $get('vis_plan_inicio');
                                                if (blank($ini)) {
                                                    $set('vis_plan_fim', null);

                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // aplica o cálculo final
                                                $set('vis_plan_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        /*
                                        TextInput::make('vis_plan_dias')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || !is_numeric($state)) {
                                                    $set('vis_plan_fim', null);
                                                    return;
                                                }

                                                $ini = $get('vis_plan_inicio');
                                                if (blank($ini)) {
                                                    $set('vis_plan_fim', null);
                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                // aplica o cálculo final
                                                $set('vis_plan_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                        */
                                        DatePicker::make('vis_plan_fim')
                                            ->label('Planejado Fim')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        DatePicker::make('vis_rea_inicio')
                                            ->label('Realizado Início')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 3
                                                'lg' => 1,      // no desktop 1
                                            ])
                                            ->afterStateHydrated(function ($state, Set $set, $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                $visita = RelatorioVisitaTecnica::where('projeto_id', $record->id)->first();

                                                if ($visita?->iniciado_em) {
                                                    $set('vis_rea_inicio', CarbonImmutable::parse($visita->iniciado_em)->toDateString());
                                                }
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                                if (blank($state)) {
                                                    $set('vis_rea_fim', null);

                                                    return;
                                                }

                                                $diasRaw = $get('vis_prazo');
                                                if (blank($diasRaw)) {
                                                    $set('vis_rea_fim', null);

                                                    return;
                                                }

                                                $ini = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                $dias = max(0, (int) $diasRaw);

                                                // fim = início + N dias
                                                $set('vis_rea_fim', $ini->addDays($dias)->toDateString());
                                            }),
                                        TextInput::make('vis_prazo')
                                            ->label('Prazo')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || ! is_numeric($state)) {
                                                    $set('vis_rea_fim', null);

                                                    return;
                                                }

                                                $ini = $get('vis_rea_inicio');
                                                if (blank($ini)) {
                                                    $set('vis_rea_fim', null);

                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) {
                                                    $dias = 0;
                                                }

                                                // aplica o cálculo final
                                                $set('vis_rea_fim', $iniDate->addDays($dias)->toDateString());
                                            }),

                                        /*
                                        TextInput::make('vis_prazo')
                                            ->label('Prazo')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $ini = $get('vis_rea_inicio');

                                                // se início vazio ou prazo não informado, limpa o fim
                                                if (blank($ini) || blank($state)) {
                                                    $set('vis_rea_fim', null);
                                                    return;
                                                }

                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                $set('vis_rea_fim', $iniDate->addDays($dias)->toDateString());
                                            }),
                                            */
                                        DatePicker::make('vis_rea_fim')
                                            ->label('Realizado Fim')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            ->afterStateHydrated(function ($state, Set $set, $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                $visita = RelatorioVisitaTecnica::where('projeto_id', $record->id)->first();

                                                if ($visita?->concluido_em) {
                                                    $set('vis_rea_fim', CarbonImmutable::parse($visita->concluido_em)->toDateString());
                                                }
                                            })
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Select::make('vis_status')
                                            ->label('Status')
                                            ->afterStateHydrated(function ($state, Set $set, $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                $visita = RelatorioVisitaTecnica::where('projeto_id', $record->id)->first();

                                                if ($visita?->etapa_contrato) {
                                                    $set('vis_status', $visita->etapa_contrato);
                                                }
                                            })
                                            ->options([
                                                'CONCLUÍDO' => 'CONCLUÍDO',
                                                'EM ANDAMENTO' => 'EM ANDAMENTO',
                                                'N/A' => 'N/A',
                                                'NÃO INICIADO' => 'NÃO INICIADO',
                                                'AGENDADO' => 'AGENDADO',
                                                'PENDÊNCIAS' => 'PENDÊNCIAS',
                                                'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                                'SOLICITADO' => 'SOLICITADO',
                                            ])
                                            ->searchable()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        DownloadPdfButton::make('download_pdf')
                                            ->columnSpan([
                                                'default' => 3,
                                                'lg' => 1,
                                            ]),

                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                            ])
                            ->columns(1)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Briefing & Layout')
                            ->schema([
                                DatePicker::make('brief_plan')
                                    ->label('Planejado (Briefing)')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                DatePicker::make('brief_plan_lay_inicio')
                                    ->label('Planejado Layout Início')
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // limpou início -> limpa fim e ordem
                                        if (blank($state)) {
                                            $set('brief_plan_lay_fim', null);
                                            $set('ordem_planej_ini', null);

                                            return;
                                        }

                                        $diasRaw = $get('brief_plan_dias');
                                        if (blank($diasRaw)) {
                                            // sem prazo informado, zera fim e ordem
                                            $set('brief_plan_lay_fim', null);
                                            $set('ordem_planej_ini', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $dias = max(0, (int) $diasRaw);

                                        // fim = início + N dias
                                        $fim = $ini->addDays($dias);
                                        $set('brief_plan_lay_fim', $fim->toDateString());

                                        // ordem = fim + 1 dia
                                        $set('ordem_planej_ini', $fim->addDay()->toDateString());
                                    }),

                                TextInput::make('brief_plan_dias')
                                    ->label('Dias Planejados')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('brief_plan_lay_fim', null);

                                            // $set('ordem_planej_ini', null);
                                            return;
                                        }

                                        $ini = $get('brief_plan_lay_inicio');
                                        if (blank($ini)) {
                                            $set('brief_plan_lay_fim', null);
                                            $set('ordem_planej_ini', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        $fim = $iniDate->addDays($dias);
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('brief_plan_lay_fim', $iniDate->addDays($dias)->toDateString());
                                        // ordem = fim + 1 dia
                                        $set('ordem_planej_ini', $fim->addDay()->toDateString());
                                    }),
                                /*
                                TextInput::make('brief_plan_dias')
                                    ->label('Dias Planejados')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('brief_plan_lay_inicio');

                                        // sem início ou sem dias -> limpa fim e ordem
                                        if (blank($ini) || blank($state)) {
                                            $set('brief_plan_lay_fim', null);
                                            $set('ordem_planej_ini', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $ini);

                                        $dias = max(0, (int) $state);

                                        $fim = $iniDate->addDays($dias);
                                        $set('brief_plan_lay_fim', $fim->toDateString());

                                        // ordem = fim + 1 dia
                                        $set('ordem_planej_ini', $fim->addDay()->toDateString());
                                    }),
                                    */

                                DatePicker::make('brief_plan_lay_fim')
                                    ->label('Planejado Layout Fim')
                                    ->readonly() // mantenha se o fim sempre for calculado
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('ordem_planej_ini', null);

                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('ordem_planej_ini', $fim->addDay()->toDateString());
                                    }),

                                DatePicker::make('brief_real')
                                    ->label('Realizado (Briefing)')
                                    ->columnSpan([
                                        'default' => 4, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                DatePicker::make('brief_real_lay_inicio')
                                    ->label('Realizado Layout Início')
                                    ->columnSpan([
                                        'default' => 4, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('brief_real_lay_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('brief_prazo');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('brief_real_lay_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('brief_real_lay_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('brief_prazo')
                                    ->label('Prazo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('brief_real_lay_fim', null);

                                            return;
                                        }

                                        $ini = $get('brief_real_lay_inicio');
                                        if (blank($ini)) {
                                            $set('brief_real_lay_fim', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('brief_real_lay_fim', $iniDate->addDays($dias)->toDateString());
                                    }),
                                /*
                                TextInput::make('brief_prazo')
                                    ->label('Prazo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('brief_real_lay_inicio');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('brief_real_lay_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('brief_real_lay_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/

                                DatePicker::make('brief_real_lay_fim')
                                    ->label('Realizado Layout Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live(),

                                Select::make('brief_status')
                                    ->label('Status')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(4)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Ordem de Investimento')
                            ->schema([
                                DatePicker::make('ordem_planej_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // limpou início -> limpa fim e ordem
                                        if (blank($state)) {
                                            $set('ordem_planej_fim', null);
                                            $set('proj_planej_reuniao_start', null);

                                            return;
                                        }

                                        $diasRaw = $get('ordem_planejado');
                                        if (blank($diasRaw)) {
                                            // sem prazo informado, zera fim e ordem
                                            $set('ordem_planej_fim', null);
                                            $set('proj_planej_reuniao_start', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $dias = max(0, (int) $diasRaw);

                                        // fim = início + N dias
                                        $fim = $ini->addDays($dias);
                                        $set('ordem_planej_fim', $fim->toDateString());

                                        // ordem = fim + 1 dia
                                        $set('proj_planej_reuniao_start', $fim->addDay()->toDateString());
                                    }),

                                /*
                                DatePicker::make('ordem_planej_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // dispara o afterStateUpdated ao mudar
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('ordem_planej_fim', null);
                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('ordem_planejado');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('ordem_planej_fim', null);
                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) $dias = 0;

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('ordem_planej_fim', $ini->addDays($dias)->toDateString());
                                    }),*/

                                TextInput::make('ordem_planejado')
                                    ->label('Dias Planejados')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('ordem_planej_fim', null);

                                            // $set('ordem_planej_ini', null);
                                            return;
                                        }

                                        $ini = $get('ordem_planej_ini');
                                        if (blank($ini)) {
                                            $set('ordem_planej_fim', null);
                                            $set('proj_planej_reuniao_start', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        $fim = $iniDate->addDays($dias);
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('ordem_planej_fim', $iniDate->addDays($dias)->toDateString());
                                        // ordem = fim + 1 dia
                                        $set('proj_planej_reuniao_start', $fim->addDay()->toDateString());
                                    }),

                                /*
                                    TextInput::make('ordem_planejado')
                                            ->label('Planejado (Dias)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || !is_numeric($state)) {
                                                    $set('ordem_planej_fim', null);
                                                    return;
                                                }

                                                $ini = $get('ordem_planej_ini');
                                                if (blank($ini)) {
                                                    $set('ordem_planej_fim', null);
                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                // aplica o cálculo final
                                                $set('ordem_planej_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/

                                /*
                                TextInput::make('ordem_planejado')
                                    ->label('Planejado (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('ordem_planej_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('ordem_planej_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('ordem_planej_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/
                                DatePicker::make('ordem_planej_fim')
                                    ->label('Planejado Fim')
                                    ->readonly() // mantenha se o fim sempre for calculado
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('proj_planej_reuniao_start', null);

                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('proj_planej_reuniao_start', $fim->addDay()->toDateString());
                                    }),
                                /*
                                 DatePicker::make('ordem_planej_fim')
                                    ->label('Planejado Fim')
                                    ->readonly() // mantenha se o fim sempre for calculado
                                    ->extraAttributes(['style' => 'background-color:#eee;'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('proj_planej_reuniao_start', null);
                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('proj_planej_reuniao_start', $fim->addDay()->toDateString());
                                    }),*/
                                /*


                                DatePicker::make('ordem_planej_fim')
                                    ->label('Planejado Fim')
                                    ->readonly()
                                    ->extraAttributes(['style' => 'background-color:#eee;'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                                    */

                                DatePicker::make('ordem_realizado')
                                    ->label('Realizado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])

                                    ->live() // dispara o afterStateUpdated ao mudar
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('ordem_realizado_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('ordem_prazo');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('ordem_realizado_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('ordem_realizado_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('ordem_prazo')
                                    ->label('Prazo (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('ordem_realizado_fim', null);

                                            return;
                                        }

                                        $ini = $get('ordem_realizado');
                                        if (blank($ini)) {
                                            $set('ordem_realizado_fim', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('ordem_realizado_fim', $iniDate->addDays($dias)->toDateString());
                                    }),

                                /*
                                TextInput::make('ordem_prazo')
                                    ->label('Prazo (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('ordem_realizado');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('ordem_realizado_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('ordem_realizado_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/

                                DatePicker::make('ordem_realizado_fim')
                                    ->label('Realizado Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('ordem_status')
                                    ->label('Status')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                DatePicker::make('ordem_data_aprov')
                                    ->label('Data Aprovação')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('ordem_status_aprov')
                                    ->label('Status de aprovação')
                                    ->options([
                                        'APROVADO' => 'APROVADO',
                                        'EM APROVAÇÃO' => 'EM APROVAÇÃO',
                                        'N/A' => 'N/A',
                                        'REVISÃO' => 'REVISÃO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(3)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Projeto Executivo')
                            ->schema([
                                DatePicker::make('proj_planej_reuniao_start')
                                    ->label('Planej. Reunião de Start')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),

                                DatePicker::make('proj_real_reuniao_start')
                                    ->label('Realizado Reunião de Start')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),
                                DatePicker::make('proj_plan_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // limpou início -> limpa fim e ordem
                                        if (blank($state)) {
                                            $set('proj_plan_fim', null);
                                            $set('orca_reuniao_kickoff', null);

                                            return;
                                        }

                                        $diasRaw = $get('proj_plan');
                                        if (blank($diasRaw)) {
                                            // sem prazo informado, zera fim e ordem
                                            $set('proj_plan_fim', null);
                                            $set('orca_reuniao_kickoff', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $dias = max(0, (int) $diasRaw);

                                        // fim = início + N dias
                                        $fim = $ini->addDays($dias);
                                        $set('proj_plan_fim', $fim->toDateString());

                                        // ordem = fim + 1 dia
                                        $set('orca_reuniao_kickoff', $fim->addDay()->toDateString());
                                    }),
                                /*

                                DatePicker::make('proj_plan_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('proj_plan_fim', null);
                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('proj_plan');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('proj_plan_fim', null);
                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) $dias = 0;

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('proj_plan_fim', $ini->addDays($dias)->toDateString());
                                    }),*/
                                TextInput::make('proj_plan')
                                    ->label('Dias Planejados')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('proj_plan_fim', null);

                                            // $set('ordem_planej_ini', null);
                                            return;
                                        }

                                        $ini = $get('proj_plan_ini');
                                        if (blank($ini)) {
                                            $set('proj_plan_fim', null);
                                            $set('orca_reuniao_kickoff', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        $fim = $iniDate->addDays($dias);
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('proj_plan_fim', $iniDate->addDays($dias)->toDateString());
                                        // ordem = fim + 1 dia
                                        $set('orca_reuniao_kickoff', $fim->addDay()->toDateString());
                                    }),
                                /*
                                    TextInput::make('proj_plan')
                                            ->label('Prazo (Dias)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || !is_numeric($state)) {
                                                    $set('proj_plan_fim', null);
                                                    return;
                                                }

                                                $ini = $get('proj_plan_ini');
                                                if (blank($ini)) {
                                                    $set('proj_plan_fim', null);
                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                if ($dias < 0) $dias = 0;

                                                // aplica o cálculo final
                                                $set('proj_plan_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/
                                /*
                                TextInput::make('proj_plan')
                                    ->label('Planejado (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('proj_plan_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('proj_plan_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('proj_plan_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/

                                DatePicker::make('proj_plan_fim')
                                    ->label('Planejado Fim')
                                    ->readonly() // mantenha se o fim sempre for calculado
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('orca_reuniao_kickoff', null);

                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('orca_reuniao_kickoff', $fim->addDay()->toDateString());
                                    }),
                                /*

                                DatePicker::make('proj_plan_fim')
                                    ->label('Planejado Fim')
                                    ->readonly()
                                    ->extraAttributes(['style' => 'background-color:#eee;'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ]),*/

                                DatePicker::make('proj_real_ini')
                                    ->label('Realizado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('proj_real_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('proj_prazo');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('proj_real_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('proj_real_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('proj_prazo')
                                    ->label('Prazo (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('proj_real_fim', null);

                                            return;
                                        }

                                        $ini = $get('proj_real_ini');
                                        if (blank($ini)) {
                                            $set('proj_real_fim', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('proj_real_fim', $iniDate->addDays($dias)->toDateString());
                                    }),
                                /*
                                TextInput::make('proj_prazo')
                                    ->label('Prazo (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('proj_real_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('proj_real_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('proj_real_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/

                                DatePicker::make('proj_real_fim')
                                    ->label('Realizado Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ]),

                                Select::make('proj_status')
                                    ->label('Status')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(6)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Orçamentos e Contratações')
                            ->schema([
                                DatePicker::make('orca_reuniao_kickoff')
                                    ->label('Reunião Kickoff')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live(),
                                /*
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                        if (blank($state)) {
                                            $set('orca_planejado_ini', null);

                                            return;
                                        }
                                        // Normaliza a data independente de vir como string ou DateTime
                                        $d = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        // seta +1 dia no formato que o segundo campo espera
                                        $set('orca_planejado_ini', $d->addDay()->toDateString()); // Y-m-d

                                    }),*/

                                DatePicker::make('orca_planejado_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // limpou início -> limpa fim e ordem
                                        if (blank($state)) {
                                            $set('orca_planejado_fim', null);
                                            $set('data_posse', null);

                                            return;
                                        }

                                        $diasRaw = $get('orca_planejado');
                                        if (blank($diasRaw)) {
                                            // sem prazo informado, zera fim e ordem
                                            $set('orca_planejado_fim', null);
                                            $set('data_posse', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $dias = max(0, (int) $diasRaw);

                                        // fim = início + N dias
                                        $fim = $ini->addDays($dias);
                                        $set('orca_planejado_fim', $fim->toDateString());

                                        // ordem = fim + 1 dia
                                        $set('data_posse', $fim->addDay()->toDateString());
                                        $set('mes_posse', $fim->month);
                                    }),

                                /*

                                DatePicker::make('orca_planejado_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('orca_planejado_fim', null);
                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('orca_planejado');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('orca_planejado_fim', null);
                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) $dias = 0;

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('orca_planejado_fim', $ini->addDays($dias)->toDateString());
                                    }),*/
                                TextInput::make('orca_planejado')
                                    ->label('Dias Planejados')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live() // importante para disparar o afterStateUpdated
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // garante que o valor seja tratado corretamente como número
                                        $state = trim((string) $state);

                                        // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                        if ($state === '' || ! is_numeric($state)) {
                                            $set('orca_planejado_fim', null);

                                            // $set('orca_planejado_ini', null);
                                            return;
                                        }

                                        $ini = $get('orca_planejado_ini');
                                        if (blank($ini)) {
                                            $set('orca_planejado_fim', null);
                                            $set('data_posse', null);

                                            return;
                                        }

                                        // garante consistência do tipo de data
                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        // converte o texto em número inteiro real
                                        $dias = (int) $state;
                                        $fim = $iniDate->addDays($dias);
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // aplica o cálculo final
                                        $set('orca_planejado_fim', $iniDate->addDays($dias)->toDateString());
                                        // ordem = fim + 1 dia
                                        $set('data_posse', $fim->addDay()->toDateString());
                                        $set('mes_posse', $fim->month);
                                    }),

                                /*
                                TextInput::make('orca_planejado')
                                    ->label('Planejado (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('orca_planejado_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('orca_planejado_fim', null);
                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) $dias = 0;

                                        $set('orca_planejado_fim', $iniDate->addDays($dias)->toDateString());
                                    }),*/
                                DatePicker::make('orca_planejado_fim')
                                    ->label('Planejado Fim')
                                    ->readonly() // mantenha se o fim sempre for calculado
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('data_posse', null);

                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('data_posse', $fim->addDay()->toDateString());
                                        $set('mes_posse', $fim->month);
                                    }),

                                /*

                                DatePicker::make('orca_planejado_fim')
                                    ->label('Planejado Fim')
                                    ->readonly()
                                    ->extraAttributes(['style' => 'background-color:#eee;'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),*/

                                DatePicker::make('orca_real_ini')
                                    ->label('Realizado Início')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('orca_real_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('orca_prazo');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('orca_real_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('orca_real_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('orca_prazo')
                                    ->label('Prazo (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('orca_real_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('orca_real_fim', null);

                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        $set('orca_real_fim', $iniDate->addDays($dias)->toDateString());
                                    }),

                                DatePicker::make('orca_real_fim')
                                    ->label('Realizado Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('orca_status')
                                    ->label('Status')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(4)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Legalização')
                            ->schema([
                                Select::make('legal_status_consulta_prev')
                                    ->label('Status CP/EVTL Consulta Prévia')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                        'FINALIZADO' => 'FINALIZADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),

                                TextInput::make('legal_doc_posse')
                                    ->label('Documentação Posse')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),

                                DatePicker::make('legal_plan_ini')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('legal_plan_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('legal_prazo_legal');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('legal_plan_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('legal_plan_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('legal_prazo_legal')
                                    ->label('Prazo Legal (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('legal_plan_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('legal_plan_fimm', null);

                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        $set('legal_plan_fim', $iniDate->addDays($dias)->toDateString());
                                    }),

                                DatePicker::make('legal_plan_fim')
                                    ->label('Planejado Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ]),

                                DatePicker::make('legal_realizado_ini')
                                    ->label('Realizado Início')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // se limpou o início, limpa o fim
                                        if (blank($state)) {
                                            $set('legal_realizado_fim', null);

                                            return;
                                        }

                                        // pegue o valor bruto do prazo (sem cast!)
                                        $diasRaw = $get('legal_prazo');

                                        // se o prazo ainda não foi informado, mantenha o fim nulo
                                        if (blank($diasRaw)) {
                                            $set('legal_realizado_fim', null);

                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                        $dias = (int) $diasRaw;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        // padrão: fim = início + N dias
                                        // (se quiser contagem inclusiva, use addDays(max(0, $dias - 1)))
                                        $set('legal_realizado_fim', $ini->addDays($dias)->toDateString());
                                    }),

                                TextInput::make('legal_prazo')
                                    ->label('Prazo Realizado (Dias)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $ini = $get('legal_realizado_ini');

                                        // se início vazio ou prazo não informado, limpa o fim
                                        if (blank($ini) || blank($state)) {
                                            $set('legal_realizado_fim', null);

                                            return;
                                        }

                                        $iniDate = $ini instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($ini)
                                            : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                        $dias = (int) $state;
                                        if ($dias < 0) {
                                            $dias = 0;
                                        }

                                        $set('legal_realizado_fim', $iniDate->addDays($dias)->toDateString());
                                    }),

                                DatePicker::make('legal_realizado_fim')
                                    ->label('Realizado Fim')
                                    ->readonly()
                                    // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                    // ->extraInputAttributes(['style' => 'color:black'])
                                    ->live()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 2,      // no desktop continua 1
                                    ]),

                                Select::make('legal_status')
                                    ->label('Status')
                                    ->options([
                                        'CONCLUÍDO' => 'CONCLUÍDO',
                                        'EM ANDAMENTO' => 'EM ANDAMENTO',
                                        'N/A' => 'N/A',
                                        'NÃO INICIADO' => 'NÃO INICIADO',
                                        'AGENDADO' => 'AGENDADO',
                                        'PENDÊNCIAS' => 'PENDÊNCIAS',
                                        'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                                        'SOLICITADO' => 'SOLICITADO',
                                    ])
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 3,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(6)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Obras')
                            ->schema([
                                Section::make('Posse')
                                    ->schema([
                                        DatePicker::make('data_posse')
                                            ->label('Data de Posse')
                                            ->required(fn () => Auth::user()?->hasAnyRole(['PMO', 'Planejamento Estratégico', 'super_admin']))
                                            ->live()
                                            ->disabled(fn () => ! Auth::user()?->hasAnyRole(['PMO', 'Planejamento Estratégico', 'super_admin']))
                                            ->helperText(fn () => Auth::user()?->hasAnyRole(['PMO', 'Planejamento Estratégico', 'super_admin'])
                                                ? null
                                                : 'Somente PMO ou Planejamento Estratégico pode editar a Data de Posse.')
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                                if (blank($state)) {
                                                    $set('mes_posse', null);

                                                    return;
                                                }

                                                $d = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                // sempre sincroniza o mês quando a data de posse mudar (inclusive edição manual)
                                                $set('mes_posse', $d->month);
                                            })
                                            ->columnSpan([
                                                'default' => 3,
                                                'lg' => 1,
                                            ]),

                                        TextInput::make('mes_posse')
                                            ->label('Mês da Posse Oficial')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            // ->reactive()
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Select::make('posse_engenharia')
                                            ->label('Posse Engenharia')
                                            ->options([
                                                'SIM' => 'SIM',
                                                'NÃO' => 'NÃO',
                                            ])
                                            ->searchable()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Select::make('posse_legalizacao')
                                            ->label('Posse Legalização')
                                            ->options([
                                                'SIM' => 'SIM',
                                                'NÃO' => 'NÃO',
                                            ])
                                            ->searchable()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        // Forms\Components\Select::make('posse_legalizacao')
                                        //     ->label('Posse Legalização')
                                        //     ->options([
                                        //         'SIM' => 'Sim',
                                        //         'NAO' => 'Não',
                                        //     ])
                                        //     ->searchable()
                                        //     ->columnSpan([
                                        //         'default' => 3, // no celular ocupa 1
                                        //         'lg' => 1,      // no desktop continua 1
                                        //     ]),

                                        Select::make('posse_status')
                                            ->label('Status')
                                            ->options([
                                                'NÃO REALIZADO' => 'NÃO REALIZADO',
                                                'REALIZADO' => 'REALIZADO',
                                            ])
                                            ->searchable()
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('posse_comentarios')
                                            ->label('Comentários sobre Posse')
                                            ->placeholder('Escreva aqui os comentários sobre a posse...')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(5)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Execução de Obras')
                                    ->schema([
                                        DatePicker::make('inicio_obra')
                                            ->label('Prazo para início da Obra')
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                                if (blank($state)) {
                                                    // limpando quando tirar a data de início de obra
                                                    $set('data_posse', null);
                                                    $set('mes_posse', null);

                                                    return;
                                                }

                                                $d = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                // 1) iguala a data de posse à data de início da obra
                                                $set('data_posse', $d->toDateString()); // Y-m-d

                                                // 2) já calcula o mês (robusto mesmo que o hook de data_posse não dispare)
                                                $set('mes_posse', $d->month);
                                            })
                                            ->columnSpan([
                                                'default' => 2,
                                                'lg' => 1,
                                            ]),
                                        DatePicker::make('entrega_obra')
                                            ->label('Prazo para entrega da obra')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (blank($state)) {
                                                    // limpou => limpa todo o encadeamento
                                                    $set('imp_inicio', null);
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $entrega = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                                // imp_inicio = entrega_obra + 1 dia
                                                $ini = $entrega->addDay();
                                                $set('imp_inicio', $ini->toDateString());

                                                // se já existe um prazo, calcula fim e o resto em cascata
                                                $prazoRaw = $get('imp_prazo_planejado');
                                                if (filled($prazoRaw) && is_numeric($prazoRaw)) {
                                                    $dias = max(0, (int) $prazoRaw);
                                                    $fim = $ini->addDays($dias);
                                                    $set('imp_fim', $fim->toDateString());
                                                    $set('imp_mes', $fim->month);
                                                    $set('imp_ano', $fim->year);

                                                    $inaug = $fim->addDay();
                                                    $set('inauguracao', $inaug->toDateString());
                                                    $set('ano_inauguracao', $inaug->year);
                                                } else {
                                                    // sem prazo => limpa dependentes
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);
                                                }
                                            }),

                                        /*
                                    DatePicker::make('entrega_obra')
                                    ->label('Prazo para entrega da obra')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // Se por algum motivo o FIM mudar, recalcula a ORDEM
                                        if (blank($state)) {
                                            $set('imp_inicio', null);
                                            return;
                                        }

                                        $fim = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $set('imp_inicio', $fim->addDay()->toDateString());
                                    }),
                                    */

                                        TextInput::make('exec_prazo_plan')
                                            ->label('Prazo Planejado (Dias)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('exec_prazo_real')
                                            ->label('Prazo Realizado (Dias)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Implantação')
                                    ->schema([
                                        DatePicker::make('imp_inicio')
                                            ->label('Planejado Início')
                                            ->columnSpan(['default' => 4, 'lg' => 1])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                                if (blank($state)) {
                                                    // limpou início => limpa tudo que depende
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $prazoRaw = $get('imp_prazo_planejado'); // <- nome correto
                                                if (blank($prazoRaw) || ! is_numeric($prazoRaw)) {
                                                    // sem prazo => não dá para calcular o fim
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $ini = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                                $dias = max(0, (int) $prazoRaw);
                                                $fim = $ini->addDays($dias);

                                                $set('imp_fim', $fim->toDateString());
                                                $set('imp_mes', $fim->month);
                                                $set('imp_ano', $fim->year);

                                                $inaug = $fim->addDay();
                                                $set('inauguracao', $inaug->toDateString());
                                                $set('ano_inauguracao', $inaug->year);
                                            }),

                                        /*
                                    DatePicker::make('imp_inicio')
                                    ->label('Planejado Início')
                                    ->columnSpan([
                                        'default' => 4,
                                        'lg' => 1,
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set, Get $get) {
                                        // limpou início -> limpa fim e ordem
                                        if (blank($state)) {

                                            $set('imp_fim', null);
                                            return;
                                        }

                                        $diasRaw = $get('exec_prazo_plan');
                                        if (blank($diasRaw)) {
                                            // sem prazo informado, zera fim e ordem

                                            $set('imp_fim', null);
                                            return;
                                        }

                                        $ini = $state instanceof \DateTimeInterface
                                            ? CarbonImmutable::instance($state)
                                            : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                        $dias = max(0, (int) $diasRaw);

                                        // fim = início + N dias
                                        $fim = $ini->addDays($dias);
                                        $set('imp_fim', $fim->toDateString());

                                    }),
                                    */
                                        TextInput::make('imp_prazo_planejado')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan(['default' => 3, 'lg' => 1])
                                            ->live()
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $state = trim((string) $state);

                                                if ($state === '' || ! is_numeric($state)) {
                                                    // prazo inválido => limpa dependentes
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $ini = $get('imp_inicio');
                                                if (blank($ini)) {
                                                    // sem início => não dá para calcular
                                                    $set('imp_fim', null);
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', (string) $ini);

                                                $dias = max(0, (int) $state);
                                                $fim = $iniDate->addDays($dias);

                                                $set('imp_fim', $fim->toDateString());
                                                $set('imp_mes', $fim->month);
                                                $set('imp_ano', $fim->year);

                                                $inaug = $fim->addDay();
                                                $set('inauguracao', $inaug->toDateString());
                                                $set('ano_inauguracao', $inaug->year);
                                            }),

                                        /*
TextInput::make('imp_prazo_planejado')
                                            ->label('Dias Planejados')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ])
                                            ->live() // importante para disparar o afterStateUpdated
                                            ->debounce(500)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                // garante que o valor seja tratado corretamente como número
                                                $state = trim((string) $state);

                                                // se o campo foi apagado ou contém algo não numérico, limpa o fim
                                                if ($state === '' || !is_numeric($state)) {
                                                    $set('imp_fim', null);

                                                    return;
                                                }

                                                $ini = $get('imp_inicio');
                                                if (blank($ini)) {
                                                    $set('imp_fim', null);

                                                    return;
                                                }

                                                // garante consistência do tipo de data
                                                $iniDate = $ini instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($ini)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $ini);

                                                // converte o texto em número inteiro real
                                                $dias = (int) $state;
                                                $fim = $iniDate->addDays($dias);
                                                if ($dias < 0) $dias = 0;

                                                // aplica o cálculo final
                                                $set('imp_fim', $iniDate->addDays($dias)->toDateString());

                                    }),*/

                                        DatePicker::make('imp_fim')
                                            ->label('Fim Implantação')
                                            ->columnSpan(['default' => 2, 'lg' => 1])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                                if (blank($state)) {
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $d = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                                $set('imp_mes', $d->month);
                                                $set('imp_ano', $d->year);

                                                $inaug = $d->addDay();
                                                $set('inauguracao', $inaug->toDateString());
                                                $set('ano_inauguracao', $inaug->year);
                                            }),
                                        /*
                                        DatePicker::make('imp_fim')
                                            ->label('Fim Implantação')
                                            ->columnSpan([
                                                'default' => 2,
                                                'lg' => 1,
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                                if (blank($state)) {
                                                    // Limpando dependentes
                                                    $set('imp_mes', null);
                                                    $set('imp_ano', null);
                                                    $set('inauguracao', null);
                                                    $set('ano_inauguracao', null);
                                                    return;
                                                }

                                                $d = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', $state);

                                                // Calcula mês/ano da implantação (como você já fazia)
                                                $set('imp_mes', $d->month);
                                                $set('imp_ano', $d->year);

                                                // Define inauguração = imp_fim + 1 dia
                                                $inaug = $d->addDay()->toDateString(); // Y-m-d
                                                $set('inauguracao', $inaug);

                                                // Por robustez, já define também o ano aqui.
                                                // (Mesmo que o hook de 'inauguracao' normalmente já atualize)
                                                $set('ano_inauguracao', $d->addDay()->year);
                                            }),*/

                                        TextInput::make('imp_prazo_realizado')
                                            ->label('Prazo Realizado (dias)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('imp_mes')
                                            ->label('Mês')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            ->columnSpan(['default' => 2, 'lg' => 1]),

                                        TextInput::make('imp_ano')
                                            ->label('Ano')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->live()
                                            ->columnSpan(['default' => 2, 'lg' => 1]),

                                        DatePicker::make('inauguracao')
                                            ->label('Inauguração')
                                            ->live()
                                            ->afterStateUpdated(function (\DateTimeInterface|string|null $state, Set $set) {
                                                if (blank($state)) {
                                                    $set('ano_inauguracao', null);

                                                    return;
                                                }

                                                $d = $state instanceof \DateTimeInterface
                                                    ? CarbonImmutable::instance($state)
                                                    : CarbonImmutable::createFromFormat('Y-m-d', (string) $state);

                                                $set('ano_inauguracao', $d->year);
                                            }),

                                        TextInput::make('ano_inauguracao')
                                            ->label('Ano da Inauguração')
                                            ->readonly()
                                            // ->extraAttributes(['style' => 'background-color:#bbb;'])
                                            // ->extraInputAttributes(['style' => 'color:black'])
                                            ->dehydrated(),
                                    ])
                                    ->columns(3)
                                    ->grow()
                                    ->collapsible()
                                    ->collapsed(fn () => true),

                                Section::make('Risco de obra')
                                    ->schema([
                                        Toggle::make('risco_obra')
                                            ->label('Há risco de obra?')
                                            ->live(),
                                        TextInput::make('risco_obra_comentario')
                                            ->label('Comentários sobre os riscos')
                                            ->visible(fn ($get) => (bool) $get('risco_obra')),

                                    ]),

                            ])
                            ->columns(1)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Dados do Imóvel')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Select::make('tipo_imovel')
                                            ->label('Tipo de Imóvel')
                                            ->options([
                                                'bts' => 'BTS',
                                                'padrao' => 'Padrão',
                                                'construcao_smart_fit' => 'Construção DPC',
                                                'BTS' => 'BTS',
                                                'CONSTRUÇÃO SF' => 'CONSTRUÇÃO SF',
                                                'PADRÃO' => 'PADRÃO',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('endereco')
                                            ->label('Endereço')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        Select::make('empreendimento')
                                            ->label('Empreendimento')
                                            ->options([
                                                'RUA' => 'RUA',
                                                'SUPERMERCADO' => 'SUPERMERCADO',
                                                'MALL / SHOPPING' => 'MALL / SHOPPING',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(3),
                                Section::make()
                                    ->schema([
                                        Select::make('locacao')
                                            ->label('Locação')
                                            // ->reactive()
                                            ->options([
                                                'Mono usuário' => 'Mono usuário',
                                                'Multiusuário' => 'Multiusuário',
                                            ])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('aluguel_cto')
                                            ->label('Aluguel/CTO')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(99999999.99)
                                            ->step(0.01)
                                            ->prefix('R$')
                                            ->validationMessages([
                                                'numeric' => 'O valor deve ser numérico.',
                                                'min' => 'O valor não pode ser negativo.',
                                                'max' => 'Valor ultrapassa o limite permitido de R$99.999.999,99.',
                                            ])
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Textarea::make('obs_aluguel')
                                            ->label('Observações Aluguel')
                                            ->columnSpan([
                                                'default' => 2, // no celular ocupa 1
                                                'lg' => 2,      // no desktop continua 1
                                            ]),
                                    ])->columns(2),
                                Section::make()
                                    ->schema([
                                        TextInput::make('carencia')
                                            ->label('Carência (meses)')
                                            ->columnSpan([
                                                'default' => 4, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('multa_contrato')
                                            ->label('Multa Contrato')
                                            ->columnSpan([
                                                'default' => 4, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('metro_contrato')
                                            ->label('Metragem Contrato (m²)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 4, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('metro_layout_util')
                                            ->label('Metragem Layout Útil (m²)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->columnSpan([
                                                'default' => 4, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(4),
                                Section::make()
                                    ->schema([
                                        TextInput::make('pavimento')
                                            ->label('Pavimento')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('n_vagas_livres')
                                            ->label('Estacionamento')
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('tipo_de_loja')
                                            ->label('Tipo de Loja')
                                            ->placeholder('Digite o tipo de loja')
                                            ->maxLength(255)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(3),
                                Section::make()
                                    ->schema([
                                        TextInput::make('numero_loja')
                                            ->label('Número da Loja')
                                            ->numeric()
                                            ->minValue(0)
                                            ->validationMessages([
                                                'numeric' => 'O número da loja deve ser numérico.',
                                                'min' => 'O número da loja não pode ser negativo.',
                                            ])
                                            ->columnSpan([
                                                'default' => 5, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        Select::make('tipo')
                                            ->label('Tipo do Ponto')
                                            ->options([
                                                'Corporativa' => 'Corporativa',
                                                'Franquia' => 'Franquia',
                                                'Própria' => 'Própria',
                                            ])
                                            ->native(false)
                                            ->columnSpan([
                                                'default' => 5, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('area_terreno')
                                            ->label('Área do Imóvel (Contrato)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1000000)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            // ->visible(fn ($get) => $get('locacao') === 'Mono usuário')
                                            ->validationMessages([
                                                'numeric' => 'A área deve ser um número.',
                                                'min' => 'A área não pode ser negativa.',
                                                'max' => 'A área ultrapassa o limite permitido (1.000.000 m²).',
                                            ])
                                            ->columnSpan([
                                                'default' => 5, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('area_locada')
                                            ->label('Área Locada')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1000000)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            // ->visible(fn ($get) => $get('locacao') === 'Mono usuário')
                                            ->validationMessages([
                                                'numeric' => 'A área deve ser um número.',
                                                'min' => 'A área não pode ser negativa.',
                                                'max' => 'A área ultrapassa o limite permitido (1.000.000 m²).',
                                            ])
                                            ->columnSpan([
                                                'default' => 5, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),

                                        TextInput::make('area_academia')
                                            ->label('Área da academia')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1000000)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            // ->visible(fn ($get) => in_array($get('locacao'), ['Mono usuário', 'Multiusuário']))
                                            ->validationMessages([
                                                'numeric' => 'A área deve ser um número.',
                                                'min' => 'A área não pode ser negativa.',
                                                'max' => 'A área ultrapassa o limite permitido (1.000.000 m²).',
                                            ])
                                            ->columnSpan([
                                                'default' => 5, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(5),
                                Section::make()
                                    ->schema([
                                        TextInput::make('n_pisos')
                                            ->label('Nº de pisos')
                                            ->placeholder('Digite o nº de pisos')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->validationMessages([
                                                'numeric' => 'O Nº de pisos deve ser um número.',
                                                'min' => 'O Nº de pisos pode ser negativa.',
                                                'max' => 'O Nº de pisos ultrapassa o limite permitido (100).',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('pe_direito')
                                            ->label('Pé-direito')
                                            ->placeholder('Digite o pé-direito')
                                            ->suffix('m')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->step(0.01)
                                            ->validationMessages([
                                                'numeric' => 'O Pé-direito deve ser um número.',
                                                'min' => 'O Pé-direito pode ser negativa.',
                                                'max' => 'O Pé-direito ultrapassa o limite permitido (50,00 m).',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('modelo_entrega_p')
                                            ->label('Modelo de entrega de PP')
                                            ->placeholder('Digite o modelo e entrega')
                                            ->maxLength(255)
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(3),
                                Section::make()
                                    ->schema([
                                        TextInput::make('luvas')
                                            ->placeholder('Digite o valor das luvas')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(99999999.99)
                                            ->step(0.01)
                                            ->prefix('R$')
                                            ->validationMessages([
                                                'numeric' => 'O valor deve ser numérico.',
                                                'min' => 'O valor não pode ser negativo.',
                                                'max' => 'Valor ultrapassa o limite permitido de R$99.999.999,99.',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('iptu')
                                            ->label('IPTU')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(99999999.99)
                                            ->step(0.01)
                                            ->prefix('R$')
                                            ->validationMessages([
                                                'numeric' => 'O valor deve ser numérico.',
                                                'min' => 'O valor não pode ser negativo.',
                                                'max' => 'Valor ultrapassa o limite permitido de R$99.999.999,99.',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        TextInput::make('condominio')
                                            ->label('Condomínio')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(99999999.99)
                                            ->step(0.01)
                                            ->prefix('R$')
                                            ->validationMessages([
                                                'numeric' => 'O valor deve ser numérico.',
                                                'min' => 'O valor não pode ser negativo.',
                                                'max' => 'Valor ultrapassa o limite permitido de R$99.999.999,99.',
                                            ])
                                            ->columnSpan([
                                                'default' => 3, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ])->columns(3),
                                Section::make()->schema([
                                    Grid::make(2)->schema([
                                        Textarea::make('configuracao_academia')
                                            ->label('Configuração da Academia')
                                            ->rows(5)
                                            ->columnSpan([
                                                'default' => 1, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                        Textarea::make('dados_engenharia')
                                            ->label('Dados da Engenharia')
                                            ->rows(5)
                                            ->columnSpan([
                                                'default' => 1, // no celular ocupa 1
                                                'lg' => 1,      // no desktop continua 1
                                            ]),
                                    ]),
                                    Toggle::make('projeto_croqui')
                                        ->label('Projeto/Croqui')
                                        ->columnSpan([
                                            'default' => 2, // no celular ocupa 1
                                            'lg' => 2,      // no desktop continua 1
                                        ]),
                                ]),
                            ])
                            ->columns(1)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Planejamento Estratégico')
                            ->schema([
                                TextInput::make('capex_aprovado_diretoria_valor')
                                    ->label('Valor Capex Aprovado Diretoria')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                Select::make('capex_aprovado_diretoria')
                                    ->label('Capex Aprovado Diretoria')
                                    ->options([
                                        '1' => 'SIM',
                                        '0' => 'NÃO',
                                    ])
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('coc_aprovado')
                                    ->label('Cash on Cash')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->validationMessages([
                                        'numeric' => 'O valor deve ser numérico.',
                                        'min' => 'O valor não pode ser negativo.',
                                        'max' => 'Porcentagem não pode ultrapassar 100%.',
                                    ])
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('potencial_alunos')
                                    ->label('Potencial final de alunos')
                                    ->placeholder('Digite o nº de alunos')
                                    ->numeric()
                                    ->minValue(0)
                                    ->validationMessages([
                                        'numeric' => 'O valor deve ser numérico.',
                                        'min' => 'O valor não pode ser negativo.',
                                    ])
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('link_estudo_projecao_alunos')
                                    ->label('Link para estudo de projeção de alunos')
                                    ->url()
                                    ->columnSpan([
                                        'default' => 1, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('tier')
                                    ->label('Tier')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('renda')
                                    ->label('Renda')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                            ])
                            ->columns(3)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Operação')
                            ->schema([
                                TextInput::make('set_equipamentos')
                                    ->label('Set Equipamentos')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                DatePicker::make('vendas_mkt')
                                    ->label('Pré-vendas MKT')
                                    ->format('Y-m-d')
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('vendas_mkt_realizado')
                                    ->label('Pré-vendas MKT Realizado')
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan([
                                        'default' => 3, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(3)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Diretoria')
                            ->schema([
                                TextInput::make('diretoria')
                                    ->label('Diretoria')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('reuniao_ita')
                                    ->label('Reunião ITA')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('contato_corretor')
                                    ->label('Contato Corretor')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),

                                TextInput::make('dir_status_contrato')
                                    ->label('Status do Contrato Diretoria')
                                    ->columnSpan([
                                        'default' => 2, // no celular ocupa 1
                                        'lg' => 1,      // no desktop continua 1
                                    ]),
                            ])
                            ->columns(2)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),

                        Section::make('Orçamento')
                            ->schema([
                                FileUpload::make('oi_pdf')
                                    ->label('Ordem de Investimento')->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory(fn ($record) => "projetos/{$record->id}")
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->downloadable()
                                    ->openable()
                                    ->preserveFilenames()
                                    ->visible(fn ($record) => $record !== null)
                                    ->helperText('Apenas arquivos PDF.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->grow()
                            ->collapsible()
                            ->collapsed(fn () => true),
                    ])
                    ->columnSpanFull()
                    /*
                    ->columns([
                        'default' => 1, // celular: 1 coluna
                        'sm' => 2,      // tablets pequenos: 2 colunas
                        'lg' => 3,      // desktop: 3 colunas
                    ])*/
                    ->grow(),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses(['text-sm' => true])
            // ->paginated([10, 25])
            ->modifyQueryUsing(function ($query) {
                $etapas = request('etapas', []);

                if (is_string($etapas)) {
                    $etapas = explode(',', $etapas);
                }

                if (! empty($etapas)) {
                    $query->whereHas('etapas', function ($q) use ($etapas) {
                        $q->whereIn('etapas.id', $etapas);
                    });
                }

                $user = auth()->user();

                if (! $user->hasAnyRole(['super_admin', 'PMO', 'Planejamento Estratégico', 'Comercial', 'Inteligência Global'])) {
                    $query->where('user_id', $user->id);
                }

                $query->orderBy('created_at', 'desc');
            })
            ->columns([
                ColumnGroup::make('', [
                    TextColumn::make('codigo')
                        ->label('CÓDIGO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextColumn::make('sigla')
                        ->label('SIGLA')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextColumn::make('nova_sigla')
                        ->label('NOVA SIGLA')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('crono_revisado')
                        ->label('CRONO REVISADO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('nome')
                        ->label('UNIDADE')
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader(),

                ColumnGroup::make('STATUS DO PROCESSO', [

                    SelectColumn::make('marca')
                        ->label('MARCA')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options(
                            Marca::query()
                                ->whereNotNull('nome')
                                ->where('nome', '!=', '')
                                ->distinct()
                                ->orderBy('nome')
                                ->pluck('nome', 'nome')
                        )
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('escopo')
                        ->label('ESCOPO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'EXPANSÃO' => 'EXPANSÃO',
                            'AQUISIÇÃO' => 'AQUISIÇÃO',
                            'RELOCATION' => 'RELOCATION',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('pipeline')
                        ->label('PIPE/LAND')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options(
                            Pipe::query()
                                ->whereNotNull('pipeline')
                                ->where('pipeline', '!=', '')
                                ->distinct()
                                ->orderBy('pipeline')
                                ->pluck('pipeline', 'pipeline')
                        )
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('status')
                        ->label('STATUS')
                        // ->badge()
                        ->alignment(Alignment::Center)
                        ->options([
                            'Em processo' => 'Em processo',
                            'Obras' => 'Obras',
                            'Inaugurada' => 'Inaugurada',
                            'Cancelada' => 'Cancelada',
                            'Stand-by' => 'Stand-by',
                            'Deletar comercial' => 'Deletar comercial',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                ColumnGroup::make('SQUAD', [
                    TextColumn::make('gerenteGeral.name')
                        ->label('GERENTE GERAL')
                        ->searchable()
                        ->alignCenter()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    SelectColumn::make('resp_pmo')
                        ->label('PMO')
                        ->searchable()
                        ->alignCenter()
                        ->options(fn (): array => self::squadUserOptions('PMO'))
                        ->toggleable(isToggledHiddenByDefault: false),
                    SelectColumn::make('resp_com')
                        ->label('COMERCIAL')
                        ->searchable()
                        ->alignCenter()
                        ->options(fn (): array => self::squadUserOptions('Comercial'))
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('resp_arq')
                        ->label('ARQUITETURA')
                        ->searchable()
                        ->alignCenter()
                        ->options(fn (): array => self::squadUserOptions('Arquitetura'))
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('resp_eng')
                        ->label('ENGENHARIA')
                        ->searchable()
                        ->alignCenter()
                        ->options(fn (): array => self::squadUserOptions('Engenharia'))
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('status_comite')
                        ->label('STATUS COMITE')
                        ->searchable()
                        ->alignCenter()
                        ->options([
                            '01 - INAUGURADO' => '01 - INAUGURADO',
                            '02 - ASSINADO' => '02 - ASSINADO',
                            '03 - APROVADO' => '03 - APROVADO',
                            '04 - APROVADO COMO LOCALIZAÇÃO' => '04 - APROVADO COMO LOCALIZAÇÃO',
                            '04 - EM VALIDAÇÃO' => '04 - EM VALIDAÇÃO',
                            '05 - EM VALIDAÇÃO' => '05 - EM VALIDAÇÃO',
                            '05 - MINUTA' => '05 - MINUTA',
                            '06 - EM NEGOCIAÇÃO' => '06 - EM NEGOCIAÇÃO',
                            '07 - MINUTA' => '07 - MINUTA',
                            '07 - ON HOLD' => '07 - ON HOLD',
                            '08 - REPROVADO' => '08 - REPROVADO',
                            '09 - CAIU' => '09 - CAIU',
                            '10 - DISTRATADO' => '10 - DISTRATADO',
                            '11 - A APRESENTAR' => '11 - A APRESENTAR',
                            '3 - ASSINADO' => '3 - ASSINADO',
                            'APROVADO' => 'APROVADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                ColumnGroup::make('COMERCIAL', [

                    SelectColumn::make('status_imovel')
                        ->label('STATUS IMÓVEL')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'N/A' => 'N/A',
                            'OBRA PP' => 'OBRA PP',
                            'OBRA SF' => 'OBRA SF',
                            'PRONTO' => 'PRONTO',
                            'VALIDAÇÃO ENG' => 'VALIDAÇÃO ENG',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('prazo_inicio')
                        ->label('INÍCIO DO PROJETO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->prazo_inicio?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['prazo_inicio' => $state])->save();
                        }),

                    SelectColumn::make('status_contrato')
                        ->label('STATUS CONTRATO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'ASSINADO' => 'ASSINADO',
                            'EM ASSINATURA' => 'EM ASSINATURA',
                            'MINUTA' => 'MINUTA',
                            'NEGOCIAÇÃO' => 'NEGOCIAÇÃO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('data_ass_contrato')
                        ->label('DATA ASSINATURA CONTRATO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->data_ass_contrato?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['data_ass_contrato' => $state])->save();
                        }),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO CADASTRAL
                ColumnGroup::make('CADASTRAL', [

                    TextInputColumn::make('cad_plan_inicio')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->cad_plan_inicio?->format('Y-m-d')) // garante string mostrada
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'date'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'cad_plan_inicio', 'cad_plan_dias', 'cad_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('cad_plan_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->cad_plan_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('cad_plan_dias')
                        ->label('PLANEJADO (15 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'cad_plan_inicio', 'cad_plan_dias', 'cad_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('cad_rea_inicio')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->cad_rea_inicio?->format('Y-m-d')) // garante string mostrada
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'date'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'cad_rea_inicio', 'cad_prazo', 'cad_rea_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('cad_rea_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->cad_rea_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('cad_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'cad_rea_inicio', 'cad_prazo', 'cad_rea_fim');
                            $record->save();
                        }),

                    SelectColumn::make('cad_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO VISITA TECNICA
                ColumnGroup::make('VISITA TÉCNICA', [

                    TextInputColumn::make('vis_plan_inicio')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->vis_plan_inicio?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'vis_plan_inicio', 'vis_plan_dias', 'vis_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('vis_plan_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->vis_plan_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('vis_plan_dias')
                        ->label('PLANEJADO (05 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'vis_plan_inicio', 'vis_plan_dias', 'vis_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('vis_rea_inicio')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->vis_rea_inicio?->format('Y-m-d')) // garante string mostrada
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'date'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'vis_rea_inicio', 'vis_prazo', 'vis_rea_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('vis_rea_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->vis_rea_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('vis_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'vis_rea_inicio', 'vis_prazo', 'vis_rea_fim');
                            $record->save();
                        }),

                    SelectColumn::make('vis_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO BRIEFING + LAYOUT
                ColumnGroup::make('BRIEFING + LAYOUT', [

                    TextInputColumn::make('brief_plan')
                        ->label('PLANEJADO BRIEFING')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_plan?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['brief_plan' => $state])->save();
                        }),

                    TextInputColumn::make('brief_plan_lay_inicio')
                        ->label('PLANEJ. LAYOUT INICIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_plan_lay_inicio?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'brief_plan_lay_inicio', 'brief_plan_dias', 'brief_plan_lay_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('brief_plan_lay_fim')
                        ->label('PLANEJ. LAYOUT FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_plan_lay_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('brief_plan_dias')
                        ->label('PLANEJADO (07 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'brief_plan_lay_inicio', 'brief_plan_dias', 'brief_plan_lay_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('brief_real')
                        ->label('REALIZADO BRIEFING')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_real?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['brief_real' => $state])->save();
                        }),

                    TextInputColumn::make('brief_real_lay_inicio')
                        ->label('REALIZADO LAYOUT INICIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_real_lay_inicio?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'brief_real_lay_inicio', 'brief_prazo', 'brief_real_lay_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('brief_real_lay_fim')
                        ->label('REALIZADO LAYOUT FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->brief_real_lay_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('brief_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'brief_real_lay_inicio', 'brief_prazo', 'brief_real_lay_fim');
                            $record->save();
                        }),

                    SelectColumn::make('brief_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO ORDEM DE INVESTIMENTO
                ColumnGroup::make('ORDEM DE INVESTIMENTO', [

                    TextInputColumn::make('ordem_planej_ini')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->ordem_planej_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'ordem_planej_ini', 'ordem_planejado', 'ordem_planej_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('ordem_planej_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->ordem_planej_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('ordem_planejado')
                        ->label('PLANEJADO (05 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'ordem_planej_ini', 'ordem_planejado', 'ordem_planej_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('ordem_realizado')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->ordem_realizado?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'ordem_realizado', 'ordem_prazo', 'ordem_realizado_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('ordem_realizado_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->ordem_realizado_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('ordem_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'ordem_realizado', 'ordem_prazo', 'ordem_realizado_fim');
                            $record->save();
                        }),

                    SelectColumn::make('ordem_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('ordem_data_aprov')
                        ->label('DATA APROVAÇÃO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->ordem_data_aprov?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['ordem_data_aprov' => $state])->save();
                        }),

                    SelectColumn::make('ordem_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('ordem_status_aprov')
                        ->label('STATUS APROVAÇÃO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'APROVADO' => 'APROVADO',
                            'EM APROVAÇÃO' => 'EM APROVAÇÃO',
                            'N/A' => 'N/A',
                            'REVISÃO' => 'REVISÃO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO PROJETO EXECUTIVO
                ColumnGroup::make('PROJETO EXECUTIVO', [

                    TextInputColumn::make('proj_planej_reuniao_start')
                        ->label('PLANEJ. REUNIÃO START')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_planej_reuniao_start?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['proj_planej_reuniao_start' => $state])->save();
                        }),

                    TextInputColumn::make('proj_real_reuniao_start')
                        ->label('REALIZADO REUNIÃO START')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_real_reuniao_start?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['proj_real_reuniao_start' => $state])->save();
                        }),

                    TextInputColumn::make('proj_plan_ini')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_plan_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'proj_plan_ini', 'proj_plan', 'proj_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('proj_plan_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_plan_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('proj_plan')
                        ->label('PLANEJADO (30/45 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'proj_plan_ini', 'proj_plan', 'proj_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('proj_real_ini')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_real_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'proj_real_ini', 'proj_prazo', 'proj_real_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('proj_real_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->proj_real_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('proj_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'proj_real_ini', 'proj_prazo', 'proj_real_fim');
                            $record->save();
                        }),

                    SelectColumn::make('proj_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO ORÇAMENTOS E CONTRATAÇÕES
                ColumnGroup::make('ORÇAMENTOS E CONTRATAÇÕES', [

                    TextInputColumn::make('orca_reuniao_kickoff')
                        ->label('REUNIÃO DE KICKOFF')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->orca_reuniao_kickoff?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['orca_reuniao_kickoff' => $state])->save();
                        }),

                    TextInputColumn::make('orca_planejado_ini')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->orca_planejado_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'orca_planejado_ini', 'orca_planejado', 'orca_planejado_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('orca_planejado_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->orca_planejado_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('orca_planejado')
                        ->label('PLANEJADO (20 D)')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'orca_planejado_ini', 'orca_planejado', 'orca_planejado_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('orca_real_ini')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->orca_real_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'orca_real_ini', 'orca_prazo', 'orca_real_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('orca_real_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->orca_real_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('orca_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'orca_real_ini', 'orca_prazo', 'orca_real_fim');
                            $record->save();
                        }),

                    SelectColumn::make('orca_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO LEGALIZAÇÃO
                ColumnGroup::make('LEGALIZAÇÃO', [

                    SelectColumn::make('legal_status_consulta_prev')
                        ->label('STATUS CP/EVTL CONSULTA PREVIA')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                            'FINALIZADO' => 'FINALIZADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('legal_doc_posse')
                        ->label('DOCUMENTAÇÃO POSSE')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('legal_plan_ini')
                        ->label('PLANEJ. INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->legal_plan_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'legal_plan_ini', 'legal_prazo_legal', 'legal_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('legal_plan_fim')
                        ->label('PLANEJ. FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->legal_plan_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('legal_prazo_legal')
                        ->label('PRAZO LEGAL')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'legal_plan_ini', 'legal_prazo_legal', 'legal_plan_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('legal_realizado_ini')
                        ->label('REALIZADO INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->legal_realizado_ini?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'legal_realizado_ini', 'legal_prazo', 'legal_realizado_fim');
                            $record->save();
                        }),

                    TextInputColumn::make('legal_realizado_fim')
                        ->label('REALIZADO FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->legal_realizado_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->extraInputAttributes(['readonly' => true])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('legal_prazo')
                        ->label('PRAZO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->afterStateUpdated(function ($record) {
                            DateCalc::applyToModel($record, 'legal_realizado_ini', 'legal_prazo', 'legal_realizado_fim');
                            $record->save();
                        }),

                    SelectColumn::make('legal_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'CONCLUÍDO' => 'CONCLUÍDO',
                            'EM ANDAMENTO' => 'EM ANDAMENTO',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'NÃO INICIADO',
                            'AGENDADO' => 'AGENDADO',
                            'PENDÊNCIAS' => 'PENDÊNCIAS',
                            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
                            'SOLICITADO' => 'SOLICITADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO POSSE
                ColumnGroup::make('POSSE', [

                    TextInputColumn::make('data_posse')
                        ->label('DATA DE POSSE')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->data_posse?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['data_posse' => $state])->save();
                        }),

                    TextInputColumn::make('mes_posse')
                        ->label('MÊS POSSE')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('posse_engenharia')
                        ->label('ENGENHARIA')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'SIM' => 'SIM',
                            'NÃO' => 'NÃO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('posse_legalizacao')
                        ->label('LEGALIZAÇÃO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'SIM' => 'SIM',
                            'NÃO' => 'NÃO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('posse_status')
                        ->label('STATUS')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'NÃO REALIZADO' => 'NÃO REALIZADO',
                            'REALIZADO' => 'REALIZADO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('posse_comentarios')
                        ->label('COMENTÁRIOS')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO EXECUÇÃO DE OBRAS
                ColumnGroup::make('EXECUÇÃO DE OBRAS', [

                    TextInputColumn::make('inicio_obra')
                        ->label('INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->inicio_obra?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['inicio_obra' => $state])->save();
                        }),

                    TextInputColumn::make('entrega_obra')
                        ->label('FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->entrega_obra?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['entrega_obra' => $state])->save();
                        }),

                    TextInputColumn::make('exec_prazo_plan')
                        ->label('PRAZO PLANEJADO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('exec_prazo_real')
                        ->label('PRAZO REALIZADO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO IMPLANTAÇÃO
                ColumnGroup::make('IMPLANTAÇÃO', [

                    TextInputColumn::make('imp_inicio')
                        ->label('INÍCIO')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->imp_inicio?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['imp_inicio' => $state])->save();
                        }),

                    TextInputColumn::make('imp_fim')
                        ->label('FIM')
                        ->type('date') // input HTML5 (YYYY-MM-DD)
                        ->getStateUsing(fn ($record) => $record->imp_fim?->format('Y-m-d')) // garante string mostrada
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($record, $state) {
                            // $state aqui vem como 'YYYY-MM-DD' — grava no model
                            $record->forceFill(['imp_fim' => $state])->save();
                        }),

                    TextInputColumn::make('imp_prazo_planejado')
                        ->label('PRAZO PLANEJADO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('imp_prazo_realizado')
                        ->label('PRAZO REALIZADO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('imp_mes')
                        ->label('MÊS')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('imp_ano')
                        ->label('ANO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO DADOS DO IMÓVEL
                ColumnGroup::make('DADOS DO IMÓVEL', [

                    SelectColumn::make('tipo_imovel')
                        ->label('TIPO DE IMÓVEL')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'bts' => 'BTS',
                            'padrao' => 'Padrão',
                            'construcao_smart_fit' => 'Construção DPC',
                            'BTS' => 'BTS',
                            'CONSTRUÇÃO SF' => 'CONSTRUÇÃO SF',
                            'PADRÃO' => 'PADRÃO',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('endereco')
                        ->label('ENDEREÇO')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextColumn::make('cidade.nome')
                        ->label('CIDADE')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextColumn::make('estado.nome')
                        ->label('ESTADO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('empreendimento')
                        ->label('EMPREENDIMENTO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'RUA' => 'RUA',
                            'SUPERMERCADO' => 'SUPERMERCADO',
                            'MALL / SHOPPING' => 'MALL / SHOPPING',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('locacao')
                        ->label('LOCAÇÃO')
                        ->searchable()
                        ->alignment(Alignment::Center)
                        ->options([
                            'Mono usuário' => 'Mono usuário',
                            'Multiusuário' => 'Multiusuário',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('aluguel_cto')
                        ->label('ALUGUEL')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('obs_aluguel')
                        ->label('OBS ALUGUEL')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('carencia')
                        ->label('CARÊNCIA CONTRATO MESES')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('multa_contrato')
                        ->label('MULTA CONTRATO MESES')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('metro_contrato')
                        ->label('M² CONTRATO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('metro_layout_util')
                        ->label('M² LAYOUT UTIL')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('pavimento')
                        ->label('PAVIMENTO')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('n_vagas_livres')
                        ->label('ESTACIONAMENTO')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO PLANEJAMENTO ESTRATÉGICO
                ColumnGroup::make('PLANEJAMENTO ESTRATÉGICO', [

                    TextInputColumn::make('capex_aprovado_diretoria_valor')
                        ->label('CAPEX APROVADO DIRETORIA (R$)')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    SelectColumn::make('capex_aprovado_diretoria')
                        ->label('CAPEX APROVADO DIRETORIA')
                        ->options([
                            '1' => 'SIM',
                            '0' => 'NÃO',
                        ])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('coc_aprovado')
                        ->label('COC APROVADO (%)')
                        ->rules(['nullable', 'decimal:2', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('potencial_alunos')
                        ->label('ESTIMATIVA DE ALUNOS')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('tier')
                        ->label('TIER')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('renda')
                        ->label('RENDA')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                // GRUPO OPERAÇÃO
                ColumnGroup::make('OPERAÇÃO', [

                    TextInputColumn::make('set_equipamentos')
                        ->label('SET EQUIPAMENTOS')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('vendas_mkt')
                        ->label('PRÉ VENDAS MKT')
                        ->type('date')
                        ->getStateUsing(
                            fn ($record) => $record->vendas_mkt
                                ? Carbon::parse($record->vendas_mkt)->format('Y-m-d')
                                : null
                        )
                        ->rules(['nullable', 'date'])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('vendas_mkt_realizado')
                        ->label('PRÉ-VENDAS MKT REALIZADO')
                        ->rules(['nullable', 'integer', 'min:0'])
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([]),

                // GRUPO DIRETORIA
                ColumnGroup::make('DIRETORIA', [

                    TextInputColumn::make('diretoria')
                        ->label('DIRETORIA')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('reuniao_ita')
                        ->label('OBS. REUNIÃO ITA')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextInputColumn::make('contato_corretor')
                        ->label('Contato do Corretor/PP')
                        ->searchable()
                        ->extraInputAttributes([
                            'style' => 'width:300px;',
                        ])
                        ->toggleable(isToggledHiddenByDefault: false),

                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()
                    ->extraHeaderAttributes([
                        'style' => 'background-color: #ffe599; color: #090909;',
                    ]),

                /*
                TextColumn::make('user.name')
                    ->label('Responsável')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),


                BadgeColumn::make('etapas.nome')
                    ->label('Etapa')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ViewColumn::make('status_aprovacao')
                    ->label('Status de Aprovação')
                    ->view('tables.columns.aprovacoes-badges')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        // Se ainda não chegou na etapa, não mostra nada
                        if (! $record->etapas->contains('nome', 'Reunião de comitê')) {
                            return [];
                        }

                        $labels = [
                            'aprovado'               => 'Aprovado',
                            'reprovado'              => 'Reprovado',
                            'aprovado_com_ressalva'  => 'Aprovado com ressalva',
                            'pendente'               => 'Pendente',
                        ];

                        $approvals = $record->reunioesComite; // hasMany
                        $roles = ['super_admin', 'PMO', 'Comercial', 'Planejamento Estratégico'];
                        $roleLabels = [
                            'super_admin'               => 'Diretoria',
                            'PMO'                       => 'PMO',
                            'Comercial'                 => 'Comercial',
                            'Planejamento Estratégico'  => 'Planejamento Estratégico',
                        ];

                        return collect($roles)->map(function (string $role) use ($approvals, $labels, $roleLabels) {
                            $aprov = $approvals->firstWhere('role', $role);

                            $raw        = $aprov->aprovacao ?? 'pendente';
                            $normalized = mb_strtolower($raw);

                            $color = match ($normalized) {
                                'aprovado'               => 'success',
                                'reprovado'              => 'danger',
                                'aprovado_com_ressalva'  => 'blue',
                                'pendente'               => 'warning',
                                default                  => 'gray',
                            };

                            $icon = match ($normalized) {
                                'aprovado'               => 'heroicon-o-check-circle',
                                'reprovado'              => 'heroicon-o-x-circle',
                                'aprovado_com_ressalva'  => 'heroicon-o-exclamation-triangle',
                                'pendente'               => 'heroicon-o-clock',
                                default                  => 'heroicon-o-question-mark-circle',
                            };

                            $statusLabel = $labels[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
                            $roleLabel   = $roleLabels[$role] ?? $role;

                            return [
                                'role'   => $roleLabel,
                                'status' => $statusLabel,
                                'color'  => $color,
                                'icon'   => $icon,
                            ];
                        })->all();
                    })
                    ->visible(function ($livewire) {
                        // Só esconde/mostra por tab quando estivermos numa página com tabs (ListProjetos)
                        if (property_exists($livewire, 'activeTab')) {
                            // ATENÇÃO: aqui usamos a **chave** da tab definida em ListProjetos->getTabs()
                            return $livewire->activeTab === 'Reunião de comitê';
                        }

                        // Em outras telas (sem tabs), mantemos a coluna visível
                        return true;
                    }),

                ViewColumn::make('status_aprovacao_viabilidade')
                    ->label('Status de Aprovação')
                    ->view('tables.columns.aprovacoes-badges')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        // Se ainda não chegou na etapa, não mostra nada
                        if (! $record->etapas->contains('nome', 'Viabilidade')) {
                            return [];
                        }

                        $labels = [
                            'aprovado'               => 'Aprovado',
                            'reprovado'              => 'Reprovado',
                            //'aprovado_com_ressalva'  => 'Aprovado com ressalva',
                            //'pendente'               => 'Pendente',
                        ];

                        $approvals = $record->viabilidades; // hasMany
                        $roles = ['super_admin', 'Inteligência Global'];
                        $roleLabels = [
                            'super_admin'               => 'Diretoria',
                            'Inteligência Global'       => 'Inteligência Global',
                        ];

                        return collect($roles)->map(function (string $role) use ($approvals, $labels, $roleLabels) {
                            $aprov = $approvals->firstWhere('role', $role);

                            $raw        = $aprov->aprovacao ?? 'pendente';
                            $normalized = mb_strtolower($raw);

                            $color = match ($normalized) {
                                'aprovado'               => 'success',
                                'reprovado'              => 'danger',
                                default                  => 'gray',
                            };

                            $icon = match ($normalized) {
                                'aprovado'               => 'heroicon-o-check-circle',
                                'reprovado'              => 'heroicon-o-x-circle',
                                default                  => 'heroicon-o-question-mark-circle',
                            };

                            $statusLabel = $labels[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
                            $roleLabel   = $roleLabels[$role] ?? $role;

                            return [
                                'role'   => $roleLabel,
                                'status' => $statusLabel,
                                'color'  => $color,
                                'icon'   => $icon,
                            ];
                        })->all();
                    })
                    ->visible(function ($livewire) {
                        // Só esconde/mostra por tab quando estivermos numa página com tabs (ListProjetos)
                        if (property_exists($livewire, 'activeTab')) {
                            // ATENÇÃO: aqui usamos a **chave** da tab definida em ListProjetos->getTabs()
                            return $livewire->activeTab === 'Viabilidade';
                        }

                        // Em outras telas (sem tabs), mantemos a coluna visível
                        return true;
                    }),



                TextColumn::make('rua')
                    ->label('Rua')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bairro')
                    ->label('Bairro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cep')
                    ->label('CEP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('pais.nome')
                    ->label('País')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                */

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('historicoGlobal')
                    ->label('Histórico')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn () => \App\Filament\Pages\HistoricoProjetos::getUrl())
                    ->openUrlInNewTab(false),
            ])
            ->filters([
                Filter::make('localizacao')
                    ->form([
                        Grid::make(3)
                            ->schema([
                                Select::make('pipeline')
                                    ->label('Pipe / Land')
                                    ->options(
                                        Pipe::query()
                                            ->whereNotNull('pipeline')
                                            ->where('pipeline', '!=', '')
                                            ->distinct()
                                            ->orderBy('pipeline')
                                            ->pluck('pipeline', 'pipeline')
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),

                                Select::make('etapa_id')
                                    ->relationship('etapas', 'nome', fn ($query) => $query->orderBy('nome'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload()
                                    ->label('Etapa'),

                                Select::make('pais_id')
                                    ->label('País')
                                    ->options(Pais::orderBy('nome')->pluck('nome', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('estado_id', null)),

                                Select::make('estado_id')
                                    ->label('Estado')
                                    ->options(
                                        fn ($get) => Estado::where('pais_id', $get('pais_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null)),

                                Select::make('cidade_id')
                                    ->label('Cidade')
                                    ->options(
                                        fn ($get) => Cidade::where('estado_id', $get('estado_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(null),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(
                                        Projeto::query()
                                            ->whereNotNull('status')
                                            ->where('status', '!=', '')
                                            ->distinct()
                                            ->orderBy('status')
                                            ->pluck('status', 'status')
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),

                                Select::make('status_comite')
                                    ->label('Status Comitê')
                                    ->options(
                                        Projeto::query()
                                            ->whereNotNull('status_comite')
                                            ->where('status_comite', '!=', '')
                                            ->distinct()
                                            ->orderBy('status_comite')
                                            ->pluck('status_comite', 'status_comite')
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),

                            ]),
                    ])
                    ->columnSpanFull()
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['pipeline'] ?? null, fn ($q, $pipeline) => $q->whereIn('pipeline', $pipeline))
                            ->when($data['etapa_id'] ?? null, fn ($q, $etapas) => $q->whereHas('etapas', fn ($q2) => $q2->whereIn('etapa_id', $etapas)))
                            ->when($data['pais_id'], fn ($q, $pais) => $q->where('pais_id', $pais))
                            ->when($data['estado_id'], fn ($q, $estado) => $q->where('estado_id', $estado))
                            ->when($data['cidade_id'], fn ($q, $cidade) => $q->where('cidade_id', $cidade))
                            ->when($data['status'] ?? null, fn ($q, $status) => $q->whereIn('status', $status));
                    })
                    ->indicateUsing(fn (array $data): array => array_filter([
                        $data['pipeline'] ? 'Pipeline: '.implode(', ', $data['pipeline']) : null,
                        $data['etapa_id'] ? 'Etapas: '.implode(', ', Etapa::whereIn('id', $data['etapa_id'])->pluck('nome')->toArray()) : null,
                        $data['pais_id'] ? 'País: '.(Pais::find($data['pais_id'])?->nome ?? '') : null,
                        $data['estado_id'] ? 'Estado: '.(Estado::find($data['estado_id'])?->nome ?? '') : null,
                        $data['cidade_id'] ? 'Cidade: '.(Cidade::find($data['cidade_id'])?->nome ?? '') : null,
                        $data['status'] ? 'Status: '.implode(', ', $data['status']) : null,
                    ])),

                TrashedFilter::make()
                    ->label('Arquivados')
                    ->placeholder('Todos')
                    ->trueLabel('Somente arquivados')
                    ->falseLabel('Ocultar arquivados'),

            ])->filtersLayout(FiltersLayout::AboveContent)->deferFilters(false)
            ->actions([
                ViewAction::make()->label(''),
                EditAction::make()->label('')
                    ->visible(function ($record) {
                        $user = auth()->user();
                        $role = $user->getRoleNames()->first();

                        // quem tem acesso total
                        if (in_array($role, ['super_admin', 'PMO'], true)) {
                            return true;
                        }

                        // mapa de etapas permitidas por papel
                        $permitidasPorRole = [
                            'Comercial' => ['Reunião de comitê'],
                            'Planejamento Estratégico' => ['Reunião de comitê'],
                            // 'OutroPapel'               => ['Viabilidade', 'Contrato'],
                        ];

                        $etapasPermitidas = $permitidasPorRole[$role] ?? [];

                        // precisa estar em alguma das etapas permitidas
                        $estaNaEtapa = $record?->etapas?->contains(
                            fn ($e) => in_array($e->nome, $etapasPermitidas, true)
                        );

                        // e ser o responsável
                        $ehResponsavel = (int) $record->user_id === (int) $user->id;

                        return $estaNaEtapa && $ehResponsavel;
                    }),
                AvancoEtapa::make(),
                ReuniaoComiteAction::make('reuniao_comite'),
                ViabilidadeAction::make('viabilidade'),
                \Filament\Tables\Actions\Action::make('arquivar')
                    ->label('')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->tooltip('Arquivar projeto')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Arquivar projeto')
                    ->modalDescription('O projeto será arquivado e não aparecerá nos dashboards. Pode ser restaurado a qualquer momento.')
                    ->modalSubmitActionLabel('Arquivar')
                    ->visible(fn ($record) => ! $record->trashed())
                    ->action(fn ($record) => $record->delete()),
                \Filament\Tables\Actions\Action::make('restaurar')
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->tooltip('Restaurar projeto')
                    ->color('success')
                    ->visible(fn ($record) => $record->trashed())
                    ->action(fn ($record) => $record->restore()),
            ], position: RecordActionsPosition::BeforeCells)
            ->bulkActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    AvancoEtapa::makeBulkAction(),
                ]),
            ])
            ->deferColumnManager(false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('etapas');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            // ProspeccaoRelationManager::class,
        ];
    }
    /*
    public static function afterSave($record)
    {
        // Aqui, após o salvamento do Projeto, vamos atualizar a tabela de Prospeccao
        if ($record->prospeccao) {
            $prospeccao = $record->prospeccao;
            $prospeccao->etapa_id = $record->etapa_id;
            $prospeccao->save();
        }
    }
    */

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // dd($data); // olha se modelo_entrega_p tá vindo
        return $data;
    }

    public static function isCommercialUser(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $role = $user->getRoleNames()->first();

        return in_array($role, ['Comercial'], false)
            || strcasecmp((string) $user->setor, 'Comercial') === 0;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! auth()->user()?->hasAnyRole(['Comercial', 'Colaborador', 'Visita Técnica', 'Fornecedor', 'Planejamento']);
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()?->hasAnyRole(['Planejamento Editor', 'Planejamento Visualizador'])) {
            return false;
        }
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return ! static::isCommercialUser();
    }

    public static function canEdit($record): bool
    {
        return auth()->check();
    }

    public static function canView($record): bool
    {
        return auth()->check();
    }

    public static function canDelete($record): bool
    {
        return ! static::isCommercialUser();
    }

    public static function canDeleteAny(): bool
    {
        return ! static::isCommercialUser();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjetos::route('/'),
            'create' => Pages\CreateProjeto::route('/create'),
            'view' => Pages\ViewProjeto::route('/{record}'),
            'edit' => Pages\EditProjeto::route('/{record}/edit'),
            'painel' => Pages\PainelProjeto::route('/{record}/painel'),
            'viewer-3d' => Pages\Viewer3DProjeto::route('/{record}/viewer-3d'),
            'visualizar-ponto' => Pages\VisualizarPonto::route('/{record}/visualizar-ponto'),
            'editar-ponto' => Pages\EditarPonto::route('/{record}/editar-ponto'),
        ];
    }
    /*
    protected static function recalcCadPlanFim($record): void
    {
        $inicio = $record->cad_plan_inicio;
        $dias   = $record->cad_plan_dias;

        if (blank($inicio) || !is_numeric($dias)) {
            $record->cad_plan_fim = null;
            $record->save();
            return;
        }

        $ini = $inicio instanceof \DateTimeInterface
            ? CarbonImmutable::instance($inicio)
            : CarbonImmutable::parse($inicio); // espera 'Y-m-d'

        // ajuste para contagem inclusiva trocando $dias por max(0, (int)$dias - 1)
        $record->cad_plan_fim = $ini->addDays((int) $dias);
        $record->save();
    }
    */
}
