<?php

namespace App\Services;

use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscalItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class AutorizacaoServicoPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function getViewData(AutorizacaoServico $autorizacaoServico): array
    {
        $autorizacaoServico->loadMissing([
            'asEscopo',
            'construtora',
            'createdBy',
            'obra.projeto.cidade',
            'obra.projeto.estado',
            'obra.projeto.responsavelEng',
            'itens.asEscopo',
            'itens.controleNotaFiscal.obra',
        ]);

        $itens = $autorizacaoServico->itens;
        $itemPrincipal = $this->itemPrincipal($autorizacaoServico);
        $escopoShellSelecionado = $this->itemPertenceAoShell($autorizacaoServico, $itemPrincipal);
        $subtotal = (float) ($autorizacaoServico->valor_estimado ?: $autorizacaoServico->valor);
        $desconto = max((float) ($autorizacaoServico->desconto_autorizacao_servico ?? 0), 0.0);
        $total = (float) $autorizacaoServico->valor;

        return [
            'autorizacaoServico' => $autorizacaoServico,
            'obra' => $autorizacaoServico->obra,
            'projeto' => $autorizacaoServico->obra?->projeto,
            'asEscopo' => $autorizacaoServico->asEscopo,
            'construtora' => $autorizacaoServico->construtora,
            'gestor' => $this->gestorDaObra($autorizacaoServico),
            'itens' => $itens,
            'itemPrincipal' => $itemPrincipal,
            'subtotal' => $subtotal,
            'desconto' => $desconto,
            'total' => $total,
            'parcelamento' => $this->parcelamento($autorizacaoServico, $total),
            'totalPorExtenso' => $this->valorPorExtenso($total),
            'nomeArquivo' => $this->nomeArquivo($autorizacaoServico),
            'revisao' => $this->revisao($autorizacaoServico),
            'escopoShellSelecionado' => $escopoShellSelecionado,
            'escopoRecheioSelecionado' => ! $escopoShellSelecionado,
            'percentualFaturamentoMaoObra' => (float) ($itemPrincipal?->percentual_faturamento_mao_obra ?? 0.0),
            'percentualFaturamentoMaterial' => (float) ($itemPrincipal?->percentual_faturamento_material ?? 0.0),
            'descricaoServicoPdf' => filled($autorizacaoServico->descricao_servico_pdf)
                ? (string) $autorizacaoServico->descricao_servico_pdf
                : null,
            'itensDescricaoServicoPdf' => $this->itensDescricaoServico($autorizacaoServico, $itemPrincipal),
            'quantidadeAnexosEmail' => $this->quantidadeAnexosEmail($autorizacaoServico),
        ];
    }

    public function makePdf(AutorizacaoServico $autorizacaoServico)
    {
        ini_set('memory_limit', '1024M');

        return Pdf::loadView('pdf.autorizacao-servico', $this->getViewData($autorizacaoServico))
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);
    }

    public function generateAndStorePdf(AutorizacaoServico $autorizacaoServico): string
    {
        $path = $this->storagePath($autorizacaoServico);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->diskName());
        $disk->put($path, $this->makePdf($autorizacaoServico)->output(), [
            'ContentType' => 'application/pdf',
        ]);

        $autorizacaoServico->forceFill([
            'anexo_autorizacao_servico' => $path,
        ])->saveQuietly();

        return $path;
    }

    public function storagePath(AutorizacaoServico $autorizacaoServico): string
    {
        return 'autorizacao-servico/'.$autorizacaoServico->id.'/pdf/'.$this->nomeArquivo($autorizacaoServico);
    }

    public function nomeArquivo(AutorizacaoServico $autorizacaoServico): string
    {
        return sprintf(
            '%s.pdf',
            $this->sanitizeSegment((string) $autorizacaoServico->numero_as),
        );
    }

    public function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    public function revisao(AutorizacaoServico $autorizacaoServico): string
    {
        return filled($autorizacaoServico->numero_complemento)
            ? (string) $autorizacaoServico->numero_complemento
            : '00';
    }

    public function gestorDaObra(AutorizacaoServico $autorizacaoServico): ?User
    {
        $gestorProjeto = $autorizacaoServico->obra?->projeto?->responsavelEng;

        return $gestorProjeto instanceof User ? $gestorProjeto : null;
    }

    protected function sanitizeSegment(string $value): string
    {
        $segment = (string) Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9._-]+/', '-')
            ->trim('-');

        return $segment !== '' ? $segment : 'SEM-NUMERO';
    }

    protected function itemPertenceAoShell(AutorizacaoServico $autorizacaoServico, ?ControleNotaFiscalItem $itemPrincipal): bool
    {
        $grupo = $itemPrincipal?->grupo
            ?: $itemPrincipal?->asEscopo?->grupo
            ?: $autorizacaoServico->asEscopo?->grupo;

        return Str::of((string) $grupo)->trim()->lower()->toString() === 'shell';
    }

    protected function itemPrincipal(AutorizacaoServico $autorizacaoServico): ?ControleNotaFiscalItem
    {
        $itens = $autorizacaoServico->itens;

        if ($itens->isEmpty()) {
            return null;
        }

        $asEscopoId = (int) ($autorizacaoServico->as_escopo_id ?? 0);
        $numeroComplemento = (string) ($autorizacaoServico->numero_complemento ?? '');

        return $itens->first(
            fn (ControleNotaFiscalItem $item): bool => (int) ($item->as_escopo_id ?? 0) === $asEscopoId
                && (string) ($item->numero_complemento ?? '') === $numeroComplemento,
        )
            ?? $itens->first(fn (ControleNotaFiscalItem $item): bool => (int) ($item->as_escopo_id ?? 0) === $asEscopoId)
            ?? $itens->first();
    }

    protected function quantidadeAnexosEmail(AutorizacaoServico $autorizacaoServico): int
    {
        $paths = array_values(array_filter((array) $autorizacaoServico->anexos_autorizacao_servico));

        if ($paths === []) {
            return 0;
        }

        $disk = Storage::disk($this->diskName());

        return collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && $disk->exists($path))
            ->count();
    }

    /**
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>, descricao_imagens: array<int, string>, valor_total: float}>
     */
    protected function itensDescricaoServico(AutorizacaoServico $autorizacaoServico, ?ControleNotaFiscalItem $itemPrincipal): array
    {
        $itensDescricao = $autorizacaoServico->itens_descricao_servico_pdf;

        if (is_array($itensDescricao) && $itensDescricao !== []) {
            $normalizados = collect($itensDescricao)
                ->filter(fn (mixed $item): bool => is_array($item))
                ->take(1)
                ->map(function (array $item) use ($autorizacaoServico): array {
                    $descricaoArquivo = array_values(array_filter((array) ($item['descricao_arquivo'] ?? [])));
                    $descricaoTipo = in_array((string) ($item['descricao_tipo'] ?? ''), ['texto', 'arquivo'], true)
                        ? (string) $item['descricao_tipo']
                        : ($descricaoArquivo === [] ? 'texto' : 'arquivo');

                    return [
                        'descricao_tipo' => $descricaoTipo,
                        'descricao' => filled($item['descricao'] ?? null) ? (string) $item['descricao'] : null,
                        'descricao_arquivo' => $descricaoArquivo,
                        'descricao_imagens' => collect($descricaoArquivo)
                            ->map(fn (mixed $path): ?string => is_string($path) ? $this->imagemDescricaoDataUri($path) : null)
                            ->filter()
                            ->values()
                            ->all(),
                        'valor_total' => (float) $autorizacaoServico->valor,
                    ];
                })
                ->filter(fn (array $item): bool => filled($item['descricao']) || $item['descricao_arquivo'] !== [])
                ->values()
                ->all();

            if ($normalizados !== []) {
                return $normalizados;
            }
        }

        $descricao = filled($autorizacaoServico->descricao_servico_pdf)
            ? (string) $autorizacaoServico->descricao_servico_pdf
            : ($itemPrincipal?->escopo_complementar ?: ($itemPrincipal?->escopo ?: ($autorizacaoServico->asEscopo?->escopo ?: 'EXECUÇÃO DE OBRA CIVIL - RECHEIO')));

        return [[
            'descricao_tipo' => 'texto',
            'descricao' => $descricao,
            'descricao_arquivo' => [],
            'descricao_imagens' => [],
            'valor_total' => (float) $autorizacaoServico->valor,
        ]];
    }

    protected function imagemDescricaoDataUri(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $disk = Storage::disk($this->diskName());

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $mimeType = $disk->mimeType($path) ?: match (Str::of($path)->lower()->afterLast('.')->toString()) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            default => 'image/png',
        };

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    /**
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    protected function parcelamento(AutorizacaoServico $autorizacaoServico, float $total): array
    {
        $parcelamento = $autorizacaoServico->parcelamento_autorizacao_servico;

        if (is_array($parcelamento) && $parcelamento !== []) {
            return collect($parcelamento)
                ->filter(fn (mixed $parcela): bool => is_array($parcela))
                ->map(fn (array $parcela, int $indice): array => [
                    'parcela' => filled($parcela['parcela'] ?? null)
                        ? (string) $parcela['parcela']
                        : 'Parcela '.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                    'percentual' => (float) ($parcela['percentual'] ?? 0),
                    'valor' => (float) ($parcela['valor'] ?? 0),
                    'observacao' => (string) ($parcela['observacao'] ?? ''),
                ])
                ->values()
                ->all();
        }

        return [[
            'parcela' => 'Parcela 01',
            'percentual' => $total > 0 ? 100.0 : 0.0,
            'valor' => $total,
            'observacao' => '>> FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) DPC',
        ]];
    }

    protected function valorPorExtenso(float $valor): string
    {
        return Str::of(Number::currency($valor, in: 'BRL', locale: 'pt_BR'))
            ->replace('R$', '')
            ->trim()
            ->prepend('VALOR GLOBAL NEGOCIADO: ')
            ->toString();
    }
}
