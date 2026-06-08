<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    protected $fillable = [
        'numero',
        'fornecedor',
        'cnpj',
        'valor',
        'data_emissao',
        'data_recebimento',
        'data_envio',
        'status',
        'arquivo',
        'observacoes',
        'obra_id',
        // 'tipos_faturamento',
    ];

    protected $casts = [
        'tipos_faturamento' => 'array',
    ];

    public function tiposFaturamento()
    {
        return $this->belongsToMany(TipoFaturamento::class, 'nota_fiscal_tipo_faturamento');
    }

    public function obra()
    {
        return $this->belongsTo(GestaoObra::class);
    }

    public function faturamentos()
    {
        return $this->hasMany(Faturamento::class);
    }

    public function faturamentosMaoObra()
    {
        return $this->hasMany(Faturamento::class)->where('tipo', 'mao_obra');
    }

    public function faturamentosMaterial()
    {
        return $this->hasMany(Faturamento::class)->where('tipo', 'material');
    }
}
