<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Projeto;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'task_category_id',
        'sigla',
        'marca_id',
        'created_by',
        'assigned_to',
        'prazo',
        'inicio',
        'termino_programado',
        'data_entrega',
        'status',
        'setor_id',
        'dias_corridos',
        'projeto_id',
        'cronograma_fase_item_id',
        'eh_revisor',
    ];

    protected $casts = [
        'inicio' => 'date',
        'termino_programado' => 'date',
        'data_entrega' => 'datetime',
        'dias_corridos' => 'boolean',
        'eh_revisor' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function responsaveis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withTimestamps();
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    protected static function booted()
    {
        static::creating(function (self $task) {
            $user = auth()->user();

            $task->created_by ??= $user?->id;
            $task->assigned_to ??= $user?->id;

            $task->termino_programado = self::calcularTerminoProgramadoData(
                $task->inicio,
                $task->prazo,
                $task->dias_corridos
            );
        });

        static::updating(function (self $task) {
            if ($task->isDirty(['inicio', 'prazo', 'dias_corridos'])) {
                $task->termino_programado = self::calcularTerminoProgramadoData(
                    $task->inicio,
                    $task->prazo,
                    $task->dias_corridos
                );
            }
        });

        static::saving(function (self $task) {
            if (! $task->isDirty('status')) {
                return;
            }

            if ($task->status === 'concluida') {
                $task->data_entrega = now();
            }
        });
    }

    public static function calcularTerminoProgramadoData($inicio, $prazo, $diasCorridos): ?string
    {
        if (! $inicio || $prazo === null || $prazo === '') {
            return null;
        }

        $dias = (int) $prazo;

        if ($dias <= 0) {
            return null;
        }

        $data = Carbon::parse($inicio);
        $diasCorridos = (bool) $diasCorridos;

        if ($diasCorridos) {
            $termino = $data->copy()->addDays($dias - 1);
        } else {
            if ($data->isWeekend()) {
                $data = $data->nextWeekday();
            }

            $termino = $data->copy()->addWeekdays($dias - 1);
        }

        return $termino->toDateString();
    }
}
