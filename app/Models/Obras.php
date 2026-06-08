<?php

namespace App\Models;

use App\Observers\ObrasObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ObrasObserver::class)]
class Obras extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'obras';

    protected $fillable = [
        'projeto_id',
        'tipos_unidade',
        'civil', // Tem no db e excel
        'hidraulica',
        'eletrica',
        'incendio',
        'instalacao_ar_condicionado',
        'maquinas_ar_condicionado',
        'homologados_em_atraso',
        'status',
        'relatorio_fotografico',
        'termo_de_posse',
        'comentarios',
        'cronograma_implantacao',
        'dias_para_inauguracao',
        'percentual_obra',
        'cronograma_visi',
        'ponto_atencao',
        'fachada_data_instalacao',
        'fachada_status',
        'fachada_observacao',
        'energia',
        'agua',
        'gas',
        'energia_observacoes',
        'agua_observacoes',
        'gas_observacoes',
        'comentario',
        'email_solicitacao_cl',
        'envio_qrcod',
        'checklist_manutencao',
        'inicio_prev_pendencias',
        'termino_prev_pendencias',
        'comentarios_adicionais',
        'codigo',
        'unidade',
        'foto_perfil',
        'foto_capa',
        'fotos',
        'pipe_land',
        'status_visita',
        'data_solicitacao_vt',
        'data_agendamento_vt',
        'status_proj_exec', // Tem no db e excel
        'engenharia', // Tem no db e excel
        'comercial', // Tem no db e excel
        'status_data_posse', // Tem no db e excel
        'inicio', // Tem no db e excel
        'fim', // Tem no db e excel
        'prazo_planejado', // Tem no db e excel
        'prazo_realizado', // Tem no db e excel
        'inicio_imp', // Tem no db e excel
        'fim_imp',
        'observacao',
        'imp_prazo_planej', // Tem no db e excel
        'imp_prazo_realiz', // Tem no db e excel
        'mes',
        'ano',
        'endereco',
        'cidade',
        'uf',
        'link',
        'arquitetura',
        'entrada_ponto',
        'data_assinatura_contrato',
        'entrada_ponto_ate_inauguracao',
        'assinatura_ate_inauguracao',
        'data_envio_relatorio_fotografico',
        'data_atualizacao_comentario',
        'inicio_real',
        'observacao_implantacao',
        'dias_obra_inicio_pmo',
        'percentual_obra_executado',
        'desvio',
        'itens_criticos',
        'descricao_itens_criticos',
        'camera_unidade',
        'previsao_ligacao_energia',
        'gerador_contratual',
        'data_check_list',
        'elevador',
        'gestor_pos_obra',
        'constructin_project_id',
        'set_equipamentos',
        'piso',
        'alteracao_spa_addons',
    ];

    protected $casts = [
        'entrada_ponto' => 'date',
        'data_assinatura_contrato' => 'date',
        'data_envio_relatorio_fotografico' => 'date',
        'data_atualizacao_comentario' => 'date',
        'status_data_posse' => 'date',
        'inicio' => 'date',
        'inicio_real' => 'date',
        'fim' => 'date',
        'inicio_imp' => 'date',
        'fim_imp' => 'date',
        'previsao_ligacao_energia' => 'date',
        'data_check_list' => 'date',
        'inicio_prev_pendencias' => 'date',
        'termino_prev_pendencias' => 'date',
        'data_solicitacao_vt' => 'date',
        'data_agendamento_vt' => 'date',
        'fachada_data_instalacao' => 'date',

        'entrada_ponto_ate_inauguracao' => 'integer',
        'assinatura_ate_inauguracao' => 'integer',
        'dias_obra_inicio_pmo' => 'integer',

        'percentual_obra_executado' => 'decimal:2',
        'desvio' => 'decimal:2',

        'constructin_project_id' => 'integer',
        'fotos' => 'array',
        'tipos_unidade' => 'array',
    ];

    public function getSiglaAttribute(): ?string
    {
        return $this->projeto?->sigla;
    }

    public function getNovaSiglaAttribute(): ?string
    {
        return $this->projeto?->nova_sigla;
    }

    public function getMarcaAttribute(): ?string
    {
        return $this->projeto?->marca;
    }

    public function getTipoImovelAttribute(): ?string
    {
        return $this->projeto?->tipo_imovel;
    }

    public function getEmpreendimentoAttribute(): ?string
    {
        return $this->projeto?->empreendimento;
    }

    public function getLocacaoAttribute(): ?string
    {
        return $this->projeto?->locacao;
    }

    public function getContatoCorretorAttribute(): ?string
    {
        return $this->projeto?->contato_corretor;
    }

    public function getInauguracaoAttribute()
    {
        return $this->projeto?->inauguracao;
    }

    public function controleAutorizacaoServicoResumo(): HasOne
    {
        return $this->hasOne(ControleAutorizacaoServicoResumo::class, 'obra_id');
    }

    public function getStatusContratoAttribute(): ?string
    {
        return $this->projeto?->status_contrato;
    }

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'obra_user',   // tabela pivot
            'obra_id',      // FK da obra
            'user_id'       // FK do usuário
        );
    }

    public function etiquetas()
    {
        return $this->belongsToMany(
            Etiqueta::class,
            'obra_etiqueta',
            'obra_id',
            'etiqueta_id'
        );
    }

    public function construtoras()
    {
        return $this->belongsToMany(
            Construtora::class,
            'obra_construtora',
            'obra_id',
            'construtora_id'
        );
    }

    public function atualizacoes(): HasMany
    {
        return $this->hasMany(AtualizacaoObra::class, 'obra_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(ObraDocumento::class, 'obra_id')->orderBy('created_at');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(ObraRecebimento::class, 'obra_id')->orderBy('created_at');
    }

    public function colunasPersonalizadas(): HasMany
    {
        return $this->hasMany(ColunaPersonalizada::class, 'obra_id')->orderBy('nome');
    }

    public function controlesNotaFiscal(): HasMany
    {
        return $this->hasMany(ControleNotaFiscal::class, 'obra_id');
    }

    public function entregasContratuais(): HasMany
    {
        return $this->hasMany(ObraEntregaContratual::class, 'obra_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getCustoEstimadoEntregasAttribute(): float
    {
        return (float) $this->entregasContratuais()->sum('custo_estimado');
    }

    public function midias(): MorphMany
    {
        return $this->morphMany(Midia::class, 'mediavel');
    }
}
