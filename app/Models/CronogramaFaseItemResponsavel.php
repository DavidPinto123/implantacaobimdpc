<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot customizado para cronograma_fase_item_responsaveis.
 * Ao remover um responsável do item (detach), a Task vinculada é deletada automaticamente.
 */
class CronogramaFaseItemResponsavel extends Pivot
{
    protected $table = 'cronograma_fase_item_responsaveis';

    protected static function booted(): void
    {
        static::deleted(function (self $pivot) {
            Task::where('cronograma_fase_item_id', $pivot->cronograma_fase_item_id)
                ->where('assigned_to', $pivot->user_id)
                ->where('eh_revisor', false)
                ->delete();
        });
    }
}
