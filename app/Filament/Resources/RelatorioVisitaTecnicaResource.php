<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RelatorioVisitaTecnicaResource\Pages;
use App\Mail\EnviarPdfMail;
use App\Models\ListaEmail;
use App\Models\Marca;
use App\Models\Projeto;
use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use App\Services\VisitaTecnicaPdfService;
use App\Support\ImageUploadHelper;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
// use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class RelatorioVisitaTecnicaResource extends Resource
{
    protected static ?string $model = RelatorioVisitaTecnica::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Visita Técnica';

    protected static ?string $modelLabel = 'Visita Técnica';

    protected static ?int $navigationSort = 6;

    protected static ?string $breadcrumb = 'Visita Técnica';

    protected static ?string $pluralModelLabel = 'Relatório das Visitas Técnicas';

    protected static UnitEnum|string|null $navigationGroup = 'Registro fotográfico';

    protected static ?string $slug = 'relatorio-visita-tecnicas';

    protected static function getRelatorioDirectory(string $suffix, $get, $record = null): string
    {
        $numero = $get('numero_relatorio_vt')
            ?? $record?->numero_relatorio_vt
            ?? 'sem-numero';

        return "relatorios-vt/{$numero}/{$suffix}";
    }

    protected static function saveRelatorioUpload(string $field, string $suffix = 'midia'): Closure
    {
        return ImageUploadHelper::callback(fn ($get, $record) => static::getRelatorioDirectory($suffix, $get, $record), (string) config('filesystems.media_disk', 'r2'), $field);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function baseDirectory(string $numero): string
    {
        return 'relatorios-vt/'.$numero;
    }

    protected static function midiaDirectory(Get $get): string
    {
        return static::baseDirectory($get('numero_relatorio_vt') ?: 'temp').'/midia';
    }

    protected static function pdfDirectory(string $numero): string
    {
        return static::baseDirectory($numero).'/pdf';
    }

    /**
     * @return array<string, string>
     */
    protected static function emailOptionsEnvioRelatorioVisitaTecnica(): array
    {
        $emailOptions = [];

        ListaEmail::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['nome', 'emails'])
            ->each(function (ListaEmail $lista) use (&$emailOptions): void {
                foreach (($lista->emails ?? []) as $email) {
                    $email = mb_strtolower(trim((string) $email));

                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    $emailOptions[$email] = "{$lista->nome} <{$email}>";
                }
            });

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Informações Iniciais')
                        ->schema([
                            Section::make('Dados do Relatório')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([

                                            TextInput::make('novo_projeto')
                                                ->default(false)
                                                ->hidden()
                                                ->dehydrated(false)
                                                ->live(),

                                            Select::make('projeto_id')
                                                ->label('Projeto')
                                                ->relationship(
                                                    name: 'projeto',
                                                    titleAttribute: 'nome',
                                                    modifyQueryUsing: fn ($query) => $query->whereNotNull('nome')
                                                )
                                                ->getOptionLabelFromRecordUsing(fn (Projeto $record): string => $record->nome ?? 'Projeto sem nome')
                                                ->placeholder('Selecione um projeto')
                                                ->searchable()
                                                ->preload()
                                                ->live()

                                                ->createOptionForm([
                                                    TextInput::make('nome')
                                                        ->label('Nome do Projeto')
                                                        ->required(),
                                                ])

                                                ->createOptionUsing(function (array $data) {
                                                    $projeto = Projeto::create([
                                                        'nome' => $data['nome'],
                                                    ]);

                                                    return $projeto->id;
                                                })

                                                ->createOptionAction(
                                                    fn ($action) => $action->after(function (Set $set) {
                                                        $set('novo_projeto', true);
                                                    })
                                                )

                                                ->afterStateUpdated(function ($state, Set $set) {

                                                    // Se remover o projeto (clicar no X)
                                                    if (! $state) {

                                                        $set('unidade', null);
                                                        $set('endereco', null);
                                                        $set('pavimento', null);
                                                        $set('empreendimento', null);
                                                        $set('locacao', null);
                                                        $set('contato_responsavel', null);
                                                        $set('marca_id', null);

                                                        $set('novo_projeto', false);

                                                        return;
                                                    }

                                                    $projeto = Projeto::find($state);

                                                    if (! $projeto) {
                                                        return;
                                                    }

                                                    $set('unidade', $projeto->nome);
                                                    $set('endereco', $projeto->endereco);
                                                    $set('pavimento', $projeto->pavimento);
                                                    $set('empreendimento', $projeto->empreendimento);
                                                    $set('locacao', $projeto->locacao);
                                                    $set('contato_responsavel', $projeto->contato_corretor);

                                                    // Marca
                                                    if ($projeto->marca) {

                                                        $marca = Marca::where('nome', $projeto->marca)->first();

                                                        if ($marca) {
                                                            $set('marca_id', $marca->id);
                                                        }
                                                    }

                                                    $set('novo_projeto', false);
                                                })

                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Campo Obrigatório',
                                                ]),

                                            TextInput::make('numero_relatorio_vt')
                                                ->label('Número do relatório')
                                                ->disabled()
                                                ->dehydrated(true),

                                            DateTimePicker::make('iniciado_em')
                                                ->label('Iniciado em')
                                                ->default(now())
                                                ->disabled()
                                                ->dehydrated(true),

                                            TextInput::make('autor')
                                                ->label('Autor')
                                                ->default(fn () => Filament::auth()->user()?->name) // nome do usuário logado
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->required(),
                                        ]),
                                ]),
                        ]),

                    Step::make('Área 1 – Informações Técnicas')
                        ->schema([
                            Section::make('Dados Técnicos da Unidade')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Section::make('Informações Gerais')
                                                ->schema([
                                                    Grid::make(2)->schema([
                                                        TextInput::make('unidade')
                                                            ->label('Unidade')
                                                            ->readOnly()
                                                            ->dehydrated(true),

                                                        Select::make('marca_id')
                                                            ->label('Marca')
                                                            ->relationship(
                                                                name: 'marca',
                                                                titleAttribute: 'nome',
                                                                modifyQueryUsing: fn ($query) => $query->whereNotNull('nome')
                                                            )
                                                            ->getOptionLabelFromRecordUsing(fn (Marca $record): string => $record->nome ?? 'Marca sem nome')
                                                            ->searchable()
                                                            ->preload()
                                                            // ->disabled(fn($get) => ! $get('novo_projeto'))
                                                            ->dehydrated(true)
                                                            ->required(fn ($get) => $get('novo_projeto')),

                                                        TextInput::make('endereco')
                                                            ->label('Endereço')
                                                            ->columnSpanFull()
                                                            // ->readOnly(fn($get) => !$get('novo_projeto'))
                                                            ->dehydrated(true)
                                                            ->required(fn ($get) => $get('novo_projeto')),

                                                        Select::make('pavimento')
                                                            ->label('Configuração de pavimentos')
                                                            ->options(function ($get, $record) {
                                                                $options = [
                                                                    'Subsolo' => 'Subsolo',
                                                                    'Térreo ( Sem subsolo)' => 'Térreo ( Sem subsolo)',
                                                                    'Térreo ( Com subsolo)' => 'Térreo ( Com subsolo)',
                                                                    '1° pavimento' => '1° pavimento',
                                                                    '2° pavimento' => '2° pavimento',
                                                                    'Outro (descrever)' => 'Outro (descrever)',
                                                                ];

                                                                $values = [];

                                                                if ($record && is_array($record->pavimento)) {
                                                                    $values = array_merge($values, $record->pavimento);
                                                                }

                                                                $state = $get('pavimento');

                                                                if (is_array($state)) {
                                                                    $values = array_merge($values, $state);
                                                                } elseif (! is_null($state)) {
                                                                    $values[] = $state;
                                                                }

                                                                foreach ($values as $item) {
                                                                    if (is_null($item) || $item === '') {
                                                                        continue;
                                                                    }

                                                                    $item = (string) $item;

                                                                    if (! isset($options[$item])) {
                                                                        $options[$item] = $item;
                                                                    }
                                                                }

                                                                return $options;
                                                            })
                                                            ->multiple()
                                                            // ->disabled(fn($get) => ! $get('novo_projeto'))
                                                            ->required(fn ($get) => $get('novo_projeto'))
                                                            ->dehydrated(true)
                                                            ->native(false)
                                                            ->searchable(false)
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if (! in_array('Outro (descrever)', $state ?? [], true)) {
                                                                    $set('pavimento_outro', null);
                                                                }
                                                            })
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        TextInput::make('pavimento_outro')
                                                            ->label('Informe a configuração de pavimento')
                                                            ->visible(fn (callable $get) => in_array('Outro (descrever)', $get('pavimento') ?? [], true))
                                                            ->required(fn (callable $get) => in_array('Outro (descrever)', $get('pavimento') ?? [], true))
                                                            ->dehydrated(fn (callable $get) => in_array('Outro (descrever)', $get('pavimento') ?? [], true))
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        Select::make('empreendimento')
                                                            ->label('Empreendimento')
                                                            ->options(function ($get, $record) {
                                                                $options = [
                                                                    'Shopping' => 'Shopping',
                                                                    'Rua' => 'Rua',
                                                                    'Supermercado' => 'Supermercado',
                                                                    'Mall' => 'Mall',
                                                                    'Edifício Comercial' => 'Edifício Comercial',
                                                                    'Outro (descrever)' => 'Outro (descrever)',
                                                                ];

                                                                $values = [];

                                                                if ($record && ! is_null($record->empreendimento)) {
                                                                    $values[] = $record->empreendimento;
                                                                }

                                                                $state = $get('empreendimento');

                                                                if (! is_null($state)) {
                                                                    $values[] = $state;
                                                                }

                                                                foreach ($values as $item) {
                                                                    if (is_null($item) || $item === '') {
                                                                        continue;
                                                                    }

                                                                    $item = (string) $item;

                                                                    if (! isset($options[$item])) {
                                                                        $options[$item] = $item;
                                                                    }
                                                                }

                                                                return $options;
                                                            })
                                                            // ->disabled(fn($get) => !$get('novo_projeto'))
                                                            ->required(fn ($get) => $get('novo_projeto'))
                                                            ->dehydrated(true)
                                                            ->native(false)
                                                            ->searchable(false),

                                                        TextInput::make('empreendimento_outro')
                                                            ->label('Informe o empreendimento')
                                                            ->visible(fn (callable $get) => $get('empreendimento') === 'Outro (descrever)')
                                                            ->required(fn (callable $get) => $get('empreendimento') === 'Outro (descrever)')
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        Select::make('locacao')
                                                            ->label('Locação')
                                                            ->options(function ($get, $record) {
                                                                $options = [
                                                                    'Monousuário' => 'Monousuário',
                                                                    'Multiusuário' => 'Multiusuário',
                                                                ];

                                                                $values = [];

                                                                if ($record && ! is_null($record->locacao)) {
                                                                    $values[] = $record->locacao;
                                                                }

                                                                $state = $get('locacao');

                                                                if (! is_null($state)) {
                                                                    $values[] = $state;
                                                                }

                                                                foreach ($values as $item) {
                                                                    if (is_null($item) || $item === '') {
                                                                        continue;
                                                                    }

                                                                    $item = (string) $item;

                                                                    if (! isset($options[$item])) {
                                                                        $options[$item] = $item;
                                                                    }
                                                                }

                                                                return $options;
                                                            })
                                                            // ->disabled(fn($get) => !$get('novo_projeto'))
                                                            ->required(fn ($get) => $get('novo_projeto'))
                                                            ->dehydrated(true)
                                                            ->native(false)
                                                            ->searchable(false),

                                                        ToggleButtons::make('validador_ticket_estacionamento')
                                                            ->label('Validador de Ticket de Estacionamento')
                                                            ->options([
                                                                1 => 'Sim',
                                                                0 => 'Não',
                                                            ])
                                                            ->colors([
                                                                1 => 'success',
                                                                0 => 'danger',
                                                            ])
                                                            ->inline()
                                                            ->visible(fn ($get) => $get('locacao') === 'Multiusuário')
                                                            ->required(fn ($get) => $get('locacao') === 'Multiusuário'),

                                                        TextInput::make('contato_responsavel')
                                                            ->label('Contato do Responsável')
                                                            // ->readOnly(fn($get) => !$get('novo_projeto'))
                                                            ->required(fn ($get) => $get('novo_projeto'))
                                                            ->dehydrated(true),
                                                    ])
                                                        ->extraAttributes(['class' => 'gap-2']),
                                                ])
                                                ->columnSpanFull(),

                                            Section::make('Imóvel')
                                                ->schema([
                                                    Grid::make(2)->schema([
                                                        Radio::make('condicoes_imovel')
                                                            ->label('Condições em que o imóvel se encontra')
                                                            ->options([
                                                                'Imóvel pronto e desocupado' => 'Imóvel pronto e desocupado',
                                                                'Imóvel pronto com ocupação (informar prazo de desocupação)' => 'Imóvel pronto com ocupação (informar prazo de desocupação)',
                                                                'Imóvel pronto com adequações contratuais (Informar quais adequações e respectivos prazo)' => 'Imóvel pronto com adequações contratuais (Informar quais adequações e respectivos prazo)',
                                                                'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)' => 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)',
                                                                'Terreno (Sem demolição)' => 'Terreno (Sem demolição)',
                                                                'Terreno (Com demolição)' => 'Terreno (Com demolição)',
                                                            ])
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                $bts = 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)';
                                                                $ocupacao = 'Imóvel pronto com ocupação (informar prazo de desocupação)';

                                                                if ($state !== $bts) {
                                                                    $set('contrato_bts', null);
                                                                    $set('prazo_bts', null);
                                                                }

                                                                if ($state !== $ocupacao) {
                                                                    $set('prazo_desocupacao', null);
                                                                }
                                                            })
                                                            ->required()
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        Textarea::make('comentario_condicoes_imovel')
                                                            ->label('Comentário das condições do imóvel')
                                                            ->rows(3),

                                                        FileUpload::make('contrato_bts')
                                                            ->label('Anexar contrato')
                                                            ->multiple()
                                                            ->visible(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->required(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->dehydrated(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->acceptedFileTypes([
                                                                'application/pdf',
                                                                'image/jpeg',
                                                                'image/png',
                                                            ])

                                                            ->panelLayout('grid')
                                                            ->columnSpan(1)->disk((string) config('filesystems.media_disk', 'r2'))
                                                            ->visibility('public')
                                                            ->previewable(true)
                                                            ->fetchFileInformation(false)
                                                            ->openable()
                                                            ->downloadable()
                                                            ->imagePreviewHeight('250')
                                                            ->saveUploadedFileUsing(static::saveRelatorioUpload('contrato_bts'))
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        DatePicker::make('prazo_bts')
                                                            ->label('Prazo previsto')
                                                            ->visible(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->required(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->dehydrated(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                                            )
                                                            ->displayFormat('d/m/Y')
                                                            ->columnSpan(1)
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),

                                                        DatePicker::make('prazo_desocupacao')
                                                            ->label('Prazo de desocupação')
                                                            ->visible(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'Imóvel pronto com ocupação (informar prazo de desocupação)'
                                                            )
                                                            ->required(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'Imóvel pronto com ocupação (informar prazo de desocupação)'
                                                            )
                                                            ->dehydrated(
                                                                fn (Get $get) => $get('condicoes_imovel') === 'Imóvel pronto com ocupação (informar prazo de desocupação)'
                                                            )
                                                            ->displayFormat('d/m/Y')
                                                            ->columnSpanFull()
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),
                                                    ])
                                                        ->extraAttributes(['class' => 'gap-2']),
                                                ])
                                                ->columnSpanFull(),

                                            Section::make('Prazo e Contrato')
                                                ->schema([
                                                    Grid::make(2)->schema([
                                                        Radio::make('prazo_de_obras')
                                                            ->label('Prazo de obras')
                                                            ->options([
                                                                '85 dias' => '85 dias',
                                                                '90 dias' => '90 dias',
                                                                '100 dias' => '100 dias',
                                                                '120 dias' => '120 dias',
                                                                'outro' => 'Outro',
                                                            ])
                                                            ->reactive()
                                                            ->required()
                                                            ->validationMessages(
                                                                [
                                                                    'required' => 'Campo Obrigatório',
                                                                ],
                                                            ),

                                                        TextInput::make('prazo_de_obras_outro')
                                                            ->label('Informe o prazo')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->visible(fn (callable $get) => $get('prazo_de_obras') === 'outro')
                                                            ->required(fn (callable $get) => $get('prazo_de_obras') === 'outro')
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                                'numeric' => 'Informe apenas números',
                                                                'min' => 'O valor não pode ser negativo',
                                                            ]),

                                                        Textarea::make('descricao_prazo_obras')
                                                            ->label('Descrição do prazo de obras')
                                                            ->rows(3)
                                                            ->reactive()
                                                            ->columnSpan(function ($get) {

                                                                if ($get('prazo_de_obras') === 'outro') {
                                                                    return 'full';
                                                                }

                                                                return 1;
                                                            }),

                                                        Radio::make('etapa_contrato')
                                                            ->label('Etapa de contrato:')
                                                            ->options([
                                                                'Negociação comercial' => 'Negociação comercial',
                                                                'Em minuta' => 'Em minuta',
                                                                'Contrato assinado' => 'Contrato assinado',
                                                            ])
                                                            ->required()
                                                            ->validationMessages([
                                                                'required' => 'Campo Obrigatório',
                                                            ]),
                                                    ])
                                                        ->extraAttributes(['class' => 'gap-2']),
                                                ])
                                                ->columnSpanFull(),

                                            Section::make('Projeto')
                                                ->schema([
                                                    Grid::make(2)->schema([
                                                        ToggleButtons::make('planta_demarcacao_area')
                                                            ->label('Foi disponibilizado projeto ou planta com demarcação da área ?')
                                                            ->options([
                                                                true => 'Sim',
                                                                false => 'Não',
                                                            ])
                                                            ->colors([
                                                                true => 'success',
                                                                false => 'danger',
                                                            ])
                                                            ->inline()
                                                            ->helperText('Caso "sim" incluir aqui o projeto e confirmar medidas.')
                                                            ->columnSpan(1)
                                                            ->live()
                                                            ->required()
                                                            ->validationMessages(
                                                                [
                                                                    'required' => 'Campo Obrigatório',
                                                                ],
                                                            ),

                                                        Textarea::make('descricao_planta_demarcacao_area')
                                                            ->label('Descrição do projeto ou planta')
                                                            ->columnSpan(1)
                                                            ->rows(3),

                                                        TextInput::make('link_planta_demarcacao_area')
                                                            ->label('Link do projeto ou planta com demarcação da área')
                                                            ->url()
                                                            ->prefix('https://')
                                                            ->placeholder('https://drive.google.com/...')
                                                            ->helperText('Insira um link válido')
                                                            ->columnSpanFull()
                                                            ->required(fn (callable $get) => $get('planta_demarcacao_area') === 1)
                                                            ->validationMessages(
                                                                [
                                                                    'required' => 'Campo Obrigatório',
                                                                ],
                                                            ),

                                                        Section::make('Foto da planta ou projeto')
                                                            ->schema([
                                                                FileUpload::make('foto_planta_demarcacao_area')
                                                                    ->label('Anexar imagem e/ou vídeo')
                                                                    ->multiple()
                                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                                    ->visibility('public')
                                                                    ->previewable(true)
                                                                    ->fetchFileInformation(false)
                                                                    ->openable()
                                                                    ->downloadable()
                                                                    ->panelLayout('grid')
                                                                    ->imagePreviewHeight('200')
                                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('planta'))
                                                                    ->acceptedFileTypes([
                                                                        'image/jpeg',
                                                                        'image/png',
                                                                        'video/*',
                                                                    ])
                                                                    ->maxSize(204800),
                                                            ])
                                                            ->columnSpanFull()
                                                            ->collapsible()
                                                            ->collapsed(fn () => true),

                                                    ])
                                                        ->extraAttributes(['class' => 'gap-2']),
                                                ])
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),

                    Step::make('Área 2 – Elétrica / Telefonia / Internet')
                        ->schema([
                            Section::make('Energia Definitiva')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Radio::make('entrada_de_energia')
                                            ->label('Entrada de energia - Tensão disponível')
                                            ->options([
                                                '380_220' => '380/220V',
                                                '220_127' => '220/127V',
                                                'nao_informado' => 'Não informado',
                                            ])
                                            ->inline()
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_energia')
                                            ->label('Descrição da entrada de energia')
                                            ->columnSpan(1)
                                            ->rows(3),

                                        Section::make('Foto entrada de energia')
                                            ->schema([
                                                FileUpload::make('foto_entrada_de_energia')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('entrada_de_energia'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),

                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('energia_carga_superior_150')
                                            ->label('Entrada de energia - Temos disponível carga superior a 150kVA?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->helperText('Informar potência do trafo.')
                                            ->inline()
                                            ->columnSpan(1)
                                            ->required()
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            ),

                                        Textarea::make('descricao_energia_carga_superior_150')
                                            ->label('Descrição da energia com carga superior a 150kVA:')
                                            ->columnSpan(1)
                                            ->rows(3),

                                        Section::make('Foto da entrada de energia com carga superior a 150kVA:')
                                            ->schema([
                                                FileUpload::make('foto_energia_carga_superior_150')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('carga_superior_150'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('energia_provisoria')
                                            ->label('Temos energia provisória para obra ?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->columnSpan(1)
                                            ->required()
                                            ->helperText('Informar amperagem do disjuntor geral.')
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            ),

                                        Textarea::make('descricao_energia_provisoria')
                                            ->label('Descrição da energia provisória')
                                            ->columnSpan(1)
                                            ->rows(3),

                                        Section::make('Foto Energia provisória para obra')
                                            ->schema([
                                                FileUpload::make('foto_energia_provisoria')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpan(2)
                                                    ->panelLayout('grid')->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('energia_provisoria'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('cabos_alimentadores_shell')
                                            ->label('Cabos alimentadores entregues dentro do shell?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->reactive()
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                            ]),

                                        TextInput::make('metros_cabeamento')
                                            ->label('Quantidade de metros para cabeamento')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('m')
                                            ->visible(fn ($get) => $get('cabos_alimentadores_shell') === 0)
                                            ->required(fn ($get) => $get('cabos_alimentadores_shell') === 0)
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        ToggleButtons::make('unica_medicao')
                                            ->label('Única medição?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Inserir informação do medidor (se houver).')
                                            ->columnSpan(1)
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_medicao')
                                            ->label('Descrição da medição')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('cabos_alimentadores_shell') === 0) {
                                                    return 1;
                                                }

                                                return 'full';
                                            }),

                                        Section::make('Foto Única medição')
                                            ->schema([
                                                FileUpload::make('foto_unica_medicao')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('unica_medicao'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('necessario_visita_consultor_energia')
                                            ->label('É necessário a visita do consultor de energia?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->live()
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),
                                    ])->extraAttributes(['class' => 'gap-2']),
                                ]),

                            Section::make('Proteção')
                                ->schema([
                                    Grid::make(4)->schema([

                                        ToggleButtons::make('spda')
                                            ->label('SPDA existente ?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->columnSpan(2)
                                            ->helperText('Informar se recebemos teste de impedância.')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_spda')
                                            ->label('Descrição do SPDA')
                                            ->columnSpan(2)
                                            ->rows(3),

                                        Section::make('Foto SPDA')
                                            ->schema([
                                                FileUpload::make('foto_spda')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('spda'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),

                            Section::make('Telefonia')
                                ->schema([
                                    Grid::make([
                                        'default' => 1, // celular
                                        'md' => 4,
                                    ])->schema([
                                        ToggleButtons::make('telegonia_dg')
                                            ->label('Telefonia (DG) dentro do Shell ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state !== 0) {
                                                    $set('distancia_ponto_telefonia', null);
                                                }
                                            })
                                            ->required()
                                            ->reactive()
                                            ->helperText('Em caso negativo, informar ponto mais próximo a ser trazido.')
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            )
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        TextInput::make('distancia_ponto_telefonia')
                                            ->label('Distância até o ponto mais próximo')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('m')
                                            ->visible(fn (callable $get) => $get('telegonia_dg') === 0)
                                            ->required(fn (callable $get) => $get('telegonia_dg') === 0)
                                            ->dehydrated(fn (callable $get) => $get('telegonia_dg') === 0)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                                'numeric' => 'Informe apenas números',
                                                'min' => 'O valor não pode ser negativo',
                                            ]),

                                        Textarea::make('descricao_telefonia')
                                            ->label('Descrição da telefonia')
                                            ->required(fn ($get) => $get('telegonia_dg') === 0)
                                            ->rows(3)
                                            ->columnSpan(function ($get) {

                                                if ($get('telegonia_dg') === 0) {
                                                    return 'full';
                                                }

                                                return 2;
                                            }),

                                        Section::make('Foto Telefonia (DG)')
                                            ->schema([
                                                FileUpload::make('foto_telegonia_dg')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('telegonia_dg'))
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),
                        ]),

                    Step::make('Área 3 - Estrutura / Cobertura / Acústica')
                        ->schema([
                            Section::make('Estrutura')
                                ->schema([
                                    Grid::make([
                                        'default' => 1, // celular
                                        'md' => 4,      // desktop mantém 4 colunas
                                    ])->schema([

                                        Radio::make('tipo_estrutura')
                                            ->label('Tipo de estrutura')
                                            ->options([
                                                'Estrutura de concreto armado' => 'Estrutura de concreto armado',
                                                'Estrutura pre-moldada com laje alveolar' => 'Estrutura pre-moldada com laje alveolar',
                                                'Estrutura metálica' => 'Estrutura metálica',
                                                'Estrutura de concreto armado com vigas invertidas' => 'Estrutura de concreto armado com vigas invertidas',
                                                'estrutura_mista' => 'Estrutura Mista (descrever)',
                                                'outro' => 'Outros (descrever)',
                                            ])
                                            ->reactive()
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_estrutura_auxiliar')
                                            ->label('Observação')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        TextInput::make('tipo_estrutura_outro')
                                            ->label('Informe o tipo de estrutura')
                                            ->visible(fn (callable $get) => in_array($get('tipo_estrutura'), ['outro', 'estrutura_mista']))
                                            ->required(fn (callable $get) => in_array($get('tipo_estrutura'), ['outro', 'estrutura_mista']))
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Section::make('Foto Estrutura')
                                            ->schema([
                                                FileUpload::make('foto_necessario_estrutura_auxiliar')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('estrutura_auxiliar'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->markAsRequired()
                                                // ->validationMessages([
                                                // 'required' => 'Campo Obrigatório'
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('cobertura_vao_1_5')
                                            ->label('Cobertura com vãos inferiores a 1,5m nos dois sentidos?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar espaçamento se maior de 1,5m, incluir pelo menos 3 fotos da cobertura.')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('cobertura_vao_1_5_metragem')
                                            ->label('Informar a metragem quadrada do reforço estrutural')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('cobertura_vao_1_5') === 0)
                                            ->required(fn ($get) => $get('cobertura_vao_1_5') === 0)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_cobertura_vao_1_5')
                                            ->label('Descrição da cobertura')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('cobertura_vao_1_5') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto da cobertura')
                                            ->schema([
                                                FileUpload::make('foto_cobertura_vao_1_5')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('cobertura_vao_1_5'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages([
                                                // 'required' => 'Campo Obrigatório'
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('estrutura_fachada')
                                            ->label('Imóvel com estrutura para fachada ?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Inserir pelo menos 3 fotos da fachada, anexar no drive video com fluxo de pessoas.')
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_estrutura_fachada')
                                            ->label('Descrição da estrutura para fachada')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        Section::make('Foto Estrutura de fachada')
                                            ->schema([
                                                FileUpload::make('foto_estrutura_fachada')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('estrutura_fachada'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages([
                                                // 'required' => 'Campo Obrigatório'
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),

                            Section::make('Cobertura')
                                ->schema([
                                    Grid::make(4)->schema([
                                        ToggleButtons::make('cobertura_isolamento')
                                            ->label('Cobertura com isolamento térmico')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->required()
                                            ->reactive()
                                            ->helperText('Informar tipo de cobertura, e se há mais de um tipo.')
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            )
                                            ->columnSpan(2),

                                        TextInput::make('cobertura_area_isolamento')
                                            ->label('Qual a área que necessita de isolamento térmico?')
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('cobertura_isolamento') === 0)
                                            ->required(fn ($get) => $get('cobertura_isolamento') === 0)
                                            ->columnSpan(2)
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_cobertura_isolamento')
                                            ->label('Descrição do isolamento da cobertura')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('cobertura_isolamento') === 0) {
                                                    return 'full';
                                                }

                                                return 2;
                                            }),

                                        Section::make('Foto da Cobertura com isolamento térmico')
                                            ->schema([
                                                FileUpload::make('foto_cobertura_isolamento')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('cobertura_isolamento'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages(
                                                // [
                                                // 'required' => 'Campo Obrigatório'
                                                // ],
                                                // ),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),

                            Section::make('Lajes e Sobrecargas')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('permitidas_furacoes_laje')
                                            ->label('Permitidas furações de laje')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar se há operação funcionando no pavimento de baixo.')
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_furacoes_laje')
                                            ->label('Descrição das furações na laje')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        Section::make('Foto Permitidas furações de laje')
                                            ->schema([
                                                FileUpload::make('foto_permitidas_furacoes_laje')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('furacoes_laje'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('sobrecarga_minima_laje')
                                            ->label('Sobrecarga mínima da laje (500kg/m²)')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Marcar sim, somente com projeto estrutural ou laudo')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Radio::make('comprovacao_sobrecarga_laje')
                                            ->label('Comprovação da sobrecarga da laje')
                                            ->options([
                                                'Recebido projeto estrutural' => 'Recebido projeto estrutural',
                                                'Recebido Laudo' => 'Recebido Laudo',
                                                'Não recebido' => 'Não recebido',
                                            ])
                                            ->inline()
                                            ->visible(fn ($get) => $get('sobrecarga_minima_laje') === 1)
                                            ->required(fn ($get) => $get('sobrecarga_minima_laje') === 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_sobrecarga_minima_laje')
                                            ->label('Descrição da sobrecarga da laje')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('sobrecarga_minima_laje') === 1) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Sobrecarga mínima da laje (500kg/m²)')
                                            ->schema([
                                                FileUpload::make('foto_sobrecarga_minima_laje')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('sc_min_laje'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('sobrecarga_minima_laje_teto')
                                            ->label('Sobrecarga mínima de laje de teto (35kg/m²)')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Marcar sim, somente com projeto estrutural ou laudo')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Radio::make('comprovacao_sobrecarga_laje_teto')
                                            ->label('Comprovação da sobrecarga da laje de teto')
                                            ->options([
                                                'Recebido projeto estrutural' => 'Recebido projeto estrutural',
                                                'Recebido Laudo' => 'Recebido Laudo',
                                                'Não recebido' => 'Não recebido',
                                            ])
                                            ->inline()
                                            ->visible(fn ($get) => $get('sobrecarga_minima_laje_teto') === 1)
                                            ->required(fn ($get) => $get('sobrecarga_minima_laje_teto') === 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_sobrecarga_minima_laje_teto')
                                            ->label('Descrição da sobrecarga no teto')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('sobrecarga_minima_laje_teto') === 1) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Sobrecarga mínima de laje de teto (35kg/m²)')
                                            ->schema([
                                                FileUpload::make('foto_sobrecarga_minima_laje_teto')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('sc_min_laje_teto'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),

                            Section::make('Exaustão e Vedação')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('local_tomada_ar_externo_exaustao')
                                            ->label('Existe local para tomada de ar externo/ exaustão')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Inserir marcação em planta das paredes disponíveis.')
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_local_tomada_ar_externo_exaustao')
                                            ->label('Descrição do ponto de exaustão/ar')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        Section::make('Foto local para tomada de ar externo / exaustão')
                                            ->schema([
                                                FileUpload::make('foto_local_tomada_ar_externo_exaustao')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('tomada_ar_ext_exaust'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('alvenaria_periferia_existente')
                                            ->label('Alvenaria de periferia existente ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->reactive()
                                            ->helperText('Inserir foto de toda periferia')
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('metros_alvenaria_periferia')
                                            ->label('Quantidade de metros quadrados de alvenaria a executar')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            ->reactive()
                                            ->visible(fn ($get) => $get('alvenaria_periferia_existente') === 0)
                                            ->required(fn ($get) => $get('alvenaria_periferia_existente') === 0)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Informe a quantidade de metros quadrados.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_alvenaria_periferia_existente')
                                            ->label('Descrição da alvenaria da periferia')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('alvenaria_periferia_existente') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Alvenaria de periferia existente')
                                            ->schema([
                                                FileUpload::make('foto_alvenaria_periferia_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('alv_periferia_exist'))
                                                    ->maxSize(204800),
                                                // ->required(fn($get) => $get('alvenaria_periferia_existente') === 1)
                                                // ->validationMessages([
                                                // 'required' => 'É obrigatório inserir foto/ vídeo da alvenaria de periferia.',
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('reboco_interno_externo_existente')
                                            ->label('Reboco interno e externo existente ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->reactive()
                                            ->helperText('Inserir foto do reboco externo')
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('metros_reboco')
                                            ->label('Quantidade de metros quadrados de reboco a executar')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('reboco_interno_externo_existente') === 0)
                                            ->required(fn ($get) => $get('reboco_interno_externo_existente') === 0)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Informe a quantidade de metros quadrados.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_reboco_interno_externo_existente')
                                            ->label('Descrição do reboco interno/externo')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('reboco_interno_externo_existente') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto do Reboco interno e externo existente')
                                            ->schema([
                                                FileUpload::make('foto_reboco_interno_externo_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('reboco_int_ext_exist'))
                                                    ->maxSize(204800),
                                                // ->required(fn($get) => $get('reboco_interno_externo_existente') === 1)
                                                // >validationMessages([
                                                // 'required' => 'É obrigatório inserir foto do reboco.',
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('estanqueidade')
                                            ->label('Necessita de estanqueidade ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->reactive()
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Radio::make('descricao_estanqueidade')
                                            ->label('Descrição da estanqueidade')
                                            ->options([
                                                'Vazamento de telhado' => 'Vazamento de telhado',
                                                'Falta de veda onda' => 'Falta de veda onda',
                                                'Falta de caixinhos' => 'Falta de caixinhos',
                                                'Falta de fechamento em alvenaria' => 'Falta de fechamento em alvenaria',
                                                'outro' => 'Outro (descrever)',
                                            ])
                                            ->reactive()
                                            ->visible(fn ($get) => $get('estanqueidade') == 1)
                                            ->required(fn ($get) => $get('estanqueidade') == 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('estanqueidade_outro')
                                            ->label('Informe a descrição da estanqueidade')
                                            ->visible(fn ($get) => $get('descricao_estanqueidade') === 'outro')
                                            ->required(fn ($get) => $get('descricao_estanqueidade') === 'outro')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_complementar_estanqueidade')
                                            ->label('Descrição complementar da estanqueidade')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Section::make('Foto Estanqueidade')
                                            ->schema([
                                                FileUpload::make('foto_estanqueidade')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('estanqueidade'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),
                        ]),

                    Step::make('Área 4 – Área Técnica')
                        ->schema([
                            Section::make('Área Técnica Externa')
                                ->schema([
                                    Grid::make(4)->schema([
                                        ToggleButtons::make('area_tecnica_externa_existente')
                                            ->label('Área técnica externa existente')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText(' Indicar em planta marcação ou sugestão de locais.')
                                            ->columnSpan(2)
                                            ->required()
                                            ->reactive()
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            ),

                                        Textarea::make('descricao_area_tecnica_externa_existente')
                                            ->label('Descrição da área técnica externa')
                                            ->columnSpan(2)
                                            ->rows(3)
                                            ->required(fn ($get) => $get('area_tecnica_externa_existente') === 0),

                                        Section::make('Foto Área técnica externa existente')
                                            ->schema([
                                                FileUpload::make('foto_area_tecnica_externa_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('area_tec_ext_exist'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages(
                                                // [
                                                // 'required' => 'Campo Obrigatório'
                                                // ],
                                                // ),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                    Grid::make(4)->schema([
                                        ToggleButtons::make('prever_acustica_condensadores')
                                            ->label('Prever acústica de condensadoras')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->columnSpan(2)
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_prever_acustica_condensadores')
                                            ->label('Descrição do tratamento acústico')
                                            ->columnSpan(2)
                                            ->rows(3),

                                        Section::make('Foto da acústica de condensadoras')
                                            ->schema([
                                                FileUpload::make('foto_prever_acustica_condensadores')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('acustica_condensadores'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages(
                                                // [
                                                // 'required' => 'Campo Obrigatório'
                                                // ],
                                                // ),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('prever_protecao_condensadores')
                                            ->label('Prever proteção para condensadoras')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->columnSpan(2)
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_prever_protecao_condensadores')
                                            ->label('Descrição da proteção para condensadores')
                                            ->columnSpan(2)
                                            ->rows(3),

                                        Section::make('Foto Prever proteção para condensadoras')
                                            ->schema([
                                                FileUpload::make('foto_prever_protecao_condensadores')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('protecao_condensadores'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages(
                                                // [
                                                // 'required' => 'Campo Obrigatório'
                                                // ],
                                                // ),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),
                        ]),

                    Step::make('Área 5 – Hidráulica / Esgoto / Gás')
                        ->schema([
                            Section::make('Reservatórios')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('reservatorio_agua_existente')
                                            ->label('Reservatório de água existente')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar quantos litros.')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('reservatorio_agua_litragem')
                                            ->label('Qual o volume do reservatório?')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('L')
                                            ->visible(fn ($get) => $get('reservatorio_agua_existente') === 1)
                                            ->required(fn ($get) => $get('reservatorio_agua_existente') === 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_reservatorio_agua_existente')
                                            ->label('Descrição do reservatório de água')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('reservatorio_agua_existente') === 1) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Reservatório de água existente')
                                            ->schema([
                                                FileUpload::make('foto_reservatorio_agua_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('reserv_agua_exist'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('reservatorio_incendio_existente')
                                            ->label('Reservatório de incêndio existente')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar quantos litros.')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('reservatorio_incendio_litragem')
                                            ->label('Qual o volume do reservatório de incêndio?')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('L')
                                            ->visible(fn ($get) => $get('reservatorio_incendio_existente') === 1)
                                            ->required(fn ($get) => $get('reservatorio_incendio_existente') === 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_reservatorio_incendio_existente')
                                            ->label('Descrição do reservatório de incêndio')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('reservatorio_incendio_existente') === 1) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Reservatório de incêndio existente')
                                            ->schema([
                                                FileUpload::make('foto_reservatorio_incendio_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('reserv_incendio_exist'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),

                            Section::make('Esgoto e Gás')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        Grid::make([
                                            'default' => 1,
                                            'md' => 4,
                                        ])->schema([
                                            ToggleButtons::make('ponto_esgoto_existente_shell')
                                                ->label('Ponto de esgoto existente dentro do shell')
                                                ->options([
                                                    1 => 'Sim',
                                                    0 => 'Não',
                                                ])
                                                ->colors([
                                                    1 => 'success',
                                                    0 => 'danger',
                                                ])
                                                ->inline()
                                                ->helperText('Informar em caso de fossa. Informar em caso de esgoto bombeado. Verificar profundidade e caimento da rede.')
                                                ->required()
                                                ->live()
                                                ->columnSpan([
                                                    'default' => 1,
                                                    'md' => 2,
                                                ])
                                                ->validationMessages([
                                                    'required' => 'Campo Obrigatório',
                                                ]),

                                            TextInput::make('ponto_esgoto_mais_proximo')
                                                ->label('Distância até o ponto mais próximo?')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->suffix('m')
                                                ->visible(fn ($get) => $get('ponto_esgoto_existente_shell') === 0)
                                                ->required(fn ($get) => $get('ponto_esgoto_existente_shell') === 0)
                                                ->columnSpan([
                                                    'default' => 1,
                                                    'md' => 2,
                                                ])
                                                ->validationMessages([
                                                    'required' => 'Campo obrigatório.',
                                                    'numeric' => 'Informe apenas números.',
                                                    'min' => 'O valor não pode ser negativo.',
                                                ]),
                                        ])
                                            ->columnSpanFull(),

                                        Textarea::make('descricao_ponto_esgoto_existente_shell')
                                            ->label('Descrição do ponto de esgoto')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Section::make('Foto do Ponto de esgoto existente dentro do shell')
                                            ->schema([
                                                FileUpload::make('foto_ponto_esgoto_existente_shell')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('esgoto_exist_shell'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        Radio::make('rede_gas_disponivel')
                                            ->label('Rede de gás disponível')
                                            ->options([
                                                'GN (instalado)' => 'GN (instalado)',
                                                'GN (solicitar ligação)' => 'GN (solicitar ligação)',
                                                'GLP (Abrigo existente)' => 'GLP (Abrigo existente)',
                                                'GLP (Construir abrigo)' => 'GLP (Construir abrigo)',
                                                'Boiler' => 'Boiler',
                                            ])
                                            ->helperText('Inserir foto e posicionamento em planta do local indicado para cavalete (GN) ou abrigo (GLP).')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state !== 'GN (solicitar ligação)') {
                                                    $set('distancia_rede_gas', null);
                                                }
                                            })
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('distancia_rede_gas')
                                            ->label('Distância até o ponto mais próximo')
                                            ->numeric()
                                            ->step('0.01')
                                            ->minValue(0)
                                            ->suffix('m')
                                            ->hidden(fn ($get) => $get('rede_gas_disponivel') !== 'GN (solicitar ligação)')
                                            ->required(fn ($get) => $get('rede_gas_disponivel') === 'GN (solicitar ligação)')
                                            ->dehydrated(fn ($get) => $get('rede_gas_disponivel') === 'GN (solicitar ligação)')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                                'numeric' => 'Informe apenas números',
                                                'min' => 'O valor não pode ser negativo',
                                            ]),

                                        Textarea::make('descricao_rede_gas_disponivel')
                                            ->label('Descrição da rede de gás')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Section::make('Foto Rede de gás disponível')
                                            ->schema([
                                                FileUpload::make('foto_rede_gas_disponivel')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('gas_disp'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),

                            Section::make('Medição e Sistema de Incêndio')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('medidor_agua_instalado_ligado')
                                            ->label('Medidor de água instalado e ligado')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Inserir numero da instalação')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('numero_instalacao_agua')
                                            ->label('Número da instalação')
                                            ->visible(fn ($get) => $get('medidor_agua_instalado_ligado') === 1)
                                            ->required(fn ($get) => $get('medidor_agua_instalado_ligado') === 1)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_medidor_agua_instalado_ligado')
                                            ->label('Descrição do medidor de água')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('medidor_agua_instalado_ligado') === 1) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto do Medidor de água instalado e ligado')
                                            ->schema([
                                                FileUpload::make('foto_medidor_agua_instalado_ligado')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('med_agua_inst'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        Select::make('sistema_incendio_existente')
                                            ->label('Sistema de incêndio existente')
                                            ->helperText('Em caso de multiusuário, informar o que existe no restante do empreendimento.')
                                            ->multiple()
                                            ->options([
                                                'Extintor' => 'Extintor',
                                                'Hidrante' => 'Hidrante',
                                                'SPK' => 'SPK',
                                                'Não há sistema existente' => 'Não há sistema existente',
                                            ])
                                            ->required()
                                            ->rules([
                                                function (): Closure {
                                                    return function (string $attribute, $value, Closure $fail): void {
                                                        if (! is_array($value)) {
                                                            return;
                                                        }

                                                        if (
                                                            in_array('Não há sistema existente', $value, true) &&
                                                            count($value) > 1
                                                        ) {
                                                            $fail('A opção "Não há sistema existente" não pode ser selecionada junto com outros itens.');
                                                        }
                                                    };
                                                },
                                            ])
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_sistema_incendio_existente')
                                            ->label('Descrição do sistema de incêndio')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        Section::make('Foto Sistema de incêndio existente')
                                            ->schema([
                                                FileUpload::make('foto_sistema_incendio_existente')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('sist_incendio_exist'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),
                        ]),

                    Step::make('Área 6 – Arquitetura / Civil')
                        ->schema([
                            Section::make('Altura e Acessibilidade')
                                ->schema([
                                    Grid::make(4)->schema([
                                        ToggleButtons::make('pd_acima_livre')
                                            ->label('PD acima de 3,5 m livres')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar PD de todas as áreas.')
                                            ->columnSpan(2)
                                            ->required()
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            ),

                                        Textarea::make('descricao_pd_acima_livre')
                                            ->label('Descrição do pé-direito')
                                            ->columnSpan(2)
                                            ->rows(3),

                                        Section::make('Foto do PD acima de 3,5 m livre')
                                            ->schema([
                                                FileUpload::make('foto_pd_acima_livre')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('pd_acima_liv'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('necessario_elevador_plataforma')
                                            ->label('Em caso de necessidade o elevador ou plataforma é existente ?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->columnSpan(2)
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->validationMessages(
                                                [
                                                    'required' => 'Campo Obrigatório',
                                                ],
                                            ),

                                        Textarea::make('descricao_necessario_elevador_plataforma')
                                            ->label('Descrição sobre acessibilidade vertical')
                                            ->columnSpan(2)
                                            ->rows(3),

                                        Section::make('Foto da acessibilidade vertical')
                                            ->schema([
                                                FileUpload::make('foto_necessario_elevador_plataforma')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('elevador_platfm'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),

                            Section::make('Acabamento')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('piso_acabamento_polido')
                                            ->label('Piso com acabamento polido')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->helperText('Informar intervenção necessária e inserir fotos.')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('piso_area_intervencao')
                                            ->label('Qual a metragem quadrada que necessita intervenção?')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('piso_acabamento_polido') === 0)
                                            ->required(fn ($get) => $get('piso_acabamento_polido') === 0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_piso_acabamento_polido')
                                            ->label('Descrição do piso polido')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('piso_acabamento_polido') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto do Piso com acabamento polido')
                                            ->schema([
                                                FileUpload::make('foto_piso_acabamento_polido')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('piso_acab_polido'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('necessario_pelicula_fachada')
                                            ->label('Película na fachada existente ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->reactive()
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('pelicula_fachada_area')
                                            ->label('Qual a metragem quadrada que necessita de película?')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('necessario_pelicula_fachada') === 0)
                                            ->required(fn ($get) => $get('necessario_pelicula_fachada') === 0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_necessario_pelicula_fachada')
                                            ->label('Descrição da película na fachada')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('necessario_pelicula_fachada') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto Necessário película na fachada')
                                            ->schema([
                                                FileUpload::make('foto_necessario_pelicula_fachada')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('pelicula_fachada'))
                                                    ->maxSize(204800),
                                                // ->required()
                                                // ->validationMessages([
                                                // 'required' => 'Campo Obrigatório'
                                                // ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),

                            Section::make('Elementos Arquitetônicos')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('prever_marquise')
                                            ->label('Marquise existente ?')
                                            ->options([
                                                true => 'Sim',
                                                false => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        Textarea::make('descricao_prever_marquise')
                                            ->label('Descrição da marquise')
                                            ->rows(3)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        Section::make('Foto Marquise')
                                            ->schema([
                                                FileUpload::make('foto_prever_marquise')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('marquise'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('prever_porta_enrolar')
                                            ->label('Porta de enrolar existente ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            ->helperText('Verificar a necessidade, informar o motivo, área aproximada necessária e colocar fotos no local (por dentro e por fora) e do entorno.')
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('porta_enrolar_area_necessaria')
                                            ->label('Área aproximada necessária para porta de enrolar')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('prever_porta_enrolar') === 0)
                                            ->required(fn ($get) => $get('prever_porta_enrolar') === 0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_prever_porta_enrolar')
                                            ->label('Descrição da porta de enrolar')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('prever_porta_enrolar') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto porta de enrolar existente')
                                            ->schema([
                                                FileUpload::make('foto_prever_porta_enrolar')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('porta_enrolar'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),

                            Section::make('Vedação e Impermeabilização')
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 4,
                                    ])->schema([

                                        ToggleButtons::make('caixilhos_vidros_existentes')
                                            ->label('Caixilhos e vidros existentes')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                            ])
                                            ->inline()
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('caixilhos_vidros_area')
                                            ->label('Qual a área necessária de caixilhos/vidros?')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('caixilhos_vidros_existentes') === 0)
                                            ->required(fn ($get) => $get('caixilhos_vidros_existentes') === 0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_caixilhos_vidros_existentes')
                                            ->label('Descrição dos caixilhos')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('caixilhos_vidros_existentes') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto dos Caixilhos e vidros existentes')
                                            ->schema([
                                                FileUpload::make('foto_caixilhos_vidros_existentes')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('caixilhos_vid_exist'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                        ToggleButtons::make('prever_impermeabilizacao')
                                            ->label('Impermeabilização externa executada ?')
                                            ->options([
                                                1 => 'Sim',
                                                0 => 'Não',
                                                2 => 'Não se aplica',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                0 => 'danger',
                                                2 => 'gray',
                                            ])
                                            ->inline()
                                            // ->formatStateUsing(fn($state) => is_null($state) ? 'na' : $state)
                                            ->dehydrateStateUsing(fn ($state) => $state === 'na' ? null : $state)
                                            ->required()
                                            ->reactive()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo Obrigatório',
                                            ]),

                                        TextInput::make('impermeabilizacao_area_necessaria')
                                            ->label('Qual a área que necessita impermeabilização?')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->visible(fn ($get) => $get('prever_impermeabilizacao') === 0)
                                            ->required(fn ($get) => $get('prever_impermeabilizacao') === 0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                            ->validationMessages([
                                                'required' => 'Campo obrigatório.',
                                                'numeric' => 'Informe apenas números.',
                                                'min' => 'O valor não pode ser negativo.',
                                            ]),

                                        Textarea::make('descricao_prever_impermeabilizacao')
                                            ->label('Descrição da impermeabilização')
                                            ->rows(3)
                                            ->reactive()
                                            ->columnSpan(function ($get) {

                                                if ($get('prever_impermeabilizacao') === 0) {
                                                    return [
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ];
                                                }

                                                return [
                                                    'default' => 1,
                                                    'md' => 2,
                                                ];
                                            }),

                                        Section::make('Foto da impermeabilização externa')
                                            ->schema([
                                                FileUpload::make('foto_prever_impermeabilizacao')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->fetchFileInformation(false)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->panelLayout('grid')

                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('impermeabilizacao_ext'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),

                                    ]),
                                ]),
                        ]),
                    Step::make('Área 7 – Comentários Adicionais')
                        ->schema([
                            Section::make('Observações Gerais')
                                ->schema([
                                    Grid::make(4)->schema([
                                        FileUpload::make('foto_capa')
                                            ->label('Foto de capa')
                                            ->image()
                                            // ->downloadable()
                                            // ->openable()
                                            // ->previewable(true)
                                            /*
                                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                                $nome = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                                                return $nome . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                                            })*/
                                            ->acceptedFileTypes([
                                                'image/jpeg',
                                                'image/png',

                                            ])->disk((string) config('filesystems.media_disk', 'r2'))
                                            ->visibility('public')
                                            ->previewable(true)
                                            ->fetchFileInformation(false)
                                            ->openable()
                                            ->downloadable()
                                            ->saveUploadedFileUsing(static::saveRelatorioUpload('capa'))
                                            ->imagePreviewHeight('200')
                                            ->extraAttributes([
                                                'wire:loading.class' => 'opacity-50',
                                            ])
                                            ->columnSpanFull(),

                                        RichEditor::make('pontos_atencao')
                                            ->label('Pontos de atenção')
                                            ->helperText('Será adicionado no corpo do e-mail.')
                                            ->columnSpanFull(),

                                        Textarea::make('observacoes_gerais')
                                            ->label('Observações')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Section::make('Fotos gerais')
                                            ->schema([
                                                FileUpload::make('fotos_gerais')
                                                    ->label('Anexar imagem e/ou vídeo')
                                                    ->multiple()
                                                    ->columnSpanFull()->disk((string) config('filesystems.media_disk', 'r2'))
                                                    ->visibility('public')
                                                    ->previewable(true)
                                                    ->openable()
                                                    ->downloadable()

                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('200')
                                                    ->extraAttributes([
                                                        'wire:loading.class' => 'opacity-50',
                                                    ])
                                                    ->acceptedFileTypes([
                                                        'image/jpeg',
                                                        'image/png',
                                                        'video/*',
                                                    ])
                                                    ->saveUploadedFileUsing(static::saveRelatorioUpload('gerais'))
                                                    ->maxSize(204800),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(fn () => true),
                                    ]),
                                ]),
                        ]),
                ])
                    ->skippable()
                    ->persistStepInQueryString()
                    ->columnSpanFull(),
                // ->submitActionLabel('Salvar Relatório'),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table

            ->modifyQueryUsing(function (Builder $query): Builder {
                $lixeira = session('relatorio_visita_tecnica_view', 'without');

                return match ($lixeira) {
                    'only' => $query->onlyTrashed(),
                    default => $query->withoutTrashed(),
                };
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('numero_relatorio_vt')
                    ->label('Relatório')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('foto_capa')
                    ->label('Foto de capa')
                    ->alignCenter()->disk((string) config('filesystems.media_disk', 'r2'))
                    ->visibility('public')
                    ->url(function ($record) {
                        $path = $record->foto_capa;

                        if (blank($path)) {
                            return null;
                        }

                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                        return $disk->url($path);
                    })
                    ->circular()
                    ->stacked()
                    ->size(60)
                    ->limit(1),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Rascunho' => 'Rascunho',
                        'Concluído' => 'Concluído',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'Concluído',
                        'warning' => 'Rascunho',
                    ])
                    ->sortable(),

                TextColumn::make('projeto.nome')
                    ->label('Projeto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('autor')
                    ->label('Autor')
                    ->searchable(),

                TextColumn::make('iniciado_em')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('concluido_em')
                    ->label('Conclusão')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])

            ->headerActions([
                Action::make('lixeira')
                    ->label(fn () => 'Lixeira ('.RelatorioVisitaTecnica::onlyTrashed()->count().')')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function () {
                        session(['relatorio_visita_tecnica_view' => 'only']);

                        redirect(static::getUrl('index', [
                            'lixeira' => 'only',
                        ]));
                    }),

                Action::make('ativos')
                    ->label('Ver ativos')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function () {
                        session(['relatorio_visita_tecnica_view' => 'without']);

                        redirect(static::getUrl('index', [
                            'lixeira' => 'without',
                        ]));
                    }),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Rascunho' => 'Rascunho',
                        'Concluído' => 'Concluído',
                    ]),

                Tables\Filters\SelectFilter::make('projeto_id')
                    ->label('Projeto')
                    ->relationship('projeto', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('autor')
                    ->label('Autor')
                    ->options(
                        RelatorioVisitaTecnica::query()
                            ->whereNotNull('autor')
                            ->distinct()
                            ->orderBy('autor')
                            ->pluck('autor', 'autor')
                            ->toArray()
                    )
                    ->searchable(),

            ])->filtersLayout(FiltersLayout::AboveContent)->deferFilters(false)
            // ->recordUrl(fn($record) => Filament::getResource(RelatorioVisitaTecnicaResource::class)->getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make()->label('')->tooltip('Visualizar'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->visible(fn ($record) => ! $record->trashed()),

                RestoreAction::make()
                    ->label('')
                    ->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),

                ForceDeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir definitivamente')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Excluir permanentemente')
                    ->modalDescription('Essa ação não pode ser desfeita.'),

                Action::make('enviar_email')

                    ->label(' ')
                    ->tooltip('Enviar por email')
                    ->icon('heroicon-o-envelope')

                    ->visible(fn ($record) => $record->status === 'Concluído' && ! $record->trashed())
                    ->color('primary')
                    ->form([

                        Select::make('lista_email_id')
                            ->label('Lista de e-mails')
                            ->options(
                                ListaEmail::query()
                                    ->where('ativo', true)
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $lista = ListaEmail::find($state);

                                $emails = $lista?->emails ?? [];

                                $usuarioEmail = Filament::auth()->user()?->email;

                                if ($usuarioEmail) {
                                    $set('cc', [$usuarioEmail]);
                                }

                                $set('para', collect($emails)->filter()->unique()->values()->all());
                            }),
                        Select::make('para')
                            ->label('Para')
                            ->placeholder('Digite para buscar usuários ou listas')
                            ->options(fn (): array => static::emailOptionsEnvioRelatorioVisitaTecnica())
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
                            ->placeholder('Digite para buscar usuários ou listas')
                            ->options(fn (): array => static::emailOptionsEnvioRelatorioVisitaTecnica())
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
                            ->placeholder('Digite para buscar usuários ou listas')
                            ->options(fn (): array => static::emailOptionsEnvioRelatorioVisitaTecnica())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->rules(['nullable', 'array'])
                            ->nestedRecursiveRules(['email'])
                            ->validationMessages([
                                'email' => 'Um ou mais e-mails são inválidos.',
                            ]),

                        Hidden::make('assunto')
                            ->required(),

                        Hidden::make('mensagem')
                            ->label('Mensagem')
                            ->default('Segue a visita técnica em anexo.')
                            ->required(),
                    ])
                    ->fillForm(function ($record) {
                        $contador = 1;
                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
                        $fotoCapaUrl = null;

                        if (filled($record->foto_capa) && $disk->exists($record->foto_capa)) {
                            $fotoCapaUrl = $disk->url($record->foto_capa);
                        }

                        return [
                            'lista_email_id' => null,
                            'para' => array_values(array_filter([
                                Filament::auth()->user()?->email,
                            ])),
                            'cc' => [],
                            'cco' => [],

                            // 'assunto' => 'RELATÓRIO DE VT - '.$record->projeto?->marca.' - '.$record->unidade,
                            'assunto' => 'RELATÓRIO DE VT - '.($record->marca?->nome ?? 'SEM MARCA').' - '.$record->unidade,

                            'mensagem' => '<h4>👤 Autor: '.($record->autor ?? 'Não informado').'</h4>'.
                                '<h4>📅 Data de criação: '.($record->created_at?->format('d/m/Y H:i') ?? '-').'</h4>'.
                                '<h4>🧾 Nº Relatório: '.$record->numero_relatorio_vt.'</h4>'.

                                '<h4>Olá,<h4>'.
                                '<h4>Segue relatório de visita técnica ao ponto denominado '.($record->projeto?->nome ?? '[NÃO PREENCHIDO]').', localizado em '.($record->endereco ?? '[NÃO PREENCHIDO]').', realizado em '.($record->created_at?->format('d/m/Y') ?? '-').'</h4>'.
                                (
                                    $fotoCapaUrl ?
                                    "<img src='".$fotoCapaUrl.
                                    "' style='max-width:500px; border-radius:8px;'>" : '[FOTO DE CAPA]'
                                ).'<br>'.

                                '<h4><strong>• PRAZO DE OBRAS:</strong> '.($record->prazo_de_obras ?? '[NÃO PREENCHIDO]').'</h4>'.

                                (
                                    filled($record->descricao_prazo_obras)
                                    ? '<h4>Descrição do Prazo de obras: '.$record->descricao_prazo_obras.'</h4>'
                                    : ''
                                ).

                                '<h4><strong>• STATUS DO CONTRATO:</strong> '.($record->etapa_contrato ?? '[NÃO PREENCHIDO]').'</h4>'.

                                '<strong>_______________________________________________________________________________________________</strong> '.'<br><br>'.

                                '<h4><strong>• CONDIÇÕES DO IMÓVEL:</strong> '.
                                match ($record->condicoes_imovel) {
                                    'Imóvel pronto e desocupado' => 'Imóvel pronto e desocupado',

                                    'Imóvel pronto com ocupação (informar prazo de desocupação)' => 'Imóvel pronto com ocupação (informar prazo de desocupação)',

                                    'Imóvel pronto com adequações contratuais (Informar quais adequações e respectivos prazo)' => 'Imóvel pronto com adequações contratuais (Informar quais adequações e respectivos prazo)',

                                    'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)' => 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)',

                                    'Terreno (Sem demolição)' => 'Terreno (Sem demolição)',

                                    'Terreno (Com demolição)' => 'Terreno (Com demolição)',

                                    default => '[NÃO PREENCHIDO]',
                                }.'</h4>'.

                                // 👇 PRAZO DE DESOCUPAÇÃO
                                (
                                    $record->condicoes_imovel === 'Imóvel pronto com ocupação (informar prazo de desocupação)'
                                    ? '<h4>Prazo de desocupação: '.($record->prazo_desocupacao?->format('d/m/Y') ?? '[NÃO PREENCHIDO]').'</h4>'
                                    : ''
                                )

                                // 👇 PRAZO BTS
                                .
                                (
                                    $record->condicoes_imovel === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)'
                                    ? '<h4>Prazo previsto (Shell / Documentação / AVCB / Habite-se): '.
                                    ($record->prazo_bts?->format('d/m/Y') ?? '[NÃO PREENCHIDO]').'</h4>'
                                    : ''
                                ).

                                (
                                    filled($record->comentario_condicoes_imovel)
                                    ? '<h4>Comentários sobre as condições em que o imóvel está ou que será entregue: '.$record->comentario_condicoes_imovel.'</h4>'
                                    : ''
                                ).

                                '<strong>_______________________________________________________________________________________________</strong> '.'<br><br>'.

                                '<h4><strong>• PONTOS DE ATENÇÃO:</strong></h4><h4>'.($record->pontos_atencao ?? '[NÃO PREENCHIDO]').'</h4>'.

                                '<strong>_______________________________________________________________________________________________</strong> '.'<br><br>'.

                                '<h4><strong>• ENGENHARIA / ARQUITETURA</strong></h4>'.

                                '<strong>ENTRADA DE ENERGIA</strong><br>'.

                                '<strong>'.($contador++).'. Tensão disponível:</strong> '.
                                match ($record->entrada_de_energia) {
                                    '380_220' => '380/220V',
                                    '220_127' => '220/127V',
                                    'nao_informado' => 'Não informado',
                                    default => '[NÃO PREENCHIDO]',
                                }.'<br>'.

                                (
                                    filled($record->descricao_energia)
                                    ? '- Descrição energia: '.($record->descricao_energia ?? '[NÃO PREENCHIDO]').'<br>'
                                    : ''
                                ).

                                (
                                    $record->energia_carga_superior_150 == 0
                                    ? '' : '<strong>'.($contador++).'. Temos disponível carga superior a 150kVA?</strong> '.

                                    (
                                        $record->energia_carga_superior_150 === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->energia_carga_superior_150 ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->energia_carga_superior_150 == 0
                                        ? '- Descrição da energia com carga superior a 150kVA: '.
                                        ($record->descricao_energia_carga_superior_150 ?? '[NÃO PREENCHIDO]').'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->cabos_alimentadores_shell == 1
                                    ? '' : '<strong>'.($contador++).'. Cabos alimentadores entregues dentro do shell?</strong> '.

                                    (
                                        $record->cabos_alimentadores_shell === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->cabos_alimentadores_shell ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->cabos_alimentadores_shell == 0
                                        ? '- Quantidade de metros para cabeamento: '.
                                        ($record->metros_cabeamento ?? '[NÃO PREENCHIDO]').' m'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->telegonia_dg == 0
                                    ? '<br><strong>TELEFONIA</strong><br>'.

                                    '<strong>'.($contador++).'. Telefonia (DG) dentro do Shell?</strong> '.
                                    (
                                        $record->telegonia_dg === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->telegonia_dg ? 'Sim' : 'Não')
                                    ).'<br>'.

                                    '- Distância até o ponto mais próximo: '.
                                    ($record->distancia_ponto_telefonia ?? '[NÃO PREENCHIDO]').' m<br>'

                                    : ''
                                ).

                                (
                                    ($record->cobertura_vao_1_5 == 0 || $record->cobertura_isolamento == 0)
                                    ? '<br><strong>COBERTURAS</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->cobertura_vao_1_5 == 1
                                    ? '' : '<strong>'.($contador++).'. Cobertura com vãos inferiores a 1,5m nos dois sentidos?</strong> '.

                                    (
                                        $record->cobertura_vao_1_5 === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->cobertura_vao_1_5 ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->cobertura_vao_1_5 == 0
                                        ? '- Informar a metragem quadrada do reforço estrutural: '.
                                        ($record->cobertura_vao_1_5_metragem ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->cobertura_isolamento == 1
                                    ? '' : '<strong>'.($contador++).'. Cobertura com isolamento térmico?</strong> '.

                                    (
                                        $record->cobertura_isolamento === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->cobertura_isolamento ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->cobertura_isolamento == 0
                                        ? '- Qual a área que necessita de isolamento térmico? '.
                                        ($record->cobertura_area_isolamento ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    ($record->sobrecarga_minima_laje == 1 || $record->sobrecarga_minima_laje_teto == 1)
                                    ? '<br><strong>LAJES E SOBRECARGAS</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->sobrecarga_minima_laje == 0
                                    ? '' : '<strong>'.($contador++).'. Sobrecarga mínima da laje (500kg/m²)?</strong> '.
                                    (
                                        $record->sobrecarga_minima_laje === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->sobrecarga_minima_laje ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->sobrecarga_minima_laje == 1
                                        ? '- Comprovação da sobrecarga da laje:  '.
                                        ($record->comprovacao_sobrecarga_laje ?? '[NÃO PREENCHIDO]').'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->sobrecarga_minima_laje_teto == 0
                                    ? '' : '<strong>'.($contador++).'. Sobrecarga mínima de laje de teto (35kg/m²)?</strong> '.
                                    (
                                        $record->sobrecarga_minima_laje_teto === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->sobrecarga_minima_laje_teto ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->sobrecarga_minima_laje_teto == 1
                                        ? '- Comprovação da sobrecarga da laje de teto:  '.
                                        ($record->comprovacao_sobrecarga_laje_teto ?? '[NÃO PREENCHIDO]').'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    ($record->alvenaria_periferia_existente == 0 || $record->reboco_interno_externo_existente == 0 || $record->estanqueidade == 1)
                                    ? '<br><strong>VEDAÇÃO</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->alvenaria_periferia_existente == 1
                                    ? '' : '<strong>'.($contador++).'. Alvenaria de periferia existente?</strong> '.

                                    (
                                        $record->alvenaria_periferia_existente === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->alvenaria_periferia_existente ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->alvenaria_periferia_existente == 0
                                        ? '- Quantidade de metros quadrados de alvenaria a executar:  '.
                                        ($record->metros_alvenaria_periferia ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->reboco_interno_externo_existente == 1
                                    ? '' : '<strong>'.($contador++).'. Reboco interno e externo existente?</strong> '.
                                    (
                                        $record->reboco_interno_externo_existente === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->reboco_interno_externo_existente ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->reboco_interno_externo_existente == 0
                                        ? '- Quantidade de metros quadrados de reboco a executar: '.
                                        ($record->metros_reboco ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->estanqueidade == 0
                                    ? '' : '<strong>'.($contador++).'. Necessita de estanqueidade?</strong> '.
                                    (
                                        $record->estanqueidade === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->estanqueidade ? 'Sim' : 'Não')
                                    ).'<br>'.

                                    (
                                        $record->estanqueidade == 1
                                        ? '- Descrição da estanqueidade: '.
                                        ($record->descricao_estanqueidade ?? '[NÃO PREENCHIDO]').'<br>'.

                                        (
                                            $record->descricao_estanqueidade === 'outro'
                                            ? '- Informe a descrição da estanqueidade: '.($record->estanqueidade_outro ?? '[NÃO PREENCHIDO]').'<br>'
                                            : ''
                                        )
                                        : ''
                                    )
                                ).

                                (
                                    ($record->reservatorio_agua_existente == 1 || $record->medidor_agua_instalado_ligado == 1 || $record->reservatorio_incendio_existente == 1 || $record->ponto_esgoto_existente_shell == 0)
                                    ? '<br><strong>HIDRÁULICA E ESGOTO</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->reservatorio_agua_existente == 0
                                    ? '' : '<strong>'.($contador++).'. Reservatório de água existente:</strong> '.
                                    (
                                        $record->reservatorio_agua_existente === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->reservatorio_agua_existente ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->reservatorio_agua_existente == 1
                                        ? '- Qual o volume do reservatório? '.
                                        ($record->reservatorio_agua_litragem ?? '[NÃO PREENCHIDO]').' Litros'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->medidor_agua_instalado_ligado == 0
                                    ? '' : '<strong>'.($contador++).'. Medidor de água instalado e ligado:</strong> '.

                                    (
                                        $record->medidor_agua_instalado_ligado === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->medidor_agua_instalado_ligado ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->medidor_agua_instalado_ligado == 1
                                        ? '- Número da instalação: '.
                                        ($record->numero_instalacao_agua ?? '[NÃO PREENCHIDO]').'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->reservatorio_incendio_existente == 0
                                    ? '' : '<strong>'.($contador++).'. Reservatório de incêndio existente:</strong> '.
                                    (
                                        $record->reservatorio_incendio_existente === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->reservatorio_incendio_existente ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->reservatorio_incendio_existente == 1
                                        ? '- Qual o volume do reservatório de incêndio? '.
                                        ($record->reservatorio_incendio_litragem ?? '[NÃO PREENCHIDO]').' Litros'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->ponto_esgoto_existente_shell == 1
                                    ? '' : '<strong>'.($contador++).'. Ponto de esgoto existente dentro do shell:</strong> '.
                                    (
                                        $record->ponto_esgoto_existente_shell === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->ponto_esgoto_existente_shell ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->ponto_esgoto_existente_shell == 0
                                        ? '- Distância até o ponto mais próximo? '.
                                        ($record->ponto_esgoto_mais_proximo ?? '[NÃO PREENCHIDO]').' m'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    ($record->rede_gas_disponivel === 'GN (solicitar ligação)')
                                    ? '<br><strong>GÁS</strong><br>'.'<strong>'.($contador++).'. Rede de gás disponível:</strong> '.($record->rede_gas_disponivel ?? '[NÃO PREENCHIDO]').'<br>'
                                    : ''
                                ).

                                (
                                    $record->rede_gas_disponivel === 'GN (solicitar ligação)'
                                    ? '- Distância até o ponto mais próximo: '.
                                    ($record->distancia_rede_gas ?? '[NÃO PREENCHIDO]').' m'.'<br>'
                                    : ''
                                ).

                                (
                                    ($record->piso_acabamento_polido == 0 || $record->necessario_pelicula_fachada == 0)
                                    ? '<br><strong>ACABAMENTO</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->piso_acabamento_polido == 1
                                    ? '' : '<strong>'.($contador++).'. Piso com acabamento polido:</strong> '.
                                    (
                                        $record->piso_acabamento_polido === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->piso_acabamento_polido ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->piso_acabamento_polido == 0
                                        ? '- Qual a metragem quadrada que necessita intervenção? '.
                                        ($record->piso_area_intervencao ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    $record->necessario_pelicula_fachada !== 1
                                    ? '<strong>'.($contador++).'. Película na fachada existente?</strong> '.
                                    match ($record->necessario_pelicula_fachada) {
                                        1 => 'Sim',
                                        0 => 'Não',
                                        2 => 'Não se aplica',
                                        default => '[NÃO PREENCHIDO]',
                                    }.'<br>'.
                                    (
                                        $record->necessario_pelicula_fachada == 0
                                        ? '- Qual a metragem quadrada que necessita de película? '.
                                        ($record->pelicula_fachada_area ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                    : ''
                                ).

                                (
                                    ($record->prever_porta_enrolar == 0 || $record->caixilhos_vidros_existentes == 0)
                                    ? '<br><strong>ELEMENTOS ARQUITETÔNICOS</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->prever_porta_enrolar !== 1
                                    ? '<strong>'.($contador++).'. Porta de enrolar existente?</strong> '.
                                    match ($record->prever_porta_enrolar) {
                                        1 => 'Sim',
                                        0 => 'Não',
                                        2 => 'Não se aplica',
                                        default => '[NÃO PREENCHIDO]',
                                    }.'<br>'.
                                    (
                                        $record->prever_porta_enrolar == 0
                                        ? '- Área aproximada necessária para porta de enrolar: '.
                                        ($record->porta_enrolar_area_necessaria ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                    : ''
                                ).

                                (
                                    $record->caixilhos_vidros_existentes == 1
                                    ? '' : '<strong>'.($contador++).'. Caixilhos e vidros existentes:</strong> '.
                                    (
                                        $record->caixilhos_vidros_existentes === null
                                        ? '[NÃO PREENCHIDO]'
                                        : ($record->caixilhos_vidros_existentes ? 'Sim' : 'Não')
                                    ).'<br>'.
                                    (
                                        $record->caixilhos_vidros_existentes == 0
                                        ? '- Qual a área necessária de caixilhos/vidros? '.
                                        ($record->caixilhos_vidros_area ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                ).

                                (
                                    ($record->prever_impermeabilizacao == 0)
                                    ? '<br><strong>IMPERMEABILIZAÇÃO</strong><br>'
                                    : ''
                                ).

                                (
                                    $record->prever_impermeabilizacao !== 1
                                    ? '<strong>'.($contador++).'. Impermeabilização externa executada?</strong> '.
                                    match ($record->prever_impermeabilizacao) {
                                        1 => 'Sim',
                                        0 => 'Não',
                                        2 => 'Não se aplica',
                                        default => '[NÃO PREENCHIDO]',
                                    }.'<br>'.
                                    (
                                        $record->prever_impermeabilizacao == 0
                                        ? '- Qual a área que necessita impermeabilização? '.
                                        ($record->impermeabilizacao_area_necessaria ?? '[NÃO PREENCHIDO]').' m²'.'<br>'
                                        : ''
                                    )
                                    : ''
                                ).

                                '<h4>Atenciosamente, '.($record->autor ?? 'Não informado').
                                '</h4>',
                        ];
                    })
                    ->action(function ($record, array $data, VisitaTecnicaPdfService $pdfService) {
                        $usuario = Filament::auth()->user();
                        $record->refresh();
                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                        if ($pdfService->isGenerating($record)) {
                            Notification::make()
                                ->title('PDF em geração')
                                ->body('O PDF ainda está sendo gerado. Aguarde a conclusão para enviar o e-mail.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if (! $pdfService->hasValidStoredPdf($record)) {
                            Notification::make()
                                ->title('PDF indisponível')
                                ->body('Não foi possível localizar um PDF válido para este relatório.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $nomeArquivo = 'Relatorio-Visita-Tecnica-'.$record->numero_relatorio_vt.'.pdf';

                        $pdfBinary = $disk->get($record->pdf_path);

                        // $path = 'relatorios-vt/' . $nomeArquivo;
                        $path = static::pdfDirectory($record->numero_relatorio_vt).'/'.$nomeArquivo;

                        $disk->put($path, $pdfBinary, [
                            'ContentType' => 'application/pdf',
                            'visibility' => 'public',
                        ]);

                        $link = $disk->url($path);

                        $mensagem = $data['mensagem']
                            .'<h4>O arquivo está disponível no link.<br>'
                            .'<a href="'.$link.'" target="_blank">Baixar PDF</a></h4>'
                            .'<h4>Este email foi enviado por,<br>'
                            .($usuario?->name ?? 'Não informado').'<br>'
                            .($usuario?->email ?? '')
                            .'</h4>';

                        Mail::to($data['para'] ?? [])
                            ->cc($data['cc'] ?? [])
                            ->bcc($data['cco'] ?? [])
                            ->send(
                                new EnviarPdfMail(
                                    assunto: $data['assunto'],
                                    mensagemEmail: $mensagem,
                                    pdfBinary: '',
                                    nomeArquivo: '',
                                )
                            );

                        Notification::make()
                            ->title('E-mail enviado com link do PDF.')
                            ->success()
                            ->send();
                    }),

            ])->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /*
    public static function mutateFormDataBeforeCreate(array $data): array
    {
          Log::info('Mutate Form Data Before Create chamado', $data);

        $data['concluido_em'] = now();
        dd($data); // veja se o campo aparece aqui
        return $data;
    }
    */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRelatorioVisitaTecnicas::route('/'),
            'create' => Pages\CreateRelatorioVisitaTecnica::route('/create'),
            'edit' => Pages\EditRelatorioVisitaTecnica::route('/{record}/edit'),
            'view' => Pages\ViewRelatorioVisitaTecnica::route('/{record}'),

        ];
    }

    protected static function uploadAutosaveInputAttributes(string $field): array
    {
        return [
            'x-on:livewire-upload-start' => '$dispatch(\'draft-upload-start\', { field: \''.$field.'\' })',
            'x-on:livewire-upload-finish' => '$dispatch(\'draft-upload-finish\', { field: \''.$field.'\' })',
            'x-on:livewire-upload-error' => '$dispatch(\'draft-upload-finish\', { field: \''.$field.'\' })',
            'x-on:livewire-upload-cancel' => '$dispatch(\'draft-upload-finish\', { field: \''.$field.'\' })',
        ];
    }
}
