<?php

namespace App\Models;

use App\Enums\ModoAncoraCronograma;
use App\Observers\ProjetoSyncObserver;
use App\Services\CronogramaTemplateService;
use App\Support\DateCalc;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(ProjetoSyncObserver::class)]
class Projeto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'sem_fases_auto',
        'sigla',
        'sigla_antiga',
        'nova_sigla',
        'cnpj',
        'cnpj_provisorio',
        'status_cnpj',
        'inscricao_estadual',
        'user_id',
        'etapa_id',
        'rua',
        'bairro',
        'cep',
        'telefone',
        'numero',
        'complemento',
        'cidade_id',
        'estado_id',
        'pais_id',
        'link_matterport',

        // Novos campos
        'status',
        'evtl_status',
        'evtl_recebido_em',
        'anexo_evtl',
        'pipeline',
        'tipo',
        'tipo_entrada',
        'nome_contato',
        'contato',
        'pin_google',
        'tipo_de_loja',
        'n_vagas_livres',
        'area_academia',
        'area_terreno',
        'n_pisos',
        'pe_direito',
        'modelo_entrega_p',
        'aluguel_cto',
        'luvas',
        'iptu',
        'condominio',
        'configuracao_academia',
        'dados_engenharia',
        'pontos_atencao',
        'prazo_inicio',
        'entrega_projeto',
        'inicio_obra',
        'entrega_obra',
        'inauguracao',
        'ano_inauguracao',
        'projeto_croqui',
        'potencial_alunos',
        'link_estudo_projecao_alunos',
        'codigo',
        'imagem_ponto',
        'anexos',
        'anexo_matricula_iptu',
        'anexo_habite_se',
        'anexo_avcb',
        'anexo_projeto',
        'anexo_convencao_condominio',
        'anexo_regime_interno',
        'anexo_normas_gerais',
        'anexo_outros_documentos',
        'oi_pdf',
        'observacoes_ponto',
        'cash_on_cash',
        'marca',
        'numero_loja',
        'locacao',
        'area_locada',
        'carencia',
        'multa_contrato',
        'empreendimento',
        'tipo_imovel',
        'data_entrega_shell',
        'relocation',
        'imovel_pronto',
        'link_docs',
        'link_construct_in',
        'anexo_proposta_comercial',
        'anexo_contrato_assinado',
        'anexo_proposta_comercial_comentario',
        'anexo_contrato_assinado_comentario',
        'anexo_pmo_cronograma',
        'comentario_pmo_cronograma',
        'anexo_pmo_termo_abertura',
        'comentario_pmo_termo_abertura',
        'anexo_planejamento_plano',
        'planejamento_plano_comentario',
        'anexo_planejamento_estudo',
        'planejamento_estudo_comentario',
        'anexo_consulta_previa',
        'anexo_consulta_previa_comentario',
        'anexo_estudoviabilidade',
        'anexo_estudoviabilidade_comentario',
        'anexo_visita_tecnica',
        'anexo_visita_tecnica_comentario',
        'anexo_projetos_adicionais',
        'anexo_projetos_adicionais_comentario',

        'endereco',
        'crono_revisado',
        'escopo',
        'resp_com',
        'resp_arq',
        'resp_eng',
        'resp_pmo',
        'gerente_geral_id',
        'pmo_nome',
        'status_comite',
        'status_imovel',
        'status_contrato',
        'data_ass_contrato',
        'cad_plan_inicio',
        'cad_plan_fim',
        'cad_plan_dias',
        'cad_rea_inicio',
        'cad_rea_fim',
        'cad_prazo',
        'cad_status',
        'vis_plan_inicio',
        'vis_plan_fim',
        'vis_plan_dias',
        'vis_rea_inicio',
        'vis_rea_fim',
        'vis_prazo',
        'vis_status',
        'brief_plan',
        'brief_plan_lay_inicio',
        'brief_plan_lay_fim',
        'brief_plan_dias',
        'brief_real',
        'brief_real_lay_inicio',
        'brief_real_lay_fim',
        'brief_prazo',
        'brief_status',
        'ordem_planej_ini',
        'ordem_planej_fim',
        'ordem_planejado',
        'ordem_realizado',
        'ordem_realizado_fim',
        'ordem_prazo',
        'ordem_status',
        'ordem_data_aprov',
        'ordem_status_aprov',
        'proj_planej_reuniao_start',
        'proj_real_reuniao_start',
        'proj_plan_ini',
        'proj_plan_fim',
        'proj_plan',
        'proj_real_ini',
        'proj_real_fim',
        'proj_prazo',
        'proj_status',
        'orca_reuniao_kickoff',
        'orca_planejado_ini',
        'orca_planejado_fim',
        'orca_planejado',
        'orca_real_ini',
        'orca_real_fim',
        'orca_prazo',
        'orca_status',
        'legal_status_consulta_prev',
        'legal_doc_posse',
        'legal_plan_ini',
        'legal_plan_fim',
        'legal_prazo_legal',
        'legal_realizado_ini',
        'legal_realizado_fim',
        'legal_prazo',
        'legal_status',
        'data_posse',
        'data_posse_pendente',
        'data_posse_pendente_motivo',
        'data_posse_pendente_motivo_codigo',
        'data_posse_pendente_user_id',
        'data_posse_pendente_solicitada_em',
        'modo_ancora',
        'aplicavel_suframa',
        'posse_data_posse',
        'mes_posse',
        'posse_engenharia',
        'posse_legalizacao',
        'posse_status',
        'posse_comentarios',
        'exec_prazo_plan',
        'exec_prazo_real',
        'imp_inicio',
        'imp_fim',
        'imp_prazo_planejado',
        'imp_prazo_realizado',
        'imp_mes',
        'imp_ano',
        'obs_aluguel',
        'metro_contrato',
        'metro_layout_util',
        'pavimento',
        'estacionamento_qtd',
        'vagas_estacionamento',
        'capex_aprovado_diretoria_valor',
        'capex_aprovado_diretoria',
        'coc_aprovado',
        'tier',
        'renda',
        'set_equipamentos',
        'vendas_mkt',
        'vendas_mkt_realizado',
        'diretoria',
        'reuniao_ita',
        'contato_corretor',
        'dir_status_contrato',
        'obs_diretoria',
        'risco_obra',
        'risco_obra_comentario',
    ];

    protected $casts = [
        'users_setor' => 'array',
        'anexos' => 'array',
        'oi_pdf' => 'array',
        'prazo_inicio' => 'date',
        'entrega_projeto' => 'date',
        'inicio_obra' => 'date',
        'entrega_obra' => 'date',
        'inauguracao' => 'date',
        'data_entrega_shell' => 'date',
        'relocation' => 'boolean',
        'imovel_pronto' => 'boolean',
        'risco_obra' => 'boolean',
        'cash_on_cash' => 'decimal:2',
        'area_academia' => 'decimal:2',
        'area_terreno' => 'decimal:2',
        'pe_direito' => 'decimal:2',
        'aluguel_cto' => 'float',
        'luvas' => 'decimal:2',
        'iptu' => 'decimal:2',
        'condominio' => 'decimal:2',
        'area_locada' => 'decimal:2',
        'imagem_ponto' => 'array',
        'anexo_proposta_comercial' => 'array',
        'anexo_contrato_assinado' => 'array',
        'anexo_pmo_cronograma' => 'array',
        'anexo_pmo_termo_abertura' => 'array',
        'anexo_planejamento_plano' => 'array',
        'anexo_planejamento_estudo' => 'array',
        'anexo_consulta_previa' => 'array',
        'anexo_estudoviabilidade' => 'array',
        'anexo_visita_tecnica' => 'array',
        'anexo_projetos_adicionais' => 'array',
        'anexo_matricula_iptu' => 'array',
        'anexo_habite_se' => 'array',
        'anexo_avcb' => 'array',
        'anexo_projeto' => 'array',
        'anexo_convencao_condominio' => 'array',
        'anexo_regime_interno' => 'array',
        'anexo_normas_gerais' => 'array',
        'anexo_outros_documentos' => 'array',
        'evtl_recebido_em' => 'date',
        'anexo_evtl' => 'array',

        'data_ass_contrato' => 'date',
        'cad_plan_inicio' => 'date',
        'cad_plan_fim' => 'date',
        'cad_rea_inicio' => 'date',
        'cad_rea_fim' => 'date',
        'vis_plan_inicio' => 'date',
        'vis_plan_fim' => 'date',
        'vis_rea_inicio' => 'date',
        'vis_rea_fim' => 'date',
        'brief_plan' => 'date',
        'brief_plan_lay_inicio' => 'date',
        'brief_plan_lay_fim' => 'date',
        'brief_real' => 'date',
        'brief_real_lay_inicio' => 'date',
        'brief_real_lay_fim' => 'date',
        'ordem_planej_ini' => 'date',
        'ordem_planej_fim' => 'date',
        'ordem_realizado' => 'date',
        'ordem_realizado_fim' => 'date',
        'ordem_data_aprov' => 'date',
        'proj_planej_reuniao_start' => 'date',
        'proj_real_reuniao_start' => 'date',
        'proj_plan_ini' => 'date',
        'proj_plan_fim' => 'date',
        'proj_real_ini' => 'date',
        'proj_real_fim' => 'date',
        'orca_reuniao_kickoff' => 'date',
        'orca_planejado_ini' => 'date',
        'orca_planejado_fim' => 'date',
        'orca_real_ini' => 'date',
        'orca_real_fim' => 'date',
        'legal_plan_ini' => 'date',
        'legal_plan_fim' => 'date',
        'legal_realizado_ini' => 'date',
        'legal_realizado_fim' => 'date',
        'data_posse' => 'date',
        'data_posse_pendente' => 'date',
        'data_posse_pendente_solicitada_em' => 'datetime',
        'modo_ancora' => ModoAncoraCronograma::class,
        'aplicavel_suframa' => 'boolean',
        'posse_data_posse' => 'date',
        'imp_inicio' => 'date',
        'imp_fim' => 'date',

        // Inteiros (dias, meses, anos)
        'cad_plan_dias' => 'integer',
        'vis_plan_dias' => 'integer',
        'brief_plan_dias' => 'integer',
        'brief_prazo' => 'integer',
        'imp_mes' => 'integer',
        'imp_ano' => 'integer',

        // Booleanos
        'capex_aprovado_diretoria' => 'boolean',
        'coc_aprovado' => 'boolean',

        // Decimais / valores monetários
        'capex_aprovado_diretoria_valor' => 'decimal:2',

        // Caso queira garantir metros como decimal também
        'metro_contrato' => 'decimal:2',
        'metro_layout_util' => 'decimal:2',
    ];

    public array $fases_antigas_cache = [];

    public ?string $motivo_alteracao_posse_codigo = null;

    public ?string $motivo_alteracao_posse_historico = null;

    public function user()
    {
        return $this->belongsTo(User::class); // Relacionamento com o modelo User
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'projeto_user');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class); // Relacionamento com o modelo Etapa
    }

    public function etapas()
    {
        return $this->belongsToMany(Etapa::class, 'etapa_projeto');
    }

    public function setores()
    {
        return $this->belongsToMany(Setor::class, 'projeto_setor');
    }

    public function cidade()
    {
        return $this->belongsTo(Cidade::class); // Relacionamento com o modelo Cidade
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class); // Relacionamento com Estado
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class); // Relacionamento com País
    }

    public function historicos()
    {
        return $this->hasMany(HistoricoProjeto::class, 'projeto_id');
    }

    public function prospeccao()
    {
        return $this->hasOne(Prospeccao::class);
    }

    public function prospeccoes()
    {
        return $this->hasMany(Prospeccao::class);
    }

    public function reuniaos()
    {
        return $this->belongsToMany(Reuniao::class, 'reuniao_projeto')
            ->withPivot(['status', 'corretor'])
            ->withTimestamps();
    }

    public function __toString()
    {
        return $this->nome; // ou o campo que você quer exibir
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'user_id'); // ou o nome correto da FK
    }

    public function reunioesComite()
    {
        return $this->hasMany(AprovacaoReuniaoComite::class, 'projeto_id');
    }

    public function viabilidades()
    {
        return $this->hasMany(AprovacaoViabilidade::class, 'projeto_id');
    }

    public function relatoriosVisitaTecnica()
    {
        return $this->hasMany(RelatorioVisitaTecnica::class);
    }

    public function relatorioFotograficos()
    {
        return $this->hasMany(RelatorioFotografico::class);
    }

    public function responsavelArq()
    {
        return $this->belongsTo(User::class, 'resp_arq');
    }

    public function responsavelCom()
    {
        return $this->belongsTo(User::class, 'resp_com');
    }

    public function responsavelEng()
    {
        return $this->belongsTo(User::class, 'resp_eng');
    }

    public function respPmo()
    {
        return $this->belongsTo(User::class, 'resp_pmo');
    }

    public function gerenteGeral()
    {
        return $this->belongsTo(User::class, 'gerente_geral_id');
    }

    // 🔹 OI
    public function getOiValorAttribute()
    {
        return $this->oi_pdf['valor_total'] ?? 0;
    }

    // 🔹 Pago
    /*
    public function getPagoTotalAttribute()
    {
        return $this->pagamentos()->sum('valor');
    }

    // 🔹 Compromissado (pedido emitido e não pago)
    public function getCompromissadoTotalAttribute()
    {
        return $this->pedidos()
            ->where('status', 'emitido')
            ->where('pago', false)
            ->sum('valor');
    }
    */
    public function getPagoTotalAttribute(): float
    {
        return 1000000; // temporário
    }

    public function getCompromissadoTotalAttribute(): float
    {
        return 755700; // temporário
    }

    // 🔹 Saldo
    public function getSaldoAttribute(): float
    {
        $oi = $this->ultimaOi?->valor_total ?? 0;
        $pago = $this->pago_total ?? 0;
        $compromissado = $this->compromissado_total ?? 0;

        return $oi - $pago - $compromissado;
    }

    public function ordensInvestimento()
    {
        return $this->hasMany(OrdemInvestimento::class);
    }

    public function ultimaOi()
    {
        return $this->hasOne(OrdemInvestimento::class)->latestOfMany();
    }

    public function controlePedido()
    {
        return $this->hasOne(ControlePedido::class);
    }

    public function controlePedidos()
    {
        return $this->hasMany(ControlePedido::class);
    }

    public function obras()
    {
        return $this->hasMany(Obras::class);
    }

    public function cronogramaFases()
    {
        return $this->hasMany(CronogramaFase::class)->orderBy('ordem');
    }

    public function dataPossePendenteSolicitante()
    {
        return $this->belongsTo(User::class, 'data_posse_pendente_user_id');
    }

    /**
     * SUFRAMA: decisão pendente quando ainda não foi marcada (null) e faltam
     * <= 60 dias para a data prevista da fase INAUGURACAO. Permite renderizar
     * badge piscante no header do cronograma como alerta para Vitor/Nathalia.
     */
    public function suframaPendente(): bool
    {
        if ($this->aplicavel_suframa !== null) {
            return false;
        }

        $inauguracao = $this->cronogramaFases()
            ->where('fase', \App\Enums\FaseCronograma::INAUGURACAO->value)
            ->value('data_prevista_inicio');

        if (! $inauguracao) {
            return false;
        }

        $diasParaInauguracao = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($inauguracao)->startOfDay(), false);

        return $diasParaInauguracao >= 0 && $diasParaInauguracao <= 60;
    }

    /**
     * Quantos dias faltam até a inauguração prevista. Usado pelo badge SUFRAMA.
     */
    public function diasParaInauguracao(): ?int
    {
        $inauguracao = $this->cronogramaFases()
            ->where('fase', \App\Enums\FaseCronograma::INAUGURACAO->value)
            ->value('data_prevista_inicio');

        if (! $inauguracao) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($inauguracao)->startOfDay(), false);
    }

    public function colunasPersonalizadas()
    {
        return $this->hasMany(ColunaPersonalizada::class, 'projeto_id');
    }

    public function relatorioVisitaTecnica(): HasOne
    {
        return $this->hasOne(RelatorioVisitaTecnica::class, 'projeto_id');
    }

    protected static function booted()
    {
        /*
        // Gera código automático no padrão SF10000+
        static::creating(function ($projeto) {
            if (!$projeto->codigo) {
                $lastCode = self::withTrashed()->orderBy('id', 'desc')->value('codigo');
                $number = $lastCode ? intval(substr($lastCode, 2)) + 1 : 10000;
                $projeto->codigo = 'SF' . $number;
            }
        });
        */
        /*
        static::saved(function ($projeto) {
            // Atualizar a etapa na tabela de prospeccao
            if ($projeto->prospeccao) {
                $prospeccao = $projeto->prospeccao;
                $prospeccao->etapa_id = $projeto->etapa_id;
                $prospeccao->save();
            }
        });
        */

        // Temporário: data_posse e data_entrega_shell devem permanecer sincronizadas
        static::saving(function ($projeto) {
            if ($projeto->isDirty('data_posse')) {
                $projeto->data_entrega_shell = $projeto->data_posse;
            } elseif ($projeto->isDirty('data_entrega_shell')) {
                $projeto->data_posse = $projeto->data_entrega_shell;
            }
        });

        static::created(function ($projeto) {
            // Se etapas ainda não foram sincronizadas, pegar do pivot após salvar
            $projeto->load('etapas');

            $fases = $projeto->etapas->pluck('nome');
            HistoricoProjeto::create([
                'projeto_id' => $projeto->id,
                'usuario_id' => Auth::id(),
                'setor' => 'Criação',
                'status' => $projeto->status ?? 'pendente',
                'fase' => $fases->isNotEmpty() ? $fases->implode(', ') : 'Geral',
                'etapa' => $fases->isNotEmpty() ? $fases->first() : 'Geral',
                'status_antigo' => null,
                'status_novo' => $projeto->status,
                'acao' => 'criado',
            ]);
        });

        static::saved(function ($projeto) {
            // Etapas novas: agora o pivot já foi sincronizado
            $fasesNovas = $projeto->etapas()->pluck('nome')->toArray();

            // Etapas antigas: você precisa salvar antes do update num observer OU...
            // ...carregar de forma controlada no evento 'updating'
            // Aqui vamos guardar no cache estático do modelo:
            $fasesAntigas = $projeto->fases_antigas_cache ?? [];

            // 🔹 Detecta mudança de status
            if ($projeto->wasChanged('status')) {
                HistoricoProjeto::create([
                    'projeto_id' => $projeto->id,
                    'usuario_id' => Auth::id(),
                    'setor' => Auth::user()?->setor ?? 'Desconhecido',
                    'status_antigo' => $projeto->getOriginal('status'),
                    'status_novo' => $projeto->status,
                    'status' => $projeto->status ?? 'pendente',
                    'fase' => implode(', ', $fasesNovas),
                    'etapa' => $fasesNovas[0] ?? 'Geral',
                    'acao' => 'alterou_status',
                ]);
            }

            // 🔹 Detecta mudança nas etapas (fase)
            if ($fasesAntigas !== $fasesNovas) {
                HistoricoProjeto::create([
                    'projeto_id' => $projeto->id,
                    'usuario_id' => Auth::id(),
                    'setor' => Auth::user()?->setor ?? 'Desconhecido',
                    'status' => $projeto->status ?? 'pendente',
                    'fase' => implode(', ', $fasesNovas),
                    'etapa' => $fasesNovas[0] ?? 'Geral',
                    'fase_antiga' => implode(', ', $fasesAntigas),
                    'fase_nova' => implode(', ', $fasesNovas),
                    'acao' => 'alterou_fase',
                ]);
            }

            if ($projeto->wasChanged('data_posse')) {
                // Grava entry de auditoria com motivo padronizado (PR 8).
                $valorAnterior = $projeto->getOriginal('data_posse');
                $valorNovo = $projeto->data_posse;

                CronogramaFaseHistorico::create([
                    'projeto_id' => $projeto->id,
                    'cronograma_fase_id' => null,
                    'campo_alterado' => 'projeto.data_posse',
                    'valor_anterior' => $valorAnterior ? (string) $valorAnterior : null,
                    'valor_novo' => $valorNovo ? $valorNovo->toDateString() : null,
                    'motivo' => sprintf(
                        'Data de posse alterada de %s para %s',
                        $valorAnterior ? Carbon::parse($valorAnterior)->format('d/m/Y') : '—',
                        $valorNovo ? $valorNovo->format('d/m/Y') : '—',
                    ),
                    'motivo_codigo' => $projeto->motivo_alteracao_posse_codigo,
                    'motivo_historico' => $projeto->motivo_alteracao_posse_historico,
                    'usuario_id' => Auth::id(),
                    'automatico' => false,
                ]);

                $projeto->motivo_alteracao_posse_codigo = null;
                $projeto->motivo_alteracao_posse_historico = null;

                // Modo de âncora (PR 4): em modo "obras" não recalcula; em
                // modo "posse" recalcula com regra assimétrica (antecipar
                // só ajusta fases elásticas; adiar ajusta todas).
                $modoAncora = $projeto->modo_ancora instanceof ModoAncoraCronograma
                    ? $projeto->modo_ancora
                    : ModoAncoraCronograma::from($projeto->modo_ancora ?? ModoAncoraCronograma::POSSE->value);

                if ($modoAncora === ModoAncoraCronograma::POSSE) {
                    // Recálculo assimétrico: quando a posse foi antecipada
                    // (nova data < antiga), só fases elásticas recalculam.
                    // Quando adiada, recalcula tudo normalmente.
                    $dataAntiga = $projeto->getOriginal('data_posse');
                    $dataNova = $projeto->data_posse;
                    $antecipou = $dataAntiga && $dataNova
                        && Carbon::parse($dataNova)->lt(Carbon::parse($dataAntiga));

                    $faseComTemplate = CronogramaFase::where('projeto_id', $projeto->id)
                        ->whereNotNull('cronograma_template_id')
                        ->first();

                    if ($faseComTemplate) {
                        $template = $faseComTemplate->template()->with('fases.dependencias')->first();

                        if ($template && $template->ancora_campo === 'projeto.data_posse') {
                            $ancoraFase = CronogramaFase::where('projeto_id', $projeto->id)
                                ->whereNotNull('cronograma_template_id')
                                ->whereHas('templateFase', fn ($q) => $q->where('is_ancora', true))
                                ->first();

                            if ($ancoraFase) {
                                (new CronogramaTemplateService)->recalcularFaseEDependentes($ancoraFase, $antecipou);
                            }
                        }
                    }
                }
            }
        });

        // ⚠️ Aqui capturamos as etapas antes de alterar
        static::updating(function ($projeto) {
            $projeto->fases_antigas_cache = $projeto->etapas()->pluck('nome')->toArray();
        });

        static::deleting(function ($projeto) {
            $projeto->historicos()->delete();

            if ($projeto->isForceDeleting()) {
                $projeto->obras()->forceDelete();
            } else {
                $projeto->obras()->delete();
            }
        });

        static::restoring(function ($projeto) {
            $projeto->obras()->onlyTrashed()->restore();
        });

        static::saving(function ($model) {
            // Cadastral Planejado
            DateCalc::applyToModel($model, 'cad_plan_inicio', 'cad_plan_dias', 'cad_plan_fim', inclusive: false);

            // Cadastral Realizado
            DateCalc::applyToModel($model, 'cad_rea_inicio', 'cad_prazo', 'cad_rea_fim', inclusive: false);

            // Visita Técnica Planejado
            DateCalc::applyToModel($model, 'vis_plan_inicio', 'vis_plan_dias', 'vis_plan_fim', inclusive: false);

            // Visita Técnica Realizado
            DateCalc::applyToModel($model, 'vis_rea_inicio', 'vis_prazo', 'vis_rea_fim', inclusive: false);

            // Briefing e Layout Planejado
            DateCalc::applyToModel($model, 'brief_plan_lay_inicio', 'brief_plan_dias', 'brief_plan_lay_fim', inclusive: false);

            // Briefing e Layout Realizado
            DateCalc::applyToModel($model, 'brief_real_lay_inicio', 'brief_prazo', 'brief_real_lay_fim', inclusive: false);

            // Ordem de investimento Planejado
            DateCalc::applyToModel($model, 'ordem_planej_ini', 'ordem_planejado', 'ordem_planej_fim', inclusive: false);

            // Ordem de investimento Realizado
            DateCalc::applyToModel($model, 'ordem_realizado', 'ordem_prazo', 'ordem_realizado_fim', inclusive: false);

            // Projeto executivo Planejado
            DateCalc::applyToModel($model, 'proj_plan_ini', 'proj_plan', 'proj_plan_fim', inclusive: false);

            // Projeto executivo Realizado
            DateCalc::applyToModel($model, 'proj_real_ini', 'proj_prazo', 'proj_real_fim', inclusive: false);

            // Orçamentos e contratações Planejado
            DateCalc::applyToModel($model, 'orca_planejado_ini', 'orca_planejado', 'orca_planejado_fim', inclusive: false);

            // Orçamentos e contratações Realizado
            DateCalc::applyToModel($model, 'orca_real_ini', 'orca_prazo', 'orca_real_fim', inclusive: false);

            // Legalização Planejado
            DateCalc::applyToModel($model, 'legal_plan_ini', 'legal_prazo_legal', 'legal_plan_fim', inclusive: false);

            // Legalização Realizado
            DateCalc::applyToModel($model, 'legal_realizado_ini', 'legal_prazo', 'legal_realizado_fim', inclusive: false);
        });
    }
}
