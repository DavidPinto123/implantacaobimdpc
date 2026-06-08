<?php

namespace App\Filament\Resources\Obras\Schemas;

use App\Enums\TipoUnidade;
use App\Models\Projeto;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ObrasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make('Dados da Obra PIPE')
                ->schema([

                    Section::make('Selecionar Projeto')
                        ->description('Escolha o projeto para carregar automaticamente as informações.')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Select::make('projeto_id')
                                ->relationship('projeto', 'nome')
                                ->label('Projeto')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->columnSpanFull()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $projeto = Projeto::with(['responsavelEng', 'responsavelCom', 'cidade', 'estado'])->find($state);

                                        if ($projeto) {
                                            $set('codigo', $projeto->codigo);
                                            $set('unidade', $projeto->nome);
                                            $set('pipe_land', $projeto->pipeline);
                                            $set('status', $projeto->status);

                                            $set('status_visita', $projeto->vis_status);
                                            $set('status_proj_exec', $projeto->proj_status);

                                            $set('engenharia', $projeto->responsavelEng?->name);
                                            $set('comercial', $projeto->responsavelCom?->name);
                                            $set('arquitetura', $projeto->responsavelArq?->name);

                                            $set('status_data_posse', $projeto->data_posse ? Carbon::parse($projeto->data_posse)->format('Y-m-d') : null);
                                            $set('inicio', $projeto->inicio_obra ? Carbon::parse($projeto->inicio_obra)->format('Y-m-d') : null);
                                            $set('fim', $projeto->entrega_obra ? Carbon::parse($projeto->entrega_obra)->format('Y-m-d') : null);

                                            $set('prazo_planejado', $projeto->exec_prazo_plan);
                                            $set('prazo_realizado', $projeto->exec_prazo_real);

                                            $set('inicio_imp', $projeto->imp_inicio ? Carbon::parse($projeto->imp_inicio)->format('Y-m-d') : null);
                                            $set('fim_imp', $projeto->imp_fim ? Carbon::parse($projeto->imp_fim)->format('Y-m-d') : null);

                                            $set('observacao', $projeto->observacoes_ponto);
                                            $set('imp_prazo_planej', $projeto->imp_prazo_planejado);
                                            $set('imp_prazo_realiz', $projeto->imp_prazo_realizado);
                                            $set('mes', $projeto->imp_mes);
                                            $set('ano', $projeto->imp_ano);

                                            $set('endereco', $projeto->endereco);
                                            $set('cidade', $projeto->cidade?->nome);
                                            $set('uf', $projeto->estado?->sigla ?? $projeto->estado?->nome);

                                            $refEntrega = $projeto->inauguracao
                                                ? Carbon::parse($projeto->inauguracao)
                                                : ($projeto->imp_fim ? Carbon::parse($projeto->imp_fim)->addDay() : null);

                                            $set(
                                                'dias_para_inauguracao',
                                                $refEntrega
                                                    ? now()->diffInDays($refEntrega, false).' dias'
                                                    : null
                                            );

                                            if ($projeto->imp_inicio && $projeto->imp_fim) {
                                                $set(
                                                    'dias_obra_inicio_pmo',
                                                    Carbon::parse($projeto->imp_inicio)->diffInDays(Carbon::parse($projeto->imp_fim)).' dias'
                                                );
                                            }
                                        }
                                    }
                                }),
                        ]),

                    Section::make('Fotos da Obra')
                        ->description('Foto de perfil e imagem de capa exibidas na página de visualização.')
                        ->icon('heroicon-o-camera')
                        ->schema([
                            FileUpload::make('foto_perfil')
                                ->label('Foto de Perfil')
                                ->image()
                                ->disk((string) config('filesystems.media_disk', 'r2'))
                                ->fetchFileInformation(false)
                                ->directory('obras/perfil')
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('400')
                                ->imageResizeTargetHeight('400'),

                            FileUpload::make('foto_capa')
                                ->label('Foto de Capa')
                                ->image()
                                ->disk((string) config('filesystems.media_disk', 'r2'))
                                ->fetchFileInformation(false)
                                ->directory('obras/capa')
                                ->imageResizeMode('cover')
                                ->imageResizeTargetWidth('1200')
                                ->imageResizeTargetHeight('400'),
                        ])
                        ->columns(2)
                        ->collapsible(),

                    Section::make('Informações do Projeto')
                        ->schema([
                            TextInput::make('codigo')
                                ->label('Código')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('unidade')
                                ->label('Unidade')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('pipe_land')
                                ->label('PIPE / LAND')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('status')
                                ->label('Status do Projeto')
                                ->disabled()
                                ->dehydrated(),

                            Select::make('tipos_unidade')
                                ->label('Tipos da Unidade')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(TipoUnidade::options())
                                ->helperText('Use uma ou mais tags para classificar a unidade.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Section::make('Gestor')
                        ->schema([
                            TextInput::make('engenharia')
                                ->label('Engenharia')
                                ->placeholder('Responsável da Engenharia')
                                ->readOnly(),

                            TextInput::make('comercial')
                                ->label('Comercial')
                                ->placeholder('Responsável do Comercial')
                                ->readOnly(),

                            TextInput::make('arquitetura')
                                ->label('Arquitetura')
                                ->placeholder('Responsável da Arquitetura')
                                ->readOnly(),
                            // Pega as informações do projeto
                            DatePicker::make('entrada_ponto')
                                ->label('Entrada do Ponto'),
                            TextInput::make('status_contrato')
                                ->label('Status do Contrato')
                                ->disabled()
                                ->dehydrated(false),
                            // Pega as informações do projeto
                            DatePicker::make('data_assinatura_contrato')
                                ->label('Data Assinatura do Contrato'),

                        ])->columns(3),

                    Section::make('Processo')
                        ->schema([
                            // Pega a inauguração - entrada do ponto
                            TextInput::make('entrada_ponto_ate_inauguracao')
                                ->numeric()
                                ->suffix('dias')
                                ->label('Entrada do ponto até a Inauguração'),
                            // Pega a inauguração - assinatura do contrato
                            TextInput::make('assinatura_ate_inauguracao')
                                ->numeric()
                                ->suffix('dias')
                                ->label('Assinatura até a Inauguração'),

                        ])->columns(2),

                    Grid::make(2)
                        ->schema([
                            Section::make('Visita Técnica')
                                ->schema([
                                    TextInput::make('status_visita')
                                        ->label('Status')
                                        ->readOnly(),
                                ])
                                ->columnSpan(1),

                            Section::make('Projeto Executivo')
                                ->schema([
                                    TextInput::make('status_proj_exec')
                                        ->label('Status')
                                        ->readOnly(),
                                ])
                                ->columnSpan(1),
                        ]),

                    Section::make('Contratações')
                        ->schema([
                            TextInput::make('civil')
                                ->label('Civil')
                                ->placeholder('Responsável / Empresa contratada'),

                            TextInput::make('hidraulica')
                                ->label('Hidráulica')
                                ->placeholder('Responsável / Empresa contratada'),

                            TextInput::make('eletrica')
                                ->label('Elétrica')
                                ->placeholder('Responsável / Empresa contratada'),

                            TextInput::make('incendio')
                                ->label('Incêndio')
                                ->placeholder('Responsável / Empresa contratada'),

                            TextInput::make('instalacao_ar_condicionado')
                                ->label('Instalação Ar Condicionado')
                                ->placeholder('Responsável / Empresa contratada'),

                            TextInput::make('maquinas_ar_condicionado')
                                ->label('Máquinas Ar Condicionado')
                                ->placeholder('Responsável / Empresa contratada'),

                            Select::make('homologados_em_atraso')
                                ->label('Homologados em Atraso')
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ])
                                ->native(false),
                        ])
                        ->columns(3),

                    Section::make('Posse')
                        ->schema([
                            DatePicker::make('status_data_posse')
                                ->label('Data de Posse')
                                ->readOnly()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record?->status_data_posse ?? $record?->projeto?->data_posse) {
                                        $data = $record->status_data_posse ?? $record->projeto?->data_posse;
                                        $set('status_data_posse', Carbon::parse($data)->format('Y-m-d'));
                                    }
                                }),

                            Select::make('relatorio_fotografico')
                                ->label('Relatório Fotográfico')
                                ->options([
                                    'enviado' => 'Enviado',
                                    'pendencias' => 'Enviado com Pendências',
                                    'nao_enviado' => 'Não Enviado',
                                ])
                                ->native(false),

                            // Futuramente pegar automático do relatório
                            DatePicker::make('data_envio_relatorio_fotografico')
                                ->label('Data de Envio do Relatório Fotográfico'),

                            // Futuramente pegar automático do comentário
                            DatePicker::make('data_atualizacao_comentario')
                                ->label('Data de Atualização do Comentário'),

                            Select::make('termo_de_posse')
                                ->label('Termo de Posse')
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ])
                                ->native(false),

                            RichEditor::make('comentarios')
                                ->label('Comentários')
                                ->columnSpanFull(),
                        ])->columns(5),

                    Section::make('Execução de Obras')
                        ->schema([
                            DatePicker::make('inicio')
                                ->readOnly()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record?->inicio ?? $record?->projeto?->inicio_obra) {
                                        $data = $record->inicio ?? $record->projeto?->inicio_obra;
                                        $set('inicio', Carbon::parse($data)->format('Y-m-d'));
                                    }
                                }),

                            // Verificar quem preenche essas informações
                            DatePicker::make('inicio_real')
                                ->label('Início Real'),

                            DatePicker::make('fim')
                                ->readOnly()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record?->fim ?? $record?->projeto?->entrega_obra) {
                                        $data = $record->fim ?? $record->projeto?->entrega_obra;
                                        $set('fim', Carbon::parse($data)->format('Y-m-d'));
                                    }
                                }),

                            TextInput::make('prazo_planejado')
                                ->label('Prazo Planejado')
                                ->readOnly(),

                            TextInput::make('prazo_realizado')
                                ->label('Prazo Realizado')
                                ->readOnly(),

                            TextInput::make('link')
                                ->label('Link do VISI')
                                ->url()
                                ->placeholder('https://exemplo.com')
                                ->suffixAction(
                                    fn ($state) => filled($state)
                                        ? Action::make('open_link')
                                            ->icon('heroicon-o-link')
                                            ->tooltip('Abrir link')
                                            ->url($state, true) // true = abre em nova aba
                                        : null
                                )
                                ->columnSpanFull(),

                        ])->columns(5),

                    Section::make('Integração Constructin')
                        ->description('ID do projeto na plataforma Constructin.')
                        ->collapsible()
                        ->schema([
                            TextInput::make('constructin_project_id')
                                ->label('ID do Projeto (Constructin)')
                                ->numeric()
                                ->placeholder('Ex: 12345')
                                ->helperText('Preenchido automaticamente via nova_sigla quando possível. Edite manualmente se necessário.'),
                        ]),

                    Section::make('Implantação')
                        ->schema([
                            DatePicker::make('inicio_imp')
                                ->readOnly()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record?->inicio_imp ?? $record?->projeto?->imp_inicio) {
                                        $data = $record->inicio_imp ?? $record->projeto?->imp_inicio;
                                        $set('inicio_imp', Carbon::parse($data)->format('Y-m-d'));
                                    }
                                }),

                            Select::make('cronograma_implantacao')
                                ->label('Cronograma de Implantação')
                                ->options([
                                    'enviado' => 'Enviado',
                                    'nao_enviado' => 'Não Enviado',
                                ])
                                ->native(false),

                            TextInput::make('inauguracao')
                                ->label('Inauguração')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null),

                            DatePicker::make('fim_imp')
                                ->label('Término Implantação')
                                ->readOnly()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record) {
                                        $inicio = $record->inicio_imp ?? $record->projeto?->imp_inicio;
                                        $fim = $record->fim_imp ?? $record->projeto?->imp_fim;

                                        if ($fim) {
                                            $set('fim_imp', Carbon::parse($fim)->format('Y-m-d'));

                                            $inauguracao = $record->inauguracao
                                                ? Carbon::parse($record->inauguracao)
                                                : Carbon::parse($fim)->addDay();

                                            $set('dias_para_inauguracao', now()->diffInDays($inauguracao, false).' dias');
                                        }

                                        if ($inicio && $fim) {
                                            $set(
                                                'dias_obra_inicio_pmo',
                                                Carbon::parse($inicio)->diffInDays(Carbon::parse($fim)).' dias'
                                            );
                                        }
                                    }
                                }),

                            TextInput::make('imp_prazo_planej')
                                ->label('Prazo Planejado')
                                ->readOnly(),

                            TextInput::make('imp_prazo_realiz')
                                ->label('Prazo Realizado')
                                ->readOnly(),

                            TextInput::make('mes')
                                ->label('Mês')
                                ->readOnly(),

                            TextInput::make('ano')
                                ->label('Ano')
                                ->readOnly(),

                            RichEditor::make('observacao')
                                ->label('Observação')
                                ->columnSpanFull(),

                        ])->columns(4),

                    Section::make('Dados do Imóvel')
                        ->schema([
                            TextInput::make('tipo_imovel')
                                ->label('Tipo de Imóvel')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('endereco')
                                ->label('Endereço')
                                ->readOnly(),

                            TextInput::make('cidade')
                                ->label('Cidade')
                                ->readOnly(),

                            TextInput::make('uf')
                                ->label('Estado')
                                ->readOnly(),

                            TextInput::make('empreendimento')
                                ->label('Empreendimento')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('locacao')
                                ->label('Locação')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('contato_corretor')
                                ->label('Contato do Corretor / PP')
                                ->disabled()
                                ->dehydrated(false),
                        ])->columns(3),

                    // Section % de Obra
                    Section::make('% de Obra')
                        ->schema([
                            TextInput::make('dias_para_inauguracao')
                                ->label('Dias para Entrega')
                                ->readOnly()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($set, $record) {
                                    if (! $record) {
                                        return;
                                    }

                                    $inauguracao = $record->inauguracao
                                        ? Carbon::parse($record->inauguracao)
                                        : ($record->fim_imp ? Carbon::parse($record->fim_imp)->addDay() : null);

                                    if ($inauguracao) {
                                        $set('dias_para_inauguracao', now()->diffInDays($inauguracao, false).' dias');
                                    }
                                }),

                            TextInput::make('dias_obra_inicio_pmo')
                                ->label('Dias de Obra')
                                ->readOnly()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($set, $record) {
                                    if (! $record) {
                                        return;
                                    }

                                    $inicio = $record->inicio_imp ?? $record->projeto?->imp_inicio;
                                    $fim = $record->fim_imp ?? $record->projeto?->imp_fim;

                                    if ($inicio && $fim) {
                                        $set(
                                            'dias_obra_inicio_pmo',
                                            Carbon::parse($inicio)->diffInDays(Carbon::parse($fim)).' dias'
                                        );
                                    }
                                }),

                            TextInput::make('percentual_obra')
                                ->label('% de Obra Previsto')
                                ->numeric()
                                ->suffix('%'),

                            TextInput::make('percentual_obra_executado')
                                ->label('% de Obra Executado')
                                ->numeric()
                                ->suffix('%'),

                            TextInput::make('desvio')
                                ->label('Desvio')
                                ->numeric()
                                ->suffix('%'),
                        ])->columns(3),

                    // Acompanhamento de Obra
                    Section::make('Acompanhamento de Obra')
                        ->schema([

                            TextInput::make('itens_criticos')
                                ->label('Itens Críticos'),

                            RichEditor::make('descricao_itens_criticos')
                                ->label('Descrever Itens Críticos'),

                        ])->columns(2),

                    Section::make('Cronograma Visi')
                        ->schema([
                            Select::make('cronograma_visi')
                                ->label('Cronograma Visi')
                                ->options([
                                    'enviado' => 'Enviado',
                                    'nao_enviado' => 'Não Enviado',
                                ])
                                ->native(false),

                            Select::make('camera_unidade')
                                ->label('Câmera na Unidade')
                                ->options([
                                    'sim' => 'Sim',
                                    'nao' => 'Não',
                                ])
                                ->native(false),

                            RichEditor::make('ponto_atencao')
                                ->label('Ponto de Atenção')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Section::make('Contas de Consumo')
                        ->schema([
                            Select::make('energia')
                                ->label('Energia')
                                ->options([
                                    'Ligada / Rateio' => 'Ligada / Rateio',
                                    'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                                    'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
                                    'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
                                    'Pendente, responsavel PP' => 'Pendente, responsavel PP',
                                    'GERADOR' => 'GERADOR',
                                ])
                                ->native(false),

                            DatePicker::make('previsao_ligacao_energia')
                                ->label('Previsão de Ligação de Energia'),

                            TextInput::make('gerador_contratual')
                                ->label('Gerador Contratual'),

                            Select::make('agua')
                                ->label('Água')
                                ->options([
                                    'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                                    'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
                                    'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
                                    'Pendente, responsavel PP' => 'Pendente, responsavel PP',
                                    'Ligada / Rateio' => 'Ligada / Rateio',
                                ])
                                ->native(false),

                            Select::make('gas')
                                ->label('Gás')
                                ->options([
                                    'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                                    'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
                                    'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
                                    'Pendente, responsavel PP' => 'Pendente, responsavel PP',
                                    'Boiler Instalado provisório' => 'Boiler Instalado provisório',
                                ])
                                ->native(false),

                            RichEditor::make('comentario')
                                ->label('Comentário')
                                ->columnSpanFull(),
                        ])->columns(3),

                    // Section Pós-Obra
                    Section::make('Pós-Obra')
                        ->schema([
                            Select::make('email_solicitacao_cl')
                                ->label('E-mail Solicitação CL')
                                ->options([
                                    'enviado' => 'Enviado',
                                    'nao_enviado' => 'Não Enviado',
                                ])
                                ->native(false),

                            Select::make('envio_qrcod')
                                ->label('Envio QRCODE')
                                ->options([
                                    'enviado' => 'Enviado',
                                    'nao_enviado' => 'Não Enviado',
                                ])
                                ->native(false),

                            Select::make('checklist_manutencao')
                                ->label('Checklist Manutenção')
                                ->options([
                                    'concluido' => 'Concluído',
                                    'em_andamento' => 'Em andamento',
                                    'em_atraso' => 'Em atraso',
                                    'nao_iniciado' => 'Não iniciado',
                                ])
                                ->native(false),

                            DatePicker::make('data_check_list')
                                ->label('Data do Check List'),

                            DatePicker::make('inicio_prev_pendencias')
                                ->label('Início Prev. Pendências'),

                            DatePicker::make('termino_prev_pendencias')
                                ->label('Término Prev. Pendências'),

                            TextInput::make('elevador')
                                ->label('Elevador'),

                            // Verificar se possui em Projetos para puxar automático
                            TextInput::make('gestor_pos_obra')
                                ->label('Gestor Pós Obra'),

                            RichEditor::make('comentarios_adicionais')
                                ->label('Comentários Adicionais')
                                ->columnSpanFull(),
                        ])->columns(2),
                ])
                ->collapsible()
                ->columnSpan('full'),
        ]);
    }
}
