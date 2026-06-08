<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TablePreset extends Model
{
    protected $table = 'table_presets';

    protected $fillable = [
        'table_key',
        'name',
        'hidden_columns',
        'is_global',
        'created_by',
    ];

    protected $casts = [
        'hidden_columns' => 'array',
        'is_global' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
