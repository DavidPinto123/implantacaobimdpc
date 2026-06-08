<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegiaoInteresse extends Model
{
    //
    use HasFactory;

    protected $guarded = [];

    public function cidade()
    {
        return $this->belongsTo(Cidade::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }
}
