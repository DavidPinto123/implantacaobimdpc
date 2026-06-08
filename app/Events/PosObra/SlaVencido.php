<?php

namespace App\Events\PosObra;

use App\Models\PosObra\Pendencia;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaVencido
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Pendencia $pendencia,
        public int $nivelEscalamento, // 1=Lembrete, 2=Atenção, 3=Urgente, 4=Crítico
    ) {}
}
