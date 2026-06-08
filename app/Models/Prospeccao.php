<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospeccao extends Model
{
    use HasFactory;

    protected $table = 'prospeccoes';

    protected $fillable = [
        'projeto_id',
        'etapa_id',
        'nome',
        'sigla',
        'status',
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
        'modelo_entrega_pp',
        'aluguel_cto',
        'luvas',
        'iptu',
        'condominio',
        'configuracao_academia',
        'dados_engenharia',
        'prazo_inicio',
        'projeto_croqui',
        'potencial_alunos',
        'link_estudo_projecao_alunos',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class);
    }

    public function setAreaAcademiaAttribute($value)
    {
        if (! is_null($value)) {
            $valor = str_replace('.', '', $value);       // remove separador de milhar
            $valor = str_replace(',', '.', $valor);      // troca vírgula por ponto
            $this->attributes['area_academia'] = $valor;
        }
    }
}
