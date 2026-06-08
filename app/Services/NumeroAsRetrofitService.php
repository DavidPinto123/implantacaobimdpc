<?php

namespace App\Services;

use App\Models\AsEscopo;
use App\Models\ControleNotaFiscal;
use App\Models\Obras;

class NumeroAsRetrofitService
{
    public function gerar(Obras $obra, ControleNotaFiscal $controle, int $asEscopoId, ?string $fornecedor = null): string
    {
        $asEscopo = AsEscopo::find($asEscopoId);

        if (! $asEscopo) {
            return 'RET-SEM-AS';
        }

        $sigla = $this->normalizeSegmento($obra->sigla, 'SEM SIGLA');
        $numeroAs = $this->normalizeSegmento($asEscopo->numero_as, 'SEM AS');
        $escopo = $this->normalizeSegmento($asEscopo->escopo, 'SEM ESCOPO');
        $fornecedorSegment = $this->normalizeSegmento($fornecedor, 'SEM FORNECEDOR');
        $unidade = $this->normalizeSegmento($controle->unidade, 'SEM UNIDADE');

        return implode('-', array_filter([
            $sigla,
            'SF',
            'RET',
            $numeroAs,
            $unidade,
            $escopo,
            $fornecedorSegment,
        ]));
    }

    private function normalizeSegmento(?string $valor, string $fallback): string
    {
        if (blank($valor)) {
            return $fallback;
        }

        $normalized = trim((string) $valor);
        return $normalized !== '' ? $normalized : $fallback;
    }
}
