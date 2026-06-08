<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reuniao extends Model
{
    protected $fillable = [
        'titulo',
        'data',
        'hora',
        'tipo',
        'convidados',
        'link_video',
        'local',
        'descricao',
    ];

    /*
    public function projetos()
    {
        return $this->belongsToMany(Projeto::class, 'reuniao_projeto');
    }
    */
    public function projetos()
    {
        return $this->belongsToMany(Projeto::class, 'reuniao_projeto')
            ->withPivot(['status', 'corretor'])
            ->withTimestamps();
    }
}
