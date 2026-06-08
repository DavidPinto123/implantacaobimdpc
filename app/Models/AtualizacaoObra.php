<?php

namespace App\Models;

use App\Enums\CategoriaAtualizacaoObra;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtualizacaoObra extends Model
{
    use HasFactory;

    protected $table = 'atualizacoes_obra';

    protected $fillable = [
        'obra_id',
        'usuario_id',
        'parent_id',
        'categoria',
        'titulo',
        'conteudo',
        'mencoes',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'fixado',
        'automatico',
    ];

    protected $casts = [
        'categoria' => CategoriaAtualizacaoObra::class,
        'mencoes' => 'array',
        'fixado' => 'boolean',
        'automatico' => 'boolean',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function usuariosMencionados()
    {
        if (empty($this->mencoes)) {
            return collect();
        }

        return User::whereIn('id', $this->mencoes)->get();
    }
}
