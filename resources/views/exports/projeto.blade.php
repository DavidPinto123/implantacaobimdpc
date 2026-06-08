<table style="border-collapse:collapse;width:100%;font-family:Arial, sans-serif;font-size:13px;">

    @php
        $colunas = [
            '#',
            'CÓDIGO',
            'SIGLA',
            'NOVA SIGLA',
            'CRON REVISADO',
            'UNIDADE',
            'MARCA',
            'ESCOPO',
            'PIPE/LAND',
            'STATUS',
            'COMERCIAL',
            'ARQUITETURA',
            'ENGENHARIA',
            'STATUS COMITÊ(04/08/25)',
            'STATUS IMÓVEL',
            'INÍCIO DO PROJETO',
            'STATUS CONTRATO',
            'DATA ASSINATURA CONTRATO',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PLANEJADO(15 D)',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PLANEJADO(05 D)',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'PLANEJADO BRIEFING',
            'PLANEJ. LAYOUT. INÍCIO',
            'PLANEJ. LAYOUT. FIM',
            'PLANEJADO (07 D)',
            'REALIZADO BRIEFING',
            'REALIZADO LAYOUT INÍCIO',
            'REALIZADO. LAYOUT. FIM',
            'PRAZO',
            'STATUS',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PLANEJADO (05 D)',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'DATA APROVAÇÃO',
            'STATUS APROVAÇÃO',
            'PLANEJ. REUNIÃO DE START',
            'REALIZADO REUNIÃO DE START',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PLANEJADO(30/45 D)',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'REUNIÃO DE KICKOFF',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PLANEJADO(20 D)',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'STATUS CP/EVTL CONSULTA PRÉVIA',
            'DOCUMENTAÇÃO POSSE',
            'PLANEJ. INÍCIO',
            'PLANEJ. FIM',
            'PRAZO LEGAL',
            'REALIZADO INÍCIO',
            'REALIZADO FIM',
            'PRAZO',
            'STATUS',
            'DATA POSSE',
            'MÊS POSSE',
            'ENGENHARIA',
            'LEGALIZAÇÃO',
            'STATUS',
            'COMENTÁRIOS',
            'INÍCIO',
            'FIM',
            'PRAZO PLANEJADO',
            'PRAZO REALIZADO',
            'INÍCIO',
            'FIM',
            'PRAZO PLANEJADO',
            'PRAZO REALIZADO',
            'MÊS',
            'ANO',
            'TIPO DE IMÓVEL',
            'ENDEREÇO',
            'CIDADE',
            'UF',
            'EMPREENDIMENTO',
            'LOCAÇÃO',
            'ALUGUEL',
            'OBS. ALUGUEL',
            'CARÊNCIA CONTRATO MESES',
            'MULTA CONTRATO MESES',
            'M² CONTRATO',
            'M² LAYOUT ÚTIL',
            'PAVIMENTO',
            'ESTACIONAMENTO(QTD)',
            'CAPEX APROVADO DIRETORIA (R$)',
            'CAPEX APROVADO DIRETORIA',
            'COC APROVADO (%)',
            'ESTIMATIVA DE ALUNOS',
            'TIER',
            'RENDA',
            'SET EQUIPAMENTOS',
            'PRÉ-VENDA MKT',
            'PRÉ-VENDA MKT REALIZADO',
            'DIRETORIA',
            'OBS. REUNIÃO ITA',
            'CONTATO DO CORRETOR/PP',
        ];

    @endphp


    <tr height="80">
        <th colspan="{{ count($colunas) }}" valign="middle"
            style="background:#000000;
                color:#ffffff;
                font-weight:bold;
                font-size:16px;
                ">
            PLANEJAMENTO ESTRATÉGICO
        </th>
    </tr>



    <!-- LINHA AMARELO CLARO -->
    <tr>
        <th colspan="6"></th>
        <th colspan="4" valign="middle"
            style="background:#f5d66b;color:#000000;;text-align:center;padding:8px;height:40px;">
            STATUS DO PROCESSO
        </th>
        <th valign="middle" colspan="4" style="color:#000000;text-align:center;padding:8px;">
            SQUAD
        </th>
        <th valign="middle" colspan="4" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            COMERCIAL
        </th>
        <th valign="middle" colspan="7" style="color:#000000;text-align:center;padding:8px;">
            CADASTRAL
        </th>
        <th valign="middle" colspan="7" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            VISITA TÉCNICA
        </th>
        <th valign="middle" colspan="9" style="color:#000000;text-align:center;padding:8px;">
            BRIEFING + LAYOUT
        </th>
        <th valign="middle" colspan="9" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            ORDEM DE INVESTIMENTO
        </th>
        <th valign="middle" colspan="9" style="color:#000000;text-align:center;padding:8px;">
            PROJETO EXECUTIVO
        </th>
        <th valign="middle" colspan="8" style="background:#f5d66b;color:#000000;text-align:center;padding:8p   x;">
            ORÇAMENTO E CONTRATAÇÕES
        </th>
        <th valign="middle" colspan="9" style="color:#000000;text-align:center;padding:8px;">
            LEGALIZAÇÃO
        </th>
        <th valign="middle" colspan="6" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            POSSE
        </th>
        <th valign="middle" colspan="4" style="color:#000000;text-align:center;padding:8px;">
            EXECUÇÃO DE OBRAS
        </th>
        <th valign="middle" colspan="6" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            IMPLANTAÇÃO
        </th>
        <th valign="middle" colspan="14" style="color:#000000;text-align:center;padding:8px;">
            DADOS DO IMÓVEL
        </th>
        <th valign="middle" colspan="6" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            PLANEJAMENTO ESTRATÉGICO
        </th>
        <th valign="middle" colspan="3" style="color:#000000;text-align:center;padding:8px;">
            OPERAÇÃO
        </th>
        <th valign="middle" colspan="3" style="background:#f5d66b;color:#000000;text-align:center;padding:8px;">
            DIRETORIA
        </th>
    </tr>

    <tr>
        @foreach ($colunas as $coluna)
            <th valign="middle"
                style="background:#FFC000;color:#000;width:120px;height:50px;text-align:center;
               border:1px solid #ddd; white-space: normal; word-wrap: break-word;">
                {!! str_replace(' ', "\n", $coluna) !!}
            </th>
        @endforeach

    </tr>

    {{-- @dd($projeto); --}}

    <!-- Linha de dados -->
    <tr valign="middle" style="background:#ffffff; text-indent:10px;">
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ 1 }}</td>
        <td valign="middle"
            style="border:1px solid #fff;word-wrap:break-word;font-weight:bold;white-space:normal;text-align:center">
            {{ $projeto->codigo }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->sigla }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->nova_sigla }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->crono_revisado ?? 'não informado' }}</td>
        <td valign="middle"
            style="border:1px solid #fff;word-wrap:break-word;font-weight:bold;white-space:normal;text-align:center">
            {{ $projeto->nome }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->marca }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->escopo }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->pipeline }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->status }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $user = App\Models\User::find($projeto->resp_com)->name ?? 'sem nome' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $user = App\Models\User::find($projeto->resp_arq)->name ?? 'sem nome' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $user = App\Models\User::find($projeto->resp_eng)->name ?? 'sem nome' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->status_comite }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->status_imovel }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->prazo_inicio ? \Carbon\Carbon::parse($projeto->prazo_inicio)->format('d/m/Y') : '' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->status_contrato }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->data_ass_contrato ? \Carbon\Carbon::parse($projeto->data_ass_contrato)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_plan_inicio ? \Carbon\Carbon::parse($projeto->cad_plan_inicio)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_plan_fim ? \Carbon\Carbon::parse($projeto->cad_plan_fim)->format('d/m/Y') : '' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_plan_dias ? $projeto->cad_plan_dias . ' Dias' : 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_rea_inicio ? \Carbon\Carbon::parse($projeto->cad_rea_inicio)->format('d/m/Y') : '' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_rea_fim ? \Carbon\Carbon::parse($projeto->cad_rea_fim)->format('d/m/Y') : '' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_prazo }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->cad_status }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_plan_inicio ? \Carbon\Carbon::parse($projeto->vis_plan_inicio)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_plan_fim ? \Carbon\Carbon::parse($projeto->vis_plan_fim)->format('d/m/Y') : '' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_plan_dias ? $projeto->vis_plan_dias . ' Dias' : 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_rea_inicio ? \Carbon\Carbon::parse($projeto->vis_rea_inicio)->format('d/m/Y') : '' }}</td>


        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_rea_fim ? \Carbon\Carbon::parse($projeto->vis_rea_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_prazo ? $projeto->vis_prazo . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vis_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_plan ? \Carbon\Carbon::parse($projeto->brief_plan)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_plan_lay_inicio ? \Carbon\Carbon::parse($projeto->brief_plan_lay_inicio)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_plan_lay_fim ? \Carbon\Carbon::parse($projeto->brief_plan_lay_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_plan_dias ? $projeto->brief_plan_dias . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_real ? \Carbon\Carbon::parse($projeto->brief_real)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_real_lay_inicio ? \Carbon\Carbon::parse($projeto->brief_real_lay_inicio)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_real_lay_fim ? \Carbon\Carbon::parse($projeto->brief_real_lay_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_prazo }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->brief_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_planej_ini ? \Carbon\Carbon::parse($projeto->ordem_planej_ini)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_planej_fim ? \Carbon\Carbon::parse($projeto->ordem_planej_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_planejado ? $projeto->ordem_planejado . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_realizado ? \Carbon\Carbon::parse($projeto->ordem_realizado)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_realizado_fim ? \Carbon\Carbon::parse($projeto->ordem_realizado_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_prazo }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_data_aprov ? \Carbon\Carbon::parse($projeto->ordem_data_aprov)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->ordem_status_aprov }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_planej_reuniao_start ? \Carbon\Carbon::parse($projeto->proj_planej_reuniao_start)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_real_reuniao_start ? \Carbon\Carbon::parse($projeto->proj_real_reuniao_start)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_plan_ini ? \Carbon\Carbon::parse($projeto->proj_plan_ini)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_plan_fim ? \Carbon\Carbon::parse($projeto->proj_plan_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_plan ? $projeto->proj_plan . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_real_ini ? \Carbon\Carbon::parse($projeto->proj_real_ini)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_real_fim ? \Carbon\Carbon::parse($projeto->proj_real_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_prazo ? $projeto->proj_prazo . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->proj_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_reuniao_kickoff ? \Carbon\Carbon::parse($projeto->orca_reuniao_kickoff)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_planejado_ini ? \Carbon\Carbon::parse($projeto->orca_planejado_ini)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_planejado_fim ? \Carbon\Carbon::parse($projeto->orca_planejado_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_planejado ? $projeto->orca_planejado . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_real_ini ? \Carbon\Carbon::parse($projeto->orca_real_ini)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_real_fim ? \Carbon\Carbon::parse($projeto->orca_real_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_prazo ? $projeto->orca_prazo . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->orca_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_status_consulta_prev }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_doc_posse }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_plan_ini ? \Carbon\Carbon::parse($projeto->legal_plan_ini)->format('d/m/Y') : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_plan_fim ? \Carbon\Carbon::parse($projeto->legal_plan_fim)->format('d/m/Y') : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_prazo_legal ? $projeto->legal_prazo_legal . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_realizado_ini ? \Carbon\Carbon::parse($projeto->legal_realizado_ini)->format('d/m/Y') : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_realizado_fim ? \Carbon\Carbon::parse($projeto->legal_realizado_fim)->format('d/m/Y') : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_prazo ? $projeto->legal_prazo . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->legal_status ?? 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->data_posse ? \Carbon\Carbon::parse($projeto->data_posse)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->mes_posse ? \Carbon\Carbon::createFromFormat('m', $projeto->mes_posse)->translatedFormat('F') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->posse_engenharia }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->posse_legalizacao }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->posse_status }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {!! nl2br(e($projeto->posse_comentarios ?? 'N/A')) !!}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->inicio_obra ? \Carbon\Carbon::parse($projeto->inicio_obra)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->entrega_obra ? \Carbon\Carbon::parse($projeto->entrega_obra)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->exec_prazo_plan ? $projeto->exec_prazo_plan . ' Dias' : 'Não informado' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->exec_prazo_real ? $projeto->exec_prazo_real . ' Dias' : 'Não informado' }}

        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_inicio ? \Carbon\Carbon::parse($projeto->imp_inicio)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_fim ? \Carbon\Carbon::parse($projeto->imp_fim)->format('d/m/Y') : '' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_prazo_planejado ? $projeto->imp_prazo_planejado . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_prazo_realizado ? $projeto->imp_prazo_realizado . ' Dias' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_mes ? \Carbon\Carbon::createFromFormat('m', $projeto->imp_mes)->translatedFormat('F') : '' }}

        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->imp_ano }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->tipo_imovel }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->endereco }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ App\Models\Cidade::find($projeto->cidade_id)->nome ?? 'não informado' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ App\Models\Estado::find($projeto->estado_id)->nome ?? 'não informado' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->empreendimento }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->locacao }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->aluguel_cto }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->obs_aluguel }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->carencia ? $projeto->carencia . ' Meses' : 'N/A' }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->multa_contrato }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->metro_contrato }}
        </td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->metro_layout_util }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->pavimento }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->n_vagas_livres }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->capex_aprovado_diretoria_valor }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->capex_aprovado_diretoria == 1 ? 'SIM' : 'Não' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->coc_aprovado ? $projeto->coc_aprovado . ' %' : 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->potencial_alunos ?? 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->tier ?? 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->renda ?? 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->set_equipamentos }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vendas_mkt ? \Carbon\Carbon::parse($projeto->vendas_mkt)->format('d/m/Y') : 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->vendas_mkt_realizado ?? 'N/A' }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->diretoria }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->reuniao_ita }}</td>
        <td valign="middle" style="border:1px solid #fff;word-wrap:break-word;white-space:normal;text-align:center">
            {{ $projeto->contato_corretor }}</td>


</table>
