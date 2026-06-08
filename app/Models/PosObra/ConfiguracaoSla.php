<?php

namespace App\Models\PosObra;

use App\Enums\PosObra\UrgenciaPendencia;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoSla extends Model
{
    protected $table = 'po_configuracoes_sla';

    protected $fillable = [
        'urgencia',
        'prazo_horas',
        'ativo',
    ];

    protected $casts = [
        'urgencia' => UrgenciaPendencia::class,
        'prazo_horas' => 'integer',
        'ativo' => 'boolean',
    ];
}
