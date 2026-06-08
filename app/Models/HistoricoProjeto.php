<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HistoricoProjeto extends Model
{
    use HasFactory;

    protected $fillable = [
        'projeto_id',
        'usuario_id',
        'setor',
        'status',
        'fase',
        'etapa',
        'status_antigo',
        'status_novo',
        'fase_antiga',
        'fase_nova',
        'acao',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getDescricaoFormatadaAttribute()
    {
        $usuario = $this->usuario->name ?? 'Usuário';
        $setor = $this->usuario->setor ?? 'Setor';
        $projeto = $this->projeto->nome ?? 'Projeto';

        $formatBadge = function ($status) {
            $statusKey = str_replace(' ', '_', Str::lower($status));

            $label = match ($statusKey) {
                'Fase de Projeto' => 'Fase de Projeto',
                'Em obra' => 'Em obra',
                'Inaugurada' => 'Inaugurada',
                default => ucfirst(str_replace('_', ' ', $status)),

            };

            $color = match ($statusKey) {
                // 'novo' => ' #3b82f6;',
                'Em obra' => '#facc15',
                'Inaugurada' => '#10b981',
                'Fase de Projeto' => '#ef4444',
                default => '#9ca3af',
            };

            $text = ucfirst(str_replace('_', ' ', $statusKey));

            return '<span class="inline-block px-2 py-1 text-xs font-semibold rounded-full text-white" style="background-color: '.$color.'">'.e($label).'</span>';
        };

        return match ($this->acao) {
            'criado' => "$usuario do setor $setor criou o Projeto <strong>$projeto</strong> com o status {$formatBadge($this->status)}",
            'alterou_status' => "$usuario do setor $setor alterou o status do Projeto $projeto de {$formatBadge($this->status_antigo)} para {$formatBadge($this->status_novo)}",
            'alterou_fase' => "$usuario do setor $setor alterou a fase do Projeto $projeto de <strong>{$this->fase_antiga}</strong> para <strong>{$this->fase_nova}</strong>",
            'adicionou_usuario' => "$usuario do setor $setor adicionou o usuário <strong>{$this->alvo}</strong> ao projeto <strong>$projeto</strong>",
            'iniciou_analise' => "$usuario do setor $setor iniciou a análise do Projeto $projeto",
            default => "$usuario do setor $setor realizou uma ação no Projeto $projeto",
        };
    }
}
