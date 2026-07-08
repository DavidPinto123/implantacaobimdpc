<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtaTema extends Model
{
    protected $table = 'ata_temas';

    protected $fillable = ['ata_id', 'titulo', 'descricao', 'ordem'];

    protected $casts = ['ordem' => 'integer'];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AtaAnexo::class, 'tema_id')->orderBy('ordem');
    }
}
