<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
    protected $table = 'setores';

    protected $fillable = [
        'setor',
    ];

    public function projetos()
    {
        return $this->belongsToMany(Projeto::class, 'projeto_setor');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'setor_user');
    }
}
