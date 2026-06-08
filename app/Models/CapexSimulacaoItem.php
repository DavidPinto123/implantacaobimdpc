<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapexSimulacaoItem extends Model
{
    protected $table = 'capex_simulacao_itens';

    protected $fillable = [
        'capex_simulacao_id',
        'as_escopo_id',
        'grupo_oi_id',
        'numero_complemento',
        'tipo',
        'incluir',
        'ordem',
        'nome_escopo',
        'valor_base_m2',
        'valor_base_m2_editado',
        'area',
        'fator_correcao',
        'custo_estimado',
        'percentual',
        'comentario',
    ];

    protected $casts = [
        'incluir' => 'boolean',
        'valor_base_m2_editado' => 'boolean',
    ];

    public function simulacao()
    {
        return $this->belongsTo(CapexSimulacao::class, 'capex_simulacao_id');
    }

    public function escopo()
    {
        return $this->belongsTo(AsEscopo::class, 'as_escopo_id');
    }

    public function grupoOi()
    {
        return $this->belongsTo(GrupoOi::class, 'grupo_oi_id');
    }
}
