<?php

namespace App\Models;

use App\Enums\MotivoAlteracaoObra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaFaseHistorico extends Model
{
    protected $table = 'cronograma_fase_historicos';

    protected $fillable = [
        'projeto_id',
        'cronograma_fase_id',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'motivo',
        'motivo_codigo',
        'motivo_historico',
        'usuario_id',
        'automatico',
    ];

    protected $casts = [
        'automatico' => 'boolean',
        'motivo_codigo' => MotivoAlteracaoObra::class,
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function cronogramaFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaFase::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
