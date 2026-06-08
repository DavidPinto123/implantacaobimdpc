<?php

namespace App\Services;

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Exports\ElaboracaoAditivoPlanilhaExport;
use App\Models\Asa;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AsaService
{
    private const MEDIA_FIELD_DIRECTORIES = [
        'evidencias' => 'evidencias',
        'foto_antes' => 'foto-antes',
        'foto_depois' => 'foto-depois',
        'projeto_orcado' => 'projeto-orcado',
        'projeto_revisado' => 'projeto-revisado',
        'escopo_contratado' => 'escopo-contratado',
        'escopo_real' => 'escopo-real',
    ];

    public function criarAPartirDoAditivo(ElaboracaoAditivo $aditivo, ?string $justificativa = null): Asa
    {
        $aditivo->load([
            'obra',
            'gestor',
            'construtora',
            'asEscopo',
            'itens',
            'user',
        ]);

        $obra = $aditivo->obra;
        $escopo = $aditivo->asEscopo;
        $valorBruto = (float) $aditivo->itens->sum('valor_total_geral');
        [$gestor, $gestorNome] = $this->resolveGestorFromAditivo($aditivo, $obra?->engenharia);
        // A origem da alteracao e definida posteriormente pelo gestor.
        $origemAlteracao = null;
        $numeroAsa = $this->gerarNumeroAsaEstruturado(
            siglaUnidade: $obra?->sigla,
            origemAlteracao: $origemAlteracao,
            numeroAsReferencia: $escopo?->numero_as,
            nomeUnidade: $obra?->unidade,
            escopo: $escopo?->escopo,
            fornecedor: $aditivo->construtora?->nome,
        );
        $solicitante = $this->resolveSolicitanteFromAditivo($aditivo);

        return DB::transaction(function () use ($aditivo, $obra, $escopo, $valorBruto, $gestor, $gestorNome, $origemAlteracao, $numeroAsa, $justificativa, $solicitante) {
            $asa = Asa::create([
                'numero_asa' => $numeroAsa,
                'projeto_id' => $obra?->projeto_id,
                'sigla' => $obra?->sigla,
                'endereco' => $obra?->endereco,
                'contrato' => $origemAlteracao,
                'subgrupo' => $escopo?->grupo,
                'status' => AsStatus::SOLICITADO,
                'codigo_as_emitida' => $escopo?->numero_as,
                'data_solicitacao' => $aditivo->data,
                'data_aprovacao' => null,
                // Mantemos o campo por obrigatoriedade no banco, mas ele nao e mais exibido na UI.
                'objeto' => $escopo?->escopo ?? 'Sem escopo definido',
                'justificativa' => $justificativa ?? $aditivo->justificativa,
                'altera_prazo' => null,
                'dias_prazo' => null,
                'valor_bruto' => $valorBruto,
                'desconto' => 0,
                'valor_total' => $valorBruto,
                'evidencias' => $aditivo->anexos,
                'observacoes' => null,
                'gestor_id' => $gestor?->id,
                'gestor_nome' => $gestorNome,
                'solicitante' => $solicitante,
                'planilha_apresentada' => null,
                'foto_antes' => $aditivo->foto_antes ?? null,
                'foto_depois' => $aditivo->foto_depois ?? null,
                'projeto_orcado' => $aditivo->projeto_orcado ?? null,
                'projeto_revisado' => $aditivo->projeto_revisado ?? null,
                'escopo_contratado' => $aditivo->escopo_contratado ?? null,
                'escopo_real' => $aditivo->escopo_real ?? null,
                'descricao' => $escopo?->escopo,
                'elaboracao_aditivo_id' => $aditivo->id,
            ]);

            $itens = $aditivo->itens->map(function ($item) {
                return [
                    'item' => $item->item,
                    'descricao' => $item->descricao_servico,
                    'unidade' => $item->unidade,
                    'quantidade' => (float) ($item->quantidade ?? 0),
                    'valor_unitario' => (float) ($item->total_unitario ?? 0),
                    'valor_total' => (float) ($item->valor_total_geral ?? 0),
                ];
            })->values()->all();

            if (! empty($itens)) {
                $asa->itens()->createMany($itens);
            }

            $asa->forceFill([
                'planilha_apresentada' => $this->gerarPlanilhaAditivo($aditivo, $asa),
            ])->saveQuietly();

            $asa = $this->normalizeMediaPaths($asa);

            $aditivo->update([
                'status_fluxo' => 'em_aprovacao_gestor',
                'justificativa_reprovacao_gestor' => null,
                'justificativa_reprovacao_orcamento' => null,
                'aprovado_gestor_por_id' => null,
                'aprovado_gestor_em' => null,
                'aprovado_orcamento_por_id' => null,
                'aprovado_orcamento_em' => null,
            ]);

            return $asa;
        });
    }

    public function sincronizarAsaComAditivo(ElaboracaoAditivo $aditivo, ?string $justificativa = null): ?Asa
    {
        $asa = Asa::query()
            ->where('elaboracao_aditivo_id', $aditivo->id)
            ->first();

        if (! $asa) {
            return null;
        }

        $aditivo->loadMissing([
            'obra',
            'gestor',
            'construtora',
            'asEscopo',
            'itens',
        ]);

        $obra = $aditivo->obra;
        $escopo = $aditivo->asEscopo;
        $valorBruto = (float) $aditivo->itens->sum('valor_total_geral');
        [$gestor, $gestorNome] = $this->resolveGestorFromAditivo($aditivo, $obra?->engenharia);
        $origemAlteracao = filled($asa->contrato) ? $asa->contrato : null;
        $numeroAsa = $this->gerarNumeroAsaEstruturado(
            siglaUnidade: $obra?->sigla,
            origemAlteracao: $origemAlteracao,
            numeroAsReferencia: $escopo?->numero_as,
            nomeUnidade: $obra?->unidade,
            escopo: $escopo?->escopo,
            fornecedor: $aditivo->construtora?->nome,
            asaIdAtual: $asa->id,
        );
        $solicitante = $this->resolveSolicitanteFromAditivo($aditivo);

        DB::transaction(function () use ($asa, $aditivo, $obra, $escopo, $valorBruto, $gestor, $gestorNome, $origemAlteracao, $numeroAsa, $justificativa, $solicitante) {
            $asa->update([
                'numero_asa' => $numeroAsa,
                'projeto_id' => $obra?->projeto_id,
                'sigla' => $obra?->sigla,
                'endereco' => $obra?->endereco,
                'contrato' => $origemAlteracao,
                'subgrupo' => $escopo?->grupo,
                'codigo_as_emitida' => $escopo?->numero_as,
                'data_solicitacao' => $aditivo->data,
                'objeto' => $escopo?->escopo ?? 'Sem escopo definido',
                'justificativa' => $justificativa ?? $aditivo->justificativa,
                'valor_bruto' => $valorBruto,
                'valor_total' => max($valorBruto - (float) ($asa->desconto ?? 0), 0),
                'evidencias' => $aditivo->anexos,
                'gestor_id' => $gestor?->id,
                'gestor_nome' => $gestorNome,
                'solicitante' => $solicitante,
                'planilha_apresentada' => $this->gerarPlanilhaAditivo($aditivo, $asa),
                'foto_antes' => $aditivo->foto_antes ?? null,
                'foto_depois' => $aditivo->foto_depois ?? null,
                'projeto_orcado' => $aditivo->projeto_orcado ?? null,
                'projeto_revisado' => $aditivo->projeto_revisado ?? null,
                'escopo_contratado' => $aditivo->escopo_contratado ?? null,
                'escopo_real' => $aditivo->escopo_real ?? null,
                'descricao' => $escopo?->escopo,
            ]);

            $itens = $aditivo->itens->map(function ($item) {
                return [
                    'item' => $item->item,
                    'descricao' => $item->descricao_servico,
                    'unidade' => $item->unidade,
                    'quantidade' => (float) ($item->quantidade ?? 0),
                    'valor_unitario' => (float) ($item->total_unitario ?? 0),
                    'valor_total' => (float) ($item->valor_total_geral ?? 0),
                ];
            })->values()->all();

            $asa->itens()->delete();

            if (! empty($itens)) {
                $asa->itens()->createMany($itens);
            }

            $this->normalizeMediaPaths($asa);
        });

        return $asa->fresh();
    }

    public function sincronizarItemAuxiliarFiscal(Asa $asa): ControleNotaFiscalAuxiliar
    {
        return DB::transaction(function () use ($asa): ControleNotaFiscalAuxiliar {
            $asa->loadMissing(['elaboracaoAditivo.obra', 'elaboracaoAditivo.construtora']);

            $obra = $asa->elaboracaoAditivo?->obra
                ?? Obras::query()->where('projeto_id', $asa->projeto_id)->first();

            if (! $obra instanceof Obras) {
                throw ValidationException::withMessages([
                    'asa' => 'Não foi possível identificar a obra da ASA.',
                ]);
            }

            $controle = ControleNotaFiscal::query()
                ->where('obra_id', $obra->id)
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->lockForUpdate()
                ->first();

            if (! $controle instanceof ControleNotaFiscal) {
                throw ValidationException::withMessages([
                    'asa' => 'Controle de Nota Fiscal de expansão não encontrado.',
                ]);
            }

            if ($controle->status === ControleNotaFiscal::STATUS_ENCERRADO) {
                throw ValidationException::withMessages([
                    'asa' => 'Controle de Nota Fiscal encerrado.',
                ]);
            }

            $auxiliar = $asa->controleNotaFiscalAuxiliar()->first()
                ?? ControleNotaFiscalAuxiliar::query()
                    ->where('controle_nota_fiscal_id', $controle->id)
                    ->where('numero_as', $asa->numero_asa)
                    ->where('numero_complemento', '')
                    ->lockForUpdate()
                    ->first();

            if ($auxiliar instanceof ControleNotaFiscalAuxiliar && $asa->notasFiscais()->exists()) {
                if ((int) $asa->controle_nota_fiscal_auxiliar_id !== (int) $auxiliar->id) {
                    $asa->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();
                }

                return $auxiliar;
            }

            $auxiliar ??= new ControleNotaFiscalAuxiliar([
                'controle_nota_fiscal_id' => $controle->id,
            ]);

            $valorTotal = (float) ($asa->valor_total ?? 0);
            $empresa = $asa->elaboracaoAditivo?->construtora?->nome
                ?: $asa->solicitante;
            $percentuaisFaturamento = $this->percentuaisFaturamentoPorAditivo($asa);

            $auxiliar->fill([
                'controle_nota_fiscal_id' => $controle->id,
                'grupo' => ControleNotaFiscalAuxiliar::normalizeGrupo($asa->subgrupo) ?? 'Solicitação Cliente',
                'numero_as' => $asa->numero_asa,
                'numero_complemento' => '',
                'escopo' => $asa->objeto,
                'empresa' => $empresa,
                'percentual_total' => 100,
                'percentual_faturamento_mao_obra' => $percentuaisFaturamento['mao_obra'] ?? 60,
                'percentual_faturamento_material' => $percentuaisFaturamento['material'] ?? 40,
                'valor_global_a' => $valorTotal,
                'total_medicao_a_menos_b' => $valorTotal,
                'valor_acumulado_medido' => 0,
                'saldo' => $valorTotal,
            ])->save();

            if ((int) $asa->controle_nota_fiscal_auxiliar_id !== (int) $auxiliar->id) {
                $asa->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();
            }

            return $auxiliar;
        });
    }

    /**
     * @return array{mao_obra: float, material: float}|null
     */
    public function percentuaisFaturamentoPorAditivo(Asa $asa): ?array
    {
        $asa->loadMissing('elaboracaoAditivo.itens');

        $itens = $asa->elaboracaoAditivo?->itens;

        if ($itens === null || $itens->isEmpty()) {
            return null;
        }

        $maoObra = 0.0;
        $material = 0.0;

        foreach ($itens as $item) {
            $quantidade = (float) ($item->quantidade ?? 0);
            $maoObra += $quantidade * (float) ($item->valor_mao_obra_unitario ?? 0);
            $material += $quantidade * (float) ($item->valor_material_unitario ?? 0);
        }

        $total = $maoObra + $material;

        if ($total <= 0) {
            return null;
        }

        $percentualMaoObra = round(($maoObra / $total) * 100, 2);

        return [
            'mao_obra' => $percentualMaoObra,
            'material' => round(100 - $percentualMaoObra, 2),
        ];
    }

    public function gerarNumeroAsaParaAsa(Asa $asa, ?string $origemAlteracao = null): string
    {
        $asa->loadMissing('projeto');

        $origem = $origemAlteracao ?? $asa->contrato;
        $sequenciaForcada = null;

        // Se a ASA foi criada sem origem, tenta preservar o sequencial ao definir a origem (SEM ORIGEM 1 -> C1/A1).
        if (filled($origemAlteracao) && filled($asa->numero_asa) && filled($asa->codigo_as_emitida)) {
            $numeroAsNormalizado = $this->normalizeNumeroAs($asa->codigo_as_emitida);
            $pattern = '/-SF-EXP-'.preg_quote($numeroAsNormalizado, '/').'-SEM\\s+ORIGEM\\s*(\\d+)-/i';

            if (preg_match($pattern, (string) $asa->numero_asa, $matches)) {
                $candidato = (int) $matches[1];

                if ($candidato > 0) {
                    $origemAscii = (string) Str::of((string) $origemAlteracao)->ascii()->lower();
                    $prefixo = str_contains($origemAscii, 'orc') ? 'C' : 'A';

                    // Evita colisao caso ja exista um numero com o mesmo sequencial no prefixo escolhido.
                    $existe = Asa::query()
                        ->where('codigo_as_emitida', $asa->codigo_as_emitida)
                        ->where('numero_asa', 'like', '%-'.$prefixo.$candidato.'-%')
                        ->when($asa->id, fn ($q) => $q->whereKeyNot($asa->id))
                        ->exists();

                    if (! $existe) {
                        $sequenciaForcada = $candidato;
                    }
                }
            }
        }

        $obra = null;

        if (filled($asa->projeto_id)) {
            // Usamos "obras" para obter unidade/sigla quando disponivel.
            $obra = Obras::query()
                ->withoutGlobalScopes()
                ->where('projeto_id', $asa->projeto_id)
                ->first();
        }

        $siglaUnidade = $asa->sigla ?: $obra?->sigla;
        $nomeUnidade = $obra?->unidade ?? $asa->projeto?->nome;

        return $this->gerarNumeroAsaEstruturado(
            siglaUnidade: $siglaUnidade,
            origemAlteracao: $origem,
            numeroAsReferencia: $asa->codigo_as_emitida,
            nomeUnidade: $nomeUnidade,
            escopo: $asa->descricao,
            fornecedor: $asa->solicitante,
            asaIdAtual: $asa->id,
            sequenciaForcada: $sequenciaForcada,
        );
    }

    protected function resolveSolicitanteFromAditivo(ElaboracaoAditivo $aditivo): string
    {
        $aditivo->loadMissing('user');

        $nomeAutor = trim((string) ($aditivo->user?->name ?? ''));

        if ($nomeAutor !== '') {
            return $nomeAutor;
        }

        if (filled($aditivo->user_id)) {
            return 'Usuario #'.$aditivo->user_id;
        }

        return 'Autor do aditivo #'.$aditivo->id;
    }

    public function normalizeMediaPaths(Asa $asa): Asa
    {
        $updates = [];

        foreach (self::MEDIA_FIELD_DIRECTORIES as $field => $directory) {
            $normalized = $this->normalizeStoredFilesToDirectory($asa->{$field}, $asa, $directory);

            if ($normalized !== $asa->{$field}) {
                $updates[$field] = $normalized;
            }
        }

        $normalizedPlanilha = $this->normalizeStoredFileToDirectory($asa->planilha_apresentada, $asa, 'planilhas');

        if ($normalizedPlanilha !== $asa->planilha_apresentada) {
            $updates['planilha_apresentada'] = $normalizedPlanilha;
        }

        if ($updates !== []) {
            $asa->forceFill($updates)->saveQuietly();
        }

        return $asa->fresh();
    }

    protected function gerarPlanilhaAditivo(ElaboracaoAditivo $aditivo, Asa $asa): ?string
    {
        $path = 'asa/'.$asa->id.'/planilhas/aditivo-'.$aditivo->id.'.xlsx';

        Excel::store(
            new ElaboracaoAditivoPlanilhaExport($aditivo->id),
            $path,
            (string) config('filesystems.media_disk', 'r2')
        );

        return $path;
    }

    private function normalizeStoredFilesToDirectory(mixed $originalValue, Asa $asa, string $directory): mixed
    {
        $files = $this->normalizeFiles($originalValue);

        if ($files === []) {
            return $originalValue;
        }

        $normalized = array_map(
            fn (mixed $file) => $this->moveStoredPathToDirectory($file, $asa, $directory),
            $files,
        );

        return $this->restoreOriginalFormat($originalValue, $normalized);
    }

    private function normalizeStoredFileToDirectory(mixed $originalValue, Asa $asa, string $directory): mixed
    {
        if (blank($originalValue)) {
            return $originalValue;
        }

        return $this->moveStoredPathToDirectory($originalValue, $asa, $directory);
    }

    private function moveStoredPathToDirectory(mixed $file, Asa $asa, string $directory): mixed
    {
        $sourcePath = $this->extractPath($file);

        if (! $sourcePath) {
            return $file;
        }

        $targetPath = 'asa/'.$asa->id.'/'.$directory.'/'.basename($sourcePath);

        if ($sourcePath === $targetPath) {
            return $targetPath;
        }

        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

        if (! $disk->exists($sourcePath)) {
            return $file;
        }

        if (! $disk->exists($targetPath)) {
            $stream = $disk->readStream($sourcePath);

            if ($stream === false) {
                return $file;
            }

            try {
                $disk->writeStream($targetPath, $stream, ['visibility' => 'public']);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return $disk->exists($targetPath) ? $targetPath : $file;
    }

    private function normalizeFiles(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => ! blank($item)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => ! blank($item)));
            }

            return trim($value) !== '' ? [$value] : [];
        }

        return [];
    }

    private function extractPath(mixed $file): ?string
    {
        if (is_string($file)) {
            $path = parse_url($file, PHP_URL_PATH) ?: $file;

            return ltrim((string) $path, '/');
        }

        if (is_array($file)) {
            $candidate = $file['path'] ?? $file['url'] ?? $file[0] ?? null;

            if (! is_string($candidate) || trim($candidate) === '') {
                return null;
            }

            $path = parse_url($candidate, PHP_URL_PATH) ?: $candidate;

            return ltrim((string) $path, '/');
        }

        return null;
    }

    private function restoreOriginalFormat(mixed $originalValue, array $files): mixed
    {
        if (is_array($originalValue)) {
            return $files;
        }

        if (is_string($originalValue)) {
            $decoded = json_decode($originalValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $files;
            }

            return $files[0] ?? null;
        }

        return $files;
    }

    protected function resolveGestor(?string $nomeGestor): array
    {
        if (blank($nomeGestor)) {
            return [null, null];
        }

        $gestorNome = trim($nomeGestor);

        $gestor = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($gestorNome)])
            ->first();

        return [$gestor, $gestorNome];
    }

    protected function resolveGestorFromAditivo(ElaboracaoAditivo $aditivo, ?string $nomeGestorFallback): array
    {
        if ($aditivo->gestor) {
            return [$aditivo->gestor, $aditivo->gestor->name];
        }

        return $this->resolveGestor($nomeGestorFallback);
    }

    protected function gerarNumeroAsaEstruturado(
        ?string $siglaUnidade,
        ?string $origemAlteracao,
        ?string $numeroAsReferencia,
        ?string $nomeUnidade,
        ?string $escopo,
        ?string $fornecedor,
        ?int $asaIdAtual = null,
        ?int $sequenciaForcada = null,
    ): string {
        $sigla = $this->normalizeReadableSegment($siglaUnidade, 'SEM SIGLA');
        // Normaliza para ascii para tratar "Orçamentos" / "Orcamentos" da mesma forma.
        $origemAscii = (string) Str::of((string) $origemAlteracao)->ascii()->lower();
        $numeroAsRaw = trim((string) ($numeroAsReferencia ?? ''));
        $numeroAs = $this->normalizeNumeroAs($numeroAsReferencia);
        $nome = $this->normalizeReadableSegment($nomeUnidade, 'SEM UNIDADE');
        $escopoSegment = $this->normalizeReadableSegment($escopo, 'SEM ESCOPO');
        $fornecedorSegment = $this->normalizeReadableSegment($fornecedor, 'SEM FORNECEDOR');

        $prefixoSequencia = null;
        $sequencia = 1;

        if (filled($origemAlteracao)) {
            $prefixoSequencia = str_contains($origemAscii, 'orc') ? 'C' : 'A';
            $sequencia = $sequenciaForcada ?: $this->proximaSequencia($numeroAsRaw, $prefixoSequencia, $asaIdAtual);
        } else {
            // Antes de definir a origem, nao usamos prefixo (A/C).
            $sequencia = $sequenciaForcada ?: $this->proximaSequenciaSemPrefixo($numeroAsRaw, $asaIdAtual);
        }

        $sequenciaSegmento = filled($prefixoSequencia)
            ? ($prefixoSequencia.$sequencia)
            : ('SEM ORIGEM '.$sequencia);

        return implode('-', [
            $sigla,
            'SF',
            'EXP',
            $numeroAs,
            $sequenciaSegmento,
            $nome,
            $escopoSegment,
            $fornecedorSegment,
        ]);
    }

    protected function proximaSequencia(string $numeroAsRaw, string $prefixo, ?int $asaIdAtual = null): int
    {
        $query = Asa::query()
            ->where('codigo_as_emitida', $numeroAsRaw)
            ->where('numero_asa', 'like', '%-'.$prefixo.'%');

        if ($asaIdAtual) {
            $query->whereKeyNot($asaIdAtual);
        }

        $max = 0;

        foreach ($query->pluck('numero_asa') as $numeroAsa) {
            if (preg_match('/-'.preg_quote($prefixo, '/').'(\d+)-/', (string) $numeroAsa, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    protected function proximaSequenciaSemPrefixo(string $numeroAsRaw, ?int $asaIdAtual = null): int
    {
        $query = Asa::query()
            ->where('codigo_as_emitida', $numeroAsRaw);

        if ($asaIdAtual) {
            $query->whereKeyNot($asaIdAtual);
        }

        $max = 0;
        $numeroAsNormalizado = $this->normalizeNumeroAs($numeroAsRaw);

        foreach ($query->pluck('numero_asa') as $numeroAsa) {
            $numeroAsa = (string) $numeroAsa;

            // Novo formato sem origem: "...-123-SEM ORIGEM 2-UNIDADE-..."
            $patternSemOrigem = '/-SF-EXP-'.preg_quote($numeroAsNormalizado, '/').'-SEM\\s+ORIGEM\\s*(\\d+)-/i';

            if (preg_match($patternSemOrigem, $numeroAsa, $matches)) {
                $max = max($max, (int) $matches[1]);

                continue;
            }

            // Legado: "...-123-2-UNIDADE-..."
            $patternLegado = '/-SF-EXP-'.preg_quote($numeroAsNormalizado, '/').'-(\\d+)-/i';

            if (preg_match($patternLegado, $numeroAsa, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    protected function normalizeReadableSegment(?string $value, string $fallback): string
    {
        if (blank($value)) {
            return $fallback;
        }

        $normalized = (string) Str::of($value)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return $normalized !== '' ? $normalized : $fallback;
    }

    protected function normalizeNumeroAs(?string $value): string
    {
        if (blank($value)) {
            return 'SEM AS';
        }

        $normalized = (string) Str::of($value)
            ->replaceMatches('/[^0-9.]+/', '')
            ->trim()
            ->value();

        if ($normalized !== '') {
            return $normalized;
        }

        return (string) Str::of($value)->replaceMatches('/\s+/', ' ')->trim()->value();
    }
}
