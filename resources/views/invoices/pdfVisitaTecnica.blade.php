<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de VT</title>
    <link rel="stylesheet" href="{{ public_path('css/visitaTecnica/visita.css') }}">
</head>

<body>
    @php
        use App\Support\PdfFormatter;
        use App\Support\PdfMedia;
        use Carbon\Carbon;

        $condicao = $record?->condicoes_imovel;

        $contratosBts = $record?->contrato_bts ?? [];
        $contratosBts = is_array($contratosBts) ? $contratosBts : [$contratosBts];
        $contratosBts = array_values(array_filter($contratosBts));

        $comentarioCondicoes = trim(strip_tags($record?->comentario_condicoes_imovel ?? ''));

        $pavimentos = $record?->pavimento ?? [];
        $pavimentos = is_array($pavimentos) ? $pavimentos : [$pavimentos];
        $temOutroPavimento = in_array('Outro (descrever)', $pavimentos, true);
        $pavimentosSemOutro = array_filter($pavimentos, fn ($item) => $item !== 'Outro (descrever)');

        $sistemasIncendio = $record?->sistema_incendio_existente ?? [];
        $sistemasIncendio = is_array($sistemasIncendio) ? $sistemasIncendio : [$sistemasIncendio];
        $sistemasIncendio = array_filter($sistemasIncendio);
    @endphp

    <!-- ===================== CABEÇALHO ===================== -->
    <div class="img">
        <img src="{{ PdfMedia::src('images/logo-smart.png') }}" alt="SmartFit">
    </div>

    <div class="header">
        <div class="linha"></div>

        <table class="title">
            <tr>
                <td>
                    <h2>Relatório de VT (novo)</h2>
                    <p class="id">#{{ $record?->numero_relatorio_vt }}</p>
                </td>
            </tr>
        </table>

        <table class="meta">
            <colgroup>
                <col style="width:50%">
                <col style="width:50%">
            </colgroup>

            <tr>
                <td class="label">Iniciado em</td>
                <td class="label">Concluído em</td>
            </tr>
            <tr>
                <td class="value">
                    {{ !empty($record?->iniciado_em) ? Carbon::parse($record->iniciado_em)->format('d/m/Y H:i') : 'Não se aplica' }}
                </td>
                <td class="value">
                    {{ !empty($record?->concluido_em) ? Carbon::parse($record->concluido_em)->format('d/m/Y H:i') : 'Não se aplica' }}
                </td>
            </tr>

            <tr>
                <td class="label" colspan="2">Autor</td>
            </tr>
            <tr>
                <td class="value" colspan="2">{{ $record?->autor ?? 'Não se aplica' }}</td>
            </tr>

            <tr>
                <td class="label" colspan="2">Unidade</td>
            </tr>
            <tr>
                <td class="value" colspan="2">{{ $record?->unidade ?? 'Não se aplica' }}</td>
            </tr>
        </table>
    </div>

    <!-- ===================== FOTO DE CAPA ===================== -->
    @if (!empty($record?->foto_capa))
        @php
            $fotoCapa = $record->foto_capa;

            if (is_string($fotoCapa)) {
                $decoded = json_decode($fotoCapa, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $fotoCapa = $decoded[0] ?? null;
                }
            }

            if (is_array($fotoCapa)) {
                $fotoCapa = $fotoCapa[0] ?? null;
            }

            $fotoCapaSrc = $fotoCapa ? PdfMedia::src($fotoCapa) : null;
        @endphp

        @if ($fotoCapaSrc)
            <div class="section cover-section">
                <div class="linha"></div>

                <table class="cover-table">
                    <tr>
                        <td>
                            <img
                                src="{{ $fotoCapaSrc }}"
                                alt="Foto de capa"
                                class="cover-image"
                            >
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endif

    <!-- ===================== RESUMO ===================== -->
    <div class="section">
        <h3 class="titulo">Resumo</h3>
        <div class="linha"></div>

        <table class="resumo">
            <tr>
                <td class="bloco">
                    <div class="label">Itens respondidos</div>
                    <div class="valor-grande">
                        {{ $totalSim + $totalNao + $totalNa }}
                        <span class="de">de {{ $totalItens }}</span>
                    </div>
                </td>

                <td class="bloco">
                    <div class="label">Itens avaliados</div>

                    <table style="margin-top:6px; border-collapse:collapse; font-weight:bold; border:none;">
                        <tr>
                            <td style="padding-right:14px; white-space:nowrap; border:none;">
                                <span class="na">N/A {{ $totalNa }}</span>
                            </td>

                            <td style="padding-right:14px; white-space:nowrap; border:none;">
                                <span class="nao">NÃO {{ $totalNao }}</span>
                            </td>

                            <td style="white-space:nowrap; border:none;">
                                <span class="sim">SIM {{ $totalSim }}</span>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="bloco">
                    <div class="label">Complementos</div>

                    <table style="margin-top:6px; font-size:14px; font-weight:bold; border:none; border-collapse:collapse;">
                        <tr>
                            <td style="padding-right:20px; vertical-align:middle; border:none;">
                                <img src="{{ public_path('images/icons/chat.png') }}" style="width:16px; height:16px; vertical-align:middle;" alt="Comentários">
                                <span style="margin-left:4px;">{{ $comentarios }}</span>
                            </td>

                            <td style="padding-right:20px; vertical-align:middle; border:none;">
                                <img src="{{ public_path('images/icons/image.png') }}" style="width:16px; height:16px; vertical-align:middle;" alt="Imagens">
                                <span style="margin-left:4px;">{{ $anexos }}</span>
                            </td>

                            <td style="vertical-align:middle; border:none;">
                                <img src="{{ public_path('images/icons/link.png') }}" style="width:16px; height:16px; vertical-align:middle;" alt="Links">
                                <span style="margin-left:4px;">{{ $videos }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- ===================== ITENS ===================== -->
    <div class="section">
        <h3 class="titulo">Itens</h3>
        <div class="linha"></div>

        <div>
            <div class="area-header">
                <span class="area-title">Área 1 | Informações Técnicas</span>
                <div class="area-subtitle">Apresentação do ponto</div>
            </div>

            <div class="field">
                <label>Unidade (Nome comercial do ponto)</label>
                <div class="value">{{ $record?->unidade ?? 'Não se aplica' }}</div>
            </div>

            <div class="field">
                <label>Marca <span class="peso">(Peso 1 | Obrigatório)</span></label>
                <div class="value check">{{ $marca?->nome ?? 'Não se aplica' }}</div>
            </div>

            <div class="field">
                <label>Endereço completo <span class="peso">(Obrigatório)</span></label>
                <div class="value">{{ $record?->endereco ?? 'Não se aplica' }}</div>
            </div>

            <div class="field">
                <label>Condições em que o imóvel se encontra: <span class="peso">(Peso 1 | Obrigatório)</span></label>
                <div class="value">
                    {{ $condicao ?? 'Não se aplica' }}

                    @if ($condicao === 'BTS em construção (Informar prazo previsto de entrega do Shell e prazo de entrega de documentação, AVCB e Habite-se)')
                        @if ($record?->prazo_bts)
                            <div style="margin-top: 6px;">
                                <strong>Prazo previsto:</strong>
                                {{ Carbon::parse($record->prazo_bts)->format('d/m/Y') }}
                            </div>
                        @endif

                        @if (count($contratosBts))
                            <div style="margin-top: 8px;">
                                <strong>Contrato(s) anexado(s):</strong>
                            </div>

                            @include('invoices.partials.attachment-links', [
                                'arquivos' => $contratosBts,
                            ])
                        @endif
                    @endif

                    @if ($condicao === 'Imóvel pronto com ocupação (informar prazo de desocupação)' && $record?->prazo_desocupacao)
                        <div style="margin-top: 6px;">
                            <strong>Prazo de desocupação:</strong>
                            {{ Carbon::parse($record->prazo_desocupacao)->format('d/m/Y') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="field">
                <label>Comentário das condições do imóvel</label>
                <div class="value">
                    {!! $comentarioCondicoes !== '' ? $record->comentario_condicoes_imovel : 'Não se aplica' !!}
                </div>
            </div>

            <div class="field">
                <label>Configuração de pavimentos <span class="peso">(Peso 1 | Obrigatório)</span></label>
                <div class="value">
                    @if (empty($pavimentos))
                        Não se aplica
                    @else
                        {{ implode(', ', $pavimentosSemOutro) }}

                        @if ($temOutroPavimento && filled($record?->pavimento_outro))
                            {{ !empty($pavimentosSemOutro) ? ' - ' : '' }}{{ $record->pavimento_outro }}
                        @elseif ($temOutroPavimento)
                            {{ !empty($pavimentosSemOutro) ? ' - ' : '' }}Outro (descrever)
                        @endif
                    @endif
                </div>
            </div>

            <div class="field">
                <label>Empreendimento <span class="peso">(Obrigatório)</span></label>
                <div class="value">
                    @if (($record?->empreendimento ?? null) === 'Outro (descrever)')
                        {{ $record?->empreendimento_outro ?? 'Não se aplica' }}
                    @else
                        {{ $record?->empreendimento ?? 'Não se aplica' }}
                    @endif
                </div>
            </div>

            <div class="field">
                <label>Locação: <span class="peso">(Obrigatório)</span></label>
                <div class="value">{{ $record?->locacao ?? 'Não se aplica' }}</div>
            </div>

            @if (!is_null($record?->validador_ticket_estacionamento))
                <div class="question-header teste">
                    <label><strong>Validador de ticket de estacionamento:</strong></label>
                    {!! PdfFormatter::badge($record->validador_ticket_estacionamento) !!}
                </div>
            @endif

            <div class="field">
                <label>Inserir contato dos responsáveis pelo imóvel (Proprietário, corretor..) Contato, Telefone e email. <span class="peso">(Obrigatório)</span></label>
                <div class="value">{{ $record?->contato_responsavel ?? 'Não se aplica' }}</div>
            </div>

            <div class="field">
                <label>Etapa de contrato <span class="peso">(Obrigatório)</span></label>
                <div class="value">{{ $record?->etapa_contrato ?? 'Não se aplica' }}</div>
            </div>

            <div class="field">
                <label>Prazo de Obras <span class="peso">(Obrigatório)</span></label>
                <div class="value">
                    @if ($record?->prazo_de_obras === 'outro')
                        {{ $record?->prazo_de_obras_outro ? $record->prazo_de_obras_outro . ' dias' : 'Não informado' }}
                    @else
                        {{ $record?->prazo_de_obras ?? 'Não informado' }}
                    @endif
                </div>
            </div>

            <div class="field mt">
                <label>Descrição do prazo de obras:</label>
                <div class="value">
                    {{ $record?->descricao_prazo_obras ?? 'Não se aplica' }}
                </div>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Foi disponibilizado projeto ou planta com demarcação da área ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->planta_demarcacao_area,
                'fields' => [
                    [
                        'label' => 'Link do projeto ou planta:',
                        'html' => $record?->link_planta_demarcacao_area
                            ? '<a href="' . e($record->link_planta_demarcacao_area) . '" target="_blank" rel="noopener noreferrer">Documento</a>'
                            : '<span>Não informado</span>',
                    ],
                    [
                        'label' => 'Descrição do projeto ou planta:',
                        'value' => $record?->descricao_planta_demarcacao_area ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_planta_demarcacao_area,
            ])
        </div>

        <!-- Elétrica/Telefonia/Internet -->
        <div>
            <div class="area-header">
                <span class="area-title">Área 2 | Elétrica/Telefonia/Internet</span>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Entrada de energia - Tensão disponível',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'showBadge' => false,
                'fields' => [
                    [
                        'label' => 'Tensão disponível:',
                        'value' => match ($record?->entrada_de_energia) {
                            '380_220' => '380/220V',
                            '220_127' => '220/127V',
                            'nao_informado' => 'Não informado',
                            default => 'Não informado',
                        },
                    ],
                    [
                        'label' => 'Descrição Energia:',
                        'value' => $record?->descricao_energia ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_entrada_de_energia,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Temos disponível carga superior a 150kVA?:',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->energia_carga_superior_150,
                'fields' => [
                    [
                        'label' => 'Descrição da energia com carga superior a 150kVA:',
                        'value' => $record?->descricao_energia_carga_superior_150 ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_energia_carga_superior_150,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Temos energia provisória para obra ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->energia_provisoria,
                'fields' => [
                    [
                        'label' => 'Descrição Energia:',
                        'value' => $record?->descricao_energia_provisoria ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_energia_provisoria,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Cabos alimentadores entregues dentro do shell?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->cabos_alimentadores_shell,
                'fields' => array_values(array_filter([
                    $record?->cabos_alimentadores_shell === 0 ? [
                        'label' => 'Quantidade de metros para cabeamento:',
                        'value' => $record?->metros_cabeamento ? $record->metros_cabeamento . ' m' : 'Não informado',
                    ] : null,
                ])),
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Única medição?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->unica_medicao,
                'fields' => [
                    [
                        'label' => 'Descrição Energia:',
                        'value' => $record?->descricao_medicao ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_unica_medicao,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'SPDA existente?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->spda,
                'fields' => [
                    [
                        'label' => 'Descrição SPDA:',
                        'value' => $record?->descricao_spda ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_spda,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Telefonia (DG) dentro do Shell ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->telegonia_dg,
                'fields' => array_values(array_filter([
                    [
                        'label' => 'Descrição Telefonia:',
                        'value' => $record?->descricao_telefonia ?? 'Não se aplica',
                    ],
                    (int) ($record?->telegonia_dg ?? 1) === 0 ? [
                        'label' => 'Distância até o ponto mais próximo (m):',
                        'value' => $record?->distancia_ponto_telefonia !== null
                            ? number_format((float) $record->distancia_ponto_telefonia, 2, ',', '.') . ' m'
                            : 'Não informado',
                    ] : null,
                ])),
                'media' => $record?->foto_telegonia_dg,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'É necessário a visita do consultor de energia?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->necessario_visita_consultor_energia,
                'fields' => [
                    [
                        'label' => 'Necessita visita do consultor de energia:',
                        'value' => (int) ($record?->necessario_visita_consultor_energia ?? 0) === 1 ? 'Sim' : 'Não',
                    ],
                ],
            ])

            <div class="area-header">
                <span class="area-title">Área 3 | 2 - Estrutura/Cobertura/Acustica</span>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Cobertura com isolamento térmico',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->cobertura_isolamento,
                'fields' => array_values(array_filter([
                    $record?->cobertura_isolamento === 0 ? [
                        'label' => 'Área que necessita isolamento térmico:',
                        'value' => $record?->cobertura_area_isolamento
                            ? number_format($record->cobertura_area_isolamento, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição Cobertura Isolamento:',
                        'value' => $record?->descricao_cobertura_isolamento ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_cobertura_isolamento,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Tipo de estrutura',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'showBadge' => false,
                'fields' => array_values(array_filter([
                    [
                        'label' => 'Tipo Estrutura:',
                        'value' => $record?->tipo_estrutura ?? 'Não se aplica',
                    ],
                    $record?->tipo_estrutura_outro != null ? [
                        'label' => 'Tipo Estrutura Outro:',
                        'value' => $record?->tipo_estrutura_outro ?? 'Não se aplica',
                    ] : null,
                ])),
                'media' => $record?->foto_necessario_estrutura_auxiliar,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Cobertura com vãos inferiores a 1,5m nos dois sentidos?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->cobertura_vao_1_5,
                'fields' => array_values(array_filter([
                    $record?->cobertura_vao_1_5 === 0 ? [
                        'label' => 'Metragem do espaçamento:',
                        'value' => $record?->cobertura_vao_1_5_metragem
                            ? number_format($record->cobertura_vao_1_5_metragem, 2, ',', '.') . ' m'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição Cobertura:',
                        'value' => $record?->descricao_cobertura_vao_1_5 ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_cobertura_vao_1_5,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Imóvel com estrutura para fachada ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->estrutura_fachada,
                'fields' => [
                    [
                        'label' => 'Descrição Estrutura Fachada:',
                        'value' => $record?->descricao_estrutura_fachada ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_estrutura_fachada,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Permitidas furações de laje',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->permitidas_furacoes_laje,
                'fields' => [
                    [
                        'label' => 'Descrição das furações na laje:',
                        'value' => $record?->descricao_furacoes_laje ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_permitidas_furacoes_laje,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Sobrecarga mínima da laje (500kg/m²)',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->sobrecarga_minima_laje,
                'fields' => array_values(array_filter([
                    $record?->sobrecarga_minima_laje == 1 ? [
                        'label' => 'Comprovação:',
                        'value' => $record?->comprovacao_sobrecarga_laje ?? 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da sobrecarga da laje:',
                        'value' => $record?->descricao_sobrecarga_minima_laje ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_sobrecarga_minima_laje,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Sobrecarga mínima de laje de teto (35kg/m²)',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->sobrecarga_minima_laje_teto,
                'fields' => array_values(array_filter([
                    $record?->sobrecarga_minima_laje_teto == 1 ? [
                        'label' => 'Comprovação:',
                        'value' => $record?->comprovacao_sobrecarga_laje_teto ?? 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da sobrecarga no teto:',
                        'value' => $record?->descricao_sobrecarga_minima_laje_teto ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_sobrecarga_minima_laje_teto,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Existe local para tomada de ar externo/ exaustão',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->local_tomada_ar_externo_exaustao,
                'fields' => [
                    [
                        'label' => 'Descrição do ponto de exaustão/ar:',
                        'value' => $record?->descricao_local_tomada_ar_externo_exaustao ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_local_tomada_ar_externo_exaustao,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Alvenaria de periferia existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->alvenaria_periferia_existente,
                'fields' => array_values(array_filter([
                    $record?->alvenaria_periferia_existente == 0 ? [
                        'label' => 'Quantidade de metros de alvenaria:',
                        'value' => $record?->metros_alvenaria_periferia
                            ? number_format($record->metros_alvenaria_periferia, 2, ',', '.') . ' m'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da alvenaria da periferia:',
                        'value' => $record?->descricao_alvenaria_periferia_existente ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_alvenaria_periferia_existente,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Reboco interno e externo existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->reboco_interno_externo_existente,
                'fields' => array_values(array_filter([
                    $record?->reboco_interno_externo_existente == 0 ? [
                        'label' => 'Quantidade de metros de reboco:',
                        'value' => $record?->metros_reboco
                            ? number_format($record->metros_reboco, 2, ',', '.') . ' m'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do reboco interno/externo:',
                        'value' => $record?->descricao_reboco_interno_externo_existente ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_reboco_interno_externo_existente,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Necessita de estanqueidade ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->estanqueidade,
                'fields' => [
                    [
                        'label' => 'Descrição da estanqueidade:',
                        'value' => ($record?->descricao_estanqueidade ?? null) === 'outro'
                            ? ($record?->estanqueidade_outro ?? 'Não se aplica')
                            : ($record?->descricao_estanqueidade ?? 'Não se aplica'),
                    ],
                    [
                        'label' => 'Descrição complementar da estanqueidade:',
                        'value' => $record?->descricao_complementar_estanqueidade ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_estanqueidade,
            ])

            <div class="area-header">
                <span class="area-title">Área 4 | 3 - Área técnica</span>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Área técnica externa existente',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->area_tecnica_externa_existente,
                'fields' => [
                    [
                        'label' => 'Descrição da área técnica externa existente:',
                        'value' => $record?->descricao_area_tecnica_externa_existente ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_area_tecnica_externa_existente,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Prever acústica de condensadoras',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->prever_acustica_condensadores,
                'fields' => [
                    [
                        'label' => 'Descrição do tratamento acústico:',
                        'value' => $record?->descricao_prever_acustica_condensadores ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_prever_acustica_condensadores,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Prever proteção para condensadoras',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->prever_protecao_condensadores,
                'fields' => [
                    [
                        'label' => 'Descrição da proteção para condensadoras:',
                        'value' => $record?->descricao_prever_protecao_condensadores ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_prever_protecao_condensadores,
            ])

            <div class="area-header">
                <span class="area-title">Área 5 | 4 - Hidráulica/Esgoto/Gás:</span>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Reservatório de água existente',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->reservatorio_agua_existente,
                'fields' => array_values(array_filter([
                    $record?->reservatorio_agua_existente == 1 ? [
                        'label' => 'Litragem do reservatório:',
                        'value' => $record?->reservatorio_agua_litragem
                            ? number_format($record->reservatorio_agua_litragem, 2, ',', '.') . ' L'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do reservatório de água:',
                        'value' => $record?->descricao_reservatorio_agua_existente ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_reservatorio_agua_existente,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Reservatório de incêndio existente',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->reservatorio_incendio_existente,
                'fields' => array_values(array_filter([
                    $record?->reservatorio_incendio_existente == 1 ? [
                        'label' => 'Litragem do reservatório de incêndio:',
                        'value' => $record?->reservatorio_incendio_litragem
                            ? number_format($record->reservatorio_incendio_litragem, 2, ',', '.') . ' L'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do reservatório de incêndio:',
                        'value' => $record?->descricao_reservatorio_incendio_existente ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_reservatorio_incendio_existente,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Ponto de esgoto existente dentro do shell',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->ponto_esgoto_existente_shell,
                'fields' => array_values(array_filter([
                    $record?->ponto_esgoto_existente_shell == 0 ? [
                        'label' => 'Ponto de esgoto mais próximo:',
                        'value' => $record?->ponto_esgoto_mais_proximo ?? 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do ponto de esgoto:',
                        'value' => $record?->descricao_ponto_esgoto_existente_shell ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_ponto_esgoto_existente_shell,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Rede de gás disponível',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'showBadge' => false,
                'fields' => array_values(array_filter([
                    [
                        'label' => 'Tipo de rede de gás:',
                        'value' => $record?->rede_gas_disponivel ?? 'Não informado',
                    ],
                    ($record?->rede_gas_disponivel ?? null) === 'GN (solicitar ligação)' ? [
                        'label' => 'Distância até o ponto mais próximo (m):',
                        'value' => $record?->distancia_rede_gas !== null
                            ? number_format((float) $record->distancia_rede_gas, 2, ',', '.') . ' m'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da rede de gás:',
                        'value' => $record?->descricao_rede_gas_disponivel ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_rede_gas_disponivel,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Medidor de água instalado e ligado',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->medidor_agua_instalado_ligado,
                'fields' => array_values(array_filter([
                    $record?->medidor_agua_instalado_ligado == 1 ? [
                        'label' => 'Número da instalação:',
                        'value' => $record?->numero_instalacao_agua ?? 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do medidor de água:',
                        'value' => $record?->descricao_medidor_agua_instalado_ligado ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_medidor_agua_instalado_ligado,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Sistema de incêndio existente',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'showBadge' => false,
                'fields' => [
                    [
                        'label' => 'Sistema existente:',
                        'value' => !empty($sistemasIncendio) ? implode(', ', $sistemasIncendio) : 'Não informado',
                    ],
                    [
                        'label' => 'Descrição do sistema de incêndio:',
                        'value' => $record?->descricao_sistema_incendio_existente ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_sistema_incendio_existente,
            ])

            <div class="area-header">
                <span class="area-title">Área 6 | 5 - Arquitetura/Civil:</span>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'PD acima de 3,5 m livres',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->pd_acima_livre,
                'fields' => [
                    [
                        'label' => 'Descrição do pé-direito:',
                        'value' => $record?->descricao_pd_acima_livre ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_pd_acima_livre,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Em caso de necessidade o elevador ou plataforma é existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->necessario_elevador_plataforma,
                'fields' => [
                    [
                        'label' => 'Descrição sobre acessibilidade vertical:',
                        'value' => $record?->descricao_necessario_elevador_plataforma ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_necessario_elevador_plataforma,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Piso com acabamento polido',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->piso_acabamento_polido,
                'fields' => array_values(array_filter([
                    $record?->piso_acabamento_polido == 0 ? [
                        'label' => 'Área que necessita intervenção:',
                        'value' => $record?->piso_area_intervencao
                            ? number_format($record->piso_area_intervencao, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição do piso polido:',
                        'value' => $record?->descricao_piso_acabamento_polido ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_piso_acabamento_polido,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Película na fachada existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->necessario_pelicula_fachada,
                'fields' => array_values(array_filter([
                    $record?->necessario_pelicula_fachada === 0 ? [
                        'label' => 'Área que necessita película:',
                        'value' => $record?->pelicula_fachada_area
                            ? number_format($record->pelicula_fachada_area, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da película na fachada:',
                        'value' => $record?->descricao_necessario_pelicula_fachada ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_necessario_pelicula_fachada,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Marquise existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->prever_marquise,
                'fields' => [
                    [
                        'label' => 'Descrição da marquise:',
                        'value' => $record?->descricao_prever_marquise ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_prever_marquise,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Porta de enrolar existente ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->prever_porta_enrolar,
                'fields' => array_values(array_filter([
                    $record?->prever_porta_enrolar === 0 ? [
                        'label' => 'Área aproximada necessária:',
                        'value' => $record?->porta_enrolar_area_necessaria
                            ? number_format($record->porta_enrolar_area_necessaria, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da porta de enrolar:',
                        'value' => $record?->descricao_prever_porta_enrolar ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_prever_porta_enrolar,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Caixilhos e vidros existentes ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->caixilhos_vidros_existentes,
                'fields' => array_values(array_filter([
                    $record?->caixilhos_vidros_existentes === 0 ? [
                        'label' => 'Área necessária de caixilhos/vidros:',
                        'value' => $record?->caixilhos_vidros_area
                            ? number_format($record->caixilhos_vidros_area, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição dos caixilhos:',
                        'value' => $record?->descricao_caixilhos_vidros_existentes ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_caixilhos_vidros_existentes,
            ])

            @include('invoices.partials.question-card', [
                'title' => 'Impermeabilização externa executada ?',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->prever_impermeabilizacao,
                'fields' => array_values(array_filter([
                    $record?->prever_impermeabilizacao === 0 ? [
                        'label' => 'Área que necessita impermeabilização:',
                        'value' => $record?->impermeabilizacao_area_necessaria
                            ? number_format($record->impermeabilizacao_area_necessaria, 2, ',', '.') . ' m²'
                            : 'Não informado',
                    ] : null,
                    [
                        'label' => 'Descrição da impermeabilização:',
                        'value' => $record?->descricao_prever_impermeabilizacao ?? 'Não se aplica',
                    ],
                ])),
                'media' => $record?->foto_prever_impermeabilizacao,
            ])

            {{--
            @include('invoices.partials.question-card', [
                'title' => 'É necessário considerar porta de enrolar? (Levar em consideração a segurança do local e se os comércios em volta utilizam.)',
                'subtitle' => '(Peso 1 | Obrigatório)',
                'badge' => $record?->necessario_porta_enrolar,
                'fields' => [
                    [
                        'label' => 'Descrição da impermeabilização:',
                        'value' => $record?->descricao_necessario_porta_enrolar ?? 'Não se aplica',
                    ],
                ],
                'media' => $record?->foto_necessario_porta_enrolar,
            ])
            --}}

            <div class="area-header">
                <span class="area-title">Área 7 – Comentários Adicionais</span>
            </div>

            <div class="card">
                <div class="question-header">
                    <h4>Pontos de atenção</h4>
                </div>

                <div class="field mt">
                    <div class="value rich-content">
                        {!! $record?->pontos_atencao ?: 'Não se aplica' !!}
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="question-header">
                    <h4>Observações gerais</h4>
                </div>

                <div class="field mt">
                    <div class="value">
                        {!! $record?->observacoes_gerais ?? 'Não se aplica' !!}
                    </div>
                </div>
            </div>

            @include('invoices.partials.question-card', [
                'title' => 'Fotos gerais',
                'fields' => [],
                'media' => $record?->fotos_gerais,
                'showBadge' => false,
            ])
        </div>
    </div>
</body>

</html>