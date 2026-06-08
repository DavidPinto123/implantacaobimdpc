<?php

namespace App\Events\PosObra;

use App\Models\PosObra\Pendencia;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrazoInformado
{
    use Dispatchable, SerializesModels;

    public function __construct(public Pendencia $pendencia) {}
}
