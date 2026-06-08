<?php

namespace App\Services;

use App\Models\Asa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class AsaPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function getViewData(Asa $asa): array
    {
        $asa->loadMissing([
            'elaboracaoAditivo.construtora',
            'elaboracaoAditivo.obra.projeto.cidade',
            'elaboracaoAditivo.obra.projeto.estado',
            'elaboracaoAditivo.obra.projeto.responsavelEng',
            'projeto',
        ]);

        $construtora = $asa->elaboracaoAditivo?->construtora;
        $obra = $asa->elaboracaoAditivo?->obra;
        $projeto = $obra?->projeto ?? $asa->projeto;
        $total = (float) $asa->valor_total;
        $desconto = max((float) ($asa->as_desconto ?? 0), 0.0);

        return [
            'autorizacaoServico' => new Fluent([
                'data_inicio_servico' => $asa->as_data_inicio,
                'data_termino_servico' => $asa->as_data_termino,
                'data_entrega_material' => $asa->as_data_entrega,
                'numero_as' => $asa->numero_asa,
                'createdBy' => $asa->asCriadaPor,
            ]),
            'asa' => $asa,
            'obra' => $obra,
            'projeto' => $projeto,
            'asEscopo' => null,
            'construtora' => $construtora,
            'gestor' => $this->gestorDaObra($asa),
            'itens' => collect(),
            'itemPrincipal' => null,
            'subtotal' => $total,
            'desconto' => $desconto,
            'total' => max($total - $desconto, 0.0),
            'parcelamento' => $this->parcelamento($asa, max($total - $desconto, 0.0)),
            'totalPorExtenso' => $this->valorPorExtenso(max($total - $desconto, 0.0)),
            'nomeArquivo' => $this->nomeArquivo($asa),
            'revisao' => '00',
            'escopoShellSelecionado' => false,
            'escopoRecheioSelecionado' => true,
            'percentualFaturamentoMaoObra' => 0.0,
            'percentualFaturamentoMaterial' => 0.0,
            'descricaoServicoPdf' => filled($asa->as_descricao_pdf) ? (string) $asa->as_descricao_pdf : null,
            'itensDescricaoServicoPdf' => $this->itensDescricaoServico($asa),
            'quantidadeAnexosEmail' => 0,
            'numeroAs' => $asa->numero_asa,
            'dataInicio' => $asa->as_data_inicio,
            'dataTermino' => $asa->as_data_termino,
            'dataEntrega' => $asa->as_data_entrega,
        ];
    }

    public function generateAndStorePdf(Asa $asa): string
    {
        $path = $this->storagePath($asa);

        ini_set('memory_limit', '1024M');

        $pdf = Pdf::loadView('pdf.autorizacao-servico', $this->getViewData($asa))
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->diskName());
        $disk->put($path, $pdf->output(), ['ContentType' => 'application/pdf']);

        $asa->forceFill(['as_pdf' => $path])->saveQuietly();

        return $path;
    }

    public function storagePath(Asa $asa): string
    {
        return 'autorizacao-servico-adicional/'.$asa->id.'/pdf/'.$this->nomeArquivo($asa);
    }

    public function nomeArquivo(Asa $asa): string
    {
        return sprintf('%s.pdf', $this->sanitizeSegment((string) $asa->numero_asa));
    }

    public function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    protected function gestorDaObra(Asa $asa): ?User
    {
        $gestor = $asa->elaboracaoAditivo?->obra?->projeto?->responsavelEng;

        return $gestor instanceof User ? $gestor : null;
    }

    /**
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>, descricao_imagens: array<int, string>, valor_total: float}>
     */
    protected function itensDescricaoServico(Asa $asa): array
    {
        $itens = $asa->as_itens_descricao_pdf;
        $total = max((float) $asa->valor_total - max((float) ($asa->as_desconto ?? 0), 0.0), 0.0);

        if (is_array($itens) && $itens !== []) {
            $normalizados = collect($itens)
                ->filter(fn (mixed $item): bool => is_array($item))
                ->take(1)
                ->map(fn (array $item): array => [
                    'descricao_tipo' => in_array((string) ($item['descricao_tipo'] ?? ''), ['texto', 'arquivo'], true)
                        ? (string) $item['descricao_tipo']
                        : 'texto',
                    'descricao' => filled($item['descricao'] ?? null) ? (string) $item['descricao'] : null,
                    'descricao_arquivo' => array_values(array_filter((array) ($item['descricao_arquivo'] ?? []))),
                    'descricao_imagens' => [],
                    'valor_total' => (float) $asa->valor_total - max((float) ($asa->as_desconto ?? 0), 0.0),
                ])
                ->filter(fn (array $item): bool => filled($item['descricao']) || $item['descricao_arquivo'] !== [])
                ->values()
                ->all();

            if ($normalizados !== []) {
                return $normalizados;
            }
        }

        $descricao = filled($asa->as_descricao_pdf)
            ? (string) $asa->as_descricao_pdf
            : ($asa->descricao ?: 'EXECUÇÃO DE OBRA CIVIL - ADICIONAL');

        return [[
            'descricao_tipo' => 'texto',
            'descricao' => $descricao,
            'descricao_arquivo' => [],
            'descricao_imagens' => [],
            'valor_total' => $total,
        ]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $parcelamento
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    protected function parcelamento(Asa $asa, float $total): array
    {
        $parcelamento = $asa->as_parcelamento;

        if (is_array($parcelamento) && $parcelamento !== []) {
            return collect($parcelamento)
                ->filter(fn (mixed $p): bool => is_array($p))
                ->map(fn (array $p, int $i): array => [
                    'parcela' => filled($p['parcela'] ?? null)
                        ? (string) $p['parcela']
                        : 'Parcela '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'percentual' => (float) ($p['percentual'] ?? 0),
                    'valor' => (float) ($p['valor'] ?? 0),
                    'observacao' => (string) ($p['observacao'] ?? ''),
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

    protected function sanitizeSegment(string $value): string
    {
        $segment = (string) Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9._-]+/', '-')
            ->trim('-');

        return $segment !== '' ? $segment : 'SEM-NUMERO';
    }
}
