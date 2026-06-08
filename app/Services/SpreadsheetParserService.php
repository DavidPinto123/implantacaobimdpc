<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SpreadsheetParserService
{
    private array $dateColumns = [
        'entrada_ponto',
        'data_assinatura_contrato',
        'data_envio_relatorio_fotografico',
        'data_atualizacao_comentario',
        'inicio_real',
        'inauguracao',
        'previsao_ligacao_energia',
        'data_check_list',
        'status_data_posse',
        'inicio',
        'fim',
        'inicio_imp',
        'fim_imp',
        'inicio_prev_pendencias',
        'termino_prev_pendencias',
        'data_solicitacao_vt',
        'data_agendamento_vt',
        // Cadastral
        'cad_plan_inicio',
        'cad_plan_fim',
        'cad_rea_inicio',
        'cad_rea_fim',
        // Visita Técnica
        'vis_plan_inicio',
        'vis_plan_fim',
        'vis_rea_inicio',
        'vis_rea_fim',
        // Briefing + Layout
        'brief_plan',
        'brief_plan_lay_inicio',
        'brief_plan_lay_fim',
        'brief_real',
        'brief_real_lay_inicio',
        'brief_real_lay_fim',
        // Ordem de Investimento
        'ordem_planej_ini',
        'ordem_planej_fim',
        'ordem_realizado',
        'ordem_realizado_fim',
        'ordem_data_aprov',
        // Projeto Executivo
        'proj_planej_reuniao_start',
        'proj_real_reuniao_start',
        'proj_plan_ini',
        'proj_plan_fim',
        'proj_real_ini',
        'proj_real_fim',
        // Orçamentos e Contratações
        'orca_reuniao_kickoff',
        'orca_planejado_ini',
        'orca_planejado_fim',
        'orca_real_ini',
        'orca_real_fim',
        // Legalização
        'legal_plan_ini',
        'legal_plan_fim',
        'legal_realizado_ini',
        'legal_realizado_fim',
        // Posse
        'data_posse',
        'vendas_mkt',
    ];

    private array $intColumns = [
        'constructin_project_id',
        'cad_plan_dias',
        'cad_prazo',
        'vis_plan_dias',
        'vis_prazo',
        'brief_plan_dias',
        'brief_prazo',
        'ordem_planejado',
        'ordem_prazo',
        'proj_plan',
        'proj_prazo',
        'orca_planejado',
        'orca_prazo',
        'legal_prazo_legal',
        'legal_prazo',
        'mes_posse',
        'carencia_contrato_meses',
        'multa_contrato_meses',
        'estacionamento_qtd',
        'estimativa_alunos',
        'vendas_mkt_realizado',
        'potencial_alunos',
        'mes',
        'ano',
    ];

    private array $floatColumns = [
        'percentual_obra',
        'percentual_obra_executado',
        'aluguel',
        'metro_contrato',
        'metro_layout_util',
        'capex_aprovado_diretoria_valor',
        'aluguel_cto',
    ];

    private array $booleanColumns = [
        'capex_aprovado_diretoria',
        'coc_aprovado',
        'projeto_croqui',
        'relocation',
        'imovel_pronto',
    ];

    private array $computedFields = [
        'desvio',
        'dias_para_inauguracao',
        'entrada_ponto_ate_inauguracao',
        'assinatura_ate_inauguracao',
        'dias_obra_inicio_pmo',
        'prazo_planejado',
        'prazo_realizado',
        'imp_prazo_planej',
        'imp_prazo_realiz',
    ];

    private array $enumColumns = [
        'status' => [
            '1. INAUGURADA' => 'Inaugurada',
            '2. OBRAS' => 'Obras',
            '3. EM PROCESSO' => 'Em processo',
            '4. CANCELADA' => 'Cancelada',
            '5. STAND-BY' => 'Stand-by',
            '5. DELETAR COMERCIAL' => 'Deletar comercial',
            'INAUGURADA' => 'Inaugurada',
            'OBRAS' => 'Obras',
            'EM PROCESSO' => 'Em processo',
            'CANCELADA' => 'Cancelada',
            'STAND-BY' => 'Stand-by',
        ],
        'status_contrato' => [
            'ASSINADO' => 'ASSINADO',
            'Assinado' => 'ASSINADO',
            'EM ASSINATURA' => 'EM ASSINATURA',
            'Em Assinatura' => 'EM ASSINATURA',
            'MINUTA' => 'MINUTA',
            'Minuta' => 'MINUTA',
            'NEGOCIAÇÃO' => 'NEGOCIAÇÃO',
            'Negociação' => 'NEGOCIAÇÃO',
            'NEGOCIAÇAO' => 'NEGOCIAÇÃO',
        ],
        'marca' => [
            'SMART FIT' => 'SMART FIT',
            'BIO RITMO' => 'BIO RITMO',
            'NATION' => 'NATION',
        ],
        'relatorio_fotografico' => [
            'ENVIADO' => 'enviado',
            'Enviado' => 'enviado',
            'ENVIADO COM PENDÊNCIAS' => 'pendencias',
            'Enviado com Pendências' => 'pendencias',
            'NÃO ENVIADO' => 'nao_enviado',
            'Não Enviado' => 'nao_enviado',
        ],
        'termo_de_posse' => [
            'SIM' => 'sim',
            'Sim' => 'sim',
            'NÃO' => 'nao',
            'Não' => 'nao',
        ],
        'cronograma_implantacao' => [
            'ENVIADO' => 'enviado',
            'Enviado' => 'enviado',
            'NÃO ENVIADO' => 'nao_enviado',
            'Não Enviado' => 'nao_enviado',
        ],
        'cronograma_visi' => [
            'ENVIADO' => 'enviado',
            'Enviado' => 'enviado',
            'NÃO ENVIADO' => 'nao_enviado',
            'Não Enviado' => 'nao_enviado',
        ],
        'email_solicitacao_cl' => [
            'ENVIADO' => 'enviado',
            'Enviado' => 'enviado',
            'NÃO ENVIADO' => 'nao_enviado',
            'Não Enviado' => 'nao_enviado',
        ],
        'envio_qrcod' => [
            'ENVIADO' => 'enviado',
            'Enviado' => 'enviado',
            'NÃO ENVIADO' => 'nao_enviado',
            'Não Enviado' => 'nao_enviado',
        ],
        'camera_unidade' => [
            'SIM' => 'sim',
            'Sim' => 'sim',
            'sim' => 'sim',
            'NÃO' => 'nao',
            'Não' => 'nao',
            'não' => 'nao',
            'NAO' => 'nao',
        ],
        'homologados_em_atraso' => [
            'SIM' => 'sim',
            'Sim' => 'sim',
            'NÃO' => 'nao',
            'Não' => 'nao',
        ],
        'checklist_manutencao' => [
            'CONCLUÍDO' => 'concluido',
            'Concluído' => 'concluido',
            'CONCLUIDO' => 'concluido',
            'EM ANDAMENTO' => 'em_andamento',
            'Em andamento' => 'em_andamento',
            'EM ATRASO' => 'em_atraso',
            'Em atraso' => 'em_atraso',
            'NÃO INICIADO' => 'nao_iniciado',
            'Não iniciado' => 'nao_iniciado',
        ],
        'locacao' => [
            'MULTIUSUÁRIO' => 'Multiusuário',
            'Multiusuário' => 'Multiusuário',
            'MONOUSUÁRIO' => 'Mono usuário',
            'Monousuário' => 'Mono usuário',
            'Mono usuário' => 'Mono usuário',
        ],
        'vendas_mkt_realizado' => [
            'NÃO' => '',
            'Não' => '',
            'NAO' => '',
        ],
        'energia' => [
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
            'Ligada / Rateio' => 'Ligada / Rateio',
            'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
            'Pendente, responsavel PP' => 'Pendente, responsavel PP',
            'GERADOR' => 'GERADOR',
        ],
        'agua' => [
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
            'Ligada / Rateio' => 'Ligada / Rateio',
            'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
            'Pendente, responsavel PP' => 'Pendente, responsavel PP',
        ],
        'gas' => [
            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
            'Ligada, necessário trocar titularidade' => 'Ligada, necessário trocar titularidade',
            'Ligada / Rateio' => 'Ligada / Rateio',
            'Pendente, responsavel Smart' => 'Pendente, responsavel Smart',
            'Pendente, responsavel PP' => 'Pendente, responsavel PP',
            'Boiler Instalado provisório' => 'Boiler Instalado provisório',
        ],
        'status_visita' => [
            'CONCLUÍDO' => 'CONCLUÍDO',
            'Concluído' => 'CONCLUÍDO',
            'EM ANDAMENTO' => 'EM ANDAMENTO',
            'Em Andamento' => 'EM ANDAMENTO',
            'N/A' => 'N/A',
            'NÃO INICIADO' => 'NÃO INICIADO',
            'Não Iniciado' => 'NÃO INICIADO',
            'AGENDADO' => 'AGENDADO',
            'Agendado' => 'AGENDADO',
            'PARALISADO' => 'PARALISADO',
            'Paralisado' => 'PARALISADO',
            'PENDÊNCIAS' => 'PENDÊNCIAS',
            'Pendências' => 'PENDÊNCIAS',
            'PENDÊNCIA COM' => 'PENDÊNCIAS',
            'PENDÊNCIA' => 'PENDÊNCIAS',
            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
            'Não Solicitado' => 'NÃO SOLICITADO',
            'SOLICITADO' => 'SOLICITADO',
            'Solicitado' => 'SOLICITADO',
        ],
        'status_proj_exec' => [
            'CONCLUÍDO' => 'CONCLUÍDO',
            'Concluído' => 'CONCLUÍDO',
            'EM ANDAMENTO' => 'EM ANDAMENTO',
            'Em Andamento' => 'EM ANDAMENTO',
            'N/A' => 'N/A',
            'NÃO INICIADO' => 'NÃO INICIADO',
            'Não Iniciado' => 'NÃO INICIADO',
            'AGENDADO' => 'AGENDADO',
            'Agendado' => 'AGENDADO',
            'PARALISADO' => 'PARALISADO',
            'Paralisado' => 'PARALISADO',
            'PENDÊNCIAS' => 'PENDÊNCIAS',
            'Pendências' => 'PENDÊNCIAS',
            'PENDÊNCIA COM' => 'PENDÊNCIAS',
            'PENDÊNCIA' => 'PENDÊNCIAS',
            'ATRASADO' => 'ATRASADO',
            'NÃO SOLICITADO' => 'NÃO SOLICITADO',
            'Não Solicitado' => 'NÃO SOLICITADO',
            'SOLICITADO' => 'SOLICITADO',
            'Solicitado' => 'SOLICITADO',
        ],
    ];

    private array $headerMap = [
        'codigo' => 'codigo',
        'código' => 'codigo',
        'cod' => 'codigo',
        'sigla' => 'sigla',
        'nova sigla' => 'nova_sigla',
        'unidade' => 'unidade',
        'marca' => 'marca',
        'status' => 'status',
        'pipe land' => 'pipe_land',
        'pipe   land' => 'pipe_land',
        'pipeline' => 'pipe_land',
        'civil' => 'civil',
        'hidraulica' => 'hidraulica',
        'hidráulica' => 'hidraulica',
        'eletrica' => 'eletrica',
        'elétrica' => 'eletrica',
        'incendio' => 'incendio',
        'incêndio' => 'incendio',
        'ar condicionado' => 'instalacao_ar_condicionado',
        'instalacao ar condicionado' => 'instalacao_ar_condicionado',
        'maquinas ar condicionado' => 'maquinas_ar_condicionado',
        'maquinas ar' => 'maquinas_ar_condicionado',
        'homologados em atraso' => 'homologados_em_atraso',
        'relatorio fotografico' => 'relatorio_fotografico',
        'relatorio fotografico' => 'relatorio_fotografico',
        'termo de posse' => 'termo_de_posse',
        'data de posse' => 'status_data_posse',
        'data posse' => 'status_data_posse',
        'comentarios' => 'comentarios',
        'comentários' => 'comentarios',
        'cronograma implantacao' => 'cronograma_implantacao',
        'cronograma implantação' => 'cronograma_implantacao',
        'dias para inauguracao' => 'dias_para_inauguracao',
        'dias para inauguração' => 'dias_para_inauguracao',
        'percentual obra' => 'percentual_obra',
        '% obra' => 'percentual_obra',
        'percentual executado' => 'percentual_obra_executado',
        '% executado' => 'percentual_obra_executado',
        'percentual obra executado' => 'percentual_obra_executado',
        'desvio' => 'desvio',
        'cronograma visi' => 'cronograma_visi',
        'ponto atencao' => 'ponto_atencao',
        'ponto atenção' => 'ponto_atencao',
        'ponto de atencao' => 'ponto_atencao',
        'energia' => 'energia',
        'agua' => 'agua',
        'água' => 'agua',
        'gas' => 'gas',
        'gás' => 'gas',
        'comentario' => 'comentario',
        'email solicitacao cl' => 'email_solicitacao_cl',
        'envio qrcod' => 'envio_qrcod',
        'checklist manutencao' => 'checklist_manutencao',
        'checklist manutenção' => 'checklist_manutencao',
        'inicio prev pendencias' => 'inicio_prev_pendencias',
        'termino prev pendencias' => 'termino_prev_pendencias',
        'comentarios adicionais' => 'comentarios_adicionais',
        'status visita' => 'status_visita',
        'status proj exec' => 'status_proj_exec',
        'engenharia' => 'engenharia',
        'comercial' => 'comercial',
        'status data posse' => 'status_data_posse',
        'inicio' => 'inicio',
        'início' => 'inicio',
        'fim' => 'fim',
        'prazo planejado' => 'prazo_planejado',
        'prazo realizado' => 'prazo_realizado',
        'inicio imp' => 'inicio_imp',
        'início imp' => 'inicio_imp',
        'fim imp' => 'fim_imp',
        'imp prazo planej' => 'imp_prazo_planej',
        'imp prazo realiz' => 'imp_prazo_realiz',
        'tipo imovel' => 'tipo_imovel',
        'tipo imóvel' => 'tipo_imovel',
        'endereco' => 'endereco',
        'endereço' => 'endereco',
        'cidade' => 'cidade',
        'uf' => 'uf',
        'empreendimento' => 'empreendimento',
        'locacao' => 'locacao',
        'locação' => 'locacao',
        'contato corretor' => 'contato_corretor',
        'inauguracao' => 'inauguracao',
        'inauguração' => 'inauguracao',
        'entrada ponto' => 'entrada_ponto',
        'entrada do ponto' => 'entrada_ponto',
        'status contrato' => 'status_contrato',
        'data assinatura contrato' => 'data_assinatura_contrato',
        'data de assinatura contrato' => 'data_assinatura_contrato',
        'entrada ponto ate inauguracao' => 'entrada_ponto_ate_inauguracao',
        'entrada do ponto ate inauguracao' => 'entrada_ponto_ate_inauguracao',
        'assinatura ate inauguracao' => 'assinatura_ate_inauguracao',
        'data envio relatorio fotografico' => 'data_envio_relatorio_fotografico',
        'data de envio do relatorio fotografico' => 'data_envio_relatorio_fotografico',
        'data atualizacao comentario' => 'data_atualizacao_comentario',
        'data de atualizacao do comentario' => 'data_atualizacao_comentario',
        'inicio real' => 'inicio_real',
        'data de solicitacao da vt' => 'data_solicitacao_vt',
        'data de agendamento da vt' => 'data_agendamento_vt',
        'observacao implantacao' => 'observacao_implantacao',
        'dias obra inicio pmo' => 'dias_obra_inicio_pmo',
        'itens criticos' => 'itens_criticos',
        'itens críticos' => 'itens_criticos',
        'descricao itens criticos' => 'descricao_itens_criticos',
        'camera unidade' => 'camera_unidade',
        'previsao ligacao energia' => 'previsao_ligacao_energia',
        'gerador contratual' => 'gerador_contratual',
        'data check list' => 'data_check_list',
        'elevador' => 'elevador',
        'gestor pos obra' => 'gestor_pos_obra',
        'observacao' => 'observacao',
        'link' => 'link',
        'arquitetura' => 'arquitetura',
        'mes' => 'mes',
        'ano' => 'ano',
        '% de obra previsto' => 'percentual_obra',
        '% de obra executado' => 'percentual_obra_executado',
        'set equipamentos' => 'set_equipamentos',
        'set de equipamentos' => 'set_equipamentos',
        'piso' => 'piso',
        'alteracao no spa / addons' => 'alteracao_spa_addons',
        'alteracao no spa' => 'alteracao_spa_addons',
        'alteracao spa addons' => 'alteracao_spa_addons',
        // Headers prefixados (grupo > nome) para colunas duplicadas
        'visita tecnica > status' => 'status_visita',
        'visita tecnica > data de solicitacao da vt' => 'data_solicitacao_vt',
        'visita tecnica > data de agendamento da vt' => 'data_agendamento_vt',
        'projeto executivo > status' => 'status_proj_exec',
        'execucao de obras > inicio' => 'inicio',
        'execucao de obras > inicio real' => 'inicio_real',
        'execucao de obras > fim' => 'fim',
        'execucao de obras > prazo planejado' => 'prazo_planejado',
        'execucao de obras > prazo realizado' => 'prazo_realizado',
        'implantacao > inicio' => 'inicio_imp',
        'implantacao > prazo planejado' => 'imp_prazo_planej',
        'implantacao > prazo realizado' => 'imp_prazo_realiz',
        'implantacao > cronograma de implantacao' => 'cronograma_implantacao',
        'implantacao > observacao' => 'observacao_implantacao',
        'contas de consumo > agua' => 'agua',
        'contas de consumo > gas' => 'gas',
        'contas de consumo > previsao de ligacao energia' => 'previsao_ligacao_energia',
        'contas de consumo > previsao ligacao energia' => 'previsao_ligacao_energia',
        'contas de consumo > gerador contratual' => 'gerador_contratual',
        'contas de consumo > comentario' => 'comentarios',
        '% de obra > % de obra previsto' => 'percentual_obra',
        '% de obra > % de obra executado' => 'percentual_obra_executado',
        '% de obra > dias para inauguracao' => 'dias_para_inauguracao',
        '% de obra > dias de obra (inicio pmo)' => 'dias_obra_inicio_pmo',
        'pos obra > email solicitacao de cl' => 'email_solicitacao_cl',
        'pos obra > envio de qrcod' => 'envio_qrcod',
        'pos obra > checklist de manutencao (trilogo)' => 'checklist_manutencao',
        'pos obra > data do check list' => 'data_check_list',
        'pos obra > inicio previsto pendencias' => 'inicio_prev_pendencias',
        'pos obra > termino previsto pendencias' => 'termino_prev_pendencias',
        'pos obra > elevador' => 'elevador',
        'pos obra > comentarios' => 'comentarios_adicionais',
        'pos obra > gestor pos obra' => 'gestor_pos_obra',
        'dados do imovel > cidade' => 'cidade',
        'dados do imovel > uf' => 'uf',
        'dados do imovel > empreendimento' => 'empreendimento',
        'cronograma visi > cronograma visi' => 'cronograma_visi',
        'contratacoes > civil' => 'civil',
        'contratacoes > energia' => 'energia',
    ];

    public function getSheetNames(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        return $spreadsheet->getSheetNames();
    }

    public function detectHeaderRow(string $filePath, string|int $sheet = 0): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return 1;
        }

        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $maxRows = min($worksheet->getHighestRow(), 10);

        $bestRow = 1;
        $bestCount = 0;

        for ($row = 1; $row <= $maxRows; $row++) {
            $filledCount = 0;
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && trim((string) $value) !== '') {
                    $filledCount++;
                }
            }
            if ($filledCount > $bestCount) {
                $bestCount = $filledCount;
                $bestRow = $row;
            }
        }

        return $bestRow;
    }

    public function analyzeSheet(string $filePath, string|int $sheet = 0, int $previewLimit = 5): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return ['headerRow' => 1, 'headers' => [], 'preview' => [], 'columnMap' => []];
        }

        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $maxRowsDetect = min($worksheet->getHighestRow(), 10);

        // 1) Detectar headerRow (linha com mais colunas preenchidas)
        $headerRow = 1;
        $bestCount = 0;
        for ($row = 1; $row <= $maxRowsDetect; $row++) {
            $filledCount = 0;
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && trim((string) $value) !== '') {
                    $filledCount++;
                }
            }
            if ($filledCount > $bestCount) {
                $bestCount = $filledCount;
                $headerRow = $row;
            }
        }

        // 2) Ler linha de agrupamento (linha anterior ao header)
        $groupRow = $headerRow > 1 ? $headerRow - 1 : 0;
        $groups = [];
        if ($groupRow >= 1) {
            $lastGroup = '';
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $groupRow)->getValue();
                if ($value !== null && trim((string) $value) !== '') {
                    $lastGroup = trim((string) $value);
                }
                $groups[$col] = $lastGroup;
            }
        }

        // 3) Extrair headers com indice de coluna real
        $rawHeaders = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $worksheet->getCellByColumnAndRow($col, $headerRow)->getValue();
            if ($value !== null && trim((string) $value) !== '') {
                $rawHeaders[$col] = trim((string) $value);
            }
        }

        // 4) Detectar duplicados e prefixar com grupo
        $nameCounts = array_count_values($rawHeaders);
        $headers = [];
        $columnMap = [];

        foreach ($rawHeaders as $col => $name) {
            $displayName = $name;
            if ($nameCounts[$name] > 1 && ! empty($groups[$col])) {
                $displayName = $groups[$col].' > '.$name;
            }

            $original = $displayName;
            $suffix = 2;
            while (isset($columnMap[$displayName])) {
                $displayName = $original." ({$suffix})";
                $suffix++;
            }

            $headers[] = $displayName;
            $columnMap[$displayName] = $col;
        }

        // 5) Detectar coluna-chave (CODIGO) para filtrar rows de template
        $codigoCol = null;
        foreach ($headers as $h) {
            if (mb_strtolower(trim(explode(' > ', $h)[0])) === 'codigo' || mb_strtolower(trim($h)) === 'codigo') {
                $codigoCol = $columnMap[$h];
                break;
            }
        }

        // 6) Preview usando columnMap
        $preview = [];
        $dataStartRow = $headerRow + 1;
        $previewScanned = 0;
        for ($row = $dataStartRow; $row <= $dataStartRow + $previewLimit + 10 && count($preview) < $previewLimit; $row++) {
            if ($codigoCol) {
                $codigoVal = $worksheet->getCellByColumnAndRow($codigoCol, $row)->getValue();
                if ($codigoVal === null || trim((string) $codigoVal) === '') {
                    continue;
                }
            }
            $rowData = [];
            $hasData = false;
            foreach ($headers as $header) {
                $col = $columnMap[$header];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $rowData[$header] = $value !== null ? trim((string) $value) : '';
                if ($value !== null && $value !== '') {
                    $hasData = true;
                }
            }
            if ($hasData) {
                $preview[] = $rowData;
            }
        }

        // 7) Amostra de valores unicos por coluna (para preview no mapeamento)
        $sampleValues = [];
        $sampleScanLimit = min($worksheet->getHighestRow(), $dataStartRow + 30);
        foreach ($headers as $header) {
            $sampleValues[$header] = [];
        }

        for ($row = $dataStartRow; $row <= $sampleScanLimit; $row++) {
            if ($codigoCol) {
                $codigoVal = $worksheet->getCellByColumnAndRow($codigoCol, $row)->getValue();
                if ($codigoVal === null || trim((string) $codigoVal) === '') {
                    continue;
                }
            }
            foreach ($headers as $header) {
                if (count($sampleValues[$header]) >= 3) {
                    continue;
                }
                $col = $columnMap[$header];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                if ($value !== null && trim((string) $value) !== '') {
                    $strVal = trim((string) $value);
                    if (! in_array($strVal, $sampleValues[$header])) {
                        $sampleValues[$header][] = $strVal;
                    }
                }
            }
        }

        return [
            'headerRow' => $headerRow,
            'headers' => $headers,
            'preview' => $preview,
            'sampleValues' => $sampleValues,
            'columnMap' => $columnMap,
        ];
    }

    public function getHeaders(string $filePath, string|int $sheet = 0, ?int $headerRow = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return [];
        }

        $headerRow = $headerRow ?? $this->detectHeaderRow($filePath, $sheet);

        $headers = [];
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $worksheet->getCellByColumnAndRow($col, $headerRow)->getValue();
            if ($value !== null && trim((string) $value) !== '') {
                $headers[] = trim((string) $value);
            }
        }

        return $headers;
    }

    public function getPreview(string $filePath, string|int $sheet = 0, int $limit = 5, ?int $headerRow = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return [];
        }

        $headerRow = $headerRow ?? $this->detectHeaderRow($filePath, $sheet);
        $headers = $this->getHeaders($filePath, $sheet, $headerRow);
        $rows = [];
        $highestColumnIndex = count($headers);
        $dataStartRow = $headerRow + 1;

        for ($row = $dataStartRow; $row < $dataStartRow + $limit; $row++) {
            $rowData = [];
            $hasData = false;

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $header = $headers[$col - 1] ?? "col_{$col}";
                $rowData[$header] = $value !== null ? trim((string) $value) : '';
                if ($value !== null && $value !== '') {
                    $hasData = true;
                }
            }

            if ($hasData) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    private array $headerMapPE = [
        'codigo' => 'codigo',
        'código' => 'codigo',
        'sigla' => 'sigla',
        'nova sigla' => 'nova_sigla',
        'unidade' => 'unidade',
        'marca' => 'marca',
        'status' => 'status',
        'escopo' => 'pipe_land',
        'pmo' => 'pmo_nome',
        'squad' => 'pipe_land',
        'pipe land' => 'pipe_land',
        'pipe   land' => 'pipe_land',
        'uf' => 'uf',
        'cidade' => 'cidade',
        'endereco' => 'endereco',
        'endereço' => 'endereco',
        'empreendimento' => 'empreendimento',
        'tipo imovel' => 'tipo_imovel',
        'tipo imóvel' => 'tipo_imovel',
        'tipo de imovel' => 'tipo_imovel',
        'locacao' => 'locacao',
        'locação' => 'locacao',
        'contato corretor' => 'contato_corretor',
        'contato do corretor pp' => 'contato_corretor',
        'contato do corretor' => 'contato_corretor',
        'status contrato' => 'status_contrato',
        'inauguracao' => 'inauguracao',
        'inauguração' => 'inauguracao',
        'posse' => 'entrada_ponto',
        'data posse' => 'entrada_ponto',
        'data de posse' => 'entrada_ponto',
        'entrada ponto' => 'entrada_ponto',
        'entrada do ponto' => 'entrada_ponto',
        'inicio do projeto' => 'entrada_ponto',
        'início do projeto' => 'entrada_ponto',
        'data assinatura contrato' => 'data_assinatura_contrato',
        'data de assinatura contrato' => 'data_assinatura_contrato',
        'data de assinatura' => 'data_assinatura_contrato',
        'inicio obra' => 'inicio',
        'inicio obras' => 'inicio',
        'início obras' => 'inicio',
        'inicio' => 'inicio',
        'início' => 'inicio',
        'fim obra' => 'fim',
        'fim' => 'fim',
        'inicio real' => 'inicio_real',
        'inicio imp' => 'inicio_imp',
        'início imp' => 'inicio_imp',
        'fim imp' => 'fim_imp',
        'engenharia' => 'engenharia',
        'comercial' => 'comercial',
        'arquitetura' => 'arquitetura',
        'observacao' => 'observacao',
        'link' => 'link',
        'mes' => 'mes',
        'ano' => 'ano',
        'set equipamentos' => 'set_equipamentos',
        'set de equipamentos' => 'set_equipamentos',
        'previsao de ligacao de energia' => 'previsao_ligacao_energia',
        'previsao de ligacao energia' => 'previsao_ligacao_energia',
        'previsao ligacao energia' => 'previsao_ligacao_energia',
        'gerador contratual' => 'gerador_contratual',
        'aluguel' => 'aluguel',
        'obs. aluguel' => 'obs_aluguel',
        'obs aluguel' => 'obs_aluguel',
        'pavimento' => 'pavimento',
        'tier' => 'tier',
        'renda' => 'renda',
        'diretoria' => 'dir_status_contrato',
        'pre-venda mkt' => 'vendas_mkt',
        'pré-venda mkt' => 'vendas_mkt',
        'pre-venda mkt realizado' => 'vendas_mkt_realizado',
        'pré-venda mkt realizado' => 'vendas_mkt_realizado',
        'reuniao ita' => 'reuniao_ita',
        'obs. reuniao ita' => 'reuniao_ita',
        // Headers prefixados (grupo > nome) para colunas duplicadas
        'status do processo > status' => 'status',
        'squad > comercial' => 'comercial',
        'squad > arquitetura' => 'arquitetura',
        'squad > engenharia' => 'engenharia',
        'comercial > status imovel' => 'status_imovel',
        'comercial > status imóvel' => 'status_imovel',
        'comercial > inicio do projeto' => 'entrada_ponto',
        'comercial > início do projeto' => 'entrada_ponto',
        'comercial > status contrato' => 'status_contrato',
        'comercial > data assinatura contrato' => 'data_assinatura_contrato',
        'comercial > status' => 'status_contrato',
        // Cadastral
        'cadastral > planej. inicio' => 'cad_plan_inicio',
        'cadastral > planej. início' => 'cad_plan_inicio',
        'cadastral > planej inicio' => 'cad_plan_inicio',
        'cadastral > planej. fim' => 'cad_plan_fim',
        'cadastral > planej fim' => 'cad_plan_fim',
        'cadastral > planejado' => 'cad_plan_dias',
        'cadastral > planejado(15 d)' => 'cad_plan_dias',
        'cadastral > realizado inicio' => 'cad_rea_inicio',
        'cadastral > realizado início' => 'cad_rea_inicio',
        'cadastral > realizado fim' => 'cad_rea_fim',
        'cadastral > prazo' => 'cad_prazo',
        'cadastral > status' => 'cad_status',
        // Visita Técnica
        'visita tecnica > planej. inicio' => 'vis_plan_inicio',
        'visita técnica > planej. inicio' => 'vis_plan_inicio',
        'visita tecnica > planej. início' => 'vis_plan_inicio',
        'visita técnica > planej. início' => 'vis_plan_inicio',
        'visita tecnica > planej inicio' => 'vis_plan_inicio',
        'visita técnica > planej inicio' => 'vis_plan_inicio',
        'visita tecnica > planej. fim' => 'vis_plan_fim',
        'visita técnica > planej. fim' => 'vis_plan_fim',
        'visita tecnica > planej fim' => 'vis_plan_fim',
        'visita técnica > planej fim' => 'vis_plan_fim',
        'visita tecnica > planejado' => 'vis_plan_dias',
        'visita técnica > planejado' => 'vis_plan_dias',
        'visita tecnica > planejado(05 d)' => 'vis_plan_dias',
        'visita técnica > planejado(05 d)' => 'vis_plan_dias',
        'visita tecnica > realizado inicio' => 'vis_rea_inicio',
        'visita técnica > realizado inicio' => 'vis_rea_inicio',
        'visita tecnica > realizado início' => 'vis_rea_inicio',
        'visita técnica > realizado início' => 'vis_rea_inicio',
        'visita tecnica > realizado fim' => 'vis_rea_fim',
        'visita técnica > realizado fim' => 'vis_rea_fim',
        'visita tecnica > prazo' => 'vis_prazo',
        'visita técnica > prazo' => 'vis_prazo',
        'visita tecnica > status' => 'vis_status',
        'visita técnica > status' => 'vis_status',
        // Briefing + Layout
        'briefing + layout > planejado briefing' => 'brief_plan',
        'briefing + layout > planejado' => 'brief_plan',
        'briefing + layout > planej. layout. inicio' => 'brief_plan_lay_inicio',
        'briefing + layout > planej. layout. início' => 'brief_plan_lay_inicio',
        'briefing + layout > planej layout inicio' => 'brief_plan_lay_inicio',
        'briefing + layout > planej. layout. fim' => 'brief_plan_lay_fim',
        'briefing + layout > planej layout fim' => 'brief_plan_lay_fim',
        'briefing + layout > planejado (07 d)' => 'brief_plan_dias',
        'briefing + layout > realizado briefing' => 'brief_real',
        'briefing + layout > realizado' => 'brief_real',
        'briefing + layout > realizado layout inicio' => 'brief_real_lay_inicio',
        'briefing + layout > realizado layout início' => 'brief_real_lay_inicio',
        'briefing + layout > realizado. layout. fim' => 'brief_real_lay_fim',
        'briefing + layout > realizado layout fim' => 'brief_real_lay_fim',
        'briefing + layout > prazo' => 'brief_prazo',
        'briefing + layout > status' => 'brief_status',
        // Ordem de Investimento
        'ordem de investimento > planej. inicio' => 'ordem_planej_ini',
        'ordem de investimento > planej. início' => 'ordem_planej_ini',
        'ordem de investimento > planej inicio' => 'ordem_planej_ini',
        'ordem de investimento > planej. fim' => 'ordem_planej_fim',
        'ordem de investimento > planej fim' => 'ordem_planej_fim',
        'ordem de investimento > planejado' => 'ordem_planejado',
        'ordem de investimento > planejado (05 d)' => 'ordem_planejado',
        'ordem de investimento > realizado inicio' => 'ordem_realizado',
        'ordem de investimento > realizado início' => 'ordem_realizado',
        'ordem de investimento > realizado fim' => 'ordem_realizado_fim',
        'ordem de investimento > prazo' => 'ordem_prazo',
        'ordem de investimento > status' => 'ordem_status',
        'ordem de investimento > data aprovacao' => 'ordem_data_aprov',
        'ordem de investimento > data aprovação' => 'ordem_data_aprov',
        'ordem de investimento > status aprovacao' => 'ordem_status_aprov',
        'ordem de investimento > status aprovação' => 'ordem_status_aprov',
        // Projeto Executivo
        'projeto executivo > planej. reuniao de start' => 'proj_planej_reuniao_start',
        'projeto executivo > planej. reunião de start' => 'proj_planej_reuniao_start',
        'projeto executivo > planej reuniao de start' => 'proj_planej_reuniao_start',
        'projeto executivo > realizado reuniao de start' => 'proj_real_reuniao_start',
        'projeto executivo > realizado reunião de start' => 'proj_real_reuniao_start',
        'projeto executivo > planej. inicio' => 'proj_plan_ini',
        'projeto executivo > planej. início' => 'proj_plan_ini',
        'projeto executivo > planej inicio' => 'proj_plan_ini',
        'projeto executivo > planej. fim' => 'proj_plan_fim',
        'projeto executivo > planej fim' => 'proj_plan_fim',
        'projeto executivo > planejado' => 'proj_plan',
        'projeto executivo > planejado(30/45 d)' => 'proj_plan',
        'projeto executivo > realizado inicio' => 'proj_real_ini',
        'projeto executivo > realizado início' => 'proj_real_ini',
        'projeto executivo > realizado fim' => 'proj_real_fim',
        'projeto executivo > prazo' => 'proj_prazo',
        'projeto executivo > status' => 'proj_status',
        // Orçamentos e Contratações
        'orcamento e contratacoes > reuniao de kickoff' => 'orca_reuniao_kickoff',
        'orcamento e contratacoes > reunião de kickoff' => 'orca_reuniao_kickoff',
        'orcamentos e contratacoes > reuniao de kickoff' => 'orca_reuniao_kickoff',
        'orcamentos e contratacoes > reunião de kickoff' => 'orca_reuniao_kickoff',
        'orcamento e contratacoes > planej. inicio' => 'orca_planejado_ini',
        'orcamento e contratacoes > planej. início' => 'orca_planejado_ini',
        'orcamentos e contratacoes > planej. inicio' => 'orca_planejado_ini',
        'orcamentos e contratacoes > planej. início' => 'orca_planejado_ini',
        'orcamento e contratacoes > planej. fim' => 'orca_planejado_fim',
        'orcamentos e contratacoes > planej. fim' => 'orca_planejado_fim',
        'orcamento e contratacoes > planejado' => 'orca_planejado',
        'orcamentos e contratacoes > planejado' => 'orca_planejado',
        'orcamento e contratacoes > planejado(20 d)' => 'orca_planejado',
        'orcamentos e contratacoes > planejado(20 d)' => 'orca_planejado',
        'orcamento e contratacoes > realizado inicio' => 'orca_real_ini',
        'orcamento e contratacoes > realizado início' => 'orca_real_ini',
        'orcamentos e contratacoes > realizado inicio' => 'orca_real_ini',
        'orcamentos e contratacoes > realizado início' => 'orca_real_ini',
        'orcamento e contratacoes > realizado fim' => 'orca_real_fim',
        'orcamentos e contratacoes > realizado fim' => 'orca_real_fim',
        'orcamento e contratacoes > prazo' => 'orca_prazo',
        'orcamentos e contratacoes > prazo' => 'orca_prazo',
        'orcamento e contratacoes > status' => 'orca_status',
        'orcamentos e contratacoes > status' => 'orca_status',
        // Legalização
        'legalizacao > status cp/evtl consulta previa' => 'legal_status_consulta_prev',
        'legalizacao > status cp/evtl consulta prévia' => 'legal_status_consulta_prev',
        'legalizacao > documentacao posse' => 'legal_doc_posse',
        'legalizacao > documentação posse' => 'legal_doc_posse',
        'legalizacao > planej. inicio' => 'legal_plan_ini',
        'legalizacao > planej. início' => 'legal_plan_ini',
        'legalizacao > planej inicio' => 'legal_plan_ini',
        'legalizacao > planej. fim' => 'legal_plan_fim',
        'legalizacao > planej fim' => 'legal_plan_fim',
        'legalizacao > prazo legal' => 'legal_prazo_legal',
        'legalizacao > realizado inicio' => 'legal_realizado_ini',
        'legalizacao > realizado início' => 'legal_realizado_ini',
        'legalizacao > realizado fim' => 'legal_realizado_fim',
        'legalizacao > prazo' => 'legal_prazo',
        'legalizacao > status' => 'legal_status',
        // Posse
        'posse > data de posse' => 'data_posse',
        'posse > data posse' => 'data_posse',
        'posse > data' => 'data_posse',
        'posse > mes posse' => 'mes_posse',
        'posse > mês posse' => 'mes_posse',
        'posse > mes' => 'mes_posse',
        'posse > posse' => 'data_posse',
        'posse > engenharia' => 'posse_engenharia',
        'posse > legalizacao' => 'posse_legalizacao',
        'posse > legalização' => 'posse_legalizacao',
        'posse > status' => 'posse_status',
        'posse > comentarios' => 'posse_comentarios',
        'posse > comentários' => 'posse_comentarios',
        'execucao de obras > inicio' => 'inicio',
        'execucao de obras > inicio obras' => 'inicio',
        'execucao de obras > início obras' => 'inicio',
        'execucao de obras > inicio real' => 'inicio_real',
        'execucao de obras > fim' => 'fim',
        'execucao de obras > prazo planejado' => 'prazo_planejado',
        'execucao de obras > prazo realizado' => 'prazo_realizado',
        'implantacao > inicio' => 'inicio_imp',
        'implantacao > início' => 'inicio_imp',
        'implantacao > inauguracao' => 'inauguracao',
        'implantacao > inauguração' => 'inauguracao',
        'implantacao > fim' => 'fim_imp',
        'implantacao > prazo planejado' => 'imp_prazo_planej',
        'implantacao > prazo realizado' => 'imp_prazo_realiz',
        'implantacao > mes' => 'mes',
        'implantacao > ano' => 'ano',
        'dados do imovel > tipo de imovel' => 'tipo_imovel',
        'dados do imovel > tipo de imóvel' => 'tipo_imovel',
        'dados do imovel > endereco' => 'endereco',
        'dados do imovel > endereço' => 'endereco',
        'dados do imovel > cidade' => 'cidade',
        'dados do imovel > uf' => 'uf',
        'dados do imovel > empreendimento' => 'empreendimento',
        'dados do imovel > locacao' => 'locacao',
        'dados do imovel > locação' => 'locacao',
        'dados do imovel > aluguel' => 'aluguel',
        'dados do imovel > obs. aluguel' => 'obs_aluguel',
        'dados do imovel > obs aluguel' => 'obs_aluguel',
        'dados do imovel > carencia contrato meses' => 'carencia_contrato_meses',
        'dados do imovel > carência contrato meses' => 'carencia_contrato_meses',
        'dados do imovel > multa contrato meses' => 'multa_contrato_meses',
        'dados do imovel > m² contrato' => 'metro_contrato',
        'dados do imovel > m2 contrato' => 'metro_contrato',
        'dados do imovel > m² layout util' => 'metro_layout_util',
        'dados do imovel > m² layout útil' => 'metro_layout_util',
        'dados do imovel > m2 layout util' => 'metro_layout_util',
        'dados do imovel > pavimento' => 'pavimento',
        'dados do imovel > estacionamento(qtd)' => 'estacionamento_qtd',
        'dados do imovel > estacionamento' => 'estacionamento_qtd',
        'dados do imovel > vagas exclusivas/compartilhadas' => 'vagas_estacionamento',
        'dados do imovel > vagas exclusívas / compartilhadas' => 'vagas_estacionamento',
        'vagas exclusivas/compartilhadas' => 'vagas_estacionamento',
        'vagas exclusivas / compartilhadas' => 'vagas_estacionamento',
        'vagas exclusívas / compartilhadas' => 'vagas_estacionamento',
        'dados do imovel > capex aprovado diretoria (r$)' => 'capex_aprovado_diretoria_valor',
        'dados do imovel > capex aprovado diretoria' => 'capex_aprovado_diretoria',
        'dados do imovel > coc aprovado (%)' => 'coc_aprovado',
        'dados do imovel > coc aprovado' => 'coc_aprovado',
        'dados do imovel > estimativa de alunos' => 'estimativa_alunos',
        'dados do imovel > tier' => 'tier',
        'dados do imovel > renda' => 'renda',
        'operacao > set equipamentos' => 'set_equipamentos',
        'operacao > set de equipamentos' => 'set_equipamentos',
        'planejamento estrategico > pre-venda mkt' => 'vendas_mkt',
        'planejamento estrategico > pré-venda mkt' => 'vendas_mkt',
        'planejamento estrategico > pre-venda mkt realizado' => 'vendas_mkt_realizado',
        'planejamento estrategico > pré-venda mkt realizado' => 'vendas_mkt_realizado',
        'planejamento estrategico > diretoria' => 'dir_status_contrato',
        'planejamento estrategico > obs. reuniao ita' => 'reuniao_ita',
        'planejamento estrategico > obs reuniao ita' => 'reuniao_ita',
        'planejamento estrategico > inauguracao' => 'inauguracao',
        'planejamento estrategico > inauguração' => 'inauguracao',
        'diretoria > diretoria' => 'dir_status_contrato',
        'diretoria > obs.' => 'obs_diretoria',
        'diretoria > obs' => 'obs_diretoria',
        'obs.' => 'obs_diretoria',
        'diretoria > contato do corretor pp' => 'contato_corretor',
        'diretoria > contato do corretor' => 'contato_corretor',
        'contas de consumo > energia' => 'energia',
        'contas de consumo > previsao de ligacao de energia' => 'previsao_ligacao_energia',
        'contas de consumo > previsao de ligacao energia' => 'previsao_ligacao_energia',
        'contas de consumo > gerador contratual' => 'gerador_contratual',
        // Entradas sem prefixo de grupo (fallback quando detecção de grupo falha)
        'status comite' => 'status_comite',
        'status comitê' => 'status_comite',
        'status imovel' => 'status_imovel',
        'status imóvel' => 'status_imovel',
        'planejado briefing' => 'brief_plan',
        'realizado briefing' => 'brief_real',
        'planej. layout inicio' => 'brief_plan_lay_inicio',
        'planej. layout início' => 'brief_plan_lay_inicio',
        'planej. layout. inicio' => 'brief_plan_lay_inicio',
        'planej. layout. início' => 'brief_plan_lay_inicio',
        'planej. layout fim' => 'brief_plan_lay_fim',
        'planej. layout. fim' => 'brief_plan_lay_fim',
        'realizado layout inicio' => 'brief_real_lay_inicio',
        'realizado layout início' => 'brief_real_lay_inicio',
        'realizado layout fim' => 'brief_real_lay_fim',
        'realizado. layout. fim' => 'brief_real_lay_fim',
        'planej. reuniao de start' => 'proj_planej_reuniao_start',
        'planej. reunião de start' => 'proj_planej_reuniao_start',
        'planej reuniao de start' => 'proj_planej_reuniao_start',
        'realizado reuniao de start' => 'proj_real_reuniao_start',
        'realizado reunião de start' => 'proj_real_reuniao_start',
        'reuniao de kickoff' => 'orca_reuniao_kickoff',
        'reunião de kickoff' => 'orca_reuniao_kickoff',
        'data aprovacao' => 'ordem_data_aprov',
        'data aprovação' => 'ordem_data_aprov',
        'status aprovacao' => 'ordem_status_aprov',
        'status aprovação' => 'ordem_status_aprov',
        'status cp/evtl consulta previa' => 'legal_status_consulta_prev',
        'status cp/evtl consulta prévia' => 'legal_status_consulta_prev',
        'documentacao posse' => 'legal_doc_posse',
        'documentação posse' => 'legal_doc_posse',
        'prazo legal' => 'legal_prazo_legal',
        'm² contrato' => 'metro_contrato',
        'm2 contrato' => 'metro_contrato',
        'm² layout util' => 'metro_layout_util',
        'm² layout útil' => 'metro_layout_util',
        'm2 layout util' => 'metro_layout_util',
        'estacionamento(qtd)' => 'estacionamento_qtd',
        'capex aprovado diretoria (r$)' => 'capex_aprovado_diretoria_valor',
        'capex aprovado diretoria' => 'capex_aprovado_diretoria',
        'coc aprovado (%)' => 'coc_aprovado',
        'coc aprovado' => 'coc_aprovado',
        'estimativa de alunos' => 'estimativa_alunos',
        'reuniao ita (obs.)' => 'reuniao_ita',
        'reunião ita (obs.)' => 'reuniao_ita',
        'pedido pre vendas mkt' => 'vendas_mkt',
        'pedido pré vendas mkt' => 'vendas_mkt',
        'pre-vendas mkt realizado' => 'vendas_mkt_realizado',
        'pré-vendas mkt realizado' => 'vendas_mkt_realizado',
        'carencia contrato meses' => 'carencia_contrato_meses',
        'carência contrato meses' => 'carencia_contrato_meses',
        'multa contrato meses' => 'multa_contrato_meses',
        // PLANEJADO (N D) — desambiguado pelo número de dias
        'planejado (15 d)' => 'cad_plan_dias',
        'planejado(15 d)' => 'cad_plan_dias',
        'planejado (05 d)' => 'vis_plan_dias',
        'planejado(05 d)' => 'vis_plan_dias',
        'planejado (07 d)' => 'brief_plan_dias',
        'planejado(07 d)' => 'brief_plan_dias',
        'planejado (20 d)' => 'orca_planejado',
        'planejado(20 d)' => 'orca_planejado',
        'planejado (30/45 d)' => 'proj_plan',
        'planejado(30/45 d)' => 'proj_plan',
    ];

    private array $projetoFieldMap = [
        // Campos com nome diferente entre mapeamento e projetos
        'aluguel' => 'aluguel_cto',
        'carencia_contrato_meses' => 'carencia',
        'multa_contrato_meses' => 'multa_contrato',
        'estimativa_alunos' => 'potencial_alunos',
        'status_visita' => 'vis_status',
        'status_proj_exec' => 'proj_status',
        // Campos duplicados removidos de obras (agora só em projetos)
        'sigla' => 'sigla',
        'nova_sigla' => 'nova_sigla',
        'marca' => 'marca',
        'tipo_imovel' => 'tipo_imovel',
        'empreendimento' => 'empreendimento',
        'locacao' => 'locacao',
        'contato_corretor' => 'contato_corretor',
        'inauguracao' => 'inauguracao',
        'status_contrato' => 'status_contrato',
        // Status de fases
        'status_imovel' => 'status_imovel',
        'status_comite' => 'status_comite',
        'dir_status_contrato' => 'dir_status_contrato',
        // Fases PE (mesmo nome em projetos)
        'cad_plan_inicio' => 'cad_plan_inicio',
        'cad_plan_fim' => 'cad_plan_fim',
        'cad_plan_dias' => 'cad_plan_dias',
        'cad_rea_inicio' => 'cad_rea_inicio',
        'cad_rea_fim' => 'cad_rea_fim',
        'cad_prazo' => 'cad_prazo',
        'cad_status' => 'cad_status',
        'vis_plan_inicio' => 'vis_plan_inicio',
        'vis_plan_fim' => 'vis_plan_fim',
        'vis_plan_dias' => 'vis_plan_dias',
        'vis_rea_inicio' => 'vis_rea_inicio',
        'vis_rea_fim' => 'vis_rea_fim',
        'vis_prazo' => 'vis_prazo',
        'vis_status' => 'vis_status',
        'brief_plan' => 'brief_plan',
        'brief_plan_lay_inicio' => 'brief_plan_lay_inicio',
        'brief_plan_lay_fim' => 'brief_plan_lay_fim',
        'brief_plan_dias' => 'brief_plan_dias',
        'brief_real' => 'brief_real',
        'brief_real_lay_inicio' => 'brief_real_lay_inicio',
        'brief_real_lay_fim' => 'brief_real_lay_fim',
        'brief_prazo' => 'brief_prazo',
        'brief_status' => 'brief_status',
        'ordem_planej_ini' => 'ordem_planej_ini',
        'ordem_planej_fim' => 'ordem_planej_fim',
        'ordem_planejado' => 'ordem_planejado',
        'ordem_realizado' => 'ordem_realizado',
        'ordem_realizado_fim' => 'ordem_realizado_fim',
        'ordem_prazo' => 'ordem_prazo',
        'ordem_status' => 'ordem_status',
        'ordem_data_aprov' => 'ordem_data_aprov',
        'ordem_status_aprov' => 'ordem_status_aprov',
        'proj_planej_reuniao_start' => 'proj_planej_reuniao_start',
        'proj_real_reuniao_start' => 'proj_real_reuniao_start',
        'proj_plan_ini' => 'proj_plan_ini',
        'proj_plan_fim' => 'proj_plan_fim',
        'proj_plan' => 'proj_plan',
        'proj_real_ini' => 'proj_real_ini',
        'proj_real_fim' => 'proj_real_fim',
        'proj_prazo' => 'proj_prazo',
        'proj_status' => 'proj_status',
        'orca_reuniao_kickoff' => 'orca_reuniao_kickoff',
        'orca_planejado_ini' => 'orca_planejado_ini',
        'orca_planejado_fim' => 'orca_planejado_fim',
        'orca_planejado' => 'orca_planejado',
        'orca_real_ini' => 'orca_real_ini',
        'orca_real_fim' => 'orca_real_fim',
        'orca_prazo' => 'orca_prazo',
        'orca_status' => 'orca_status',
        'legal_status_consulta_prev' => 'legal_status_consulta_prev',
        'legal_doc_posse' => 'legal_doc_posse',
        'legal_plan_ini' => 'legal_plan_ini',
        'legal_plan_fim' => 'legal_plan_fim',
        'legal_prazo_legal' => 'legal_prazo_legal',
        'legal_realizado_ini' => 'legal_realizado_ini',
        'legal_realizado_fim' => 'legal_realizado_fim',
        'legal_prazo' => 'legal_prazo',
        'legal_status' => 'legal_status',
        'data_posse' => 'data_posse',
        'mes_posse' => 'mes_posse',
        'posse_engenharia' => 'posse_engenharia',
        'posse_legalizacao' => 'posse_legalizacao',
        'posse_status' => 'posse_status',
        'posse_comentarios' => 'posse_comentarios',
        // Dados do imóvel / financeiro
        'obs_aluguel' => 'obs_aluguel',
        'metro_contrato' => 'metro_contrato',
        'metro_layout_util' => 'metro_layout_util',
        'pavimento' => 'pavimento',
        'estacionamento_qtd' => 'estacionamento_qtd',
        'vagas_estacionamento' => 'vagas_estacionamento',
        'capex_aprovado_diretoria_valor' => 'capex_aprovado_diretoria_valor',
        'capex_aprovado_diretoria' => 'capex_aprovado_diretoria',
        'coc_aprovado' => 'coc_aprovado',
        'tier' => 'tier',
        'renda' => 'renda',
        'set_equipamentos' => 'set_equipamentos',
        'vendas_mkt' => 'vendas_mkt',
        'vendas_mkt_realizado' => 'vendas_mkt_realizado',
        'pmo_nome' => 'pmo_nome',
        'obs_diretoria' => 'obs_diretoria',
        'reuniao_ita' => 'reuniao_ita',
    ];

    public function splitRowData(array $row): array
    {
        $obra = [];
        $projeto = [];

        foreach ($row as $key => $value) {
            if (isset($this->projetoFieldMap[$key])) {
                $projeto[$this->projetoFieldMap[$key]] = $value;
            } else {
                $obra[$key] = $value;
            }
        }

        return ['obra' => $obra, 'projeto' => $projeto];
    }

    public function getProjetoFieldMap(): array
    {
        return $this->projetoFieldMap;
    }

    public function suggestMapping(array $headers, string $tipoPlanilha = 'engenharia'): array
    {
        $mapping = [];
        $tableColumns = array_unique(array_merge(
            Schema::getColumnListing('obras'),
            array_keys($this->projetoFieldMap),
        ));
        $rawMap = $tipoPlanilha === 'planejamento_estrategico'
            ? array_merge($this->headerMap, $this->headerMapPE)
            : $this->headerMap;

        $headerMapToUse = [];
        foreach ($rawMap as $key => $value) {
            $headerMapToUse[$this->normalizeHeader($key)] = $value;
        }

        foreach ($headers as $header) {
            $normalized = $this->normalizeHeader($header);
            $matched = null;

            if (isset($headerMapToUse[$normalized])) {
                $matched = $headerMapToUse[$normalized];
            }

            // Para headers prefixados (grupo > nome), verificar se o grupo tem mapeamentos
            $isUnmappedGroup = false;
            if (! $matched && str_contains($normalized, ' > ')) {
                $parts = explode(' > ', $normalized, 2);
                $groupName = trim($parts[0]);
                $baseName = trim($parts[1]);

                // Verificar se o grupo tem pelo menos um mapeamento explícito (grupo > ...)
                $groupHasMappings = false;
                foreach (array_keys($headerMapToUse) as $key) {
                    if (str_starts_with($key, $groupName.' > ')) {
                        $groupHasMappings = true;
                        break;
                    }
                }

                if ($groupHasMappings) {
                    // Grupo conhecido — tentar nome base
                    if (isset($headerMapToUse[$baseName])) {
                        $matched = $headerMapToUse[$baseName];
                    }
                } else {
                    // Grupo sem mapeamentos — não tentar fallbacks genéricos
                    $isUnmappedGroup = true;
                }
            }

            if (! $matched && ! $isUnmappedGroup) {
                $snaked = Str::snake($normalized);
                if (in_array($snaked, $tableColumns)) {
                    $matched = $snaked;
                }
            }

            if (! $matched && ! $isUnmappedGroup) {
                $bestMatch = null;
                $bestScore = 0;
                foreach ($tableColumns as $column) {
                    similar_text($normalized, $column, $percent);
                    if ($percent > $bestScore && $percent >= 70) {
                        $bestScore = $percent;
                        $bestMatch = $column;
                    }
                }
                $matched = $bestMatch;
            }

            if ($matched && in_array($matched, $this->computedFields)) {
                $mapping[$header] = '__calculado__';
            } else {
                $mapping[$header] = $matched ?? '';
            }
        }

        return $mapping;
    }

    public function getDateColumns(): array
    {
        return $this->dateColumns;
    }

    public function parseRows(string $filePath, string|int $sheet, array $mapping, ?int $headerRow = null, array $customValueMap = [], array $columnMap = []): Collection
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return collect();
        }

        $headerRow = $headerRow ?? $this->detectHeaderRow($filePath, $sheet);
        $highestRow = $worksheet->getHighestRow();
        $tableColumns = array_unique(array_merge(
            Schema::getColumnListing('obras'),
            array_keys($this->projetoFieldMap),
        ));
        $rows = [];
        $dataStartRow = $headerRow + 1;

        // Construir mapa header → coluna → campo (sem duplicatas)
        $colFieldMap = [];
        $usedFields = [];
        foreach ($mapping as $header => $dbColumn) {
            if (empty($dbColumn) || $dbColumn === '__calculado__' || ! in_array($dbColumn, $tableColumns) || in_array($dbColumn, $this->computedFields)) {
                continue;
            }
            if (isset($usedFields[$dbColumn])) {
                continue;
            }
            $col = $columnMap[$header] ?? null;
            if ($col) {
                $colFieldMap[$col] = $dbColumn;
                $usedFields[$dbColumn] = true;
            }
        }

        // Fallback: se columnMap vazio, usar indice sequencial (compatibilidade)
        if (empty($colFieldMap)) {
            $usedFields = [];
            $headers = $this->getHeaders($filePath, $sheet, $headerRow);
            foreach ($headers as $idx => $header) {
                if (! empty($mapping[$header]) && $mapping[$header] !== '__calculado__' && in_array($mapping[$header], $tableColumns) && ! in_array($mapping[$header], $this->computedFields)) {
                    if (isset($usedFields[$mapping[$header]])) {
                        continue;
                    }
                    $colFieldMap[$idx + 1] = $mapping[$header];
                    $usedFields[$mapping[$header]] = true;
                }
            }
        }

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $item = [];
            $hasData = false;

            foreach ($colFieldMap as $col => $dbColumn) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();

                if ($value !== null && $value !== '') {
                    $hasData = true;
                }

                $item[$dbColumn] = $this->castValue($dbColumn, $value, $customValueMap);
            }

            if ($hasData) {
                if (in_array('codigo', $colFieldMap) && empty($item['codigo'])) {
                    continue;
                }
                $rows[] = $item;
            }
        }

        return collect($rows);
    }

    public function getAvailableFields(string $tipoPlanilha = 'engenharia'): array
    {
        $excluded = ['id', 'created_at', 'updated_at', 'projeto_id', 'foto_perfil', 'foto_capa', 'fotos'];
        $camposMigrados = ['sigla', 'nova_sigla', 'marca', 'tipo_imovel', 'empreendimento',
            'locacao', 'contato_corretor', 'inauguracao', 'status_contrato'];
        $columns = array_unique(array_merge(Schema::getColumnListing('obras'), $camposMigrados));

        if ($tipoPlanilha === 'planejamento_estrategico') {
            $columns = array_unique(array_merge($columns, array_keys($this->projetoFieldMap)));
        }

        return array_values(array_diff($columns, $excluded, $this->computedFields));
    }

    public function getComputedFields(): array
    {
        return $this->computedFields;
    }

    public function getFieldLabels(): array
    {
        return [
            // Identificacao
            'codigo' => 'Codigo',
            'sigla' => 'Sigla',
            'nova_sigla' => 'Nova Sigla',
            'unidade' => 'Unidade',
            'marca' => 'Marca',
            'pipe_land' => 'Pipe / Land',
            'status' => 'Status',
            'pmo_nome' => 'PMO (Nome)',
            // Gestor / Squad
            'engenharia' => 'Gestor Engenharia',
            'comercial' => 'Gestor Comercial',
            'arquitetura' => 'Gestor Arquitetura',
            // Comercial
            'status_comite' => 'Status Comite',
            'status_imovel' => 'Status Imovel',
            'entrada_ponto' => 'Entrada do Ponto',
            'status_contrato' => 'Status Contrato',
            'data_assinatura_contrato' => 'Data Assinatura Contrato',
            // Total de Dias
            'entrada_ponto_ate_inauguracao' => 'Entrada Ponto ate Inauguracao',
            'assinatura_ate_inauguracao' => 'Assinatura ate Inauguracao',
            // Visita Tecnica (engenharia)
            'status_visita' => 'VT > Status',
            'data_solicitacao_vt' => 'VT > Data Solicitacao',
            'data_agendamento_vt' => 'VT > Data Agendamento',
            // Projeto Executivo (engenharia)
            'status_proj_exec' => 'Proj. Executivo > Status',
            // Posse (engenharia)
            'status_data_posse' => 'Data de Posse',
            'relatorio_fotografico' => 'Relatorio Fotografico',
            'data_envio_relatorio_fotografico' => 'Data Envio Rel. Fotografico',
            'data_atualizacao_comentario' => 'Data Atualizacao Comentario',
            'comentarios' => 'Comentarios',
            'termo_de_posse' => 'Termo de Posse',
            // Execucao de Obras
            'inicio' => 'Exec. Obras > Inicio',
            'inicio_real' => 'Exec. Obras > Inicio Real',
            'fim' => 'Exec. Obras > Fim',
            'prazo_planejado' => 'Exec. Obras > Prazo Planejado',
            'prazo_realizado' => 'Exec. Obras > Prazo Realizado',
            // Implantacao
            'inicio_imp' => 'Implantacao > Inicio',
            'fim_imp' => 'Implantacao > Fim',
            'cronograma_implantacao' => 'Cronograma Implantacao',
            'observacao' => 'Observacao',
            'inauguracao' => 'Inauguracao',
            'imp_prazo_planej' => 'Implantacao > Prazo Planejado',
            'imp_prazo_realiz' => 'Implantacao > Prazo Realizado',
            'mes' => 'Mes',
            'ano' => 'Ano',
            // Dados do Imovel
            'tipo_imovel' => 'Tipo de Imovel',
            'endereco' => 'Endereco',
            'cidade' => 'Cidade',
            'uf' => 'UF',
            'empreendimento' => 'Empreendimento',
            'locacao' => 'Locacao',
            'contato_corretor' => 'Contato Corretor / PP',
            'aluguel' => 'Aluguel',
            'obs_aluguel' => 'Obs. Aluguel',
            'carencia_contrato_meses' => 'Carencia Contrato (meses)',
            'multa_contrato_meses' => 'Multa Contrato (meses)',
            'metro_contrato' => 'M2 Contrato',
            'metro_layout_util' => 'M2 Layout Util',
            'pavimento' => 'Pavimento',
            'estacionamento_qtd' => 'Estacionamento (Qtd)',
            'vagas_estacionamento' => 'Vagas Exclusivas/Compartilhadas',
            // % de Obra
            'dias_para_inauguracao' => 'Dias para Inauguracao',
            'dias_obra_inicio_pmo' => 'Dias de Obra (Inicio PMO)',
            'percentual_obra' => '% Obra Previsto',
            'percentual_obra_executado' => '% Obra Executado',
            'desvio' => 'Desvio',
            // Acompanhamento
            'itens_criticos' => 'Itens Criticos',
            'descricao_itens_criticos' => 'Descricao Itens Criticos',
            'set_equipamentos' => 'Set Equipamentos',
            'piso' => 'Piso',
            'alteracao_spa_addons' => 'Alteracao SPA / Addons',
            // Cronograma VISI
            'cronograma_visi' => 'Cronograma VISI',
            'camera_unidade' => 'Camera na Unidade',
            'ponto_atencao' => 'Ponto de Atencao',
            // Contratacoes
            'civil' => 'Civil',
            'hidraulica' => 'Hidraulica',
            'eletrica' => 'Eletrica',
            'incendio' => 'Incendio',
            'instalacao_ar_condicionado' => 'Instalacao Ar Condicionado',
            'maquinas_ar_condicionado' => 'Maquinas Ar Condicionado',
            'homologados_em_atraso' => 'Homologados em Atraso',
            // Contas de Consumo
            'energia' => 'Energia',
            'previsao_ligacao_energia' => 'Previsao Ligacao Energia',
            'gerador_contratual' => 'Gerador Contratual',
            'agua' => 'Agua',
            'gas' => 'Gas',
            'comentario' => 'Comentario',
            // Pos Obra
            'email_solicitacao_cl' => 'Email Solicitacao CL',
            'envio_qrcod' => 'Envio QRCod',
            'checklist_manutencao' => 'Checklist Manutencao',
            'data_check_list' => 'Data Check List',
            'inicio_prev_pendencias' => 'Inicio Prev. Pendencias',
            'termino_prev_pendencias' => 'Termino Prev. Pendencias',
            'elevador' => 'Elevador',
            'comentarios_adicionais' => 'Comentarios Adicionais',
            'gestor_pos_obra' => 'Gestor Pos Obra',
            'link' => 'Link VISI',
            'observacao_implantacao' => 'Observacao Implantacao',
            // Cadastral (PE)
            'cad_plan_inicio' => 'Cadastral > Planej. Inicio',
            'cad_plan_fim' => 'Cadastral > Planej. Fim',
            'cad_plan_dias' => 'Cadastral > Planejado (dias)',
            'cad_rea_inicio' => 'Cadastral > Realizado Inicio',
            'cad_rea_fim' => 'Cadastral > Realizado Fim',
            'cad_prazo' => 'Cadastral > Prazo',
            'cad_status' => 'Cadastral > Status',
            // Visita Tecnica (PE)
            'vis_plan_inicio' => 'Visita Tecnica > Planej. Inicio',
            'vis_plan_fim' => 'Visita Tecnica > Planej. Fim',
            'vis_plan_dias' => 'Visita Tecnica > Planejado (dias)',
            'vis_rea_inicio' => 'Visita Tecnica > Realizado Inicio',
            'vis_rea_fim' => 'Visita Tecnica > Realizado Fim',
            'vis_prazo' => 'Visita Tecnica > Prazo',
            'vis_status' => 'Visita Tecnica > Status',
            // Briefing + Layout (PE)
            'brief_plan' => 'Briefing > Planejado',
            'brief_plan_lay_inicio' => 'Briefing > Planej. Layout Inicio',
            'brief_plan_lay_fim' => 'Briefing > Planej. Layout Fim',
            'brief_plan_dias' => 'Briefing > Planejado (dias)',
            'brief_real' => 'Briefing > Realizado',
            'brief_real_lay_inicio' => 'Briefing > Realizado Layout Inicio',
            'brief_real_lay_fim' => 'Briefing > Realizado Layout Fim',
            'brief_prazo' => 'Briefing > Prazo',
            'brief_status' => 'Briefing > Status',
            // Ordem de Investimento (PE)
            'ordem_planej_ini' => 'Ordem Invest. > Planej. Inicio',
            'ordem_planej_fim' => 'Ordem Invest. > Planej. Fim',
            'ordem_planejado' => 'Ordem Invest. > Planejado (dias)',
            'ordem_realizado' => 'Ordem Invest. > Realizado Inicio',
            'ordem_realizado_fim' => 'Ordem Invest. > Realizado Fim',
            'ordem_prazo' => 'Ordem Invest. > Prazo',
            'ordem_status' => 'Ordem Invest. > Status',
            'ordem_data_aprov' => 'Ordem Invest. > Data Aprovacao',
            'ordem_status_aprov' => 'Ordem Invest. > Status Aprovacao',
            // Projeto Executivo (PE)
            'proj_planej_reuniao_start' => 'Proj. Exec. > Planej. Reuniao Start',
            'proj_real_reuniao_start' => 'Proj. Exec. > Realizado Reuniao Start',
            'proj_plan_ini' => 'Proj. Exec. > Planej. Inicio',
            'proj_plan_fim' => 'Proj. Exec. > Planej. Fim',
            'proj_plan' => 'Proj. Exec. > Planejado (dias)',
            'proj_real_ini' => 'Proj. Exec. > Realizado Inicio',
            'proj_real_fim' => 'Proj. Exec. > Realizado Fim',
            'proj_prazo' => 'Proj. Exec. > Prazo',
            'proj_status' => 'Proj. Exec. > Status',
            // Orcamentos e Contratacoes (PE)
            'orca_reuniao_kickoff' => 'Orcamento > Reuniao Kickoff',
            'orca_planejado_ini' => 'Orcamento > Planej. Inicio',
            'orca_planejado_fim' => 'Orcamento > Planej. Fim',
            'orca_planejado' => 'Orcamento > Planejado (dias)',
            'orca_real_ini' => 'Orcamento > Realizado Inicio',
            'orca_real_fim' => 'Orcamento > Realizado Fim',
            'orca_prazo' => 'Orcamento > Prazo',
            'orca_status' => 'Orcamento > Status',
            // Legalizacao (PE)
            'legal_status_consulta_prev' => 'Legalizacao > Status Consulta Previa',
            'legal_doc_posse' => 'Legalizacao > Documentacao Posse',
            'legal_plan_ini' => 'Legalizacao > Planej. Inicio',
            'legal_plan_fim' => 'Legalizacao > Planej. Fim',
            'legal_prazo_legal' => 'Legalizacao > Prazo Legal',
            'legal_realizado_ini' => 'Legalizacao > Realizado Inicio',
            'legal_realizado_fim' => 'Legalizacao > Realizado Fim',
            'legal_prazo' => 'Legalizacao > Prazo',
            'legal_status' => 'Legalizacao > Status',
            // Posse (PE)
            'data_posse' => 'Posse > Data',
            'mes_posse' => 'Posse > Mes',
            'posse_engenharia' => 'Posse > Engenharia',
            'posse_legalizacao' => 'Posse > Legalizacao',
            'posse_status' => 'Posse > Status',
            'posse_comentarios' => 'Posse > Comentarios',
            // Financeiro / Estrategico
            'capex_aprovado_diretoria_valor' => 'CAPEX Aprovado (R$)',
            'capex_aprovado_diretoria' => 'CAPEX Aprovado Diretoria',
            'coc_aprovado' => 'COC Aprovado (%)',
            'estimativa_alunos' => 'Estimativa de Alunos',
            'tier' => 'Tier',
            'renda' => 'Renda',
            'vendas_mkt' => 'Pre-Venda MKT',
            'vendas_mkt_realizado' => 'Pre-Venda MKT Realizado',
            // Diretoria
            'dir_status_contrato' => 'Diretoria > Status',
            'obs_diretoria' => 'Diretoria > Obs.',
            'reuniao_ita' => 'Reuniao ITA (Obs.)',
        ];
    }

    private function normalizeHeader(string $header): string
    {
        if (class_exists(\Normalizer::class)) {
            $header = \Normalizer::normalize($header, \Normalizer::FORM_C) ?: $header;
        }

        $header = mb_strtolower(trim($header));
        $header = str_replace(['_', '-', '.', '/', '\\'], ' ', $header);
        $header = preg_replace('/\s+/', ' ', $header);
        $header = trim($header);

        $accents = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        return strtr($header, $accents);
    }

    public function getEnumColumns(): array
    {
        return $this->enumColumns;
    }

    public function detectAllUniqueValues(string $filePath, string|int $sheet, array $headerNames, ?int $headerRow = null, int $limit = 50, array $columnMap = []): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet || empty($headerNames)) {
            return [];
        }

        $headerRow = $headerRow ?? $this->detectHeaderRow($filePath, $sheet);

        $colIndexes = [];
        foreach ($headerNames as $headerName) {
            if (! empty($columnMap[$headerName])) {
                $colIndexes[$headerName] = $columnMap[$headerName];
            } else {
                $headers = $this->getHeaders($filePath, $sheet, $headerRow);
                $idx = array_search($headerName, $headers);
                if ($idx !== false) {
                    $colIndexes[$headerName] = $idx + 1;
                }
            }
        }

        if (empty($colIndexes)) {
            return [];
        }

        $highestRow = $worksheet->getHighestRow();
        $result = array_fill_keys($headerNames, []);

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            foreach ($colIndexes as $headerName => $colIdx) {
                $v = $worksheet->getCellByColumnAndRow($colIdx, $row)->getValue();
                if ($v !== null && trim((string) $v) !== '') {
                    $key = trim((string) $v);
                    $result[$headerName][$key] = ($result[$headerName][$key] ?? 0) + 1;
                }
            }
        }

        foreach ($result as $headerName => &$values) {
            arsort($values);
            $values = array_slice($values, 0, $limit, true);
        }

        return $result;
    }

    public function detectUniqueValues(string $filePath, string|int $sheet, string $headerName, ?int $headerRow = null, int $limit = 50): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet($sheet);

        if (! $worksheet) {
            return [];
        }

        $headerRow = $headerRow ?? $this->detectHeaderRow($filePath, $sheet);
        $headers = $this->getHeaders($filePath, $sheet, $headerRow);
        $colIndex = array_search($headerName, $headers);

        if ($colIndex === false) {
            return [];
        }

        $colIndex++; // 1-based
        $highestRow = $worksheet->getHighestRow();
        $values = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $v = $worksheet->getCellByColumnAndRow($colIndex, $row)->getValue();
            if ($v !== null && trim((string) $v) !== '') {
                $key = trim((string) $v);
                $values[$key] = ($values[$key] ?? 0) + 1;
            }
        }

        arsort($values);

        return array_slice($values, 0, $limit, true);
    }

    public function getEnumOptionsForField(string $field): array
    {
        if (! isset($this->enumColumns[$field])) {
            return [];
        }

        return array_values(array_unique($this->enumColumns[$field]));
    }

    public function castValue(string $column, mixed $value, array $customValueMap = []): mixed
    {
        if ($value === null || $value === '' || $value === 'N/A' || $value === 'N.A.' || $value === '#N/A') {
            return null;
        }

        $stringValue = trim((string) $value);

        if (str_starts_with($stringValue, '=')) {
            return null;
        }

        if (isset($customValueMap[$column][$stringValue])) {
            $mapped = $customValueMap[$column][$stringValue];
            if ($mapped === '' || $mapped === null) {
                return null;
            }
            $value = $mapped;
            $stringValue = trim((string) $value);
        } elseif (isset($this->enumColumns[$column][$stringValue])) {
            $mapped = $this->enumColumns[$column][$stringValue];
            if ($mapped === '' || $mapped === null) {
                return null;
            }
            $value = $mapped;
            $stringValue = trim((string) $value);
        }

        if (in_array($column, $this->dateColumns)) {
            return $this->parseDate($value);
        }

        if (in_array($column, $this->intColumns)) {
            $cleaned = preg_replace('/\D/', '', $stringValue);

            return $cleaned !== '' ? (int) $cleaned : null;
        }

        if (in_array($column, $this->floatColumns)) {
            $cleaned = str_replace(',', '.', preg_replace('/[^\d,.\-]/', '', $stringValue));

            return is_numeric($cleaned) ? (float) $cleaned : null;
        }

        if (in_array($column, $this->booleanColumns)) {
            $lower = mb_strtolower($stringValue);

            return in_array($lower, ['sim', 'yes', 's', 'y', '1', 'true', 'x']) ? true : false;
        }

        if (isset($this->enumColumns[$column])) {
            return $stringValue;
        }

        return $stringValue;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((int) $value)->format('Y-m-d');
            }

            $stringValue = trim((string) $value);

            // dd/mm/yy → expandir ano de 2 digitos
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2})$#', $stringValue, $m)) {
                $ano = (int) $m[3];
                $ano = $ano > 50 ? 1900 + $ano : 2000 + $ano;
                $stringValue = sprintf('%02d-%02d-%04d', (int) $m[1], (int) $m[2], $ano);
            }

            // dd/mm/yyyy → converter barras para hifens (strtotime entende dd-mm-yyyy como europeu)
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $stringValue)) {
                $stringValue = str_replace('/', '-', $stringValue);
            }

            $timestamp = strtotime($stringValue);

            return $timestamp ? date('Y-m-d', $timestamp) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
