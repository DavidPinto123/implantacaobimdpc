<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlePedidoItem extends Model
{
    protected $table = 'controle_pedido_itens';

    protected $fillable = [
        'controle_pedido_id',
        'codigo',
        'nome',
        'contratado',
        'valor',
    ];

    protected $casts = [
        'contratado' => 'boolean',
        'valor' => 'float',
    ];

    public function controlePedido()
    {
        return $this->belongsTo(ControlePedido::class);
    }
}
