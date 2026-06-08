<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AprovacaoReuniaoComite extends Model
{
    use HasFactory;

    protected $table = 'aprovacao_reuniao_comite';

    protected $fillable = [
        'projeto_id',
        'user_id',
        'role',
        'aprovacao',
        'comentarios_gerais',
        'observacoes_ressalva',
        'anexos_ressalva',
        'pmo_cronograma',
        'pmo_termo_abertura',
        'comercial_proposta',
        'comercial_contrato',
        'planejamento_plano',
        'planejamento_estudo',
    ];

    protected $casts = [
        'anexos_ressalva' => 'array',
        'pmo_cronograma' => 'boolean',
        'pmo_termo_abertura' => 'boolean',
        'comercial_proposta' => 'boolean',
        'comercial_contrato' => 'boolean',
        'planejamento_plano' => 'boolean',
        'planejamento_estudo' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
