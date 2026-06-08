<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 16px 18px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            line-height: 1.22;
            color: #000;
            background: #fff;
        }

        .sheet {
            border: 2px solid #f5b400;
            padding: 7px 9px 8px;
            min-height: 0;
        }

        .brand-row {
            height: 34px;
        }

        .brand-row td {
            padding: 0;
            vertical-align: middle;
        }

        .brand-band {
            max-height: 27px;
            max-width: 360px;
        }

        .yellow-title {
            height: 14px;
            margin: 0 0 7px;
            background: #ffc000;
            border: 1px solid #808080;
            text-align: center;
            font-size: 10px;
            line-height: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .bar {
            height: 13px;
            margin-top: 9px;
            background: #ffc000;
            border: 1px solid #808080;
            text-align: center;
            font-size: 8px;
            line-height: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 1px 3px;
            vertical-align: middle;
        }

        .field-grid td {
            min-height: 14px;
        }

        .label {
            width: 78px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .label-tight {
            width: 48px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .box {
            min-height: 11px;
            border: 1px solid #b7b7b7;
            background: #fbfbfb;
            padding: 1px 4px;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .box-nowrap {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
        }

        .box-center {
            text-align: center;
        }

        .box-right {
            text-align: right;
        }

        .top-meta {
            margin-top: 2px;
        }

        .switches {
            text-align: right;
            white-space: nowrap;
        }

        .switch {
            display: inline-block;
            width: 24px;
            height: 12px;
            margin: 0 3px -3px 8px;
            border: 1px solid #f0a000;
            border-radius: 3px;
        }

        .switch-fill {
            background-color: #111;
        }

        .triple-head {
            margin-top: 6px;
        }

        .date-inline {
            width: 100%;
            border-collapse: collapse;
        }

        .date-inline td {
            padding: 0 2px;
            border: 0;
        }

        .date-label {
            width: 46px;
            text-align: right;
            font-size: 7px;
            white-space: nowrap;
        }

        .date-box {
            min-height: 12px;
            line-height: 10px;
        }

        .mini-head {
            text-align: center;
            font-size: 7px;
            font-weight: 900;
            font-style: italic;
            text-transform: uppercase;
        }

        .mini-rule {
            display: inline-block;
            width: 23px;
            height: 2px;
            margin: 0 7px 1px 0;
            background: #ffc000;
        }

        .contract-option {
            display: inline-block;
            margin-right: 12px;
            white-space: nowrap;
        }

        .contract-percent {
            display: inline-block;
            min-width: 34px;
            height: 12px;
            margin-left: 4px;
            padding: 0 4px;
            border: 1px solid #ffc000;
            text-align: center;
            line-height: 10px;
            font-weight: 900;
        }

        .payment {
            margin-top: 5px;
        }

        .payment th,
        .payment td {
            border: 1px solid #b7b7b7;
            height: 13px;
            font-size: 7px;
        }

        .payment th {
            background: #f2f2f2;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
        }

        .payment-total th,
        .payment-total td {
            background: #d9d9d9;
            font-weight: 900;
        }

        .currency {
            width: 18px;
            text-align: center;
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .notes {
            margin: 5px 0 5px;
            font-size: 7px;
            line-height: 1.22;
            text-align: justify;
        }

        .items th,
        .items td {
            border: 1px solid #b7b7b7;
        }

        .items th {
            height: 13px;
            background: #f2f2f2;
            text-align: center;
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .items .item-body td {
            min-height: 18px;
            vertical-align: middle;
        }

        .desc-cell {
            vertical-align: middle !important;
            font-weight: 900;
            overflow: hidden;
        }

        .desc-cell p {
            margin: 0 0 3px;
        }

        .desc-cell figure {
            max-width: 100%;
            margin: 3px 0;
            page-break-inside: avoid;
        }

        .desc-cell img {
            display: block;
            width: auto !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: 190px;
            margin: 3px auto;
            object-fit: contain;
        }

        .red-note {
            margin-top: 6px;
            color: #ff0000;
            text-align: center;
            font-weight: 900;
        }

        .total-label {
            border: 0 !important;
            text-align: right;
            font-weight: 900;
            height: 15px;
        }

        .total-value {
            background: #d9d9d9;
            font-weight: 900;
        }

        .discount-label {
            color: #ff0000;
        }

        .global-total {
            height: 15px;
            margin-top: 2px;
            border: 1px solid #b7b7b7;
            background: #ffc000;
            text-align: center;
            font-weight: 900;
            line-height: 13px;
        }

        .general {
            margin-top: 10px;
            font-size: 7px;
            line-height: 1.24;
        }

        .general-title {
            margin-bottom: 8px;
            font-weight: 900;
        }

        .general p {
            margin: 0 0 4px;
        }

        .attachment-summary {
            margin-top: 8px;
            border: 1px solid #808080;
            background: #f2f2f2;
            padding: 4px 6px;
            text-align: center;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .strong {
            font-weight: 900;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>
@php
    $fmtMoney = fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
    $fmtPercent = fn (mixed $value): string => number_format((float) $value, 2, ',', '.').'%';
    $fmtDate = fn (mixed $value): string => $value instanceof \Carbon\CarbonInterface ? $value->format('d/m/Y') : (filled($value) ? (string) $value : '-');
    $dash = fn (mixed $value): string => filled($value) ? (string) $value : '-';
    $enderecoExecucao = $obra?->endereco ?: $projeto?->endereco;
    $enderecoCobranca = $projeto?->endereco;
    $cep = $projeto?->cep ?: '-';
    $gestorNome = $gestor?->name;
    $gestorTelefone = $gestor?->phone ?: '-';
    $gestorEmail = $gestor?->email ?: '-';
    $gerenciadora = $projeto?->pmo_nome ?: ($projeto?->respPmo?->name ?? '-');
    $itemPrincipal = $itemPrincipal ?? $itens->first();
    $itensDescricaoServico = collect($itensDescricaoServicoPdf ?? [])->values();
    $parcelas = collect($parcelamento)->values();
    $dataInicioServico = $autorizacaoServico->data_inicio_servico;
    $dataTerminoServico = $autorizacaoServico->data_termino_servico;
    $dataEntregaMaterial = $autorizacaoServico->data_entrega_material;
    $quantidadeAnexosEmail = (int) ($quantidadeAnexosEmail ?? 0);
    $textoAnexosEmail = $quantidadeAnexosEmail === 1
        ? 'HÁ 1 ARQUIVO EM ANEXO ENVIADO NO E-MAIL EM CONJUNTO.'
        : "HÁ {$quantidadeAnexosEmail} ARQUIVOS EM ANEXO ENVIADOS NO E-MAIL EM CONJUNTO.";
@endphp
<body>
        <div class="sheet">
        <table class="brand-row">
            <tr>
                <td class="center">
                    <img class="brand-band" src="{{ public_path('images/logos-pdf-as.png') }}" alt="DPC">
                </td>
            </tr>
        </table>

        <div class="yellow-title">AUTORIZAÇÃO DE SERVIÇO</div>

        <table class="field-grid">
            <tr>
                <td class="label">NÚMERO AS:</td>
                <td style="width: 50%;"><div class="box box-nowrap">{{ $dash($autorizacaoServico->numero_as) }}</div></td>
                <td style="width: 82px;"><div class="box box-center box-nowrap">REV. {{ $revisao }}</div></td>
                <td style="width: 12px;"></td>
                <td class="label-tight right">DATA AS:</td>
                <td style="width: 120px;"><div class="box box-center box-nowrap">{{ now()->format('d/m/Y') }}</div></td>
            </tr>
            <tr>
                <td class="label">ELABORAÇÃO:</td>
                <td><div class="box">{{ $dash($autorizacaoServico->createdBy?->name) }}</div></td>
                <td></td>
                <td></td>
                <td
                    colspan="2"
                    class="switches"
                    data-escopo-shell="{{ $escopoShellSelecionado ? '1' : '0' }}"
                    data-escopo-recheio="{{ $escopoRecheioSelecionado ? '1' : '0' }}"
                >
                    <span class="switch @if($escopoShellSelecionado) switch-fill @endif"></span> SHELL
                    <span class="switch @if($escopoRecheioSelecionado) switch-fill @endif"></span> RECHEIO
                </td>
            </tr>
            <tr>
                <td class="label">UNIDADE:</td>
                <td colspan="2"><div class="box">{{ $dash($obra?->unidade) }}</div></td>
                <td colspan="3"></td>
            </tr>
        </table>

        <table class="field-grid top-meta">
            <tr>
                <td class="label">GESTOR OBRA:</td>
                <td><div class="box">{{ $dash($gestorNome) }}</div></td>
                <td class="label-tight">TEL. GESTOR:</td>
                <td><div class="box box-nowrap">{{ $gestorTelefone }}</div></td>
                <td class="label-tight">EMAIL:</td>
                <td><div class="box">{{ $gestorEmail }}</div></td>
            </tr>
            <tr>
                <td class="label">GERENCIADORA:</td>
                <td><div class="box">{{ $dash($gerenciadora) }}</div></td>
                <td class="label-tight">TEL. GERENC.:</td>
                <td><div class="box">-</div></td>
                <td class="label-tight">EMAIL:</td>
                <td><div class="box">-</div></td>
            </tr>
            <tr>
                <td class="label">CONTATO:</td>
                <td><div class="box">-</div></td>
                <td colspan="4"></td>
            </tr>
        </table>

        <div class="bar">DADOS DO FORNECEDOR</div>
        <table class="field-grid">
            <tr>
                <td class="label">RAZÃO SOCIAL:</td>
                <td colspan="3"><div class="box">{{ $dash($construtora?->nome) }}</div></td>
                <td class="label-tight">CNPJ:</td>
                <td><div class="box box-center box-nowrap">{{ $dash($construtora?->cnpj) }}</div></td>
                <td class="label-tight">I.E:</td>
                <td><div class="box box-center">{{ $dash($construtora?->inscricao_estadual) }}</div></td>
            </tr>
            <tr>
                <td class="label">ENDEREÇO:</td>
                <td colspan="3"><div class="box">{{ $dash($construtora?->endereco) }}</div></td>
                <td class="label-tight">CEP:</td>
                <td><div class="box box-center">{{ $dash($construtora?->cep) }}</div></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td class="label">RESPONSÁVEL:</td>
                <td><div class="box">{{ $dash($construtora?->responsavel) }}</div></td>
                <td class="label-tight">TEL. CONTATO:</td>
                <td><div class="box">{{ $dash($construtora?->telefone) }}</div></td>
                <td class="label-tight">EMAIL:</td>
                <td colspan="3"><div class="box">{{ $dash($construtora?->email) }}</div></td>
            </tr>
        </table>

        <div class="bar">DADOS PARA FATURAMENTO</div>
        <table class="field-grid">
            <tr>
                <td class="label">FATURAR PARA:</td>
                <td colspan="3"><div class="box">{{ $dash($projeto?->nome) }}</div></td>
                <td class="label-tight">CNPJ:</td>
                <td><div class="box box-center box-nowrap">{{ $dash($projeto?->cnpj ?: $projeto?->cnpj_provisorio) }}</div></td>
                <td class="label-tight">I.E:</td>
                <td><div class="box box-center">{{ $dash($projeto?->inscricao_estadual) }}</div></td>
            </tr>
            <tr>
                <td class="label">END. COBRANÇA:</td>
                <td colspan="3"><div class="box">{{ $dash($enderecoCobranca) }}</div></td>
                <td class="label-tight">CEP:</td>
                <td><div class="box box-center box-nowrap">{{ $cep }}</div></td>
                <td class="label-tight">TEL.:</td>
                <td><div class="box box-center">{{ $dash($projeto?->telefone) }}</div></td>
            </tr>
        </table>

        <div class="bar">DETALHAMENTO SERVIÇOS / CONDIÇÕES DE FATURAMENTO</div>
        <table class="field-grid">
            <tr>
                <td class="label">END. EXECUÇÃO:</td>
                <td colspan="5"><div class="box">{{ $dash($enderecoExecucao) }}</div></td>
                <td class="label-tight">CEP:</td>
                <td><div class="box box-center box-nowrap">{{ $cep }}</div></td>
            </tr>
            <tr>
                <td class="label">COMPLEMENTO:</td>
                <td colspan="3"><div class="box">{{ $dash($projeto?->complemento) }}</div></td>
                <td colspan="4"></td>
            </tr>
        </table>

        <table class="triple-head">
            <tr>
                <td style="width: 34%;"><span class="strong">CONTRATAÇÕES</span></td>
                <td class="mini-head" style="width: 33%;">EXECUÇÃO SERVIÇO</td>
                <td class="mini-head" style="width: 33%;">ENTREGA MATERIAL</td>
            </tr>
            <tr>
                <td>
                    <span class="contract-option">
                        MÃO DE OBRA
                        <span class="contract-percent">{{ $fmtPercent($percentualFaturamentoMaoObra) }}</span>
                    </span>
                    <span class="contract-option">
                        MATERIAL
                        <span class="contract-percent">{{ $fmtPercent($percentualFaturamentoMaterial) }}</span>
                    </span>
                </td>
                <td>
                    <table class="date-inline">
                        <tr>
                            <td class="date-label">DT. INÍCIO:</td>
                            <td><div class="box box-nowrap box-center date-box">{{ $fmtDate($dataInicioServico) }}</div></td>
                            <td class="date-label">DT. TÉRMINO:</td>
                            <td><div class="box box-nowrap box-center date-box">{{ $fmtDate($dataTerminoServico) }}</div></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="date-inline">
                        <tr>
                            <td class="date-label">DT. ENTREGA:</td>
                            <td><div class="box box-nowrap box-center date-box">{{ $fmtDate($dataEntregaMaterial) }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="strong" style="margin-top: 4px;">CONDIÇÕES DE PAGAMENTO:</div>
        <table class="payment">
            <thead>
                <tr>
                    <th style="width: 92px;">PARCELA</th>
                    <th style="width: 48px;">(%)</th>
                    <th class="currency"></th>
                    <th style="width: 86px;">VALOR</th>
                    <th>OBSERVAÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parcelas as $parcela)
                    <tr>
                        <td class="center">{{ $parcela['parcela'] }}</td>
                        <td class="center">{{ number_format((float) ($parcela['percentual'] ?? 0), 1, ',', '.') }}%</td>
                        <td class="currency">R$</td>
                        <td class="money">{{ (float) ($parcela['valor'] ?? 0) > 0 ? $fmtMoney($parcela['valor']) : '-' }}</td>
                        <td>{{ $dash($parcela['observacao'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">Parcela 01</td>
                        <td class="center">100,0%</td>
                        <td class="currency">R$</td>
                        <td class="money">{{ $fmtMoney($total) }}</td>
                        <td>&gt;&gt; FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) DPC</td>
                    </tr>
                @endforelse
                <tr class="payment-total">
                    <th>TOTAL GERAL</th>
                    <td class="center">100,0%</td>
                    <td class="currency">R$</td>
                    <td class="money">{{ $fmtMoney($total) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            Enviar as NF's para gestor DPC e para a gerenciadora da obra, impreterivelmente, entre os dias 1 a 18 para serviço para materiais.
            Faturamento direto somente através de NF de material para valores acima de R$ 10.000,00, não podendo ultrapassar 60% do valor de contrato
            e limitado a dez (10) notas fiscais, podendo ser faturadas dentro do período de obras, independente do percentual delimitado nas condições
            de pagamento, atendendo as regras do envio de notas.
            <br>
            O faturamento indireto, referente a 40% do contrato, deve ser distribuído dentro dos percentuais descritos na condição de pagamento.
            Os pagamentos são programados para no mínimo 30 dias após o recebimento da NF e certificação de que o material/serviço foi entregue ou concluído.
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>DESCRIÇÃO</th>
                    <th style="width: 76px;">VLR. TOTAL (R$)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensDescricaoServico as $descricaoItem)
                    @php
                        $descricaoLinha = (string) ($descricaoItem['descricao'] ?? '');
                        $descricaoLinhaTemHtml = $descricaoLinha !== '' && str_contains($descricaoLinha, '<');
                        $imagensDescricao = array_values(array_filter((array) ($descricaoItem['descricao_imagens'] ?? [])));
                    @endphp
                    <tr class="item-body">
                        <td class="desc-cell">
                            @if($descricaoLinhaTemHtml)
                                {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($descricaoLinha)
                                    ->fileAttachmentsDisk((string) config('filesystems.media_disk', 'r2'))
                                    ->fileAttachmentsVisibility('public')
                                    ->toHtml() !!}
                            @elseif(filled($descricaoLinha))
                                {{ mb_strtoupper($descricaoLinha) }}
                            @endif
                            @foreach($imagensDescricao as $imagemDescricao)
                                <div>
                                    <img src="{{ $imagemDescricao }}" alt="Descrição do serviço">
                                </div>
                            @endforeach
                        </td>
                        <td class="money strong">{{ $fmtMoney($loop->first ? $subtotal : 0) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="total-label">SUBTOTAL</td>
                    <td class="money total-value">{{ $fmtMoney($subtotal) }}</td>
                </tr>
                <tr>
                    <td class="total-label discount-label">DESCONTO</td>
                    <td class="money total-value discount-label">{{ $fmtMoney($desconto) }}</td>
                </tr>
                <tr>
                    <td class="total-label">TOTAL GERAL</td>
                    <td class="money total-value">{{ $fmtMoney($total) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="global-total">{{ $totalPorExtenso }}</div>

        <div class="general">
            <div class="general-title">Condições Gerais – Contratação</div>
            <p><span class="strong">1. Cadastro Nacional de Obras (CNO)</span><br>
                O registro da obra no CNO (Cadastro Nacional de Obras) é de responsabilidade exclusiva do fornecedor. Cabe à mesma providenciar o devido cadastro, atualizações e regularizações perante a Receita Federal.</p>
            <p><span class="strong">2. Retenção de INSS (11%)</span><br>
                Será aplicada a retenção de 11% de INSS sobre os valores de serviços, conforme legislação vigente. O fornecedor deverá considerar esse desconto nas suas emissões e programações financeiras.</p>
            <p><span class="strong">3. Faturamento Direto – Materiais</span><br>
                Será permitido o faturamento direto de até 60% do valor total contratado para compra de materiais. O valor mínimo por nota fiscal deve ser de R$ 10.000,00. Está limitado a dez notas por obra.</p>
            <p><span class="strong">4. Dedução de Materiais</span><br>
                Notas fiscais de serviços com dedução de materiais somente serão aceitas mediante apresentação das notas fiscais de compra dos respectivos materiais.</p>
        </div>

        @if($quantidadeAnexosEmail > 0)
            <div class="attachment-summary">{{ $textoAnexosEmail }}</div>
        @endif
    </div>
</body>
</html>
