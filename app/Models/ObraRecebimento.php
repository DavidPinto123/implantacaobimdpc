<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObraRecebimento extends Model
{
    public const ITENS_PADRAO = [
        'Aquecedores',
        'Ar condicionado',
        'Divisórias',
        'Espaldar',
        'Chuveiro',
        'Secador de mãos',
        'Bebedouro acessível',
        'Bebedouro industrial',
        'Balança',
        'Piso emborrachado',
        'Piso vinílico',
        'Kit enxoval',
        'Eletrodomésticos',
        'Relógio digital',
        'Piso drenante',
        'Porcelanato',
        'Desfribilador',
        'Luminárias',
    ];

    protected $table = 'obra_recebimentos';

    protected $fillable = [
        'obra_id',
        'construtora_id',
        'nome',
        'status',
        'foto_entrega_path',
        'foto_entrega_nome',
        'foto_entrega_paths',
        'foto_entrega_nomes',
        'nota_fiscal_path',
        'nota_fiscal_nome',
        'nota_fiscal_paths',
        'nota_fiscal_nomes',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'foto_entrega_paths' => 'array',
            'foto_entrega_nomes' => 'array',
            'nota_fiscal_paths' => 'array',
            'nota_fiscal_nomes' => 'array',
        ];
    }

    public function construtora(): BelongsTo
    {
        return $this->belongsTo(Construtora::class, 'construtora_id');
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getFotoEntregaPathsResolvedAttribute(): array
    {
        if (is_array($this->foto_entrega_paths) && $this->foto_entrega_paths !== []) {
            return array_values(array_filter($this->foto_entrega_paths));
        }

        return filled($this->foto_entrega_path) ? [$this->foto_entrega_path] : [];
    }

    public function getFotoEntregaNomesResolvedAttribute(): array
    {
        if (is_array($this->foto_entrega_nomes) && $this->foto_entrega_nomes !== []) {
            return array_values(array_filter($this->foto_entrega_nomes));
        }

        if (filled($this->foto_entrega_nome)) {
            return [$this->foto_entrega_nome];
        }

        return filled($this->foto_entrega_path) ? [basename($this->foto_entrega_path)] : [];
    }

    public function getNotaFiscalPathsResolvedAttribute(): array
    {
        if (is_array($this->nota_fiscal_paths) && $this->nota_fiscal_paths !== []) {
            return array_values(array_filter($this->nota_fiscal_paths));
        }

        return filled($this->nota_fiscal_path) ? [$this->nota_fiscal_path] : [];
    }

    public function getNotaFiscalNomesResolvedAttribute(): array
    {
        if (is_array($this->nota_fiscal_nomes) && $this->nota_fiscal_nomes !== []) {
            return array_values(array_filter($this->nota_fiscal_nomes));
        }

        if (filled($this->nota_fiscal_nome)) {
            return [$this->nota_fiscal_nome];
        }

        return filled($this->nota_fiscal_path) ? [basename($this->nota_fiscal_path)] : [];
    }

    public function hasFotoEntrega(): bool
    {
        return $this->foto_entrega_paths_resolved !== [];
    }

    public function hasNotaFiscal(): bool
    {
        return $this->nota_fiscal_paths_resolved !== [];
    }
}
