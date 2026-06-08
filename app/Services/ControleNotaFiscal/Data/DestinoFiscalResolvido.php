<?php

namespace App\Services\ControleNotaFiscal\Data;

use App\Enums\TipoDestinoFiscal;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;

final readonly class DestinoFiscalResolvido
{
    public function __construct(
        public ?ControleNotaFiscal $controle,
        public TipoDestinoFiscal $tipo,
        public ?AutorizacaoServico $documentoAs = null,
        public ?Asa $documentoAsa = null,
        public ?ControleNotaFiscalItem $itemPrincipal = null,
        public ?ControleNotaFiscalAuxiliar $itemAuxiliar = null,
        public float $saldoDisponivel = 0.0,
        public ?string $motivoBloqueio = null,
    ) {}

    public function bloqueado(): bool
    {
        return $this->motivoBloqueio !== null;
    }

    public function documento(): AutorizacaoServico|Asa|null
    {
        return $this->documentoAs ?? $this->documentoAsa;
    }

    public function item(): ControleNotaFiscalItem|ControleNotaFiscalAuxiliar|null
    {
        return $this->itemPrincipal ?? $this->itemAuxiliar;
    }
}
