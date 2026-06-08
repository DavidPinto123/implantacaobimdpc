<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElaboracaoAditivo extends Model
{
    protected $table = 'elaboracao_aditivos';

    protected $fillable = [
        'user_id',
        'construtora_id',
        'gestor_id',
        'data',
        'ref_servico',
        'justificativa',
        'anexos',
        'foto_antes',
        'foto_depois',
        'projeto_orcado',
        'projeto_revisado',
        'escopo_contratado',
        'escopo_real',
        'as_escopo_id',
        'obra_id',
        'status_fluxo',
        'justificativa_reprovacao_gestor',
        'justificativa_reprovacao_orcamento',
        'aprovado_gestor_por_id',
        'aprovado_gestor_em',
        'aprovado_orcamento_por_id',
        'aprovado_orcamento_em',
    ];

    protected $casts = [
        'data' => 'date',
        'anexos' => 'array',
        'foto_antes' => 'array',
        'foto_depois' => 'array',
        'projeto_orcado' => 'array',
        'projeto_revisado' => 'array',
        'escopo_contratado' => 'array',
        'escopo_real' => 'array',
        'aprovado_gestor_em' => 'datetime',
        'aprovado_orcamento_em' => 'datetime',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(ElaboracaoAditivoItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function construtora(): BelongsTo
    {
        return $this->belongsTo(Construtora::class);
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function asEscopo(): BelongsTo
    {
        return $this->belongsTo(AsEscopo::class, 'as_escopo_id');
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id')->withoutGlobalScopes();
    }

    public function aprovadorGestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_gestor_por_id');
    }

    public function aprovadorOrcamento(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_orcamento_por_id');
    }

    public function controlesNotaFiscal(): HasMany
    {
        return $this->hasMany(ControleNotaFiscal::class);
    }
}
