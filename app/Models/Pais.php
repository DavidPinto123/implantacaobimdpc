<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $fillable = [
        'nome',
        'iso',
    ];

    public function estados()
    {
        return $this->hasMany(Estado::class);
    }
}
