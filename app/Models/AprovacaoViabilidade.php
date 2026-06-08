<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AprovacaoViabilidade extends Model
{
    use HasFactory;

    protected $table = 'aprovacao_viabilidades';

    protected $fillable = [
        'projeto_id',
        'user_id',
        'role',
        'aprovacao',
        'comentarios_gerais',

        // Seções
        'consulta_previa',
        'estudoviabilidade',
        'visita_tecnica',
        'projetos_adicionais',

        // Anexos (JSON)
        'anexo_consulta_previa',
        'anexo_estudoviabilidade',
        'anexo_visita_tecnica',
        'anexo_projetos_adicionais',
        'observacoes_ressalva',
        'anexos_ressalva',
    ];

    protected $casts = [
        'anexos_ressalva' => 'array',
        'anexo_consulta_previa' => 'array',
        'anexo_estudoviabilidade' => 'array',
        'anexo_visita_tecnica' => 'array',
        'anexo_projetos_adicionais' => 'array',
    ];

    /** Relacionamentos */
    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
