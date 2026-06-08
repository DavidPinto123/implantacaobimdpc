<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RelatorioFotografico extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'status_relatorio',
        'projeto_id',
        'gestor_id',
        'autor_id',
        'status',
        'data_posse',
        'status_termo_de_posse',
        'sigla',
        'tipo_unidade',
        'endereco',
        'entregas_contratuais',
        'fotos',
        'agendado_em',
    ];

    protected $casts = [
        'data_posse' => 'date',
        'agendado_em' => 'datetime',
        'entregas_contratuais' => 'array',
        'fotos' => 'array',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function gestor()
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * Retorna entregas marcadas
     */
    public function entregasMarcadas(): array
    {
        $entregas = $this->entregas_contratuais ?? [];

        return collect($entregas)
            ->filter(fn ($item) => isset($item['check']) && $item['check'] === true)
            ->values()
            ->toArray();
    }
}
