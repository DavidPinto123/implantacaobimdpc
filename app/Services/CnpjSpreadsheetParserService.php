<?php

namespace App\Services;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Projeto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CnpjSpreadsheetParserService
{
    private array $enumColumns = [
        'status_cnpj' => [
            'CNPJ DEFINITIVO' => 'definitivo',
            'CNPJ PROVISORIO' => 'provisorio',
            'CNPJ PROVISÓRIO' => 'provisorio',
        ],
    ];

    private array $headerMap = [
        'sigla antiga' => 'sigla_antiga',
        'nova sigla' => 'nova_sigla',
        'unidade' => 'unidade',
        'cnpj' => 'cnpj',
        'status cnpj' => 'status_cnpj',
        'uf' => 'uf',
        'cidade' => 'cidade',
        'empresa' => 'empresa',
    ];

    public function getSheetNames(string $filePath): array
    {
        return IOFactory::load($filePath)->getSheetNames();
    }

    public function analyzeSheet(string $filePath, string|int $sheet = 0, int $previewLimit = 5): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet((int) $sheet);

        if (! $worksheet) {
            return ['headerRow' => 1, 'headers' => [], 'preview' => [], 'sampleValues' => [], 'columnMap' => []];
        }

        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $maxRowsDetect = min($worksheet->getHighestRow(), 10);

        $headerRow = 1;
        $bestCount = 0;
        for ($row = 1; $row <= $maxRowsDetect; $row++) {
            $filledCount = 0;
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && trim((string) $value) !== '') {
                    $filledCount++;
                }
            }
            if ($filledCount > $bestCount) {
                $bestCount = $filledCount;
                $headerRow = $row;
            }
        }

        $headers = [];
        $columnMap = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $worksheet->getCellByColumnAndRow($col, $headerRow)->getValue();
            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $displayName = trim((string) $value);
            $original = $displayName;
            $suffix = 2;
            while (isset($columnMap[$displayName])) {
                $displayName = $original." ({$suffix})";
                $suffix++;
            }

            $headers[] = $displayName;
            $columnMap[$displayName] = $col;
        }

        $preview = [];
        $sampleValues = array_fill_keys($headers, []);
        $dataStartRow = $headerRow + 1;
        $sampleScanLimit = min($worksheet->getHighestRow(), $dataStartRow + 30);

        for ($row = $dataStartRow; $row <= $sampleScanLimit; $row++) {
            $rowData = [];
            $hasData = false;

            foreach ($headers as $header) {
                $col = $columnMap[$header];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $strVal = $value !== null ? trim((string) $value) : '';
                $rowData[$header] = $strVal;
                if ($strVal !== '') {
                    $hasData = true;
                }
                if ($strVal !== '' && count($sampleValues[$header]) < 3 && ! in_array($strVal, $sampleValues[$header], true)) {
                    $sampleValues[$header][] = $strVal;
                }
            }

            if ($hasData && count($preview) < $previewLimit) {
                $preview[] = $rowData;
            }
        }

        return [
            'headerRow' => $headerRow,
            'headers' => $headers,
            'preview' => $preview,
            'sampleValues' => $sampleValues,
            'columnMap' => $columnMap,
        ];
    }

    public function suggestMapping(array $headers): array
    {
        $mapping = [];
        foreach ($headers as $header) {
            $normalized = str_replace('_', ' ', $this->normalizeHeader($header));
            $mapping[$header] = $this->headerMap[$normalized] ?? '';
        }

        return $mapping;
    }

    public function parseRows(string $filePath, string|int $sheet, array $mapping, ?int $headerRow = null, array $customValueMap = [], array $columnMap = []): Collection
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet((int) $sheet);

        if (! $worksheet) {
            return collect();
        }

        $analysis = $headerRow && $columnMap !== []
            ? ['headerRow' => $headerRow, 'columnMap' => $columnMap]
            : $this->analyzeSheet($filePath, $sheet);

        $headerRow = $analysis['headerRow'];
        $columnMap = $analysis['columnMap'];
        $highestRow = $worksheet->getHighestRow();

        $rows = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $item = ['linha' => $row];
            $hasData = false;

            foreach ($mapping as $header => $dbField) {
                if (! is_string($dbField) || $dbField === '' || ! isset($columnMap[$header])) {
                    continue;
                }

                $col = $columnMap[$header];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $strVal = $value !== null ? trim((string) $value) : '';

                if ($strVal !== '') {
                    $hasData = true;
                }

                if (isset($customValueMap[$dbField][$strVal]) && $customValueMap[$dbField][$strVal] !== '') {
                    $strVal = $customValueMap[$dbField][$strVal];
                }

                $item[$dbField] = $strVal;
            }

            if ($hasData) {
                $rows[] = $item;
            }
        }

        return collect($rows);
    }

    public function prepareRows(
        string $filePath,
        string|int $sheet,
        array $mapping,
        ?int $headerRow = null,
        array $customValueMap = [],
        array $columnMap = [],
        array $rowOverrides = [],
    ): Collection {
        return $this->parseRows($filePath, $sheet, $mapping, $headerRow, $customValueMap, $columnMap)
            ->map(fn (array $row): array => $this->prepareRow($row, $rowOverrides[$row['linha']] ?? []))
            ->values();
    }

    public function prepareRetryRows(array $rows, array $rowOverrides = []): Collection
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->prepareRow($row, $rowOverrides[$row['linha'] ?? 0] ?? []))
            ->values();
    }

    public function prepareRow(array $row, array $overrides = []): array
    {
        $errors = [];
        $resolvedProjeto = isset($row['projeto_id']) ? Projeto::find($row['projeto_id']) : null;
        $resolvedProjeto ??= $this->resolveProjeto($row);
        $estado = $this->resolveEstado($row['uf'] ?? null);
        $cidade = $estado instanceof Estado ? $this->resolveCidade($row['cidade'] ?? null, $estado->id) : null;
        $normalizedStatus = $this->normalizeStatus($row['status_cnpj'] ?? null);
        $formattedCnpj = $this->formatCnpj($this->normalizeDigits($row['cnpj'] ?? ($row['cnpj_formatado'] ?? null)));

        if (! $resolvedProjeto instanceof Projeto) {
            $errors[] = 'Projeto não encontrado';
        }

        if ($normalizedStatus === null) {
            $errors[] = 'Status do CNPJ inválido';
        }

        if ($formattedCnpj === null || ! $this->isValidCnpj($formattedCnpj)) {
            $errors[] = 'CNPJ inválido';
        }

        if ($formattedCnpj !== null && $resolvedProjeto instanceof Projeto) {
            $duplicateProjeto = $this->findProjetoByCnpjConflict($formattedCnpj, $resolvedProjeto->id);

            if ($duplicateProjeto instanceof Projeto) {
                $errors[] = 'CNPJ já vinculado a outro projeto';
            }
        }

        $preparedRow = [
            'linha' => $row['linha'],
            'projeto_id' => $resolvedProjeto?->id,
            'projeto_label' => $resolvedProjeto ? $this->formatProjetoLabel($resolvedProjeto) : null,
            'nova_sigla' => $this->firstFilledString($row['nova_sigla'] ?? null, $resolvedProjeto?->nova_sigla),
            'sigla_antiga' => $this->firstFilledString($row['sigla_antiga'] ?? null, $resolvedProjeto?->sigla_antiga),
            'cnpj_formatado' => $formattedCnpj,
            'status_cnpj' => $normalizedStatus,
            'uf' => $row['uf'] ?? '',
            'cidade_nome' => $row['cidade'] ?? ($row['cidade_nome'] ?? ''),
            'empresa' => $row['empresa'] ?? '',
            'unidade' => $row['unidade'] ?? '',
            'pais_id' => $estado?->pais_id ?? $resolvedProjeto?->pais_id,
            'estado_id' => $estado?->id ?? $resolvedProjeto?->estado_id,
            'cidade_id' => $cidade?->id ?? $resolvedProjeto?->cidade_id,
            'errors' => $errors,
        ];

        $preparedRow = $this->applyPreparedOverrides($preparedRow, $overrides);
        $preparedRow['resolved'] = $this->isRowResolved($preparedRow);

        if ($preparedRow['resolved']) {
            $preparedRow['errors'] = [];
        }

        return $preparedRow;
    }

    public function isRowResolved(array $row): bool
    {
        return filled($row['projeto_id'] ?? null)
            && filled($row['status_cnpj'] ?? null)
            && filled($row['cnpj_formatado'] ?? null)
            && filled($row['estado_id'] ?? null);
    }

    public function applyConflictResolutions(array $rows, array $resolucoes): array
    {
        foreach ($rows as &$row) {
            $projeto = Projeto::find($row['projeto_id'] ?? null);
            $conflictKey = $this->buildConflictKey($row, $projeto);
            $decisoes = $conflictKey ? ($resolucoes[$conflictKey] ?? []) : [];

            if (! $projeto instanceof Projeto) {
                continue;
            }

            foreach ($decisoes as $campo => $decisao) {
                if ($decisao !== 'manter') {
                    continue;
                }

                $row[$campo] = $projeto->{$campo};

                if (in_array($campo, ['cnpj', 'cnpj_provisorio'], true)) {
                    $row['cnpj_formatado'] = $projeto->{$campo};
                }
            }
        }

        return $rows;
    }

    public function normalizeFormattedCnpj(?string $value): ?string
    {
        return $this->formatCnpj($this->normalizeDigits($value));
    }

    public function isValidCnpj(string $cnpj): bool
    {
        $digits = preg_replace('/\D/', '', $cnpj) ?? '';

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $calculateDigit = static function (string $base, array $weights): int {
            $sum = 0;

            foreach ($weights as $index => $weight) {
                $sum += ((int) $base[$index]) * $weight;
            }

            $remainder = $sum % 11;

            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        $firstDigit = $calculateDigit(substr($digits, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $calculateDigit(substr($digits, 0, 12).$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $digits[12] === (string) $firstDigit && $digits[13] === (string) $secondDigit;
    }

    public function buildProjetoUpdatePayload(array $dados): array
    {
        $cidadeId = $dados['cidade_id'] ?? null;
        $cidadeNome = trim((string) ($dados['cidade_nome'] ?? ''));
        $estadoId = $dados['estado_id'] ?? null;

        if (! $cidadeId && $cidadeNome !== '' && $estadoId) {
            $cidade = Cidade::firstOrCreate([
                'estado_id' => $estadoId,
                'nome' => $cidadeNome,
            ]);

            $cidadeId = $cidade->id;
        }

        return [
            'nova_sigla' => ($dados['nova_sigla'] ?? '') ?: null,
            'sigla_antiga' => ($dados['sigla_antiga'] ?? '') ?: null,
            'status_cnpj' => ($dados['status_cnpj'] ?? '') ?: null,
            'pais_id' => $dados['pais_id'] ?? null,
            'estado_id' => $estadoId,
            'cidade_id' => $cidadeId,
            'cnpj' => ($dados['status_cnpj'] ?? null) === 'definitivo' ? (($dados['cnpj_formatado'] ?? '') ?: null) : null,
            'cnpj_provisorio' => ($dados['status_cnpj'] ?? null) === 'provisorio' ? (($dados['cnpj_formatado'] ?? '') ?: null) : null,
        ];
    }

    public function shouldClassifyAsProjetoNaoCriado(array $row): bool
    {
        return filled($row['nova_sigla'] ?? null)
            && filled($row['unidade'] ?? null)
            && (filled($row['cidade_nome'] ?? null) || filled($row['uf'] ?? null));
    }

    public function getAvailableFields(): array
    {
        return ['sigla_antiga', 'nova_sigla', 'unidade', 'cnpj', 'status_cnpj', 'uf', 'cidade', 'empresa'];
    }

    public function getFieldLabels(): array
    {
        return [
            'sigla_antiga' => 'Sigla Antiga',
            'nova_sigla' => 'Nova Sigla',
            'unidade' => 'Unidade',
            'cnpj' => 'CNPJ',
            'status_cnpj' => 'Status do CNPJ',
            'uf' => 'UF',
            'cidade' => 'Cidade',
            'empresa' => 'Empresa',
        ];
    }

    public function getEnumColumns(): array
    {
        return $this->enumColumns;
    }

    public function getEnumOptionsForField(string $field): array
    {
        return array_values(array_unique(array_values($this->enumColumns[$field] ?? [])));
    }

    public function detectAllUniqueValues(string $filePath, string|int $sheet, array $headerNames, ?int $headerRow = null, int $limit = 50, array $columnMap = []): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = is_string($sheet)
            ? $spreadsheet->getSheetByName($sheet)
            : $spreadsheet->getSheet((int) $sheet);

        if (! $worksheet) {
            return [];
        }

        $analysis = $headerRow && $columnMap !== []
            ? ['headerRow' => $headerRow, 'columnMap' => $columnMap]
            : $this->analyzeSheet($filePath, $sheet);

        $headerRow = $analysis['headerRow'];
        $columnMap = $analysis['columnMap'];
        $highestRow = min($worksheet->getHighestRow(), $headerRow + 200);
        $values = [];

        foreach ($headerNames as $header) {
            $values[$header] = [];
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            foreach ($headerNames as $header) {
                if (! isset($columnMap[$header])) {
                    continue;
                }

                $col = $columnMap[$header];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $strVal = $value !== null ? trim((string) $value) : '';
                if ($strVal === '') {
                    continue;
                }
                $values[$header][$strVal] = ($values[$header][$strVal] ?? 0) + 1;
            }
        }

        foreach ($values as $header => $map) {
            arsort($map);
            $values[$header] = array_slice($map, 0, $limit, true);
        }

        return $values;
    }

    public function resolveProjeto(array $row): ?Projeto
    {
        $normalizedCnpj = $this->normalizeDigits($row['cnpj'] ?? ($row['cnpj_formatado'] ?? null));

        $strategies = [
            fn (): ?Projeto => $this->findUniqueProjetoByCnpj($normalizedCnpj),
            fn (): ?Projeto => $this->findUniqueProjeto('codigo', $row['nova_sigla'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('codigo', $row['sigla_antiga'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('codigo', $row['unidade'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('nova_sigla', $row['nova_sigla'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('sigla', $row['nova_sigla'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('sigla_antiga', $row['sigla_antiga'] ?? null),
            fn (): ?Projeto => $this->findByNomeAndMarca($row['unidade'] ?? null, $row['empresa'] ?? null),
            fn (): ?Projeto => $this->findUniqueProjeto('nome', $row['unidade'] ?? null),
        ];

        foreach ($strategies as $strategy) {
            $projeto = $strategy();

            if ($projeto instanceof Projeto) {
                return $projeto;
            }
        }

        return null;
    }

    public function resolveEstado(?string $uf): ?Estado
    {
        $uf = trim((string) $uf);

        if ($uf === '') {
            return null;
        }

        $ufUpper = mb_strtoupper($uf);

        return Estado::query()
            ->where('uf', $ufUpper)
            ->orWhereRaw('LOWER(nome) = ?', [mb_strtolower($uf)])
            ->first();
    }

    public function resolveCidade(?string $cidade, int $estadoId): ?Cidade
    {
        $cidade = trim((string) $cidade);

        if ($cidade === '') {
            return null;
        }

        return Cidade::query()
            ->where('estado_id', $estadoId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($cidade)])
            ->first();
    }

    public function normalizeStatus(?string $status): ?string
    {
        $normalized = mb_strtoupper(trim((string) $status));

        return match ($normalized) {
            'CNPJ DEFINITIVO', 'DEFINITIVO' => 'definitivo',
            'CNPJ PROVISORIO', 'CNPJ PROVISÓRIO', 'PROVISORIO', 'PROVISÓRIO' => 'provisorio',
            default => null,
        };
    }

    public function normalizeDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public function formatCnpj(string $cnpj): ?string
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

    private function applyPreparedOverrides(array $preparedRow, array $overrides): array
    {
        foreach ($overrides as $campo => $valor) {
            if ($campo === 'errors' || $campo === 'resolved' || $campo === 'projeto_label') {
                continue;
            }

            $preparedRow[$campo] = $valor;
        }

        return $preparedRow;
    }

    private function findUniqueProjeto(string $column, ?string $value): ?Projeto
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0' || $value === '-') {
            return null;
        }

        $projetos = Projeto::query()->where($column, $value)->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function findUniqueProjetoByCnpj(string $cnpj): ?Projeto
    {
        if ($cnpj === '' || strlen($cnpj) !== 14) {
            return null;
        }

        $formattedCnpj = $this->formatCnpj($cnpj);

        if ($formattedCnpj === null) {
            return null;
        }

        $projetos = Projeto::query()
            ->where('cnpj', $formattedCnpj)
            ->orWhere('cnpj_provisorio', $formattedCnpj)
            ->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function findProjetoByCnpjConflict(string $formattedCnpj, ?int $ignoreProjetoId = null): ?Projeto
    {
        return Projeto::query()
            ->when($ignoreProjetoId, fn ($query, $projetoId) => $query->where('id', '!=', $projetoId))
            ->where(function ($query) use ($formattedCnpj): void {
                $query
                    ->where('cnpj', $formattedCnpj)
                    ->orWhere('cnpj_provisorio', $formattedCnpj);
            })
            ->first();
    }

    private function findByNomeAndMarca(?string $nome, ?string $marca): ?Projeto
    {
        $nome = trim((string) $nome);
        $marca = trim((string) $marca);

        if ($nome === '' || $nome === '0' || $nome === '-') {
            return null;
        }

        $query = Projeto::query()->where('nome', $nome);

        if ($marca !== '' && $marca !== '0' && $marca !== '-') {
            $query->where('marca', $marca);
        }

        $projetos = $query->get();

        return $projetos->count() === 1 ? $projetos->first() : null;
    }

    private function formatProjetoLabel(Projeto $projeto): string
    {
        return trim(collect([
            $projeto->nome,
            $projeto->codigo,
            $projeto->nova_sigla,
        ])->filter()->implode(' • '));
    }

    private function buildConflictKey(array $row, ?Projeto $projeto = null): ?string
    {
        $projetoId = $row['projeto_id'] ?? $projeto?->id;

        if (filled($projetoId)) {
            return 'projeto:'.$projetoId;
        }

        $fallback = collect([
            $row['nova_sigla'] ?? null,
            $row['sigla_antiga'] ?? null,
            $row['projeto_label'] ?? null,
        ])->first(fn ($value) => filled($value));

        return filled($fallback) ? 'codigo:'.$fallback : null;
    }

    private function firstFilledString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeHeader(string $header): string
    {
        return (string) Str::of($header)
            ->replace("\xEF\xBB\xBF", '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }
}
