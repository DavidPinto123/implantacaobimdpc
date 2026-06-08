<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Projeto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

final class ProjetoCnpjCsvSeeder extends Seeder
{
    private const CSV_FILE = 'PLANILHA DE CNPJ PARA EMISSÃO DE NOTAS FISCAIS.xlsx - CNPJ.csv';

    /**
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        'sigla_antiga',
        'nova_sigla',
        'unidade',
        'cnpj',
        'status_cnpj',
        'uf',
        'cidade',
        'empresa',
    ];

    public function run(): void
    {
        $rows = $this->readRows();

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $line => $row) {
            $projeto = $this->resolveProjeto($row);

            if (! $projeto instanceof Projeto) {
                $skipped++;
                $this->command?->warn("Linha {$line}: projeto não encontrado para nova sigla '{$row['nova_sigla']}' / unidade '{$row['unidade']}'.");

                continue;
            }

            $payload = $this->buildPayload($row);

            if ($payload === []) {
                $skipped++;
                $this->command?->warn("Linha {$line}: status ou CNPJ inválido para o projeto {$projeto->nome}.");

                continue;
            }

            $projeto->update($payload);
            $updated++;
        }

        $this->command?->info("ProjetoCnpjCsvSeeder concluído: {$updated} projeto(s) atualizados, {$skipped} linha(s) ignoradas.");
    }

    /**
     * @return Collection<int, array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}>
     */
    private function readRows(): Collection
    {
        $filePath = base_path(self::CSV_FILE);

        if (! is_file($filePath)) {
            throw new RuntimeException("CSV não encontrado em: {$filePath}");
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir o CSV: {$filePath}");
        }

        $headers = null;
        $rows = [];
        $lineNumber = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if (! is_array($row)) {
                    continue;
                }

                $row = $this->trimTrailingEmptyColumns($row);

                if ($headers === null) {
                    $normalizedHeader = $this->normalizeHeaderRow($row);

                    if ($this->isExpectedHeaderRow($normalizedHeader)) {
                        $headers = $normalizedHeader;
                    }

                    continue;
                }

                $mappedRow = $this->mapRow($headers, $row);

                if ($this->isEmptyRow($mappedRow)) {
                    continue;
                }

                $rows[$lineNumber] = $mappedRow;
            }
        } finally {
            fclose($handle);
        }

        if ($headers === null) {
            throw new RuntimeException('Cabeçalho esperado não encontrado no CSV de CNPJs.');
        }

        /** @var Collection<int, array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}> $collection */
        $collection = collect($rows);

        return $collection;
    }

    /**
     * @param  list<string|null>  $row
     * @return list<string>
     */
    private function trimTrailingEmptyColumns(array $row): array
    {
        while ($row !== [] && $this->normalizeValue(end($row)) === '') {
            array_pop($row);
        }

        return array_map(fn (mixed $value): string => $this->normalizeValue($value), $row);
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function normalizeHeaderRow(array $row): array
    {
        return array_map(function (string $value): string {
            return (string) Str::of($value)
                ->replace("\xEF\xBB\xBF", '')
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_');
        }, $row);
    }

    /**
     * @param  list<string>  $headers
     */
    private function isExpectedHeaderRow(array $headers): bool
    {
        return array_values(array_intersect($headers, self::REQUIRED_HEADERS)) === self::REQUIRED_HEADERS;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $row
     * @return array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}
     */
    private function mapRow(array $headers, array $row): array
    {
        $data = array_fill_keys(self::REQUIRED_HEADERS, '');

        foreach (self::REQUIRED_HEADERS as $header) {
            $index = array_search($header, $headers, true);
            $data[$header] = $index === false ? '' : ($row[$index] ?? '');
        }

        /** @var array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string} $data */
        return $data;
    }

    /**
     * @param  array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn (string $value): bool => $value === '');
    }

    private function normalizeValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function isMeaningful(?string $value): bool
    {
        $normalized = $this->normalizeValue($value);

        return $normalized !== '' && $normalized !== '-' && $normalized !== '0';
    }

    private function normalizeStatus(?string $status): ?string
    {
        $normalized = mb_strtoupper($this->normalizeValue($status));

        return match ($normalized) {
            'CNPJ DEFINITIVO' => 'definitivo',
            'CNPJ PROVISORIO', 'CNPJ PROVISÓRIO' => 'provisorio',
            default => null,
        };
    }

    private function normalizeCnpj(?string $cnpj): string
    {
        return preg_replace('/\D/', '', (string) $cnpj) ?? '';
    }

    private function formatCnpj(string $cnpj): ?string
    {
        if (strlen($cnpj) !== 14) {
            return null;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2),
        );
    }

    /**
     * @param  array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}  $row
     */
    private function resolveProjeto(array $row): ?Projeto
    {
        $strategies = [
            fn (): ?Projeto => $this->findUniqueProjeto('nova_sigla', $row['nova_sigla']),
            fn (): ?Projeto => $this->findUniqueProjeto('sigla', $row['nova_sigla']),
            fn (): ?Projeto => $this->findUniqueProjeto('sigla_antiga', $row['sigla_antiga']),
            fn (): ?Projeto => $this->findByNomeAndMarca($row['unidade'], $row['empresa']),
            fn (): ?Projeto => $this->findUniqueProjeto('nome', $row['unidade']),
        ];

        foreach ($strategies as $strategy) {
            $projeto = $strategy();

            if ($projeto instanceof Projeto) {
                return $projeto;
            }
        }

        return null;
    }

    private function findUniqueProjeto(string $column, ?string $value): ?Projeto
    {
        if (! $this->isMeaningful($value)) {
            return null;
        }

        $projetos = Projeto::withTrashed()
            ->where($column, $this->normalizeValue($value))
            ->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function findByNomeAndMarca(?string $nome, ?string $marca): ?Projeto
    {
        if (! $this->isMeaningful($nome)) {
            return null;
        }

        $query = Projeto::withTrashed()->where('nome', $this->normalizeValue($nome));

        if ($this->isMeaningful($marca)) {
            $query->where('marca', $this->normalizeValue($marca));
        }

        $projetos = $query->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    /**
     * @param  array{sigla_antiga:string,nova_sigla:string,unidade:string,cnpj:string,status_cnpj:string,uf:string,cidade:string,empresa:string}  $row
     * @return array{sigla_antiga:?string,cnpj:?string,cnpj_provisorio:?string,status_cnpj:?string}|array{}
     */
    private function buildPayload(array $row): array
    {
        $status = $this->normalizeStatus($row['status_cnpj']);
        $cnpj = $this->formatCnpj($this->normalizeCnpj($row['cnpj']));

        if ($status === null || $cnpj === null) {
            return [];
        }

        return [
            'sigla_antiga' => $this->isMeaningful($row['sigla_antiga']) ? $this->normalizeValue($row['sigla_antiga']) : null,
            'cnpj' => $status === 'definitivo' ? $cnpj : null,
            'cnpj_provisorio' => $status === 'provisorio' ? $cnpj : null,
            'status_cnpj' => $status,
        ];
    }
}
