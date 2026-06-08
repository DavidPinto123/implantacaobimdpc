<?php

namespace App\Models\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Models\Construtora;
use App\Models\Obras;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendencia extends Model
{
    protected $table = 'po_pendencias';

    protected $fillable = [
        'codigo',
        'obras_id',
        'construtora_id',
        'lider_obra_id',
        'gestor_id',
        'disciplina_config_id',
        'ticket',
        'descricao',
        'observacoes',
        'urgencia',
        'status',
        'data_inicio',
        'data_termino',
        'data_conclusao',
        'impacto_operacao',
        'local_especifico',
    ];

    protected $casts = [
        'urgencia' => UrgenciaPendencia::class,
        'status' => StatusPendencia::class,
        'data_inicio' => 'date',
        'data_termino' => 'date',
        'data_conclusao' => 'datetime',
        'impacto_operacao' => 'boolean',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obras_id');
    }

    public function construtora(): BelongsTo
    {
        return $this->belongsTo(Construtora::class, 'construtora_id');
    }

    public function liderObra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lider_obra_id');
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(DisciplinaConfig::class, 'disciplina_config_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoPendencia::class, 'pendencia_id');
    }

    public function atualizacoesStatus(): HasMany
    {
        return $this->hasMany(AtualizacaoStatus::class, 'pendencia_id');
    }

    public function aprovacaoFinalizacao(): HasOne
    {
        return $this->hasOne(AprovacaoFinalizacao::class, 'pendencia_id')->latest();
    }

    public function mensagensWhatsapp(): HasMany
    {
        return $this->hasMany(MensagemWhatsapp::class, 'pendencia_id');
    }

    public function estaAtrasada(): bool
    {
        return $this->data_termino !== null
            && $this->data_termino->isPast()
            && ! $this->status->isTerminal();
    }
}
