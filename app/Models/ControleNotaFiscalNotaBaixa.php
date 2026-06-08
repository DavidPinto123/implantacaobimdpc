<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControleNotaFiscalNotaBaixa extends Model
{
    protected $fillable = [
        'controle_nota_fiscal_nota_id',
        'user_id',
        'baixado_em',
    ];

    protected function casts(): array
    {
        return [
            'baixado_em' => 'datetime',
        ];
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscalNota::class, 'controle_nota_fiscal_nota_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
