<?php

namespace App\Services;

use App\Models\ControleNotaFiscalNota;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FinanceiroNotasFiscaisZipService
{
    /**
     * Gera ZIP com os anexos das notas, organizando em pastas
     * `[Unidade] - [Gestor]/`. Quando uma nota possui NF e Boleto, ambos vão
     * para uma subpasta `XXX - [Fornecedor]/`. Em ambos os casos os arquivos
     * mantêm o nome `XXX - [Fornecedor] - NF|BOLETO.ext`.
     *
     * Retorna `null` quando o ZIP não pôde ser criado em disco.
     * Retorna um resultado com `totalArquivos = 0` quando nenhuma nota tinha
     * anexo válido — neste caso o ZIP no disco já foi removido.
     *
     * @param  Collection<int, ControleNotaFiscalNota>  $notas
     */
    public function gerarAgrupado(Collection $notas): ?FinanceiroNotasFiscaisZipResultado
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'anexos-').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);

            return null;
        }

        $disk = Storage::disk(ControleNotaFiscalNota::getUploadDisk());
        $pastasCriadas = [];
        $totalArquivos = 0;
        $idsComArquivo = [];

        foreach ($notas as $nota) {
            $obra = $nota->obra;
            $unidade = $this->sanitize($obra?->unidade, 'Sem unidade');
            $gestor = $this->sanitize($obra?->projeto?->responsavelEng?->name, 'Sem gestor');
            $fornecedor = $this->sanitize($nota->empresa, 'Sem fornecedor');
            $numeroNf = $this->sanitize($nota->numero_nf, (string) $nota->id);

            $pasta = "{$unidade} - {$gestor}";
            if (! isset($pastasCriadas[$pasta])) {
                $zip->addEmptyDir($pasta);
                $pastasCriadas[$pasta] = true;
            }

            $arquivos = [];

            foreach (['arquivo_path' => 'NF', 'boleto_path' => 'BOLETO'] as $field => $sufixo) {
                $path = $nota->{$field};

                if (blank($path)) {
                    continue;
                }

                try {
                    if (! $disk->exists($path)) {
                        continue;
                    }

                    $contents = $disk->get($path);
                } catch (\Throwable) {
                    continue;
                }

                if ($contents === null || $contents === '') {
                    continue;
                }

                $arquivos[$sufixo] = [
                    'contents' => $contents,
                    'ext' => pathinfo($path, PATHINFO_EXTENSION) ?: 'bin',
                ];
            }

            if ($arquivos === []) {
                continue;
            }

            $idsComArquivo[] = (int) $nota->id;

            if (count($arquivos) > 1) {
                $subpasta = "{$pasta}/{$numeroNf} - {$fornecedor}";
                if (! isset($pastasCriadas[$subpasta])) {
                    $zip->addEmptyDir($subpasta);
                    $pastasCriadas[$subpasta] = true;
                }
                $pastaDestino = $subpasta;
            } else {
                $pastaDestino = $pasta;
            }

            foreach ($arquivos as $sufixo => $info) {
                $entryName = sprintf('%s/%s - %s - %s.%s', $pastaDestino, $numeroNf, $fornecedor, $sufixo, $info['ext']);
                $zip->addFromString($entryName, $info['contents']);
                $totalArquivos++;
            }
        }

        $zip->close();

        if ($totalArquivos === 0) {
            @unlink($tmpZip);

            return new FinanceiroNotasFiscaisZipResultado('', [], 0);
        }

        return new FinanceiroNotasFiscaisZipResultado($tmpZip, $idsComArquivo, $totalArquivos);
    }

    /**
     * Remove caracteres inválidos para nomes de arquivos/pastas em sistemas de arquivos comuns.
     */
    protected function sanitize(?string $valor, string $fallback): string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return $fallback;
        }

        $valor = preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]+/u', '-', $valor) ?? $fallback;

        return trim($valor, '-. ') ?: $fallback;
    }
}
