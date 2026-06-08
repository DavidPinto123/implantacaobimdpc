<?php

namespace App\Observers\PosObra;

use App\Events\PosObra\PendenciaRegistrada;
use App\Models\PosObra\Pendencia;
use App\Services\PosObra\PendenciaService;

class PendenciaObserver
{
    public function __construct(private PendenciaService $service) {}

    public function creating(Pendencia $pendencia): void
    {
        if (empty($pendencia->codigo)) {
            $pendencia->codigo = $this->service->gerarCodigo();
        }
    }

    public function created(Pendencia $pendencia): void
    {
        event(new PendenciaRegistrada($pendencia));
    }
}
