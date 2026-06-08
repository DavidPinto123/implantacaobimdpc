<?php

namespace App\Imports;

use App\Models\HistoricoProjeto;
use App\Models\Projeto;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProjetosImport implements OnEachRow, WithHeadingRow
{
    public function onRow(Row $row)
    {
        $row = $row->toArray();
        $tableColumns = Schema::getColumnListing('projetos');
        $item = [];

        $intColumns = [
            'cad_plan_dias',
            'cad_rea_inicio',
            'cad_rea_fim',
            'cad_prazo',
            'vis_plan_dias',
            'vis_rea_inicio',
            'vis_rea_fim',
            'vis_prazo',
            'brief_plan_dias',
            'brief_real_lay_inicio',
            'brief_real_lay_fim',
            'brief_prazo',
            'ordem_planejado',
            'ordem_realizado_fim',
            'ordem_prazo',
            'proj_plan',
            'proj_real_ini',
            'proj_real_fim',
            'proj_prazo',
            'orca_planejado',
            'orca_real_ini',
            'orca_real_fim',
            'orca_prazo',
            'legal_prazo_legal',
            'legal_realizado_ini',
            'legal_realizado_fim',
            'legal_prazo',
            'imp_prazo_planejado',
            'imp_prazo_realizado',
            'imp_mes',
            'imp_ano',
            'carencia',
            'vendas_mkt_realizado',
            'mes_posse',
        ];

        $floatColumns = ['aluguel_cto', 'capex_aprovado_diretoria_valor'];
        $booleanColumns = ['capex_aprovado_diretoria'];
        $dateColumns = [
            'data_ass_contrato',
            'prazo_inicio',
            'cad_plan_inicio',
            'cad_plan_fim',
            'cad_rea_inicio',
            'cad_rea_fim',
            'vis_plan_inicio',
            'vis_plan_fim',
            'vis_rea_inicio',
            'vis_rea_fim',
            'brief_plan',
            'brief_plan_lay_inicio',
            'brief_plan_lay_fim',
            'brief_real',
            'brief_real_lay_inicio',
            'brief_real_lay_fim',
            'ordem_planej_ini',
            'ordem_planej_fim',
            'ordem_realizado',
            'ordem_realizado_fim',
            'ordem_data_aprov',
            'proj_planej_reuniao_start',
            'proj_real_reuniao_start',
            'proj_plan_ini',
            'proj_plan_fim',
            'proj_real_ini',
            'proj_real_fim',
            'orca_planejado_ini',
            'orca_planejado_fim',
            'orca_real_ini',
            'orca_real_fim',
            'legal_plan_ini',
            'legal_plan_fim',
            'legal_realizado_ini',
            'legal_realizado_fim',
            'data_posse',
            'inicio_obra',
            'entrega_obra',
            'imp_inicio',
            'imp_fim',
            'orca_reuniao_kickoff',
            'vendas_mkt',
        ];

        foreach ($row as $coluna => $valor) {
            if (! in_array($coluna, $tableColumns)) {
                continue;
            }

            // Valores inválidos
            if ($valor === 'N/A' || $valor === 'N.A.' || $valor === '' || str_starts_with($valor, '=')) {
                $item[$coluna] = null;

                continue;
            }

            // Datas
            if (in_array($coluna, $dateColumns)) {
                try {
                    if (is_numeric($valor)) {
                        $item[$coluna] = Date::excelToDateTimeObject($valor)->format('Y-m-d');
                    } else {
                        $timestamp = strtotime($valor);
                        $item[$coluna] = $timestamp ? date('Y-m-d', $timestamp) : null;
                    }
                } catch (\Exception $e) {
                    $item[$coluna] = null;
                }

                continue;
            }

            // Inteiros
            if (in_array($coluna, $intColumns)) {

                // Tratamento especial para posse_data_posse
                if ($coluna === 'mes_posse') {
                    $valorLimpo = preg_replace('/\D/', '', $valor); // remove tudo que não é dígito
                    $valorInt = $valorLimpo !== '' ? (int) $valorLimpo : null;

                    // Garante que o valor está dentro do limite do INT do MySQL
                    if ($valorInt !== null && ($valorInt < -2147483648 || $valorInt > 2147483647)) {
                        $valorInt = null;
                    }

                    $item[$coluna] = $valorInt;

                    continue;
                }

                // Para os outros inteiros
                $valorLimpo = preg_replace('/\D/', '', $valor);
                $item[$coluna] = $valorLimpo !== '' ? (int) $valorLimpo : null;

                continue;
            }

            /*
            if (!empty($item['locacao'])) {
                $valor = trim(strtolower($item['locacao']));

                if (in_array($valor, ['mono', 'monousuario', 'mono usuário', 'monousuário'])) {
                    $item['locacao'] = 'Mono usuário';
                } elseif (in_array($valor, ['multi', 'multiusuario', 'multiusuário', 'multi usuário'])) {
                    $item['locacao'] = 'Multiusuário';
                } else {
                    $item['locacao'] = null; // ou defina um padrão
                }
            }
            */

            // Colunas decimais
            if (in_array($coluna, $floatColumns)) {
                // Mantém só números, ponto e vírgula
                $valorLimpo = str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $valor));

                // Se não for número válido → null
                $item[$coluna] = is_numeric($valorLimpo) ? (float) $valorLimpo : null;

                continue;
            }

            // Booleanos
            if (in_array($coluna, $booleanColumns)) {
                $item[$coluna] = strtoupper($valor) === 'SIM' ? 1 : 0;

                continue;
            }

            // Strings
            $item[$coluna] = $valor;
        }

        if (! empty($item)) {
            Projeto::create($item);

            /*
            HistoricoProjeto::create([
                'projeto_id' => $projeto->id,
                'usuario_id' => 1,
                'setor' => 'Criação',
                'status' => $projeto->status ?? 'Inaugurada',
                'fase' => 'Geral',
                'etapa' => 'Geral',
                'status_antigo' => null,
                'status_novo' => $projeto->status ?? 'Inaugurada',
                'acao' => 'criado',
            ]);
            */
        }
    }
}
