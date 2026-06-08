<?php

namespace App\Filament\Pages;

use App\Filament\Components\Forms\MoneyInput;
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
use App\Support\CronogramaLimites;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UnitEnum;

class CadastrarPonto extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    private const MEDIA_FIELDS = [
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

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Cadastrar ponto';

    protected static ?string $title = 'Cadastrar ponto';

    protected static ?string $slug = 'cadastrar-ponto';

    protected string $view = 'filament.pages.cadastrar-ponto';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public ?string $upload_uuid = null;

    protected $listeners = [
        'confirmar-cadastro-mesmo-assim' => 'confirmarCadastroMesmoAssim',
    ];

    public function mount(): void
    {
        $etapaProspeccaoId = Etapa::query()
            ->where('nome', 'Prospecção')
            ->value('id');

        $this->upload_uuid = (string) Str::uuid();

        $this->form->fill([
            'etapas' => $etapaProspeccaoId ? [$etapaProspeccaoId] : [],
            'upload_uuid' => $this->upload_uuid,
        ]);
    }

    protected static function baseDirectory(string $numero): string
    {
        return 'arquivos-pt/'.$numero;
    }

    protected static function midiaDirectory(Get $get): string
    {
        return static::baseDirectory($get('upload_uuid') ?: 'temp').'/midia';
    }

    protected static function pdfDirectory(string $numero): string
    {
        return static::baseDirectory($numero).'/pdf';
    }

    private function normalizeProjectMediaPaths(Projeto $projeto): void
    {
        $updates = [];

        foreach (self::MEDIA_FIELDS as $field) {
            $normalized = $this->normalizeStoredFilesToProjectDirectory($projeto->{$field}, $projeto->id);

            if ($normalized !== $projeto->{$field}) {
                $updates[$field] = $normalized;
            }
        }

        if ($updates !== []) {
            $projeto->forceFill($updates)->saveQuietly();
        }
    }

    private function normalizeStoredFilesToProjectDirectory(mixed $originalValue, int $projetoId): mixed
    {
        $files = $this->normalizeFiles($originalValue);

        if ($files === []) {
            return $originalValue;
        }

        $normalized = array_map(
            fn (mixed $file) => $this->moveStoredPathToProjectDirectory($file, $projetoId),
            $files,
        );

        return $this->restoreOriginalFormat($originalValue, $normalized);
    }

    private function moveStoredPathToProjectDirectory(mixed $file, int $projetoId): mixed
    {
        $sourcePath = $this->extractPath($file);

        if (! $sourcePath) {
            return $file;
        }

        $targetPath = static::baseDirectory((string) $projetoId).'/midia/'.basename($sourcePath);

        if ($sourcePath === $targetPath) {
            return $targetPath;
        }

        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

        if (! $disk->exists($sourcePath)) {
            return $file;
        }

        if (! $disk->exists($targetPath)) {
            $stream = $disk->readStream($sourcePath);

            if ($stream === false) {
                return $file;
            }

            try {
                $disk->writeStream($targetPath, $stream, ['visibility' => 'public']);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return $disk->exists($targetPath) ? $targetPath : $file;
    }

    private function normalizeFiles(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => ! blank($item)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => ! blank($item)));
            }

            return trim($value) !== '' ? [$value] : [];
        }

        return [];
    }

    private function extractPath(mixed $file): ?string
    {
        if (is_string($file)) {
            $path = parse_url($file, PHP_URL_PATH) ?: $file;

            return ltrim((string) $path, '/');
        }

        if (is_array($file)) {
            $candidate = $file['path'] ?? $file['url'] ?? $file[0] ?? null;

            if (! is_string($candidate) || trim($candidate) === '') {
                return null;
            }

            $path = parse_url($candidate, PHP_URL_PATH) ?: $candidate;

            return ltrim((string) $path, '/');
        }

        return null;
    }

    private function restoreOriginalFormat(mixed $originalValue, array $files): mixed
    {
        if (is_array($originalValue)) {
            return $files;
        }

        if (is_string($originalValue)) {
            $decoded = json_decode($originalValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $files;
            }

            return $files[0] ?? null;
        }

        return $files;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identificação do ponto')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            Hidden::make('upload_uuid'),

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
                                ]),

                            TextInput::make('codigo')
                                ->label('Código')
                                ->required(),

                            DatePicker::make('data_posse')
                                ->label('Data de Posse')
                                ->required()
                                ->live(onBlur: true)
                                ->helperText(function (Get $get): string {
                                    $base = 'Data prevista de posse do imóvel — âncora para o cronograma.';
                                    $posse = $get('data_posse');
                                    if (! $posse) {
                                        return $base;
                                    }

                                    try {
                                        $dias = (int) Carbon::today()
                                            ->diffInDays(Carbon::parse($posse), absolute: false);
                                    } catch (\Throwable $e) {
                                        return $base;
                                    }

                                    if ($dias < CronogramaLimites::DIAS_IDEAL_INICIO_PROJETO_POSSE) {
                                        return $base." Atenção: posse a {$dias} dias do início — abaixo do ideal de "
                                            .CronogramaLimites::DIAS_IDEAL_INICIO_PROJETO_POSSE
                                            .' dias. Cronograma pode nascer com fases em risco.';
                                    }

                                    return $base." (~{$dias} dias do início — dentro do ideal).";
                                }),

                        ]),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            TextInput::make('nome')
                                ->label('Nome comercial do ponto')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Informe o nome.',
                                ]),

                            Select::make('marca')
                                ->label('Marca')
                                ->searchable()
                                ->preload()
                                ->options(fn (): array => Marca::query()
                                    ->orderBy('nome')
                                    ->pluck('nome', 'nome')
                                    ->toArray()),

                            TextInput::make('numero_loja')
                                ->label('Nº da Loja / LUC'),
                        ]),

                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])->schema([

                            RichEditor::make('pontos_atencao')
                                ->label('Pontos de atenção')
                                ->extraInputAttributes([
                                    'style' => 'min-height: 6rem; resize: vertical; overflow: auto;',
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
                                ->placeholder('Descreva observações importantes, riscos, restrições ou cuidados específicos do ponto.'),

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
                                ->columnSpan([
                                    'default' => 1,
                                    'xl' => 2,
                                ]),
                        ]),
                    ]),

                Section::make('Localização do Ponto')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('cep')
                                ->label('CEP')
                                ->mask('99999-999')
                                ->live(onBlur: true)
                                ->helperText('Informe o CEP para atualizar as informações de localização.')
                                ->afterStateUpdated(function ($state, callable $set) {
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
                                }),

                            TextInput::make('numero')
                                ->label('Número')
                                ->reactive()
                                ->maxLength(20)
                                ->afterStateUpdated(function ($state, callable $get) {
                                    $cep = $get('cep');

                                    if (! $cep || ! $state) {
                                        return;
                                    }

                                    $existing = Projeto::query()
                                        ->where('cep', $cep)
                                        ->where('numero', $state)
                                        ->get();

                                    if ($existing->isEmpty()) {
                                        return;
                                    }

                                    $listaProjetos = $existing->map(
                                        fn ($projeto) => "{$projeto->nome} (Código {$projeto->codigo})"
                                    )->implode('<br>');

                                    Notification::make()
                                        ->title('Endereço já cadastrado')
                                        ->body("Já existem prospecções neste endereço:<br>{$listaProjetos}")
                                        ->warning()
                                        ->persistent()
                                        ->actions([
                                            // Action::make('ver')
                                            // ->label('Ver Projetos'),
                                            // ->url('/admin/projetos'),

                                            Action::make('continuar')
                                                ->label('Cadastrar mesmo assim')
                                                ->close(),
                                        ])
                                        ->send();
                                }),

                            TextInput::make('complemento')
                                ->label('Complemento'),

                            TextInput::make('rua')
                                ->label('Rua'),
                        ]),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('bairro')
                                ->label('Bairro'),

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
                                    ->toArray()),

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
                                }),

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
                                }),
                        ]),
                    ]),

                Section::make('Características do imóvel')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('area_academia')
                                ->label('Área contratada')
                                ->numeric()
                                ->required()
                                ->validationMessages([
                                    'required' => 'Informe a área.',
                                ])
                                ->suffix('m²'),

                            TextInput::make('n_pisos')
                                ->label('Número de pisos')
                                ->numeric(),

                            TextInput::make('configuracao_academia')
                                ->label('Configuração da academia'),

                            TextInput::make('pe_direito')
                                ->label('PD')
                                ->numeric()
                                ->suffix('m'),
                        ]),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('n_vagas_livres')
                                ->label('Quantidade de vagas de estacionamento'),

                            Select::make('tipo_de_loja')
                                ->label('Vagas')
                                ->native(false)
                                ->options([
                                    'exclusivas' => 'Exclusivas',
                                    'compartilhadas' => 'Compartilhadas',
                                    'horas_livres' => 'Horas livres',
                                ]),

                            Select::make('empreendimento')
                                ->label('Empreendimento')
                                ->native(false)
                                ->options([
                                    'Shopping' => 'Shopping',
                                    'Rua' => 'Rua',
                                    'Supermercado' => 'Supermercado',
                                    'Mall' => 'Mall',
                                    'Edifício Comercial' => 'Edifício Comercial',
                                ]),

                            Select::make('locacao')
                                ->label('Tipo de locação')
                                ->native(false)
                                ->options([
                                    'Mono usuário' => 'Mono usuário',
                                    'Multiusuário' => 'Multiusuário',
                                ]),
                        ]),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            Select::make('tipo_imovel')
                                ->label('Tipo de imóvel')
                                ->native(false)
                                ->options([
                                    'bts' => 'BTS',
                                    'padrao' => 'Padrão',
                                    'construcao_smart_fit' => 'Construção DPC',
                                ]),

                            TextInput::make('modelo_entrega_p')
                                ->label('Entregas do PP'),

                            DatePicker::make('data_entrega_shell')
                                ->label('Data de entrega do shell/ previsão de posse'),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('imovel_pronto')
                                ->label('Imóvel pronto')
                                ->onColor('success')
                                ->offColor('danger'),

                            Toggle::make('relocation')
                                ->label('Relocation')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    ]),

                Section::make('Comercial')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            MoneyInput::make('aluguel_cto', 'Valor do aluguel'),

                            TextInput::make('carencia')
                                ->label('Carência'),

                            TextInput::make('multa_contrato')
                                ->label('Multa contratual'),
                        ]),

                        Textarea::make('obs_aluguel')
                            ->label('Observações aluguel')
                            ->rows(4),
                    ]),

                Section::make('Contato')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 2,
                        ])->schema([
                            TextInput::make('nome_contato')
                                ->label('Nome do contato')
                                ->required(fn (Get $get): bool => ($get('cad_status') ?? null) === 'sim'),

                            TextInput::make('contato')
                                ->label('Telefone / e-mail')
                                ->required(fn (Get $get): bool => ($get('cad_status') ?? null) === 'sim'),
                        ]),
                    ]),

                Section::make('Necessidades')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            Select::make('cad_status')
                                ->label('Cadastral')
                                ->native(false)
                                ->live()
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ]),

                            Select::make('vis_status')
                                ->label('Visita Técnica')
                                ->native(false)
                                ->live()
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ])
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state !== 'sim') {
                                        $set('visita_tecnica_user_id', null);
                                    }
                                }),

                            Select::make('legal_status_consulta_prev')
                                ->label('Consulta Prévia')
                                ->native(false)
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ]),

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
                                }),

                            DatePicker::make('evtl_recebido_em')
                                ->label('EVTL recebido em')
                                ->visible(fn (callable $get) => in_array(($get('evtl_status') ?? null), ['sim', 'existente'], true)),

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
                                ->visible(fn (callable $get) => in_array(($get('evtl_status') ?? null), ['sim', 'existente'], true)),
                        ]),

                        Textarea::make('dados_engenharia')
                            ->label('Necessidade específica da engenharia')
                            ->rows(4),
                    ]),

                Section::make('Projetos e documentação')
                    ->schema([
                        Grid::make(2)->schema([
                            FileUpload::make('anexo_matricula_iptu')
                                ->label('Matrícula + IPTU')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                ->helperText('Envie matrícula e IPTU.'),

                            FileUpload::make('anexo_habite_se')
                                ->label('Habite-se')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                }),

                            FileUpload::make('anexo_avcb')
                                ->label('AVCB')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                }),

                            FileUpload::make('anexo_projeto')
                                ->label('Projeto')
                                ->hintIcon(Heroicon::InformationCircle, 'Formatos permitidos: PDF, DWG, RVT e ZIP. Tamanho máximo: 700 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
                                ->reorderable()
                                ->openable(false)
                                ->downloadable()
                                ->previewable(false)
                                ->directory(fn (Get $get) => static::midiaDirectory($get))->disk((string) config('filesystems.media_disk', 'r2'))
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    '.dwg',
                                    'image/vnd.dwg',
                                    'application/x-autocad',
                                    '.rvt',
                                    'application/vnd.autodesk.revit',
                                    'application/zip',
                                    'application/x-zip-compressed',
                                ])
                                ->mimeTypeMap([
                                    'dwg' => 'image/vnd.dwg',
                                    'rvt' => 'application/vnd.autodesk.revit',
                                ])
                                ->maxSize(716800)
                                ->getUploadedFileNameForStorageUsing(function ($file) {
                                    $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                    return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                                }),

                            FileUpload::make('anexo_convencao_condominio')
                                ->label('Convenção do condomínio')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                }),

                            FileUpload::make('anexo_regime_interno')
                                ->label('Regime Interno')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                }),

                            FileUpload::make('anexo_normas_gerais')
                                ->label('Normas Gerais')
                                ->hintIcon(Heroicon::InformationCircle, 'Formato permitido: PDF. Tamanho máximo: 50 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                }),

                            FileUpload::make('anexo_outros_documentos')
                                ->label('Outros documentos')
                                ->hintIcon(Heroicon::InformationCircle, 'Formatos permitidos: imagens, PDF, Word, Excel, PowerPoint, TXT, DWG, RVT e ZIP. Tamanho máximo: 300 MB por arquivo.')
                                ->multiple()
                                ->panelLayout('grid')
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
                                    'image/vnd.dwg',
                                    'application/x-autocad',
                                    '.rvt',
                                    'application/vnd.autodesk.revit',
                                    'application/zip',
                                    'application/x-zip-compressed',
                                ])
                                ->mimeTypeMap([
                                    'dwg' => 'image/vnd.dwg',
                                    'rvt' => 'application/vnd.autodesk.revit',
                                ])
                                ->maxFiles(20)
                                ->maxSize(716800)
                                ->getUploadedFileNameForStorageUsing(function ($file) {
                                    $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                                    return $nome.'-'.uniqid().'.'.$file->getClientOriginalExtension();
                                }),
                        ]),
                    ]),
            ]);
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
            'data_posse' => 'Data de posse',
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
            if (in_array($campo, ['nome_contato', 'contato'], true) && (($data['cad_status'] ?? null) !== 'sim')) {
                continue;
            }

            $valor = $data[$campo] ?? null;

            $vazio = match (true) {
                is_array($valor) => empty(array_filter($valor, fn ($item) => filled($item))),
                is_bool($valor) => false, // toggle false também é valor preenchido
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

    protected function persistCreate(array $data): void
    {
        $etapaIds = collect($data['etapas'] ?? [])
            ->filter(fn (mixed $etapaId): bool => filled($etapaId))
            ->map(fn (mixed $etapaId): int => (int) $etapaId)
            ->values()
            ->all();

        $etapaId = $etapaIds[0] ?? Etapa::query()
            ->where('nome', 'Prospecção')
            ->value('id');

        if ($etapaIds === [] && $etapaId) {
            $etapaIds = [$etapaId];
        }

        if (! $etapaId) {
            Notification::make()
                ->title('Etapa inicial não encontrada')
                ->body('Não foi possível definir a etapa inicial "Prospecção" para o ponto.')
                ->danger()
                ->send();

            return;
        }

        $cidade = Cidade::find($data['cidade_id'] ?? null);
        $estado = Estado::find($data['estado_id'] ?? null);

        $cidadeUf = trim(
            collect([
                $cidade?->nome,
                $estado?->uf ?? $estado?->sigla ?? null,
            ])->filter()->implode('/')
        );

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

        $projeto = Projeto::create([
            'user_id' => Auth::id(),
            'resp_com' => Auth::id(),
            'etapa_id' => $etapaId,

            'status_comite' => $data['status_comite'] ?? null,
            'codigo' => $data['codigo'] ?? null,
            'sigla' => null,
            'nome' => $data['nome'] ?? null,
            'marca' => $data['marca'] ?? null,
            'numero_loja' => $data['numero_loja'] ?? null,
            'pin_google' => $data['pin_google'] ?? null,

            'cep' => $data['cep'] ?? null,
            'rua' => $data['rua'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade_id' => $data['cidade_id'] ?? null,
            'estado_id' => $data['estado_id'] ?? null,
            'pais_id' => $data['pais_id'] ?? null,
            'endereco' => $enderecoCompleto,

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
            'data_posse' => $data['data_posse'] ?? null,
            'imovel_pronto' => $data['imovel_pronto'] ?? false,
            'relocation' => $data['relocation'] ?? false,

            'aluguel_cto' => MoneyInput::parse($data['aluguel_cto'] ?? null),
            'carencia' => $data['carencia'] ?? null,
            'multa_contrato' => $data['multa_contrato'] ?? null,
            'obs_aluguel' => $data['obs_aluguel'] ?? null,

            'nome_contato' => $data['nome_contato'] ?? null,
            'contato' => $data['contato'] ?? null,
            'contato_corretor' => $data['contato_corretor'] ?? null,

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

        if ($etapaIds !== []) {
            $projeto->etapas()->sync($etapaIds);
        }

        $this->normalizeProjectMediaPaths($projeto);

        if (($data['vis_status'] ?? null) === 'sim') {
            $taskCategoryId = TaskCategory::where('name', 'Visita Técnica')->value('id');

            if (! $taskCategoryId) {
                Notification::make()
                    ->title('Categoria "Visita Técnica" não encontrada')
                    ->danger()
                    ->send();

                return;
            }

            $responsaveis = User::query()
                ->whereIn('email', [
                    'talita.carmona@bioritmo.com.br',
                    'talita.soares@smartfit.com',
                ])
                ->get();

            if ($responsaveis->isEmpty()) {
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

            $responsaveisIds = $responsaveis->pluck('id')->toArray();

            $marcaNome = blank($data['marca'] ?? null)
                ? 'A DEFINIR'
                : $data['marca'];

            $marca = Marca::firstOrCreate(
                ['nome' => $marcaNome],
                ['nome' => $marcaNome]
            );

            $nomeProjeto = $projeto->nome ?? 'Novo ponto';
            $enderecoProjeto = $projeto->endereco ?: 'Endereço não informado';

            $primeiroResponsavel = $responsaveis->first();

            $task = Task::create([
                'projeto_id' => $projeto->id,
                'title' => 'Realizar visita técnica - '.$nomeProjeto,
                'description' => 'Solicitação feita para realizar a visita técnica do novo ponto cadastrado.'
                    ."\n\nProjeto ID: {$projeto->id}"
                    ."\nNome do ponto: {$nomeProjeto}"
                    ."\nEndereço: {$enderecoProjeto}",
                'task_category_id' => $taskCategoryId,
                'sigla' => null,
                'marca_id' => $marca->id,
                'created_by' => Auth::id(),
                'assigned_to' => $primeiroResponsavel?->id, // compatibilidade temporária
                'setor_id' => $setorId,
                'prazo' => null,
                'dias_corridos' => 0,
                'inicio' => now()->toDateString(),
                'termino_programado' => null,
                'status' => 'pendente',
            ]);

            $task->responsaveis()->sync($responsaveisIds);
        }

        Notification::make()
            ->title('Ponto cadastrado com sucesso.')
            ->success()
            ->send();

        $this->redirect('/admin/dashboard-comercial');
    }

    public function confirmarCadastroMesmoAssim(): void
    {
        $this->create(true);
    }

    public function create(bool $forcar = false): void
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
                            ->label('Cadastrar mesmo assim')
                            ->button()
                            ->dispatch('confirmar-cadastro-mesmo-assim'),
                        Action::make('fechar')
                            ->label('Fechar')
                            ->close(),
                    ])
                    ->send();

                return;
            }
        }

        $this->persistCreate($data);
    }
}
