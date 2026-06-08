<?php

namespace App\Models;

use App\Models\Obras;
use App\Models\Projeto;
use App\Models\RelatorioFotografico;
use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use App\Traits\TemArquivos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgendaEvent extends Model
{
    use HasFactory;
    use TemArquivos;

    protected $table = 'agenda_events';

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'origin',
        'event_type',
        'status',
        'color',
        'location',
        'responsible_user_id',
        'projeto_id',
        'obra_id',
        'relatorio_visita_tecnica_id',
        'relatorio_fotografico_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function relatorioVisitaTecnica(): BelongsTo
    {
        return $this->belongsTo(RelatorioVisitaTecnica::class);
    }

    public function relatorioFotografico(): BelongsTo
    {
        return $this->belongsTo(RelatorioFotografico::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agenda_event_participant', 'agenda_event_id', 'user_id')
            ->withPivot('status', 'responded_at')
            ->withTimestamps();
    }
}
