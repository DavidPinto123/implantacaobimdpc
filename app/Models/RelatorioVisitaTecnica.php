<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RelatorioVisitaTecnica extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero_relatorio_vt',
        'agendado_em',
        'iniciado_em',
        'concluido_em',
        'sicronizado_em',
        'autor',
        'unidade_relatorio',
        'projeto_id',

        // Área 1 – Técnicas
        'unidade',
        'marca_id',
        'endereco',
        'responsavel_tecnico',
        'prazo_de_obras',
        'descricao_prazo_obras',
        'prazo_de_obras_outro',
        'condicoes_imovel',
        'comentario_condicoes_imovel',
        'validador_ticket_estacionamento',
        'pavimento',
        'pavimento_outro',
        'empreendimento',
        'empreendimento_outro',
        'contrato_bts',
        'prazo_bts',
        'prazo_desocupacao',
        'locacao',
        'contato_responsavel',
        'etapa_contrato',

        // Área 2 – Elétrica / Telefonia / Internet
        'entrada_de_energia',
        'descricao_energia',
        'energia_provisoria',
        'descricao_energia_provisoria',
        'unica_medicao',
        'descricao_medicao',
        'spda',
        'descricao_spda',
        'telegonia_dg',
        'distancia_ponto_telefonia',
        'descricao_telefonia',
        'energia_carga_superior_150',
        'descricao_energia_carga_superior_150',
        'cabos_alimentadores_shell',
        'metros_cabeamento',
        'necessario_visita_consultor_energia',

        // Área 3 – Estrutura / Cobertura / Acústica
        'tipo_estrutura',
        'tipo_estrutura_outro',
        'cobertura_vao_1_5',
        'cobertura_vao_1_5_metragem',
        'descricao_cobertura_vao_1_5',
        'cobertura_isolamento',
        'cobertura_area_isolamento',
        'descricao_cobertura_isolamento',
        'necessario_estrutura_auxiliar',
        'descricao_estrutura_auxiliar',
        'estrutura_fachada',
        'descricao_estrutura_fachada',
        'permitidas_furacoes_laje',
        'descricao_furacoes_laje',
        'sobrecarga_minima_laje',
        'comprovacao_sobrecarga_laje',
        'descricao_sobrecarga_minima_laje',
        'sobrecarga_minima_laje_teto',
        'comprovacao_sobrecarga_laje_teto',
        'descricao_sobrecarga_minima_laje_teto',
        'local_tomada_ar_externo_exaustao',
        'descricao_local_tomada_ar_externo_exaustao',
        'alvenaria_periferia_existente',
        'metros_alvenaria_periferia',
        'descricao_alvenaria_periferia_existente',
        'reboco_interno_externo_existente',
        'metros_reboco',
        'descricao_reboco_interno_externo_existente',
        'estanqueidade',
        'estanqueidade_outro',
        'descricao_complementar_estanqueidade',
        'descricao_estanqueidade',

        // Área 4 – Área Técnica
        'area_tecnica_externa_existente',
        'descricao_area_tecnica_externa_existente',
        /*
      'sugestao_area_tecnica_interna',
      'descricao_sugestao_area_tecnica_interna',
      */
        'prever_acustica_condensadores',
        'descricao_prever_acustica_condensadores',
        'prever_protecao_condensadores',
        'descricao_prever_protecao_condensadores',

        // Área 5 – Hidráulica / Esgoto / Gás
        'reservatorio_agua_existente',
        'reservatorio_agua_litragem',
        'descricao_reservatorio_agua_existente',
        'reservatorio_incendio_existente',
        'reservatorio_incendio_litragem',
        'descricao_reservatorio_incendio_existente',
        'ponto_esgoto_existente_shell',
        'ponto_esgoto_mais_proximo',
        'descricao_ponto_esgoto_existente_shell',
        'rede_gas_disponivel',
        'distancia_rede_gas',
        'descricao_rede_gas_disponivel',
        'medidor_agua_instalado_ligado',
        'numero_instalacao_agua',
        'descricao_medidor_agua_instalado_ligado',
        'sistema_incendio_existente',
        'descricao_sistema_incendio_existente',

        // Área 6 – Arquitetura / Civil
        'planta_demarcacao_area',
        'link_planta_demarcacao_area',
        'descricao_planta_demarcacao_area',
        'pd_acima_livre',
        'descricao_pd_acima_livre',
        'necessario_elevador_plataforma',
        'descricao_necessario_elevador_plataforma',
        'piso_acabamento_polido',
        'piso_area_intervencao',
        'descricao_piso_acabamento_polido',
        'necessario_pelicula_fachada',
        'pelicula_fachada_area',
        'descricao_necessario_pelicula_fachada',
        'prever_marquise',
        'descricao_prever_marquise',
        'prever_porta_enrolar',
        'porta_enrolar_area_necessaria',
        'descricao_prever_porta_enrolar',
        /*
      'necessario_porta_enrolar',
      'descricao_necessario_porta_enrolar',
      */
        'caixilhos_vidros_existentes',
        'caixilhos_vidros_area',
        'descricao_caixilhos_vidros_existentes',
        'prever_impermeabilizacao',
        'impermeabilizacao_area_necessaria',
        'descricao_prever_impermeabilizacao',

        // Área 7 - Observações gerais
        'observacoes_gerais',
        'pontos_atencao',

        // Imagens
        'foto_entrada_de_energia',
        'foto_energia_carga_superior_150',
        'foto_energia_provisoria',
        'foto_unica_medicao',
        'foto_spda',
        'foto_telegonia_dg',
        'foto_necessario_estrutura_auxiliar',
        'foto_estrutura_fachada',
        'foto_cobertura_vao_1_5',
        'foto_cobertura_isolamento',
        'foto_permitidas_furacoes_laje',
        'foto_sobrecarga_minima_laje',
        'foto_sobrecarga_minima_laje_teto',
        'foto_local_tomada_ar_externo_exaustao',
        'foto_alvenaria_periferia_existente',
        'foto_reboco_interno_externo_existente',
        'foto_estanqueidade',
        'foto_area_tecnica_externa_existente',
        // 'foto_sugestao_area_tecnica_interna',
        'foto_prever_acustica_condensadores',
        'foto_prever_protecao_condensadores',
        'foto_reservatorio_agua_existente',
        'foto_reservatorio_incendio_existente',
        'foto_ponto_esgoto_existente_shell',
        'foto_rede_gas_disponivel',
        'foto_medidor_agua_instalado_ligado',
        'foto_sistema_incendio_existente',
        'foto_planta_demarcacao_area',
        'foto_pd_acima_livre',
        'foto_necessario_elevador_plataforma',
        'foto_piso_acabamento_polido',
        'foto_necessario_pelicula_fachada',
        'foto_prever_marquise',
        'foto_prever_porta_enrolar',
        'foto_caixilhos_vidros_existentes',
        'foto_prever_impermeabilizacao',
        'foto_necessario_porta_enrolar',
        'fotos_gerais',
        'foto_capa',

        'status',
    ];

    protected $casts = [
        'foto_entrada_de_energia' => 'array',
        'foto_energia_carga_superior_150' => 'array',
        'foto_energia_provisoria' => 'array',
        'foto_unica_medicao' => 'array',
        'foto_spda' => 'array',
        'foto_telegonia_dg' => 'array',
        'foto_necessario_estrutura_auxiliar' => 'array',
        'foto_estrutura_fachada' => 'array',
        'foto_cobertura_vao_1_5' => 'array',
        'foto_cobertura_isolamento' => 'array',
        'foto_permitidas_furacoes_laje' => 'array',
        'foto_sobrecarga_minima_laje' => 'array',
        'foto_sobrecarga_minima_laje_teto' => 'array',
        'foto_local_tomada_ar_externo_exaustao' => 'array',
        'foto_alvenaria_periferia_existente' => 'array',
        'foto_reboco_interno_externo_existente' => 'array',
        'foto_estanqueidade' => 'array',
        'foto_area_tecnica_externa_existente' => 'array',
        // 'foto_sugestao_area_tecnica_interna' => 'array',
        'foto_prever_acustica_condensadores' => 'array',
        'foto_prever_protecao_condensadores' => 'array',
        'foto_reservatorio_agua_existente' => 'array',
        'foto_reservatorio_incendio_existente' => 'array',
        'foto_ponto_esgoto_existente_shell' => 'array',
        'foto_rede_gas_disponivel' => 'array',
        'foto_medidor_agua_instalado_ligado' => 'array',
        'foto_sistema_incendio_existente' => 'array',
        'foto_planta_demarcacao_area' => 'array',
        'foto_pd_acima_livre' => 'array',
        'foto_necessario_elevador_plataforma' => 'array',
        'foto_piso_acabamento_polido' => 'array',
        'foto_necessario_pelicula_fachada' => 'array',
        'foto_prever_marquise' => 'array',
        'foto_prever_porta_enrolar' => 'array',
        'foto_caixilhos_vidros_existentes' => 'array',
        'foto_prever_impermeabilizacao' => 'array',
        'foto_necessario_porta_enrolar' => 'array',
        'fotos_gerais' => 'array',

        'agendado_em' => 'datetime',
        'iniciado_em' => 'datetime',
        'concluido_em' => 'datetime',

        'metros_cabeamento' => 'decimal:2',
        'metros_alvenaria_periferia' => 'decimal:2',
        'metros_reboco' => 'decimal:2',

        'cobertura_vao_1_5_metragem' => 'decimal:2',
        'cobertura_area_isolamento' => 'decimal:2',

        'reservatorio_agua_litragem' => 'decimal:2',
        'reservatorio_incendio_litragem' => 'decimal:2',

        'piso_area_intervencao' => 'decimal:2',
        'pelicula_fachada_area' => 'decimal:2',
        'porta_enrolar_area_necessaria' => 'decimal:2',
        'caixilhos_vidros_area' => 'decimal:2',
        'impermeabilizacao_area_necessaria' => 'decimal:2',

        'ponto_esgoto_mais_proximo' => 'decimal:2',

        'distancia_ponto_telefonia' => 'decimal:2',
        'distancia_rede_gas' => 'decimal:2',

        'sistema_incendio_existente' => 'array',
        'pavimento' => 'array',
        'contrato_bts' => 'array',
        'prazo_bts' => 'date',
        'prazo_desocupacao' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($relatorio) {

            $ultimo = self::orderBy('id', 'desc')->first();

            if (! $ultimo || ! $ultimo->numero_relatorio_vt) {
                $numero = 1;
            } else {
                $numero = (int) str_replace('VT-', '', $ultimo->numero_relatorio_vt) + 1;
            }

            $relatorio->numero_relatorio_vt = 'VT-'.str_pad($numero, 3, '0', STR_PAD_LEFT);
        });
    }

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }
}
