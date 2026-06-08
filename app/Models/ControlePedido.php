<?php

namespace App\Models;

use App\Observers\ControlePedidoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ControlePedidoObserver::class)]
class ControlePedido extends Model
{
    protected $fillable = [
        'projeto_id',
        'elaboracao_contrato',
        'cnpj',
        'status',
        'contratacao',
        'observacoes',

        'instal_ar',
        'luminarias',
        'instal_aquecedores',
        'fachada',
        'marcenaria',
        'construtora_sugestao',
        'divisorias',
        'construtora_id',
        'contr_ar',
        'ginastica',

        'valor_oi',
        'valor_realizado',
        'realizado_nf',
        'saldo',

        'situacao',
        'responsavel_orc',
        'gestor_obra',
        'tamanho',
        'numero',

        'pedidos',
    ];

    protected $casts = [
        'pedidos' => 'array',
        'elaboracao_contrato' => 'date',
        'contratacao' => 'date',
        'valor_oi' => 'decimal:2',
        'valor_realizado' => 'decimal:2',
        'realizado_nf' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function responsavelOrc()
    {
        return $this->belongsTo(User::class, 'responsavel_orc');
    }

    public function gestorObra()
    {
        return $this->belongsTo(User::class, 'gestor_obra');
    }

    public function construtora()
    {
        return $this->belongsTo(Construtora::class);
    }

    public function itens()
    {
        return $this->hasMany(ControlePedidoItem::class);
    }
}
