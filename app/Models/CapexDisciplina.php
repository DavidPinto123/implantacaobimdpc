<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapexDisciplina extends Model
{
    protected $fillable = [
        'nome',
        'parent_id',
        'tipo_calculo',
        'valor_base',
        'usa_fator_correcao',
        'ativo',
        'consideracoes',
    ];

    protected $casts = [
        'valor_base' => 'float',
        'usa_fator_correcao' => 'boolean',
        'ativo' => 'boolean',
    ];

    // 🔹 Pai
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // 🔹 Filhos
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // 🔹 Apenas principais
    public function scopePrincipais($query)
    {
        return $query->whereNull('parent_id');
    }
}
