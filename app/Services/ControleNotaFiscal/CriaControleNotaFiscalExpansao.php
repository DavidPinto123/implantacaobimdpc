<?php

namespace App\Services\ControleNotaFiscal;

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\Obras;

class CriaControleNotaFiscalExpansao
{
    public function __construct(
        protected PreencheEscoposPadraoControleNotaFiscal $preencheEscoposPadrao,
    ) {}

    public function handle(Obras $obra): ControleNotaFiscal
    {
        $controle = ControleNotaFiscal::query()->firstOrCreate(
            [
                'obra_id' => $obra->id,
                'tipo_unidade' => TipoUnidade::EXPANSAO->value,
            ],
            [
                'status' => ControleNotaFiscal::STATUS_ATIVO,
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
                'endereco' => $obra->endereco,
            ],
        );

        if ($controle->wasRecentlyCreated) {
            $this->preencheEscoposPadrao->handle($controle);
        }

        return $controle;
    }
}
