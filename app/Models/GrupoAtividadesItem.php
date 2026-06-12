<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoAtividadesItem extends Model
{
    protected $table = 'grupos_atividades_itens';

    protected $fillable = ['grupo_id', 'parent_id', 'titulo', 'descricao', 'ordem', 'duracao_dias'];

    protected $casts = [
        'ordem'        => 'integer',
        'duracao_dias' => 'integer',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoAtividades::class, 'grupo_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GrupoAtividadesItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GrupoAtividadesItem::class, 'parent_id')->orderBy('ordem');
    }
}
