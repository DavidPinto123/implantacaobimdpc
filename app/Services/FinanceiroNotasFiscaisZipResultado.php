<?php

namespace App\Services;

/**
 * Resultado da geração do ZIP de notas fiscais.
 *
 * - `caminho` aponta para o arquivo temporário gerado.
 * - `idsComArquivo` lista as notas que tiveram pelo menos um anexo adicionado.
 * - `totalArquivos` soma os anexos efetivamente incluídos no ZIP.
 */
readonly class FinanceiroNotasFiscaisZipResultado
{
    /**
     * @param  array<int, int>  $idsComArquivo
     */
    public function __construct(
        public string $caminho,
        public array $idsComArquivo,
        public int $totalArquivos,
    ) {}

    public function vazio(): bool
    {
        return $this->totalArquivos === 0;
    }
}
