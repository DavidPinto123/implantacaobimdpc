<?php

namespace App\Filament\Resources\Obras\Support;

use App\Enums\TipoUnidade;
use App\Models\ColunaPersonalizada;
use App\Models\Obras;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Schema do form de edição "rica" de Obras (usado no slideOver do preset e
 * na action `editarObra` do modo Page). Espelha o form original em
 * ObrasTable::buildEditForm().
 */
final class ObrasEditFormSchema
{
    /**
     * @return array<int, Section>
     */
    public static function schema(): array
    {
        $hintProjeto = 'Preenchido via Projetos';
        $hintCalc = 'Calculado automaticamente';

        return [
            Section::make('Informações do Projeto')
                ->description(self::fieldStats(
                    ['codigo', 'sigla', 'nova_sigla', 'unidade', 'marca', 'pipe_land', 'status', 'tipos_unidade'],
                    'status',
                ))
                ->schema([
                    Forms\Components\TextInput::make('codigo')->label('Código')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('sigla')->label('Sigla')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('nova_sigla')->label('Nova Sigla')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('unidade')->label('Unidade')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('marca')->label('Marca')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('pipe_land')->label('PIPE / LAND')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('status')->label('Status')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\Select::make('tipos_unidade')
                        ->label('Tipos da Unidade')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(TipoUnidade::options())
                        ->helperText('Use uma ou mais tags para classificar a unidade.')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Gestor')
                ->description(self::fieldStats(
                    ['engenharia', 'comercial', 'arquitetura', 'entrada_ponto', 'status_contrato', 'data_assinatura_contrato'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('engenharia')->label('Engenharia')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('comercial')->label('Comercial')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('arquitetura')->label('Arquitetura')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('entrada_ponto')->label('Entrada do Ponto'),
                    Forms\Components\TextInput::make('status_contrato')
                        ->label('Status do Contrato')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                    Forms\Components\DatePicker::make('data_assinatura_contrato')->label('Data Assinatura Contrato'),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Total de Dias de Processo')
                ->description(self::fieldStats(
                    ['entrada_ponto_ate_inauguracao', 'assinatura_ate_inauguracao'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('entrada_ponto_ate_inauguracao')
                        ->label('Entrada do Ponto até Inauguração')
                        ->disabled()->dehydrated(false)
                        ->suffix('dias')
                        ->hint($hintCalc),
                    Forms\Components\TextInput::make('assinatura_ate_inauguracao')
                        ->label('Assinatura até Inauguração')
                        ->disabled()->dehydrated(false)
                        ->suffix('dias')
                        ->hint($hintCalc),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Visita Técnica / Projeto Executivo')
                ->description(function (Get $get): HtmlString {
                    $visita = $get('status_visita');
                    $proj = $get('status_proj_exec');
                    $parts = [];
                    if (filled($visita)) {
                        $parts[] = "Visita: {$visita}";
                    }
                    if (filled($proj)) {
                        $parts[] = "Proj: {$proj}";
                    }
                    $text = $parts ? implode(' | ', $parts) : 'Sem dados';
                    $color = $parts ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500';

                    return new HtmlString("<span class='{$color}' style='font-size:0.75rem'>{$text}</span>");
                })
                ->schema([
                    Forms\Components\TextInput::make('status_visita')->label('Status Visita')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('status_proj_exec')->label('Status Projeto Executivo')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Contratações')
                ->description(function (Get $get): HtmlString {
                    $fields = ['civil', 'hidraulica', 'eletrica', 'incendio', 'instalacao_ar_condicionado', 'maquinas_ar_condicionado'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $atraso = $get('homologados_em_atraso');

                    $color = $filled === $total ? 'text-green-500 dark:text-green-400' : 'text-amber-500 dark:text-amber-400';
                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} contratados</span>";
                    if ($atraso === 'sim') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Homologados em atraso</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\TextInput::make('civil')->label('Civil'),
                    Forms\Components\TextInput::make('hidraulica')->label('Hidráulica'),
                    Forms\Components\TextInput::make('eletrica')->label('Elétrica'),
                    Forms\Components\TextInput::make('incendio')->label('Incêndio'),
                    Forms\Components\TextInput::make('instalacao_ar_condicionado')->label('Instalação Ar Condicionado'),
                    Forms\Components\TextInput::make('maquinas_ar_condicionado')->label('Máquinas Ar Condicionado'),
                    Forms\Components\Select::make('homologados_em_atraso')
                        ->label('Homologados em Atraso')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            Section::make('Posse')
                ->description(function (Get $get): HtmlString {
                    $fields = ['status_data_posse', 'relatorio_fotografico', 'data_envio_relatorio_fotografico', 'data_atualizacao_comentario', 'termo_de_posse', 'comentarios'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";

                    $rel = $get('relatorio_fotografico');
                    if ($rel === 'pendencias') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Rel. com pendências</span>";
                    } elseif ($rel === 'nao_enviado') {
                        $html .= "<span class='text-amber-500 dark:text-amber-400 ml-2' style='font-size:0.7rem'>| Rel. não enviado</span>";
                    }
                    if ($get('termo_de_posse') === 'nao') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Sem termo de posse</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('status_data_posse')->label('Data de Posse')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\Select::make('relatorio_fotografico')
                        ->label('Relatório Fotográfico')
                        ->options([
                            'enviado' => 'Enviado',
                            'pendencias' => 'Enviado com Pendências',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('data_envio_relatorio_fotografico')->label('Data Envio Rel. Fotográfico'),
                    Forms\Components\DatePicker::make('data_atualizacao_comentario')->label('Data Atualização Comentário'),
                    Forms\Components\Select::make('termo_de_posse')
                        ->label('Termo de Posse')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                    Forms\Components\Textarea::make('comentarios')->label('Comentários')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Execução de Obras')
                ->description(function (Get $get): HtmlString {
                    $inicio = $get('inicio');
                    $fim = $get('fim');
                    $parts = [];
                    if (filled($inicio)) {
                        $parts[] = Carbon::parse($inicio)->format('d/m/Y');
                    }
                    if (filled($fim)) {
                        $parts[] = Carbon::parse($fim)->format('d/m/Y');
                    }
                    $periodo = $parts ? implode(' → ', $parts) : null;
                    $prazo = $get('prazo_planejado');

                    $html = '';
                    if ($periodo) {
                        $html .= "<span class='text-amber-500 dark:text-amber-400' style='font-size:0.75rem'>{$periodo}</span>";
                    }
                    if (filled($prazo)) {
                        $html .= "<span class='text-gray-400 dark:text-gray-500 ml-2' style='font-size:0.7rem'>| Prazo: {$prazo} dias</span>";
                    }
                    if (! $html) {
                        $html = "<span class='text-gray-400 dark:text-gray-500' style='font-size:0.75rem'>Datas não definidas</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('inicio')->label('Início')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('inicio_real')->label('Início Real'),
                    Forms\Components\DatePicker::make('fim')->label('Fim')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('prazo_planejado')->label('Prazo Planejado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('prazo_realizado')->label('Prazo Realizado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('link')->label('Link VISI')->url(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Implantação')
                ->description(function (Get $get): HtmlString {
                    $inaug = $get('inauguracao');
                    $crono = $get('cronograma_implantacao');
                    $fields = ['inicio_imp', 'fim_imp', 'cronograma_implantacao', 'inauguracao', 'mes', 'ano', 'observacao'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";
                    if (filled($inaug)) {
                        $html .= "<span class='text-green-500 dark:text-green-400 ml-2' style='font-size:0.7rem'>| Inaug: ".Carbon::parse($inaug)->format('d/m/Y').'</span>';
                    } elseif ($crono === 'nao_enviado') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Cronograma não enviado</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('inicio_imp')->label('Início')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('fim_imp')->label('Fim')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\Select::make('cronograma_implantacao')
                        ->label('Cronograma de Implantação')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\TextInput::make('inauguracao')
                        ->label('Inauguração')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto)
                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null),
                    Forms\Components\TextInput::make('imp_prazo_planej')->label('Prazo Planejado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('imp_prazo_realiz')->label('Prazo Realizado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('mes')->label('Mês'),
                    Forms\Components\TextInput::make('ano')->label('Ano'),
                    Forms\Components\Textarea::make('observacao')->label('Observação')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Dados do Imóvel')
                ->description(self::fieldStats(
                    ['tipo_imovel', 'endereco', 'cidade', 'uf', 'empreendimento', 'locacao', 'contato_corretor'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('tipo_imovel')->label('Tipo do Imóvel')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('endereco')->label('Endereço')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('cidade')->label('Cidade')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('uf')->label('Estado')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('empreendimento')->label('Empreendimento')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('locacao')
                        ->label('Locação')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                    Forms\Components\TextInput::make('contato_corretor')
                        ->label('Contato Corretor / PP')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            Section::make('% de Obra')
                ->description(function (Get $get): HtmlString {
                    $prev = $get('percentual_obra');
                    $exec = $get('percentual_obra_executado');
                    $desvio = $get('desvio');

                    $parts = [];
                    if (filled($prev)) {
                        $parts[] = "Previsto: {$prev}%";
                    }
                    if (filled($exec)) {
                        $parts[] = "Executado: {$exec}%";
                    }
                    if (filled($desvio)) {
                        $desvioColor = floatval($desvio) < 0 ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400';
                        $parts[] = "<span class='{$desvioColor}'>Desvio: {$desvio}%</span>";
                    }

                    $html = $parts
                        ? "<span style='font-size:0.75rem'>".implode(' <span class="text-gray-400">|</span> ', $parts).'</span>'
                        : "<span class='text-gray-400 dark:text-gray-500' style='font-size:0.75rem'>Sem dados de progresso</span>";

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\TextInput::make('dias_para_inauguracao')
                        ->label('Dias para Inauguração')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('dias_obra_inicio_pmo')
                        ->label('Dias de Obra (Início PMO)')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('percentual_obra')->label('% Previsto')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('percentual_obra_executado')->label('% Executado')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('desvio')->label('Desvio')
                        ->disabled()->dehydrated(false)->suffix('%')->hint($hintCalc),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Acompanhamento de Obra')
                ->description(function (Get $get): HtmlString {
                    $criticos = $get('itens_criticos');
                    if (filled($criticos)) {
                        return new HtmlString("<span class='text-red-500 dark:text-red-400' style='font-size:0.75rem'>Itens críticos: {$criticos}</span>");
                    }

                    return new HtmlString("<span class='text-green-500 dark:text-green-400' style='font-size:0.75rem'>Sem itens críticos</span>");
                })
                ->schema([
                    Forms\Components\TextInput::make('itens_criticos')->label('Itens Críticos'),
                    Forms\Components\Textarea::make('descricao_itens_criticos')->label('Descrição Itens Críticos'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Cronograma Visi')
                ->description(self::fieldStats(
                    ['cronograma_visi', 'camera_unidade', 'ponto_atencao'],
                ))
                ->schema([
                    Forms\Components\Select::make('cronograma_visi')
                        ->label('Cronograma Visi')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('camera_unidade')
                        ->label('Câmera na Unidade')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                    Forms\Components\Textarea::make('ponto_atencao')->label('Ponto de Atenção')->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Contas de Consumo')
                ->description(function (Get $get): HtmlString {
                    $items = [
                        'E' => $get('energia'),
                        'A' => $get('agua'),
                        'G' => $get('gas'),
                    ];
                    $parts = [];
                    foreach ($items as $label => $val) {
                        if (filled($val)) {
                            $isOk = str_contains(strtolower($val), 'ligada');
                            $color = $isOk ? 'text-green-500 dark:text-green-400' : 'text-amber-500 dark:text-amber-400';
                            $parts[] = "<span class='{$color}'>{$label}</span>";
                        } else {
                            $parts[] = "<span class='text-gray-400 dark:text-gray-500'>{$label}</span>";
                        }
                    }

                    return new HtmlString("<span style='font-size:0.75rem'>".implode(' ', $parts).'</span>');
                })
                ->schema([
                    Forms\Components\Select::make('energia')
                        ->label('Energia')
                        ->options([
                            'Ligada / Rateio' => 'Ligada / Rateio',
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'GERADOR' => 'GERADOR',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('previsao_ligacao_energia')->label('Previsão Ligação Energia'),
                    Forms\Components\TextInput::make('gerador_contratual')->label('Gerador Contratual'),
                    Forms\Components\Select::make('agua')
                        ->label('Água')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Ligada / Rateio' => 'Ligada / Rateio',
                        ])
                        ->native(false),
                    Forms\Components\Select::make('gas')
                        ->label('Gás')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Boiler Instalado provisório' => 'Boiler Instalado provisório',
                        ])
                        ->native(false),
                    Forms\Components\Textarea::make('comentario')->label('Comentário')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            Section::make('Pós-Obra')
                ->description(function (Get $get): HtmlString {
                    $fields = ['email_solicitacao_cl', 'envio_qrcod', 'checklist_manutencao', 'data_check_list', 'inicio_prev_pendencias', 'termino_prev_pendencias', 'elevador', 'gestor_pos_obra', 'comentarios_adicionais'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";

                    $checklist = $get('checklist_manutencao');
                    if ($checklist === 'em_atraso') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Checklist em atraso</span>";
                    } elseif ($checklist === 'concluido') {
                        $html .= "<span class='text-green-500 dark:text-green-400 ml-2' style='font-size:0.7rem'>| Checklist concluído</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\Select::make('email_solicitacao_cl')
                        ->label('E-mail Solicitação CL')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('envio_qrcod')
                        ->label('Envio QRCODE')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('checklist_manutencao')
                        ->label('Checklist Manutenção')
                        ->options([
                            'concluido' => 'Concluído',
                            'em_andamento' => 'Em andamento',
                            'em_atraso' => 'Em atraso',
                            'nao_iniciado' => 'Não iniciado',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('data_check_list')->label('Data Check List'),
                    Forms\Components\DatePicker::make('inicio_prev_pendencias')->label('Início Prev. Pendências'),
                    Forms\Components\DatePicker::make('termino_prev_pendencias')->label('Término Prev. Pendências'),
                    Forms\Components\TextInput::make('elevador')->label('Elevador'),
                    Forms\Components\TextInput::make('gestor_pos_obra')->label('Gestor Pós Obra'),
                    Forms\Components\Textarea::make('comentarios_adicionais')->label('Comentários Adicionais')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            self::buildPontosAtencaoSection(),
        ];
    }

    public static function fieldStats(array $fields, ?string $extra = null): Closure
    {
        return function (Get $get) use ($fields, $extra): HtmlString {
            $filled = 0;
            foreach ($fields as $f) {
                if (filled($get($f))) {
                    $filled++;
                }
            }
            $total = count($fields);
            $pct = $total > 0 ? round(($filled / $total) * 100) : 0;

            if ($pct >= 80) {
                $color = 'text-green-500 dark:text-green-400';
                $icon = '&#10004;';
            } elseif ($pct >= 40) {
                $color = 'text-amber-500 dark:text-amber-400';
                $icon = '&#9679;';
            } else {
                $color = 'text-gray-400 dark:text-gray-500';
                $icon = '&#9675;';
            }

            $html = "<span class='{$color}' style='font-size:0.75rem'>{$icon} {$filled}/{$total} ({$pct}%)</span>";

            if ($extra) {
                $extraVal = $get($extra);
                if (filled($extraVal)) {
                    $html .= "<span class='text-gray-400 dark:text-gray-500 ml-2' style='font-size:0.7rem'>| {$extraVal}</span>";
                }
            }

            return new HtmlString($html);
        };
    }

    public static function buildPontosAtencaoSection(): Section
    {
        return Section::make('Pontos de Atenção')
            ->description('Campos personalizados por projeto')
            ->schema(function (Get $get) {
                $obrasId = $get('id');
                if (! $obrasId) {
                    return [];
                }

                $colunas = ColunaPersonalizada::query()
                    ->where('obra_id', $obrasId)
                    ->whereNotNull('nome')
                    ->orderBy('nome')
                    ->get();

                if ($colunas->isEmpty()) {
                    return [
                        Forms\Components\Placeholder::make('sem_colunas')
                            ->label('')
                            ->content('Nenhum campo personalizado cadastrado para esta obra.'),
                    ];
                }

                $fields = [];
                foreach ($colunas as $coluna) {
                    $key = 'ponto_atencao_id_'.$coluna->id;
                    $tipo = (string) ($coluna->tipo ?? 'texto');

                    if ($tipo === 'select') {
                        $opcoes = collect($coluna->opcoes ?? [])
                            ->map(fn ($item) => trim((string) $item))
                            ->filter(fn ($item) => $item !== '')
                            ->values()
                            ->all();

                        $opcoesMap = collect($opcoes)
                            ->mapWithKeys(fn ($item) => [$item => $item])
                            ->all();

                        $fields[] = Forms\Components\Select::make($key)
                            ->label((string) $coluna->nome)
                            ->options($opcoesMap)
                            ->native(false)
                            ->default($coluna->valor);
                    } elseif ($tipo === 'numero') {
                        $fields[] = Forms\Components\TextInput::make($key)
                            ->label((string) $coluna->nome)
                            ->numeric()
                            ->default($coluna->valor);
                    } elseif ($tipo === 'data') {
                        $fields[] = Forms\Components\DatePicker::make($key)
                            ->label((string) $coluna->nome)
                            ->default($coluna->valor);
                    } else {
                        $fields[] = Forms\Components\TextInput::make($key)
                            ->label((string) $coluna->nome)
                            ->default($coluna->valor);
                    }
                }

                return $fields;
            })
            ->columns(2)
            ->collapsible();
    }

    public static function getPontosAtencaoValues(Obras $record): array
    {
        $result = [];
        $colunas = ColunaPersonalizada::query()
            ->where('obra_id', $record->id)
            ->whereNotNull('nome')
            ->get();

        foreach ($colunas as $coluna) {
            $key = 'ponto_atencao_id_'.$coluna->id;
            $result[$key] = $coluna->valor;
        }

        return $result;
    }

    public static function saveFromForm(Obras $record, array $data): void
    {
        $pontosAtencaoData = [];
        $mainData = [];

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, 'ponto_atencao_id_')) {
                $colunaId = (int) Str::replace('ponto_atencao_id_', '', $key);
                $pontosAtencaoData[$colunaId] = $value;
            } else {
                $mainData[$key] = $value;
            }
        }

        $record->update($mainData);

        foreach ($pontosAtencaoData as $colunaId => $valor) {
            ColunaPersonalizada::query()
                ->where('id', $colunaId)
                ->where('obra_id', $record->id)
                ->update([
                    'valor' => filled($valor) ? substr((string) $valor, 0, 255) : null,
                    'usuario_id' => auth()->id(),
                ]);
        }
    }
}
