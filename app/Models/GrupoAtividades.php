<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoAtividades extends Model
{
    protected $table = 'grupos_atividades';

    protected $fillable = ['nome', 'descricao', 'criado_por'];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function itensRaiz(): HasMany
    {
        return $this->hasMany(GrupoAtividadesItem::class, 'grupo_id')
            ->whereNull('parent_id')
            ->orderBy('ordem');
    }

    public function todosItens(): HasMany
    {
        return $this->hasMany(GrupoAtividadesItem::class, 'grupo_id')->orderBy('ordem');
    }
}
